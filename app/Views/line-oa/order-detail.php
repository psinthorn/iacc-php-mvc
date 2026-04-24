<?php
$pageTitle = 'LINE OA — Order Details';

/**
 * LINE OA Order Detail
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'Order Detail',
        'order_info' => 'Order Information',
        'order_ref' => 'Order Ref',
        'customer' => 'Customer',
        'phone' => 'Phone',
        'email' => 'Email',
        'type' => 'Type',
        'status' => 'Status',
        'payment_status' => 'Payment Status',
        'amount' => 'Total Amount',
        'date' => 'Created',
        'notes' => 'Notes',
        'booking_date' => 'Booking Date',
        'booking_time' => 'Booking Time',
        'items' => 'Items',
        'item_name' => 'Name',
        'item_qty' => 'Qty',
        'item_price' => 'Price',
        'update_status' => 'Update Status',
        'confirm_payment' => 'Confirm Payment',
        'reject_payment' => 'Reject Payment',
        'back' => 'Back to Orders',
        'conversation' => 'Conversation',
        'no_messages' => 'No messages',
        'payment_slip' => 'Payment Slip',
        'processed_by' => 'Processed By',
        'processed_at' => 'Processed At',
        'linked_pr' => 'Linked PR',
        'linked_po' => 'Linked PO',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'customer_order' => 'Customer Order',
        'agent_order' => 'Agent Order',
        'booking' => 'Booking',
        'unpaid' => 'Unpaid',
        'slip_uploaded' => 'Slip Uploaded',
        'payment_confirmed' => 'Confirmed',
        'rejected' => 'Rejected',
    ],
    'th' => [
        'page_title' => 'รายละเอียดคำสั่งซื้อ',
        'order_info' => 'ข้อมูลคำสั่งซื้อ',
        'order_ref' => 'เลขอ้างอิง',
        'customer' => 'ลูกค้า',
        'phone' => 'โทรศัพท์',
        'email' => 'อีเมล',
        'type' => 'ประเภท',
        'status' => 'สถานะ',
        'payment_status' => 'สถานะการชำระเงิน',
        'amount' => 'จำนวนเงินรวม',
        'date' => 'วันที่สร้าง',
        'notes' => 'หมายเหตุ',
        'booking_date' => 'วันที่จอง',
        'booking_time' => 'เวลาจอง',
        'items' => 'รายการ',
        'item_name' => 'ชื่อ',
        'item_qty' => 'จำนวน',
        'item_price' => 'ราคา',
        'update_status' => 'อัปเดตสถานะ',
        'confirm_payment' => 'ยืนยันการชำระเงิน',
        'reject_payment' => 'ปฏิเสธการชำระเงิน',
        'back' => 'กลับไปรายการ',
        'conversation' => 'การสนทนา',
        'no_messages' => 'ไม่มีข้อความ',
        'payment_slip' => 'สลิปการชำระเงิน',
        'processed_by' => 'ดำเนินการโดย',
        'processed_at' => 'เวลาดำเนินการ',
        'linked_pr' => 'PR ที่เชื่อมโยง',
        'linked_po' => 'PO ที่เชื่อมโยง',
        'pending' => 'รอดำเนินการ',
        'confirmed' => 'ยืนยันแล้ว',
        'processing' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก',
        'customer_order' => 'สั่งซื้อ',
        'agent_order' => 'ตัวแทนสั่ง',
        'booking' => 'จอง',
        'unpaid' => 'ยังไม่ชำระ',
        'slip_uploaded' => 'อัปโหลดสลิปแล้ว',
        'payment_confirmed' => 'ยืนยันแล้ว',
        'rejected' => 'ปฏิเสธ',
    ]
];
$t = $labels[$lang];
$statusBadge = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','completed'=>'success','cancelled'=>'danger'];

// Parse items JSON
$items = [];
if (!empty($order['items_json'])) {
    $items = json_decode($order['items_json'], true) ?? [];
}
?>

<?php $currentNavPage = 'line_order_detail'; $navIcon = 'fa-file-text-o'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="row">
    <!-- Order Info -->
    <div class="col-lg-6">
        <div style="background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:15px;">
            <div style="padding:15px 20px; border-bottom:1px solid #eee; font-weight:600;"><i class="fa fa-info-circle"></i> <?= $t['order_info'] ?></div>
            <div class="panel-body">
                <table class="table table-bordered">
                    <tr><th style="width:35%"><?= $t['order_ref'] ?></th><td><strong><?= htmlspecialchars($order['order_ref'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td></tr>
                    <tr><th><?= $t['customer'] ?></th><td><?= htmlspecialchars($order['display_name'] ?? $order['guest_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th><?= $t['phone'] ?></th><td><?= htmlspecialchars($order['guest_phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th><?= $t['email'] ?></th><td><?= htmlspecialchars($order['guest_email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><th><?= $t['type'] ?></th><td><span class="label label-default"><?= $t[$order['order_type']] ?? htmlspecialchars($order['order_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                    <tr><th><?= $t['status'] ?></th><td><span class="label label-<?= $statusBadge[$order['status']] ?? 'default' ?>"><?= $t[$order['status']] ?? ucfirst($order['status'] ?? '') ?></span></td></tr>
                    <tr><th><?= $t['payment_status'] ?></th><td><span class="label label-<?= ['unpaid'=>'default','slip_uploaded'=>'warning','confirmed'=>'success','rejected'=>'danger'][$order['payment_status'] ?? ''] ?? 'default' ?>"><?= $t[$order['payment_status']] ?? ucfirst($order['payment_status'] ?? 'unpaid') ?></span></td></tr>
                    <tr><th><?= $t['amount'] ?></th><td><strong><?= number_format($order['total_amount'] ?? 0, 2) ?> <?= $order['currency'] ?? 'THB' ?></strong></td></tr>
                    <tr><th><?= $t['date'] ?></th><td><?= $order['created_at'] ?? '-' ?></td></tr>
                    <?php if (!empty($order['booking_date'])): ?>
                    <tr><th><?= $t['booking_date'] ?></th><td><?= $order['booking_date'] ?></td></tr>
                    <tr><th><?= $t['booking_time'] ?></th><td><?= $order['booking_time'] ?? '-' ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($order['notes'])): ?>
                    <tr><th><?= $t['notes'] ?></th><td><?= htmlspecialchars($order['notes'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($order['linked_pr_id'])): ?>
                    <tr><th><?= $t['linked_pr'] ?></th><td><a href="index.php?page=pr_view&id=<?= (int)$order['linked_pr_id'] ?>">#<?= (int)$order['linked_pr_id'] ?></a></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($order['linked_po_id'])): ?>
                    <tr><th><?= $t['linked_po'] ?></th><td><a href="index.php?page=po_view&id=<?= (int)$order['linked_po_id'] ?>">#<?= (int)$order['linked_po_id'] ?></a></td></tr>
                    <?php endif; ?>
                </table>

                <!-- Items -->
                <?php if (!empty($items)): ?>
                <h4><?= $t['items'] ?></h4>
                <table class="table table-bordered table-condensed">
                    <thead><tr><th><?= $t['item_name'] ?></th><th><?= $t['item_qty'] ?></th><th><?= $t['item_price'] ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= intval($item['qty'] ?? 1) ?></td>
                            <td><?= number_format($item['price'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div style="background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:15px;">
            <div style="padding:15px 20px; border-bottom:1px solid #eee; font-weight:600;"><i class="fa fa-cogs"></i> <?= $t['update_status'] ?></div>
            <div class="panel-body">
                <form method="POST" action="index.php?page=line_store" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="update_order_status">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" class="form-control">
                        <?php foreach (['pending','confirmed','processing','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($order['status'] ?? '') === $s ? 'selected' : '' ?>><?= $t[$s] ?? ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> <?= $t['update_status'] ?></button>
                </form>

                <?php if (($order['payment_status'] ?? '') === 'slip_uploaded'): ?>
                <hr>
                <form method="POST" action="index.php?page=line_store" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="confirm_payment">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="payment_status" value="confirmed">
                    <button type="submit" class="btn btn-success"><i class="fa fa-check-circle"></i> <?= $t['confirm_payment'] ?></button>
                </form>
                <form method="POST" action="index.php?page=line_store" class="form-inline" style="margin-top: 5px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="confirm_payment">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="payment_status" value="rejected">
                    <button type="submit" class="btn btn-danger"><i class="fa fa-times-circle"></i> <?= $t['reject_payment'] ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Conversation -->
    <div class="col-lg-6">
        <div style="background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:15px;">
            <div style="padding:15px 20px; border-bottom:1px solid #eee; font-weight:600;"><i class="fa fa-comments"></i> <?= $t['conversation'] ?></div>
            <div class="panel-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <p class="text-muted"><?= $t['no_messages'] ?></p>
                <?php else: ?>
                    <?php foreach (array_reverse($messages) as $msg): ?>
                    <div class="<?= $msg['direction'] === 'inbound' ? 'text-left' : 'text-right' ?>" style="margin-bottom: 10px;">
                        <div style="display: inline-block; max-width: 80%; padding: 8px 12px; border-radius: 12px; background: <?= $msg['direction'] === 'inbound' ? '#f1f0f0' : '#DCF8C6' ?>;">
                            <small class="text-muted"><?= htmlspecialchars($msg['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?> · <?= date('H:i', strtotime($msg['created_at'])) ?></small><br>
                            <?= htmlspecialchars($msg['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div><!-- /master-data-container -->
