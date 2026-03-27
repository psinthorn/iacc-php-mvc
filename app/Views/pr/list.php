<?php
/**
 * PR List View — Modern UX/UI (from legacy pr-list.php)
 * Variables: $items_out, $items_in, $total_out, $total_in, $total_records, $pagination, $filters, $per_page, $query_params
 */
require_once __DIR__ . '/../../../inc/pagination.php';
global $xml;
$status = $filters['status'] ?? '';
$statusClasses = ['0'=>'pending','1'=>'quotation','2'=>'confirmed','3'=>'delivered','4'=>'invoiced','5'=>'success'];
?>

<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .pr-list-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header-pr {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .page-header-pr h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header-pr .subtitle {
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .page-header-pr .header-actions {
        display: flex;
        gap: 8px;
    }
    
    .page-header-pr .btn-create {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .page-header-pr .btn-create:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
    }
    
    .filter-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .filter-card .filter-header {
        background: #f9fafb;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .filter-card .filter-header i {
        color: #667eea;
        margin-right: 8px;
    }
    
    .filter-card .filter-body {
        padding: 20px;
    }
    
    .filter-card .form-control {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 10px 14px;
        font-size: 14px;
        min-height: 42px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .filter-card .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .filter-card .btn-primary {
        background: #667eea;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .filter-card .btn-primary:hover {
        background: #5a6fd6;
        transform: translateY(-1px);
    }
    
    .filter-card .btn-default {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 20px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .filter-card .btn-default:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .data-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .data-card .section-header {
        background: #f9fafb;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .data-card .table {
        margin: 0;
        font-size: 13px;
    }
    
    .data-card .table thead th {
        background: #f9fafb;
        color: #1f2937;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 2px solid #e5e7eb;
        border-top: none;
    }
    
    .data-card .table tbody td {
        padding: 14px 16px;
        border-color: #e5e7eb;
        vertical-align: middle;
        color: #1f2937;
    }
    
    .data-card .table tbody tr:hover {
        background: rgba(102, 126, 234, 0.03);
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        text-decoration: none;
        transition: all 0.2s;
        margin-right: 4px;
    }
    
    .action-btn:hover {
        background: #667eea;
        color: white;
        text-decoration: none;
    }
    
    .action-btn.info {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    
    .action-btn.info:hover {
        background: #3b82f6;
        color: white;
    }
    
    .action-btn.danger {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    
    .action-btn.danger:hover {
        background: #ef4444;
        color: white;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.quotation { background: #e0e7ff; color: #4f46e5; }
    .status-badge.confirmed { background: #dbeafe; color: #2563eb; }
    .status-badge.delivered { background: #d1fae5; color: #059669; }
    .status-badge.invoiced { background: #fee2e2; color: #dc2626; }
    .status-badge.success { background: #d1fae5; color: #10b981; }
    
    .pr-number {
        font-family: 'SF Mono', 'Fira Code', monospace;
        font-weight: 600;
        color: #667eea;
        font-size: 13px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
    }
    
    .empty-state i {
        font-size: 40px;
        margin-bottom: 12px;
        display: block;
    }
    
    .stats-row-pr {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-item {
        flex: 1;
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    
    .stat-item .stat-value { font-size: 28px; font-weight: 700; color: #1f2937; }
    .stat-item .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .stat-item.primary .stat-value { color: #667eea; }
    .stat-item.info .stat-value { color: #3b82f6; }
    .stat-item.success .stat-value { color: #10b981; }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .page-header-pr { padding: 16px 20px; flex-direction: column; align-items: flex-start; }
        .filter-card .filter-body .form-inline { flex-direction: column; }
        .filter-card .filter-body .form-group { width: 100%; margin-right: 0 !important; }
        .filter-card .filter-body .form-control { width: 100% !important; }
        .stats-row-pr { flex-direction: column; }
    }
</style>

<div class="pr-list-wrapper">
    <!-- Page Header -->
    <div class="page-header-pr">
        <div>
            <h2><i class="fa fa-clipboard"></i> <?=$xml->purchasingrequest ?? 'Purchase Requests'?></h2>
            <div class="subtitle">Manage and track all purchase requests</div>
        </div>
        <div class="header-actions">
            <a href="index.php?page=pr_create" class="btn-create"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Create'?> PR</a>
        </div>
    </div>

    <!-- Search and Filter Panel -->
    <div class="filter-card">
        <div class="filter-header">
            <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        </div>
        <div class="filter-body">
            <form method="get" action="" class="form-inline">
                <input type="hidden" name="page" value="pr_list">
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> PR, Description, Customer..." 
                           value="<?=e($filters['search'])?>" style="width: 240px;">
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <select name="status" class="form-control">
                        <option value="0" <?=$status==='0'||$status===''?'selected':''?>><?=$xml->processpr ?? 'PR'?></option>
                        <option value="1" <?=$status==='1'?'selected':''?>><?=$xml->processquo ?? 'Quotation'?></option>
                        <option value="2" <?=$status==='2'?'selected':''?>><?=$xml->processpo ?? 'PO'?></option>
                        <option value="3" <?=$status==='3'?'selected':''?>><?=$xml->processdeli ?? 'Delivery'?></option>
                        <option value="4" <?=$status==='4'?'selected':''?>><?=$xml->processpaid ?? 'Paid'?></option>
                        <option value="5" <?=$status==='5'?'selected':''?>><?=$xml->success ?? 'Success'?></option>
                        <option value="6" <?=$status==='6'?'selected':''?>><?=$xml->processall ?? 'All'?></option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <label style="margin-right: 6px; color: #6b7280; font-weight: 500;"><?=$xml->from ?? 'From'?>:</label>
                    <input type="date" class="form-control" name="date_from" value="<?=e($filters['date_from'])?>">
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <label style="margin-right: 6px; color: #6b7280; font-weight: 500;"><?=$xml->to ?? 'To'?>:</label>
                    <input type="date" class="form-control" name="date_to" value="<?=e($filters['date_to'])?>">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom: 10px;"><i class="fa fa-search"></i> <?=$xml->filter ?? 'Filter'?></button>
                <a href="?page=pr_list" class="btn btn-default" style="margin-bottom: 10px; margin-left: 8px;"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row-pr">
        <div class="stat-item primary"><div class="stat-value"><?=$total_records?></div><div class="stat-label"><?=$xml->total ?? 'Total'?></div></div>
        <div class="stat-item info"><div class="stat-value"><?=$total_out?></div><div class="stat-label"><?=$xml->out ?? 'Outgoing'?></div></div>
        <div class="stat-item success"><div class="stat-value"><?=$total_in?></div><div class="stat-label"><?=$xml->in ?? 'Incoming'?></div></div>
    </div>

    <!-- PR Out Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->purchasingrequest ?? 'Purchase Request'?> - <?=$xml->out ?? 'Out'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->prno ?? 'PR#'?></th>
                    <th width="230"><?=$xml->customer ?? 'Customer'?></th>
                    <th><?=$xml->description ?? 'Description'?></th>
                    <th width="110"><?=$xml->date ?? 'Date'?></th>
                    <th width="100"><?=$xml->status ?? 'Status'?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items_out)): ?>
                <tr><td colspan="6">
                    <div class="empty-state"><i class="fa fa-inbox"></i><p>No outgoing purchase requests</p></div>
                </td></tr>
            <?php else: foreach ($items_out as $row):
                $statusVar = decodenum($row['status']);
                $badgeClass = $statusClasses[$row['status']] ?? 'pending';
            ?>
                <tr>
                    <td><span class="pr-number">PR-<?=str_pad($row['id'], 6, '0', STR_PAD_LEFT)?></span></td>
                    <td><?=e($row['name_en'])?></td>
                    <td><?=e($row['des'] ?? $row['name'])?></td>
                    <td><?=e($row['createdate'])?></td>
                    <td><span class="status-badge <?=$badgeClass?>"><?=$xml->$statusVar ?? ucfirst($statusVar)?></span></td>
                    <td>
                        <a class="action-btn" href="index.php?page=pr_view&id=<?=$row['id']?>" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                        <?php if ($row['status'] == '0'): ?>
                            <a class="action-btn info" href="index.php?page=po_make&id=<?=$row['id']?>" title="<?=$xml->makepo ?? 'Make PO'?>"><i class="fa fa-pencil"></i></a>
                            <a class="action-btn danger" onclick="return confirm('<?=$xml->areyousure ?? 'Are you sure?'?>')" href="index.php?page=pr_store&method=D&id=<?=$row['id']?>&csrf_token=<?=csrf_token()?>" title="<?=$xml->cancel ?? 'Cancel'?>"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PR In Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->purchasingrequest ?? 'Purchase Request'?> - <?=$xml->in ?? 'In'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->prno ?? 'PR#'?></th>
                    <th width="230"><?=$xml->vender ?? 'Vendor'?></th>
                    <th><?=$xml->description ?? 'Description'?></th>
                    <th width="110"><?=$xml->date ?? 'Date'?></th>
                    <th width="100"><?=$xml->status ?? 'Status'?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items_in)): ?>
                <tr><td colspan="6">
                    <div class="empty-state"><i class="fa fa-inbox"></i><p>No incoming purchase requests</p></div>
                </td></tr>
            <?php else: foreach ($items_in as $row):
                $statusVar = decodenum($row['status']);
                $badgeClass = $statusClasses[$row['status']] ?? 'pending';
            ?>
                <tr>
                    <td><span class="pr-number">PR-<?=str_pad($row['id'], 6, '0', STR_PAD_LEFT)?></span></td>
                    <td><?=e($row['name_en'])?></td>
                    <td><?=e($row['des'] ?? $row['name'])?></td>
                    <td><?=e($row['createdate'])?></td>
                    <td><span class="status-badge <?=$badgeClass?>"><?=$xml->$statusVar ?? ucfirst($statusVar)?></span></td>
                    <td>
                        <a class="action-btn" href="index.php?page=pr_view&id=<?=$row['id']?>" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                        <?php if ($row['status'] == '0'): ?>
                            <a class="action-btn info" href="index.php?page=po_make&id=<?=$row['id']?>" title="<?=$xml->makepo ?? 'Make PO'?>"><i class="fa fa-pencil"></i></a>
                            <a class="action-btn danger" onclick="return confirm('<?=$xml->areyousure ?? 'Are you sure?'?>')" href="index.php?page=pr_store&method=D&id=<?=$row['id']?>&csrf_token=<?=csrf_token()?>" title="<?=$xml->cancel ?? 'Cancel'?>"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper">
        <?php render_pagination($pagination, array_merge($query_params, ['page' => 'pr_list'])); ?>
    </div>
</div>
