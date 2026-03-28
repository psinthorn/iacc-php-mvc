<?php
/**
 * Billing List View — Grouped Billing Notes + Unbilled Invoices
 * Variables: $items, $stats, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .billing-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1400px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(139,92,246,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
    .summary-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 16px; text-align: center; }
    .summary-card .number { font-size: 28px; font-weight: 700; }
    .summary-card .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-top: 4px; }
    .filter-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
    .filter-card .filter-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; }
    .filter-card .filter-body { padding: 20px; }
    .filter-card .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 44px; }
    .filter-card .form-control:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); outline: none; }
    .filter-card .btn-primary { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
    .filter-card .btn-primary:hover { box-shadow: 0 4px 12px rgba(139,92,246,0.35); }
    .date-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .date-presets .btn { border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 500; }
    .date-presets .btn.active { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border-color: #7c3aed; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f9fafb; }
    .billing-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .billing-yes { background: #d1fae5; color: #059669; }
    .billing-no { background: #fef3c7; color: #d97706; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; text-decoration: none; margin: 0 1px; border: none; cursor: pointer; font-size: 12px; }
    .action-col { white-space: nowrap; text-align: center; }
    .action-view { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .action-view:hover { background: #3b82f6; color: white; text-decoration: none; }
    .action-print { background: rgba(16,185,129,0.1); color: #10b981; }
    .action-print:hover { background: #10b981; color: white; text-decoration: none; }
    .action-create { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .action-create:hover { background: #8b5cf6; color: white; text-decoration: none; }
    .action-delete { background: rgba(239,68,68,0.1); color: #ef4444; }
    .action-delete:hover { background: #ef4444; color: white; text-decoration: none; }
    .amount-col { font-family: 'Courier New', monospace; font-weight: 600; }

    /* Grouped billing row */
    .billing-group-row { cursor: pointer; background: #faf5ff; }
    .billing-group-row:hover { background: #f3e8ff !important; }
    .billing-group-row td { border-bottom: 1px solid #e9d5ff; }
    .billing-group-row .toggle-icon { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; background: rgba(139,92,246,0.15); color: #7c3aed; font-size: 12px; margin-right: 8px; transition: transform 0.2s; }
    .billing-group-row.expanded .toggle-icon { transform: rotate(90deg); }
    .billing-group-row .bn-label { font-weight: 700; color: #7c3aed; }
    .inv-count-badge { display: inline-block; background: #8b5cf6; color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-left: 8px; }

    /* Sub-invoice rows (hidden by default) */
    .billing-sub-row { display: none; }
    .billing-sub-row td { padding-left: 48px !important; background: #fefbff; font-size: 12px; border-bottom: 1px solid #f5f0ff; }
    .billing-sub-loading td { text-align: center; padding: 20px !important; color: #8b5cf6; }

    /* Pagination footer with per-page selector inline */
    .pagination-footer { display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .pagination-footer .pagination-info { font-size: 13px; color: #6b7280; font-weight: 500; white-space: nowrap; }
    .pagination-footer .per-page-inline { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280; font-weight: 500; white-space: nowrap; }
    .pagination-footer .per-page-inline select { border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px 8px; font-size: 13px; background: white; cursor: pointer; }
    .pagination-footer .per-page-inline select:focus { border-color: #8b5cf6; outline: none; box-shadow: 0 0 0 2px rgba(139,92,246,0.15); }
    .pagination-footer .pagination { margin: 0; }

    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .pagination-footer { flex-direction: column; gap: 8px; } }
</style>

<?php
$filters = $filters ?? [];
$search = $filters['search'] ?? '';
$status_filter = $filters['status'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
$date_preset = $date_preset ?? ($filters['date_preset'] ?? '');
$stats = $stats ?? ['total' => 0, 'with_billing' => 0, 'without_billing' => 0, 'total_amount' => 0];
$per_page = $per_page ?? 20;
$query_params = ['search' => $search, 'status' => $status_filter, 'date_from' => $date_from, 'date_to' => $date_to, 'date_preset' => $date_preset, 'per_page' => $per_page];
?>

<div class="billing-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-file-text-o"></i> <?=$xml->billing ?? 'Billing'?> <?=$xml->list ?? 'List'?></h2>
        <div class="header-actions">
            <!-- Billing is created from invoice, no direct create -->
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card"><div class="number" style="color:#8b5cf6"><?=number_format($stats['total'])?></div><div class="label"><?=$xml->total ?? 'Total'?> Invoices</div></div>
        <div class="summary-card"><div class="number" style="color:#059669"><?=number_format($stats['with_billing'])?></div><div class="label">Billed</div></div>
        <div class="summary-card"><div class="number" style="color:#d97706"><?=number_format($stats['without_billing'])?></div><div class="label">Unbilled</div></div>
        <div class="summary-card"><div class="number" style="color:#1f2937"><?=number_format(floatval($stats['total_amount']), 2)?></div><div class="label"><?=$xml->total ?? 'Total'?> <?=$xml->price ?? 'Amount'?></div></div>
    </div>

    <div class="filter-card">
        <div class="filter-header"><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></div>
        <div class="filter-body">
            <form method="get" action="">
                <input type="hidden" name="page" value="billing">
                <div class="date-presets"><?= render_date_presets($date_preset, 'billing') ?></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4" style="margin-bottom:12px;">
                        <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Name..." value="<?=htmlspecialchars($search)?>">
                    </div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;">
                        <select name="status" class="form-control">
                            <option value=""><?=$xml->all ?? 'All'?> Status</option>
                            <option value="billed" <?=$status_filter=='billed'?'selected':''?>>Billed</option>
                            <option value="unbilled" <?=$status_filter=='unbilled'?'selected':''?>>Unbilled</option>
                        </select>
                    </div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>"></div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>"></div>
                    <div class="col-xs-12 col-sm-2" style="margin-bottom:12px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                        <a href="?page=billing" class="btn btn-default"><i class="fa fa-refresh"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-list" style="color:#8b5cf6;margin-right:8px"></i> Billing Notes & Invoices (<?=number_format($total_records ?? 0)?>)</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px">#</th>
                        <th style="width:17%">No. / <?=$xml->invoice ?? 'Invoice'?>#</th>
                        <th style="width:20%"><?=$xml->customer ?? 'Customer'?></th>
                        <th style="width:100px"><?=$xml->datecreate ?? 'Date'?></th>
                        <th style="text-align:right;width:120px"><?=$xml->grandtotal ?? 'Amount'?></th>
                        <th style="width:75px">Status</th>
                        <th style="width:130px;text-align:center"><?=$xml->action ?? 'Actions'?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($items)): $rowNum = ($pagination['offset'] ?? 0) + 1; foreach($items as $b):
                        $isBilling = ($b['row_type'] === 'billing');
                    ?>
                    <?php if($isBilling): ?>
                    <!-- Billing Note Group Row (expandable) -->
                    <tr class="billing-group-row" data-bil-id="<?=e($b['bil_id'])?>" onclick="toggleBillingGroup(this, <?=e($b['bil_id'])?>)">
                        <td><?=$rowNum++?></td>
                        <td>
                            <span class="toggle-icon"><i class="fa fa-chevron-right"></i></span>
                            <span class="bn-label"><?=e($b['display_id'])?></span>
                            <span class="inv-count-badge"><?=e($b['inv_count'])?> invoice<?=$b['inv_count'] > 1 ? 's' : ''?></span>
                        </td>
                        <td><?=e($b['customer_name'] ?? '')?></td>
                        <td><?=e($b['display_date'] ?? '')?></td>
                        <td class="amount-col" style="text-align:right;font-weight:700;color:#7c3aed">฿<?=number_format(floatval($b['total_amount']), 2)?></td>
                        <td><span class="billing-badge billing-yes"><i class="fa fa-check"></i> Billed</span></td>
                        <td class="action-col" onclick="event.stopPropagation()">
                            <a href="index.php?page=billing_view&id=<?=e($b['bil_id'])?>" class="action-btn action-view" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                            <a href="index.php?page=billing_print&id=<?=e($b['bil_id'])?>" class="action-btn action-print" title="<?=$xml->print ?? 'Print'?>" target="_blank"><i class="fa fa-print"></i></a>
                            <form method="post" action="index.php?page=billing_store" style="display:inline" onsubmit="return confirm('<?=$xml->confirmdelete ?? 'Delete this billing note and unlink all invoices?'?>')">
                                <input type="hidden" name="method" value="D">
                                <input type="hidden" name="bil_id" value="<?=e($b['bil_id'])?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="action-btn action-delete" title="<?=$xml->delete ?? 'Delete'?>"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <!-- Sub-invoice placeholder (loaded via AJAX on expand) -->
                    <tr class="billing-sub-row billing-sub-loading" data-parent="<?=e($b['bil_id'])?>">
                        <td colspan="7"><i class="fa fa-spinner fa-spin"></i> Loading invoices...</td>
                    </tr>
                    <?php else: ?>
                    <!-- Unbilled Invoice Row -->
                    <tr>
                        <td><?=$rowNum++?></td>
                        <td><strong><?=e($b['display_id'] ?? $b['tex'] ?? '')?></strong></td>
                        <td><?=e($b['customer_name'] ?? '')?></td>
                        <td><?=e($b['display_date'] ?? '')?></td>
                        <td class="amount-col" style="text-align:right">
                            <?php
                            $sub = floatval($b['subtotal'] ?? 0);
                            $dpct = floatval($b['discount_pct'] ?? 0);
                            $after = $sub * (1 - $dpct / 100);
                            $vatAmt = $after * (floatval($b['vat'] ?? 0) / 100);
                            $whAmt = $after * (floatval($b['withholding'] ?? 0) / 100);
                            $tot = $after + $vatAmt - $whAmt;
                            ?>
                            ฿<?=number_format($tot, 2)?>
                        </td>
                        <td><span class="billing-badge billing-no">Unbilled</span></td>
                        <td class="action-col">
                            <a href="index.php?page=billing_make&inv_id=<?=e($b['tex'] ?? $b['display_id'])?>" class="action-btn action-create" title="Create Billing"><i class="fa fa-plus"></i></a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox" style="font-size:28px;display:block;margin-bottom:8px"></i><?=$xml->nodata ?? 'No data found'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(!empty($pagination) && $pagination['total_pages'] > 0): ?>
    <div class="pagination-footer">
        <div class="pagination-info">Showing <?=$pagination['start_record']?>-<?=$pagination['end_record']?> of <?=$pagination['total_records']?> records</div>
        <?php if($pagination['total_pages'] > 1): ?>
        <?= render_pagination($pagination, '?page=billing', $query_params) ?>
        <?php endif; ?>
        <div class="per-page-inline">
            <span>Show</span>
            <select onchange="changePerPage(this.value)">
                <?php foreach([10, 20, 50, 100] as $opt): ?>
                <option value="<?=$opt?>" <?=$per_page==$opt?'selected':''?>><?=$opt?></option>
                <?php endforeach; ?>
            </select>
            <span>per page</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
var billingInvoiceCache = {};

function toggleBillingGroup(row, bilId) {
    var isExpanded = row.classList.contains('expanded');
    var subRows = document.querySelectorAll('.billing-sub-row[data-parent="' + bilId + '"]');

    if (isExpanded) {
        row.classList.remove('expanded');
        for (var i = 0; i < subRows.length; i++) subRows[i].style.display = 'none';
    } else {
        row.classList.add('expanded');
        if (billingInvoiceCache[bilId]) {
            renderSubRows(row, bilId, billingInvoiceCache[bilId]);
        } else {
            var loadingRow = document.querySelector('.billing-sub-loading[data-parent="' + bilId + '"]');
            if (loadingRow) loadingRow.style.display = 'table-row';
            fetch('index.php?page=billing_invoices_json&bil_id=' + bilId)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    billingInvoiceCache[bilId] = data;
                    renderSubRows(row, bilId, data);
                })
                .catch(function() {
                    if (loadingRow) loadingRow.querySelector('td').innerHTML = '<span style="color:#ef4444"><i class="fa fa-exclamation-triangle"></i> Failed to load</span>';
                });
        }
    }
}

function renderSubRows(parentRow, bilId, invoices) {
    var existing = document.querySelectorAll('.billing-sub-row[data-parent="' + bilId + '"]');
    for (var i = 0; i < existing.length; i++) existing[i].remove();

    var ref = parentRow;
    for (var j = 0; j < invoices.length; j++) {
        var inv = invoices[j];
        var amt = inv.amount ? parseFloat(inv.amount) : 0;
        var amtStr = amt.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

        var tr = document.createElement('tr');
        tr.className = 'billing-sub-row';
        tr.setAttribute('data-parent', bilId);
        tr.style.display = 'table-row';
        tr.innerHTML = '<td style="color:#9ca3af;text-align:center">' + (j+1) + '</td>'
            + '<td style="padding-left:48px !important"><i class="fa fa-file-text-o" style="color:#8b5cf6;margin-right:6px"></i>' + escHtml(inv.po_number || inv.inv_no || '') + '</td>'
            + '<td>' + escHtml(inv.pr_description || inv.po_name || '') + '</td>'
            + '<td>' + escHtml(inv.invoice_date || '') + '</td>'
            + '<td class="amount-col" style="text-align:right">฿' + amtStr + '</td>'
            + '<td><span style="color:#8b5cf6;font-size:11px"><i class="fa fa-link"></i> Linked</span></td>'
            + '<td></td>';
        ref.parentNode.insertBefore(tr, ref.nextSibling);
        ref = tr;
    }
    if (invoices.length === 0) {
        var tr = document.createElement('tr');
        tr.className = 'billing-sub-row';
        tr.setAttribute('data-parent', bilId);
        tr.style.display = 'table-row';
        tr.innerHTML = '<td colspan="7" style="text-align:center;padding:16px;color:#9ca3af">No invoices linked</td>';
        ref.parentNode.insertBefore(tr, ref.nextSibling);
    }
}

function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function changePerPage(val) {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.delete('pg');
    window.location.href = url.toString();
}
</script>
