<?php
$pageTitle = 'Tax — Dashboard';

/**
 * Tax Report Dashboard — Annual VAT Summary
 * 
 * Modern UI using master-data.css design system
 * Variables from controller: $summary, $savedReports, $year, $lang
 */

$months_th = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

$totalOutput = $summary['totals']['output_vat'] ?? 0;
$totalInput  = $summary['totals']['input_vat'] ?? 0;
$totalNet    = $summary['totals']['net_vat'] ?? 0;
$filedCount  = 0;
$pendingCount = 0;
foreach (($summary['months'] ?? []) as $m) {
    if ($m['status'] === 'filed') $filedCount++;
    elseif ($m['output_vat'] > 0 || $m['input_vat'] > 0) $pendingCount++;
}
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.tax-year-selector {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.2); border-radius: 10px; padding: 4px;
}
.tax-year-selector .yr-btn {
    padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 13px;
    color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.2s;
    border: none; background: none; cursor: pointer;
}
.tax-year-selector .yr-btn:hover { color: white; background: rgba(255,255,255,0.15); }
.tax-year-selector .yr-btn.active { color: #4f46e5; background: white; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }

.vat-summary-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
.vat-summary-card {
    background: white; border-radius: 14px; padding: 24px; text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid var(--md-border, #e2e8f0);
    transition: all 0.2s;
}
.vat-summary-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.vat-summary-card .label { font-size: 13px; font-weight: 600; color: var(--md-text-secondary, #64748b); margin-bottom: 8px; }
.vat-summary-card .value { font-size: 26px; font-weight: 800; font-family: 'SF Mono','Fira Code',monospace; }
.vat-summary-card .sub { font-size: 12px; color: var(--md-text-muted, #94a3b8); margin-top: 4px; }
.vat-summary-card.output { border-top: 3px solid #10b981; }
.vat-summary-card.output .value { color: #059669; }
.vat-summary-card.input { border-top: 3px solid #ef4444; }
.vat-summary-card.input .value { color: #dc2626; }
.vat-summary-card.net { border-top: 3px solid #4f46e5; }
.vat-summary-card.net .value { color: #4f46e5; }

.month-row { transition: background 0.15s; }
.month-row:hover { background: rgba(79,70,229,0.03) !important; }
.month-name { font-weight: 600; color: var(--md-text-primary, #1e293b); }
.month-name .month-num { font-weight: 400; color: var(--md-text-muted, #94a3b8); font-size: 12px; margin-left: 6px; }
.vat-amount { font-family: 'SF Mono','Fira Code',monospace; font-weight: 600; font-size: 13px; }
.vat-positive { color: #059669; }
.vat-negative { color: #dc2626; }
.net-amount { font-weight: 700; font-size: 14px; }

.action-btn-sm {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 12px; border-radius: 8px; font-weight: 600; font-size: 11px;
    text-decoration: none; transition: all 0.2s; letter-spacing: 0.3px;
}
.action-btn-sm:hover { transform: translateY(-1px); text-decoration: none; }
.action-btn-sm.pp30 { background: rgba(6,182,212,0.10); color: #0891b2; }
.action-btn-sm.pp30:hover { background: #06b6d4; color: white; box-shadow: 0 3px 8px rgba(6,182,212,0.3); }
.action-btn-sm.wht { background: rgba(100,116,139,0.10); color: #475569; }
.action-btn-sm.wht:hover { background: #64748b; color: white; box-shadow: 0 3px 8px rgba(100,116,139,0.3); }
.action-btn-sm.export { background: rgba(16,185,129,0.10); color: #059669; }
.action-btn-sm.export:hover { background: #10b981; color: white; box-shadow: 0 3px 8px rgba(16,185,129,0.3); }

.status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.status-pill.filed { background: #d1fae5; color: #059669; }
.status-pill.pending { background: #fef3c7; color: #d97706; }
.status-pill.empty { background: var(--md-bg-light, #f8fafc); color: var(--md-text-muted, #94a3b8); }

.total-row td {
    background: linear-gradient(135deg, rgba(79,70,229,0.04), rgba(79,70,229,0.08)) !important;
    border-top: 2px solid var(--md-primary, #4f46e5) !important;
    font-weight: 700 !important; font-size: 14px !important;
}

.saved-reports-section { margin-top: 24px; }
.saved-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border: 1px solid var(--md-border, #e2e8f0);
    border-radius: 10px; margin-bottom: 8px; background: white; transition: all 0.2s;
}
.saved-item:hover { border-color: var(--md-primary, #4f46e5); background: rgba(79,70,229,0.02); }
.saved-item .saved-info { display: flex; align-items: center; gap: 12px; }
.saved-item .saved-type {
    background: rgba(79,70,229,0.10); color: #4f46e5;
    font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 6px;
}
.saved-item .saved-date { font-size: 12px; color: var(--md-text-muted, #94a3b8); }

@media (max-width: 768px) {
    .vat-summary-row { grid-template-columns: 1fr; }
    .action-btn-sm span { display: none; }
}
</style>

<div class="master-data-container">

    <!-- Page Header -->
    <div class="master-data-header">
        <h2><i class="fa fa-calculator"></i> <?= $lang === 'th' ? 'รายงานภาษี' : 'Tax Reports' ?></h2>
        <div class="tax-year-selector">
            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
            <a href="index.php?page=tax_reports&year=<?= $y ?>" class="yr-btn <?= $year == $y ? 'active' : '' ?>"><?= $y + ($lang === 'th' ? 543 : 0) ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible" style="border-radius:10px; border-left:4px solid #10b981;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'บันทึกรายงานเรียบร้อย' : 'Report saved successfully' ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card success">
            <i class="fa fa-arrow-up stat-icon"></i>
            <div class="stat-value">฿<?= number_format($totalOutput, 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ภาษีขายรวม' : 'Total Output VAT' ?></div>
        </div>
        <div class="stat-card danger">
            <i class="fa fa-arrow-down stat-icon"></i>
            <div class="stat-value">฿<?= number_format($totalInput, 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ภาษีซื้อรวม' : 'Total Input VAT' ?></div>
        </div>
        <div class="stat-card primary">
            <i class="fa fa-calculator stat-icon"></i>
            <div class="stat-value">฿<?= number_format($totalNet, 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'VAT สุทธิ' : 'Net VAT' ?></div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-value"><?= $filedCount ?>/12</div>
            <div class="stat-label"><?= $lang === 'th' ? 'ยื่นแล้ว' : 'Filed' ?></div>
        </div>
        <div class="stat-card warning">
            <i class="fa fa-clock-o stat-icon"></i>
            <div class="stat-value"><?= $pendingCount ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'รอดำเนินการ' : 'Pending' ?></div>
        </div>
    </div>

    <!-- VAT Annual Summary Cards -->
    <div class="vat-summary-row">
        <div class="vat-summary-card output">
            <div class="label"><i class="fa fa-arrow-circle-up"></i> <?= $lang === 'th' ? 'ภาษีขายรวมทั้งปี' : 'Annual Output VAT' ?></div>
            <div class="value">฿<?= number_format($totalOutput, 2) ?></div>
            <div class="sub"><?= $lang === 'th' ? 'จากการขายสินค้า/บริการ' : 'From sales of goods/services' ?></div>
        </div>
        <div class="vat-summary-card input">
            <div class="label"><i class="fa fa-arrow-circle-down"></i> <?= $lang === 'th' ? 'ภาษีซื้อรวมทั้งปี' : 'Annual Input VAT' ?></div>
            <div class="value">฿<?= number_format($totalInput, 2) ?></div>
            <div class="sub"><?= $lang === 'th' ? 'จากการซื้อสินค้า/บริการ' : 'From purchases of goods/services' ?></div>
        </div>
        <div class="vat-summary-card net">
            <div class="label"><i class="fa fa-balance-scale"></i> <?= $lang === 'th' ? 'VAT สุทธิทั้งปี' : 'Annual Net VAT' ?></div>
            <div class="value">฿<?= number_format($totalNet, 2) ?></div>
            <div class="sub">
                <?php if ($totalNet > 0): ?>
                    <span style="color:#ef4444;"><i class="fa fa-exclamation-triangle"></i> <?= $lang === 'th' ? 'ต้องชำระ' : 'Tax Payable' ?></span>
                <?php elseif ($totalNet < 0): ?>
                    <span style="color:#10b981;"><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ขอคืนได้' : 'Refundable' ?></span>
                <?php else: ?>
                    <?= $lang === 'th' ? 'สมดุล' : 'Balanced' ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown Table -->
    <div class="master-data-table">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0;">
            <span style="font-size:14px; font-weight:700; color:var(--md-text-primary, #1e293b);">
                <i class="fa fa-calendar"></i> <?= $lang === 'th' ? 'รายละเอียดรายเดือน ปี ' . ($year + 543) : 'Monthly Breakdown — ' . $year ?>
            </span>
            <a href="index.php?page=tax_report_export&type=pp30&year=<?= $year ?>&month=1&format=csv" class="action-btn-sm export">
                <i class="fa fa-download"></i> <span>Export CSV</span>
            </a>
        </div>
        <table>
            <thead>
                <tr>
                    <th><?= $lang === 'th' ? 'เดือน' : 'Month' ?></th>
                    <th style="text-align:right;"><?= $lang === 'th' ? 'ภาษีขาย' : 'Output VAT' ?></th>
                    <th style="text-align:right;"><?= $lang === 'th' ? 'ภาษีซื้อ' : 'Input VAT' ?></th>
                    <th style="text-align:right;"><?= $lang === 'th' ? 'VAT สุทธิ' : 'Net VAT' ?></th>
                    <th style="text-align:center;"><?= $lang === 'th' ? 'สถานะ' : 'Status' ?></th>
                    <th style="text-align:center; width:180px;"><?= $lang === 'th' ? 'จัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($summary['months'] ?? []) as $m): 
                    $hasData = ($m['output_vat'] > 0 || $m['input_vat'] > 0);
                    $monthName = $lang === 'th' ? ($months_th[$m['month']] ?? $m['month_name']) : $m['month_name'];
                ?>
                <tr class="month-row">
                    <td>
                        <span class="month-name">
                            <?= $monthName ?>
                            <span class="month-num"><?= sprintf('%02d', $m['month']) ?>/<?= $year ?></span>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <span class="vat-amount <?= $m['output_vat'] > 0 ? 'vat-positive' : '' ?>">
                            <?= $m['output_vat'] > 0 ? number_format($m['output_vat'], 2) : '-' ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <span class="vat-amount <?= $m['input_vat'] > 0 ? 'vat-negative' : '' ?>">
                            <?= $m['input_vat'] > 0 ? number_format($m['input_vat'], 2) : '-' ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <?php if ($hasData): ?>
                        <span class="vat-amount net-amount <?= $m['net_vat'] >= 0 ? 'vat-negative' : 'vat-positive' ?>">
                            <?= number_format($m['net_vat'], 2) ?>
                        </span>
                        <?php else: ?>
                        <span class="vat-amount" style="color:var(--md-text-muted,#94a3b8);">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($m['status'] === 'filed'): ?>
                            <span class="status-pill filed"><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ยื่นแล้ว' : 'Filed' ?></span>
                        <?php elseif ($hasData): ?>
                            <span class="status-pill pending"><i class="fa fa-clock-o"></i> <?= $lang === 'th' ? 'รอยื่น' : 'Pending' ?></span>
                        <?php else: ?>
                            <span class="status-pill empty">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="index.php?page=tax_report_pp30&year=<?= $year ?>&month=<?= $m['month'] ?>" class="action-btn-sm pp30" title="ภ.พ.30">
                                <i class="fa fa-file-text"></i> <span>PP30</span>
                            </a>
                            <a href="index.php?page=tax_report_wht&year=<?= $year ?>&month=<?= $m['month'] ?>&type=pnd53" class="action-btn-sm wht" title="ภ.ง.ด.53">
                                <i class="fa fa-file"></i> <span>WHT</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong><?= $lang === 'th' ? 'รวมทั้งปี' : 'Annual Total' ?></strong></td>
                    <td style="text-align:right;"><span class="vat-amount vat-positive"><?= number_format($totalOutput, 2) ?></span></td>
                    <td style="text-align:right;"><span class="vat-amount vat-negative"><?= number_format($totalInput, 2) ?></span></td>
                    <td style="text-align:right;"><span class="vat-amount net-amount <?= $totalNet >= 0 ? 'vat-negative' : 'vat-positive' ?>"><?= number_format($totalNet, 2) ?></span></td>
                    <td style="text-align:center;"><?= $filedCount ?>/12</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Saved Reports -->
    <?php if (!empty($savedReports)): ?>
    <div class="saved-reports-section">
        <div style="font-size:14px; font-weight:700; color:var(--md-text-primary, #1e293b); margin-bottom:12px;">
            <i class="fa fa-archive"></i> <?= $lang === 'th' ? 'รายงานที่บันทึกไว้' : 'Saved Reports' ?>
        </div>
        <?php foreach ($savedReports as $r): ?>
        <div class="saved-item">
            <div class="saved-info">
                <span class="saved-type"><?= htmlspecialchars($r['report_type']) ?></span>
                <span style="font-weight:600; font-size:13px; color:var(--md-text-primary, #1e293b);">
                    <?= $lang === 'th' ? ($months_th[$r['tax_month']] ?? '') : date('F', mktime(0,0,0,$r['tax_month'],1)) ?> <?= $r['tax_year'] ?>
                </span>
                <span class="status-pill <?= $r['status'] === 'filed' ? 'filed' : 'pending' ?>">
                    <?= ucfirst($r['status']) ?>
                </span>
            </div>
            <div class="saved-date">
                <i class="fa fa-clock-o"></i> <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
