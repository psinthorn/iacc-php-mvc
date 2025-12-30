<?php
// Dashboard page for iacc system
// This displays the modern dashboard with KPI cards and metrics
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

    .quick-link-arrow {
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

    .badge-danger {
        background-color: #ff6b6b;
        color: white;
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
                <div class="kpi-value">฿0.00</div>
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
                <div class="kpi-value">฿0.00</div>
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
                <div class="kpi-value">0</div>
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
                <div class="kpi-value">0</div>
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
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa fa-inbox"></i>
                                        <p>No receipts found</p>
                                    </div>
                                </td>
                            </tr>
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
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa fa-check-circle"></i>
                                        <p>✓ All orders processed!</p>
                                    </div>
                                </td>
                            </tr>
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
                    <i class="fa fa-chevron-right quick-link-arrow"></i>
                </a>
                <a href="index.php?page=pr_list" class="quick-link">
                    <i class="fa fa-clipboard-list"></i>
                    <span class="quick-link-text">Purchase Requests</span>
                    <i class="fa fa-chevron-right quick-link-arrow"></i>
                </a>
                <a href="index.php?page=po_list" class="quick-link">
                    <i class="fa fa-shopping-bag"></i>
                    <span class="quick-link-text">Purchase Orders</span>
                    <i class="fa fa-chevron-right quick-link-arrow"></i>
                </a>
                <a href="index.php?page=vou_list" class="quick-link">
                    <i class="fa fa-file-invoice"></i>
                    <span class="quick-link-text">Vouchers</span>
                    <i class="fa fa-chevron-right quick-link-arrow"></i>
                </a>
                <a href="index.php?page=deliv_list" class="quick-link">
                    <i class="fa fa-truck"></i>
                    <span class="quick-link-text">Deliveries</span>
                    <i class="fa fa-chevron-right quick-link-arrow"></i>
                </a>
            </div>

            <!-- System Info -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-info-circle"></i> System Info
                </h5>
                <div style="font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Date:</strong></span>
                        <span><?php echo date('M d, Y'); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Time:</strong></span>
                        <span><?php echo date('H:i:s'); ?></span>
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
                            <div class="stat-label">Total Invoices</div>
                            <div class="stat-value">0</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-box" style="background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Add some interactivity
    document.querySelectorAll('.kpi-card').forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'translateY(-3px)';
            }, 100);
        });
    });
</script>
