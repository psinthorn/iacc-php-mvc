<?php
require_once("inc/security.php");

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (payment_name LIKE '%$search_escaped%' OR payment_des LIKE '%$search_escaped%')";
}

// Get statistics
$stats_total = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM payment WHERE com_id='".$_SESSION['com_id']."'"))['cnt'];
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/master-data.css">
<style>
.payment-list-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.page-header-payment {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: white;
    padding: 28px 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-header-payment .header-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.page-header-payment .header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.page-header-payment h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 700;
}

.page-header-payment .subtitle {
    margin: 4px 0 0;
    opacity: 0.9;
    font-size: 14px;
    font-weight: 400;
}

.page-header-payment .header-badge {
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid rgba(255,255,255,0.3);
}

.btn-add-payment {
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.btn-add-payment:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.filter-card-payment {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.filter-card-payment .card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 16px 24px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
}

.filter-card-payment .card-header i {
    color: #4f46e5;
}

.filter-card-payment .card-body {
    padding: 20px 24px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}

.filter-card-payment .form-control {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    height: 46px;
    padding: 10px 16px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s;
}

.filter-card-payment .form-control:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    outline: none;
}

.filter-card-payment .btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-card-payment .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.filter-card-payment .btn-default {
    background: #fff;
    border: 2px solid #e5e7eb;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-card-payment .btn-default:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.table-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.table-card .card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 16px 24px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
}

.table-card .card-header i {
    color: #4f46e5;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
}

.payment-table th {
    background: #f9fafb;
    padding: 14px 20px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.payment-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    color: #374151;
    font-size: 14px;
}

.payment-table tr:hover {
    background: #f9fafb;
}

.payment-table tr:last-child td {
    border-bottom: none;
}

.payment-name {
    font-weight: 600;
    color: #1f2937;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-action.edit {
    background: #fef3c7;
    color: #d97706;
}

.btn-action.edit:hover {
    background: #fbbf24;
    color: white;
    transform: translateY(-2px);
}

.btn-action.delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-action.delete:hover {
    background: #ef4444;
    color: white;
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h4 {
    margin: 0 0 8px;
    color: #374151;
    font-weight: 600;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}
</style>

<div class="payment-list-container">
    <!-- Page Header -->
    <div class="page-header-payment">
        <div class="header-content">
            <div class="header-icon">
                <i class="fa fa-credit-card"></i>
            </div>
            <div>
                <h2><?=$xml->payment ?? 'Payment Terms'?></h2>
                <p class="subtitle"><?=$xml->manage_payment_terms ?? 'Manage payment terms for invoices and orders'?></p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 16px;">
            <span class="header-badge">
                <i class="fa fa-list"></i> <?=$stats_total?> <?=$xml->total ?? 'Total'?>
            </span>
            <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'payment.php', true); return false;" class="btn-add-payment">
                <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?>
            </a>
        </div>
    </div>

    <!-- Search and Filter Panel -->
    <div class="filter-card-payment">
        <div class="card-header">
            <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        </div>
        <div class="card-body">
            <form method="get" action="" class="form-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
                <input type="hidden" name="page" value="payment">
                
                <div class="form-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> Payment Name, Description..." 
                           value="<?=htmlspecialchars($search)?>" style="width: 320px;">
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                <a href="?page=payment" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            </form>
        </div>
    </div>

    <div id="fetch_state"></div>

    <!-- Payment Table -->
    <div class="table-card">
        <div class="card-header">
            <i class="fa fa-list"></i> <?=$xml->payment_list ?? 'Payment Terms List'?>
        </div>
        
        <?php
        $query = mysqli_query($db->conn, "SELECT id, payment_name, payment_des FROM payment WHERE com_id='".$_SESSION['com_id']."' $search_cond ORDER BY id DESC");
        
        if (mysqli_num_rows($query) > 0): ?>
        <table class="payment-table">
            <thead>
                <tr>
                    <th><?=$xml->name ?? 'Name'?></th>
                    <th><?=$xml->description ?? 'Description'?></th>
                    <th width="120"><?=$xml->actions ?? 'Actions'?></th>
                </tr>
            </thead>
            <tbody>
                <?php while($data = mysqli_fetch_array($query)): ?>
                <tr>
                    <td><span class="payment-name"><?=e($data['payment_name'])?></span></td>
                    <td><?=e($data['payment_des'])?></td>
                    <td>
                        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'payment.php?id=<?=$data['id']?>', true); return false;" class="btn-action edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a onClick="return Conf(this)" href="#" class="btn-action delete" title="<?=$xml->delete ?? 'Delete'?>">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-credit-card"></i>
            <h4><?=$xml->no_payment_terms ?? 'No Payment Terms Found'?></h4>
            <p><?=$xml->no_payment_terms_desc ?? 'Create your first payment term to get started'?></p>
        </div>
        <?php endif; ?>
    </div>
</div>