<?php
/**
 * Receipt List View — Legacy Modern Design
 * Variables: $items, $stats, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .receipt-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1400px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(5,150,105,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
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
    .filter-card .form-control:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.1); outline: none; }
    .filter-card .btn-primary { background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
    .filter-card .btn-primary:hover { box-shadow: 0 4px 12px rgba(5,150,105,0.35); }
    .date-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .date-presets .btn { border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 500; }
    .date-presets .btn.active { background: linear-gradient(135deg, #059669, #10b981); color: white; border-color: #059669; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f9fafb; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-draft { background: #fef3c7; color: #d97706; }
    .status-confirmed { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { text-decoration: none; }
    .action-view { background: rgba(102,126,234,0.1); color: #667eea; }
    .action-view:hover { background: #667eea; color: white; }
    .action-edit { background: rgba(16,185,129,0.1); color: #10b981; }
    .action-edit:hover { background: #10b981; color: white; }
    .action-print { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .action-print:hover { background: #8b5cf6; color: white; }
    .action-col { white-space: nowrap; text-align: center; }
    .pagination-footer { display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .pagination-footer .pagination-info { font-size: 13px; color: #6b7280; font-weight: 500; white-space: nowrap; }
    .pagination-footer .per-page-inline { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280; font-weight: 500; white-space: nowrap; }
    .pagination-footer .per-page-inline select { border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px 8px; font-size: 13px; background: white; cursor: pointer; }
    .pagination-footer .per-page-inline select:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 2px rgba(5,150,105,0.15); }
    .pagination-footer .pagination { margin: 0; }
    .source-badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
    .source-invoice { background: #ede9fe; color: #7c3aed; }
    .source-quotation { background: #dbeafe; color: #2563eb; }
    .source-direct { background: #f3f4f6; color: #6b7280; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .data-card { overflow-x: auto; } .pagination-footer { flex-direction: column; gap: 8px; } }
</style>

<?php
$filters = $filters ?? [];
$search = $filters['search'] ?? '';
$status_filter = $filters['status'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
$date_preset = $filters['date_preset'] ?? '';
$stats = $stats ?? ['total' => 0, 'confirmed' => 0, 'draft' => 0, 'cancelled' => 0];
$per_page = $per_page ?? 25;
$query_params = ['search' => $search, 'status' => $status_filter, 'date_from' => $date_from, 'date_to' => $date_to, 'date_preset' => $date_preset, 'per_page' => $per_page];
?>

<div class="receipt-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-file-text"></i> <?=$xml->receipt ?? 'Receipt'?> <?=$xml->list ?? 'List'?></h2>
        <div class="header-actions">
            <a href="index.php?page=receipt_make"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Create'?></a>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card"><div class="number" style="color:#059669"><?=number_format($stats['total'])?></div><div class="label"><?=$xml->total ?? 'Total'?></div></div>
        <div class="summary-card"><div class="number" style="color:#10b981"><?=number_format($stats['confirmed'])?></div><div class="label"><?=$xml->confirmed ?? 'Confirmed'?></div></div>
        <div class="summary-card"><div class="number" style="color:#d97706"><?=number_format($stats['draft'])?></div><div class="label"><?=$xml->draft ?? 'Draft'?></div></div>
        <div class="summary-card"><div class="number" style="color:#dc2626"><?=number_format($stats['cancelled'])?></div><div class="label"><?=$xml->cancelled ?? 'Cancelled'?></div></div>
    </div>

    <div class="filter-card">
        <div class="filter-header"><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></div>
        <div class="filter-body">
            <form method="get" action="">
                <input type="hidden" name="page" value="receipt_list">
                <?php if(function_exists('render_date_presets')): ?>
                <div class="date-presets"><?= render_date_presets($date_preset, 'receipt_list') ?></div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-xs-12 col-sm-4" style="margin-bottom:12px;">
                        <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> Receipt#, Name..." value="<?=e($search)?>">
                    </div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;">
                        <select name="status" class="form-control">
                            <option value=""><?=$xml->all ?? 'All'?> <?=$xml->status ?? 'Status'?></option>
                            <option value="draft" <?=$status_filter=='draft'?'selected':''?>><?=$xml->draft ?? 'Draft'?></option>
                            <option value="confirmed" <?=$status_filter=='confirmed'?'selected':''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                            <option value="cancelled" <?=$status_filter=='cancelled'?'selected':''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
                        </select>
                    </div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_from" value="<?=e($date_from)?>"></div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_to" value="<?=e($date_to)?>"></div>
                    <div class="col-xs-12 col-sm-2" style="margin-bottom:12px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                        <a href="?page=receipt_list" class="btn btn-default"><i class="fa fa-refresh"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-list" style="color:#059669;margin-right:8px"></i> <?=$xml->receipt ?? 'Receipt'?> (<?=number_format($total_records ?? 0)?>)</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?=$xml->receipt ?? 'Receipt'?>#</th>
                        <th>Source</th>
                        <th><?=$xml->name ?? 'Name'?></th>
                        <th><?=$xml->payment ?? 'Payment'?></th>
                        <th>VAT</th>
                        <th><?=$xml->status ?? 'Status'?></th>
                        <th><?=$xml->datecreate ?? 'Date'?></th>
                        <th><?=$xml->action ?? 'Actions'?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($items)): foreach($items as $i => $r): 
                        $st = $r['status'] ?? 'draft';
                        $st_class = $st == 'confirmed' ? 'status-confirmed' : ($st == 'cancelled' ? 'status-cancelled' : 'status-draft');
                        $src = $r['source_type'] ?? 'direct';
                        $src_class = $src == 'invoice' ? 'source-invoice' : ($src == 'quotation' ? 'source-quotation' : 'source-direct');
                        $vat_val = !empty($r['taxrw']) ? $r['taxrw'] : (!empty($r['po_tax']) ? $r['po_tax'] : '');
                    ?>
                    <tr>
                        <td><?=$i+1?></td>
                        <td><strong><?=e($r['rep_rw'] ?? $r['id'])?></strong></td>
                        <td><span class="source-badge <?=$src_class?>"><?=e($src)?></span></td>
                        <td><?=e($r['name'] ?? '')?></td>
                        <td><?=e($r['payment_method'] ?? '')?></td>
                        <td><?=$vat_val ? e($vat_val).'%' : '-'?></td>
                        <td><span class="status-badge <?=$st_class?>"><?=e($st)?></span></td>
                        <td><?=e($r['createdate'] ?? '')?></td>
                        <td>
                            <a href="index.php?page=receipt_view&id=<?=e($r['id'])?>" class="action-btn action-view" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                            <?php if($st !== 'cancelled'): ?>
                            <a href="index.php?page=receipt_make&id=<?=e($r['id'])?>" class="action-btn action-edit" title="<?=$xml->edits ?? 'Edit'?>"><i class="fa fa-pencil"></i></a>
                            <?php endif; ?>
                            <a href="index.php?page=receipt_print&id=<?=e($r['id'])?>" class="action-btn action-print" title="<?=$xml->print ?? 'Print'?>" target="_blank"><i class="fa fa-print"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox" style="font-size:28px;display:block;margin-bottom:8px"></i><?=$xml->nodata ?? 'No receipts found'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(!empty($pagination) && $pagination['total_pages'] > 0): ?>
    <div class="pagination-footer">
        <div class="pagination-info">Showing <?=$pagination['start_record']?>-<?=$pagination['end_record']?> of <?=$pagination['total_records']?> records</div>
        <?php if($pagination['total_pages'] > 1): ?>
        <?= render_pagination($pagination, '?page=receipt_list', $query_params) ?>
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
function changePerPage(val) {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.delete('pg');
    window.location.href = url.toString();
}
</script>
