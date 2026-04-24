<?php
$pageTitle = 'Purchase Orders';

/**
 * PO List View — Legacy Modern Design
 * Variables: $items_out, $items_in, $total_out, $total_in, $total_records, $pagination, $filters, $per_page, $query_params
 */
require_once __DIR__ . '/../../../inc/pagination.php';
$date_preset = $filters['date_preset'] ?? '';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .list-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1400px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(16,185,129,0.25); }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .subtitle { margin-top: 6px; opacity: 0.9; font-size: 14px; }
    .filter-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }
    .filter-card .filter-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .filter-card .filter-header i { color: #10b981; margin-right: 8px; }
    .filter-card .filter-body { padding: 20px; }
    .filter-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; min-height: 42px; transition: border-color 0.2s, box-shadow 0.2s; }
    .filter-card .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); outline: none; }
    .filter-card .btn-primary { background: #10b981; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 500; }
    .filter-card .btn-primary:hover { background: #059669; }
    .filter-card .btn-default { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 20px; color: #6b7280; }
    .filter-card .btn-default:hover { background: #f9fafb; }
    .summary-cards { display: flex; gap: 16px; margin-bottom: 24px; }
    .summary-card { flex: 1; background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; display: flex; flex-direction: column; align-items: center; }
    .summary-card .number { font-size: 28px; font-weight: 700; }
    .summary-card .label-text { font-size: 13px; color: #6b7280; margin-top: 4px; }
    .summary-card.out { border-left: 4px solid #10b981; }
    .summary-card.in { border-left: 4px solid #3b82f6; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }
    .data-card .section-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .data-card .section-header .badge { background: #10b981; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-left: 8px; }
    .data-card .section-header.in-header .badge { background: #3b82f6; }
    .data-card .table { margin: 0; font-size: 13px; }
    .data-card .table thead th { background: #f9fafb; color: #1f2937; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; border-top: none; }
    .data-card .table tbody td { padding: 14px 16px; border-color: #e5e7eb; vertical-align: middle; color: #1f2937; }
    .data-card .table tbody tr:hover { background: rgba(16,185,129,0.03); }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(16,185,129,0.1); color: #10b981; text-decoration: none; transition: all 0.2s; margin-right: 4px; }
    .action-btn:hover { background: #10b981; color: white; text-decoration: none; }
    .action-btn.primary { background: rgba(102,126,234,0.1); color: #667eea; }
    .action-btn.primary:hover { background: #667eea; color: white; }
    .action-btn.warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .action-btn.warning:hover { background: #f59e0b; color: white; }
    .action-btn.danger { background: rgba(239,68,68,0.1); color: #ef4444; }
    .action-btn.danger:hover { background: #ef4444; color: white; }
    .status-badge { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.cancelled { background: #fee2e2; color: #ef4444; }
    .status-badge.success { background: #d1fae5; color: #10b981; }
    .status-badge.confirmed { background: #dbeafe; color: #3b82f6; }
    .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
    .empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 16px; }
    .empty-state h4 { margin: 0 0 8px 0; color: #1f2937; }
    .record-count { color: #6b7280; font-size: 13px; font-weight: 400; }
    @media (max-width: 768px) { .summary-cards { flex-direction: column; } .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<div class="list-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder ?? 'Purchase Orders'?></h2>
        <div class="subtitle"><?=$xml->manage ?? 'Manage and track all'?> <?=$xml->purchasingorder ?? 'purchase orders'?></div>
    </div>

    <div class="filter-card">
        <div class="filter-header">
            <span><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></span>
            <span class="record-count"><?=$total_records?> <?=$xml->record ?? 'records'?></span>
        </div>
        <div class="filter-body">
            <form method="get" action="">
                <input type="hidden" name="page" value="po_list">
                <div style="margin-bottom: 16px;"><?= render_date_presets($date_preset, 'po_list') ?></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4 col-md-3" style="margin-bottom:10px">
                        <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> PO#, Name..." value="<?=e($filters['search'] ?? '')?>">
                    </div>
                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:10px">
                        <input type="date" class="form-control" name="date_from" value="<?=e($filters['date_from'] ?? '')?>">
                    </div>
                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:10px">
                        <input type="date" class="form-control" name="date_to" value="<?=e($filters['date_to'] ?? '')?>">
                    </div>
                    <div class="col-xs-6 col-sm-2 col-md-2" style="margin-bottom:10px">
                        <select name="per_page" class="form-control" onchange="this.form.submit()">
                            <?php foreach([10,20,50,100] as $pp): ?>
                            <option value="<?=$pp?>" <?=$per_page==$pp?'selected':''?>><?=$pp?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-6 col-sm-12 col-md-3" style="margin-bottom:10px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                        <a href="?page=po_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card out"><span class="number text-success"><?=$total_out?></span><span class="label-text">PO <?=$xml->out ?? 'Out'?></span></div>
        <div class="summary-card in"><span class="number text-primary"><?=$total_in?></span><span class="label-text">PO <?=$xml->in ?? 'In'?></span></div>
    </div>

    <!-- PO OUT -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->purchasingorder ?? 'PO'?> - <?=$xml->out ?? 'Out'?>
            <span class="badge"><?=$total_out?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr>
                    <th width="120"><?=$xml->pono ?? 'PO#'?></th>
                    <th width="230"><?=$xml->customer ?? 'Customer'?></th>
                    <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
                    <th width="100"><?=$xml->duedate ?? 'Due Date'?></th>
                    <th class="hidden-xs" width="90"><?=$xml->status ?? 'Status'?></th>
                    <th width="150"></th>
                </tr></thead>
                <tbody>
                <?php if(empty($items_out)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No data found'?></h4><p><?=$xml->tryadjust ?? 'Try adjusting your search or date filters'?></p></div></td></tr>
                <?php else: foreach($items_out as $row):
                    $is_cancelled = ($row['cancel'] == '1');
                    $var = decodenum($row['status']);
                    $status_class = $is_cancelled ? 'cancelled' : ($row['status']=='2' ? 'confirmed' : 'pending');
                    $pg = ($row['status'] == 2) ? 'po_deliv' : 'po_edit';
                ?>
                <tr<?=$is_cancelled?' style="opacity:0.5"':''?>>
                    <td>PO-<?=e($row['tax'])?></td>
                    <td><?=e($row['name'])?></td>
                    <td class="hidden-xs"><?=e($row['name_en'])?></td>
                    <td><?=e($row['valid_pay'])?></td>
                    <td class="hidden-xs"><span class="status-badge <?=$status_class?>"><?=$is_cancelled ? ($xml->cancel ?? 'Cancelled') : $xml->$var?></span></td>
                    <td>
                        <a href="index.php?page=po_view&id=<?=e($row['id'])?>" class="action-btn primary" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                        <?php if(!$is_cancelled): ?>
                            <?php if($row['status']=='1'): ?><a href="index.php?page=po_edit&id=<?=e($row['id'])?>" class="action-btn warning" title="<?=$xml->edit ?? 'Edit'?>"><i class="fa fa-pencil"></i></a><?php endif; ?>
                            <a href="index.php?page=<?=$pg?>&id=<?=e($row['id'])?>&action=c" class="action-btn" title="Process"><i class="fa fa-magic"></i></a>
                            <a onclick="return Conf(this)" href="index.php?page=po_store&method=D&id=<?=e($row['id'])?>&csrf_token=<?=csrf_token()?>" class="action-btn danger" title="<?=$xml->cancel ?? 'Cancel'?>"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PO IN -->
    <div class="data-card">
        <div class="section-header in-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->purchasingorder ?? 'PO'?> - <?=$xml->in ?? 'In'?>
            <span class="badge"><?=$total_in?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr>
                    <th width="120"><?=$xml->pono ?? 'PO#'?></th>
                    <th width="230"><?=$xml->vender ?? 'Vendor'?></th>
                    <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
                    <th width="100"><?=$xml->duedate ?? 'Due Date'?></th>
                    <th class="hidden-xs" width="90"><?=$xml->status ?? 'Status'?></th>
                    <th width="150"></th>
                </tr></thead>
                <tbody>
                <?php if(empty($items_in)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No data found'?></h4><p><?=$xml->tryadjust ?? 'Try adjusting your search or date filters'?></p></div></td></tr>
                <?php else: foreach($items_in as $row):
                    $is_cancelled = ($row['cancel'] == '1');
                    $var = decodenum($row['status']);
                    $status_class = $is_cancelled ? 'cancelled' : ($row['status']=='2' ? 'confirmed' : 'pending');
                    $pg = ($row['status'] == 2) ? 'po_deliv' : 'po_edit';
                ?>
                <tr<?=$is_cancelled?' style="opacity:0.5"':''?>>
                    <td>PO-<?=e($row['tax'])?></td>
                    <td><?=e($row['name_en'])?></td>
                    <td class="hidden-xs"><?=e($row['name'])?></td>
                    <td><?=e($row['valid_pay'])?></td>
                    <td class="hidden-xs"><span class="status-badge <?=$status_class?>"><?=$is_cancelled ? ($xml->cancel ?? 'Cancelled') : $xml->$var?></span></td>
                    <td>
                        <a href="index.php?page=po_view&id=<?=e($row['id'])?>" class="action-btn primary" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-dropbox"></i></a>
                        <?php if(!$is_cancelled): ?>
                            <a href="index.php?page=<?=$pg?>&id=<?=e($row['id'])?>&action=c" class="action-btn" title="Process"><i class="fa fa-magic"></i></a>
                            <a onclick="return Conf(this)" href="index.php?page=po_store&method=D&id=<?=e($row['id'])?>&csrf_token=<?=csrf_token()?>" class="action-btn danger" title="<?=$xml->cancel ?? 'Cancel'?>"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= render_pagination($pagination, '?page=po_list', $query_params) ?>
</div>
