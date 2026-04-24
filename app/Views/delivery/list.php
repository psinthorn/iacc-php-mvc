<?php
$pageTitle = 'Delivery';

/**
 * Delivery List View — Legacy Modern Design
 * Variables: $items_out, $items_in, $sendouts_out, $sendouts_in, $total_out, $total_in, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../../inc/pagination.php';
$date_preset = $filters['date_preset'] ?? '';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .list-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1400px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(102,126,234,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .subtitle { margin-top: 6px; opacity: 0.9; font-size: 14px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .filter-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }
    .filter-card .filter-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .filter-card .filter-header i { color: #667eea; margin-right: 8px; }
    .filter-card .filter-body { padding: 20px; }
    .filter-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; min-height: 42px; }
    .filter-card .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); outline: none; }
    .filter-card .btn-primary { background: #667eea; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 500; }
    .filter-card .btn-primary:hover { background: #5a6fd6; }
    .filter-card .btn-default { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 20px; color: #6b7280; }
    .summary-cards { display: flex; gap: 16px; margin-bottom: 24px; }
    .summary-card { flex: 1; background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; display: flex; flex-direction: column; align-items: center; }
    .summary-card .number { font-size: 28px; font-weight: 700; }
    .summary-card .label-text { font-size: 13px; color: #6b7280; margin-top: 4px; }
    .summary-card.out { border-left: 4px solid #667eea; }
    .summary-card.in { border-left: 4px solid #3b82f6; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }
    .data-card .section-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .data-card .section-header .badge { background: #667eea; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-left: 8px; }
    .data-card .section-header.in-header .badge { background: #3b82f6; }
    .data-card .table { margin: 0; font-size: 13px; }
    .data-card .table thead th { background: #f9fafb; color: #1f2937; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; border-top: none; }
    .data-card .table tbody td { padding: 14px 16px; border-color: #e5e7eb; vertical-align: middle; color: #1f2937; }
    .data-card .table tbody tr:hover { background: rgba(102,126,234,0.03); }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(102,126,234,0.1); color: #667eea; text-decoration: none; transition: all 0.2s; margin-right: 4px; }
    .action-btn:hover { background: #667eea; color: white; text-decoration: none; }
    .action-btn.success { background: rgba(16,185,129,0.1); color: #10b981; }
    .action-btn.success:hover { background: #10b981; color: white; }
    .action-btn.warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .action-btn.warning:hover { background: #f59e0b; color: white; }
    .action-btn.danger { background: rgba(239,68,68,0.1); color: #ef4444; }
    .action-btn.danger:hover { background: #ef4444; color: white; }
    .dn-number { font-weight: 600; color: #667eea; }
    .dn-number small { color: #9ca3af; font-weight: 400; }
    .status-badge { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-badge.active { background: #fef3c7; color: #d97706; }
    .status-badge.success { background: #d1fae5; color: #10b981; }
    .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
    .empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 16px; }
    .empty-state h4 { margin: 0 0 8px 0; color: #1f2937; }
    .record-count { color: #6b7280; font-size: 13px; font-weight: 400; }
    @media (max-width: 768px) { .summary-cards { flex-direction: column; } .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<div class="list-wrapper">
    <div class="page-header">
        <div>
            <h2><i class="fa fa-truck"></i> <?=$xml->deliver ?? 'Delivery Notes'?></h2>
            <div class="subtitle"><?=$xml->manage ?? 'Manage'?> <?=$xml->deliver ?? 'delivery notes'?></div>
        </div>
        <div class="header-actions">
            <a href="index.php?page=deliv_make"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Create'?> DN</a>
        </div>
    </div>

    <div class="filter-card">
        <div class="filter-header">
            <span><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></span>
            <span class="record-count"><?=$total_records?> <?=$xml->record ?? 'records'?></span>
        </div>
        <div class="filter-body">
            <form method="get" action="">
                <input type="hidden" name="page" value="deliv_list">
                <div class="row">
                    <div class="col-xs-12 col-sm-4 col-md-4" style="margin-bottom:10px">
                        <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?>..." value="<?=e($filters['search'] ?? '')?>">
                    </div>
                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:10px">
                        <input type="date" class="form-control" name="date_from" value="<?=e($filters['date_from'] ?? '')?>">
                    </div>
                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:10px">
                        <input type="date" class="form-control" name="date_to" value="<?=e($filters['date_to'] ?? '')?>">
                    </div>
                    <div class="col-xs-12 col-sm-2 col-md-4" style="margin-bottom:10px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                        <a href="?page=deliv_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card out"><span class="number" style="color:#667eea"><?=$total_out + count($sendouts_out ?? [])?></span><span class="label-text">DN <?=$xml->out ?? 'Out'?></span></div>
        <div class="summary-card in"><span class="number text-primary"><?=$total_in + count($sendouts_in ?? [])?></span><span class="label-text">DN <?=$xml->in ?? 'In'?></span></div>
    </div>

    <!-- DN OUT -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->deliver ?? 'Delivery Note'?> - <?=$xml->out ?? 'Out'?>
            <span class="badge"><?=$total_out + count($sendouts_out ?? [])?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr>
                    <th>DN#</th><th><?=$xml->customer ?? 'Customer'?></th><th class="hidden-xs"><?=$xml->description ?? 'Description'?></th>
                    <th><?=$xml->duedate ?? 'Due Date'?></th><th><?=$xml->deliverydate ?? 'Delivery Date'?></th><th></th>
                </tr></thead>
                <tbody>
                <?php
                $hasData = false;
                if(!empty($items_out)): $hasData = true;
                    foreach($items_out as $row): ?>
                <tr>
                    <td><span class="dn-number">DN-<?=str_pad($row['id'], 8, '0', STR_PAD_LEFT)?></span></td>
                    <td><?=e($row['name_en'])?></td>
                    <td class="hidden-xs"><?=e($row['name'])?></td>
                    <td><?=e($row['valid_pay'])?></td>
                    <td><?=e($row['deliver_date'])?></td>
                    <td>
                        <a href="index.php?page=deliv_view&id=<?=$row['id']?>" class="action-btn" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                        <a href="index.php?page=deliv_print&id=<?=$row['id']?>" class="action-btn success" title="Print DN" target="_blank"><i class="fa fa-print"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif;
                if(!empty($sendouts_out)): $hasData = true;
                    foreach($sendouts_out as $row): ?>
                <tr>
                    <td><span class="dn-number">DN-<?=str_pad($row['deliv_id'], 8, '0', STR_PAD_LEFT)?> <small>(make)</small></span></td>
                    <td><?=e($row['name_en'])?></td>
                    <td class="hidden-xs"><?=e($row['description'])?></td>
                    <td>-</td>
                    <td><?=e($row['deliver_date'])?></td>
                    <td>
                        <a href="index.php?page=deliv_view&id=<?=$row['deliv_id']?>&modep=ad" class="action-btn" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                        <a href="index.php?page=deliv_edit&id=<?=$row['deliv_id']?>&modep=ad" class="action-btn warning" title="<?=$xml->edit ?? 'Edit'?>"><i class="fa fa-pencil"></i></a>
                        <a href="index.php?page=deliv_print&id=<?=$row['deliv_id']?>&modep=ad" class="action-btn success" title="Print" target="_blank"><i class="fa fa-print"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif;
                if(!$hasData): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No data found'?></h4></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DN IN -->
    <div class="data-card">
        <div class="section-header in-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->deliver ?? 'Delivery Note'?> - <?=$xml->in ?? 'In'?>
            <span class="badge"><?=$total_in + count($sendouts_in ?? [])?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr>
                    <th>DN#</th><th><?=$xml->vender ?? 'Vendor'?></th><th class="hidden-xs"><?=$xml->description ?? 'Description'?></th>
                    <th><?=$xml->duedate ?? 'Due Date'?></th><th><?=$xml->deliverydate ?? 'Delivery Date'?></th><th></th>
                </tr></thead>
                <tbody>
                <?php
                $hasDataIn = false;
                if(!empty($items_in)): $hasDataIn = true;
                    foreach($items_in as $row): ?>
                <tr>
                    <td><span class="dn-number">DN-<?=str_pad($row['id'], 8, '0', STR_PAD_LEFT)?></span></td>
                    <td><?=e($row['name_en'])?></td>
                    <td class="hidden-xs"><?=e($row['name'])?></td>
                    <td><?=e($row['valid_pay'])?></td>
                    <td><?=e($row['deliver_date'])?></td>
                    <td>
                        <a href="index.php?page=deliv_view&id=<?=$row['id']?>" class="action-btn success" title="<?=$xml->receive ?? 'Receive'?>"><i class="fa fa-dropbox"></i></a>
                        <a href="index.php?page=deliv_print&id=<?=$row['id']?>" class="action-btn" title="Print" target="_blank"><i class="fa fa-print"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif;
                if(!empty($sendouts_in)): $hasDataIn = true;
                    foreach($sendouts_in as $row): ?>
                <tr>
                    <td><span class="dn-number">DN-<?=str_pad($row['deliv_id'], 8, '0', STR_PAD_LEFT)?></span></td>
                    <td><?=e($row['name_en'])?></td>
                    <td class="hidden-xs"><?=e($row['description'])?></td>
                    <td>-</td>
                    <td><?=e($row['deliver_date'])?></td>
                    <td>
                        <a href="index.php?page=deliv_view&id=<?=$row['deliv_id']?>&modep=ad" class="action-btn success" title="<?=$xml->receive ?? 'Receive'?>"><i class="fa fa-dropbox"></i></a>
                    </td>
                </tr>
                <?php endforeach; endif;
                if(!$hasDataIn): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No data found'?></h4></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= render_pagination($pagination, '?page=deliv_list', $query_params ?? []) ?>
</div>
