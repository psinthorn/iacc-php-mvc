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
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; background: white; border-radius: 12px; padding: 14px 20px; border: 1px solid #e2e8f0; }
.cal-nav h3 { margin: 0; font-size: 18px; font-weight: 700; }
.cal-nav a { text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; padding: 6px 14px; border-radius: 8px; border: 1px solid #e2e8f0; }
.cal-nav a:hover { background: #f1f5f9; }

.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-header { text-align: center; font-size: 11px; font-weight: 700; color: #94a3b8; padding: 8px 0; text-transform: uppercase; }
.cal-cell { background: white; border-radius: 10px; border: 1px solid #e2e8f0; min-height: 90px; padding: 8px; transition: all 0.2s; }
.cal-cell:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
.cal-cell.empty { background: #fafafa; border-color: transparent; }
.cal-cell.today { border-color: #8e44ad; border-width: 2px; }
.cal-cell.closed { background: #fef2f2; border-color: #fecaca; }
.cal-day { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
.cal-fill { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; margin-bottom: 4px; }
.cal-fill-bar { height: 100%; border-radius: 3px; transition: width 0.3s; }
.cal-fill-bar.green { background: #10b981; }
.cal-fill-bar.yellow { background: #f59e0b; }
.cal-fill-bar.red { background: #ef4444; }
.cal-seats { font-size: 11px; color: #64748b; font-weight: 600; }
.cal-seats.overbooked { color: #dc2626; }
.cal-lock { font-size: 10px; color: #dc2626; font-weight: 600; }

.legend { display: flex; gap: 20px; margin-top: 16px; padding: 12px 20px; background: white; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 12px; color: #64748b; align-items: center; }
.legend-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; margin-right: 6px; vertical-align: middle; }

@media (max-width: 768px) {
    .cal-cell { min-height: 60px; padding: 4px; }
    .cal-seats { font-size: 10px; }
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
            $isToday = ($dateStr === $today);
            $isClosed = $a && $a['is_closed'];
            $cellClass = 'cal-cell';
            if ($isToday) $cellClass .= ' today';
            if ($isClosed) $cellClass .= ' closed';
        ?>
        <a href="index.php?page=tour_allotment_date&date=<?= $dateStr ?>" class="<?= $cellClass ?>" style="text-decoration:none;color:inherit;">
            <div class="cal-day"><?= $d ?></div>
            <?php if ($a):
                $total = $a['total_seats'];
                $booked = $a['booked_seats'];
                $pct = $total > 0 ? min(100, round(($booked / $total) * 100)) : 0;
                $barClass = $pct > 90 ? 'red' : ($pct > 70 ? 'yellow' : 'green');
                if ($a['is_overbooked']) $barClass = 'red';
            ?>
            <div class="cal-fill"><div class="cal-fill-bar <?= $barClass ?>" style="width:<?= min(100, $pct) ?>%"></div></div>
            <div class="cal-seats <?= $a['is_overbooked'] ? 'overbooked' : '' ?>"><?= $booked ?>/<?= $total ?></div>
            <?php if ($isClosed): ?>
            <div class="cal-lock"><i class="fa fa-lock"></i> <?= $isThai ? 'ปิด' : 'Closed' ?></div>
            <?php endif; ?>
            <?php endif; ?>
        </a>
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
    </div>

    <?php endif; ?>
</div>
