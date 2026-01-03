<?php
// Security already checked in index.php
require_once("inc/payment-method-helper.php");
$com_id = sql_int($_SESSION['com_id']);

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (r.name LIKE '%$search_escaped%' OR r.email LIKE '%$search_escaped%' OR r.phone LIKE '%$search_escaped%' OR r.rep_rw LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND r.createdate >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND r.createdate <= '$date_to'";
}

// Status filter
$status_cond = '';
if (!empty($status_filter)) {
    $status_cond = " AND r.status = '".sql_escape($status_filter)."'";
}

// Get statistics
$stats_total = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM receipt WHERE vender='".$com_id."'"))['cnt'];
$stats_confirmed = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM receipt WHERE vender='".$com_id."' AND status='confirmed'"))['cnt'];
$stats_draft = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM receipt WHERE vender='".$com_id."' AND status='draft'"))['cnt'];
$stats_cancelled = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM receipt WHERE vender='".$com_id."' AND status='cancelled'"))['cnt'];

// Get payment method labels
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$payment_labels_with_icons = getPaymentMethodLabelsWithIcons($db->conn, $lang);
?>

<style>
/* Modern Receipt List Styling - Green Theme */
.receipt-container { max-width: 1400px; margin: 0 auto; }

.page-header-rep {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    color: #fff;
    padding: 25px 30px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(39,174,96,0.3);
}
.page-header-rep h2 { margin: 0; font-size: 26px; font-weight: 600; }
.page-header-rep p { margin: 5px 0 0 0; opacity: 0.9; font-size: 14px; }
.btn-create {
    background: #fff;
    color: #27ae60;
    padding: 12px 25px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-create:hover { background: #f8f9fa; color: #27ae60; transform: translateY(-2px); }

/* Stats Cards */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
.stat-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
}
.stat-icon.total { background: linear-gradient(135deg, #27ae60, #2ecc71); }
.stat-icon.confirmed { background: linear-gradient(135deg, #27ae60, #2ecc71); }
.stat-icon.draft { background: linear-gradient(135deg, #f39c12, #f1c40f); }
.stat-icon.cancelled { background: linear-gradient(135deg, #e74c3c, #c0392b); }
.stat-info h3 { margin: 0; font-size: 24px; font-weight: 700; color: #2c3e50; }
.stat-info p { margin: 0; color: #7f8c8d; font-size: 13px; }

/* Filter Card */
.filter-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
.filter-group { flex: 1; min-width: 150px; }
.filter-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #555; font-size: 13px; }
.filter-group input, .filter-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}
.filter-group input:focus, .filter-group select:focus { border-color: #27ae60; outline: none; box-shadow: 0 0 0 3px rgba(39,174,96,0.1); }
.btn-search { background: #27ae60; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; }
.btn-search:hover { background: #219a52; }
.btn-reset { background: #95a5a6; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; text-decoration: none; }
.btn-reset:hover { background: #7f8c8d; color: #fff; }

/* Table Card */
.receipt-table-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.receipt-table-card .card-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    color: #2c3e50;
}
.receipt-table { width: 100%; border-collapse: collapse; }
.receipt-table th {
    background: #fafbfc;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    font-size: 12px;
    text-transform: uppercase;
    border-bottom: 2px solid #eee;
}
.receipt-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.receipt-table tr:hover { background: #f8f9fa; }
.receipt-table tr:last-child td { border-bottom: none; }

.rec-number { font-weight: 600; color: #27ae60; }
.customer-name { font-weight: 500; color: #2c3e50; }
.invoice-link a { color: #3498db; text-decoration: none; }
.invoice-link a:hover { text-decoration: underline; }

/* Action Buttons */
.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s;
    margin-right: 5px;
    font-size: 13px;
}
.btn-action-view { background: #e3f2fd; color: #1976d2; }
.btn-action-view:hover { background: #1976d2; color: #fff; }
.btn-action-edit { background: #fff3e0; color: #f57c00; }
.btn-action-edit:hover { background: #f57c00; color: #fff; }
.btn-action-pdf { background: #d4edda; color: #27ae60; padding: 6px 12px; width: auto; font-size: 11px; font-weight: 600; }
.btn-action-pdf:hover { background: #27ae60; color: #fff; }

/* Empty State */
.empty-state { text-align: center; padding: 60px 20px; color: #7f8c8d; }
.empty-state i { font-size: 50px; margin-bottom: 15px; opacity: 0.5; }

@media (max-width: 992px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .stats-row { grid-template-columns: 1fr; } .filter-row { flex-direction: column; } }
</style>

<div class="receipt-container">

<!-- Page Header -->
<div class="page-header-rep">
    <div>
        <h2><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt ?? 'Receipts'?></h2>
        <p><?=$xml->managereceipts ?? 'Manage all receipts and payment records'?></p>
    </div>
    <a href="?page=rep_make" class="btn-create"><i class="glyphicon glyphicon-plus"></i> <?=$xml->create ?? 'Create'?> <?=$xml->receipt ?? 'Receipt'?></a>
</div>

<!-- Stats Row -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon total"><i class="glyphicon glyphicon-list-alt"></i></div>
        <div class="stat-info">
            <h3><?=$stats_total?></h3>
            <p><?=$xml->totalreceipts ?? 'Total Receipts'?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon confirmed"><i class="glyphicon glyphicon-ok-circle"></i></div>
        <div class="stat-info">
            <h3><?=$stats_confirmed?></h3>
            <p><?=$xml->confirmed ?? 'Confirmed'?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon draft"><i class="glyphicon glyphicon-edit"></i></div>
        <div class="stat-info">
            <h3><?=$stats_draft?></h3>
            <p><?=$xml->draft ?? 'Draft'?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cancelled"><i class="glyphicon glyphicon-remove-circle"></i></div>
        <div class="stat-info">
            <h3><?=$stats_cancelled?></h3>
            <p><?=$xml->cancelled ?? 'Cancelled'?></p>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <form method="get" action="">
        <input type="hidden" name="page" value="receipt_list">
        <div class="filter-row">
            <div class="filter-group" style="flex:2;">
                <label><?=$xml->search ?? 'Search'?></label>
                <input type="text" name="search" placeholder="<?=$xml->searchbyname ?? 'Search by name, email, receipt#...'?>" value="<?=htmlspecialchars($search)?>">
            </div>
            <div class="filter-group">
                <label><?=$xml->status ?? 'Status'?></label>
                <select name="status">
                    <option value=""><?=$xml->allstatus ?? 'All Status'?></option>
                    <option value="confirmed" <?=$status_filter=='confirmed'?'selected':''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                    <option value="draft" <?=$status_filter=='draft'?'selected':''?>><?=$xml->draft ?? 'Draft'?></option>
                    <option value="cancelled" <?=$status_filter=='cancelled'?'selected':''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
                </select>
            </div>
            <div class="filter-group">
                <label><?=$xml->from ?? 'From'?></label>
                <input type="date" name="date_from" value="<?=htmlspecialchars($date_from)?>">
            </div>
            <div class="filter-group">
                <label><?=$xml->to ?? 'To'?></label>
                <input type="date" name="date_to" value="<?=htmlspecialchars($date_to)?>">
            </div>
            <button type="submit" class="btn-search"><i class="glyphicon glyphicon-search"></i> <?=$xml->filter ?? 'Filter'?></button>
            <a href="?page=receipt_list" class="btn-reset"><i class="glyphicon glyphicon-refresh"></i> <?=$xml->reset ?? 'Reset'?></a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="receipt-table-card">
    <div class="card-header"><i class="glyphicon glyphicon-list"></i> <?=$xml->receiptlist ?? 'Receipt List'?></div>
    <table class="receipt-table">
        <thead>
            <tr>
                <th style="width:120px;"><?=$xml->receiptno ?? 'Receipt #'?></th>
                <th><?=$xml->customer ?? 'Customer'?></th>
                <th style="width:120px;"><?=$xml->invoicelink ?? 'Invoice'?></th>
                <th style="width:120px;"><?=$xml->paymentmethod ?? 'Payment'?></th>
                <th style="width:80px;"><?=$xml->source ?? 'Source'?></th>
                <th style="width:90px;"><?=$xml->status ?? 'Status'?></th>
                <th style="width:100px;"><?=$xml->createdate ?? 'Date'?></th>
                <th style="width:140px;"><?=$xml->actions ?? 'Actions'?></th>
            </tr>
        </thead>
        <tbody>
<?php
$query = mysqli_query($db->conn, "SELECT r.id, r.name, r.email, r.phone, DATE_FORMAT(r.createdate,'%d-%m-%Y') as createdate, r.description, r.rep_rw, r.brand, r.vender, r.payment_method, r.status, r.invoice_id, r.payment_source, r.payment_transaction_id, c.inv_rw as invoice_no FROM receipt r LEFT JOIN complain c ON r.invoice_id = c.id WHERE r.vender='".$com_id."' $search_cond $date_cond $status_cond ORDER BY r.id DESC");

$count = mysqli_num_rows($query);
if($count > 0):
    while($data = mysqli_fetch_array($query)){
        // Payment method display
        $payment_display = $payment_labels_with_icons[$data['payment_method']] ?? getPaymentMethodDisplayName($db->conn, $data['payment_method'], $lang);
        
        // Payment source badge
        $source = $data['payment_source'] ?? 'manual';
        $source_badge = '';
        switch($source) {
            case 'paypal':
                $source_badge = '<span class="label" style="background:#003087;color:#fff;"><i class="fa fa-paypal"></i> PayPal</span>';
                break;
            case 'stripe':
                $source_badge = '<span class="label" style="background:#635bff;color:#fff;"><i class="fa fa-cc-stripe"></i> Stripe</span>';
                break;
            default:
                $source_badge = '<span class="label label-default"><i class="fa fa-user"></i> Manual</span>';
        }
        
        // Status badge
        $status_class = '';
        $status_label = '';
        switch($data['status']) {
            case 'draft': $status_class = 'warning'; $status_label = $xml->draft ?? 'Draft'; break;
            case 'confirmed': $status_class = 'success'; $status_label = $xml->confirmed ?? 'Confirmed'; break;
            case 'cancelled': $status_class = 'danger'; $status_label = $xml->cancelled ?? 'Cancelled'; break;
            default: $status_class = 'success'; $status_label = $xml->confirmed ?? 'Confirmed';
        }
        
        // Invoice link - also check iv table
        $invoice_display = '-';
        if(!empty($data['invoice_id'])) {
            if(!empty($data['invoice_no'])) {
                $invoice_display = '<a href="?page=compl_view&id='.e($data['invoice_id']).'">INV-'.e($data['invoice_no']).'</a>';
            } else {
                // Invoice from iv table
                $invoice_display = '<a href="inv.php?id='.e($data['invoice_id']).'" target="_blank">INV-'.e($data['invoice_id']).'</a>';
            }
        }
        
        echo "<tr>
            <td class='rec-number'>REC-".e($data['rep_rw'])."</td>
            <td class='customer-name'>".e($data['name'])."</td>
            <td class='invoice-link'>".$invoice_display."</td>
            <td>".$payment_display."</td>
            <td>".$source_badge."</td>
            <td><span class='label label-".$status_class."'>".$status_label."</span></td>
            <td>".e($data['createdate'])."</td>
            <td>
                <a href='?page=rep_view&id=".e($data['id'])."' class='btn-action btn-action-view' title='View'><i class='glyphicon glyphicon-eye-open'></i></a>
                <a href='?page=rep_make&id=".e($data['id'])."' class='btn-action btn-action-edit' title='Edit'><i class='glyphicon glyphicon-pencil'></i></a>
                <a href='index.php?page=rep_print&id=".e($data['id'])."' target='_blank' class='btn-action btn-action-pdf' title='PDF'>PDF</a>
            </td>
        </tr>";
    }
else:
?>
        <tr>
            <td colspan="8">
                <div class="empty-state">
                    <i class="glyphicon glyphicon-inbox"></i>
                    <p><?=$xml->noreceipts ?? 'No receipts found'?></p>
                    <a href="?page=rep_make" class="btn-search" style="margin-top:10px;"><i class="glyphicon glyphicon-plus"></i> <?=$xml->create ?? 'Create'?> <?=$xml->receipt ?? 'Receipt'?></a>
                </div>
            </td>
        </tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /receipt-container -->