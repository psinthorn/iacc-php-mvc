<?php
/**
 * API Usage Logs View
 * 
 * Variables from AdminApiController::usageLogs():
 *   $logs, $total, $pagination, $daily_summary, $channel_breakdown, $subscription
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-bar-chart"></i> API Usage Logs</h2>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<!-- Daily Summary Chart (last 7 days) -->
<?php if (!empty($daily)): ?>
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;">Daily API Calls (Last 7 Days)</h4>
    <div style="display:flex; align-items:flex-end; gap:8px; height:120px;">
        <?php
        $maxCount = max(array_column($daily, 'total_requests'));
        foreach ($daily as $day):
            $pct = $maxCount > 0 ? ($day['total_requests'] / $maxCount) * 100 : 0;
            $date = date('M d', strtotime($day['date']));
        ?>
        <div style="flex:1; display:flex; flex-direction:column; align-items:center;">
            <small style="color:#666; font-size:0.75rem;"><?= intval($day['total_requests']) ?></small>
            <div style="background:linear-gradient(135deg,#667eea,#764ba2); width:100%; min-height:4px; height:<?= max($pct, 4) ?>%; border-radius:6px 6px 0 0; transition:height 0.3s;"></div>
            <small style="color:#999; font-size:0.7rem; margin-top:4px;"><?= $date ?></small>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Channel Breakdown -->
<?php if (!empty($channels)): ?>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:15px; margin-bottom:20px;">
    <?php
    $chColors = ['website' => '#667eea', 'email' => '#f093fb', 'line' => '#00c300', 'facebook' => '#1877f2', 'manual' => '#999'];
    foreach ($channels as $ch):
        $color = $chColors[$ch['channel']] ?? '#667eea';
    ?>
    <div style="background:white; border-radius:12px; padding:15px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border-left:4px solid <?= $color ?>;">
        <div style="font-size:1.5rem; font-weight:700; color:<?= $color ?>;"><?= number_format($ch['total_requests']) ?></div>
        <div style="color:#666; font-size:0.85rem;"><?= ucfirst($ch['channel']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Logs Table -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <?php if (empty($logs)): ?>
        <p style="color:#999; text-align:center; padding:30px;">No API usage logs found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0; font-size:0.9rem;">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Endpoint</th>
                    <th>Method</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>IP</th>
                    <th>Speed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log):
                    // Extract HTTP method from endpoint string (e.g. "POST /api/v1/orders")
                    $parts = explode(' ', $log['endpoint'], 2);
                    $httpMethod = $parts[0] ?? 'GET';
                    $endpointPath = $parts[1] ?? $log['endpoint'];
                ?>
                <tr>
                    <td><?= date('M d H:i:s', strtotime($log['created_at'])) ?></td>
                    <td><code style="font-size:0.8rem;"><?= htmlspecialchars($endpointPath) ?></code></td>
                    <td><span class="badge badge-<?= $httpMethod === 'POST' ? 'success' : ($httpMethod === 'DELETE' ? 'danger' : 'info') ?>"><?= $httpMethod ?></span></td>
                    <td><?= $log['channel'] ? ucfirst($log['channel']) : '-' ?></td>
                    <td>
                        <?php
                        $code = intval($log['status_code']);
                        $codeClass = $code >= 500 ? 'danger' : ($code >= 400 ? 'warning' : 'success');
                        ?>
                        <span class="badge badge-<?= $codeClass ?>"><?= $code ?></span>
                    </td>
                    <td><small><?= htmlspecialchars($log['request_ip'] ?? '') ?></small></td>
                    <td><small><?= $log['processing_ms'] ? $log['processing_ms'] . 'ms' : '-' ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total > 30): ?>
    <div style="margin-top:15px;">
        <?php render_pagination($pagination, 'index.php?page=api_usage_logs'); ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</div>
