<?php
/**
 * Single task row partial — included from index.php
 * Expects $t in scope (an associative array from TaskQueue::listByStatus).
 *
 * To minimize XSS surface, every dynamic field is htmlspecialchars'd.
 */
$id           = intval($t['id']);
$type         = htmlspecialchars($t['task_type'] ?? '', ENT_QUOTES, 'UTF-8');
$priority     = intval($t['priority']);
$status       = $t['status'] ?? 'pending';
$attempts     = intval($t['attempts']);
$maxAttempts  = intval($t['max_attempts']);
$scheduled    = $t['scheduled_for'] ?? null;
$lockedAt     = $t['locked_at'] ?? null;
$lastError    = $t['last_error'] ?? null;

$priClass = $priority <= 3 ? 'high' : ($priority >= 7 ? 'low' : '');
$priLabel = $priority <= 3 ? 'high' : ($priority >= 7 ? 'low' : 'normal');

// Humanize times: "in 30s" / "5m ago"
// Guard with function_exists — this partial is included once per task row,
// so without the guard PHP throws a "Cannot redeclare" fatal on the 2nd row.
if (!function_exists('_tq_humanize')) {
    function _tq_humanize($ts) {
        if (!$ts) return '—';
        $delta = strtotime($ts) - time();
        $abs = abs($delta);
        if ($abs < 60)        $human = $abs . 's';
        elseif ($abs < 3600)  $human = floor($abs/60) . 'm';
        elseif ($abs < 86400) $human = floor($abs/3600) . 'h';
        else                  $human = floor($abs/86400) . 'd';
        return $delta >= 0 ? "in $human" : "$human ago";
    }
}

$canRetry  = in_array($status, ['failed', 'dead_letter'], true);
$canDelete = in_array($status, ['pending', 'failed', 'dead_letter', 'done'], true);
?>
<div class="tq-row" data-task-id="<?= $id ?>">
    <div class="tq-r-top">
        <span class="tq-r-type"><?= $type ?> <span style="color:#9ca3af;font-weight:normal;">#<?= $id ?></span></span>
        <span class="tq-r-pri <?= $priClass ?>" title="priority <?= $priority ?> (<?= $priLabel ?>)">p=<?= $priority ?></span>
    </div>
    <div class="tq-r-meta">
        <?php if ($status === 'locked'): ?>
            <i class="fa fa-cog fa-spin"></i> running · locked <?= _tq_humanize($lockedAt) ?>
        <?php elseif ($status === 'pending' || $status === 'running'): ?>
            <?= _tq_humanize($scheduled) ?> · attempt <?= $attempts ?>/<?= $maxAttempts ?>
        <?php else: ?>
            <?= htmlspecialchars($status) ?> · <?= _tq_humanize($scheduled ?: $lockedAt) ?> · <?= $attempts ?>/<?= $maxAttempts ?> tries
        <?php endif; ?>
    </div>
    <?php if ($lastError): ?>
        <div class="tq-r-err" title="<?= htmlspecialchars($lastError, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(substr($lastError, 0, 80), ENT_QUOTES, 'UTF-8') ?><?= strlen($lastError) > 80 ? '…' : '' ?>
        </div>
    <?php endif; ?>
    <div class="tq-r-actions">
        <button type="button" class="btn btn-default btn-xs" onclick="tqShowDetails(<?= $id ?>)">
            <i class="fa fa-info-circle"></i> Details
        </button>
        <?php if ($canRetry): ?>
        <form method="POST" action="index.php?page=admin_task_queue_retry" style="display:inline;"
              onsubmit="return confirm('Requeue task #<?= $id ?>?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-primary btn-xs"><i class="fa fa-undo"></i> Retry</button>
        </form>
        <?php endif; ?>
        <?php if ($canDelete): ?>
        <form method="POST" action="index.php?page=admin_task_queue_delete" style="display:inline;"
              onsubmit="return confirm('Soft-delete task #<?= $id ?>?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
        </form>
        <?php endif; ?>
    </div>
</div>
