<?php
namespace App\Workers;

use App\Workers\Handlers\TaskHandler;

/**
 * WorkerRunner — core logic for #76 (v6.1 sprint).
 *
 * Executed once per HTTP cron tick (cPanel cron at 1-min interval).
 * Processes AT MOST ONE task per invocation — predictable, observable,
 * cPanel-CPU-time safe.
 *
 * Pipeline per tick:
 *   1. reapStaleLocks()    — recover tasks locked > 10 min (worker crashed)
 *   2. claimNextTask()     — atomic SELECT FOR UPDATE → set status='locked'
 *   3. dispatch handler    — run task; record success or failure
 *   4. emit JSON status    — for cron log observability
 *
 * Concurrency safety:
 *   - claimNextTask() runs inside a transaction with SELECT FOR UPDATE
 *   - Two concurrent crons claiming the same row: 2nd blocks until 1st commits,
 *     then sees status='locked' and picks a different row (or no row)
 *
 * Multi-tenant safety:
 *   - Every claimed row carries company_id (NOT NULL in schema)
 *   - Worker passes company_id to handler via $context — handler is responsible
 *     for using it in any queries (worker can't enforce per-handler queries)
 */
class WorkerRunner
{
    /**
     * task_type → handler-class registry.
     * Adding a new task type: one line below + create the handler class.
     */
    private const HANDLERS = [
        'echo' => Handlers\EchoHandler::class,
        // future: 'send_email' => Handlers\SendEmailHandler::class,
        // future: 'generate_pdf_invoice' => Handlers\GeneratePdfInvoiceHandler::class,
        // future: 'sync_channel_inventory' => Handlers\SyncChannelInventoryHandler::class,
    ];

    /** Backoff schedule (seconds). attempts=1→60s, 2→300s, 3→1500s, 4→7200s, then dead-letter. */
    private const BACKOFF_SECONDS = [60, 300, 1500, 7200];

    /** Stale-lock threshold: rows locked longer than this get reset to pending. */
    private const STALE_LOCK_MINUTES = 10;

    /** Hard ceiling on max_attempts no matter what handler declares (anti-footgun, see #77 EC1). */
    private const ABSOLUTE_MAX_ATTEMPTS = 20;

    /** Max bytes stored in task_queue.last_error (full error goes in task_results). */
    private const LAST_ERROR_TRUNCATE = 4096;

    private \mysqli $conn;
    private string $workerId;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        // Identifier for debugging "who locked this task" — matches PM spec locked_by
        $this->workerId = 'cpanel-php-' . getmypid() . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    /**
     * Run one tick. Returns a status array suitable for json_encode.
     */
    public function run(): array
    {
        $startedAt = microtime(true);

        // Hard timeout — cPanel default 60s, leave buffer for cleanup (PM spec AC10)
        @set_time_limit(50);

        try {
            $reaped = $this->reapStaleLocks();

            $task = $this->claimNextTask();
            if ($task === null) {
                return [
                    'picked'      => null,
                    'status'      => 'none',
                    'stale_reaped' => $reaped,
                    'duration_ms' => intval((microtime(true) - $startedAt) * 1000),
                ];
            }

            $outcome = $this->dispatch($task);

            return [
                'picked'      => intval($task['id']),
                'task_type'   => $task['task_type'],
                'status'      => $outcome['status'],
                'stale_reaped' => $reaped,
                'duration_ms' => intval((microtime(true) - $startedAt) * 1000),
            ];
        } catch (\Throwable $e) {
            // Worker-level error (NOT handler-level). Log and report.
            error_log('[WorkerRunner] fatal: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'picked'      => null,
                'status'      => 'worker_error',
                'error'       => $e->getMessage(),
                'duration_ms' => intval((microtime(true) - $startedAt) * 1000),
            ];
        }
    }

    /**
     * Reset rows locked too long back to pending (worker crashed mid-execution).
     * Idempotent — running every tick is fine.
     * Returns number of rows reaped.
     */
    private function reapStaleLocks(): int
    {
        $minutes = self::STALE_LOCK_MINUTES;
        $sql = "UPDATE task_queue
                   SET status = 'pending',
                       locked_at = NULL,
                       locked_by = NULL,
                       last_error = CONCAT(COALESCE(last_error, ''), '\n[reaped at ', NOW(), ' after stale lock]')
                 WHERE status = 'locked'
                   AND locked_at IS NOT NULL
                   AND locked_at < (NOW() - INTERVAL $minutes MINUTE)
                   AND deleted_at IS NULL";
        if (mysqli_query($this->conn, $sql)) {
            return mysqli_affected_rows($this->conn);
        }
        error_log('[WorkerRunner] reapStaleLocks failed: ' . mysqli_error($this->conn));
        return 0;
    }

    /**
     * Atomically claim the next eligible task.
     * Returns associative-array row or null if none available.
     */
    private function claimNextTask(): ?array
    {
        // Use a transaction so SELECT FOR UPDATE actually locks the row.
        if (!mysqli_begin_transaction($this->conn)) {
            error_log('[WorkerRunner] begin_transaction failed: ' . mysqli_error($this->conn));
            return null;
        }

        try {
            $sql = "SELECT id, company_id, task_type, payload, attempts, max_attempts
                      FROM task_queue
                     WHERE status = 'pending'
                       AND scheduled_for <= NOW()
                       AND deleted_at IS NULL
                  ORDER BY priority ASC, scheduled_for ASC, id ASC
                     LIMIT 1
                FOR UPDATE";
            $res = mysqli_query($this->conn, $sql);
            if (!$res) {
                throw new \RuntimeException('claim SELECT failed: ' . mysqli_error($this->conn));
            }
            $row = mysqli_fetch_assoc($res);
            if (!$row) {
                mysqli_commit($this->conn);
                return null;
            }

            $taskId = intval($row['id']);
            $workerId = mysqli_real_escape_string($this->conn, $this->workerId);
            $upd = "UPDATE task_queue
                       SET status     = 'locked',
                           locked_at  = NOW(),
                           locked_by  = '$workerId',
                           attempts   = attempts + 1,
                           updated_at = NOW()
                     WHERE id = $taskId AND status = 'pending'";
            if (!mysqli_query($this->conn, $upd)) {
                throw new \RuntimeException('claim UPDATE failed: ' . mysqli_error($this->conn));
            }
            if (mysqli_affected_rows($this->conn) === 0) {
                // Lost the race (status changed between SELECT and UPDATE).
                mysqli_rollback($this->conn);
                return null;
            }

            mysqli_commit($this->conn);

            // attempts in the returned row is post-increment; payload is a JSON string.
            $row['attempts'] = intval($row['attempts']) + 1;
            return $row;
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            error_log('[WorkerRunner] claim failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Run the handler for the claimed task and persist the outcome.
     * Returns ['status' => 'done'|'failed'|'dead_letter']
     */
    private function dispatch(array $task): array
    {
        $taskId = intval($task['id']);
        $taskType = (string)$task['task_type'];
        $attempts = intval($task['attempts']);
        $maxAttempts = intval($task['max_attempts']);

        // Decode payload
        $payload = json_decode((string)$task['payload'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->recordFailure(
                $taskId, $attempts, $maxAttempts,
                'malformed payload JSON: ' . json_last_error_msg(),
                /*forceDeadLetter*/ true,  // bad data won't fix on retry
                /*startedAt*/ microtime(true)
            );
        }

        // Resolve handler
        $handlerClass = self::HANDLERS[$taskType] ?? null;
        if ($handlerClass === null) {
            return $this->recordFailure(
                $taskId, $attempts, $maxAttempts,
                "no handler registered for task_type='$taskType'",
                /*forceDeadLetter*/ true,
                /*startedAt*/ microtime(true)
            );
        }

        if (!class_exists($handlerClass)) {
            return $this->recordFailure(
                $taskId, $attempts, $maxAttempts,
                "handler class not loadable: $handlerClass",
                /*forceDeadLetter*/ true,
                /*startedAt*/ microtime(true)
            );
        }

        $handler = new $handlerClass();
        if (!$handler instanceof TaskHandler) {
            return $this->recordFailure(
                $taskId, $attempts, $maxAttempts,
                "handler does not implement TaskHandler: $handlerClass",
                /*forceDeadLetter*/ true,
                /*startedAt*/ microtime(true)
            );
        }

        $context = [
            'company_id' => intval($task['company_id']),
            'task_id'    => $taskId,
            'attempt'    => $attempts,
        ];

        $startedAt = microtime(true);
        try {
            $result = $handler->handle($payload, $context);
            return $this->recordSuccess($taskId, $attempts, $result, $startedAt);
        } catch (\Throwable $e) {
            return $this->recordFailure(
                $taskId, $attempts, $maxAttempts,
                $this->formatError($e),
                /*forceDeadLetter*/ false,
                $startedAt
            );
        }
    }

    private function recordSuccess(int $taskId, int $attempt, array $result, float $startedAt): array
    {
        $durationMs = intval((microtime(true) - $startedAt) * 1000);
        $resultJson = mysqli_real_escape_string($this->conn, json_encode($result));

        // Result history row
        mysqli_query($this->conn,
            "INSERT INTO task_results (task_id, attempt_number, success, result_data, duration_ms, completed_at)
             VALUES ($taskId, $attempt, 1, '$resultJson', $durationMs, NOW())"
        );

        // Mark task done; clear lock
        mysqli_query($this->conn,
            "UPDATE task_queue
                SET status     = 'done',
                    locked_at  = NULL,
                    locked_by  = NULL,
                    last_error = NULL,
                    updated_at = NOW()
              WHERE id = $taskId"
        );

        return ['status' => 'done'];
    }

    /**
     * Record a failure attempt. Decides retry vs dead-letter based on attempts and
     * the forceDeadLetter flag (used for unrecoverable errors like missing handler).
     */
    private function recordFailure(
        int $taskId, int $attempt, int $maxAttempts,
        string $errorMessage, bool $forceDeadLetter, float $startedAt
    ): array {
        $durationMs = intval((microtime(true) - $startedAt) * 1000);
        $errorEsc   = mysqli_real_escape_string($this->conn, $errorMessage);
        $lastErrorEsc = mysqli_real_escape_string(
            $this->conn,
            substr($errorMessage, 0, self::LAST_ERROR_TRUNCATE)
        );

        // Result history row (always written)
        mysqli_query($this->conn,
            "INSERT INTO task_results (task_id, attempt_number, success, error_message, duration_ms, completed_at)
             VALUES ($taskId, $attempt, 0, '$errorEsc', $durationMs, NOW())"
        );

        // Cap max attempts (anti-footgun per #77 EC1)
        $effectiveMax = min($maxAttempts, self::ABSOLUTE_MAX_ATTEMPTS);
        $exhausted = ($attempt >= $effectiveMax);

        if ($forceDeadLetter || $exhausted) {
            mysqli_query($this->conn,
                "UPDATE task_queue
                    SET status     = 'dead_letter',
                        locked_at  = NULL,
                        locked_by  = NULL,
                        last_error = '$lastErrorEsc',
                        updated_at = NOW()
                  WHERE id = $taskId"
            );
            error_log("[WorkerRunner] DEAD_LETTER task_id=$taskId attempts=$attempt err=" . substr($errorMessage, 0, 200));
            return ['status' => 'dead_letter'];
        }

        // Retry: schedule next attempt per backoff schedule.
        // attempts is 1-indexed; backoff[attempt-1] is delay until next run.
        $delaySeconds = self::BACKOFF_SECONDS[$attempt - 1] ?? end(self::BACKOFF_SECONDS);
        mysqli_query($this->conn,
            "UPDATE task_queue
                SET status        = 'pending',
                    locked_at     = NULL,
                    locked_by     = NULL,
                    last_error    = '$lastErrorEsc',
                    scheduled_for = NOW() + INTERVAL $delaySeconds SECOND,
                    updated_at    = NOW()
              WHERE id = $taskId"
        );

        return ['status' => 'failed'];
    }

    private function formatError(\Throwable $e): string
    {
        return get_class($e) . ': ' . $e->getMessage()
             . "\n at " . $e->getFile() . ':' . $e->getLine()
             . "\n" . $e->getTraceAsString();
    }
}
