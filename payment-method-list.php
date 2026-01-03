<?php
/**
 * Payment Method List Page
 * CRUD operations for managing payment methods
 */

require_once("inc/class.company_filter.php");
$companyFilter = CompanyFilter::getInstance();
$companyCondition = $companyFilter->hasCompany() ? "company_id = " . $companyFilter->getSafeCompanyId() : "1=1";

// Handle delete action
if(isset($_GET['del'])) {
    $del_id = intval($_GET['del']);
    mysqli_query($db->conn, "DELETE FROM payment_method WHERE id = $del_id AND $companyCondition");
    echo "<script>window.location.href='index.php?page=payment_method_list';</script>";
    exit;
}

// Handle toggle active status
if(isset($_GET['toggle'])) {
    $toggle_id = intval($_GET['toggle']);
    mysqli_query($db->conn, "UPDATE payment_method SET is_active = NOT is_active WHERE id = $toggle_id AND $companyCondition");
    echo "<script>window.location.href='index.php?page=payment_method_list';</script>";
    exit;
}

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($db->conn, $_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
// Add company filter
$where_conditions[] = $companyCondition;

if($search) {
    $where_conditions[] = "(code LIKE '%$search%' OR name LIKE '%$search%' OR name_th LIKE '%$search%')";
}
if($filter_type !== '') {
    $where_conditions[] = "is_gateway = " . intval($filter_type);
}
if($filter_status !== '') {
    $where_conditions[] = "is_active = " . intval($filter_status);
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get statistics (with company filter)
$stats_total = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM payment_method WHERE $companyCondition"))['cnt'];
$stats_active = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM payment_method WHERE $companyCondition AND is_active = 1"))['cnt'];
$stats_inactive = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM payment_method WHERE $companyCondition AND is_active = 0"))['cnt'];
$stats_gateway = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM payment_method WHERE $companyCondition AND is_gateway = 1"))['cnt'];

// Get payment methods
$query = mysqli_query($db->conn, "SELECT * FROM payment_method $where_clause ORDER BY sort_order ASC, id ASC");
?>

<style>
.payment-method-container {
    padding: 20px;
    background: #f5f6fa;
    min-height: 100vh;
}

.page-header-pm {
    background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3);
}

.page-header-pm h2 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 600;
}

.page-header-pm p {
    margin: 0;
    opacity: 0.9;
}

.header-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-add-new {
    background: white;
    color: #8e44ad;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add-new:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    color: #8e44ad;
    text-decoration: none;
}

/* Stats Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: white;
}

.stat-icon.total { background: linear-gradient(135deg, #8e44ad, #9b59b6); }
.stat-icon.active { background: linear-gradient(135deg, #27ae60, #2ecc71); }
.stat-icon.inactive { background: linear-gradient(135deg, #e74c3c, #c0392b); }
.stat-icon.gateway { background: linear-gradient(135deg, #3498db, #2980b9); }

.stat-info h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.stat-info p {
    margin: 0;
    color: #7f8c8d;
    font-size: 13px;
}

/* Filter Card */
.filter-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
    font-size: 13px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.filter-group input:focus,
.filter-group select:focus {
    border-color: #8e44ad;
    outline: none;
    box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
}

.btn-filter {
    background: #8e44ad;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-filter:hover {
    background: #7d3c98;
}

.btn-reset {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-reset:hover {
    background: #7f8c8d;
    color: white;
    text-decoration: none;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table-card .card-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    color: #2c3e50;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
}

.payment-table th {
    background: #fafbfc;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    font-size: 13px;
    text-transform: uppercase;
    border-bottom: 2px solid #eee;
}

.payment-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.payment-table tr:hover {
    background: #f8f9fa;
}

.payment-table tr:last-child td {
    border-bottom: none;
}

/* Payment Method Icon Preview */
.icon-preview {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.icon-preview.gateway {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.method-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.method-details h4 {
    margin: 0 0 3px 0;
    font-size: 15px;
    font-weight: 600;
    color: #2c3e50;
}

.method-details .code {
    font-size: 12px;
    color: #7f8c8d;
    font-family: monospace;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}

/* Badges */
.badge-type {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-type.standard {
    background: #e8f5e9;
    color: #27ae60;
}

.badge-type.gateway {
    background: #e3f2fd;
    color: #2196f3;
}

.badge-status {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-status.active {
    background: #d4edda;
    color: #155724;
}

.badge-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    text-decoration: none;
    font-size: 14px;
}

.btn-action.edit {
    background: #fff3cd;
    color: #856404;
}

.btn-action.edit:hover {
    background: #ffc107;
    color: white;
}

.btn-action.toggle {
    background: #d1ecf1;
    color: #0c5460;
}

.btn-action.toggle:hover {
    background: #17a2b8;
    color: white;
}

.btn-action.delete {
    background: #f8d7da;
    color: #721c24;
}

.btn-action.delete:hover {
    background: #dc3545;
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.empty-state i {
    font-size: 60px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h4 {
    margin: 0 0 10px 0;
    color: #555;
}

/* Responsive */
@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
}
</style>

<div class="payment-method-container">
    <!-- Page Header -->
    <div class="page-header-pm">
        <div class="row">
            <div class="col-md-8">
                <h2><i class="fa fa-credit-card-alt"></i> <?=$xml->paymentmethods ?? 'Payment Methods'?></h2>
                <p><?=$xml->managepaymentmethods ?? 'Manage all payment methods for your business'?></p>
            </div>
            <div class="col-md-4 text-right">
                <a href="index.php?page=payment_method&mode=A" class="btn-add-new">
                    <i class="fa fa-plus"></i> <?=$xml->addnew ?? 'Add New'?>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fa fa-list"></i>
            </div>
            <div class="stat-info">
                <h3><?=$stats_total?></h3>
                <p><?=$xml->totalmethods ?? 'Total Methods'?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?=$stats_active?></h3>
                <p><?=$xml->activemethods ?? 'Active'?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon inactive">
                <i class="fa fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?=$stats_inactive?></h3>
                <p><?=$xml->inactivemethods ?? 'Inactive'?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gateway">
                <i class="fa fa-globe"></i>
            </div>
            <div class="stat-info">
                <h3><?=$stats_gateway?></h3>
                <p><?=$xml->paymentgateways ?? 'Payment Gateways'?></p>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="get" action="index.php">
            <input type="hidden" name="page" value="payment_method_list">
            <div class="filter-row">
                <div class="filter-group" style="flex: 2;">
                    <label><?=$xml->search ?? 'Search'?></label>
                    <input type="text" name="search" placeholder="<?=$xml->searchbynameorcode ?? 'Search by name or code...'?>" value="<?=htmlspecialchars($search)?>">
                </div>
                <div class="filter-group">
                    <label><?=$xml->type ?? 'Type'?></label>
                    <select name="type">
                        <option value=""><?=$xml->alltypes ?? 'All Types'?></option>
                        <option value="0" <?=$filter_type==='0'?'selected':''?>><?=$xml->standard ?? 'Standard'?></option>
                        <option value="1" <?=$filter_type==='1'?'selected':''?>><?=$xml->paymentgateway ?? 'Payment Gateway'?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?=$xml->status ?? 'Status'?></label>
                    <select name="status">
                        <option value=""><?=$xml->allstatus ?? 'All Status'?></option>
                        <option value="1" <?=$filter_status==='1'?'selected':''?>><?=$xml->active ?? 'Active'?></option>
                        <option value="0" <?=$filter_status==='0'?'selected':''?>><?=$xml->inactive ?? 'Inactive'?></option>
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fa fa-search"></i> <?=$xml->filter ?? 'Filter'?>
                </button>
                <a href="index.php?page=payment_method_list" class="btn-reset">
                    <i class="fa fa-refresh"></i> <?=$xml->reset ?? 'Reset'?>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="card-header">
            <i class="fa fa-list"></i> <?=$xml->paymentmethodlist ?? 'Payment Method List'?>
        </div>
        
        <?php if(mysqli_num_rows($query) > 0): ?>
        <table class="payment-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th><?=$xml->paymentmethod ?? 'Payment Method'?></th>
                    <th><?=$xml->thainame ?? 'Thai Name'?></th>
                    <th style="width: 120px;"><?=$xml->type ?? 'Type'?></th>
                    <th style="width: 100px;"><?=$xml->status ?? 'Status'?></th>
                    <th style="width: 80px;"><?=$xml->order ?? 'Order'?></th>
                    <th style="width: 130px;"><?=$xml->actions ?? 'Actions'?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = mysqli_fetch_assoc($query)): 
                ?>
                <tr>
                    <td><?=$no++?></td>
                    <td>
                        <div class="method-info">
                            <div class="icon-preview <?=$row['is_gateway'] ? 'gateway' : ''?>">
                                <i class="fa <?=e($row['icon'])?>"></i>
                            </div>
                            <div class="method-details">
                                <h4><?=e($row['name'])?></h4>
                                <span class="code"><?=e($row['code'])?></span>
                            </div>
                        </div>
                    </td>
                    <td><?=e($row['name_th'] ?: '-')?></td>
                    <td>
                        <span class="badge-type <?=$row['is_gateway'] ? 'gateway' : 'standard'?>">
                            <?=$row['is_gateway'] ? ($xml->gateway ?? 'Gateway') : ($xml->standard ?? 'Standard')?>
                        </span>
                    </td>
                    <td>
                        <span class="badge-status <?=$row['is_active'] ? 'active' : 'inactive'?>">
                            <?=$row['is_active'] ? ($xml->active ?? 'Active') : ($xml->inactive ?? 'Inactive')?>
                        </span>
                    </td>
                    <td style="text-align: center;"><?=$row['sort_order']?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="index.php?page=payment_method&mode=E&id=<?=$row['id']?>" class="btn-action edit" title="<?=$xml->edit ?? 'Edit'?>">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <a href="index.php?page=payment_method_list&toggle=<?=$row['id']?>" class="btn-action toggle" title="<?=$xml->togglestatus ?? 'Toggle Status'?>">
                                <i class="fa fa-power-off"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?=$row['id']?>, '<?=e($row['name'])?>')" class="btn-action delete" title="<?=$xml->delete ?? 'Delete'?>">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-credit-card-alt"></i>
            <h4><?=$xml->nopaymentmethods ?? 'No payment methods found'?></h4>
            <p><?=$xml->addyourfirstpaymentmethod ?? 'Add your first payment method to get started'?></p>
            <a href="index.php?page=payment_method&mode=A" class="btn-add-new" style="margin-top: 15px;">
                <i class="fa fa-plus"></i> <?=$xml->addpaymentmethod ?? 'Add Payment Method'?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if(confirm('<?=$xml->confirmdelete ?? "Are you sure you want to delete"?> "' + name + '"?')) {
        window.location.href = 'index.php?page=payment_method_list&del=' + id;
    }
}
</script>
