<?php
namespace App\Models;

/**
 * TaskQueue — multi-tenant query helpers for the admin dashboard (#78).
 *
 * Every method takes $comId as the first argument. The worker (#76) and the
 * application code that enqueues tasks (#75) write task_queue rows with
 * company_id NOT NULL; this model is the read/admin-action surface and
 * MUST always filter by company_id to prevent cross-tenant leak.
 *
 * NOTE: This model intentionally does NOT extend BaseModel. The queue tables
 * are admin-/system-side; we use the raw mysqli connection for explicit
 * control over the JOINs with task_results.
 */
class TaskQueue
{
    /** Cap for bulk admin actions (matches #47 BulkActionController convention). */
    public const BULK_ACTION_CAP = 500;

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Count rows by status for the column-header counters in the UI.
     * Returns associative array keyed by status name (always includes all 6 keys, 0 if absent).
     */
    public function countByStatus(int $comId): array
    {
        $statuses = ['pending', 'locked', 'running', 'done', 'failed', 'dead_letter'];
        $counts = array_fill_keys($statuses, 0);

        $sql = "SELECT status, COUNT(*) AS n
                  FROM task_queue
                 WHERE company_id = $comId
                   AND deleted_at IS NULL
                 GROUP BY status";
        $res = mysqli_query($this->conn, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $counts[$row['status']] = intval($row['n']);
            }
        }
        return $counts;
    }

    /**
     * List rows by a single status (or array of statuses) — used by the
     * 3 columns: pending+locked, dead_letter, etc.
     *
     * @param int          $comId
     * @param string|array $status   single status or list (eg ['pending','locked'])
     * @param int          $limit    page size (default 50 per UX spec)
     * @param int          $offset   pagination offset
     * @return array
     */
    public function listByStatus(int $comId, $status, int $limit = 50, int $offset = 0): array
    {
        $statusList = is_array($status) ? $status : [$status];
        // Whitelist guard — only allow known status values
        $allowed = ['pending', 'locked', 'running', 'done', 'failed', 'dead_letter'];
        $statusList = array_values(array_intersect($statusList, $allowed));
        if (empty($statusList)) {
            return [];
        }

        $statusEsc = "'" . implode("','", array_map(
            fn($s) => mysqli_real_escape_string($this->conn, $s),
            $statusList
        )) . "'";

        $limit  = max(1, min(intval($limit), 200));   // hard cap
        $offset = max(0, intval($offset));

        $sql = "SELECT id, company_id, task_type, priority, status, attempts, max_attempts,
                       locked_at, locked_by, scheduled_for, last_error, created_at, updated_at
                  FROM task_queue
                 WHERE company_id = $comId
                   AND deleted_at IS NULL
                   AND status IN ($statusEsc)
              ORDER BY priority ASC, scheduled_for DESC, id DESC
                 LIMIT $limit OFFSET $offset";
        $res = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Fetch a single task with full details + result history.
     * Returns null if not found OR if it belongs to another tenant.
     */
    public function findWithHistory(int $comId, int $taskId): ?array
    {
        $sql = "SELECT * FROM task_queue
                 WHERE id = $taskId
                   AND company_id = $comId
                   AND deleted_at IS NULL
                 LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return null;
        $task = mysqli_fetch_assoc($res);
        if (!$task) return null;

        // Result history (no need for company filter — FK guarantees same tenant)
        $hsql = "SELECT id, attempt_number, success, result_data, error_message,
                        duration_ms, completed_at
                   FROM task_results
                  WHERE task_id = $taskId
               ORDER BY attempt_number ASC, id ASC";
        $hres = mysqli_query($this->conn, $hsql);
        $task['history'] = [];
        if ($hres) {
            while ($r = mysqli_fetch_assoc($hres)) {
                $task['history'][] = $r;
            }
        }
        return $task;
    }

    /**
     * Manual retry: reset failed/dead_letter task to pending, clear lock + error.
     * Returns true if any row was affected (i.e. caller had ownership AND status was retryable).
     */
    public function markRetry(int $comId, int $taskId): bool
    {
        $sql = "UPDATE task_queue
                   SET status        = 'pending',
                       attempts      = 0,
                       locked_at     = NULL,
                       locked_by     = NULL,
                       last_error    = NULL,
                       scheduled_for = NOW(),
                       updated_at    = NOW()
                 WHERE id = $taskId
                   AND company_id = $comId
                   AND deleted_at IS NULL
                   AND status IN ('failed', 'dead_letter')";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Soft-delete a single task (sets deleted_at).
     * Whitelist statuses: don't let admin delete a row currently being worked on.
     */
    public function softDelete(int $comId, int $taskId): bool
    {
        $sql = "UPDATE task_queue
                   SET deleted_at = NOW(),
                       updated_at = NOW()
                 WHERE id = $taskId
                   AND company_id = $comId
                   AND deleted_at IS NULL
                   AND status IN ('pending', 'failed', 'dead_letter', 'done')";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Bulk requeue all dead_letter rows for the tenant. Capped at BULK_ACTION_CAP.
     * Returns number of rows actually requeued.
     */
    public function bulkRequeueDeadLetter(int $comId): int
    {
        $cap = self::BULK_ACTION_CAP;
        $sql = "UPDATE task_queue
                   SET status        = 'pending',
                       attempts      = 0,
                       locked_at     = NULL,
                       locked_by     = NULL,
                       last_error    = NULL,
                       scheduled_for = NOW(),
                       updated_at    = NOW()
                 WHERE company_id = $comId
                   AND deleted_at IS NULL
                   AND status = 'dead_letter'
                 ORDER BY id ASC
                 LIMIT $cap";
        if (!mysqli_query($this->conn, $sql)) return 0;
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Bulk soft-delete all dead_letter rows for the tenant. Capped at BULK_ACTION_CAP.
     * Returns number of rows soft-deleted.
     */
    public function bulkClearDeadLetter(int $comId): int
    {
        $cap = self::BULK_ACTION_CAP;
        $sql = "UPDATE task_queue
                   SET deleted_at = NOW(),
                       updated_at = NOW()
                 WHERE company_id = $comId
                   AND deleted_at IS NULL
                   AND status = 'dead_letter'
                 ORDER BY id ASC
                 LIMIT $cap";
        if (!mysqli_query($this->conn, $sql)) return 0;
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Distinct task_types for the filter dropdown.
     */
    public function distinctTaskTypes(int $comId): array
    {
        $sql = "SELECT DISTINCT task_type FROM task_queue
                 WHERE company_id = $comId AND deleted_at IS NULL
              ORDER BY task_type ASC";
        $res = mysqli_query($this->conn, $sql);
        $types = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $types[] = $row['task_type'];
            }
        }
        return $types;
    }
}
