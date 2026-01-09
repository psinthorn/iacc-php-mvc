<?php
/**
 * Purchase Order List
 * Mobile-first responsive with pagination and default date filters
 */
require_once("inc/pagination.php");

// Security already checked in index.php
$com_id = sql_int($_SESSION['com_id']);

// Get pagination parameters
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
if (!in_array($per_page, [10, 20, 50, 100])) $per_page = 20;

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_preset = isset($_GET['date_preset']) ? $_GET['date_preset'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Apply date preset if selected (no default - show all data initially)
if (!empty($date_preset)) {
    if ($date_preset === 'all') {
        // Clear date filters when 'All' is selected
        $date_from = '';
        $date_to = '';
    } else {
        $date_range = get_date_range($date_preset);
        $date_from = $date_range['from'];
        $date_to = $date_range['to'];
    }
}

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR po.tax LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%' OR company.name_th LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND po.date >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND po.date <= '$date_to'";
}

// Count total records for OUT
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    WHERE po_id_new='' AND ven_id='$com_id' AND status='2' $search_cond $date_cond");
$total_out = mysqli_fetch_assoc($count_query)['total'];

// Count total records for IN  
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    WHERE po_id_new='' AND cus_id='$com_id' AND status='2' $search_cond $date_cond");
$total_in = mysqli_fetch_assoc($count_query)['total'];

$total_records = $total_out + $total_in;
$pagination = paginate($total_records, $per_page, $current_page);

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['pg']);
?>

<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .list-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(16, 185, 129, 0.25);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header .subtitle {
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .filter-card .filter-header i {
        color: #10b981;
        margin-right: 8px;
    }
    
    .filter-card .filter-body {
        padding: 20px;
    }
    
    .filter-card .form-control {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 12px 14px;
        font-size: 14px;
        min-height: 44px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .filter-card .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        outline: none;
    }
    
    .filter-card .btn-primary {
        background: #10b981;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .filter-card .btn-primary:hover {
        background: #059669;
        transform: translateY(-1px);
    }
    
    .filter-card .btn-default {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 20px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .filter-card .btn-default:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .date-preset-btn {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        border: 1px solid #e5e7eb;
        background: white;
        color: #6b7280;
        text-decoration: none;
        margin-right: 6px;
        margin-bottom: 6px;
        transition: all 0.2s;
    }
    
    .date-preset-btn:hover {
        border-color: #10b981;
        color: #10b981;
        text-decoration: none;
    }
    
    .date-preset-btn.active {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }
    
    .summary-cards {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .summary-card {
        flex: 1;
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .summary-card .number {
        font-size: 28px;
        font-weight: 700;
    }
    
    .summary-card .label-text {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .summary-card.out { border-left: 4px solid #10b981; }
    .summary-card.in { border-left: 4px solid #3b82f6; }
    
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
    
    .data-card .section-header .badge {
        background: #10b981;
        color: white;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        margin-left: 8px;
    }
    
    .data-card .section-header.in-header .badge {
        background: #3b82f6;
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
        background: rgba(16, 185, 129, 0.03);
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        text-decoration: none;
        transition: all 0.2s;
        margin-right: 4px;
    }
    
    .action-btn:hover {
        background: #10b981;
        color: white;
        text-decoration: none;
    }
    
    .action-btn.primary {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }
    
    .action-btn.primary:hover {
        background: #667eea;
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
    }
    
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.cancelled { background: #fee2e2; color: #ef4444; }
    .status-badge.success { background: #d1fae5; color: #10b981; }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 48px;
        opacity: 0.3;
        margin-bottom: 16px;
    }
    
    .empty-state h4 {
        margin: 0 0 8px 0;
        color: #1f2937;
    }
    
    .record-count {
        color: #6b7280;
        font-size: 13px;
        font-weight: 400;
    }
</style>

<div class="list-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder?></h2>
        <div class="subtitle">Manage and track all purchase orders</div>
    </div>

    <!-- Search and Filter Panel -->
    <div class="filter-card">
        <div class="filter-header">
            <span><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></span>
            <span class="record-count"><?=$total_records?> records</span>
        </div>
        <div class="filter-body">
            <form method="get" action="">
                <input type="hidden" name="page" value="po_list">
                
                <!-- Date Preset Buttons -->
                <div style="margin-bottom: 16px;">
                    <?= render_date_presets($date_preset, 'po_list') ?>
                </div>
                
                <!-- Search Row -->
                <div class="row">
                    <div class="col-xs-12 col-sm-4 col-md-3" style="margin-bottom: 10px;">
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="<?=$xml->search ?? 'Search'?> PO#, Name..." 
                               value="<?=htmlspecialchars($search)?>">
                    </div>
                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom: 10px;">
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               placeholder="<?=$xml->from ?? 'From'?>" 
                               value="<?=htmlspecialchars($date_from)?>">
                    </div>
                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom: 10px;">
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               placeholder="<?=$xml->to ?? 'To'?>" 
                               value="<?=htmlspecialchars($date_to)?>">
                    </div>
                    <div class="col-xs-6 col-sm-2 col-md-2" style="margin-bottom: 10px;">
                        <select name="per_page" class="form-control" onchange="this.form.submit()">
                            <option value="10" <?=$per_page==10?'selected':''?>>10</option>
                            <option value="20" <?=$per_page==20?'selected':''?>>20</option>
                            <option value="50" <?=$per_page==50?'selected':''?>>50</option>
                            <option value="100" <?=$per_page==100?'selected':''?>>100</option>
                        </select>
                    </div>
                    <div class="col-xs-6 col-sm-12 col-md-3" style="margin-bottom: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?>
                        </button>
                        <a href="?page=po_list" class="btn btn-default">
                            <i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card out">
            <span class="number text-success"><?=$total_out?></span>
            <span class="label-text">PO <?=$xml->out ?? 'Out'?></span>
        </div>
        <div class="summary-card in">
            <span class="number text-primary"><?=$total_in?></span>
            <span class="label-text">PO <?=$xml->in ?? 'In'?></span>
        </div>
    </div>

    <!-- PO List - OUT -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->purchasingorder?> - <?=$xml->out?>
            <span class="badge"><?=$total_out?></span>
            </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->pono?></th>
                    <th width="230"><?=$xml->customer?></th>
                    <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
                    <th width="100"><?=$xml->duedate?></th>
                    <th class="hidden-xs" width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$offset = $pagination['offset'];
$limit = $pagination['per_page'];

$query = mysqli_query($db->conn, "SELECT po.id as id, cancel, po.name as name, po.tax as tax, 
    DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, 
    DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    WHERE po_id_new='' AND ven_id='$com_id' AND status='2' $search_cond $date_cond 
    ORDER BY cancel, po.id DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $pg = ($data['status'] == 2) ? "po_deliv" : "po_edit";
    $var = decodenum($data['status']);
    $is_cancelled = ($data['cancel'] == "1");
    $status_class = $is_cancelled ? 'cancelled' : 'pending';
?>
        <tr>
            <td data-label="<?=$xml->pono?>">PO-<?=e($data['tax'])?></td>
            <td data-label="<?=$xml->customer?>"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->description ?? 'Description'?>" class="hidden-xs text-truncate"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->duedate?>"><?=e($data['valid_pay'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>">
                    <?=$is_cancelled ? $xml->$var : $xml->$var?>
                </span>
            </td>
            <td class="actions">
                <a href="index.php?page=<?=$pg?>&id=<?=e($data['id'])?>&action=c" class="action-btn" title="Process">
                    <i class="fa fa-magic"></i>
                </a>
                <?php if (!$is_cancelled): ?>
                <a onclick="return Conf(this)" href="core-function.php?page=po_list&id=<?=e($data['id'])?>&method=D" 
                   class="action-btn danger" title="Cancel">
                    <i class="fa fa-trash"></i>
                </a>
                <?php endif; ?>
            </td>
        </tr>
<?php } 

if ($row_count == 0): ?>
        <tr>
            <td colspan="6">
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <h4><?=$xml->nodata ?? 'No data found'?></h4>
                    <p><?=$xml->tryadjust ?? 'Try adjusting your search or date filters'?></p>
                </div>
            </td>
        </tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>

    <!-- PO List - IN -->
    <div class="data-card">
        <div class="section-header in-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->purchasingorder?> - <?=$xml->in?>
            <span class="badge"><?=$total_in?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="120"><?=$xml->pono?></th>
                        <th width="230">Vendor</th>
                        <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
                        <th width="100"><?=$xml->duedate?></th>
                        <th class="hidden-xs" width="90"><?=$xml->status?></th>
                        <th width="130"></th>
                    </tr>
                </thead>
                <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, po.name as name, po.tax as tax, cancel,
    DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, 
    DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    WHERE po_id_new='' AND cus_id='$com_id' AND status='2' $search_cond $date_cond 
    ORDER BY cancel, po.id DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $pg = ($data['status'] == 2) ? "po_deliv" : "po_edit";
    $var = decodenum($data['status']);
    $is_cancelled = ($data['cancel'] == "1");
    $status_class = $is_cancelled ? 'cancelled' : 'pending';
?>
        <tr>
            <td data-label="<?=$xml->pono?>">PO-<?=e($data['tax'])?></td>
            <td data-label="<?=$xml->vender?>"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->description ?? 'Description'?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->duedate?>"><?=e($data['valid_pay'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>">
                    <?=$xml->$var?>
                </span>
            </td>
            <td class="actions">
                <a href="index.php?page=po_view&id=<?=e($data['id'])?>" class="action-btn primary" title="View">
                    <i class="fa fa-dropbox"></i>
                </a>
                <?php if (!$is_cancelled): ?>
                <a href="index.php?page=<?=$pg?>&id=<?=e($data['id'])?>&action=c" class="action-btn" title="Process">
                    <i class="fa fa-magic"></i>
                </a>
                <a onclick="return Conf(this)" href="core-function.php?page=po_list&id=<?=e($data['id'])?>&method=D" 
                   class="action-btn danger" title="Cancel">
                    <i class="fa fa-trash"></i>
                </a>
                <?php endif; ?>
            </td>
        </tr>
<?php } 

if ($row_count == 0): ?>
        <tr>
            <td colspan="6">
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <h4><?=$xml->nodata ?? 'No data found'?></h4>
                    <p><?=$xml->tryadjust ?? 'Try adjusting your search or date filters'?></p>
                </div>
            </td>
        </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?= render_pagination($pagination, '?page=po_list', $query_params) ?>
</div>

<div id="fetch_state"></div>