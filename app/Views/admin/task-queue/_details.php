<?php
/**
 * Task details modal partial — rendered standalone, fetched via AJAX.
 * Expects $task = full task_queue row + 'history' array of task_results rows.
 */
$id          = intval($task['id']);
$type        = htmlspecialchars($task['task_type'], ENT_QUOTES, 'UTF-8');
$status      = htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8');
$priority    = intval($task['priority']);
$attempts    = intval($task['attempts']);
$maxAttempts = intval($task['max_attempts']);
$lockedBy    = $task['locked_by'] ?? null;
$lastError   = $task['last_error'] ?? null;
$payload     = $task['payload'] ?? '';
$prettyPayload = json_encode(json_decode($payload, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Status badge colors (PHP 7.4 compatible — no match expression)
switch ($task['status']) {
    case 'done':
        $statusBadge = 'background:#d1fae5;color:#065f46;'; break;
    case 'pending':
    case 'running':
        $statusBadge = 'background:#dbeafe;color:#1e40af;'; break;
    case 'locked':
    case 'failed':
        $statusBadge = 'background:#fef3c7;color:#92400e;'; break;
    case 'dead_letter':
        $statusBadge = 'background:#fee2e2;color:#991b1b;'; break;
    default:
        $statusBadge = 'background:#e5e7eb;color:#374151;'; break;
}
?>
<h3 style="margin:0 0 4px;font-size:18px;color:#111827;">
    Task #<?= $id ?> · <?= $type ?>
</h3>
<div style="margin-bottom:16px;">
    <span style="<?= $statusBadge ?>padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;text-transform:uppercase;">
        <?= $status ?>
    </span>
    <span style="margin-left:8px;color:#6b7280;font-size:13px;">
        priority <?= $priority ?> · attempt <?= $attempts ?>/<?= $maxAttempts ?>
    </span>
</div>

<dl style="display:grid;grid-template-columns:140px 1fr;gap:6px 14px;font-size:13px;">
    <dt style="color:#6b7280;">Created</dt>
    <dd><?= htmlspecialchars($task['created_at']) ?></dd>

    <dt style="color:#6b7280;">Updated</dt>
    <dd><?= htmlspecialchars($task['updated_at']) ?></dd>

    <dt style="color:#6b7280;">Scheduled for</dt>
    <dd><?= htmlspecialchars($task['scheduled_for'] ?? '—') ?></dd>

    <?php if ($task['locked_at']): ?>
    <dt style="color:#6b7280;">Locked at</dt>
    <dd><?= htmlspecialchars($task['locked_at']) ?>
        <?= $lockedBy ? ' by ' . htmlspecialchars($lockedBy) : '' ?>
    </dd>
    <?php endif; ?>
</dl>

<h4 style="margin:18px 0 6px;font-size:14px;color:#374151;">Payload</h4>
<pre style="background:#f3f4f6;padding:10px;border-radius:6px;font-size:12px;overflow:auto;max-height:200px;border:1px solid #e5e7eb;"><?= htmlspecialchars($prettyPayload, ENT_QUOTES, 'UTF-8') ?></pre>

<?php if ($lastError): ?>
<h4 style="margin:18px 0 6px;font-size:14px;color:#991b1b;">Last error</h4>
<pre style="background:#fef2f2;padding:10px;border-radius:6px;font-size:12px;overflow:auto;max-height:160px;border:1px solid #fecaca;color:#991b1b;"><?= htmlspecialchars($lastError, ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>

<?php if (!empty($task['history'])): ?>
<h4 style="margin:18px 0 6px;font-size:14px;color:#374151;">Attempt history (<?= count($task['history']) ?>)</h4>
<table style="width:100%;font-size:12px;border-collapse:collapse;">
    <thead>
        <tr style="background:#f9fafb;color:#6b7280;text-align:left;">
            <th style="padding:6px;">#</th>
            <th style="padding:6px;">Result</th>
            <th style="padding:6px;">Duration</th>
            <th style="padding:6px;">Completed</th>
            <th style="padding:6px;">Detail</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($task['history'] as $h): ?>
        <tr style="border-top:1px solid #e5e7eb;">
            <td style="padding:6px;"><?= intval($h['attempt_number']) ?></td>
            <td style="padding:6px;">
                <?php if ($h['success']): ?>
                    <span style="color:#065f46;font-weight:600;">✓ ok</span>
                <?php else: ?>
                    <span style="color:#991b1b;font-weight:600;">✗ fail</span>
                <?php endif; ?>
            </td>
            <td style="padding:6px;color:#6b7280;"><?= $h['duration_ms'] !== null ? intval($h['duration_ms']) . 'ms' : '—' ?></td>
            <td style="padding:6px;color:#6b7280;"><?= htmlspecialchars($h['completed_at']) ?></td>
            <td style="padding:6px;color:#6b7280;font-family:monospace;font-size:11px;">
                <?php
                $detail = $h['success'] ? ($h['result_data'] ?? '') : ($h['error_message'] ?? '');
                echo htmlspecialchars(substr((string)$detail, 0, 80), ENT_QUOTES, 'UTF-8');
                if (strlen((string)$detail) > 80) echo '…';
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
