<?php
$pageTitle = 'Seat Allotments';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$daysInMonth = intval(date('t', strtotime($from)));
$firstDow    = intval(date('w', strtotime($from))); // 0=Sun
$monthLabel  = date('F Y', strtotime($from));

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$statusColors = [
    'draft'     => '#94a3b8',
    'confirmed' => '#10b981',
    'completed' => '#3b82f6',
    'cancelled' => '#ef4444',
];

$modelColors = ['#8e44ad', '#2563eb', '#0d9488', '#d97706', '#dc2626', '#6366f1'];
$bkByDate   = $bookingsByDate['bookings'] ?? [];
$mdByDate   = $bookingsByDate['models'] ?? [];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; background: white; border-radius: 12px; padding: 14px 20px; border: 1px solid #e2e8f0; }
.cal-nav h3 { margin: 0; font-size: 18px; font-weight: 700; }
.cal-nav a { text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; padding: 6px 14px; border-radius: 8px; border: 1px solid #e2e8f0; }
.cal-nav a:hover { background: #f1f5f9; }

.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-header { text-align: center; font-size: 11px; font-weight: 700; color: #94a3b8; padding: 8px 0; text-transform: uppercase; }
.cal-cell { background: white; border-radius: 10px; border: 1px solid #e2e8f0; min-height: 120px; padding: 8px; transition: all 0.2s; cursor: pointer; }
.cal-cell:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
.cal-cell.empty { background: #fafafa; border-color: transparent; min-height: 40px; cursor: default; }
.cal-cell.today { border-color: #8e44ad; border-width: 2px; }
.cal-cell.closed { background: #fef2f2; border-color: #fecaca; }

.cal-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.cal-day { font-size: 13px; font-weight: 700; color: #1e293b; }
.cal-seats-badge { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 8px; }
.cal-seats-badge.green { background: #dcfce7; color: #16a34a; }
.cal-seats-badge.yellow { background: #fef3c7; color: #b45309; }
.cal-seats-badge.red { background: #fee2e2; color: #dc2626; }

.cal-fill { height: 4px; border-radius: 2px; background: #e2e8f0; overflow: hidden; margin-bottom: 4px; }
.cal-fill-bar { height: 100%; border-radius: 2px; }
.cal-fill-bar.green { background: #10b981; }
.cal-fill-bar.yellow { background: #f59e0b; }
.cal-fill-bar.red { background: #ef4444; }

.cal-lock { font-size: 10px; color: #dc2626; font-weight: 600; margin-bottom: 4px; }

.cal-models { display: flex; flex-direction: column; gap: 2px; margin-bottom: 3px; }
.cal-model { display: flex; align-items: center; gap: 4px; font-size: 10px; line-height: 1.3; padding: 2px 4px; border-radius: 4px; background: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-model .m-dot { width: 5px; height: 5px; border-radius: 2px; flex-shrink: 0; }
.cal-model .m-name { overflow: hidden; text-overflow: ellipsis; flex: 1; color: #334155; }
.cal-model .m-pax { font-weight: 700; flex-shrink: 0; font-size: 9px; padding: 0 4px; border-radius: 4px; }

.cal-latest { display: flex; align-items: center; gap: 4px; font-size: 10px; padding: 2px 4px; border-radius: 4px; text-decoration: none; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-top: 1px solid #f1f5f9; margin-top: 2px; padding-top: 4px; }
.cal-latest:hover { background: #f1f5f9; }
.cal-latest .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.cal-latest .bk-name { overflow: hidden; text-overflow: ellipsis; flex: 1; font-weight: 500; }
.cal-more { font-size: 10px; color: #8e44ad; font-weight: 600; padding: 2px 4px; }
.cal-no-bk { font-size: 10px; color: #cbd5e1; padding: 2px 0; }

.legend { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 16px; padding: 12px 20px; background: white; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 12px; color: #64748b; align-items: center; }
.legend-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; margin-right: 6px; vertical-align: middle; }
.legend-circle { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; vertical-align: middle; }

/* Scrollbar for booking lists */
.cal-bookings::-webkit-scrollbar { width: 3px; }
.cal-bookings::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

@media (max-width: 768px) {
    .cal-cell { min-height: 80px; padding: 4px; }
    .cal-bk { font-size: 9px; }
    .cal-seats-badge { font-size: 9px; }
}
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="purple">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-calendar"></i> <?= $isThai ? 'จัดการที่นั่ง' : 'Seat Allotments' ?></h2>
                <p><?= $isThai ? 'ดูและจัดการจำนวนที่นั่งรายวัน' : 'View and manage daily seat capacity' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_fleet_list" class="btn-header btn-header-outline">
                    <i class="fa fa-ship"></i> <?= $isThai ? 'จัดการฟลีท' : 'Fleets' ?>
                </a>
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'การจอง' : 'Bookings' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($fleets)): ?>
    <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
        <i class="fa fa-ship" style="font-size:48px;display:block;margin-bottom:16px;"></i>
        <p><?= $isThai ? 'กรุณาสร้างฟลีทก่อน' : 'Please create a fleet first' ?></p>
        <a href="index.php?page=tour_fleet_list" style="color:#8e44ad;font-weight:600;"><?= $isThai ? 'ไปจัดการฟลีท' : 'Go to Fleet Management' ?> &rarr;</a>
    </div>
    <?php else: ?>

    <!-- Month Navigation -->
    <div class="cal-nav">
        <a href="index.php?page=tour_allotment_list&month=<?= $prevMonth ?>&year=<?= $prevYear ?>"><i class="fa fa-chevron-left"></i> <?= $isThai ? 'ก่อนหน้า' : 'Prev' ?></a>
        <h3><?= $monthLabel ?></h3>
        <a href="index.php?page=tour_allotment_list&month=<?= $nextMonth ?>&year=<?= $nextYear ?>"><?= $isThai ? 'ถัดไป' : 'Next' ?> <i class="fa fa-chevron-right"></i></a>
    </div>

    <!-- Calendar Grid -->
    <div class="cal-grid">
        <?php
        $dowLabels = $isThai
            ? ['อา','จ','อ','พ','พฤ','ศ','ส']
            : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        foreach ($dowLabels as $dl): ?>
        <div class="cal-header"><?= $dl ?></div>
        <?php endforeach;

        // Empty cells before 1st
        for ($i = 0; $i < $firstDow; $i++): ?>
        <div class="cal-cell empty"></div>
        <?php endfor;

        $today = date('Y-m-d');

        for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $a = $allotments[$dateStr] ?? null;
            $dayBookings = $bkByDate[$dateStr] ?? [];
            $dayModels   = $mdByDate[$dateStr] ?? [];
            $isToday = ($dateStr === $today);
            $isClosed = $a && $a['is_closed'];
            $cellClass = 'cal-cell';
            if ($isToday) $cellClass .= ' today';
            if ($isClosed) $cellClass .= ' closed';

            $total = $a['total_seats'] ?? 0;
            $booked = $a['booked_seats'] ?? 0;
            $pct = $total > 0 ? min(100, round(($booked / $total) * 100)) : 0;
            $barClass = $a ? ($pct > 90 ? 'red' : ($pct > 70 ? 'yellow' : 'green')) : 'green';
            if ($a && $a['is_overbooked']) $barClass = 'red';
        ?>
        <div class="<?= $cellClass ?>" onclick="window.location='index.php?page=tour_allotment_date&date=<?= $dateStr ?>'">
            <!-- Top: day + seat badge -->
            <div class="cal-top">
                <span class="cal-day"><?= $d ?></span>
                <?php if ($a): ?>
                <span class="cal-seats-badge <?= $barClass ?>"><?= $booked ?>/<?= $total ?></span>
                <?php endif; ?>
            </div>

            <?php if ($a): ?>
            <div class="cal-fill"><div class="cal-fill-bar <?= $barClass ?>" style="width:<?= min(100, $pct) ?>%"></div></div>
            <?php endif; ?>

            <?php if ($isClosed): ?>
            <div class="cal-lock"><i class="fa fa-lock"></i> <?= $isThai ? 'ปิด' : 'Closed' ?></div>
            <?php endif; ?>

            <!-- Models breakdown -->
            <?php if (!empty($dayModels)): ?>
            <div class="cal-models">
                <?php foreach ($dayModels as $mi => $md):
                    $mColor = $modelColors[$mi % count($modelColors)];
                    $mPax = intval($md['model_pax']);
                    $paxColor = ($mPax > $total && $total > 0) ? '#dc2626' : '#475569';
                ?>
                <div class="cal-model">
                    <span class="m-dot" style="background:<?= $mColor ?>"></span>
                    <span class="m-name"><?= htmlspecialchars($md['model_name']) ?></span>
                    <span class="m-pax" style="color:<?= $paxColor ?>">(<?= $mPax ?>/<?= $total ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Latest booking + more count -->
            <?php if (!empty($dayBookings)):
                $latest = $dayBookings[0]; // already ordered by created_at DESC
                $sColor = $statusColors[$latest['status']] ?? '#94a3b8';
                $totalBk = count($dayBookings);
            ?>
            <a href="index.php?page=tour_booking_view&id=<?= intval($latest['id']) ?>" class="cal-latest" onclick="event.stopPropagation();" title="<?= htmlspecialchars($latest['booking_number'] . ' - ' . $latest['customer_name']) ?>">
                <span class="dot" style="background:<?= $sColor ?>"></span>
                <span class="bk-name"><?= htmlspecialchars($latest['customer_name']) ?></span>
            </a>
            <?php if ($totalBk > 1): ?>
            <div class="cal-more">+<?= $totalBk - 1 ?> <?= $isThai ? 'อีก' : 'more' ?></div>
            <?php endif; ?>
            <?php elseif (!$isClosed): ?>
            <div class="cal-no-bk">-</div>
            <?php endif; ?>
        </div>
        <?php endfor;

        // Fill remaining cells
        $totalCells = $firstDow + $daysInMonth;
        $remaining = (7 - ($totalCells % 7)) % 7;
        for ($i = 0; $i < $remaining; $i++): ?>
        <div class="cal-cell empty"></div>
        <?php endfor; ?>
    </div>

    <!-- Legend -->
    <div class="legend">
        <span><span class="legend-dot" style="background:#10b981"></span> <?= $isThai ? 'ว่าง (< 70%)' : 'Available (< 70%)' ?></span>
        <span><span class="legend-dot" style="background:#f59e0b"></span> <?= $isThai ? 'เกือบเต็ม (70-90%)' : 'Filling up (70-90%)' ?></span>
        <span><span class="legend-dot" style="background:#ef4444"></span> <?= $isThai ? 'เต็ม / จองเกิน' : 'Full / Overbooked' ?></span>
        <span><i class="fa fa-lock" style="color:#dc2626;margin-right:4px;"></i> <?= $isThai ? 'ปิดรับจอง' : 'Closed' ?></span>
        <span style="border-left:1px solid #e2e8f0;padding-left:16px;">
            <span class="legend-circle" style="background:#94a3b8"></span> <?= $isThai ? 'ร่าง' : 'Draft' ?>
            <span class="legend-circle" style="background:#10b981;margin-left:8px;"></span> <?= $isThai ? 'ยืนยัน' : 'Confirmed' ?>
            <span class="legend-circle" style="background:#3b82f6;margin-left:8px;"></span> <?= $isThai ? 'สำเร็จ' : 'Completed' ?>
        </span>
        <span style="border-left:1px solid #e2e8f0;padding-left:16px;">
            <?= $isThai ? 'ชื่อทริป (จอง/ที่นั่ง) = แยกตามโปรแกรม' : 'Model (booked/seats) = breakdown by trip' ?>
        </span>
    </div>

    <?php endif; ?>
</div>
