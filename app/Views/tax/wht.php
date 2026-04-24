<?php
$pageTitle = 'Tax — WHT Report';

/**
 * WHT Report (ภ.ง.ด.3 / ภ.ง.ด.53) — Modern UI
 * 
 * Uses master-data.css design system
 * Variables from controller: $report, $year, $month, $type, $lang
 */

$months_th = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$monthLabel = $lang === 'th' ? ($months_th[$month] ?? '') . ' ' . ($year + 543) : date('F', mktime(0,0,0,$month,1)) . ' ' . $year;
$itemCount = count($report['items'] ?? []);
$totalPayment = $report['total_payment'] ?? 0;
$totalWht = $report['total_wht'] ?? 0;
$formTitle = $type === 'pnd3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53';
$formTitleEn = $type === 'pnd3' ? 'PND 3 — Individual WHT' : 'PND 53 — Corporate WHT';
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.wht-type-toggle {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,0.18); border-radius: 10px; padding: 4px;
}
.wht-type-toggle a {
    padding: 8px 20px; border-radius: 8px; font-weight: 600; font-size: 13px;
    color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.wht-type-toggle a:hover { color: white; background: rgba(255,255,255,0.12); }
.wht-type-toggle a.active { color: #4f46e5; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }

.wht-table-section { background: white; border-radius: 14px; border: 1px solid var(--md-border, #e2e8f0); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.wht-table-header { padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--md-border, #e2e8f0); }
.wht-table-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--md-text-primary, #1e293b); display: flex; align-items: center; gap: 8px; }
.wht-count-badge { background: rgba(79,70,229,0.1); color: #4f46e5; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; }

.wht-table { width: 100%; border-collapse: collapse; }
.wht-table thead th {
    padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--md-text-secondary, #64748b);
    background: var(--md-bg-secondary, #f8fafc); border-bottom: 2px solid var(--md-border, #e2e8f0);
    white-space: nowrap;
}
.wht-table tbody td {
    padding: 14px 16px; font-size: 13px; color: var(--md-text-primary, #1e293b);
    border-bottom: 1px solid var(--md-border, #e2e8f0); vertical-align: middle;
}
.wht-table tbody tr { transition: background 0.15s; }
.wht-table tbody tr:hover { background: rgba(79,70,229,0.03); }
.wht-table tbody tr:last-child td { border-bottom: none; }
.wht-table tfoot td {
    padding: 14px 16px; font-size: 14px; font-weight: 700;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-top: 2px solid var(--md-border, #e2e8f0);
    color: var(--md-text-primary, #1e293b);
}
.mono { font-family: 'SF Mono','Fira Code',monospace; font-weight: 600; }
.rate-pill { background: rgba(245,158,11,0.1); color: #d97706; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }

.wht-empty-state {
    text-align: center; padding: 60px 24px;
}
.wht-empty-icon {
    width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 20px;
    background: linear-gradient(135deg, rgba(79,70,229,0.08), rgba(99,102,241,0.12));
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; color: #6366f1;
}
.wht-empty-title { font-size: 16px; font-weight: 700; color: var(--md-text-primary, #1e293b); margin-bottom: 6px; }
.wht-empty-sub { font-size: 13px; color: var(--md-text-secondary, #64748b); }

@media (max-width: 768px) {
    .wht-type-toggle { flex-direction: column; width: 100%; }
    .wht-type-toggle a { justify-content: center; }
    .stats-row { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>

<div class="master-data-container">

    <!-- Page Header with Type Toggle -->
    <div class="master-data-header" style="flex-direction:column; gap:16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 style="margin-bottom:4px;">
                    <i class="fa fa-file-text-o"></i> 
                    <?= $lang === 'th' ? $formTitle . ' — หักภาษี ณ ที่จ่าย' : $formTitleEn ?>
                </h2>
                <div style="font-size:14px; opacity:0.85;">
                    <i class="fa fa-calendar"></i> <?= $monthLabel ?>
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="index.php?page=tax_reports&year=<?= $year ?>" class="btn btn-sm" style="background:rgba(255,255,255,0.18); color:white; border-radius:8px; font-weight:600; border:1px solid rgba(255,255,255,0.25);">
                    <i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับ' : 'Back' ?>
                </a>
                <a href="index.php?page=tax_report_export&type=<?= $type ?>&year=<?= $year ?>&month=<?= $month ?>&format=csv" class="btn btn-sm" style="background:rgba(16,185,129,0.9); color:white; border-radius:8px; font-weight:600;">
                    <i class="fa fa-download"></i> CSV
                </a>
                <form method="post" action="index.php?page=tax_report_save" style="margin:0; display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="report_type" value="<?= strtoupper($type) ?>">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <button type="submit" class="btn btn-sm" style="background:white; color:#4f46e5; border-radius:8px; font-weight:700;">
                        <i class="fa fa-save"></i> <?= $lang === 'th' ? 'บันทึก' : 'Save' ?>
                    </button>
                </form>
            </div>
        </div>
        <!-- Type Toggle Pills -->
        <div class="wht-type-toggle">
            <a href="index.php?page=tax_report_wht&year=<?= $year ?>&month=<?= $month ?>&type=pnd3" class="<?= $type === 'pnd3' ? 'active' : '' ?>">
                <i class="fa fa-user"></i> ภ.ง.ด.3 <?= $lang === 'th' ? '(บุคคลธรรมดา)' : '(Individual)' ?>
            </a>
            <a href="index.php?page=tax_report_wht&year=<?= $year ?>&month=<?= $month ?>&type=pnd53" class="<?= $type === 'pnd53' ? 'active' : '' ?>">
                <i class="fa fa-building"></i> ภ.ง.ด.53 <?= $lang === 'th' ? '(นิติบุคคล)' : '(Corporate)' ?>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card primary">
            <i class="fa fa-list-ol stat-icon"></i>
            <div class="stat-value"><?= $itemCount ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'รายการทั้งหมด' : 'Total Records' ?></div>
        </div>
        <div class="stat-card success">
            <i class="fa fa-money stat-icon"></i>
            <div class="stat-value"><?= number_format($totalPayment, 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ยอดจ่ายรวม (฿)' : 'Total Paid (฿)' ?></div>
        </div>
        <div class="stat-card danger">
            <i class="fa fa-minus-circle stat-icon"></i>
            <div class="stat-value"><?= number_format($totalWht, 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ภาษีหัก ณ ที่จ่าย (฿)' : 'WHT Deducted (฿)' ?></div>
        </div>
        <div class="stat-card warning">
            <i class="fa fa-percent stat-icon"></i>
            <div class="stat-value"><?= $totalPayment > 0 ? number_format(($totalWht / $totalPayment) * 100, 1) : '0.0' ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'อัตราเฉลี่ย (%)' : 'Avg Rate (%)' ?></div>
        </div>
    </div>

    <!-- WHT Records Table -->
    <div class="wht-table-section">
        <div class="wht-table-header">
            <h3>
                <i class="fa fa-table"></i>
                <?= $lang === 'th' ? 'รายการหักภาษี ณ ที่จ่าย' : 'Withholding Tax Records' ?>
            </h3>
            <span class="wht-count-badge"><?= $itemCount ?> <?= $lang === 'th' ? 'รายการ' : 'records' ?></span>
        </div>

        <?php if (empty($report['items'])): ?>
        <!-- Empty State -->
        <div class="wht-empty-state">
            <div class="wht-empty-icon"><i class="fa fa-file-text-o"></i></div>
            <div class="wht-empty-title">
                <?= $lang === 'th' ? 'ไม่พบรายการหักภาษี ณ ที่จ่าย' : 'No Withholding Tax Records' ?>
            </div>
            <div class="wht-empty-sub">
                <?= $lang === 'th' 
                    ? 'ไม่พบข้อมูล ' . $formTitle . ' ในเดือน ' . ($months_th[$month] ?? '') . ' ' . ($year + 543)
                    : 'No ' . $formTitleEn . ' records found for ' . date('F Y', mktime(0,0,0,$month,1,$year)) ?>
            </div>
            <div style="margin-top:12px;">
                <small style="color:var(--md-text-muted, #94a3b8);">
                    <i class="fa fa-info-circle"></i> 
                    <?= $lang === 'th' ? 'หมายเหตุ: ต้องเพิ่มฟิลด์ WHT ในตาราง pay ก่อน' : 'Note: WHT fields need to be added to the pay table first' ?>
                </small>
            </div>
        </div>
        <?php else: ?>
        <!-- Data Table -->
        <div style="overflow-x:auto;">
            <table class="wht-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th><?= $lang === 'th' ? 'วันที่ชำระ' : 'Payment Date' ?></th>
                        <th><?= $lang === 'th' ? 'ผู้รับเงิน' : 'Payee' ?></th>
                        <th><?= $lang === 'th' ? 'เลขผู้เสียภาษี' : 'Tax ID' ?></th>
                        <th><?= $lang === 'th' ? 'ประเภทเงินได้' : 'Income Type' ?></th>
                        <th style="text-align:right;"><?= $lang === 'th' ? 'จำนวนเงินจ่าย' : 'Amount Paid' ?></th>
                        <th style="text-align:center;"><?= $lang === 'th' ? 'อัตรา' : 'Rate' ?></th>
                        <th style="text-align:right;"><?= $lang === 'th' ? 'ภาษีหัก ณ ที่จ่าย' : 'WHT Amount' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['items'] as $i => $item): ?>
                    <tr>
                        <td style="color:var(--md-text-muted,#94a3b8); font-weight:600;"><?= $i + 1 ?></td>
                        <td>
                            <span style="font-weight:600;"><?= $item['payment_date'] ?></span>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--md-text-primary,#1e293b);"><?= htmlspecialchars($item['payee_name'] ?? '-') ?></div>
                        </td>
                        <td>
                            <code style="background:var(--md-bg-secondary,#f8fafc); padding:3px 8px; border-radius:6px; font-size:12px; color:var(--md-text-secondary,#64748b);">
                                <?= htmlspecialchars($item['payee_tax_id'] ?? '-') ?>
                            </code>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--md-text-secondary,#64748b);">
                                <?= htmlspecialchars($item['income_type'] ?? '-') ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <span class="mono"><?= number_format(floatval($item['payment_amount']), 2) ?></span>
                        </td>
                        <td style="text-align:center;">
                            <span class="rate-pill"><?= $item['wht_rate'] ?? '-' ?>%</span>
                        </td>
                        <td style="text-align:right;">
                            <span class="mono" style="color:#dc2626;"><?= number_format(floatval($item['wht_amount']), 2) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right; font-size:14px;">
                            <?= $lang === 'th' ? 'รวมทั้งสิ้น' : 'Grand Total' ?>
                        </td>
                        <td style="text-align:right;">
                            <span class="mono" style="font-size:15px;"><?= number_format($totalPayment, 2) ?></span>
                        </td>
                        <td></td>
                        <td style="text-align:right;">
                            <span class="mono" style="font-size:15px; color:#dc2626;"><?= number_format($totalWht, 2) ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
