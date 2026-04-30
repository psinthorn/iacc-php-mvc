<?php
$pageTitle = 'Allotment Detail';
$isThai = ($_SESSION['lang'] ?? '0') === '1';
$actionLabels = \App\Models\TourAllotment::getActionLabels($isThai);

$messages = [
    'capacity_set'  => ['#10b981', $isThai ? 'ตั้งค่าจำนวนที่นั่งสำเร็จ' : 'Capacity updated'],
    'date_closed'   => ['#f59e0b', $isThai ? 'ปิดวันสำเร็จ' : 'Date closed'],
    'date_reopened' => ['#10b981', $isThai ? 'เปิดวันสำเร็จ' : 'Date reopened'],
    'recalculated'  => ['#3b82f6', $isThai ? 'คำนวณที่นั่งใหม่สำเร็จ' : 'Seats recalculated'],
];

$dateFormatted = date('D, j M Y', strtotime($date));
$total   = $summary['total_seats'] ?? 0;
$booked  = $summary['booked_seats'] ?? 0;
$avail   = $summary['available'] ?? 0;
$closed  = $summary['is_closed'] ?? false;
$overbooked = $summary['is_overbooked'] ?? false;
$pct = $total > 0 ? round(($booked / $total) * 100) : 0;
$barColor = $pct > 90 ? '#ef4444' : ($pct > 70 ? '#f59e0b' : '#10b981');
if ($overbooked) $barColor = '#ef4444';

$csrf = $_SESSION['csrf_token'] ?? '';
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.ad-back { font-size: 13px; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 16px; }
.ad-back:hover { color: #8e44ad; }
.ad-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
.ad-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; text-align: center; }
.ad-card .num { font-size: 32px; font-weight: 700; }
.ad-card .label { font-size: 12px; color: #64748b; margin-top: 4px; }
.ad-fill-lg { height: 10px; border-radius: 5px; background: #e2e8f0; overflow: hidden; margin-bottom: 20px; }
.ad-fill-lg-bar { height: 100%; border-radius: 5px; }

.ad-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.ad-btn { padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; border: 1px solid #e2e8f0; background: white; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #475569; }
.ad-btn:hover { background: #f1f5f9; }
.ad-btn.danger { color: #dc2626; border-color: #fecaca; }
.ad-btn.danger:hover { background: #fef2f2; }
.ad-btn.primary { background: #8e44ad; color: white; border-color: #8e44ad; }
.ad-btn.primary:hover { background: #6c3483; }

.ad-table { width: 100%; background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
.ad-table table { width: 100%; border-collapse: collapse; }
.ad-table th { background: #f8fafc; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.ad-table td { padding: 10px 14px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
.ad-table tr:last-child td { border-bottom: none; }
.ad-table tr:hover { background: #faf5ff; }

.ad-warning { padding: 14px 20px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; font-weight: 600; }
.ad-warning.red { background: #fef2f2; border-left: 4px solid #ef4444; color: #dc2626; }
.ad-warning.amber { background: #fffbeb; border-left: 4px solid #f59e0b; color: #b45309; }

.modal-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:1000; align-items:center; justify-content:center; }
.modal-overlay.show { display:flex; }
.modal-box { background:white; border-radius:16px; padding:28px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.15); }
.modal-box h3 { margin:0 0 16px; font-size:16px; }
.modal-box input { width:100%; height:40px; padding:0 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:14px; box-sizing:border-box; }
.modal-box textarea { width:100%; height:60px; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:14px; box-sizing:border-box; resize:vertical; }
.modal-actions { display:flex; gap:10px; justify-content:flex-end; }

.log-action { font-size:11px; font-weight:600; padding:2px 8px; border-radius:8px; }
.log-action.book { background:#dcfce7; color:#16a34a; }
.log-action.release { background:#fef3c7; color:#b45309; }
.log-action.manual_set { background:#dbeafe; color:#2563eb; }
.log-action.close { background:#fee2e2; color:#dc2626; }
.log-action.reopen { background:#d1fae5; color:#059669; }
.log-action.recalculate { background:#e0e7ff; color:#4338ca; }

@media (max-width: 768px) { .ad-summary { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="master-data-container">
    <a href="index.php?page=tour_allotment_list&month=<?= date('n', strtotime($date)) ?>&year=<?= date('Y', strtotime($date)) ?>" class="ad-back">
        <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับไปปฏิทิน' : 'Back to Calendar' ?>
    </a>

    <div class="master-data-header" data-theme="purple">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-calendar-check-o"></i> <?= $dateFormatted ?></h2>
                <p><?= $isThai ? 'รายละเอียดที่นั่งรายวัน' : 'Daily seat allotment detail' ?></p>
            </div>
        </div>
    </div>

    <!-- Flash -->
    <?php if (!empty($msg) && isset($messages[$msg])): ?>
    <div style="background:#f0fdf4; border-left:4px solid <?= $messages[$msg][0] ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$msg][1] ?>
    </div>
    <?php endif; ?>

    <!-- Warnings -->
    <?php if ($closed): ?>
    <div class="ad-warning red"><i class="fa fa-lock"></i> <?= $isThai ? 'วันนี้ถูกปิดรับจอง' : 'This date is closed for bookings' ?></div>
    <?php endif; ?>
    <?php if ($overbooked): ?>
    <div class="ad-warning red"><i class="fa fa-exclamation-triangle"></i> <?= $isThai ? 'จองเกินจำนวนที่นั่ง!' : 'Overbooked! Seats exceeded capacity.' ?></div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <?php if ($summary): ?>
    <div class="ad-summary">
        <div class="ad-card">
            <div class="num" style="color:#1e293b"><?= $total ?></div>
            <div class="label"><?= $isThai ? 'ที่นั่งทั้งหมด' : 'Total Seats' ?></div>
        </div>
        <div class="ad-card">
            <div class="num" style="color:<?= $barColor ?>"><?= $booked ?></div>
            <div class="label"><?= $isThai ? 'จองแล้ว' : 'Booked' ?></div>
        </div>
        <div class="ad-card">
            <div class="num" style="color:<?= $avail >= 0 ? '#10b981' : '#ef4444' ?>"><?= $avail ?></div>
            <div class="label"><?= $isThai ? 'ว่าง' : 'Available' ?></div>
        </div>
        <div class="ad-card">
            <div class="num" style="color:<?= $barColor ?>"><?= $pct ?>%</div>
            <div class="label"><?= $isThai ? 'อัตราการจอง' : 'Fill Rate' ?></div>
        </div>
    </div>
    <div class="ad-fill-lg"><div class="ad-fill-lg-bar" style="width:<?= min(100, $pct) ?>%;background:<?= $barColor ?>"></div></div>
    <?php else: ?>
    <p style="color:#94a3b8;font-size:14px;margin-bottom:20px;"><?= $isThai ? 'ยังไม่มีข้อมูลที่นั่งสำหรับวันนี้' : 'No allotment data for this date yet' ?></p>
    <?php endif; ?>

    <!-- Admin Actions -->
    <div class="ad-actions">
        <button class="ad-btn primary" onclick="document.getElementById('capacityModal').classList.add('show')">
            <i class="fa fa-sliders"></i> <?= $isThai ? 'ตั้งค่าที่นั่ง' : 'Set Capacity' ?>
        </button>
        <?php if ($closed): ?>
        <form method="POST" action="index.php?page=tour_allotment_reopen" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="travel_date" value="<?= $date ?>">
            <button type="submit" class="ad-btn" style="color:#059669;border-color:#bbf7d0;"><i class="fa fa-unlock"></i> <?= $isThai ? 'เปิดรับจอง' : 'Reopen' ?></button>
        </form>
        <?php else: ?>
        <button class="ad-btn danger" onclick="document.getElementById('closeModal').classList.add('show')">
            <i class="fa fa-lock"></i> <?= $isThai ? 'ปิดรับจอง' : 'Close Date' ?>
        </button>
        <?php endif; ?>
        <form method="POST" action="index.php?page=tour_allotment_recalc" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="travel_date" value="<?= $date ?>">
            <button type="submit" class="ad-btn"><i class="fa fa-refresh"></i> <?= $isThai ? 'คำนวณใหม่' : 'Recalculate' ?></button>
        </form>
    </div>

    <!-- Confirmed Bookings -->
    <div class="ad-table">
        <table>
            <thead>
                <tr>
                    <th><?= $isThai ? 'เลขจอง' : 'Booking #' ?></th>
                    <th><?= $isThai ? 'ลูกค้า' : 'Customer' ?></th>
                    <th><?= $isThai ? 'ผู้ใหญ่' : 'Adult' ?></th>
                    <th><?= $isThai ? 'เด็ก' : 'Child' ?></th>
                    <th><?= $isThai ? 'ที่นั่ง' : 'Seats' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px;"><?= $isThai ? 'ไม่มีการจองที่ยืนยันแล้ว' : 'No confirmed bookings for this date' ?></td></tr>
                <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><a href="index.php?page=tour_booking_view&id=<?= intval($b['id']) ?>" style="color:#8e44ad;font-weight:600;text-decoration:none;"><?= htmlspecialchars($b['booking_number']) ?></a></td>
                    <td><?= htmlspecialchars($b['customer_name']) ?></td>
                    <td><?= intval($b['pax_adult']) ?></td>
                    <td><?= intval($b['pax_child']) ?></td>
                    <td><strong><?= intval($b['seat_pax']) ?></strong></td>
                    <td><?php
                        $statusColors = ['confirmed' => '#059669', 'paid' => '#0d9488', 'completed' => '#2563eb', 'no_show' => '#d97706', 'cancelled' => '#dc2626', 'draft' => '#64748b'];
                        $color = $statusColors[$b['status']] ?? '#64748b';
                    ?><span style="font-size:11px;font-weight:600;color:<?= $color ?>;"><?= ucfirst(str_replace('_', ' ', $b['status'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f8fafc;">
                    <td colspan="4" style="text-align:right;font-weight:700;"><?= $isThai ? 'รวม' : 'Total' ?></td>
                    <td><strong><?= array_sum(array_column($bookings, 'seat_pax')) ?></strong></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Audit Log -->
    <?php if (!empty($logs)): ?>
    <h3 style="font-size:15px;margin-bottom:12px;"><i class="fa fa-history"></i> <?= $isThai ? 'ประวัติการเปลี่ยนแปลง' : 'Audit Log' ?></h3>
    <div class="ad-table">
        <table>
            <thead>
                <tr>
                    <th><?= $isThai ? 'เวลา' : 'Time' ?></th>
                    <th><?= $isThai ? 'การกระทำ' : 'Action' ?></th>
                    <th><?= $isThai ? 'เลขจอง' : 'Booking' ?></th>
                    <th><?= $isThai ? 'ที่นั่ง +/-' : 'Seats +/-' ?></th>
                    <th><?= $isThai ? 'หลังเปลี่ยน' : 'After' ?></th>
                    <th><?= $isThai ? 'โดย' : 'By' ?></th>
                    <th><?= $isThai ? 'หมายเหตุ' : 'Note' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($logs, 0, 50) as $l): ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap;"><?= date('H:i', strtotime($l['created_at'])) ?></td>
                    <td><span class="log-action <?= $l['action'] ?>"><?= $actionLabels[$l['action']] ?? $l['action'] ?></span></td>
                    <td><?= $l['booking_number'] ? htmlspecialchars($l['booking_number']) : '-' ?></td>
                    <td style="font-weight:600;color:<?= intval($l['seats_delta']) > 0 ? '#dc2626' : (intval($l['seats_delta']) < 0 ? '#059669' : '#64748b') ?>">
                        <?= intval($l['seats_delta']) > 0 ? '+' : '' ?><?= intval($l['seats_delta']) ?>
                    </td>
                    <td><?= intval($l['booked_seats_after']) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($l['user_name'] ?? ($isThai ? 'ระบบ' : 'System')) ?></td>
                    <td style="font-size:12px;color:#94a3b8;"><?= htmlspecialchars($l['note'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Set Capacity Modal -->
<div class="modal-overlay" id="capacityModal" onclick="if(event.target===this)this.classList.remove('show')">
    <div class="modal-box">
        <h3><i class="fa fa-sliders"></i> <?= $isThai ? 'ตั้งค่าจำนวนที่นั่ง' : 'Set Seat Capacity' ?></h3>
        <form method="POST" action="index.php?page=tour_allotment_manual_set">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="travel_date" value="<?= $date ?>">
            <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;"><?= $isThai ? 'จำนวนที่นั่งทั้งหมด' : 'Total seats' ?></label>
            <input type="number" name="total_seats" value="<?= $total ?>" min="0" max="9999" required>
            <div class="modal-actions">
                <button type="button" class="ad-btn" onclick="this.closest('.modal-overlay').classList.remove('show')"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
                <button type="submit" class="ad-btn primary"><?= $isThai ? 'บันทึก' : 'Save' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Close Date Modal -->
<div class="modal-overlay" id="closeModal" onclick="if(event.target===this)this.classList.remove('show')">
    <div class="modal-box">
        <h3><i class="fa fa-lock"></i> <?= $isThai ? 'ปิดรับจอง' : 'Close Date' ?></h3>
        <form method="POST" action="index.php?page=tour_allotment_close">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="travel_date" value="<?= $date ?>">
            <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;"><?= $isThai ? 'เหตุผล (ไม่บังคับ)' : 'Reason (optional)' ?></label>
            <textarea name="reason" placeholder="<?= $isThai ? 'เช่น สภาพอากาศไม่ดี' : 'e.g. Weather conditions' ?>"></textarea>
            <div class="modal-actions">
                <button type="button" class="ad-btn" onclick="this.closest('.modal-overlay').classList.remove('show')"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
                <button type="submit" class="ad-btn danger"><?= $isThai ? 'ปิดวัน' : 'Close Date' ?></button>
            </div>
        </form>
    </div>
</div>
