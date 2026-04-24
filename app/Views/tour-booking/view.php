<?php
$pageTitle = 'Tour Bookings — Details';

/**
 * Tour Booking — Detail View (read-only)
 *
 * Variables: $booking, $message
 */

$isThai = ($_SESSION['lang'] ?? '0') === '1';

$statusConfig = [
    'draft'     => ['label' => $isThai ? 'ฉบับร่าง' : 'Draft',     'bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => 'fa-pencil'],
    'confirmed' => ['label' => $isThai ? 'ยืนยัน' : 'Confirmed',   'bg' => '#d1fae5', 'color' => '#059669', 'icon' => 'fa-check-circle'],
    'completed' => ['label' => $isThai ? 'เสร็จสิ้น' : 'Completed', 'bg' => '#dbeafe', 'color' => '#2563eb', 'icon' => 'fa-flag-checkered'],
    'cancelled' => ['label' => $isThai ? 'ยกเลิก' : 'Cancelled',   'bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'fa-ban'],
];

$sc = $statusConfig[$booking['status']] ?? $statusConfig['draft'];

$itemTypeLabels = [
    'tour'     => $isThai ? 'ทัวร์' : 'Tour',
    'transfer' => $isThai ? 'รถรับส่ง' : 'Transfer',
    'entrance' => $isThai ? 'ค่าเข้าชม' : 'Entrance',
    'extra'    => $isThai ? 'อื่นๆ' : 'Extra',
    'hotel'    => $isThai ? 'โรงแรม' : 'Hotel',
];

$paxTypeLabels = [
    'adult'  => $isThai ? 'ผู้ใหญ่' : 'Adult',
    'child'  => $isThai ? 'เด็ก' : 'Child',
    'infant' => $isThai ? 'ทารก' : 'Infant',
];

$custName = ($isThai && !empty($booking['customer_name_th']))
    ? $booking['customer_name_th']
    : ($booking['customer_name'] ?: ($booking['contact']['contact_name'] ?? '') ?: '-');
$agentName    = ($isThai && !empty($booking['agent_name_th'])) ? $booking['agent_name_th'] : ($booking['agent_name'] ?: '-');
$salesRepName = ($isThai && !empty($booking['sales_rep_name_th'])) ? $booking['sales_rep_name_th'] : ($booking['sales_rep_name'] ?: '-');

$messages = [
    'created'        => ['✅', $isThai ? 'สร้างการจองสำเร็จ' : 'Booking created successfully'],
    'updated'        => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Booking updated successfully'],
    'docs_generated' => ['✅', $isThai ? 'สร้างเอกสารสำเร็จ (PR, PO, ใบส่งของ, ใบแจ้งหนี้)' : 'Documents generated (PR, PO, Delivery, Invoice)'],
    'docs_error'     => ['⚠️', $isThai ? 'สร้างเอกสารไม่สำเร็จ' : 'Failed to generate documents'],
    'payment_recorded' => ['✅', $isThai ? 'บันทึกการชำระเงินสำเร็จ' : 'Payment recorded successfully'],
    'payment_deleted'  => ['✅', $isThai ? 'ลบรายการชำระเงินแล้ว' : 'Payment deleted'],
    'payment_approved' => ['✅', $isThai ? 'อนุมัติการชำระเงินแล้ว' : 'Payment approved'],
    'payment_rejected' => ['✅', $isThai ? 'ปฏิเสธการชำระเงินแล้ว' : 'Payment rejected'],
    'refund_recorded'  => ['✅', $isThai ? 'บันทึกการคืนเงินสำเร็จ' : 'Refund recorded'],
    'payment_error'    => ['⚠️', $isThai ? 'บันทึกการชำระเงินไม่สำเร็จ' : 'Failed to record payment'],
    'invalid_amount'   => ['⚠️', $isThai ? 'จำนวนเงินไม่ถูกต้อง' : 'Invalid amount'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.vw-container { max-width: 960px; margin: 0 auto; }
.vw-card { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 16px; }
.vw-card h3 { font-size: 15px; font-weight: 600; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.vw-card h3 i { color: #0d9488; margin-right: 6px; }
.vw-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.vw-grid.cols-2 { grid-template-columns: 1fr 1fr; }
.vw-grid.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
.vw-item { }
.vw-item .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; margin-bottom: 4px; }
.vw-item .val { font-size: 14px; color: #1e293b; font-weight: 500; }
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 10px; font-size: 12px; font-weight: 600; }
.vw-table { width: 100%; border-collapse: collapse; }
.vw-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; text-align: left; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; }
.vw-table td { padding: 10px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
.vw-table tr:last-child td { border-bottom: none; }
.vw-table .right { text-align: right; }
.vw-totals { max-width: 350px; margin-left: auto; margin-top: 16px; }
.vw-totals .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #475569; }
.vw-totals .row.grand { font-size: 18px; font-weight: 700; color: #0d9488; border-top: 2px solid #e2e8f0; padding-top: 12px; margin-top: 8px; }
.doc-links { display: flex; gap: 10px; flex-wrap: wrap; }
.doc-link { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid #e2e8f0; color: #475569; }
.doc-link:hover { background: #f8fafc; }
.doc-link i { font-size: 14px; }
.doc-empty { color: #cbd5e1; font-size: 13px; font-style: italic; }
.action-bar { display: flex; gap: 10px; margin-top: 20px; }
.action-bar a, .action-bar button { padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-edit { background: #0d9488; color: white; }
.btn-edit:hover { background: #0f766e; }
.btn-back { background: white; color: #64748b; border: 1px solid #e2e8f0; }
.btn-back:hover { background: #f8fafc; }
.pax-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 8px; font-size: 11px; font-weight: 600; }

@media (max-width: 768px) { .vw-grid { grid-template-columns: 1fr; } .vw-grid.cols-4 { grid-template-columns: 1fr 1fr; } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-calendar-check-o"></i> <?= htmlspecialchars($booking['booking_number']) ?></h2>
                <p>
                    <span class="status-badge status-<?= htmlspecialchars($booking['status']) ?>">
                        <i class="fa <?= $sc['icon'] ?>"></i> <?= $sc['label'] ?>
                    </span>
                </p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
                <a href="index.php?page=tour_booking_make&id=<?= $booking['id'] ?>" class="btn-header btn-header-primary">
                    <i class="fa fa-pencil"></i> <?= $isThai ? 'แก้ไข' : 'Edit' ?>
                </a>
                <a href="index.php?page=tour_booking_print&id=<?= $booking['id'] ?>" target="_blank" class="btn-header btn-header-primary">
                    <i class="fa fa-print"></i> <?= $isThai ? 'พิมพ์ Voucher' : 'Print Voucher' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#f0fdf4; border-left:4px solid #10b981; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <div class="vw-container">

        <!-- Booking Info -->
        <div class="vw-card">
            <h3><i class="fa fa-info-circle"></i> <?= $isThai ? 'ข้อมูลการจอง' : 'Booking Info' ?></h3>
            <div class="vw-grid">
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'วันที่จอง' : 'Booking Date' ?></div>
                    <div class="val"><i class="fa fa-calendar" style="color:#6366f1;"></i> <?= !empty($booking['booking_date']) ? date('d M Y', strtotime($booking['booking_date'])) : '-' ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'วันเดินทาง' : 'Trip Date' ?></div>
                    <div class="val"><i class="fa fa-plane" style="color:#0d9488;"></i> <?= date('d M Y', strtotime($booking['travel_date'])) ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ลูกค้า' : 'Customer' ?></div>
                    <div class="val"><?= htmlspecialchars($custName) ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ตัวแทน' : 'Agent' ?></div>
                    <div class="val"><?= htmlspecialchars($agentName) ?>
                        <?php if (!empty($booking['agent_mobile'])): ?>
                        <span style="font-size:12px; color:#64748b; display:block;"><?= htmlspecialchars($booking['agent_mobile']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($booking['agent_email'])): ?>
                        <span style="font-size:12px; color:#64748b; display:block;"><?= htmlspecialchars($booking['agent_email']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'พนักงานขาย' : 'Sales Rep' ?></div>
                    <div class="val"><?= htmlspecialchars($salesRepName) ?>
                        <?php if (!empty($booking['sales_rep_mobile'])): ?>
                        <span style="font-size:12px; color:#64748b; display:block;"><?= htmlspecialchars($booking['sales_rep_mobile']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($booking['sales_rep_email'])): ?>
                        <span style="font-size:12px; color:#64748b; display:block;"><?= htmlspecialchars($booking['sales_rep_email']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($booking['sales_rep_messengers'])): ?>
                        <span style="font-size:12px; color:#64748b; display:block;"><?= htmlspecialchars($booking['sales_rep_messengers']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ผู้จอง' : 'Booking By' ?></div>
                    <div class="val"><?= htmlspecialchars($booking['booking_by'] ?: '-') ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'เลข Voucher' : 'Voucher #' ?></div>
                    <div class="val"><?= htmlspecialchars($booking['voucher_number'] ?: '-') ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'สกุลเงิน' : 'Currency' ?></div>
                    <div class="val"><?= htmlspecialchars($booking['currency']) ?></div>
                </div>
            </div>
        </div>

        <!-- Pax & Pickup -->
        <div class="vw-card">
            <h3><i class="fa fa-car"></i> <?= $isThai ? 'ผู้เดินทาง & การรับ-ส่ง' : 'Passengers & Pickup' ?></h3>
            <div class="vw-grid cols-4">
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ผู้ใหญ่' : 'Adults' ?></div>
                    <div class="val"><?= $booking['pax_adult'] ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'เด็ก' : 'Children' ?></div>
                    <div class="val"><?= $booking['pax_child'] ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ทารก' : 'Infants' ?></div>
                    <div class="val"><?= $booking['pax_infant'] ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'รวม' : 'Total Pax' ?></div>
                    <div class="val" style="font-weight:700; color:#0d9488;"><?= $booking['total_pax'] ?></div>
                </div>
            </div>
            <div class="vw-grid cols-4" style="margin-top:16px;">
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'จุดรับ' : 'Pickup Location' ?></div>
                    <div class="val"><?= htmlspecialchars($booking['pickup_location_name'] ?? '-') ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'โรงแรม' : 'Hotel' ?></div>
                    <div class="val"><?= htmlspecialchars($booking['pickup_hotel'] ?: '-') ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ห้อง' : 'Room' ?></div>
                    <div class="val"><?= htmlspecialchars($booking['pickup_room'] ?: '-') ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'เวลารับ' : 'Pickup Time' ?></div>
                    <div class="val"><?= $booking['pickup_time'] ? date('H:i', strtotime($booking['pickup_time'])) : '-' ?></div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="vw-card">
            <h3><i class="fa fa-list"></i> <?= $isThai ? 'รายการ' : 'Items' ?> (<?= count($booking['items']) ?>)</h3>

            <?php if (!empty($booking['items'])): ?>
            <table class="vw-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                        <th><?= $isThai ? 'รายละเอียด' : 'Description' ?></th>
                        <th class="right"><?= $isThai ? 'ยอดรวม' : 'Amount' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booking['items'] as $idx => $item): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <span style="padding:2px 8px; border-radius:6px; background:#f1f5f9; font-size:11px; font-weight:600;">
                                <?= $itemTypeLabels[$item['item_type']] ?? $item['item_type'] ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600; margin-bottom:4px;">
                                <?= htmlspecialchars($item['description']) ?>
                                <?php if (!empty($item['type_name'])): ?>
                                <span style="color:#0d9488; font-weight:500; font-size:12px; background:#f0fdfa; padding:1px 6px; border-radius:4px; margin-left:4px;"><?= htmlspecialchars($item['type_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['model_name'])): ?>
                                <span style="color:#64748b; font-weight:500;"> | <?= htmlspecialchars($item['model_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['model_des'])): ?>
                                <div style="font-size:12px; color:#94a3b8; font-weight:400; margin-top:2px;"><?= htmlspecialchars($item['model_des']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php
                            $paxLines = json_decode($item['pax_lines_json'] ?? '[]', true) ?: [];
                            if (!empty($paxLines)):
                            ?>
                            <table style="width:auto; border-collapse:collapse; margin-top:4px; font-size:12px;">
                                <?php foreach ($paxLines as $pl):
                                    $plType = ($pl['type'] ?? 'adult') === 'child' ? ($isThai ? 'เด็ก' : 'Child') : ($isThai ? 'ผู้ใหญ่' : 'Adult');
                                    $plNat  = ($pl['nat'] ?? 'thai') === 'foreigner' ? '🌍 ' . ($isThai ? 'ต่างชาติ' : 'Foreign') : '🇹🇭 ' . ($isThai ? 'ไทย' : 'Thai');
                                    $plQty  = intval($pl['qty'] ?? 0);
                                    $plPrice = floatval($pl['price'] ?? 0);
                                    $plTotal = $plQty * $plPrice;
                                ?>
                                <tr style="color:#64748b;">
                                    <td style="padding:2px 8px 2px 0;"><?= $plType ?></td>
                                    <td style="padding:2px 8px;"><?= $plNat ?></td>
                                    <td style="padding:2px 8px; text-align:right;">×<?= $plQty ?></td>
                                    <td style="padding:2px 8px; text-align:right;">@<?= number_format($plPrice, 2) ?></td>
                                    <td style="padding:2px 0 2px 8px; text-align:right; font-weight:600; color:#0d9488;">= <?= number_format($plTotal, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                        </td>
                        <td class="right" style="font-weight:600; vertical-align:top;"><?= number_format($item['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="doc-empty"><?= $isThai ? 'ไม่มีรายการ' : 'No items' ?></p>
            <?php endif; ?>

            <!-- Totals -->
            <div class="vw-totals">
                <div class="row"><span><?= $isThai ? 'รวมย่อย' : 'Subtotal' ?></span><span><?= number_format($booking['subtotal'], 2) ?></span></div>
                <?php if ($booking['entrance_fee'] > 0): ?>
                <div class="row"><span><?= $isThai ? 'ค่าเข้าชม' : 'Entrance Fee' ?></span><span><?= number_format($booking['entrance_fee'], 2) ?></span></div>
                <?php endif; ?>
                <?php if ($booking['discount'] > 0): ?>
                <div class="row"><span><?= $isThai ? 'ส่วนลด' : 'Discount' ?></span><span style="color:#ef4444;">-<?= number_format($booking['discount'], 2) ?></span></div>
                <?php endif; ?>
                <?php if ($booking['vat'] > 0): ?>
                <div class="row"><span><?= $isThai ? 'VAT' : 'VAT' ?></span><span><?= number_format($booking['vat'], 2) ?></span></div>
                <?php endif; ?>
                <div class="row grand"><span><?= $isThai ? 'ยอดรวมทั้งหมด' : 'Grand Total' ?></span><span>฿<?= number_format($booking['total_amount'], 2) ?></span></div>
            </div>
        </div>

        <!-- Payment Summary -->
        <?php
        $payStatus   = $booking['payment_status'] ?? 'unpaid';
        $amountPaid  = floatval($paymentSummary['net_paid'] ?? $booking['amount_paid'] ?? 0);
        $totalAmount = floatval($booking['total_amount'] ?? 0);
        $amountDue   = max(0.0, $totalAmount - $amountPaid);
        $payStatusCfg = [
            'unpaid'  => ['label' => $isThai ? 'ยังไม่ชำระ' : 'Unpaid',   'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
            'deposit' => ['label' => $isThai ? 'ชำระมัดจำ' : 'Deposit',   'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-clock-o'],
            'partial' => ['label' => $isThai ? 'ชำระบางส่วน' : 'Partial', 'color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => 'fa-adjust'],
            'paid'    => ['label' => $isThai ? 'ชำระแล้ว' : 'Paid',       'color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-check-circle'],
            'refunded'=> ['label' => $isThai ? 'คืนเงินแล้ว' : 'Refunded','color' => '#6366f1', 'bg' => '#e0e7ff', 'icon' => 'fa-undo'],
        ];
        $psCfg = $payStatusCfg[$payStatus] ?? $payStatusCfg['unpaid'];
        ?>
        <div class="vw-card">
            <h3><i class="fa fa-money"></i> <?= $isThai ? 'การชำระเงิน' : 'Payment' ?></h3>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:16px;">
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'สถานะการชำระ' : 'Payment Status' ?></div>
                    <div><span class="status-badge" style="background:<?= $psCfg['bg'] ?>;color:<?= $psCfg['color'] ?>;"><i class="fa <?= $psCfg['icon'] ?>"></i> <?= $psCfg['label'] ?></span></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ชำระแล้ว' : 'Amount Paid' ?></div>
                    <div class="val" style="color:#059669;">฿<?= number_format($amountPaid, 2) ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'คงเหลือ' : 'Balance Due' ?></div>
                    <div class="val" style="color:<?= $amountDue > 0 ? '#ef4444' : '#059669' ?>;">฿<?= number_format($amountDue, 2) ?></div>
                </div>
            </div>
            <?php if (($booking['status'] ?? 'draft') === 'draft'): ?>
            <div style="display:flex; align-items:center; gap:10px; padding:10px 14px; background:#fef9c3; border:1.5px solid #fde047; border-radius:10px; font-size:13px; color:#854d0e;">
                <i class="fa fa-lock" style="font-size:15px; color:#ca8a04; flex-shrink:0;"></i>
                <span>
                    <?= $isThai ? 'การจัดการชำระเงินต้องการสถานะ <strong>ยืนยัน</strong> — ' : 'Payment management requires <strong>Confirmed</strong> status — ' ?>
                    <a href="index.php?page=tour_booking_make&id=<?= $booking['id'] ?>" style="color:#ca8a04; font-weight:700; text-decoration:underline;">
                        <?= $isThai ? 'อัปเดตสถานะ' : 'Update status' ?>
                    </a>
                </span>
            </div>
            <?php else: ?>
            <a href="index.php?page=tour_booking_payments&booking_id=<?= $booking['id'] ?>" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#0d9488; color:white; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none;">
                <i class="fa fa-credit-card"></i> <?= $isThai ? 'จัดการชำระเงิน' : 'Manage Payments' ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Remark -->
        <?php if (!empty($booking['remark'])): ?>
        <div class="vw-card">
            <h3><i class="fa fa-sticky-note-o"></i> <?= $isThai ? 'หมายเหตุ' : 'Remark' ?></h3>
            <p style="font-size:14px; color:#475569; line-height:1.6;"><?= nl2br(htmlspecialchars($booking['remark'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Linked Documents -->
        <div class="vw-card">
            <h3><i class="fa fa-link"></i> <?= $isThai ? 'เอกสารที่เชื่อมโยง' : 'Linked Documents' ?></h3>
            <div class="doc-links">
                <?php
                $hasDoc = false;
                if (!empty($booking['pr_id'])):
                    $hasDoc = true;
                ?>
                <a href="index.php?page=pr_view&id=<?= $booking['pr_id'] ?>" class="doc-link" style="color:#6366f1;">
                    <i class="fa fa-file-text-o"></i> PR #<?= $booking['pr_id'] ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($booking['po_id'])):
                    $hasDoc = true;
                ?>
                <a href="index.php?page=po_view&id=<?= $booking['po_id'] ?>" class="doc-link" style="color:#0d9488;">
                    <i class="fa fa-file-o"></i> PO #<?= $booking['po_id'] ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($booking['invoice_id'])):
                    $hasDoc = true;
                ?>
                <a href="index.php?page=compl_view&id=<?= $booking['invoice_id'] ?>" class="doc-link" style="color:#f59e0b;">
                    <i class="fa fa-file-text"></i> Invoice #<?= $booking['invoice_id'] ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($booking['receipt_id'])):
                    $hasDoc = true;
                ?>
                <a href="index.php?page=receipt_view&id=<?= $booking['receipt_id'] ?>" class="doc-link" style="color:#10b981;">
                    <i class="fa fa-check-square-o"></i> Receipt #<?= $booking['receipt_id'] ?>
                </a>
                <?php endif; ?>
                <?php if (!$hasDoc): ?>
                <form method="post" action="index.php?page=tour_booking_generate" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'สร้างเอกสาร PR, PO, ใบส่งของ, ใบแจ้งหนี้ จากการจองนี้?' : 'Generate PR, PO, Delivery, and Invoice from this booking?' ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                    <button type="submit" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:#0d9488; color:white; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">
                        <i class="fa fa-magic"></i> <?= $isThai ? 'สร้างเอกสาร' : 'Generate Documents' ?>
                    </button>
                </form>
                <p class="doc-empty" style="margin-top:8px;"><?= $isThai ? 'จะสร้าง PR → PO → ใบส่งของ → ใบแจ้งหนี้ อัตโนมัติ' : 'Will auto-create PR → PO → Delivery → Invoice' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Self-Check-In QR Card ────────────────────────────── -->
        <?php
        // Lazy-generate token if missing
        use App\Models\TourBooking as TourBookingModel;
        $checkinToken = $booking['checkin_token'] ?? '';
        if (empty($checkinToken) && in_array($booking['status'], ['confirmed','completed'])) {
            $tbm = new TourBookingModel();
            $checkinToken = $tbm->ensureCheckinToken(intval($booking['id']));
        }
        $checkinUrl = $checkinToken
            ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
              . '://' . $_SERVER['HTTP_HOST']
              . '/index.php?page=tour_checkin&id=' . intval($booking['id']) . '&token=' . $checkinToken
            : '';
        $checkinStatus = intval($booking['checkin_status'] ?? 0);
        $checkinAt     = $booking['checkin_at'] ?? null;
        ?>
        <div class="section-card" style="margin-top:16px;">
            <div class="section-title">
                <i class="fa fa-qrcode" style="color:#0d9488;"></i>
                <?= $isThai ? 'เช็คอินด้วยตนเอง' : 'Customer Self-Check-In' ?>
                <?php if ($checkinStatus): ?>
                    <span style="margin-left:10px;background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                        <i class="fa fa-check-circle"></i> <?= $isThai ? 'เช็คอินแล้ว' : 'Checked In' ?>
                    </span>
                <?php else: ?>
                    <span style="margin-left:10px;background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                        <i class="fa fa-clock-o"></i> <?= $isThai ? 'ยังไม่เช็คอิน' : 'Not Checked In' ?>
                    </span>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                <?php if ($checkinUrl): ?>
                <!-- QR Code -->
                <div style="flex-shrink:0;text-align:center;">
                    <div id="qr-container" style="width:160px;height:160px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;">
                        <span style="color:#94a3b8;font-size:12px;">Loading QR...</span>
                    </div>
                    <p style="font-size:11px;color:#94a3b8;margin-top:6px;"><?= $isThai ? 'สแกนเพื่อเช็คอิน' : 'Scan to Check In' ?></p>
                </div>
                <?php endif; ?>

                <!-- Info -->
                <div style="flex:1;min-width:200px;">
                    <?php if ($checkinStatus && $checkinAt): ?>
                    <div style="background:#dcfce7;border-radius:10px;padding:12px 16px;margin-bottom:12px;font-size:13px;color:#166534;">
                        <i class="fa fa-check-circle"></i>
                        <strong><?= $isThai ? 'เช็คอินเมื่อ:' : 'Checked in at:' ?></strong>
                        <?= date('d M Y H:i', strtotime($checkinAt)) ?>
                        (<?= $booking['checkin_by'] === 'staff' ? ($isThai ? 'โดยเจ้าหน้าที่' : 'by staff') : ($isThai ? 'ด้วยตนเอง' : 'self') ?>)
                    </div>
                    <?php endif; ?>

                    <?php if ($checkinUrl): ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                        <button onclick="copyCheckinLink()" class="btn-copy-link" style="padding:8px 16px;background:#0d9488;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                            <i class="fa fa-copy"></i> <?= $isThai ? 'คัดลอกลิงก์' : 'Copy Link' ?>
                        </button>
                        <a href="<?= htmlspecialchars($checkinUrl) ?>" target="_blank"
                           style="padding:8px 16px;background:#f0fdfa;color:#0d9488;border:1px solid #ccfbf1;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
                            <i class="fa fa-external-link"></i> <?= $isThai ? 'เปิดลิงก์' : 'Open Link' ?>
                        </a>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;word-break:break-all;margin-bottom:12px;" id="checkin-url-display">
                        <?= htmlspecialchars($checkinUrl) ?>
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ($checkinStatus): ?>
                        <button onclick="staffCheckinAction('reset', <?= intval($booking['id']) ?>)"
                                style="padding:7px 14px;background:#fff;color:#ef4444;border:1px solid #fecaca;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                            <i class="fa fa-undo"></i> <?= $isThai ? 'รีเซ็ตเช็คอิน' : 'Reset Check-In' ?>
                        </button>
                        <?php else: ?>
                        <button onclick="staffCheckinAction('override', <?= intval($booking['id']) ?>)"
                                style="padding:7px 14px;background:#0d9488;color:white;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                            <i class="fa fa-check"></i> <?= $isThai ? 'เช็คอินแทน' : 'Mark Checked In' ?>
                        </button>
                        <?php endif; ?>
                        <button onclick="regenCheckinToken(<?= intval($booking['id']) ?>)"
                                style="padding:7px 14px;background:#fff;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;"
                                title="<?= $isThai ? 'สร้าง QR ใหม่ (จะยกเลิก QR เก่า)' : 'Generate new QR (old QR will stop working)' ?>">
                            <i class="fa fa-refresh"></i> <?= $isThai ? 'สร้าง QR ใหม่' : 'Regen QR' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <a href="index.php?page=tour_booking_list" class="btn-back"><i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?></a>
            <a href="index.php?page=tour_booking_make&id=<?= $booking['id'] ?>" class="btn-edit"><i class="fa fa-pencil"></i> <?= $isThai ? 'แก้ไข' : 'Edit' ?></a>
        </div>
    </div>
</div>

<?php if ($checkinUrl): ?>
<script src="js/qrcode.min.js"></script>
<script>
(function() {
    var url = <?= json_encode($checkinUrl) ?>;
    new QRCode(document.getElementById('qr-container'), {
        text: url, width: 150, height: 150,
        colorDark: '#0d9488', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
})();

function copyCheckinLink() {
    var url = <?= json_encode($checkinUrl) ?>;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            alert('<?= $isThai ? 'คัดลอกลิงก์แล้ว!' : 'Link copied!' ?>');
        });
    } else {
        var t = document.createElement('textarea');
        t.value = url; document.body.appendChild(t); t.select();
        document.execCommand('copy'); document.body.removeChild(t);
        alert('<?= $isThai ? 'คัดลอกลิงก์แล้ว!' : 'Link copied!' ?>');
    }
}

function staffCheckinAction(action, bookingId) {
    var msg = action === 'reset'
        ? '<?= $isThai ? 'รีเซ็ตสถานะเช็คอิน?' : 'Reset check-in status?' ?>'
        : '<?= $isThai ? 'เช็คอินแทนลูกค้า?' : 'Mark this booking as checked in?' ?>';
    if (!confirm(msg)) return;
    var route = action === 'reset' ? 'tour_checkin_reset' : 'tour_checkin_override';
    fetch('index.php?page=' + route, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'booking_id=' + bookingId + '&csrf_token=<?= csrf_token() ?>'
    }).then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
}

function regenCheckinToken(bookingId) {
    if (!confirm('<?= $isThai ? 'สร้าง QR ใหม่? QR เก่าจะใช้ไม่ได้อีก' : 'Generate new QR? The old QR/link will stop working.' ?>')) return;
    fetch('index.php?page=tour_checkin_regen', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'booking_id=' + bookingId + '&csrf_token=<?= csrf_token() ?>'
    }).then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
}
</script>
<?php endif; ?>
