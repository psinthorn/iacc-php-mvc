<?php
/**
 * Task Queue Admin Dashboard (#78)
 *
 * Variables passed from AdminTaskQueueController::index:
 *   $counts          — assoc array of status → int
 *   $pending         — array of rows (status IN pending,running)
 *   $locked          — array of rows (status = locked)
 *   $deadLetter      — array of rows (status IN failed, dead_letter)
 *   $flash           — ['msg' => ..., 'type' => 'success|warning|danger']
 *   $absMaxAttempts  — RetryPolicy::ABSOLUTE_MAX_ATTEMPTS (for legend)
 *   $defaultMaxAttempts — RetryPolicy::DEFAULT_MAX_ATTEMPTS (for legend)
 *   $user            — current user (com_id, level, etc.)
 */
$pendingCount     = ($counts['pending'] ?? 0) + ($counts['running'] ?? 0);
$lockedCount      = $counts['locked'] ?? 0;
$deadLetterCount  = ($counts['failed'] ?? 0) + ($counts['dead_letter'] ?? 0);
?>
<style>
.tq-page {padding:24px;max-width:1600px;margin:0 auto;}
.tq-header {display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.tq-header h2 {margin:0;font-size:22px;color:#1f2937;}
.tq-header .tq-meta {color:#6b7280;font-size:13px;}
.tq-cols {display:grid;grid-template-columns:repeat(3,minmax(300px,1fr));gap:16px;}
@media (max-width:900px) {.tq-cols {grid-template-columns:1fr;}}
.tq-col {background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px;}
.tq-col h3 {margin:0 0 12px;font-size:15px;color:#374151;display:flex;align-items:center;justify-content:space-between;font-weight:600;}
.tq-col h3 .tq-count {background:#e5e7eb;color:#374151;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:700;}
.tq-col.dl h3 {color:#991b1b;}
.tq-col.dl h3 .tq-count {background:#fee2e2;color:#991b1b;}
.tq-col.lk h3 {color:#92400e;}
.tq-col.lk h3 .tq-count {background:#fef3c7;color:#92400e;}
.tq-col.pd h3 {color:#1e40af;}
.tq-col.pd h3 .tq-count {background:#dbeafe;color:#1e40af;}
.tq-row {background:white;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;margin-bottom:8px;font-size:13px;}
.tq-row .tq-r-top {display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;}
.tq-row .tq-r-type {font-weight:600;color:#111827;}
.tq-row .tq-r-pri {font-size:11px;font-weight:700;padding:1px 6px;border-radius:4px;background:#e0e7ff;color:#3730a3;}
.tq-row .tq-r-pri.high {background:#fee2e2;color:#991b1b;}
.tq-row .tq-r-pri.low {background:#d1fae5;color:#065f46;}
.tq-row .tq-r-meta {color:#6b7280;font-size:12px;}
.tq-row .tq-r-err {color:#991b1b;font-size:12px;margin-top:4px;font-style:italic;background:#fef2f2;padding:4px 6px;border-radius:4px;border-left:3px solid #ef4444;}
.tq-row .tq-r-actions {margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;}
.tq-row .tq-r-actions button,
.tq-row .tq-r-actions a {font-size:12px;padding:4px 10px;line-height:1.4;border-radius:5px;border:1px solid transparent;cursor:pointer;text-decoration:none;}
.tq-empty {color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;font-style:italic;}
.tq-bulk {display:flex;gap:6px;margin-bottom:10px;}
.tq-bulk button {font-size:11px;padding:4px 8px;}
.tq-modal {display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;}
.tq-modal.open {display:flex;}
.tq-modal-body {background:white;width:min(800px,90vw);max-height:85vh;overflow-y:auto;border-radius:12px;padding:20px;}
.tq-modal-close {float:right;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;}
.tq-flash {padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:14px;}
.tq-flash.success {background:#d1fae5;color:#065f46;}
.tq-flash.warning {background:#fef3c7;color:#92400e;}
.tq-flash.danger  {background:#fee2e2;color:#991b1b;}
</style>

<div class="tq-page">
    <div class="tq-header">
        <div>
            <h2><i class="fa fa-tasks"></i> Task Queue</h2>
            <div class="tq-meta">
                Background worker queue · default retries: <?= intval($defaultMaxAttempts) ?> · hard cap: <?= intval($absMaxAttempts) ?> · auto-refresh: 30s
            </div>
        </div>
        <div>
            <a href="index.php?page=admin_task_queue" class="btn btn-sm btn-default">
                <i class="fa fa-refresh"></i> Refresh now
            </a>
        </div>
    </div>

    <?php if (!empty($flash['msg'])): ?>
    <div class="tq-flash <?= htmlspecialchars($flash['type']) ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="tq-cols">
        <!-- Column 1: Pending + Running -->
        <div class="tq-col pd">
            <h3>
                <span><i class="fa fa-clock-o"></i> Pending / Running</span>
                <span class="tq-count"><?= intval($pendingCount) ?></span>
            </h3>
            <?php if (empty($pending)): ?>
                <div class="tq-empty">No pending tasks.</div>
            <?php else: ?>
                <?php foreach ($pending as $t) include __DIR__ . '/_row.php'; ?>
            <?php endif; ?>
        </div>

        <!-- Column 2: Locked -->
        <div class="tq-col lk">
            <h3>
                <span><i class="fa fa-lock"></i> Locked / In Progress</span>
                <span class="tq-count"><?= intval($lockedCount) ?></span>
            </h3>
            <?php if (empty($locked)): ?>
                <div class="tq-empty">No tasks running.</div>
            <?php else: ?>
                <?php foreach ($locked as $t) include __DIR__ . '/_row.php'; ?>
            <?php endif; ?>
        </div>

        <!-- Column 3: Failed + Dead-Letter -->
        <div class="tq-col dl">
            <h3>
                <span><i class="fa fa-exclamation-triangle"></i> Failed / Dead-Letter</span>
                <span class="tq-count"><?= intval($deadLetterCount) ?></span>
            </h3>
            <?php if ($deadLetterCount > 0): ?>
            <div class="tq-bulk">
                <form method="POST" action="index.php?page=admin_task_queue_bulk" style="display:inline;"
                      onsubmit="return confirm('Requeue all dead-letter tasks (capped at <?= intval(\App\Models\TaskQueue::BULK_ACTION_CAP) ?>)?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="requeue">
                    <button type="submit" class="btn btn-default btn-xs">
                        <i class="fa fa-undo"></i> Requeue all
                    </button>
                </form>
                <form method="POST" action="index.php?page=admin_task_queue_bulk" style="display:inline;"
                      onsubmit="return confirm('Soft-delete all dead-letter tasks? Reversible within 30 days via SQL.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-danger btn-xs">
                        <i class="fa fa-trash"></i> Clear all
                    </button>
                </form>
            </div>
            <?php endif; ?>
            <?php if (empty($deadLetter)): ?>
                <div class="tq-empty">No failed tasks. 🎉</div>
            <?php else: ?>
                <?php foreach ($deadLetter as $t) include __DIR__ . '/_row.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Details modal -->
<div id="tqModal" class="tq-modal" onclick="if(event.target===this)tqCloseModal();">
    <div class="tq-modal-body">
        <span class="tq-modal-close" onclick="tqCloseModal()">×</span>
        <div id="tqModalContent">Loading…</div>
    </div>
</div>

<script>
(function(){
    function showDetails(taskId){
        var modal = document.getElementById('tqModal');
        var body  = document.getElementById('tqModalContent');
        body.innerHTML = '<div style="text-align:center;padding:40px;color:#6b7280;">Loading…</div>';
        modal.classList.add('open');
        // Pause auto-refresh while modal is open
        if (window.__tqRefreshTimer) { clearInterval(window.__tqRefreshTimer); window.__tqRefreshTimer = null; }

        fetch('index.php?page=admin_task_queue_details&id=' + encodeURIComponent(taskId))
            .then(r => r.text())
            .then(html => { body.innerHTML = html; })
            .catch(e => { body.innerHTML = '<div class="alert alert-danger">Failed to load: ' + e.message + '</div>'; });
    }
    window.tqShowDetails = showDetails;
    window.tqCloseModal  = function(){
        document.getElementById('tqModal').classList.remove('open');
        // Resume auto-refresh
        if (!window.__tqRefreshTimer) {
            window.__tqRefreshTimer = setTimeout(function(){ location.reload(); }, 30000);
        }
    };

    // Auto-refresh every 30s (paused when modal open)
    window.__tqRefreshTimer = setTimeout(function(){ location.reload(); }, 30000);
})();
</script>
