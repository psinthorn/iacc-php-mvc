<?php
/**
 * PP30 (ภ.พ.30) — Monthly VAT Return — Modern UI
 * 
 * Uses master-data.css design system
 * Variables from controller: $report, $year, $month, $lang
 */

$months_th = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$monthLabel = $lang === 'th' ? ($months_th[$month] ?? '') . ' ' . ($year + 543) : date('F', mktime(0,0,0,$month,1)) . ' ' . $year;

$outputItems = $report['output_vat']['items'] ?? [];
$inputItems  = $report['input_vat']['items'] ?? [];
$outputBase  = $report['output_vat']['total_base'] ?? 0;
$outputVat   = $report['output_vat']['total_vat'] ?? 0;
$inputBase   = $report['input_vat']['total_base'] ?? 0;
$inputVat    = $report['input_vat']['total_vat'] ?? 0;
$netVat      = $report['net_vat'] ?? 0;
$vatPayable  = $report['vat_payable'] ?? 0;
$vatRefund   = $report['vat_refundable'] ?? 0;
$isPayable   = $vatPayable > 0;
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.vat-section {
    background: white; border-radius: 14px; overflow: hidden;
    border: 1px solid var(--md-border, #e2e8f0);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 20px;
}
.vat-section-header {
    padding: 16px 24px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--md-border, #e2e8f0);
}
.vat-section-header h3 {
    margin: 0; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;
}
.vat-section-header.output { border-left: 4px solid #10b981; }
.vat-section-header.output h3 { color: #059669; }
.vat-section-header.input { border-left: 4px solid #ef4444; }
.vat-section-header.input h3 { color: #dc2626; }

.vat-count { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
.vat-count.output { background: #d1fae5; color: #059669; }
.vat-count.input { background: #fee2e2; color: #dc2626; }

.vat-table { width: 100%; border-collapse: collapse; }
.vat-table thead th {
    padding: 12px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--md-text-secondary, #64748b);
    background: var(--md-bg-secondary, #f8fafc); border-bottom: 2px solid var(--md-border, #e2e8f0);
    white-space: nowrap;
}
.vat-table tbody td {
    padding: 13px 16px; font-size: 13px; color: var(--md-text-primary, #1e293b);
    border-bottom: 1px solid var(--md-border, #e2e8f0); vertical-align: middle;
}
.vat-table tbody tr { transition: background 0.15s; }
.vat-table tbody tr:hover { background: rgba(79,70,229,0.03); }
.vat-table tbody tr:last-child td { border-bottom: none; }
.vat-table tfoot td {
    padding: 14px 16px; font-weight: 700;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-top: 2px solid var(--md-border, #e2e8f0);
}
.mono { font-family: 'SF Mono','Fira Code',monospace; font-weight: 600; }

.vat-empty-row td {
    text-align: center; padding: 40px 16px !important; color: var(--md-text-muted, #94a3b8); font-size: 14px;
}

/* Net Summary Banner */
.net-summary-banner {
    background: white; border-radius: 14px; overflow: hidden;
    border: 1px solid var(--md-border, #e2e8f0);
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.net-summary-top {
    padding: 24px 32px; display: grid; grid-template-columns: 1fr auto 1fr auto 1fr;
    align-items: center; gap: 16px;
}
.net-col { text-align: center; }
.net-col .label { font-size: 13px; font-weight: 600; color: var(--md-text-secondary, #64748b); margin-bottom: 6px; }
.net-col .amount { font-size: 28px; font-weight: 800; font-family: 'SF Mono','Fira Code',monospace; }
.net-col.output .amount { color: #059669; }
.net-col.input .amount { color: #dc2626; }
.net-col.result .amount { color: #4f46e5; }
.net-operator { font-size: 28px; font-weight: 300; color: var(--md-text-muted, #94a3b8); text-align: center; }

.net-summary-bottom {
    padding: 16px 32px;
    display: flex; align-items: center; justify-content: center; gap: 12px;
}
.net-summary-bottom.payable { background: linear-gradient(135deg, #fef2f2, #fee2e2); border-top: 2px solid #fca5a5; }
.net-summary-bottom.refundable { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-top: 2px solid #86efac; }
.net-result-icon {
    width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.net-result-icon.payable { background: rgba(239,68,68,0.12); color: #dc2626; }
.net-result-icon.refundable { background: rgba(16,185,129,0.12); color: #059669; }
.net-result-text { font-size: 15px; font-weight: 700; }
.net-result-text.payable { color: #dc2626; }
.net-result-text.refundable { color: #059669; }

/* Search Toolbar */
.pp30-search-bar {
    background: white; border-radius: 14px; padding: 16px 24px;
    border: 1px solid var(--md-border, #e2e8f0); box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.pp30-search-input {
    flex: 1; min-width: 200px; padding: 10px 16px 10px 40px; border-radius: 10px;
    border: 1.5px solid var(--md-border, #e2e8f0); font-size: 13px; font-weight: 500;
    font-family: 'Inter', sans-serif; transition: all 0.2s; background: var(--md-bg-secondary, #f8fafc);
}
.pp30-search-input:focus {
    outline: none; border-color: #4f46e5; background: white;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
.pp30-search-wrap {
    position: relative; flex: 1; min-width: 200px;
}
.pp30-search-wrap .search-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: var(--md-text-muted, #94a3b8); font-size: 14px; pointer-events: none;
}
.pp30-search-wrap .clear-btn {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--md-text-muted, #94a3b8); cursor: pointer;
    font-size: 14px; display: none; padding: 4px;
}
.pp30-search-wrap .clear-btn:hover { color: #ef4444; }
.pp30-match-count {
    font-size: 12px; font-weight: 600; color: var(--md-text-secondary, #64748b);
    white-space: nowrap;
}
.pp30-match-count strong { color: var(--md-primary, #4f46e5); }
tr.pp30-hidden { display: none; }
tr.pp30-highlight td { background: rgba(79,70,229,0.04); }

@media (max-width: 768px) {
    .net-summary-top { grid-template-columns: 1fr; gap: 12px; }
    .net-operator { font-size: 20px; }
    .net-col .amount { font-size: 22px; }
    .stats-row { grid-template-columns: repeat(2, 1fr) !important; }
    .pp30-search-bar { flex-direction: column; }
}
</style>

<div class="master-data-container">

    <!-- Page Header -->
    <div class="master-data-header">
        <div>
            <h2 style="margin-bottom:4px;">
                <i class="fa fa-file-text"></i> 
                <?= $lang === 'th' ? 'ภ.พ.30 — แบบยื่น VAT รายเดือน' : 'PP30 — Monthly VAT Return' ?>
            </h2>
            <div style="font-size:14px; opacity:0.85;">
                <i class="fa fa-calendar"></i> <?= $monthLabel ?>
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="index.php?page=tax_reports&year=<?= $year ?>" class="btn btn-sm" style="background:rgba(255,255,255,0.18); color:white; border-radius:8px; font-weight:600; border:1px solid rgba(255,255,255,0.25);">
                <i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับ' : 'Back' ?>
            </a>
            <a href="index.php?page=tax_report_export&type=pp30&year=<?= $year ?>&month=<?= $month ?>&format=csv" class="btn btn-sm" style="background:rgba(16,185,129,0.9); color:white; border-radius:8px; font-weight:600;">
                <i class="fa fa-download"></i> CSV
            </a>
            <form method="post" action="index.php?page=tax_report_save" style="margin:0; display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="report_type" value="PP30">
                <input type="hidden" name="year" value="<?= $year ?>">
                <input type="hidden" name="month" value="<?= $month ?>">
                <button type="submit" class="btn btn-sm" style="background:white; color:#4f46e5; border-radius:8px; font-weight:700;">
                    <i class="fa fa-save"></i> <?= $lang === 'th' ? 'บันทึก' : 'Save' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-row">
        <div class="stat-card success">
            <i class="fa fa-arrow-up stat-icon"></i>
            <div class="stat-value"><?= count($outputItems) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'รายการภาษีขาย' : 'Output VAT Items' ?></div>
        </div>
        <div class="stat-card danger">
            <i class="fa fa-arrow-down stat-icon"></i>
            <div class="stat-value"><?= count($inputItems) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'รายการภาษีซื้อ' : 'Input VAT Items' ?></div>
        </div>
        <div class="stat-card primary">
            <i class="fa fa-calculator stat-icon"></i>
            <div class="stat-value"><?= number_format(abs($netVat), 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'VAT สุทธิ (฿)' : 'Net VAT (฿)' ?></div>
        </div>
        <div class="stat-card <?= $isPayable ? 'warning' : 'info' ?>">
            <i class="fa fa-<?= $isPayable ? 'exclamation-triangle' : 'check-circle' ?> stat-icon"></i>
            <div class="stat-value"><?= $isPayable ? ($lang === 'th' ? 'ชำระ' : 'Pay') : ($lang === 'th' ? 'คืน' : 'Refund') ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'สถานะ' : 'Status' ?></div>
        </div>
    </div>

    <!-- Search Filter -->
    <div class="pp30-search-bar">
        <div class="pp30-search-wrap">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="pp30Search" class="pp30-search-input" 
                   placeholder="<?= $lang === 'th' ? 'ค้นหา... ชื่อลูกค้า, ผู้ขาย, เลขใบกำกับ, เลขผู้เสียภาษี' : 'Search... customer, vendor, invoice no., tax ID' ?>">
            <button class="clear-btn" id="pp30Clear" title="Clear"><i class="fa fa-times"></i></button>
        </div>
        <div class="pp30-match-count" id="pp30MatchCount"></div>
    </div>

    <!-- Output VAT Section -->
    <div class="vat-section">
        <div class="vat-section-header output">
            <h3><i class="fa fa-arrow-circle-up"></i> <?= $lang === 'th' ? 'ภาษีขาย (Output VAT)' : 'Output VAT (Sales)' ?></h3>
            <span class="vat-count output"><?= count($outputItems) ?> <?= $lang === 'th' ? 'รายการ' : 'items' ?></span>
        </div>
        <div style="overflow-x:auto;">
            <table class="vat-table" id="outputVatTable">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th><?= $lang === 'th' ? 'เลขที่ใบกำกับภาษี' : 'Tax Invoice No.' ?></th>
                        <th><?= $lang === 'th' ? 'วันที่' : 'Date' ?></th>
                        <th><?= $lang === 'th' ? 'ชื่อลูกค้า' : 'Customer' ?></th>
                        <th><?= $lang === 'th' ? 'เลขผู้เสียภาษี' : 'Tax ID' ?></th>
                        <th style="text-align:right;"><?= $lang === 'th' ? 'มูลค่า' : 'Base Amount' ?></th>
                        <th style="text-align:right;"><?= $lang === 'th' ? 'ภาษี' : 'VAT' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outputItems)): ?>
                    <tr class="vat-empty-row"><td colspan="7"><i class="fa fa-inbox"></i> <?= $lang === 'th' ? 'ไม่มีข้อมูลภาษีขาย' : 'No output VAT data' ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($outputItems as $i => $item): ?>
                    <tr>
                        <td style="color:var(--md-text-muted,#94a3b8); font-weight:600;"><?= $i + 1 ?></td>
                        <td><span style="font-weight:600;"><?= htmlspecialchars($item['tax_invoice_no'] ?? '-') ?></span></td>
                        <td><?= $item['tax_invoice_date'] ?? '-' ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($item['customer_name'] ?? '-') ?></td>
                        <td>
                            <code style="background:var(--md-bg-secondary,#f8fafc); padding:3px 8px; border-radius:6px; font-size:12px; color:var(--md-text-secondary,#64748b);">
                                <?= htmlspecialchars($item['customer_tax_id'] ?? '-') ?>
                            </code>
                        </td>
                        <td style="text-align:right;"><span class="mono"><?= number_format($item['base_amount'], 2) ?></span></td>
                        <td style="text-align:right;"><span class="mono" style="color:#059669;"><?= number_format($item['vat_amount'], 2) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right;"><?= $lang === 'th' ? 'รวมภาษีขาย' : 'Total Output VAT' ?></td>
                        <td style="text-align:right;"><span class="mono" style="font-size:14px;"><?= number_format($outputBase, 2) ?></span></td>
                        <td style="text-align:right;"><span class="mono" style="font-size:14px; color:#059669;"><?= number_format($outputVat, 2) ?></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Input VAT Section -->
    <div class="vat-section">
        <div class="vat-section-header input">
            <h3><i class="fa fa-arrow-circle-down"></i> <?= $lang === 'th' ? 'ภาษีซื้อ (Input VAT)' : 'Input VAT (Purchases)' ?></h3>
            <span class="vat-count input"><?= count($inputItems) ?> <?= $lang === 'th' ? 'รายการ' : 'items' ?></span>
        </div>
        <div style="overflow-x:auto;">
            <table class="vat-table" id="inputVatTable">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th><?= $lang === 'th' ? 'เลขที่ PO' : 'PO No.' ?></th>
                        <th><?= $lang === 'th' ? 'วันที่' : 'Date' ?></th>
                        <th><?= $lang === 'th' ? 'ชื่อผู้ขาย' : 'Vendor' ?></th>
                        <th><?= $lang === 'th' ? 'เลขผู้เสียภาษี' : 'Tax ID' ?></th>
                        <th style="text-align:right;"><?= $lang === 'th' ? 'มูลค่า' : 'Base Amount' ?></th>
                        <th style="text-align:right;"><?= $lang === 'th' ? 'ภาษี' : 'VAT' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inputItems)): ?>
                    <tr class="vat-empty-row"><td colspan="7"><i class="fa fa-inbox"></i> <?= $lang === 'th' ? 'ไม่มีข้อมูลภาษีซื้อ' : 'No input VAT data' ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($inputItems as $i => $item): ?>
                    <tr>
                        <td style="color:var(--md-text-muted,#94a3b8); font-weight:600;"><?= $i + 1 ?></td>
                        <td><span style="font-weight:600;"><?= htmlspecialchars($item['po_number'] ?? '-') ?></span></td>
                        <td><?= $item['po_date'] ?? '-' ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($item['vendor_name'] ?? '-') ?></td>
                        <td>
                            <code style="background:var(--md-bg-secondary,#f8fafc); padding:3px 8px; border-radius:6px; font-size:12px; color:var(--md-text-secondary,#64748b);">
                                <?= htmlspecialchars($item['vendor_tax_id'] ?? '-') ?>
                            </code>
                        </td>
                        <td style="text-align:right;"><span class="mono"><?= number_format($item['base_amount'], 2) ?></span></td>
                        <td style="text-align:right;"><span class="mono" style="color:#dc2626;"><?= number_format($item['vat_amount'], 2) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right;"><?= $lang === 'th' ? 'รวมภาษีซื้อ' : 'Total Input VAT' ?></td>
                        <td style="text-align:right;"><span class="mono" style="font-size:14px;"><?= number_format($inputBase, 2) ?></span></td>
                        <td style="text-align:right;"><span class="mono" style="font-size:14px; color:#dc2626;"><?= number_format($inputVat, 2) ?></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Net VAT Summary Banner -->
    <div class="net-summary-banner">
        <div class="net-summary-top">
            <div class="net-col output">
                <div class="label"><i class="fa fa-arrow-up"></i> <?= $lang === 'th' ? 'ภาษีขาย' : 'Output VAT' ?></div>
                <div class="amount"><?= number_format($outputVat, 2) ?></div>
            </div>
            <div class="net-operator">−</div>
            <div class="net-col input">
                <div class="label"><i class="fa fa-arrow-down"></i> <?= $lang === 'th' ? 'ภาษีซื้อ' : 'Input VAT' ?></div>
                <div class="amount"><?= number_format($inputVat, 2) ?></div>
            </div>
            <div class="net-operator">=</div>
            <div class="net-col result">
                <div class="label"><i class="fa fa-calculator"></i> <?= $lang === 'th' ? 'VAT สุทธิ' : 'Net VAT' ?></div>
                <div class="amount"><?= number_format($netVat, 2) ?></div>
            </div>
        </div>
        <div class="net-summary-bottom <?= $isPayable ? 'payable' : 'refundable' ?>">
            <div class="net-result-icon <?= $isPayable ? 'payable' : 'refundable' ?>">
                <i class="fa fa-<?= $isPayable ? 'exclamation-circle' : 'check-circle' ?>"></i>
            </div>
            <div class="net-result-text <?= $isPayable ? 'payable' : 'refundable' ?>">
                <?php if ($isPayable): ?>
                    <?= $lang === 'th' ? 'ต้องชำระภาษี' : 'Tax Payable' ?>: ฿<?= number_format($vatPayable, 2) ?>
                <?php else: ?>
                    <?= $lang === 'th' ? 'ขอคืนภาษีได้' : 'Tax Refundable' ?>: ฿<?= number_format($vatRefund, 2) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
(function() {
    const input = document.getElementById('pp30Search');
    const clearBtn = document.getElementById('pp30Clear');
    const matchCount = document.getElementById('pp30MatchCount');
    const lang = '<?= $lang ?>';
    if (!input) return;

    function getBodyRows(tableId) {
        const t = document.getElementById(tableId);
        return t ? Array.from(t.querySelectorAll('tbody tr:not(.vat-empty-row)')) : [];
    }

    function filterTables() {
        const q = input.value.trim().toLowerCase();
        clearBtn.style.display = q ? 'block' : 'none';

        const outputRows = getBodyRows('outputVatTable');
        const inputRows = getBodyRows('inputVatTable');
        const allRows = outputRows.concat(inputRows);

        if (!q) {
            allRows.forEach(r => { r.classList.remove('pp30-hidden','pp30-highlight'); });
            matchCount.innerHTML = '';
            return;
        }

        let matched = 0;
        allRows.forEach(r => {
            const text = r.textContent.toLowerCase();
            if (text.includes(q)) {
                r.classList.remove('pp30-hidden');
                r.classList.add('pp30-highlight');
                matched++;
            } else {
                r.classList.add('pp30-hidden');
                r.classList.remove('pp30-highlight');
            }
        });

        const label = lang === 'th' ? 'พบ' : 'Found';
        const suffix = lang === 'th' ? 'รายการ' : 'records';
        matchCount.innerHTML = '<strong>' + matched + '</strong> / ' + allRows.length + ' ' + suffix;
    }

    input.addEventListener('input', filterTables);
    clearBtn.addEventListener('click', function() {
        input.value = '';
        filterTables();
        input.focus();
    });
})();
</script>
