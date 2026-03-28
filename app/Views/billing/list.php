<?php
/**
 * Billing List View — Legacy Modern Design
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
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; text-decoration: none; margin: 0 2px; border: none; cursor: pointer; }
    .action-view { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .action-view:hover { background: #3b82f6; color: white; text-decoration: none; }
    .action-print { background: rgba(16,185,129,0.1); color: #10b981; }
    .action-print:hover { background: #10b981; color: white; text-decoration: none; }
    .action-create { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .action-create:hover { background: #8b5cf6; color: white; text-decoration: none; }
    .action-delete { background: rgba(239,68,68,0.1); color: #ef4444; }
    .action-delete:hover { background: #ef4444; color: white; text-decoration: none; }
    .amount-col { font-family: 'Courier New', monospace; font-weight: 600; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<?php
$filters = $filters ?? [];
$search = $filters['search'] ?? '';
$status_filter = $filters['status'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
$date_preset = $date_preset ?? ($filters['date_preset'] ?? '');
$stats = $stats ?? ['total' => 0, 'with_billing' => 0, 'without_billing' => 0, 'total_amount' => 0];
$per_page = $per_page ?? 25;
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
                    <div class="col-xs-12 col-sm-3" style="margin-bottom:12px;">
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
                    <div class="col-xs-12 col-sm-3" style="margin-bottom:12px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                        <a href="?page=billing" class="btn btn-default"><i class="fa fa-refresh"></i></a>
                        <?= render_per_page_selector($per_page) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-list" style="color:#8b5cf6;margin-right:8px"></i> Invoices & Billing (<?=number_format($total_records ?? 0)?>)</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?=$xml->invoice ?? 'Invoice'?>#</th>
                        <th><?=$xml->customer ?? 'Customer'?></th>
                        <th><?=$xml->datecreate ?? 'Date'?></th>
                        <th><?=$xml->total ?? 'Subtotal'?></th>
                        <th>VAT</th>
                        <th><?=$xml->grandtotal ?? 'Total'?></th>
                        <th>Billing</th>
                        <th><?=$xml->action ?? 'Actions'?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($items)): foreach($items as $i => $b):
                        $subtotal = floatval($b['subtotal'] ?? 0);
                        $vat_pct = floatval($b['vat'] ?? 0);
                        $dis_pct = floatval($b['discount'] ?? 0);
                        $wh_pct = floatval($b['withholding'] ?? 0);
                        $after_disc = $subtotal * (1 - $dis_pct / 100);
                        $vat_amt = $after_disc * ($vat_pct / 100);
                        $total_amt = $after_disc + $vat_amt - ($after_disc * $wh_pct / 100);
                        $has_billing = !empty($b['bil_id']);
                    ?>
                    <tr>
                        <td><?=$i+1?></td>
                        <td><strong><?=e($b['tex'] ?? $b['id'])?></strong></td>
                        <td><?=e($b['name_en'] ?? $b['name'] ?? '')?></td>
                        <td><?=e($b['createdate'] ?? '')?></td>
                        <td class="amount-col"><?=number_format($subtotal, 2)?></td>
                        <td><?=$vat_pct ? $vat_pct.'%' : '-'?></td>
                        <td class="amount-col" style="font-weight:700"><?=number_format($total_amt, 2)?></td>
                        <td>
                            <?php if($has_billing): ?>
                            <span class="billing-badge billing-yes"><i class="fa fa-check"></i> <?=e($b['bil_id'])?></span>
                            <?php else: ?>
                            <span class="billing-badge billing-no">Unbilled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!$has_billing): ?>
                            <a href="index.php?page=billing_make&inv_id=<?=e($b['id'])?>" class="action-btn action-create" title="Create Billing"><i class="fa fa-plus"></i></a>
                            <?php else: ?>                            <a href="index.php?page=billing_view&id=<?=e($b['bil_id'])?>" class="action-btn action-view" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                            <a href="index.php?page=billing_print&id=<?=e($b['bil_id'])?>" class="action-btn action-print" title="<?=$xml->print ?? 'Print'?>" target="_blank"><i class="fa fa-print"></i></a>                            <form method="post" action="index.php?page=billing_store" style="display:inline" onsubmit="return confirm('<?=$xml->confirmdelete ?? 'Delete this billing?'?>')">
                                <input type="hidden" name="method" value="D">
                                <input type="hidden" name="bil_id" value="<?=e($b['bil_id'])?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="action-btn action-delete" title="<?=$xml->delete ?? 'Delete'?>"><i class="fa fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox" style="font-size:28px;display:block;margin-bottom:8px"></i><?=$xml->nodata ?? 'No invoices found'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(!empty($pagination) && $pagination['total_pages'] > 0): ?>
    <div style="text-align:center;margin-bottom:24px">
        <div style="font-size:13px;color:#6b7280;font-weight:500;margin-bottom:8px">Showing <?=$pagination['start_record']?>-<?=$pagination['end_record']?> of <?=$pagination['total_records']?> records</div>
        <?php if($pagination['total_pages'] > 1): ?>
        <?= render_pagination($pagination, '?page=billing', $query_params) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
