<?php
namespace App\Controllers;

use App\Models\TaskQueue;
use App\Workers\Config\RetryPolicy;

/**
 * AdminTaskQueueController — admin dashboard for v6.1 task queue (#78).
 *
 * Routes (registered in app/Config/routes.php):
 *   admin_task_queue          GET  — main 3-column dashboard
 *   admin_task_queue_details  GET  — AJAX: render details modal HTML for one task
 *   admin_task_queue_retry    POST — single retry (failed/dead_letter → pending)
 *   admin_task_queue_delete   POST — single soft-delete
 *   admin_task_queue_bulk     POST — bulk action on dead_letter (requeue|clear)
 *
 * Multi-tenant: every query filters by $this->user['com_id']. There is no
 * super-admin path in v1 (deferred — see #78 Definition of Done).
 *
 * Security:
 *   - level >= 2 (admin) gate on every action
 *   - CSRF token verified on every POST via $this->verifyCsrf()
 *   - Bulk cap of 500 enforced at the model layer
 *   - Audit log: failures emit error_log lines (admin user_id + action +
 *     task_id + result). Audit-table integration is a follow-up.
 */
class AdminTaskQueueController extends BaseController
{
    private const ACCESS_DENIED_HTML =
        '<div class="alert alert-danger m-4"><i class="fa fa-lock"></i> Access denied. Admin privileges required.</div>';

    /** GET — main dashboard view. */
    public function index(): void
    {
        if ($this->user['level'] < 2) {
            echo self::ACCESS_DENIED_HTML;
            return;
        }

        require_once __DIR__ . '/../Workers/Config/RetryPolicy.php';

        $model = new TaskQueue($this->conn);
        $comId = intval($this->user['com_id']);

        $counts      = $model->countByStatus($comId);
        $pending     = $model->listByStatus($comId, ['pending', 'running'], 50);
        $locked      = $model->listByStatus($comId, ['locked'], 50);
        $deadLetter  = $model->listByStatus($comId, ['failed', 'dead_letter'], 50);

        // Flash message from prior POST (success/info)
        $flash = [
            'msg'  => $_GET['flash'] ?? '',
            'type' => $_GET['flash_type'] ?? 'success',
        ];

        $this->render('admin/task-queue/index', [
            'counts'     => $counts,
            'pending'    => $pending,
            'locked'     => $locked,
            'deadLetter' => $deadLetter,
            'flash'      => $flash,
            'absMaxAttempts' => RetryPolicy::ABSOLUTE_MAX_ATTEMPTS,
            'defaultMaxAttempts' => RetryPolicy::DEFAULT_MAX_ATTEMPTS,
        ]);
    }

    /** GET — AJAX endpoint that returns HTML for the details modal. */
    public function details(): void
    {
        if ($this->user['level'] < 2) {
            http_response_code(403);
            echo self::ACCESS_DENIED_HTML;
            return;
        }

        $taskId = intval($_GET['id'] ?? 0);
        if ($taskId <= 0) {
            http_response_code(400);
            echo '<div class="alert alert-danger m-4">Invalid task ID.</div>';
            return;
        }

        $model = new TaskQueue($this->conn);
        $task  = $model->findWithHistory(intval($this->user['com_id']), $taskId);

        if ($task === null) {
            http_response_code(404);
            echo '<div class="alert alert-warning m-4">Task not found or access denied.</div>';
            return;
        }

        $this->render('admin/task-queue/_details', ['task' => $task]);
    }

    /** POST — single retry. */
    public function retry(): void
    {
        if ($this->user['level'] < 2) {
            $this->redirect('admin_task_queue', ['flash' => 'Access denied', 'flash_type' => 'danger']);
            return;
        }
        $this->verifyCsrf();

        $taskId = intval($_POST['id'] ?? 0);
        $model  = new TaskQueue($this->conn);
        $ok     = $model->markRetry(intval($this->user['com_id']), $taskId);

        $this->logAdminAction('retry', $taskId, $ok);
        $this->redirect('admin_task_queue', [
            'flash'      => $ok ? "Task #$taskId requeued." : "Task #$taskId could not be retried (already done or not yours).",
            'flash_type' => $ok ? 'success' : 'warning',
        ]);
    }

    /** POST — single soft-delete. */
    public function delete(): void
    {
        if ($this->user['level'] < 2) {
            $this->redirect('admin_task_queue', ['flash' => 'Access denied', 'flash_type' => 'danger']);
            return;
        }
        $this->verifyCsrf();

        $taskId = intval($_POST['id'] ?? 0);
        $model  = new TaskQueue($this->conn);
        $ok     = $model->softDelete(intval($this->user['com_id']), $taskId);

        $this->logAdminAction('delete', $taskId, $ok);
        $this->redirect('admin_task_queue', [
            'flash'      => $ok ? "Task #$taskId removed." : "Task #$taskId could not be deleted.",
            'flash_type' => $ok ? 'success' : 'warning',
        ]);
    }

    /** POST — bulk action on dead_letter (action=requeue|clear). */
    public function bulk(): void
    {
        if ($this->user['level'] < 2) {
            $this->redirect('admin_task_queue', ['flash' => 'Access denied', 'flash_type' => 'danger']);
            return;
        }
        $this->verifyCsrf();

        $action = $_POST['action'] ?? '';
        $model  = new TaskQueue($this->conn);
        $comId  = intval($this->user['com_id']);
        $cap    = TaskQueue::BULK_ACTION_CAP;

        switch ($action) {
            case 'requeue':
                $n = $model->bulkRequeueDeadLetter($comId);
                $this->logAdminAction('bulk_requeue_dl', 0, true, "n=$n");
                $msg = $n === 0
                    ? 'No dead-letter tasks to requeue.'
                    : "$n dead-letter task(s) requeued" . ($n === $cap ? " (cap of $cap hit — click again to continue)." : '.');
                $this->redirect('admin_task_queue', ['flash' => $msg, 'flash_type' => 'success']);
                return;

            case 'clear':
                $n = $model->bulkClearDeadLetter($comId);
                $this->logAdminAction('bulk_clear_dl', 0, true, "n=$n");
                $msg = $n === 0
                    ? 'No dead-letter tasks to clear.'
                    : "$n dead-letter task(s) cleared" . ($n === $cap ? " (cap of $cap hit — click again to continue)." : '.');
                $this->redirect('admin_task_queue', ['flash' => $msg, 'flash_type' => 'success']);
                return;

            default:
                $this->redirect('admin_task_queue', ['flash' => 'Unknown bulk action.', 'flash_type' => 'danger']);
                return;
        }
    }

    /** Lightweight audit logging — error_log only for now. */
    private function logAdminAction(string $action, int $taskId, bool $success, string $extra = ''): void
    {
        $userId = intval($this->user['id']);
        $comId  = intval($this->user['com_id']);
        $status = $success ? 'OK' : 'NOOP';
        error_log("[AdminTaskQueue] user_id=$userId com_id=$comId action=$action task_id=$taskId status=$status $extra");
    }
}
