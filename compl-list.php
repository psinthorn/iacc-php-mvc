<?php
/**
 * Invoice List
 * Mobile-first responsive with pagination and default date filters
 */
require_once("inc/security.php");
require_once("inc/pagination.php");

$com_id = sql_int($_SESSION['com_id']);

// Get pagination parameters
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
if (!in_array($per_page, [10, 20, 50, 100])) $per_page = 20;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_preset = isset($_GET['date_preset']) ? $_GET['date_preset'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Apply date preset if selected (no default - show all data initially)
if (!empty($date_preset) && $date_preset !== 'all') {
    $date_range = get_date_range($date_preset);
    $date_from = $date_range['from'];
    $date_to = $date_range['to'];
}

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR iv.taxrw LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND iv.createdate >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND iv.createdate <= '$date_to'";
}

// Build status filter
$status_cond = '';
if ($status_filter === 'pending') {
    $status_cond = " AND status='4'";
} elseif ($status_filter === 'completed') {
    $status_cond = " AND status='5'";
}

// Count total records for OUT
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.ven_id='$com_id' AND status>='4' $search_cond $date_cond $status_cond");
$total_out = mysqli_fetch_assoc($count_query)['total'];

// Count total records for IN
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.cus_id='$com_id' AND status>='4' $search_cond $date_cond $status_cond");
$total_in = mysqli_fetch_assoc($count_query)['total'];

$total_records = $total_out + $total_in;
$pagination = paginate($total_records, $per_page, $current_page);
$offset = $pagination['offset'];
$limit = $pagination['per_page'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['pg']);
?>

<h2><i class="fa fa-thumbs-up"></i> <?=$xml->invoice?></h2>

<!-- Search and Filter Panel (Mobile-First) -->
<div class="panel panel-default filter-panel">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        <span class="pull-right">
            <small class="text-muted"><?=$total_records?> records</small>
        </span>
    </div>
    <div class="panel-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="compl_list">
            
            <!-- Date Preset Buttons -->
            <div style="margin-bottom: 10px;">
                <?= render_date_presets($date_preset, 'compl_list') ?>
            </div>
            
            <!-- Search Row -->
            <div class="row">
                <div class="col-xs-12 col-sm-3" style="margin-bottom: 10px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Name..." 
                           value="<?=htmlspecialchars($search)?>">
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 10px;">
                    <select name="status" class="form-control">
                        <option value=""><?=$xml->all ?? 'All Status'?></option>
                        <option value="pending" <?=$status_filter=='pending'?'selected':''?>><?=$xml->pending ?? 'Pending'?></option>
                        <option value="completed" <?=$status_filter=='completed'?'selected':''?>><?=$xml->completed ?? 'Completed'?></option>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 10px;">
                    <input type="date" class="form-control" name="date_from" 
                           placeholder="<?=$xml->from ?? 'From'?>" 
                           value="<?=htmlspecialchars($date_from)?>">
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 10px;">
                    <input type="date" class="form-control" name="date_to" 
                           placeholder="<?=$xml->to ?? 'To'?>" 
                           value="<?=htmlspecialchars($date_to)?>">
                </div>
                <div class="col-xs-6 col-sm-3" style="margin-bottom: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?>
                    </button>
                    <a href="?page=compl_list" class="btn btn-default">
                        <i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row" style="margin-bottom: 15px;">
    <div class="col-xs-6 col-sm-3">
        <div class="summary-card" style="background: #dff0d8;">
            <span class="number text-success"><?=$total_out?></span>
            <span class="label-text"><?=$xml->invoice?> <?=$xml->out ?? 'Out'?></span>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3">
        <div class="summary-card" style="background: #d9edf7;">
            <span class="number text-primary"><?=$total_in?></span>
            <span class="label-text"><?=$xml->invoice?> <?=$xml->in ?? 'In'?></span>
        </div>
    </div>
</div>

<!-- Invoice List - OUT -->
<div class="section-header out">
    <i class="fa fa-arrow-up"></i> <?=$xml->invoice?> - <?=$xml->out ?? 'Out'?>
    <span class="badge"><?=$total_out?></span>
</div>

<div class="table-responsive-mobile">
<table class="table table-hover table-cards">
    <thead>
        <tr>
            <th><?=$xml->customer?></th>
            <th><?=$xml->inno?></th>
            <th class="hidden-xs"><?=$xml->name?></th>
            <th><?=$xml->duedate?></th>
            <th class="hidden-xs"><?=$xml->status?></th>
            <th width="100"></th>
        </tr>
    </thead>
    <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, countmailinv, po.name as name, taxrw as tax, status_iv,  
    DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, 
    DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.ven_id='$com_id' AND status>='4' $search_cond $date_cond $status_cond 
    ORDER BY iv.id DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    
    // Determine status
    if(($data['status_iv']=="2") && ($data['status']=="4")){
        $statusiv = "void";
        $status_class = "cancelled";
    } else if(($data['status']=="4") && ($data['valid_pay'] < date("d-m-Y"))) {
        $statusiv = "overdue";
        $status_class = "overdue";
    } else {
        $statusiv = decodenum($data['status']);
        $status_class = ($data['status'] == '5') ? 'completed' : 'pending';
    }
?>
        <tr>
            <td data-label="<?=$xml->customer?>"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->inno?>">INV-<?=e($data['tax'])?></td>
            <td data-label="<?=$xml->name?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->duedate?>"><?=e($data['valid_pay'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>"><?=$xml->$statusiv?></span>
            </td>
            <td class="actions">
                <?php if($data['status'] != "5"): ?>
                <a href="index.php?page=compl_view&id=<?=e($data['id'])?>" class="action-btn" title="View">
                    <i class="fa fa-search-plus"></i>
                </a>
                <?php endif; ?>
                <a href="inv.php?id=<?=e($data['id'])?>" target="_blank" class="action-btn" title="Invoice">IV</a>
                <a data-toggle="modal" href="model_mail.php?page=inv&id=<?=e($data['id'])?>" data-target=".bs-example-modal-lg" class="action-btn" title="Email">
                    <i class="glyphicon glyphicon-envelope"></i>
                    <?php if($data['countmailinv'] > 0): ?>
                    <span class="badge"><?=e($data['countmailinv'])?></span>
                    <?php endif; ?>
                </a>
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

<!-- Invoice List - IN -->
<div class="section-header in">
    <i class="fa fa-arrow-down"></i> <?=$xml->invoice?> - <?=$xml->in ?? 'In'?>
    <span class="badge"><?=$total_in?></span>
</div>

<div class="table-responsive-mobile">
<table class="table table-hover table-cards">
    <thead>
        <tr>
            <th><?=$xml->vender?></th>
            <th><?=$xml->inno?></th>
            <th class="hidden-xs"><?=$xml->name?></th>
            <th><?=$xml->duedate?></th>
            <th class="hidden-xs"><?=$xml->status?></th>
            <th width="80"></th>
        </tr>
    </thead>
    <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, po.name as name, taxrw as tax,  
    DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, 
    DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.cus_id='$com_id' AND status>='4' $search_cond $date_cond $status_cond 
    ORDER BY iv.id DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $var = decodenum($data['status']);
    $status_class = ($data['status'] == '5') ? 'completed' : 'pending';
?>
        <tr>
            <td data-label="<?=$xml->vender?>"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->inno?>">INV-<?=e($data['tax'])?></td>
            <td data-label="<?=$xml->name?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->duedate?>"><?=e($data['valid_pay'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>"><?=$xml->$var?></span>
            </td>
            <td class="actions">
                <?php if($data['status'] != "5"): ?>
                <a href="index.php?page=compl_view&id=<?=e($data['id'])?>" class="action-btn" title="View">
                    <i class="fa fa-search-plus"></i>
                </a>
                <?php endif; ?>
                <a href="inv.php?id=<?=e($data['id'])?>" target="_blank" class="action-btn" title="Invoice">IV</a>
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

<!-- Pagination -->
<?= render_pagination($pagination, '?page=compl_list', $query_params) ?>

<div id="fetch_state"></div>