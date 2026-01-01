<?php
// Dashboard page for iacc system - REAL DATA WIRED
// Pulls actual data from database tables

$cur_date = date('Y-m-d');
$month_start = date('Y-m-01');

// ============ FETCH KPI DATA FROM DATABASE ============

// Sales Today - from pay table (payment volume)
$sql_today = "SELECT IFNULL(SUM(pay.volumn), 0) as total FROM pay 
              WHERE DATE(pay.date) = '$cur_date'";
$result_today = mysqli_query($db->conn, $sql_today);
$row_today = mysqli_fetch_assoc($result_today);
$sales_today = $row_today['total'] ?? 0;

// Sales This Month - from pay table
$sql_month = "SELECT IFNULL(SUM(pay.volumn), 0) as total FROM pay 
              WHERE DATE(pay.date) >= '$month_start' AND DATE(pay.date) <= '$cur_date'";
$result_month = mysqli_query($db->conn, $sql_month);
$row_month = mysqli_fetch_assoc($result_month);
$sales_month = $row_month['total'] ?? 0;

// Pending Purchase Orders - from purchase_order table (assuming status checking)
$sql_pending = "SELECT COUNT(purchase_order.id) as count FROM purchase_order WHERE purchase_order.over = 0";
$result_pending = mysqli_query($db->conn, $sql_pending);
$row_pending = mysqli_fetch_assoc($result_pending);
$pending_orders = $row_pending['count'] ?? 0;

// Total PO count
$sql_total_po = "SELECT COUNT(purchase_order.id) as count FROM purchase_order";
$result_total = mysqli_query($db->conn, $sql_total_po);
$row_total = mysqli_fetch_assoc($result_total);
$total_orders = $row_total['count'] ?? 0;

// Recent payments - from pay table
$sql_recent_pay = "SELECT pay.*, purchase_order.name, purchase_order.tax 
                   FROM pay 
                   LEFT JOIN purchase_order ON pay.purchase_order_id = purchase_order.id 
                   ORDER BY pay.date DESC LIMIT 5";
$recent_payments = mysqli_query($db->conn, $sql_recent_pay);

// Pending POs - from purchase_order table where over = 0
$sql_pending_po = "SELECT purchase_order.*, 
                   (SELECT SUM(volumn) FROM pay WHERE purchase_order_id = purchase_order.id) as paid_amount
                   FROM purchase_order 
                   WHERE purchase_order.over = 0 
                   ORDER BY purchase_order.date DESC LIMIT 5";
$pending_pos = mysqli_query($db->conn, $sql_pending_po);

// Completed orders this month
$sql_completed = "SELECT COUNT(purchase_order.id) as count FROM purchase_order 
                  WHERE purchase_order.over = 1 AND DATE(purchase_order.date) >= '$month_start'";
$result_completed = mysqli_query($db->conn, $sql_completed);
$row_completed = mysqli_fetch_assoc($result_completed);
$completed_orders = $row_completed['count'] ?? 0;

// Invoice statistics
$sql_invoices = "SELECT COUNT(DISTINCT invoice.tex) as count FROM invoice 
                 WHERE DATE(invoice.createdate) >= '$month_start'";
$result_invoices = mysqli_query($db->conn, $sql_invoices);
$row_invoices = mysqli_fetch_assoc($result_invoices);
$total_invoices = $row_invoices['count'] ?? 0;

// Tax Invoice statistics
$sql_tax_invoices = "SELECT COUNT(DISTINCT invoice.texiv) as count FROM invoice 
                     WHERE invoice.texiv > 0 AND DATE(invoice.texiv_create) >= '$month_start'";
$result_tax_inv = mysqli_query($db->conn, $sql_tax_invoices);
$row_tax_inv = mysqli_fetch_assoc($result_tax_inv);
$total_tax_invoices = $row_tax_inv['count'] ?? 0;

// Recent invoices
$sql_recent_inv = "SELECT invoice.*, company.name_en FROM invoice 
                   LEFT JOIN company ON invoice.customer_id = company.id
                   ORDER BY invoice.createdate DESC LIMIT 5";
$recent_invoices = mysqli_query($db->conn, $sql_recent_inv);

function format_currency($amount) {
    return '฿' . number_format($amount, 2);
}

function get_status_badge($status) {
    if ($status == 0) {
        return '<span class="badge" style="background: #ffc107; color: black;">Active</span>';
    } else if ($status == 1) {
        return '<span class="badge" style="background: #28a745; color: white;">Completed</span>';
    }
    return '<span class="badge" style="background: #6c757d; color: white;">Unknown</span>';
}
<style>
    /* Dashboard Page Styles */
    .dashboard-wrapper {
        padding: 20px;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        color: white;
    }

    .dashboard-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }

    .dashboard-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 5px;
    }

    .kpi-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid #667eea;
    }

    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .kpi-card.alert {
        border-left-color: #ff6b6b;
    }

    .kpi-card.success {
        border-left-color: #51cf66;
    }

    .kpi-card.warning {
        border-left-color: #ffd43b;
    }

    .kpi-icon {
        font-size: 28px;
        margin-bottom: 10px;
        display: inline-block;
    }

    .kpi-icon.primary {
        color: #667eea;
    }

    .kpi-icon.success {
        color: #51cf66;
    }

    .kpi-icon.warning {
        color: #ffd43b;
    }

    .kpi-icon.danger {
        color: #ff6b6b;
    }

    .kpi-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .kpi-change {
        font-size: 12px;
        color: #6c757d;
    }

    .content-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
        color: #2c3e50;
        padding-bottom: 12px;
        border-bottom: 2px solid #667eea;
    }

    .card-title i {
        margin-right: 10px;
        color: #667eea;
    }

    .table-responsive {
        border-radius: 6px;
        overflow: hidden;
    }

    .table {
        margin-bottom: 0;
        font-size: 13px;
    }

    .table thead th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        border: none;
        padding: 12px;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        padding: 12px;
        border-color: #e9ecef;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .quick-link {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 6px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        margin-bottom: 8px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
    }

    .quick-link:hover {
        transform: translateX(3px);
        color: white;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3);
    }

    .quick-link i {
        font-size: 20px;
        margin-right: 12px;
        min-width: 25px;
        text-align: center;
    }

    .quick-link-text {
        flex: 1;
        font-weight: 600;
        font-size: 14px;
    }

    .empty-state {
        text-align: center;
        padding: 30px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 40px;
        margin-bottom: 12px;
        color: #dee2e6;
    }

    .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    .kpi-row {
        margin-bottom: 20px;
    }

    .stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 12px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-success {
        background-color: #51cf66;
        color: white;
    }

    .badge-warning {
        background-color: #ffc107;
        color: #333;
    }

    .action-btn {
        padding: 4px 8px;
        margin: 0 2px;
        font-size: 11px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #667eea;
        color: white;
    }

    .action-btn:hover {
        background: #764ba2;
    }
</style>

<div class="dashboard-wrapper">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title"><i class="fa fa-tachometer-alt"></i> Dashboard</h2>
            <div class="dashboard-subtitle">Welcome back! Here's your business overview</div>
        </div>
        <div style="text-align: right;">
            <small><?php echo date('l, F j, Y'); ?></small>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row kpi-row">
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card">
                <div class="kpi-icon primary">
                    <i class="fa fa-dollar-sign"></i>
                </div>
                <div class="kpi-label">Sales Today</div>
                <div class="kpi-value"><?php echo format_currency($sales_today); ?></div>
                <div class="kpi-change">
                    <i class="fa fa-arrow-up"></i> Last 24 hours
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card success">
                <div class="kpi-icon success">
                    <i class="fa fa-chart-line"></i>
                </div>
                <div class="kpi-label">Month Sales</div>
                <div class="kpi-value"><?php echo format_currency($sales_month); ?></div>
                <div class="kpi-change">
                    Current month
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card warning">
                <div class="kpi-icon warning">
                    <i class="fa fa-hourglass-half"></i>
                </div>
                <div class="kpi-label">Pending Orders</div>
                <div class="kpi-value"><?php echo $pending_orders; ?></div>
                <div class="kpi-change">
                    <?php if($pending_orders > 0): ?>
                        <span class="badge badge-warning">Action needed</span>
                    <?php else: ?>
                        <span class="badge badge-success">All clear</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card alert">
                <div class="kpi-icon danger">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?php echo $total_orders; ?></div>
                <div class="kpi-change">
                    All-time
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice KPI Cards Row -->
    <div class="row kpi-row">
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card" style="border-left: 4px solid #4caf50;">
                <div class="kpi-icon" style="color: #4caf50;">
                    <i class="fa fa-file-invoice"></i>
                </div>
                <div class="kpi-label">Invoices (This Month)</div>
                <div class="kpi-value"><?php echo $total_invoices; ?></div>
                <div class="kpi-change">
                    Customer invoices
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card" style="border-left: 4px solid #2196f3;">
                <div class="kpi-icon" style="color: #2196f3;">
                    <i class="fa fa-receipt"></i>
                </div>
                <div class="kpi-label">Tax Invoices (This Month)</div>
                <div class="kpi-value"><?php echo $total_tax_invoices; ?></div>
                <div class="kpi-change">
                    Tax documents issued
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Recent Payments -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-receipt"></i> Recent Payments
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>PO #</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_payments && mysqli_num_rows($recent_payments) > 0): ?>
                                <?php while($payment = mysqli_fetch_assoc($recent_payments)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment['date'])); ?></td>
                                    <td><?php echo $payment['purchase_order_id'] . ' - ' . substr($payment['name'] ?? 'N/A', 0, 15); ?></td>
                                    <td><?php echo format_currency($payment['volumn']); ?></td>
                                    <td><?php echo $payment['value'] ?? 'Direct'; ?></td>
                                    <td>
                                        <button class="action-btn">View</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fa fa-inbox"></i>
                                            <p>No payments recorded yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Purchase Orders -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-shopping-cart"></i> Active Purchase Orders
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>PO ID</th>
                                <th>Name</th>
                                <th>Tax ID</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($pending_pos && mysqli_num_rows($pending_pos) > 0): ?>
                                <?php while($po = mysqli_fetch_assoc($pending_pos)): ?>
                                <tr>
                                    <td><?php echo $po['po_id_new'] ?? $po['id']; ?></td>
                                    <td><?php echo substr($po['name'], 0, 20); ?></td>
                                    <td><?php echo $po['tax']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($po['date'])); ?></td>
                                    <td><?php echo get_status_badge($po['over']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fa fa-check-circle"></i>
                                            <p>✓ All orders completed</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-file-invoice"></i> Recent Invoices
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Tax ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_invoices && mysqli_num_rows($recent_invoices) > 0): ?>
                                <?php while($invoice = mysqli_fetch_assoc($recent_invoices)): ?>
                                <tr>
                                    <td><?php echo $invoice['tex'] ?? 'N/A'; ?></td>
                                    <td><?php echo substr($invoice['name_en'] ?? 'N/A', 0, 18); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['createdate'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_text = ($invoice['status_iv'] == 1) ? 'Approved' : 'Pending';
                                        $status_color = ($invoice['status_iv'] == 1) ? '#28a745' : '#ffc107';
                                        <span class="badge" style="background: <?php echo $status_color; ?>; color: <?php echo ($invoice['status_iv'] == 1) ? 'white' : '#333'; ?>;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fa fa-file"></i>
                                            <p>No invoices found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Tax Invoices -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-receipt"></i> Tax Invoices Issued
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Tax Inv ID</th>
                                <th>Customer</th>
                                <th>Created</th>
                                <th>Mailed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Re-query for tax invoices with actual data
                            $sql_tax_inv_detail = "SELECT invoice.*, company.name_en FROM invoice 
                                                  LEFT JOIN company ON invoice.customer_id = company.id
                                                  WHERE invoice.texiv > 0 
                                                  ORDER BY invoice.texiv_create DESC LIMIT 5";
                            $tax_inv_results = mysqli_query($db->conn, $sql_tax_inv_detail);
                            <?php if($tax_inv_results && mysqli_num_rows($tax_inv_results) > 0): ?>
                                <?php while($tax_inv = mysqli_fetch_assoc($tax_inv_results)): ?>
                                <tr>
                                    <td><?php echo $tax_inv['texiv'] ?? 'N/A'; ?></td>
                                    <td><?php echo substr($tax_inv['name_en'] ?? 'N/A', 0, 18); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($tax_inv['texiv_create'])); ?></td>
                                    <td>
                                        <?php echo ($tax_inv['countmailtax'] > 0) ? 'Yes (' . $tax_inv['countmailtax'] . ')' : '-'; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fa fa-receipt"></i>
                                            <p>No tax invoices issued yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Quick Links -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-bolt"></i> Quick Links
                </h5>
                <a href="index.php?page=rec" class="quick-link">
                    <i class="fa fa-receipt"></i>
                    <span class="quick-link-text">Payments</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=po_list" class="quick-link">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="quick-link-text">Purchase Orders</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=pr_list" class="quick-link">
                    <i class="fa fa-clipboard-list"></i>
                    <span class="quick-link-text">Requests</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=report" class="quick-link">
                    <i class="fa fa-chart-bar"></i>
                    <span class="quick-link-text">Reports</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
            </div>

            <!-- System Info -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-info-circle"></i> System Stats
                </h5>
                <div style="font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Total Orders:</strong></span>
                        <span><?php echo $total_orders; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Completed:</strong></span>
                        <span><?php echo $completed_orders; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span><strong>Updated:</strong></span>
                        <span><?php echo date('H:i:s'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Order Stats -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-chart-pie"></i> This Month
                </h5>
                <div class="row" style="margin: 0;">
                    <div class="col-md-6">
                        <div class="stat-box">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value"><?php echo $completed_orders; ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-box" style="background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value"><?php echo $pending_orders; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
