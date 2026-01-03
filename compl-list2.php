<?php
/**
 * Tax Invoice List
 * Mobile-first responsive with pagination and date presets
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

// Apply date preset if selected
if (!empty($date_preset)) {
    if ($date_preset === 'all') {
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
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR name_en LIKE '%$search_escaped%' OR texiv_rw LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND texiv_create >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND texiv_create <= '$date_to'";
}

// Count total records for OUT
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND ven_id='$com_id' AND status='5' AND status_iv='1' $search_cond $date_cond");
$total_out = mysqli_fetch_assoc($count_query)['total'];

// Count total records for IN
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.cus_id='$com_id' AND status='5' AND status_iv='1' $search_cond $date_cond");
$total_in = mysqli_fetch_assoc($count_query)['total'];

$total_records = $total_out + $total_in;
$pagination = paginate($total_records, $per_page, $current_page);
$offset = $pagination['offset'];
$limit = $pagination['per_page'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['pg']);
?>

<h2><i class="fa fa-thumbs-up"></i> <?=$xml->taxinvoice?></h2>

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
            <input type="hidden" name="page" value="compl_list2">
            
            <!-- Date Preset Buttons -->
            <div style="margin-bottom: 10px;">
                <?= render_date_presets($date_preset, 'compl_list2') ?>
            </div>
            
            <!-- Search Row -->
            <div class="row">
                <div class="col-xs-12 col-sm-3" style="margin-bottom: 10px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> TAX#, Name..." 
                           value="<?=htmlspecialchars($search)?>">
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
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 10px;">
                    <select name="per_page" class="form-control" onchange="this.form.submit()">
                        <option value="10" <?=$per_page==10?'selected':''?>>10</option>
                        <option value="20" <?=$per_page==20?'selected':''?>>20</option>
                        <option value="50" <?=$per_page==50?'selected':''?>>50</option>
                        <option value="100" <?=$per_page==100?'selected':''?>>100</option>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-3" style="margin-bottom: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?>
                    </button>
                    <a href="?page=compl_list2" class="btn btn-default">
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
            <span class="label-text"><?=$xml->taxinvoice?> <?=$xml->out ?? 'Out'?></span>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3">
        <div class="summary-card" style="background: #d9edf7;">
            <span class="number text-primary"><?=$total_in?></span>
            <span class="label-text"><?=$xml->taxinvoice?> <?=$xml->in ?? 'In'?></span>
        </div>
    </div>
</div>

<!-- Tax Invoice List - OUT -->
<div class="section-header out">
    <i class="fa fa-arrow-up"></i> <?=$xml->taxinvoice?> - <?=$xml->out ?? 'Out'?>
    <span class="badge"><?=$total_out?></span>
</div>

<div class="table-responsive-mobile">
<table class="table table-hover table-cards">
    <thead>
        <tr>
            <th width="120"><?=$xml->taxno?></th>
            <th width="230"><?=$xml->customer?></th>
            <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
            <th width="100"><?=$xml->createdate?></th>
            <th class="hidden-xs" width="90"><?=$xml->status?></th>
            <th width="130"></th>
        </tr>
    </thead>
    <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, countmailtax, po.name as name, texiv_rw, 
    DATE_FORMAT(texiv_create,'%d-%m-%Y') as texiv_create, name_en, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND ven_id='$com_id' AND status='5' AND status_iv='1' $search_cond $date_cond 
    ORDER BY texiv_rw DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $var = decodenum($data['status']);
    $status_class = 'completed';
?>
        <tr>
            <td data-label="<?=$xml->taxno?>">TAX-<?=str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)?></td>
            <td data-label="<?=$xml->customer?>"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->description ?? 'Description'?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->createdate?>"><?=e($data['texiv_create'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>"><?=$xml->$var?></span>
            </td>
            <td class="actions">
                <a href="taxiv.php?id=<?=e($data['id'])?>" target="_blank" class="action-btn" title="Tax Invoice">TAX</a>
                <a data-toggle="modal" href="model_mail.php?page=tax&id=<?=e($data['id'])?>" data-target=".bs-example-modal-lg" class="action-btn" title="Email">
                    <i class="glyphicon glyphicon-envelope"></i>
                    <?php if($data['countmailtax'] > 0): ?>
                    <span class="badge"><?=e($data['countmailtax'])?></span>
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

<!-- Tax Invoice List - IN -->
<div class="section-header in">
    <i class="fa fa-arrow-down"></i> <?=$xml->taxinvoice?> - <?=$xml->in ?? 'In'?>
    <span class="badge"><?=$total_in?></span>
</div>

<div class="table-responsive-mobile">
<table class="table table-hover table-cards">
    <thead>
        <tr>
            <th width="120"><?=$xml->taxno?></th>
            <th width="230"><?=$xml->vender?></th>
            <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
            <th width="100"><?=$xml->createdate?></th>
            <th class="hidden-xs" width="90"><?=$xml->status?></th>
            <th width="130"></th>
        </tr>
    </thead>
    <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, po.name as name, iv.id as tax, texiv_rw, 
    DATE_FORMAT(texiv_create,'%d-%m-%Y') as texiv_create, name_en, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.cus_id='$com_id' AND status='5' AND status_iv='1' $search_cond $date_cond 
    ORDER BY texiv_rw DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $var = decodenum($data['status']);
    $status_class = 'completed';
?>
        <tr>
            <td data-label="<?=$xml->taxno?>">TAX-<?=str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)?></td>
            <td data-label="<?=$xml->vender?>"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->description ?? 'Description'?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->createdate?>"><?=e($data['texiv_create'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>"><?=$xml->$var?></span>
            </td>
            <td class="actions">
                <a href="taxiv.php?id=<?=e($data['id'])?>" target="_blank" class="action-btn" title="Tax Invoice">TAX</a>
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
<?= render_pagination($pagination, '?page=compl_list2', $query_params) ?>
