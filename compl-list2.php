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
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR name_en LIKE '%$search_escaped%' OR iv.texiv_rw LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND iv.texiv_create >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND iv.texiv_create <= '$date_to'";
}

// Count total records for OUT
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND ven_id='$com_id' AND status='5' AND iv.texiv_rw IS NOT NULL AND iv.texiv_rw != '' $search_cond $date_cond");
$total_out = $count_query ? (mysqli_fetch_assoc($count_query)['total'] ?? 0) : 0;

// Count total records for IN
$count_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.cus_id='$com_id' AND status='5' AND iv.texiv_rw IS NOT NULL AND iv.texiv_rw != '' $search_cond $date_cond");
$total_in = $count_query ? (mysqli_fetch_assoc($count_query)['total'] ?? 0) : 0;

$total_records = $total_out + $total_in;
$pagination = paginate($total_records, $per_page, $current_page);
$offset = $pagination['offset'];
$limit = $pagination['per_page'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['pg']);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Modern Tax Invoice List Styling */
.taxinv-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }
.page-header-tax { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(5,150,105,0.3); }
.page-header-tax h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-tax .record-count { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500; }

.filter-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.filter-card .filter-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; }
.filter-card .filter-header i { color: #059669; }
.filter-card .filter-body { padding: 20px; }
.filter-card .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 44px; padding: 10px 16px; font-size: 14px; transition: all 0.2s; }
.filter-card .form-control:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.15); }
.filter-card .btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
.filter-card .btn-default { border-radius: 10px; padding: 10px 20px; }

.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.summary-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 16px; }
.summary-card .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.summary-card .icon.out { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #16a34a; }
.summary-card .icon.in { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }
.summary-card .info h3 { margin: 0; font-size: 28px; font-weight: 700; color: #1f2937; }
.summary-card .info p { margin: 0; font-size: 13px; color: #6b7280; }

.data-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.data-card .card-header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 15px; }
.data-card .card-header.out { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #166534; }
.data-card .card-header.in { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e40af; }
.data-card .card-header .badge { background: rgba(0,0,0,0.1); padding: 4px 12px; border-radius: 20px; font-size: 13px; }

.table-modern { margin-bottom: 0; }
.table-modern thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
.table-modern tbody tr { transition: background-color 0.2s; }
.table-modern tbody tr:hover { background-color: #f0fdf4; }
.table-modern tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.table-modern .tax-number { font-weight: 600; color: #059669; }
.table-modern .customer-name { font-weight: 500; color: #1f2937; }

.status-badge { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge.completed { background: #dcfce7; color: #166534; }

.btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; margin-right: 4px; transition: all 0.2s; text-decoration: none; font-size: 12px; }
.btn-action-tax { background: #dcfce7; color: #16a34a; font-weight: 600; width: auto; padding: 0 12px; }
.btn-action-tax:hover { background: #16a34a; color: #fff; }
.btn-action-email { background: #fef3c7; color: #d97706; }
.btn-action-email:hover { background: #d97706; color: #fff; }

.empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
.empty-state i { font-size: 48px; margin-bottom: 16px; color: #d1d5db; }
.empty-state h4 { margin: 0 0 8px 0; color: #374151; font-weight: 600; }
.empty-state p { margin: 0; font-size: 14px; }

.date-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.date-presets .btn { border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 500; }
</style>

<div class="taxinv-container">

<!-- Page Header -->
<div class="page-header-tax">
    <h2><i class="fa fa-file-text"></i> <?=$xml->taxinvoice?></h2>
    <span class="record-count"><i class="fa fa-list"></i> <?=$total_records?> <?=$xml->records ?? 'records'?></span>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <div class="filter-header">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="filter-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="compl_list2">
            
            <!-- Date Preset Buttons -->
            <div class="date-presets">
                <?= render_date_presets($date_preset, 'compl_list2') ?>
            </div>
            
            <!-- Search Row -->
            <div class="row">
                <div class="col-xs-12 col-sm-3" style="margin-bottom: 12px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> TAX#, Name..." 
                           value="<?=htmlspecialchars($search)?>">
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 12px;">
                    <input type="date" class="form-control" name="date_from" 
                           placeholder="<?=$xml->from ?? 'From'?>" 
                           value="<?=htmlspecialchars($date_from)?>">
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 12px;">
                    <input type="date" class="form-control" name="date_to" 
                           placeholder="<?=$xml->to ?? 'To'?>" 
                           value="<?=htmlspecialchars($date_to)?>">
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom: 12px;">
                    <select name="per_page" class="form-control" onchange="this.form.submit()">
                        <option value="10" <?=$per_page==10?'selected':''?>>10</option>
                        <option value="20" <?=$per_page==20?'selected':''?>>20</option>
                        <option value="50" <?=$per_page==50?'selected':''?>>50</option>
                        <option value="100" <?=$per_page==100?'selected':''?>>100</option>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-3" style="margin-bottom: 12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?>
                    </button>
                    <a href="?page=compl_list2" class="btn btn-default">
                        <i class="fa fa-refresh"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="icon out"><i class="fa fa-arrow-up"></i></div>
        <div class="info">
            <h3><?=$total_out?></h3>
            <p><?=$xml->taxinvoice?> <?=$xml->out ?? 'Out'?></p>
        </div>
    </div>
    <div class="summary-card">
        <div class="icon in"><i class="fa fa-arrow-down"></i></div>
        <div class="info">
            <h3><?=$total_in?></h3>
            <p><?=$xml->taxinvoice?> <?=$xml->in ?? 'In'?></p>
        </div>
    </div>
</div>

<!-- Tax Invoice List - OUT -->
<div class="data-card">
    <div class="card-header out">
        <i class="fa fa-arrow-up"></i> <?=$xml->taxinvoice?> - <?=$xml->out ?? 'Out'?>
        <span class="badge"><?=$total_out?></span>
    </div>

<div class="table-responsive">
<table class="table table-modern">
    <thead>
        <tr>
            <th width="120"><?=$xml->taxno?></th>
            <th width="230"><?=$xml->customer?></th>
            <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
            <th width="100"><?=$xml->createdate?></th>
            <th class="hidden-xs" width="90"><?=$xml->status?></th>
            <th width="120"><?=$xml->action ?? 'Actions'?></th>
        </tr>
    </thead>
    <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, countmailtax, po.name as name, iv.texiv_rw, 
    DATE_FORMAT(iv.texiv_create,'%d-%m-%Y') as texiv_create, name_en, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.cus_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND ven_id='$com_id' AND status='5' AND iv.texiv_rw IS NOT NULL AND iv.texiv_rw != '' $search_cond $date_cond 
    ORDER BY iv.texiv_rw DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $var = decodenum($data['status']);
    $status_class = 'completed';
?>
        <tr>
            <td data-label="<?=$xml->taxno?>" class="tax-number">TAX-<?=str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)?></td>
            <td data-label="<?=$xml->customer?>" class="customer-name"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->description ?? 'Description'?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->createdate?>"><?=e($data['texiv_create'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>"><?=$xml->$var?></span>
            </td>
            <td>
                <a href="taxiv.php?id=<?=e($data['id'])?>" target="_blank" class="btn-action btn-action-tax" title="Tax Invoice">TAX</a>
                <a data-toggle="modal" href="model_mail.php?page=tax&id=<?=e($data['id'])?>" data-target=".bs-example-modal-lg" class="btn-action btn-action-email" title="Email">
                    <i class="fa fa-envelope"></i>
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
</div>

<!-- Tax Invoice List - IN -->
<div class="data-card">
    <div class="card-header in">
        <i class="fa fa-arrow-down"></i> <?=$xml->taxinvoice?> - <?=$xml->in ?? 'In'?>
        <span class="badge"><?=$total_in?></span>
    </div>

<div class="table-responsive">
<table class="table table-modern">
    <thead>
        <tr>
            <th width="120"><?=$xml->taxno?></th>
            <th width="230"><?=$xml->vender ?? 'Vendor'?></th>
            <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
            <th width="100"><?=$xml->createdate?></th>
            <th class="hidden-xs" width="90"><?=$xml->status?></th>
            <th width="100"><?=$xml->action ?? 'Actions'?></th>
        </tr>
    </thead>
    <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT po.id as id, po.name as name, iv.id as tax, iv.texiv_rw, 
    DATE_FORMAT(iv.texiv_create,'%d-%m-%Y') as texiv_create, name_en, status 
    FROM po 
    JOIN pr ON po.ref=pr.id 
    JOIN company ON pr.ven_id=company.id 
    JOIN iv ON po.id=iv.tex 
    WHERE po_id_new='' AND pr.cus_id='$com_id' AND status='5' AND iv.texiv_rw IS NOT NULL AND iv.texiv_rw != '' $search_cond $date_cond 
    ORDER BY iv.texiv_rw DESC 
    LIMIT $offset, $limit");

$row_count = 0;
while($data = mysqli_fetch_array($query)) {
    $row_count++;
    $var = decodenum($data['status']);
    $status_class = 'completed';
?>
        <tr>
            <td data-label="<?=$xml->taxno?>" class="tax-number">TAX-<?=str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)?></td>
            <td data-label="<?=$xml->vender ?? 'Vendor'?>" class="customer-name"><?=e($data['name_en'])?></td>
            <td data-label="<?=$xml->description ?? 'Description'?>" class="hidden-xs text-truncate"><?=e($data['name'])?></td>
            <td data-label="<?=$xml->createdate?>"><?=e($data['texiv_create'])?></td>
            <td data-label="<?=$xml->status?>" class="hidden-xs">
                <span class="status-badge <?=$status_class?>"><?=$xml->$var?></span>
            </td>
            <td>
                <a href="taxiv.php?id=<?=e($data['id'])?>" target="_blank" class="btn-action btn-action-tax" title="Tax Invoice">TAX</a>
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
<?= render_pagination($pagination, '?page=compl_list2', $query_params) ?>

</div><!-- /taxinv-container -->
