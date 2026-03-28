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
    .filter-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 16px 20px; margin-bottom: 24px; }
    .filter-card form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .filter-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 8px 12px; font-size: 13px; }
    .filter-card .form-control:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); outline: none; }
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
$date_preset = $filters['date_preset'] ?? '';
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
        <form method="get">
            <input type="hidden" name="page" value="billing">
            <input type="text" name="search" class="form-control" placeholder="<?=$xml->search ?? 'Search'?>..." value="<?=e($search)?>" style="min-width:180px">
            <select name="status" class="form-control">
                <option value="">-- Billing Status --</option>
                <option value="billed" <?=$status_filter=='billed'?'selected':''?>>Billed</option>
                <option value="unbilled" <?=$status_filter=='unbilled'?'selected':''?>>Unbilled</option>
            </select>
            <input type="date" name="date_from" class="form-control" value="<?=e($date_from)?>">
            <input type="date" name="date_to" class="form-control" value="<?=e($date_to)?>">
            <button type="submit" class="btn btn-primary" style="border-radius:8px"><i class="fa fa-search"></i></button>
            <a href="index.php?page=billing" class="btn btn-default" style="border-radius:8px"><i class="fa fa-refresh"></i></a>
        </form>
        <?php if(function_exists('render_date_presets')): ?>
        <div style="margin-top:10px"><?= render_date_presets($date_preset, 'billing') ?></div>
        <?php endif; ?>
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
                            <?php else: ?>
                            <form method="post" action="index.php?page=billing_store" style="display:inline" onsubmit="return confirm('<?=$xml->confirmdelete ?? 'Delete this billing?'?>')">
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

    <?php if(!empty($pagination) && function_exists('render_pagination')): ?>
    <div class="text-center"><?= render_pagination($pagination, '?page=billing', $query_params) ?></div>
    <?php endif; ?>
</div>
