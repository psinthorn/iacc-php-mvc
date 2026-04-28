<?php
$pageTitle = 'Agent Portal — Bookings';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$statusBadges = [
    'pending'   => [$isThai ? 'รอ' : 'Pending',     '#d97706', '#fffbeb'],
    'confirmed' => [$isThai ? 'ยืนยัน' : 'Confirmed', '#059669', '#ecfdf5'],
    'cancelled' => [$isThai ? 'ยกเลิก' : 'Cancelled', '#dc2626', '#fef2f2'],
    'completed' => [$isThai ? 'สำเร็จ' : 'Completed', '#2563eb', '#eff6ff'],
];
?>

<style>
.booking-table { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.booking-table table { width: 100%; border-collapse: collapse; }
.booking-table th { background: #f8fafc; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.booking-table th.text-right { text-align: right; }
.booking-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
.booking-table td.text-right { text-align: right; }
.booking-table tr:last-child td { border-bottom: none; }
.booking-table tr:hover td { background: #f8fafc; }
.booking-no { font-family: monospace; color: #0d9488; font-weight: 600; }
.booking-amount { font-weight: 600; color: #1e293b; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-calendar-check-o"></i> <?= $isThai ? 'การจองของคุณ' : 'My Bookings' ?></h2>
                <p><?= $isThai ? 'การจองที่คุณได้สร้างไว้' : 'Bookings you have created' ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_shared.php'; ?>

    <?php if (empty($bookings)): ?>
    <div class="empty-state">
        <i class="fa fa-calendar-o"></i>
        <p><?= $isThai ? 'ยังไม่มีการจอง' : 'No bookings yet.' ?></p>
    </div>
    <?php else: ?>
    <div class="booking-table">
        <table>
            <thead>
                <tr>
                    <th><?= $isThai ? 'เลขที่' : 'Number' ?></th>
                    <th><?= $isThai ? 'ลูกค้า' : 'Customer' ?></th>
                    <th><?= $isThai ? 'ผู้ประกอบการ' : 'Operator' ?></th>
                    <th><?= $isThai ? 'วันเดินทาง' : 'Travel Date' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th class="text-right"><?= $isThai ? 'จำนวนเงิน' : 'Amount' ?></th>
                    <th><?= $isThai ? 'สร้างเมื่อ' : 'Created' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b):
                    $st = $statusBadges[$b['status'] ?? 'pending'] ?? $statusBadges['pending'];
                ?>
                <tr>
                    <td><span class="booking-no"><?= htmlspecialchars($b['booking_number'] ?? '#' . $b['id']) ?></span></td>
                    <td><?= htmlspecialchars($b['customer_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($b['operator_name'] ?: '—') ?></td>
                    <td>
                        <?php if (!empty($b['travel_date'])): ?>
                            <?= date('d M Y', strtotime($b['travel_date'])) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge" style="background:<?= $st[2] ?>;color:<?= $st[1] ?>;"><?= $st[0] ?></span></td>
                    <td class="text-right"><span class="booking-amount">฿<?= number_format(floatval($b['total_amount'] ?? 0), 2) ?></span></td>
                    <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
