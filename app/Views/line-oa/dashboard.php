<?php
/**
 * LINE OA Dashboard
 * Shows stats, recent orders, recent messages
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
        'configure_now' => 'Configure Now',
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
        'no_orders' => 'No orders yet',
        'no_messages' => 'No messages yet',
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
        'configure_now' => 'ตั้งค่าเลย',
        'inbound' => 'ขาเข้า',
        'outbound' => 'ขาออก',
        'no_orders' => 'ยังไม่มีคำสั่งซื้อ',
        'no_messages' => 'ยังไม่มีข้อความ',
    ]
];
$t = $labels[$lang];

$statusBadge = [
    'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'primary',
    'completed' => 'success', 'cancelled' => 'danger'
];
?>

<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header"><i class="fa fa-comment"></i> <?= $t['page_title'] ?></h3>
    </div>
</div>

<?php if (!$config): ?>
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i> <?= $t['not_configured'] ?>
    <a href="?page=line_settings" class="btn btn-sm btn-success ms-3"><?= $t['configure_now'] ?></a>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-users fa-3x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['total_users'] ?? 0) ?></div>
                        <div><?= $t['total_users'] ?></div>
                    </div>
                </div>
            </div>
            <a href="?page=line_users"><div class="panel-footer"><span class="pull-left"><?= $t['view_all'] ?></span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-shopping-cart fa-3x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['total_orders'] ?? 0) ?></div>
                        <div><?= $t['total_orders'] ?></div>
                    </div>
                </div>
            </div>
            <a href="?page=line_orders"><div class="panel-footer"><span class="pull-left"><?= $t['view_all'] ?></span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-yellow">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-clock-o fa-3x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['pending_orders'] ?? 0) ?></div>
                        <div><?= $t['pending_orders'] ?></div>
                    </div>
                </div>
            </div>
            <a href="?page=line_orders&status=pending"><div class="panel-footer"><span class="pull-left"><?= $t['view_all'] ?></span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div></a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-envelope fa-3x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['today_messages'] ?? 0) ?></div>
                        <div><?= $t['today_messages'] ?></div>
                    </div>
                </div>
            </div>
            <a href="?page=line_messages"><div class="panel-footer"><span class="pull-left"><?= $t['view_all'] ?></span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div></a>
        </div>
    </div>
</div>

<!-- Recent Orders + Messages -->
<div class="row">
    <div class="col-lg-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-shopping-cart"></i> <?= $t['recent_orders'] ?>
                <a href="?page=line_orders" class="pull-right"><?= $t['view_all'] ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <div class="panel-body">
                <?php if (empty($recentOrders)): ?>
                    <p class="text-muted"><?= $t['no_orders'] ?></p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t['order_ref'] ?></th>
                                <th><?= $t['customer'] ?></th>
                                <th><?= $t['type'] ?></th>
                                <th><?= $t['status'] ?></th>
                                <th><?= $t['amount'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><a href="?page=line_order_detail&id=<?= $order['id'] ?>"><?= htmlspecialchars($order['order_ref'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><?= htmlspecialchars($order['display_name'] ?? $order['guest_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="label label-default"><?= htmlspecialchars($order['order_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><span class="label label-<?= $statusBadge[$order['status']] ?? 'default' ?>"><?= htmlspecialchars($order['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= number_format($order['total_amount'] ?? 0, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-comments"></i> <?= $t['recent_messages'] ?>
                <a href="?page=line_messages" class="pull-right"><?= $t['view_all'] ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <div class="panel-body">
                <?php if (empty($recentMessages)): ?>
                    <p class="text-muted"><?= $t['no_messages'] ?></p>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recentMessages as $msg): ?>
                    <div class="list-group-item">
                        <div class="row">
                            <div class="col-xs-8">
                                <strong><?= htmlspecialchars($msg['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="label label-<?= $msg['direction'] === 'inbound' ? 'info' : 'success' ?>"><?= $t[$msg['direction']] ?? $msg['direction'] ?></span>
                            </div>
                            <div class="col-xs-4 text-right">
                                <small class="text-muted"><?= date('H:i', strtotime($msg['created_at'])) ?></small>
                            </div>
                        </div>
                        <p class="text-muted" style="margin-top:5px; font-size:12px;"><?= htmlspecialchars(mb_substr($msg['content'] ?? '', 0, 80), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($msg['content'] ?? '') > 80 ? '...' : '' ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
