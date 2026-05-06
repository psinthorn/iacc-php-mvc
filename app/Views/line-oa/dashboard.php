<?php
$pageTitle = 'LINE OA — Dashboard';

/**
 * LINE OA Dashboard — redesigned to match API Sales Channel dashboard style
 * Uses master-data.css design system (stat-card, stats-row, master-data-container)
 *
 * Variables from LineOAController::dashboard():
 *   $stats, $config, $recentOrders, $recentMessages, $dailyMessages
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'LINE OA Dashboard',
        'total_users' => 'Total Users',
        'total_orders' => 'Total Orders',
        'pending_orders' => 'Pending Orders',
        'today_messages' => "Today's Messages",
        'total_revenue' => 'Total Revenue',
        'recent_orders' => 'Recent Orders',
        'recent_messages' => 'Recent Messages',
        'order_ref' => 'Order Ref',
        'customer' => 'Customer',
        'type' => 'Type',
        'status' => 'Status',
        'amount' => 'Amount',
        'date' => 'Date',
        'direction' => 'Direction',
        'message' => 'Message',
        'view_all' => 'View All',
        'not_configured' => 'LINE OA is not configured yet.',
        'not_configured_desc' => 'Connect your LINE Official Account to start receiving messages and orders from LINE users.',
        'configure_now' => 'Configure Now',
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
        'no_orders' => 'No orders yet',
        'no_messages' => 'No messages yet',
        'channel_info' => 'Channel Information',
        'channel_id' => 'Channel ID',
        'connection_status' => 'Connection Status',
        'connected' => 'Connected',
        'disconnected' => 'Disconnected',
        'webhook_url' => 'Webhook URL',
        'webhook_configured' => 'Configured',
        'webhook_not_set' => 'Not Set',
        'auto_reply' => 'Auto Reply',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'greeting_msg' => 'Greeting Message',
        'set' => 'Set',
        'not_set' => 'Not Set',
        'daily_messages' => 'Daily Messages (Last 7 Days)',
        'messages_per_day' => 'Messages per day',
        'no_data' => 'No message data yet.',
        'completed' => 'Completed',
        'confirmed' => 'Confirmed',
        'linked_orders' => 'Linked PR/PO',
        'total_messages' => 'Total Messages',
        'settings' => 'Settings',
        'orders' => 'Orders',
        'messages' => 'Messages',
        'users' => 'Users',
        'auto_replies' => 'Auto Replies',
        'send_message' => 'Send Message',
        'pending' => 'Pending',
        'processing' => 'Processing',
        'cancelled' => 'Cancelled',
        'created_at' => 'Created',
    ],
    'th' => [
        'page_title' => 'แดชบอร์ด LINE OA',
        'total_users' => 'ผู้ใช้ทั้งหมด',
        'total_orders' => 'คำสั่งซื้อทั้งหมด',
        'pending_orders' => 'รอดำเนินการ',
        'today_messages' => 'ข้อความวันนี้',
        'total_revenue' => 'รายได้รวม',
        'recent_orders' => 'คำสั่งซื้อล่าสุด',
        'recent_messages' => 'ข้อความล่าสุด',
        'order_ref' => 'เลขอ้างอิง',
        'customer' => 'ลูกค้า',
        'type' => 'ประเภท',
        'status' => 'สถานะ',
        'amount' => 'จำนวนเงิน',
        'date' => 'วันที่',
        'direction' => 'ทิศทาง',
        'message' => 'ข้อความ',
        'view_all' => 'ดูทั้งหมด',
        'not_configured' => 'ยังไม่ได้ตั้งค่า LINE OA',
        'not_configured_desc' => 'เชื่อมต่อบัญชี LINE Official Account เพื่อเริ่มรับข้อความและคำสั่งซื้อจากผู้ใช้ LINE',
        'configure_now' => 'ตั้งค่าเลย',
        'inbound' => 'ขาเข้า',
        'outbound' => 'ขาออก',
        'no_orders' => 'ยังไม่มีคำสั่งซื้อ',
        'no_messages' => 'ยังไม่มีข้อความ',
        'channel_info' => 'ข้อมูลช่องทาง',
        'channel_id' => 'Channel ID',
        'connection_status' => 'สถานะการเชื่อมต่อ',
        'connected' => 'เชื่อมต่อแล้ว',
        'disconnected' => 'ยังไม่เชื่อมต่อ',
        'webhook_url' => 'Webhook URL',
        'webhook_configured' => 'ตั้งค่าแล้ว',
        'webhook_not_set' => 'ยังไม่ตั้งค่า',
        'auto_reply' => 'ตอบกลับอัตโนมัติ',
        'enabled' => 'เปิดใช้งาน',
        'disabled' => 'ปิดใช้งาน',
        'greeting_msg' => 'ข้อความต้อนรับ',
        'set' => 'ตั้งค่าแล้ว',
        'not_set' => 'ยังไม่ตั้งค่า',
        'daily_messages' => 'ข้อความรายวัน (7 วันล่าสุด)',
        'messages_per_day' => 'ข้อความต่อวัน',
        'no_data' => 'ยังไม่มีข้อมูลข้อความ',
        'completed' => 'เสร็จสิ้น',
        'confirmed' => 'ยืนยันแล้ว',
        'linked_orders' => 'เชื่อมโยง PR/PO',
        'total_messages' => 'ข้อความทั้งหมด',
        'settings' => 'ตั้งค่า',
        'orders' => 'คำสั่งซื้อ',
        'messages' => 'ข้อความ',
        'users' => 'ผู้ใช้',
        'auto_replies' => 'ตอบกลับอัตโนมัติ',
        'send_message' => 'ส่งข้อความ',
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังดำเนินการ',
        'cancelled' => 'ยกเลิก',
        'created_at' => 'วันที่สร้าง',
    ]
];
$t = $labels[$lang];

$statusBadge = [
    'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'primary',
    'completed' => 'success', 'cancelled' => 'danger'
];
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Header with navigation -->
<div class="master-data-header">
    <h2><i class="fa fa-comment"></i> <?= $t['page_title'] ?></h2>
    <div>
        <a href="index.php?page=line_settings" class="btn btn-sm btn-outline-primary"><i class="fa fa-cog"></i> <?= $t['settings'] ?></a>
        <a href="index.php?page=line_orders" class="btn btn-sm btn-outline-primary"><i class="fa fa-shopping-cart"></i> <?= $t['orders'] ?></a>
        <a href="index.php?page=line_messages" class="btn btn-sm btn-outline-primary"><i class="fa fa-comments"></i> <?= $t['messages'] ?></a>
        <a href="index.php?page=line_users" class="btn btn-sm btn-outline-primary"><i class="fa fa-users"></i> <?= $t['users'] ?></a>
        <a href="index.php?page=line_auto_replies" class="btn btn-sm btn-outline-primary"><i class="fa fa-reply-all"></i> <?= $t['auto_replies'] ?></a>
        <a href="index.php?page=line_send_message" class="btn btn-sm btn-outline-primary"><i class="fa fa-paper-plane"></i> <?= $t['send_message'] ?></a>
    </div>
</div>

<!-- Flash Messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php if (!$config): ?>
<!-- Not Configured — Full Page CTA -->
<div style="text-align:center; padding:60px 20px;">
    <i class="fa fa-comment" style="font-size:4rem; color:#06C755; margin-bottom:20px;"></i>
    <h3><?= $t['not_configured'] ?></h3>
    <p style="color:#666; max-width:500px; margin:10px auto 30px;"><?= $t['not_configured_desc'] ?></p>
    <a href="index.php?page=line_settings" class="btn btn-success btn-lg">
        <i class="fa fa-cog"></i> <?= $t['configure_now'] ?>
    </a>
</div>

<?php else: ?>

<!-- Channel Info + Connection Status -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-top:0; margin-bottom:15px;"><i class="fa fa-info-circle"></i> <?= $t['channel_info'] ?></h4>
    <div style="display:flex; flex-wrap:wrap; gap:20px;">
        <div style="flex:1; min-width:200px;">
            <table class="table table-condensed" style="margin:0;">
                <tr>
                    <th style="width:140px; border-top:none;"><?= $t['channel_id'] ?></th>
                    <td style="border-top:none;"><code><?= htmlspecialchars($config['channel_id'] ?? '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                </tr>
                <tr>
                    <th><?= $t['webhook_url'] ?></th>
                    <td>
                        <?php if (!empty($config['webhook_url'])): ?>
                            <span class="label label-success"><i class="fa fa-check"></i> <?= $t['webhook_configured'] ?></span>
                            <small class="text-muted" style="margin-left:5px;"><?= htmlspecialchars($config['webhook_url'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php else: ?>
                            <span class="label label-danger"><i class="fa fa-times"></i> <?= $t['webhook_not_set'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <div style="flex:1; min-width:200px;">
            <table class="table table-condensed" style="margin:0;">
                <tr>
                    <th style="width:140px; border-top:none;"><?= $t['connection_status'] ?></th>
                    <td style="border-top:none;">
                        <span id="line-health-badge">
                        <?php
                        // v6.3 — show real probe state (not just is_active toggle)
                        $probeStatus = $config['last_probe_status'] ?? 'unknown';
                        $probeAt     = $config['last_probe_at'] ?? null;
                        if ($probeStatus === 'connected'): ?>
                            <span class="label label-success"><i class="fa fa-plug"></i> <?= $t['connected'] ?></span>
                        <?php elseif ($probeStatus === 'invalid_credentials'): ?>
                            <span class="label label-warning"><i class="fa fa-key"></i> <?= ($lang === 'th') ? 'ข้อมูลรับรองไม่ถูกต้อง' : 'Invalid credentials' ?></span>
                        <?php elseif ($probeStatus === 'unreachable'): ?>
                            <span class="label label-danger"><i class="fa fa-chain-broken"></i> <?= ($lang === 'th') ? 'ติดต่อเซิร์ฟเวอร์ LINE ไม่ได้' : 'Unreachable' ?></span>
                        <?php elseif ($config['is_active']): ?>
                            <span class="label label-info"><i class="fa fa-question-circle"></i> <?= ($lang === 'th') ? 'ยังไม่ตรวจสอบ' : 'Not yet probed' ?></span>
                        <?php else: ?>
                            <span class="label label-default"><i class="fa fa-times-circle"></i> <?= $t['disconnected'] ?></span>
                        <?php endif; ?>
                        </span>
                        <button type="button" id="line-health-retest" class="btn btn-xs btn-outline-secondary" style="margin-left:8px;" title="<?= ($lang === 'th') ? 'ทดสอบอีกครั้ง' : 'Re-test' ?>">
                            <i class="fa fa-refresh"></i>
                        </button>
                        <?php if ($probeAt): ?>
                            <small class="text-muted" style="display:block; margin-top:4px;">
                                <?= ($lang === 'th') ? 'ตรวจสอบล่าสุด' : 'Last checked' ?>:
                                <?= date('M d, H:i', strtotime($probeAt)) ?>
                            </small>
                        <?php endif; ?>
                        <?php if (!empty($config['last_probe_error'])): ?>
                            <small class="text-danger" style="display:block; margin-top:4px;">
                                <?= htmlspecialchars($config['last_probe_error'], ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($config['bot_display_name'])): ?>
                <tr>
                    <th><?= ($lang === 'th') ? 'ชื่อบอท' : 'Bot Name' ?></th>
                    <td>
                        <?php if (!empty($config['bot_picture_url'])): ?>
                            <img src="<?= htmlspecialchars($config['bot_picture_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:24px; height:24px; border-radius:50%; vertical-align:middle; margin-right:6px;">
                        <?php endif; ?>
                        <?= htmlspecialchars($config['bot_display_name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($config['bot_basic_id'])): ?>
                            <small class="text-muted">(<?= htmlspecialchars($config['bot_basic_id'], ENT_QUOTES, 'UTF-8') ?>)</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?= $t['auto_reply'] ?></th>
                    <td>
                        <?php if ($config['auto_reply_enabled'] ?? 0): ?>
                            <span class="label label-success"><i class="fa fa-check"></i> <?= $t['enabled'] ?></span>
                        <?php else: ?>
                            <span class="label label-default"><i class="fa fa-minus"></i> <?= $t['disabled'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= $t['greeting_msg'] ?></th>
                    <td>
                        <?php if (!empty($config['greeting_message'])): ?>
                            <span class="label label-success"><?= $t['set'] ?></span>
                        <?php else: ?>
                            <span class="label label-default"><?= $t['not_set'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- KPI Stat Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-users stat-icon"></i>
        <div class="stat-value"><?= number_format($stats['total_users'] ?? 0) ?></div>
        <div class="stat-label"><a href="index.php?page=line_users" style="color:inherit;"><?= $t['total_users'] ?></a></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-shopping-cart stat-icon"></i>
        <div class="stat-value"><?= number_format($stats['total_orders'] ?? 0) ?></div>
        <div class="stat-label"><a href="index.php?page=line_orders" style="color:inherit;"><?= $t['total_orders'] ?></a></div>
    </div>
    <div class="stat-card warning">
        <i class="fa fa-clock-o stat-icon"></i>
        <div class="stat-value"><?= number_format($stats['pending_orders'] ?? 0) ?></div>
        <div class="stat-label"><a href="index.php?page=line_orders&amp;status=pending" style="color:inherit;"><?= $t['pending_orders'] ?></a></div>
    </div>
    <div class="stat-card info">
        <i class="fa fa-envelope stat-icon"></i>
        <div class="stat-value"><?= number_format($stats['today_messages'] ?? 0) ?></div>
        <div class="stat-label"><a href="index.php?page=line_messages" style="color:inherit;"><?= $t['today_messages'] ?></a></div>
    </div>
    <div class="stat-card" style="border-left:4px solid #06C755;">
        <i class="fa fa-money stat-icon" style="color:#06C755;"></i>
        <div class="stat-value">฿<?= number_format($stats['total_revenue'] ?? 0, 0) ?></div>
        <div class="stat-label"><?= $t['total_revenue'] ?></div>
    </div>
</div>

<!-- Daily Messages Chart -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h4 style="margin:0;"><i class="fa fa-line-chart"></i> <?= $t['daily_messages'] ?></h4>
        <small style="color:#777;"><?= $t['messages_per_day'] ?></small>
    </div>
    <?php
    $dailyRows = $dailyMessages ?? [];
    $maxMsg = 1;
    foreach ($dailyRows as $r) {
        $maxMsg = max($maxMsg, intval($r['total'] ?? 0));
    }
    ?>
    <?php if (empty($dailyRows)): ?>
        <p style="color:#999; margin:0;"><?= $t['no_data'] ?></p>
    <?php else: ?>
        <div style="display:flex; gap:8px; align-items:flex-end; height:180px; padding:10px 0;">
            <?php foreach ($dailyRows as $r): ?>
                <?php
                $total = intval($r['total'] ?? 0);
                $inbound = intval($r['inbound'] ?? 0);
                $outbound = intval($r['outbound'] ?? 0);
                $h = max(6, intval(($total / $maxMsg) * 140));
                $barColor = $outbound > $inbound ? '#06C755' : '#3498db';
                ?>
                <div style="flex:1; min-width:30px; text-align:center;">
                    <div title="<?= htmlspecialchars($r['day'], ENT_QUOTES, 'UTF-8') ?>: <?= $inbound ?> <?= $t['inbound'] ?>, <?= $outbound ?> <?= $t['outbound'] ?>"
                         style="height:<?= $h ?>px; background:<?= $barColor ?>; border-radius:6px 6px 0 0;"></div>
                    <div style="font-size:0.75rem; color:#666; margin-top:6px;"><?= date('m/d', strtotime($r['day'])) ?></div>
                    <div style="font-size:0.75rem; color:#222;"><?= $total ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:8px; font-size:0.8rem;">
            <span style="display:inline-block; width:12px; height:12px; background:#3498db; border-radius:2px; vertical-align:middle;"></span> <?= $t['inbound'] ?>
            <span style="display:inline-block; width:12px; height:12px; background:#06C755; border-radius:2px; vertical-align:middle; margin-left:15px;"></span> <?= $t['outbound'] ?>
        </div>
    <?php endif; ?>
</div>

<!-- Order Stats Row -->
<div class="stats-row">
    <div class="stat-card success">
        <i class="fa fa-check stat-icon"></i>
        <div class="stat-value"><?= intval($stats['completed_orders'] ?? 0) ?></div>
        <div class="stat-label"><?= $t['completed'] ?></div>
    </div>
    <div class="stat-card info">
        <i class="fa fa-thumbs-up stat-icon"></i>
        <div class="stat-value"><?= intval($stats['confirmed_orders'] ?? 0) ?></div>
        <div class="stat-label"><?= $t['confirmed'] ?></div>
    </div>
    <div class="stat-card" style="border-left:4px solid #8e44ad;">
        <i class="fa fa-link stat-icon" style="color:#8e44ad;"></i>
        <div class="stat-value"><?= intval($stats['linked_orders'] ?? 0) ?></div>
        <div class="stat-label"><?= $t['linked_orders'] ?></div>
    </div>
    <div class="stat-card primary">
        <i class="fa fa-comments stat-icon"></i>
        <div class="stat-value"><?= number_format($stats['total_messages'] ?? 0) ?></div>
        <div class="stat-label"><?= $t['total_messages'] ?></div>
    </div>
</div>

<!-- Recent Orders + Messages -->
<div style="display:flex; gap:20px; flex-wrap:wrap;">
    <!-- Recent Orders -->
    <div style="flex:3; min-width:300px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h4 style="margin:0;"><i class="fa fa-shopping-cart"></i> <?= $t['recent_orders'] ?></h4>
                <a href="index.php?page=line_orders" style="font-size:0.9rem;"><?= $t['view_all'] ?> →</a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <p style="color:#999; text-align:center; padding:30px;"><?= $t['no_orders'] ?></p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" style="margin:0;">
                    <thead>
                        <tr>
                            <th><?= $t['order_ref'] ?></th>
                            <th><?= $t['customer'] ?></th>
                            <th><?= $t['type'] ?></th>
                            <th><?= $t['status'] ?></th>
                            <th><?= $t['amount'] ?></th>
                            <th><?= $t['created_at'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><a href="index.php?page=line_order_detail&amp;id=<?= (int)$order['id'] ?>"><?= htmlspecialchars($order['order_ref'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars($order['display_name'] ?? $order['guest_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="label label-default"><?= htmlspecialchars($order['order_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><span class="label label-<?= $statusBadge[$order['status']] ?? 'default' ?>"><?= $t[$order['status']] ?? ucfirst($order['status'] ?? '') ?></span></td>
                            <td>฿<?= number_format($order['total_amount'] ?? 0, 2) ?></td>
                            <td><?= !empty($order['created_at']) ? date('M d, H:i', strtotime($order['created_at'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Messages -->
    <div style="flex:2; min-width:280px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h4 style="margin:0;"><i class="fa fa-comments"></i> <?= $t['recent_messages'] ?></h4>
                <a href="index.php?page=line_messages" style="font-size:0.9rem;"><?= $t['view_all'] ?> →</a>
            </div>
            <?php if (empty($recentMessages)): ?>
                <p style="color:#999; text-align:center; padding:30px;"><?= $t['no_messages'] ?></p>
            <?php else: ?>
                <?php foreach ($recentMessages as $msg): ?>
                <div style="padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong><?= htmlspecialchars($msg['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="label label-<?= $msg['direction'] === 'inbound' ? 'info' : 'success' ?>" style="margin-left:5px;"><?= $t[$msg['direction']] ?? $msg['direction'] ?></span>
                        </div>
                        <small style="color:#999;"><?= date('H:i', strtotime($msg['created_at'])) ?></small>
                    </div>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;"><?= htmlspecialchars(mb_substr($msg['content'] ?? '', 0, 80), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($msg['content'] ?? '') > 80 ? '...' : '' ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

</div>

<?php if (!empty($config)): ?>
<script>
// v6.3 — Health probe re-test button
(function() {
    var btn = document.getElementById('line-health-retest');
    var badge = document.getElementById('line-health-badge');
    if (!btn || !badge) return;
    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        fetch('index.php?page=line_probe', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(j) {
                var map = {
                    connected:           '<span class="label label-success"><i class="fa fa-plug"></i> Connected</span>',
                    invalid_credentials: '<span class="label label-warning"><i class="fa fa-key"></i> Invalid credentials</span>',
                    unreachable:         '<span class="label label-danger"><i class="fa fa-chain-broken"></i> Unreachable</span>',
                    unknown:             '<span class="label label-default"><i class="fa fa-question-circle"></i> Unknown</span>'
                };
                badge.innerHTML = map[j.status] || map.unknown;
                if (j.status === 'connected') {
                    setTimeout(function() { window.location.reload(); }, 800);
                }
            })
            .catch(function() { badge.innerHTML = '<span class="label label-danger">Probe failed</span>'; })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh"></i>';
            });
    });
})();
</script>
<?php endif; ?>
