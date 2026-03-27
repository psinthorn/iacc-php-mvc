<?php
/**
 * Webhook Delivery Log View
 * 
 * Variables from AdminApiController::webhookDeliveries():
 *   $webhook, $deliveries
 */
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-history"></i> Webhook Deliveries — #<?= intval($webhook['id']) ?></h2>
    <div>
        <a href="index.php?page=api_webhooks" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Back to Webhooks</a>
    </div>
</div>

<!-- Webhook Info -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; gap:30px; flex-wrap:wrap;">
        <div>
            <small style="color:#999;">URL</small><br>
            <code style="font-size:0.9rem;"><?= htmlspecialchars($webhook['url']) ?></code>
        </div>
        <div>
            <small style="color:#999;">Status</small><br>
            <?php if ($webhook['is_active']): ?>
                <span class="badge badge-success"><i class="fa fa-check"></i> Active</span>
            <?php else: ?>
                <span class="badge badge-danger"><i class="fa fa-times"></i> Disabled</span>
            <?php endif; ?>
        </div>
        <div>
            <small style="color:#999;">Events</small><br>
            <?php foreach (explode(',', $webhook['events']) as $evt): ?>
                <span class="badge badge-info" style="font-size:0.75rem; margin:1px;"><?= trim($evt) ?></span>
            <?php endforeach; ?>
        </div>
        <div>
            <small style="color:#999;">Failures</small><br>
            <?php if ($webhook['failure_count'] > 0): ?>
                <span class="badge badge-warning"><?= $webhook['failure_count'] ?></span>
            <?php else: ?>
                <span style="color:#27ae60;">0</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delivery Log Table -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-list"></i> Recent Deliveries (last 50)</h4>
    
    <?php if (empty($deliveries)): ?>
        <p style="color:#999; text-align:center; padding:30px;">
            No delivery attempts yet. Webhook deliveries will appear here when events are triggered.
        </p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event</th>
                    <th>Status Code</th>
                    <th>Duration</th>
                    <th>Result</th>
                    <th>Error</th>
                    <th>Timestamp</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td>#<?= $d['id'] ?></td>
                    <td><code style="font-size:0.85rem;"><?= htmlspecialchars($d['event']) ?></code></td>
                    <td>
                        <?php if ($d['response_code']): ?>
                            <span class="badge badge-<?= ($d['response_code'] >= 200 && $d['response_code'] < 300) ? 'success' : 'danger' ?>">
                                <?= $d['response_code'] ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-secondary">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['duration_ms'] !== null): ?>
                            <?= $d['duration_ms'] ?>ms
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['success']): ?>
                            <span class="badge badge-success"><i class="fa fa-check"></i> Success</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fa fa-times"></i> Failed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['error']): ?>
                            <small style="color:#e74c3c;"><?= htmlspecialchars(mb_substr($d['error'], 0, 80)) ?></small>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, H:i:s', strtotime($d['created_at'])) ?></td>
                    <td>
                        <button type="button" class="btn btn-xs btn-outline-info" onclick="togglePayload(<?= $d['id'] ?>)" title="View payload">
                            <i class="fa fa-code"></i>
                        </button>
                    </td>
                </tr>
                <tr id="payload-<?= $d['id'] ?>" style="display:none;">
                    <td colspan="8">
                        <div style="display:flex; gap:15px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:300px;">
                                <small><strong>Request Payload:</strong></small>
                                <pre style="background:#f8f9fa; padding:10px; border-radius:6px; font-size:0.8rem; max-height:200px; overflow:auto; margin-top:5px;"><?= htmlspecialchars(json_encode(json_decode($d['payload'] ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $d['payload']) ?></pre>
                            </div>
                            <?php if ($d['response_body']): ?>
                            <div style="flex:1; min-width:300px;">
                                <small><strong>Response Body:</strong></small>
                                <pre style="background:#f8f9fa; padding:10px; border-radius:6px; font-size:0.8rem; max-height:200px; overflow:auto; margin-top:5px;"><?= htmlspecialchars(mb_substr($d['response_body'], 0, 1000)) ?></pre>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</div>

<script>
function togglePayload(id) {
    var row = document.getElementById('payload-' + id);
    if (row) {
        row.style.display = row.style.display === 'none' ? '' : 'none';
    }
}
</script>
