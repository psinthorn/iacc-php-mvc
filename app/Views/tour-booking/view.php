<?php
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

$custName = ($isThai && !empty($booking['customer_name_th'])) ? $booking['customer_name_th'] : ($booking['customer_name'] ?: '-');
$agentName = ($isThai && !empty($booking['agent_name_th'])) ? $booking['agent_name_th'] : ($booking['agent_name'] ?: '-');

$messages = [
    'created' => ['✅', $isThai ? 'สร้างการจองสำเร็จ' : 'Booking created successfully'],
    'updated' => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Booking updated successfully'],
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
                    <span class="status-badge" style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>;">
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
                    <div class="lbl"><?= $isThai ? 'วันเดินทาง' : 'Travel Date' ?></div>
                    <div class="val"><i class="fa fa-calendar-o" style="color:#0d9488;"></i> <?= date('d M Y', strtotime($booking['travel_date'])) ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ลูกค้า' : 'Customer' ?></div>
                    <div class="val"><?= htmlspecialchars($custName) ?></div>
                </div>
                <div class="vw-item">
                    <div class="lbl"><?= $isThai ? 'ตัวแทน' : 'Agent' ?></div>
                    <div class="val"><?= htmlspecialchars($agentName) ?></div>
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
                        <th class="right"><?= $isThai ? 'จำนวน' : 'Qty' ?></th>
                        <th class="right"><?= $isThai ? 'ราคา/หน่วย' : 'Unit Price' ?></th>
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
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td class="right"><?= intval($item['quantity']) ?></td>
                        <td class="right"><?= number_format($item['unit_price'], 2) ?></td>
                        <td class="right" style="font-weight:600;"><?= number_format($item['amount'], 2) ?></td>
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

        <!-- Passengers -->
        <?php if (!empty($booking['pax'])): ?>
        <div class="vw-card">
            <h3><i class="fa fa-users"></i> <?= $isThai ? 'รายชื่อผู้เดินทาง' : 'Passenger List' ?> (<?= count($booking['pax']) ?>)</h3>
            <table class="vw-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                        <th><?= $isThai ? 'ชื่อ-นามสกุล' : 'Full Name' ?></th>
                        <th><?= $isThai ? 'สัญชาติ' : 'Nationality' ?></th>
                        <th><?= $isThai ? 'พาสปอร์ต' : 'Passport #' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booking['pax'] as $idx => $p):
                        $paxColors = ['adult' => '#0d9488', 'child' => '#f59e0b', 'infant' => '#ec4899'];
                        $pc = $paxColors[$p['pax_type']] ?? '#64748b';
                    ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <span class="pax-badge" style="background: <?= $pc ?>18; color: <?= $pc ?>;">
                                <?= $paxTypeLabels[$p['pax_type']] ?? $p['pax_type'] ?>
                            </span>
                        </td>
                        <td style="font-weight:500;"><?= htmlspecialchars($p['full_name']) ?></td>
                        <td><?= htmlspecialchars($p['nationality'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($p['passport_number'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

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
                <a href="index.php?page=pr&id=<?= $booking['pr_id'] ?>" class="doc-link" style="color:#6366f1;">
                    <i class="fa fa-file-text-o"></i> PR #<?= $booking['pr_id'] ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($booking['po_id'])):
                    $hasDoc = true;
                ?>
                <a href="index.php?page=po&id=<?= $booking['po_id'] ?>" class="doc-link" style="color:#0d9488;">
                    <i class="fa fa-file-o"></i> PO #<?= $booking['po_id'] ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($booking['invoice_id'])):
                    $hasDoc = true;
                ?>
                <a href="index.php?page=iv&id=<?= $booking['invoice_id'] ?>" class="doc-link" style="color:#f59e0b;">
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
                <p class="doc-empty"><?= $isThai ? 'ยังไม่มีเอกสารที่เชื่อมโยง (สร้างได้จากเมนู Generate Documents)' : 'No linked documents yet (Generate via Phase 4)' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <a href="index.php?page=tour_booking_list" class="btn-back"><i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?></a>
            <a href="index.php?page=tour_booking_make&id=<?= $booking['id'] ?>" class="btn-edit"><i class="fa fa-pencil"></i> <?= $isThai ? 'แก้ไข' : 'Edit' ?></a>
        </div>
    </div>
</div>
