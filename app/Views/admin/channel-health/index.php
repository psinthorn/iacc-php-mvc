<?php
/**
 * Channel Health Dashboard (v6.2 #83)
 *
 * Variables passed from ChannelHealthController::index:
 *   $statusCards   — assoc array channel_type → ['state','response_ms','ago_seconds','error','last_checked_at']
 *   $openAlerts    — array of alert rows (status IN open, acknowledged)
 *   $timeline      — array of last-100 heartbeat rows (newest first)
 *   $chartSeries   — array of {channel_type, hour_bucket, avg_ms, max_ms, fail_count}
 *   $flash         — ['msg' => ..., 'type' => 'success|warning|danger']
 *   $channels      — list of channel_type keys (ChannelHealthLog::CHANNELS)
 *   $user          — current user (com_id, level)
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

// Bilingual label tables — kept inline so this view is self-contained
$channelLabels = [
    'line_oa'           => $isThai ? 'ไลน์ OA'           : 'LINE OA',
    'sales_channel_api' => $isThai ? 'API ขายช่องทาง'   : 'Sales Channel API',
    'outbound_webhook'  => $isThai ? 'เว็บฮุคขาออก'      : 'Outbound Webhook',
    'email_smtp'        => $isThai ? 'อีเมล SMTP'        : 'Email SMTP',
];
$stateLabels = [
    'healthy'        => $isThai ? 'สถานะปกติ'         : 'Healthy',
    'degraded'       => $isThai ? 'ผิดปกติบางส่วน'    : 'Degraded',
    'down'           => $isThai ? 'ขัดข้อง'           : 'Down',
    'not_configured' => $isThai ? 'ยังไม่ตั้งค่า'     : 'Not configured',
    'stale'          => $isThai ? 'ไม่มีข้อมูลล่าสุด' : 'No recent data',
];
$stateClass = [
    'healthy' => 'success', 'degraded' => 'warning',
    'down' => 'danger', 'not_configured' => 'secondary', 'stale' => 'secondary',
];
$stateIcon = [
    'healthy' => '🟢', 'degraded' => '🟡', 'down' => '🔴',
    'not_configured' => '⚪', 'stale' => '⚪',
];

/** Format seconds-ago as "Xm ago" / "Xs ago" / "Xh ago" — bilingual. */
function ch_relative_time($seconds, $isThai) {
    if ($seconds === null) return $isThai ? 'ไม่มีข้อมูล' : 'no data';
    $s = intval($seconds);
    if ($s < 60)        return $isThai ? "$s วินาทีที่แล้ว"  : "{$s}s ago";
    if ($s < 3600)      return $isThai ? floor($s/60) . " นาทีที่แล้ว" : floor($s/60) . "m ago";
    if ($s < 86400)     return $isThai ? floor($s/3600) . " ชั่วโมงที่แล้ว" : floor($s/3600) . "h ago";
    return $isThai ? floor($s/86400) . " วันที่แล้ว" : floor($s/86400) . "d ago";
}
?>
<style>
.ch-page {padding:24px;max-width:1600px;margin:0 auto;}
.ch-header {display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.ch-header h2 {margin:0;font-size:22px;color:#1f2937;}
.ch-header .ch-meta {color:#6b7280;font-size:13px;}
.ch-flash {padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:14px;}
.ch-flash.success {background:#d1fae5;color:#065f46;}
.ch-flash.warning {background:#fef3c7;color:#92400e;}
.ch-flash.danger  {background:#fee2e2;color:#991b1b;}
.ch-grid {display:grid;grid-template-columns:repeat(4, minmax(220px, 1fr));gap:14px;margin-bottom:20px;}
@media (max-width:1024px) {.ch-grid {grid-template-columns:repeat(2, 1fr);}}
@media (max-width:540px)  {.ch-grid {grid-template-columns:1fr;}}
.ch-card {background:white;border:1px solid #e5e7eb;border-radius:10px;padding:14px;}
.ch-card .ch-c-title {font-weight:600;color:#111827;font-size:14px;margin-bottom:6px;}
.ch-card .ch-c-state {display:inline-block;padding:3px 10px;border-radius:14px;font-size:12px;font-weight:600;margin-bottom:6px;}
.ch-card .ch-c-state.healthy        {background:#d1fae5;color:#065f46;}
.ch-card .ch-c-state.degraded       {background:#fef3c7;color:#92400e;}
.ch-card .ch-c-state.down           {background:#fee2e2;color:#991b1b;}
.ch-card .ch-c-state.not_configured {background:#f3f4f6;color:#6b7280;}
.ch-card .ch-c-state.stale          {background:#f3f4f6;color:#6b7280;background-image:repeating-linear-gradient(45deg,transparent,transparent 4px,#e5e7eb 4px,#e5e7eb 6px);}
.ch-card .ch-c-meta {font-size:12px;color:#6b7280;margin-bottom:4px;}
.ch-card .ch-c-error {font-size:11px;color:#991b1b;background:#fef2f2;padding:4px 6px;border-radius:4px;margin-top:6px;border-left:3px solid #ef4444;font-style:italic;word-break:break-word;}
.ch-card .ch-c-actions {margin-top:8px;display:flex;gap:6px;}
.ch-card .ch-c-actions button {font-size:11px;padding:3px 8px;border-radius:4px;border:1px solid #d1d5db;background:white;cursor:pointer;}
.ch-card .ch-c-actions button:hover {background:#f9fafb;}
.ch-card .ch-c-actions button:disabled {opacity:0.5;cursor:not-allowed;}
.ch-alerts {background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-bottom:20px;}
.ch-alerts h3 {margin:0 0 10px;font-size:15px;color:#991b1b;font-weight:600;}
.ch-alerts .ch-empty {color:#6b7280;font-size:13px;font-style:italic;}
.ch-section {background:white;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:20px;}
.ch-section h3 {margin:0 0 12px;font-size:15px;color:#374151;font-weight:600;}
.ch-section .ch-chart-box {position:relative;height:280px;}
.ch-table {width:100%;border-collapse:collapse;font-size:13px;}
.ch-table th, .ch-table td {padding:6px 10px;border-bottom:1px solid #f3f4f6;text-align:left;}
.ch-table th {color:#6b7280;font-weight:600;font-size:12px;background:#f9fafb;text-transform:uppercase;letter-spacing:0.04em;}
.ch-table tbody tr:hover {background:#f9fafb;}
.ch-table .ch-t-status {display:inline-block;padding:1px 8px;border-radius:10px;font-size:11px;font-weight:600;}
.ch-table .ch-t-status.success {background:#d1fae5;color:#065f46;}
.ch-table .ch-t-status.failure {background:#fee2e2;color:#991b1b;}
.ch-table .ch-t-status.not_configured {background:#f3f4f6;color:#6b7280;}
.ch-table .ch-t-error {color:#991b1b;font-size:12px;font-style:italic;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ch-table-wrap {max-height:420px;overflow-y:auto;}
@media (max-width:540px) {.ch-table {font-size:12px;} .ch-table th, .ch-table td {padding:4px 6px;}}
</style>

<div class="ch-page">
    <div class="ch-header">
        <div>
            <h2><i class="fa fa-heartbeat"></i>
                <?= $isThai ? 'สถานะช่องทางการขาย' : 'Channel Health Monitor' ?>
            </h2>
            <div class="ch-meta">
                <?= $isThai
                    ? 'ตรวจสอบทุก 5 นาที · แจ้งเตือนเมื่อล้มเหลวต่อเนื่อง 5 ครั้ง'
                    : 'Heartbeat every 5 minutes · alert after 5 consecutive failures' ?>
            </div>
        </div>
        <div>
            <a href="index.php?page=channel_health" class="btn btn-sm btn-default">
                <i class="fa fa-refresh"></i> <?= $isThai ? 'รีเฟรช' : 'Refresh' ?>
            </a>
        </div>
    </div>

    <?php if (!empty($flash['msg'])): ?>
    <div class="ch-flash <?= htmlspecialchars($flash['type']) ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Status grid: 4 cards, 1 per channel -->
    <div class="ch-grid">
        <?php foreach ($channels as $channel):
            $card = $statusCards[$channel] ?? ['state'=>'stale','response_ms'=>null,'ago_seconds'=>null,'error'=>null];
            $cardName  = $channelLabels[$channel];
            $cardState = $card['state'];
            $cardLabel = $stateLabels[$cardState];
            $cardClass = $cardState;
            $cardIcon  = $stateIcon[$cardState];
            include __DIR__ . '/_status_card.php';
        endforeach; ?>
    </div>

    <!-- Open alerts panel -->
    <div class="ch-alerts">
        <h3><i class="fa fa-bell"></i>
            <?= $isThai ? 'แจ้งเตือนที่เปิดอยู่' : 'Open alerts' ?>
            <span style="background:#991b1b;color:white;padding:1px 8px;border-radius:10px;font-size:12px;font-weight:700;margin-left:6px;"><?= count($openAlerts) ?></span>
        </h3>
        <?php if (empty($openAlerts)): ?>
            <div class="ch-empty">
                <?= $isThai ? '✅ ไม่มีแจ้งเตือนในขณะนี้ — ทุกช่องทางทำงานปกติ' : '✅ No active alerts — all channels healthy' ?>
            </div>
        <?php else: ?>
            <?php foreach ($openAlerts as $a) include __DIR__ . '/_alert_row.php'; ?>
        <?php endif; ?>
    </div>

    <!-- 24h response time chart -->
    <div class="ch-section">
        <h3><?= $isThai ? 'เวลาตอบสนอง 24 ชั่วโมงล่าสุด (มิลลิวินาที)' : 'Response time — last 24h (ms)' ?></h3>
        <div class="ch-chart-box">
            <canvas id="chChart24h"></canvas>
        </div>
    </div>

    <!-- Last 100 heartbeats timeline -->
    <div class="ch-section">
        <h3><?= $isThai ? 'ประวัติการตรวจสอบล่าสุด (100 รายการ)' : 'Recent heartbeats (last 100)' ?></h3>
        <?php if (empty($timeline)): ?>
            <div class="ch-empty"><?= $isThai
                ? 'ระบบกำลังเริ่มต้น — ผลตรวจสอบจะแสดงภายใน 5 นาที'
                : 'Health monitor starting up — first heartbeats arrive within 5 minutes.' ?></div>
        <?php else: ?>
        <div class="ch-table-wrap">
        <table class="ch-table">
            <thead>
                <tr>
                    <th><?= $isThai ? 'เวลา' : 'Time' ?></th>
                    <th><?= $isThai ? 'ช่องทาง' : 'Channel' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th><?= $isThai ? 'ตอบสนอง' : 'Response' ?></th>
                    <th><?= $isThai ? 'ข้อผิดพลาด' : 'Error' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeline as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['checked_at']) ?></td>
                    <td><?= htmlspecialchars($channelLabels[$row['channel_type']] ?? $row['channel_type']) ?></td>
                    <td><span class="ch-t-status <?= htmlspecialchars($row['status']) ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </span></td>
                    <td><?= $row['response_ms'] !== null ? intval($row['response_ms']).' ms' : '—' ?></td>
                    <td class="ch-t-error" title="<?= htmlspecialchars((string)($row['error_message'] ?? '')) ?>">
                        <?= htmlspecialchars(mb_substr((string)($row['error_message'] ?? ''), 0, 60)) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js for the 24h chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';

    // Build chart data from server-side $chartSeries.
    // Format: array of {channel_type, hour_bucket, avg_ms, max_ms, fail_count}
    var rawSeries = <?= json_encode($chartSeries, JSON_UNESCAPED_SLASHES) ?>;
    var channelLabels = <?= json_encode($channelLabels, JSON_UNESCAPED_UNICODE) ?>;

    // Pivot to chart format: one dataset per channel, x-axis = hour buckets
    var hourSet = {};
    var byChannel = {};
    rawSeries.forEach(function(r) {
        hourSet[r.hour_bucket] = true;
        if (!byChannel[r.channel_type]) byChannel[r.channel_type] = {};
        byChannel[r.channel_type][r.hour_bucket] = parseInt(r.avg_ms || 0);
    });
    var hourLabels = Object.keys(hourSet).sort();
    var palette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
    var datasets = [];
    var ci = 0;
    Object.keys(byChannel).forEach(function(channel) {
        var data = hourLabels.map(function(h) {
            return byChannel[channel][h] !== undefined ? byChannel[channel][h] : null;
        });
        datasets.push({
            label: channelLabels[channel] || channel,
            data: data,
            borderColor: palette[ci % palette.length],
            backgroundColor: palette[ci % palette.length] + '33',
            tension: 0.25,
            spanGaps: true,
        });
        ci++;
    });

    var canvas = document.getElementById('chChart24h');
    if (canvas && hourLabels.length > 0 && typeof Chart !== 'undefined') {
        new Chart(canvas, {
            type: 'line',
            data: { labels: hourLabels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'ms' } } }
            }
        });
    } else if (canvas) {
        canvas.parentElement.innerHTML =
            '<div style="text-align:center;padding:40px;color:#9ca3af;font-style:italic;">' +
            <?= $isThai ? "'ยังไม่มีข้อมูล 24 ชั่วโมง'" : "'No 24h data yet — chart will populate after a few heartbeats.'" ?> +
            '</div>';
    }

    // ---- Test-now button handler ----
    var csrfToken = '<?= htmlspecialchars(csrf_token()) ?>';
    document.querySelectorAll('[data-action="test-now"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var channel = btn.getAttribute('data-channel');
            btn.disabled = true;
            btn.textContent = '<?= $isThai ? 'กำลังทดสอบ...' : 'Testing...' ?>';

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('channel', channel);

            fetch('index.php?page=channel_health_test_now', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.ok) {
                        btn.textContent = '<?= $isThai ? '✓ เข้าคิวแล้ว' : '✓ Queued' ?>';
                        setTimeout(function() { window.location.reload(); }, 2000);
                    } else if (j.error === 'rate_limited') {
                        btn.textContent = '<?= $isThai ? 'รอสักครู่' : 'Rate limited' ?>';
                        setTimeout(function() { btn.disabled = false; btn.textContent = '<?= $isThai ? 'ทดสอบ' : 'Test now' ?>'; }, 5000);
                    } else {
                        btn.textContent = '<?= $isThai ? 'เกิดข้อผิดพลาด' : 'Error' ?>';
                        setTimeout(function() { btn.disabled = false; btn.textContent = '<?= $isThai ? 'ทดสอบ' : 'Test now' ?>'; }, 3000);
                    }
                })
                .catch(function() {
                    btn.textContent = '<?= $isThai ? 'เครือข่ายล้มเหลว' : 'Network error' ?>';
                    setTimeout(function() { btn.disabled = false; btn.textContent = '<?= $isThai ? 'ทดสอบ' : 'Test now' ?>'; }, 3000);
                });
        });
    });

    // ---- Acknowledge button handler ----
    document.querySelectorAll('[data-action="acknowledge"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var alertId = btn.getAttribute('data-alert-id');
            btn.disabled = true;

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('alert_id', alertId);

            fetch('index.php?page=channel_health_acknowledge', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.ok) {
                        btn.closest('.ch-alert-row').style.opacity = '0.5';
                        btn.textContent = '<?= $isThai ? '✓ รับทราบแล้ว' : '✓ Acknowledged' ?>';
                    } else {
                        btn.disabled = false;
                        alert('<?= $isThai ? 'ไม่สามารถบันทึกได้' : 'Could not acknowledge' ?>');
                    }
                });
        });
    });
})();
</script>
