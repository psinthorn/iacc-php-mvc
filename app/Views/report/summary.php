<?php
/**
 * Business Summary Report View — MVC version
 * Variables: $reportData, $totals, $period, $periodLabel, $sortBy, $sortDir, $noCompany
 */
$xml = $xml ?? (object)[];

if (!empty($noCompany)) {
    echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Please select a company to view reports.</div>';
    return;
}

$baseUrl = '?page=report&period=' . urlencode($period);

function sortLink2($col, $label, $curSort, $curDir, $base) {
    $newDir = ($curSort == $col && $curDir == 'desc') ? 'asc' : 'desc';
    $icon = ($curSort == $col)
        ? ($curDir == 'asc' ? ' <i class="fa fa-sort-asc"></i>' : ' <i class="fa fa-sort-desc"></i>')
        : ' <i class="fa fa-sort" style="opacity:.3;"></i>';
    return '<a href="'.$base.'&sort='.$col.'&dir='.$newDir.'" class="sort-link">'.$label.$icon.'</a>';
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.report-container { font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; max-width:1400px; margin:0 auto; padding:0 20px; }
.page-header-rep { background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; padding:24px 28px; border-radius:16px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 20px rgba(99,102,241,.3); }
.page-header-rep h2 { margin:0; font-size:24px; font-weight:700; display:flex; align-items:center; gap:12px; }
.page-header-rep .header-actions { display:flex; gap:10px; }
.page-header-rep .btn-export { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.3); color:#fff; padding:10px 16px; border-radius:10px; text-decoration:none; font-weight:500; display:flex; align-items:center; gap:8px; transition:all .2s; }
.page-header-rep .btn-export:hover { background:rgba(255,255,255,.3); color:#fff; }
.filter-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden; }
.filter-card .filter-header { background:linear-gradient(135deg,#f8fafc,#f1f5f9); padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; color:#374151; display:flex; align-items:center; gap:10px; }
.filter-card .filter-body { padding:20px; display:flex; flex-wrap:wrap; gap:16px; align-items:center; justify-content:space-between; }
.period-tabs { display:flex; gap:8px; }
.period-tabs .btn { border-radius:20px; padding:8px 16px; font-size:13px; font-weight:500; border:1px solid #e5e7eb; background:#fff; color:#374151; }
.period-tabs .btn.active { background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; border-color:#4f46e5; }
.period-tabs .btn:hover:not(.active) { background:#f3f4f6; }
.period-label { background:#eef2ff; color:#4338ca; padding:8px 16px; border-radius:20px; font-size:14px; font-weight:500; }
.summary-cards { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; margin-bottom:24px; }
@media(max-width:992px){ .summary-cards{grid-template-columns:repeat(3,1fr);} }
@media(max-width:576px){ .summary-cards{grid-template-columns:repeat(2,1fr);} .report-container{padding:0 12px;} }
.summary-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); border:1px solid #e5e7eb; text-align:center; }
.summary-card .icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; margin:0 auto 12px; }
.summary-card .icon.pr { background:#fef3c7; color:#d97706; }
.summary-card .icon.qa { background:#dbeafe; color:#2563eb; }
.summary-card .icon.po { background:#dcfce7; color:#16a34a; }
.summary-card .icon.iv { background:#fce7f3; color:#db2777; }
.summary-card .icon.tx { background:#e0e7ff; color:#4338ca; }
.summary-card h3 { margin:0 0 4px; font-size:28px; font-weight:700; color:#1f2937; }
.summary-card p { margin:0; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
.data-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden; }
.data-card .card-header { background:linear-gradient(135deg,#eef2ff,#e0e7ff); padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:12px; font-weight:600; font-size:15px; color:#3730a3; }
.table-modern { margin-bottom:0; }
.table-modern thead th { background:#f8fafc; color:#374151; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.5px; padding:14px 16px; border-bottom:2px solid #e5e7eb; }
.table-modern tbody tr { transition:background .2s; }
.table-modern tbody tr:hover { background:#eef2ff; }
.table-modern tbody td,.table-modern tbody th { padding:14px 16px; vertical-align:middle; border-bottom:1px solid #f3f4f6; font-size:14px; }
.table-modern tbody th { font-weight:500; color:#1f2937; }
.table-modern tfoot { background:linear-gradient(135deg,#f0fdf4,#dcfce7); }
.table-modern tfoot th,.table-modern tfoot td { padding:14px 16px; font-weight:700; color:#166534; }
.sort-link { color:#374151; text-decoration:none; display:flex; align-items:center; gap:6px; }
.sort-link:hover { color:#4f46e5; }
</style>

<div class="report-container">
<div class="page-header-rep">
    <h2><i class="fa fa-bar-chart-o"></i> <?=$xml->report ?? 'Report'?></h2>
    <div class="header-actions">
        <a href="index.php?page=export_report&period=<?=$period?>&sort=<?=$sortBy?>&dir=<?=$sortDir?>" class="btn-export"><i class="fa fa-file-excel-o"></i> Excel</a>
        <button onclick="window.print();" class="btn-export"><i class="fa fa-print"></i> Print</button>
    </div>
</div>

<div class="filter-card">
    <div class="filter-header"><i class="fa fa-filter"></i> <?=$xml->filter ?? 'Filter'?></div>
    <div class="filter-body">
        <div class="period-tabs">
            <?php foreach (['today'=>'Today','week'=>'7 Days','month'=>'30 Days','year'=>'This Year','all'=>'All Time'] as $k=>$v): ?>
            <a href="?page=report&period=<?=$k?>&sort=<?=$sortBy?>&dir=<?=$sortDir?>" class="btn <?=$period==$k?'active':''?>"><?=$v?></a>
            <?php endforeach; ?>
        </div>
        <span class="period-label"><i class="fa fa-calendar"></i> <?=$periodLabel?></span>
    </div>
</div>

<div class="summary-cards">
    <div class="summary-card"><div class="icon pr"><i class="fa fa-file-text-o"></i></div><h3><?=$totals['prs']?></h3><p><?=$xml->purchasingrequest ?? 'PR'?></p></div>
    <div class="summary-card"><div class="icon qa"><i class="fa fa-list-alt"></i></div><h3><?=$totals['qas']?></h3><p><?=$xml->quotation ?? 'QA'?></p></div>
    <div class="summary-card"><div class="icon po"><i class="fa fa-shopping-cart"></i></div><h3><?=$totals['pos']?></h3><p><?=$xml->purchasingorder ?? 'PO'?></p></div>
    <div class="summary-card"><div class="icon iv"><i class="fa fa-file-text"></i></div><h3><?=$totals['ivs']?></h3><p><?=$xml->invoice ?? 'Invoice'?></p></div>
    <div class="summary-card"><div class="icon tx"><i class="fa fa-check-circle"></i></div><h3><?=$totals['txs']?></h3><p><?=$xml->taxinvoice ?? 'Tax Invoice'?></p></div>
</div>

<div class="data-card">
    <div class="card-header"><i class="fa fa-table"></i> Customer Transaction Summary</div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr>
                <th><?= sortLink2('name','Customer',$sortBy,$sortDir,$baseUrl) ?></th>
                <th class="text-center"><?= sortLink2('pr',$xml->purchasingrequest ?? 'PR',$sortBy,$sortDir,$baseUrl) ?></th>
                <th class="text-center"><?= sortLink2('qa',$xml->quotation ?? 'QA',$sortBy,$sortDir,$baseUrl) ?></th>
                <th class="text-center"><?= sortLink2('po',$xml->purchasingorder ?? 'PO',$sortBy,$sortDir,$baseUrl) ?></th>
                <th class="text-center"><?= sortLink2('iv',$xml->invoice ?? 'Invoice',$sortBy,$sortDir,$baseUrl) ?></th>
                <th class="text-center"><?= sortLink2('tx',$xml->taxinvoice ?? 'Tax Invoice',$sortBy,$sortDir,$baseUrl) ?></th>
            </tr></thead>
            <tbody>
<?php foreach ($reportData as $row): ?>
                <tr>
                    <th><?=htmlspecialchars($row['name'])?></th>
                    <td class="text-center"><?=$row['pr']?></td>
                    <td class="text-center"><?=$row['qa']?></td>
                    <td class="text-center"><?=$row['po']?></td>
                    <td class="text-center"><?=$row['iv']?></td>
                    <td class="text-center"><?=$row['tx']?></td>
                </tr>
<?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <th style="text-align:right;"><?=$xml->summary ?? 'Summary'?></th>
                <td class="text-center"><?=$totals['prs']?></td>
                <td class="text-center"><?=$totals['qas']?></td>
                <td class="text-center"><?=$totals['pos']?></td>
                <td class="text-center"><?=$totals['ivs']?></td>
                <td class="text-center"><?=$totals['txs']?></td>
            </tr></tfoot>
        </table>
    </div>
</div>

<?php if (empty($reportData)): ?>
<div class="alert alert-info" style="border-radius:12px;border:none;background:#eef2ff;color:#4338ca;">
    <i class="fa fa-info-circle"></i> No transactions found for the selected period.
</div>
<?php endif; ?>
</div>
