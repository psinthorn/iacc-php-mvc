<?php
namespace App\Workers\Handlers;

/**
 * TaskHandler — contract for #76 worker (v6.1 sprint).
 *
 * Each task_type registered in WorkerRunner::HANDLERS maps to a class
 * implementing this interface. The worker dispatches a claimed task to its
 * handler, captures the return value as `task_results.result_data`, and
 * catches any thrown exception as a failure (recorded + retried per backoff).
 *
 * Handlers MUST:
 *  - Be idempotent — the worker may invoke the same task more than once if
 *    the previous attempt left the lock stale (machine crash, cron timeout).
 *  - Use $context['company_id'] for multi-tenant scoping in any DB queries.
 *  - Return a JSON-serializable array on success.
 *  - Throw an exception (any \Throwable) on failure — NEVER silently return
 *    a "failed" status, the worker won't know to retry.
 *
 * Handlers MAY:
 *  - Declare `public static int $maxAttempts = N` to override the default
 *    (#77 will read this for per-task-type retry policy).
 *  - Declare `public static array $backoffSchedule = [...]` to override
 *    the default backoff schedule (also #77).
 */
interface TaskHandler
{
    /**
     * Execute the task.
     *
     * @param array $payload task_queue.payload decoded as associative array
     * @param array $context [
     *   'company_id' => int,    // multi-tenant scope; NEVER skip in queries
     *   'task_id'    => int,    // task_queue.id (for logging/correlation)
     *   'attempt'    => int,    // 1-indexed retry attempt number
     * ]
     * @return array JSON-serializable result, written to task_results.result_data
     * @throws \Throwable on failure — caught by worker, recorded, retried
     */
    public function handle(array $payload, array $context): array;
}
