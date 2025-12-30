<?php
// Dashboard page for iacc system
// Simplified version with mock data for now

$com_id = $_SESSION['com_id'] ?? null;
$cur_date = date('Y-m-d');

// Initialize KPI data with mock values
$kpi_data = [
    'sales_today' => 15500.00,
    'sales_month' => 125800.50,
    'pending_orders' => 3,
    'low_stock' => 2
];

// Mock recent receipts
$recent_receipts_data = [
    ['date' => '2025-12-30', 'company' => 'ABC Company', 'amount' => 5000, 'method' => 'Cash'],
    ['date' => '2025-12-29', 'company' => 'XYZ Corp', 'amount' => 3500, 'method' => 'Bank Transfer'],
    ['date' => '2025-12-28', 'company' => 'DEF Ltd', 'amount' => 7000, 'method' => 'Check'],
];

// Mock pending POs
$pending_pos_data = [
    ['id' => 'PO-001', 'vendor' => 'Supplier A', 'amount' => 12000, 'status' => '0'],
    ['id' => 'PO-002', 'vendor' => 'Supplier B', 'amount' => 8500, 'status' => '1'],
];

function format_currency($amount) {
    return '฿' . number_format($amount, 2);
}

function get_status_badge($status) {
    $badges = [
        '0' => '<span class="badge" style="background: #ffc107; color: black;">Pending</span>',
        '1' => '<span class="badge" style="background: #17a2b8; color: white;">Processing</span>',
        '2' => '<span class="badge" style="background: #28a745; color: white;">Completed</span>',
        '3' => '<span class="badge" style="background: #6c757d; color: white;">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge" style="background: #6c757d; color: white;">Unknown</span>';
}
?>

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
        background-color: #ffd43b;
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
                <div class="kpi-value"><?php echo format_currency($kpi_data['sales_today']); ?></div>
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
                <div class="kpi-value"><?php echo format_currency($kpi_data['sales_month']); ?></div>
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
                <div class="kpi-value"><?php echo $kpi_data['pending_orders']; ?></div>
                <div class="kpi-change">
                    <span class="badge badge-warning">Action needed</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card alert">
                <div class="kpi-icon danger">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div class="kpi-label">Low Stock Items</div>
                <div class="kpi-value"><?php echo $kpi_data['low_stock']; ?></div>
                <div class="kpi-change">
                    Need attention
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Recent Receipts -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-receipt"></i> Recent Receipts
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Company</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_receipts_data as $receipt): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($receipt['date'])); ?></td>
                                <td><?php echo $receipt['company']; ?></td>
                                <td><?php echo format_currency($receipt['amount']); ?></td>
                                <td><?php echo $receipt['method']; ?></td>
                                <td>
                                    <button class="action-btn">View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Purchase Orders -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-shopping-cart"></i> Pending Purchase Orders
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_pos_data as $po): ?>
                            <tr>
                                <td><?php echo $po['id']; ?></td>
                                <td><?php echo $po['vendor']; ?></td>
                                <td><?php echo format_currency($po['amount']); ?></td>
                                <td><?php echo get_status_badge($po['status']); ?></td>
                                <td>
                                    <button class="action-btn">View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Links -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-bolt"></i> Quick Links
                </h5>
                <a href="index.php?page=rec" class="quick-link">
                    <i class="fa fa-receipt"></i>
                    <span class="quick-link-text">Receipts</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=po_list" class="quick-link">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="quick-link-text">Purchase Orders</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=deliv_list" class="quick-link">
                    <i class="fa fa-truck"></i>
                    <span class="quick-link-text">Deliveries</span>
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
                    <i class="fa fa-info-circle"></i> System Info
                </h5>
                <div style="font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Company:</strong></span>
                        <span><?php echo $_SESSION['com_name'] ?? 'N/A'; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Date:</strong></span>
                        <span><?php echo date('M d, Y'); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span><strong>Status:</strong></span>
                        <span><span class="badge badge-success">Connected</span></span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-bar-chart"></i> Quick Stats
                </h5>
                <div class="row" style="margin: 0;">
                    <div class="col-md-6">
                        <div class="stat-box">
                            <div class="stat-label">Total Orders</div>
                            <div class="stat-value">12</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-box" style="background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value">9</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
