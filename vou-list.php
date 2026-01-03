<?php
require_once("inc/security.php");
require_once("inc/payment-method-helper.php");

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (voucher.name LIKE '%$search_escaped%' OR voucher.email LIKE '%$search_escaped%' OR voucher.phone LIKE '%$search_escaped%' OR voucher.vou_rw LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND createdate >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND createdate <= '$date_to'";
}

// Status filter
$status_cond = '';
if (!empty($status_filter)) {
    $status_cond = " AND status = '".sql_escape($status_filter)."'";
}
?>
<style>
/* Modern Voucher List Styling */
.voucher-container { max-width: 1400px; margin: 0 auto; }
.page-header-vou { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: #fff; padding: 20px 25px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
.page-header-vou h2 { margin: 0; font-size: 24px; font-weight: 600; }
.page-header-vou h2 i { margin-right: 10px; }
.btn-create { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s; }
.btn-create:hover { background: rgba(255,255,255,0.3); color: #fff; text-decoration: none; }
.btn-create i { margin-right: 5px; }

.filter-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
.filter-card .filter-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: 600; color: #555; }
.filter-card .filter-header i { margin-right: 8px; color: #e74c3c; }
.filter-card .filter-body { padding: 20px; }
.filter-card .form-group { margin-bottom: 0; }
.filter-card .form-control { border-radius: 5px; border: 1px solid #ddd; }
.filter-card .form-control:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); }
.filter-card .btn-search { background: #e74c3c; border: none; color: #fff; padding: 10px 25px; border-radius: 5px; font-weight: 600; }
.filter-card .btn-search:hover { background: #c0392b; }
.filter-card .btn-clear { background: #95a5a6; border: none; color: #fff; padding: 10px 20px; border-radius: 5px; }
.filter-card .btn-clear:hover { background: #7f8c8d; }

.voucher-table-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }
.voucher-table-card .table-header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: #fff; padding: 15px 20px; }
.voucher-table-card .table-header h4 { margin: 0; font-size: 16px; font-weight: 600; }
.voucher-table-card .table-header h4 i { margin-right: 8px; }

.table-voucher { margin-bottom: 0; }
.table-voucher thead th { background: #f8f9fa; color: #555; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; border-bottom: 2px solid #eee; }
.table-voucher tbody tr { transition: background-color 0.2s; }
.table-voucher tbody tr:hover { background-color: #fff5f5; }
.table-voucher tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
.table-voucher .vou-number { font-weight: 600; color: #e74c3c; }
.table-voucher .customer-name { font-weight: 500; color: #333; }

.btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 5px; margin-right: 5px; transition: all 0.2s; text-decoration: none; }
.btn-action-view { background: #e3f2fd; color: #1976d2; }
.btn-action-view:hover { background: #1976d2; color: #fff; }
.btn-action-edit { background: #fff3e0; color: #f57c00; }
.btn-action-edit:hover { background: #f57c00; color: #fff; }
.btn-action-pdf { background: #ffebee; color: #e74c3c; font-size: 10px; font-weight: 600; width: auto; padding: 0 10px; }
.btn-action-pdf:hover { background: #e74c3c; color: #fff; }

.empty-state { text-align: center; padding: 60px 20px; color: #999; }
.empty-state i { font-size: 48px; margin-bottom: 15px; color: #ddd; }
.empty-state p { font-size: 16px; }

.stats-row { display: flex; gap: 15px; margin-bottom: 20px; }
.stat-card { flex: 1; background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; align-items: center; }
.stat-card .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px; }
.stat-card .stat-icon.total { background: #ebf5fb; color: #3498db; }
.stat-card .stat-icon.confirmed { background: #eafaf1; color: #27ae60; }
.stat-card .stat-icon.draft { background: #fef9e7; color: #f39c12; }
.stat-card .stat-icon.cancelled { background: #fdedec; color: #e74c3c; }
.stat-card .stat-info h3 { margin: 0; font-size: 24px; font-weight: 700; color: #333; }
.stat-card .stat-info p { margin: 0; font-size: 12px; color: #999; text-transform: uppercase; }
</style>

<div class="voucher-container">

<!-- Page Header -->
<div class="page-header-vou">
    <h2><i class="glyphicon glyphicon-tags"></i> <?=$xml->voucher ?? 'Vouchers'?></h2>
    <a href="?page=voc_make" class="btn-create"><i class="glyphicon glyphicon-plus"></i> <?=$xml->create ?? 'Create'?> <?=$xml->voucher ?? 'Voucher'?></a>
</div>

<?php
// Get statistics
$stats = mysqli_fetch_array(mysqli_query($db->conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM voucher WHERE vender='".$_SESSION['com_id']."'"));
?>

<!-- Stats Row -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon total"><i class="glyphicon glyphicon-list-alt"></i></div>
        <div class="stat-info">
            <h3><?=$stats['total'] ?? 0?></h3>
            <p><?=$xml->total ?? 'Total'?> <?=$xml->voucher ?? 'Vouchers'?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon confirmed"><i class="glyphicon glyphicon-ok-circle"></i></div>
        <div class="stat-info">
            <h3><?=$stats['confirmed'] ?? 0?></h3>
            <p><?=$xml->confirmed ?? 'Confirmed'?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon draft"><i class="glyphicon glyphicon-edit"></i></div>
        <div class="stat-info">
            <h3><?=$stats['draft'] ?? 0?></h3>
            <p><?=$xml->draft ?? 'Draft'?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cancelled"><i class="glyphicon glyphicon-ban-circle"></i></div>
        <div class="stat-info">
            <h3><?=$stats['cancelled'] ?? 0?></h3>
            <p><?=$xml->cancelled ?? 'Cancelled'?></p>
        </div>
    </div>
</div>

<!-- Search and Filter Panel -->
<div class="filter-card">
    <div class="filter-header">
        <i class="glyphicon glyphicon-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="filter-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="voucher_list">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="<?=$xml->search ?? 'Search'?> Name, Email, Phone, VOC#..." 
                               value="<?=htmlspecialchars($search)?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value=""><?=$xml->allstatus ?? 'All Status'?></option>
                            <option value="confirmed" <?=$status_filter=='confirmed'?'selected':''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                            <option value="draft" <?=$status_filter=='draft'?'selected':''?>><?=$xml->draft ?? 'Draft'?></option>
                            <option value="cancelled" <?=$status_filter=='cancelled'?'selected':''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>" placeholder="From">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>" placeholder="To">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-search"><i class="glyphicon glyphicon-search"></i> <?=$xml->search ?? 'Search'?></button>
                    <a href="?page=voucher_list" class="btn btn-clear"><i class="glyphicon glyphicon-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Voucher Table -->
<div class="voucher-table-card">
    <div class="table-header">
        <h4><i class="glyphicon glyphicon-arrow-up"></i> <?=$xml->voucher ?? 'Voucher'?> - <?=$xml->out ?? 'Expenses'?></h4>
    </div>
    <table class="table table-voucher">
        <thead>
            <tr>
                <th width="120"><?=$xml->voucherno ?? 'Voucher No.'?></th>
                <th><?=$xml->customer ?? 'Vendor/Payee'?></th>
                <th width="130"><?=$xml->paymentmethod ?? 'Payment'?></th>
                <th width="100"><?=$xml->status ?? 'Status'?></th>
                <th width="120"><?=$xml->createdate ?? 'Date'?></th>
                <th width="100" class="text-center"><?=$xml->actions ?? 'Actions'?></th>
            </tr>
        </thead>
        <tbody>
<?php
$query=mysqli_query($db->conn, "SELECT voucher.id as id, voucher.name as name, voucher.email as email, phone, 
    DATE_FORMAT(createdate,'%d-%m-%Y') as createdate, voucher.description as description, vou_rw, 
    voucher.brand as brand, voucher.vender as vender, payment_method, status 
    FROM voucher 
    WHERE voucher.vender='".$_SESSION['com_id']."' $search_cond $date_cond $status_cond 
    ORDER BY voucher.id DESC");

$count = mysqli_num_rows($query);

// Get language for payment method display
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$payment_labels_with_icons = getPaymentMethodLabelsWithIcons($db->conn, $lang);

if($count > 0):
    while($data=mysqli_fetch_array($query)){
        // Payment method display - use dynamic labels from database
        $payment_display = $payment_labels_with_icons[$data['payment_method']] ?? getPaymentMethodDisplayName($db->conn, $data['payment_method'], $lang);
        
        // Status display with badge
        $status_class = '';
        $status_label = '';
        switch($data['status']) {
            case 'draft': $status_class = 'warning'; $status_label = $xml->draft ?? 'Draft'; break;
            case 'confirmed': $status_class = 'success'; $status_label = $xml->confirmed ?? 'Confirmed'; break;
            case 'cancelled': $status_class = 'danger'; $status_label = $xml->cancelled ?? 'Cancelled'; break;
            default: $status_class = 'default'; $status_label = $data['status'];
        }
        
        echo "<tr>
            <td class='vou-number'>VOC-".$data['vou_rw']."</td>
            <td class='customer-name'>".htmlspecialchars($data['name'])."</td>
            <td>".$payment_display."</td>
            <td><span class='label label-".$status_class."'>".$status_label."</span></td>
            <td>".$data['createdate']."</td>
            <td class='text-center'>
                <a href='?page=voc_view&id=".$data['id']."' class='btn-action btn-action-view' title='View'><i class='glyphicon glyphicon-eye-open'></i></a>
                <a href='?page=voc_make&id=".$data['id']."' class='btn-action btn-action-edit' title='Edit'><i class='glyphicon glyphicon-pencil'></i></a>
                <a target='_blank' href='index.php?page=vou_print&id=".$data['id']."' class='btn-action btn-action-pdf' title='PDF'>PDF</a>
            </td>
        </tr>";
    }
else:
?>
        <tr>
            <td colspan="6">
                <div class="empty-state">
                    <i class="glyphicon glyphicon-inbox"></i>
                    <p><?=$xml->novouchers ?? 'No vouchers found'?></p>
                    <a href="?page=voc_make" class="btn btn-search" style="margin-top:10px;"><i class="glyphicon glyphicon-plus"></i> <?=$xml->create ?? 'Create'?> <?=$xml->voucher ?? 'Voucher'?></a>
                </div>
            </td>
        </tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /voucher-container -->