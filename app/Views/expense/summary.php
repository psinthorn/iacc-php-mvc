<?php
/**
 * Expense Summary — Monthly Report & Category Breakdown
 * 
 * Uses master-data.css design system
 * Variables from controller: $summary, $byCategory, $monthlyTotals, $year, $month, $lang
 */

$isThai = ($lang ?? '2') === '1';

$monthNames = $isThai 
    ? ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม']
    : ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

$displayYear = $isThai ? ($year + 543) : $year;
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* Header layout — extends master-data-header */
.summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 16px; }
@media (max-width: 768px) { .summary-grid { grid-template-columns: 1fr; } .master-data-header .header-content { flex-direction:column; align-items:flex-start; } }
.summary-card { background: white; border-radius: 14px; padding: 24px; border: 1px solid var(--md-border, #e2e8f0); }
.summary-card h3 { margin: 0 0 16px; font-size: 15px; color: #374151; }
.cat-bar { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.cat-bar:last-child { border-bottom: none; }
.cat-bar-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.cat-bar-info { flex: 1; }
.cat-bar-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.cat-bar-count { font-size: 11px; color: #94a3b8; }
.cat-bar-amount { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 600; color: #059669; }
.chart-bar-container { margin-top: 8px; }
.chart-bar { height: 8px; border-radius: 4px; background: #f1f5f9; overflow: hidden; }
.chart-bar-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
.month-nav { display: flex; align-items: center; gap: 12px; }
.month-nav a { padding: 6px 14px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; text-decoration: none; font-size: 13px; }
.month-nav a:hover { border-color: var(--md-primary, #4f46e5); color: var(--md-primary); }
.month-chart { display: flex; align-items: flex-end; gap: 6px; height: 120px; margin-top: 12px; }
.month-bar { flex: 1; background: var(--md-primary, #4f46e5); border-radius: 6px 6px 0 0; min-height: 4px; transition: height 0.4s ease; position: relative; }
.month-bar:hover { opacity: 0.8; }
.month-label { text-align: center; font-size: 10px; color: #94a3b8; margin-top: 6px; }
.month-labels { display: flex; gap: 6px; }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-bar-chart"></i> <?= $isThai ? 'สรุปค่าใช้จ่าย' : 'Expense Summary' ?></h2>
                <p><?= $monthNames[$month] ?> <?= $displayYear ?></p>
            </div>
            <div class="header-actions">
                <div class="month-nav">
                    <?php
                    $prevM = $month - 1; $prevY = $year;
                    if ($prevM < 1) { $prevM = 12; $prevY--; }
                    $nextM = $month + 1; $nextY = $year;
                    if ($nextM > 12) { $nextM = 1; $nextY++; }
                    ?>
                    <a href="index.php?page=expense_summary&year=<?= $prevY ?>&month=<?= $prevM ?>"><i class="fa fa-chevron-left"></i></a>
                    <span style="font-weight:600; color:#1e293b;"><?= $monthNames[$month] ?> <?= $displayYear ?></span>
                    <a href="index.php?page=expense_summary&year=<?= $nextY ?>&month=<?= $nextM ?>"><i class="fa fa-chevron-right"></i></a>
                </div>
                <a href="index.php?page=expense_list" class="btn-header btn-header-outline">
                    <i class="fa fa-list"></i> <?= $isThai ? 'รายการ' : 'List' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background: #ede9fe;"><i class="fa fa-file-text-o" style="color: #7c3aed;"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($summary['total_count'] ?? 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'รายการ' : 'Expenses' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe;"><i class="fa fa-money" style="color: #3b82f6;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($summary['total_net'] ?? 0, 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'ยอดรวม' : 'Total' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7;"><i class="fa fa-percent" style="color: #f59e0b;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($summary['total_vat'] ?? 0, 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'VAT ซื้อ' : 'Input VAT' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dcfce7;"><i class="fa fa-check-circle" style="color: #10b981;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($summary['paid_amount'] ?? 0, 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'จ่ายแล้ว' : 'Paid' ?></span>
            </div>
        </div>
    </div>

    <div class="summary-grid">
        <!-- Category Breakdown -->
        <div class="summary-card">
            <h3><i class="fa fa-pie-chart"></i> <?= $isThai ? 'แยกตามหมวดหมู่' : 'By Category' ?></h3>
            <?php 
            $maxAmount = max(array_column($byCategory ?: [['total_amount' => 1]], 'total_amount')) ?: 1;
            foreach ($byCategory as $cat): 
                $pct = $maxAmount > 0 ? ($cat['total_amount'] / $maxAmount * 100) : 0;
            ?>
            <div class="cat-bar">
                <div class="cat-bar-icon" style="background: <?= $cat['color'] ?? '#6366f1' ?>22; color: <?= $cat['color'] ?? '#6366f1' ?>;">
                    <i class="fa <?= $cat['icon'] ?? 'fa-folder' ?>"></i>
                </div>
                <div class="cat-bar-info">
                    <div class="cat-bar-name"><?= htmlspecialchars($isThai && $cat['name_th'] ? $cat['name_th'] : $cat['name']) ?></div>
                    <div class="cat-bar-count"><?= $cat['expense_count'] ?> <?= $isThai ? 'รายการ' : 'items' ?></div>
                    <div class="chart-bar-container">
                        <div class="chart-bar"><div class="chart-bar-fill" style="width: <?= $pct ?>%; background: <?= $cat['color'] ?? '#6366f1' ?>;"></div></div>
                    </div>
                </div>
                <div class="cat-bar-amount">฿<?= number_format($cat['total_amount'], 0) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($byCategory)): ?>
            <div style="text-align:center; padding:30px; color:#94a3b8;">
                <i class="fa fa-inbox" style="font-size:24px;"></i><br>
                <?= $isThai ? 'ไม่มีข้อมูล' : 'No data' ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Chart -->
        <div class="summary-card">
            <h3><i class="fa fa-line-chart"></i> <?= $isThai ? 'ค่าใช้จ่ายรายเดือน' : 'Monthly Expenses' ?> <?= $displayYear ?></h3>
            <?php
            $maxMonthly = max(array_column($monthlyTotals, 'total')) ?: 1;
            $shortMonths = $isThai 
                ? ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.']
                : ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            ?>
            <div class="month-chart">
                <?php for ($m = 1; $m <= 12; $m++): 
                    $h = $maxMonthly > 0 ? ($monthlyTotals[$m]['total'] / $maxMonthly * 100) : 0;
                    $isCurrentMonth = ($m == $month);
                ?>
                <div class="month-bar" style="height: <?= max($h, 4) ?>%; background: <?= $isCurrentMonth ? '#4f46e5' : '#e0e7ff' ?>;" 
                     title="<?= $shortMonths[$m] ?>: ฿<?= number_format($monthlyTotals[$m]['total'], 0) ?>"></div>
                <?php endfor; ?>
            </div>
            <div class="month-labels">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <div class="month-label" style="flex:1; <?= $m == $month ? 'font-weight:700; color:#4f46e5;' : '' ?>"><?= $shortMonths[$m] ?></div>
                <?php endfor; ?>
            </div>

            <!-- Status Breakdown -->
            <div style="margin-top:24px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <h3 style="font-size:13px; color:#64748b; margin-bottom:12px;"><?= $isThai ? 'แยกตามสถานะ' : 'By Status' ?></h3>
                <?php
                $statuses = [
                    'draft'    => ['icon'=>'fa-pencil',  'color'=>'#94a3b8', 'label'=>$isThai?'ฉบับร่าง':'Draft',     'count'=>$summary['draft_count']??0],
                    'pending'  => ['icon'=>'fa-clock-o', 'color'=>'#f59e0b', 'label'=>$isThai?'รออนุมัติ':'Pending',  'count'=>$summary['pending_count']??0],
                    'approved' => ['icon'=>'fa-check',   'color'=>'#3b82f6', 'label'=>$isThai?'อนุมัติ':'Approved',   'count'=>$summary['approved_count']??0],
                    'paid'     => ['icon'=>'fa-money',   'color'=>'#10b981', 'label'=>$isThai?'จ่ายแล้ว':'Paid',      'count'=>$summary['paid_count']??0],
                ];
                foreach ($statuses as $s):
                ?>
                <div style="display:flex; align-items:center; gap:10px; padding:6px 0;">
                    <i class="fa <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>; width:16px; text-align:center;"></i>
                    <span style="flex:1; font-size:13px;"><?= $s['label'] ?></span>
                    <span style="font-weight:600; font-size:14px;"><?= $s['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
