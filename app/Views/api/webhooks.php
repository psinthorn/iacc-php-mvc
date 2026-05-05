<?php
$pageTitle = 'API — Webhooks';

/**
 * Webhook Management View
 *
 * Variables from AdminApiController::webhooks():
 *   $webhooks, $subscription
 */
$validEvents = ['order.created', 'order.completed', 'order.failed', 'order.cancelled', 'order.updated',
                'allotment.created', 'allotment.updated', 'allotment.depleted', 'allotment.closed', 'allotment.snapshot'];
$isThai = ($_SESSION['lang'] ?? '0') === '1';
$flashMsg  = $_SESSION['flash_msg']  ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-bell"></i> Webhook Management</h2>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
        <a href="index.php?page=api_docs" class="btn btn-sm btn-outline-info"><i class="fa fa-book"></i> API Docs</a>
    </div>
</div>

<?php if (!empty($flashMsg)): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom:15px;">
    <?= htmlspecialchars($flashMsg) ?>
</div>
<?php endif; ?>

<?php if (!$subscription): ?>
<div style="text-align:center; padding:40px 20px; color:#999;">
    <i class="fa fa-bell-slash" style="font-size:3rem; margin-bottom:15px;"></i>
    <h4>No API Subscription</h4>
    <p>Activate your API trial to use webhooks.</p>
    <a href="index.php?page=api_dashboard" class="btn btn-primary">Go to Dashboard</a>
</div>
<?php else: ?>

<!-- Register Webhook Form -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-plus-circle"></i> Register Webhook</h4>
    <form method="post" action="index.php?page=api_webhook_create">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:12px;">
            <label><strong>Webhook URL</strong> <small style="color:#999;">(HTTPS required for production)</small></label>
            <input type="url" name="webhook_url" class="form-control" placeholder="https://example.com/webhook" required>
        </div>
        <div class="form-group" style="margin-bottom:15px;">
            <label><strong>Events</strong></label>
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <?php foreach ($validEvents as $evt): ?>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="events[]" value="<?= $evt ?>" checked>
                    <code style="font-size:0.85rem;"><?= $evt ?></code>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Register Webhook</button>
    </form>
</div>

<!-- Inventory Snapshot Backfill (v6.2 #82) — admin tool for catching up new partner subscribers -->
<?php if (!empty($webhooks)): ?>
<div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:16px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div style="flex:1; min-width:240px;">
            <strong><i class="fa fa-refresh"></i>
                <?= $isThai ? 'ส่งข้อมูลคงเหลือทั้งหมดให้พาร์ทเนอร์' : 'Send full inventory snapshot to partners' ?>
            </strong>
            <div style="font-size:12px; color:#475569; margin-top:4px;">
                <?= $isThai
                    ? 'ส่งสถานะปัจจุบันของ allotments ทั้งหมด (ที่ยังไม่ปิดและวันที่ยังไม่ผ่าน) เป็นเหตุการณ์ allotment.snapshot — ใช้เมื่อมีพาร์ทเนอร์ใหม่ที่ต้องการซิงก์ข้อมูลเริ่มต้น'
                    : 'Re-emits the current state of every active allotment (not closed, future travel date) as allotment.snapshot events. Useful when a new partner subscribes and needs to backfill their inventory cache.' ?>
            </div>
        </div>
        <form method="post" action="index.php?page=api_webhook_snapshot" style="margin:0;"
              onsubmit="return confirm('<?= $isThai ? 'ส่ง snapshot ให้พาร์ทเนอร์ทั้งหมด?' : 'Queue snapshot events for all active allotments?' ?>');">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-sm btn-outline-info">
                <i class="fa fa-paper-plane"></i> <?= $isThai ? 'ส่ง snapshot' : 'Send snapshot' ?>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Webhook List -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-list"></i> Active Webhooks</h4>
    
    <?php if (empty($webhooks)): ?>
        <p style="color:#999; text-align:center; padding:30px;">
            No webhooks registered. Create one above to receive real-time order notifications.
        </p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>URL</th>
                    <th>Events</th>
                    <th>Status</th>
                    <th>Failures</th>
                    <th>Last Triggered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($webhooks as $wh): ?>
                <tr>
                    <td>#<?= $wh['id'] ?></td>
                    <td>
                        <code style="font-size:0.85rem; word-break:break-all;"><?= htmlspecialchars($wh['url']) ?></code>
                    </td>
                    <td>
                        <?php foreach (explode(',', $wh['events']) as $evt): ?>
                            <span class="badge badge-info" style="font-size:0.75rem; margin:1px;"><?= trim($evt) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php if ($wh['is_active']): ?>
                            <span class="badge badge-success"><i class="fa fa-check"></i> Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fa fa-times"></i> Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($wh['failure_count'] > 0): ?>
                            <span class="badge badge-warning"><?= $wh['failure_count'] ?></span>
                            <?php if ($wh['failure_count'] >= 10): ?>
                                <small style="color:#e74c3c;">(auto-disabled)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#27ae60;">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($wh['last_triggered']): ?>
                            <?= date('M d, H:i', strtotime($wh['last_triggered'])) ?>
                            <?php if ($wh['last_status']): ?>
                                <span class="badge badge-<?= ($wh['last_status'] >= 200 && $wh['last_status'] < 300) ? 'success' : 'danger' ?>"><?= $wh['last_status'] ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#999;">Never</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <a href="index.php?page=api_webhook_deliveries&id=<?= $wh['id'] ?>" class="btn btn-sm btn-outline-info" title="View Deliveries">
                            <i class="fa fa-history"></i>
                        </a>
                        <form method="post" action="index.php?page=api_webhook_toggle" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="webhook_id" value="<?= $wh['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-<?= $wh['is_active'] ? 'warning' : 'success' ?>" title="<?= $wh['is_active'] ? 'Disable' : 'Enable' ?>">
                                <i class="fa fa-<?= $wh['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                        <form method="post" action="index.php?page=api_webhook_delete" style="display:inline;" onsubmit="return confirm('Delete this webhook?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="webhook_id" value="<?= $wh['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php if ($wh['last_error']): ?>
                <tr>
                    <td></td>
                    <td colspan="6">
                        <small style="color:#e74c3c;"><i class="fa fa-exclamation-triangle"></i> Last error: <?= htmlspecialchars($wh['last_error']) ?></small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Webhook Signature Info -->
<div style="margin-top:20px; background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-shield"></i> Verifying Webhook Signatures</h4>
    <p style="color:#666; font-size:0.9rem;">
        Each webhook delivery includes an <code>X-Webhook-Signature</code> header containing an HMAC-SHA256 signature.
        Verify it using your webhook secret to ensure the request is authentic.
    </p>
    <pre style="background:#f8f9fa; padding:15px; border-radius:8px; font-size:0.85rem; overflow-x:auto;"><code>// PHP verification example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, $yourWebhookSecret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

$data = json_decode($payload, true);
// Process $data['event'] and $data['data']</code></pre>
</div>

<?php endif; ?>

</div>
