<?php
/**
 * Voucher List View — Legacy Modern Design
 * Variables: $items, $stats, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .voucher-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1400px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(231,76,60,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
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
    .filter-card .form-control:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); outline: none; }
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
    .action-view { background: rgba(102,126,234,0.1); color: #667eea; }
    .action-view:hover { background: #667eea; color: white; text-decoration: none; }
    .action-edit { background: rgba(16,185,129,0.1); color: #10b981; }
    .action-edit:hover { background: #10b981; color: white; text-decoration: none; }
    .action-print { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .action-print:hover { background: #8b5cf6; color: white; text-decoration: none; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
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

<div class="voucher-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-credit-card"></i> <?=$xml->voucher ?? 'Voucher'?> <?=$xml->list ?? 'List'?></h2>
        <div class="header-actions">
            <a href="index.php?page=voucher_make"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Create'?></a>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card"><div class="number" style="color:#e74c3c"><?=number_format($stats['total'])?></div><div class="label"><?=$xml->total ?? 'Total'?></div></div>
        <div class="summary-card"><div class="number" style="color:#059669"><?=number_format($stats['confirmed'])?></div><div class="label"><?=$xml->confirmed ?? 'Confirmed'?></div></div>
        <div class="summary-card"><div class="number" style="color:#d97706"><?=number_format($stats['draft'])?></div><div class="label"><?=$xml->draft ?? 'Draft'?></div></div>
        <div class="summary-card"><div class="number" style="color:#dc2626"><?=number_format($stats['cancelled'])?></div><div class="label"><?=$xml->cancelled ?? 'Cancelled'?></div></div>
    </div>

    <div class="filter-card">
        <form method="get">
            <input type="hidden" name="page" value="voucher_list">
            <input type="text" name="search" class="form-control" placeholder="<?=$xml->search ?? 'Search'?>..." value="<?=e($search)?>" style="min-width:180px">
            <select name="status" class="form-control">
                <option value="">-- <?=$xml->status ?? 'Status'?> --</option>
                <option value="draft" <?=$status_filter=='draft'?'selected':''?>><?=$xml->draft ?? 'Draft'?></option>
                <option value="confirmed" <?=$status_filter=='confirmed'?'selected':''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                <option value="cancelled" <?=$status_filter=='cancelled'?'selected':''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
            </select>
            <input type="date" name="date_from" class="form-control" value="<?=e($date_from)?>">
            <input type="date" name="date_to" class="form-control" value="<?=e($date_to)?>">
            <button type="submit" class="btn btn-danger"><i class="fa fa-search"></i></button>
            <a href="index.php?page=voucher_list" class="btn btn-default"><i class="fa fa-refresh"></i></a>
        </form>
        <?php if(function_exists('render_date_presets')): ?>
        <div style="margin-top:10px"><?= render_date_presets($date_preset, 'voucher_list') ?></div>
        <?php endif; ?>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-list" style="color:#e74c3c;margin-right:8px"></i> <?=$xml->voucher ?? 'Voucher'?> (<?=number_format($total_records ?? 0)?>)</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>#</th><th><?=$xml->voucher ?? 'Voucher'?>#</th><th><?=$xml->name ?? 'Vendor'?></th><th><?=$xml->email ?? 'Email'?></th><th><?=$xml->payment ?? 'Payment'?></th><th><?=$xml->status ?? 'Status'?></th><th><?=$xml->datecreate ?? 'Date'?></th><th><?=$xml->action ?? 'Actions'?></th></tr>
                </thead>
                <tbody>
                    <?php if(!empty($items)): foreach($items as $i => $v):
                        $st = $v['status'] ?? 'draft';
                        $st_class = $st == 'confirmed' ? 'status-confirmed' : ($st == 'cancelled' ? 'status-cancelled' : 'status-draft');
                    ?>
                    <tr>
                        <td><?=$i+1?></td>
                        <td><strong><?=e($v['vou_rw'] ?? $v['id'])?></strong></td>
                        <td><?=e($v['name'] ?? '')?></td>
                        <td><?=e($v['email'] ?? '')?></td>
                        <td><?=e($v['payment_method'] ?? '')?></td>
                        <td><span class="status-badge <?=$st_class?>"><?=e($st)?></span></td>
                        <td><?=e($v['createdate'] ?? '')?></td>
                        <td>
                            <a href="index.php?page=voucher_view&id=<?=e($v['id'])?>" class="action-btn action-view" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                            <?php if($st !== 'cancelled'): ?>
                            <a href="index.php?page=voucher_make&id=<?=e($v['id'])?>" class="action-btn action-edit" title="<?=$xml->edits ?? 'Edit'?>"><i class="fa fa-pencil"></i></a>
                            <?php endif; ?>
                            <a href="index.php?page=voucher_print&id=<?=e($v['id'])?>" class="action-btn action-print" title="<?=$xml->print ?? 'Print'?>" target="_blank"><i class="fa fa-print"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox" style="font-size:28px;display:block;margin-bottom:8px"></i><?=$xml->nodata ?? 'No vouchers found'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(!empty($pagination) && function_exists('render_pagination')): ?>
    <div class="text-center"><?= render_pagination($pagination, '?page=voucher_list', $query_params) ?></div>
    <?php endif; ?>
</div>
