<?php
$pageTitle = 'LINE OA — Orders';

/**
 * LINE OA Orders List
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'LINE Orders',
        'order_ref' => 'Order Ref',
        'customer' => 'Customer',
        'type' => 'Type',
        'status' => 'Status',
        'payment' => 'Payment',
        'amount' => 'Amount',
        'date' => 'Date',
        'actions' => 'Actions',
        'view' => 'View',
        'all' => 'All',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'customer_order' => 'Customer Order',
        'agent_order' => 'Agent Order',
        'booking' => 'Booking',
        'no_orders' => 'No orders found.',
        'filter_status' => 'Filter by Status',
        'filter_type' => 'Filter by Type',
        'unpaid' => 'Unpaid',
        'slip_uploaded' => 'Slip Uploaded',
        'payment_confirmed' => 'Confirmed',
        'rejected' => 'Rejected',
    ],
    'th' => [
        'page_title' => 'คำสั่งซื้อ LINE',
        'order_ref' => 'เลขอ้างอิง',
        'customer' => 'ลูกค้า',
        'type' => 'ประเภท',
        'status' => 'สถานะ',
        'payment' => 'การชำระเงิน',
        'amount' => 'จำนวนเงิน',
        'date' => 'วันที่',
        'actions' => 'จัดการ',
        'view' => 'ดู',
        'all' => 'ทั้งหมด',
        'pending' => 'รอดำเนินการ',
        'confirmed' => 'ยืนยันแล้ว',
        'processing' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก',
        'customer_order' => 'สั่งซื้อ',
        'agent_order' => 'ตัวแทนสั่ง',
        'booking' => 'จอง',
        'no_orders' => 'ไม่พบคำสั่งซื้อ',
        'filter_status' => 'กรองตามสถานะ',
        'filter_type' => 'กรองตามประเภท',
        'unpaid' => 'ยังไม่ชำระ',
        'slip_uploaded' => 'อัปโหลดสลิปแล้ว',
        'payment_confirmed' => 'ยืนยันแล้ว',
        'rejected' => 'ปฏิเสธ',
    ]
];
$t = $labels[$lang];
$statusBadge = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','completed'=>'success','cancelled'=>'danger'];
$paymentBadge = ['unpaid'=>'default','slip_uploaded'=>'warning','confirmed'=>'success','rejected'=>'danger'];
?>

<?php $currentNavPage = 'line_orders'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<!-- Filters -->
<div style="margin-bottom: 15px;">
        <div class="btn-group">
            <a href="index.php?page=line_orders" class="btn btn-<?= !$currentStatus ? 'primary' : 'default' ?>"><?= $t['all'] ?></a>
            <?php foreach (['pending','confirmed','processing','completed','cancelled'] as $s): ?>
            <a href="index.php?page=line_orders&status=<?= $s ?>" class="btn btn-<?= $currentStatus === $s ? 'primary' : 'default' ?>"><?= $t[$s] ?></a>
            <?php endforeach; ?>
        </div>
        <div class="btn-group" style="margin-left: 10px;">
            <a href="index.php?page=line_orders" class="btn btn-<?= !$currentType ? 'info' : 'default' ?>"><?= $t['all'] ?></a>
            <?php foreach (['customer_order','agent_order','booking'] as $ot): ?>
            <a href="index.php?page=line_orders&order_type=<?= $ot ?>" class="btn btn-<?= $currentType === $ot ? 'info' : 'default' ?>"><?= $t[$ot] ?></a>
            <?php endforeach; ?>
        </div>
</div>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <?php if (empty($orders)): ?>
            <p class="text-muted text-center"><?= $t['no_orders'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= $t['order_ref'] ?></th>
                        <th><?= $t['customer'] ?></th>
                        <th><?= $t['type'] ?></th>
                        <th><?= $t['status'] ?></th>
                        <th><?= $t['payment'] ?></th>
                        <th><?= $t['amount'] ?></th>
                        <th><?= $t['date'] ?></th>
                        <th><?= $t['actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($order['order_ref'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($order['display_name'] ?? $order['guest_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="label label-default"><?= $t[$order['order_type']] ?? $order['order_type'] ?></span></td>
                        <td><span class="label label-<?= $statusBadge[$order['status']] ?? 'default' ?>"><?= $t[$order['status']] ?? $order['status'] ?></span></td>
                        <td><span class="label label-<?= $paymentBadge[$order['payment_status']] ?? 'default' ?>"><?= $t[$order['payment_status']] ?? $order['payment_status'] ?></span></td>
                        <td><?= number_format($order['total_amount'] ?? 0, 2) ?> <?= $order['currency'] ?? 'THB' ?></td>
                        <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                        <td><a href="index.php?page=line_order_detail&id=<?= $order['id'] ?>" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> <?= $t['view'] ?></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
</div>
</div><!-- /master-data-container -->
