<?php
/**
 * Invoice Payments View — MVC version
 * Variables: $summary, $rows, $pagination, $status, $search, $queryParams
 */
$xml = $xml ?? (object)[];
$outstanding = ($summary['total_amount'] ?? 0) - ($summary['total_paid'] ?? 0);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.payments-container { font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; max-width:1400px; margin:0 auto; }
.page-header-pay { background:linear-gradient(135deg,#0ea5e9,#0284c7); color:#fff; padding:24px 28px; border-radius:16px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 20px rgba(14,165,233,.3); }
.page-header-pay h2 { margin:0; font-size:24px; font-weight:700; display:flex; align-items:center; gap:12px; }
.page-header-pay .header-actions { display:flex; gap:10px; }
.page-header-pay .btn-export { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.3); color:#fff; padding:10px 16px; border-radius:10px; text-decoration:none; font-weight:500; display:flex; align-items:center; gap:8px; transition:all .2s; }
.page-header-pay .btn-export:hover { background:rgba(255,255,255,.3); color:#fff; }
.filter-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden; }
.filter-card .filter-header { background:linear-gradient(135deg,#f8fafc,#f1f5f9); padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; color:#374151; display:flex; align-items:center; gap:10px; }
.filter-card .filter-header i { color:#0ea5e9; }
.filter-card .filter-body { padding:20px; display:flex; flex-wrap:wrap; gap:16px; align-items:center; }
.filter-card .form-control { border-radius:10px; border:1px solid #e5e7eb; height:44px; padding:10px 16px; font-size:14px; min-width:200px; }
.filter-card .form-control:focus { border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.15); }
.status-tabs { display:flex; gap:8px; }
.status-tabs .btn { border-radius:20px; padding:8px 16px; font-size:13px; font-weight:500; border:1px solid #e5e7eb; transition:all .2s; }
.status-tabs .btn.active-all { background:#0ea5e9; color:#fff; border-color:#0ea5e9; }
.status-tabs .btn.active-paid { background:#10b981; color:#fff; border-color:#10b981; }
.status-tabs .btn.active-partial { background:#f59e0b; color:#fff; border-color:#f59e0b; }
.status-tabs .btn.active-unpaid { background:#ef4444; color:#fff; border-color:#ef4444; }
.summary-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.summary-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); border:1px solid #e5e7eb; }
.summary-card .card-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; margin-bottom:12px; }
.summary-card .card-icon.total { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#2563eb; }
.summary-card .card-icon.paid { background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#16a34a; }
.summary-card .card-icon.partial { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#d97706; }
.summary-card .card-icon.unpaid { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#dc2626; }
.summary-card h3 { margin:0 0 4px; font-size:28px; font-weight:700; color:#1f2937; }
.summary-card p { margin:0; font-size:13px; color:#6b7280; }
.amount-cards { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:24px; }
.amount-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); border:1px solid #e5e7eb; }
.amount-card.outstanding { border-left:4px solid #f59e0b; }
.amount-card h4 { margin:0 0 8px; font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
.amount-card .value { font-size:24px; font-weight:700; color:#1f2937; }
.amount-card.outstanding .value { color:#d97706; }
.data-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden; }
.data-card .card-header { background:linear-gradient(135deg,#f0f9ff,#e0f2fe); padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:12px; font-weight:600; font-size:15px; color:#0369a1; }
.table-modern { margin-bottom:0; }
.table-modern thead th { background:#f8fafc; color:#374151; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.5px; padding:14px 16px; border-bottom:2px solid #e5e7eb; white-space:nowrap; }
.table-modern tbody tr { transition:background .2s; }
.table-modern tbody tr:hover { background:#f0f9ff; }
.table-modern tbody td { padding:14px 16px; vertical-align:middle; border-bottom:1px solid #f3f4f6; font-size:14px; }
.table-modern .inv-number { font-weight:600; color:#0ea5e9; }
.status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.status-badge.paid,.status-badge.success { background:#dcfce7; color:#166534; }
.status-badge.partial,.status-badge.warning { background:#fef3c7; color:#92400e; }
.status-badge.unpaid,.status-badge.danger { background:#fee2e2; color:#991b1b; }
.btn-action { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; margin-right:4px; transition:all .2s; text-decoration:none; }
.btn-action-view { background:#eff6ff; color:#2563eb; }
.btn-action-view:hover { background:#2563eb; color:#fff; }
.btn-action-pdf { background:#f3f4f6; color:#374151; }
.btn-action-pdf:hover { background:#374151; color:#fff; }
.empty-state { text-align:center; padding:60px 20px; color:#6b7280; }
.empty-state i { font-size:48px; margin-bottom:16px; color:#d1d5db; }
.progress { height:6px; border-radius:3px; background:#e5e7eb; margin-top:6px; }
.progress-bar { border-radius:3px; }
</style>
<link rel="stylesheet" href="css/master-data.css">

<div class="payments-container">
<div class="page-header-pay">
    <h2><i class="fa fa-credit-card"></i> <?=$xml->invoice ?? 'Invoice'?> <?=$xml->payment ?? 'Payments'?> <?=$xml->tracking ?? 'Tracking'?></h2>
    <div class="header-actions">
        <a href="invoice-payments-export.php?status=<?=urlencode($status)?>&search=<?=urlencode($search)?>" class="btn-export"><i class="fa fa-file-excel-o"></i> Export Excel</a>
        <button onclick="window.print();" class="btn-export"><i class="fa fa-print"></i> Print</button>
    </div>
</div>

<div class="filter-card">
    <div class="filter-header"><i class="fa fa-filter"></i> <?=$xml->filter ?? 'Filter'?></div>
    <div class="filter-body">
        <form method="get" style="display:contents;">
            <input type="hidden" name="page" value="invoice_payments">
            <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Customer..." value="<?=htmlspecialchars($search)?>">
            <div class="status-tabs">
                <a href="?page=invoice_payments&search=<?=urlencode($search)?>" class="btn <?=$status==''?'active-all':'btn-default'?>">All</a>
                <a href="?page=invoice_payments&status=paid&search=<?=urlencode($search)?>" class="btn <?=$status=='paid'?'active-paid':'btn-default'?>"><i class="fa fa-check"></i> Paid</a>
                <a href="?page=invoice_payments&status=partial&search=<?=urlencode($search)?>" class="btn <?=$status=='partial'?'active-partial':'btn-default'?>"><i class="fa fa-clock-o"></i> Partial</a>
                <a href="?page=invoice_payments&status=unpaid&search=<?=urlencode($search)?>" class="btn <?=$status=='unpaid'?'active-unpaid':'btn-default'?>"><i class="fa fa-times"></i> Unpaid</a>
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:10px;padding:10px 20px;"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=invoice_payments" class="btn btn-default" style="border-radius:10px;padding:10px 20px;"><i class="fa fa-refresh"></i></a>
        </form>
    </div>
</div>

<div class="summary-cards">
    <div class="summary-card"><div class="card-icon total"><i class="fa fa-file-text-o"></i></div><h3><?=number_format($summary['total_invoices'] ?? 0)?></h3><p>Total Invoices</p></div>
    <div class="summary-card"><div class="card-icon paid"><i class="fa fa-check"></i></div><h3><?=number_format($summary['paid_count'] ?? 0)?></h3><p>Fully Paid</p></div>
    <div class="summary-card"><div class="card-icon partial"><i class="fa fa-clock-o"></i></div><h3><?=number_format($summary['partial_count'] ?? 0)?></h3><p>Partial Payment</p></div>
    <div class="summary-card"><div class="card-icon unpaid"><i class="fa fa-times"></i></div><h3><?=number_format($summary['unpaid_count'] ?? 0)?></h3><p>Unpaid</p></div>
</div>

<div class="amount-cards">
    <div class="amount-card"><h4>Total Amount</h4><div class="value"><?=number_format($summary['total_amount'] ?? 0, 2)?> ฿</div></div>
    <div class="amount-card outstanding"><h4>Outstanding</h4><div class="value"><?=number_format($outstanding, 2)?> ฿</div></div>
</div>

<div class="data-card">
    <div class="card-header"><i class="fa fa-table"></i> <?=$xml->invoice ?? 'Invoice'?> <?=$xml->payment ?? 'Payment'?> Details</div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr>
                <th width="100">Invoice #</th>
                <th><?=$xml->customer ?? 'Customer'?></th>
                <th><?=$xml->descriptions ?? 'Descriptions'?></th>
                <th width="90"><?=$xml->date ?? 'Date'?></th>
                <th width="120" class="text-right">Outstanding</th>
                <th width="100" class="text-center"><?=$xml->status ?? 'Status'?></th>
                <th width="120"><?=$xml->action ?? 'Actions'?></th>
            </tr></thead>
            <tbody>
<?php if (!empty($rows)): foreach ($rows as $row):
    $ost = $row['total_amount'] - $row['paid_amount'];
    if ($row['total_amount'] <= 0) { $sc='default'; $st='N/A'; $si='fa-question'; }
    elseif ($row['paid_amount'] >= $row['total_amount']) { $sc='success'; $st='Paid'; $si='fa-check'; }
    elseif ($row['paid_amount'] > 0) { $sc='warning'; $st='Partial'; $si='fa-clock-o'; }
    else { $sc='danger'; $st='Unpaid'; $si='fa-times'; }
    $pct = $row['total_amount'] > 0 ? min(100, ($row['paid_amount']/$row['total_amount'])*100) : 0;
?>
                <tr>
                    <td class="inv-number"><strong>INV-<?=htmlspecialchars($row['invoice_id'])?></strong></td>
                    <td><?=htmlspecialchars($row['customer_name'] ?: $row['customer_name_th'])?></td>
                    <td><?=htmlspecialchars($row['description'])?></td>
                    <td><?=date('d/m/Y', strtotime($row['createdate']))?></td>
                    <td class="text-right" style="font-weight:600;color:<?=$ost>0?'#dc2626':'#16a34a'?>;"><?=number_format($ost,2)?></td>
                    <td class="text-center">
                        <span class="status-badge <?=$sc?>"><i class="fa <?=$si?>"></i> <?=$st?></span>
                        <?php if ($pct > 0 && $pct < 100): ?><div class="progress"><div class="progress-bar progress-bar-<?=$sc?>" style="width:<?=$pct?>%;"></div></div><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="?page=compl_view&id=<?=$row['invoice_id']?>" class="btn-action btn-action-view" title="View"><i class="fa fa-eye"></i></a>
                        <a href="inv.php?id=<?=$row['invoice_id']?>" target="_blank" class="btn-action btn-action-pdf" title="PDF"><i class="fa fa-file-text-o"></i></a>
                    </td>
                </tr>
<?php endforeach; else: ?>
                <tr><td colspan="7"><div class="empty-state"><i class="fa fa-inbox"></i><p>No invoices found matching your criteria.</p></div></td></tr>
<?php endif; ?>
            </tbody>
        </table>
        <?= render_pagination($pagination, '?page=invoice_payments', $queryParams) ?>
    </div>
</div>
</div>
