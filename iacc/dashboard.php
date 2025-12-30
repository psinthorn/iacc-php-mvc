<?php
// Dashboard page for iacc system
// This displays the modern dashboard with KPI cards and metrics
?>

<style>
    /* Dashboard Page Styles */
    .dashboard-wrapper {
        padding: 30px 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        margin-left: -15px;
        margin-right: -15px;
        margin-bottom: -15px;
    }

    .dashboard-title {
        color: white;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 30px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .kpi-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 5px solid #667eea;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
        font-size: 32px;
        margin-bottom: 10px;
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
        font-size: 14px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    .kpi-value {
        font-size: 32px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .kpi-change {
        font-size: 13px;
        color: #6c757d;
    }

    .content-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .card-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #2c3e50;
        padding-bottom: 15px;
        border-bottom: 2px solid #667eea;
    }

    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }

    .table thead th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        border: none;
        padding: 15px;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        padding: 15px;
        border-color: #e9ecef;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .quick-link {
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        margin-bottom: 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    }

    .quick-link:hover {
        transform: translateX(5px);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .quick-link i {
        font-size: 24px;
        margin-right: 15px;
        min-width: 30px;
        text-align: center;
    }

    .quick-link-text {
        flex: 1;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #dee2e6;
    }
</style>

<div class="dashboard-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </div>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card">
                <div class="kpi-icon primary">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="kpi-label">Sales Today</div>
                <div class="kpi-value">฿0.00</div>
                <div class="kpi-change">
                    <i class="fas fa-arrow-up"></i> Updated today
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card success">
                <div class="kpi-icon success">
                    <i class="fas fa-chart-line"></i>
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
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="kpi-label">Pending Orders</div>
                <div class="kpi-value">0</div>
                <div class="kpi-change">
                    <span class="badge badge-warning">⚠️ Action needed</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card alert">
                <div class="kpi-icon danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="kpi-label">Low Stock Items</div>
                <div class="kpi-value">0</div>
                <div class="kpi-change">
                    Need restocking
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
                    <i class="fas fa-receipt"></i> Recent Receipts
                </h5>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No receipts found - Start creating transactions</p>
                </div>
            </div>

            <!-- Pending Purchase Orders -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fas fa-shopping-cart"></i> Pending Purchase Orders
                </h5>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>✓ All orders processed!</p>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Links -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fas fa-bolt"></i> Quick Links
                </h5>
                <a href="index.php?page=receipt_list" class="quick-link">
                    <i class="fas fa-receipt"></i>
                    <span class="quick-link-text">Receipts</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="index.php?page=pr_list" class="quick-link">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="quick-link-text">Purchase Requests</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="index.php?page=po_list" class="quick-link">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="quick-link-text">Purchase Orders</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="index.php?page=voucher_list" class="quick-link">
                    <i class="fas fa-file-invoice"></i>
                    <span class="quick-link-text">Vouchers</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="index.php?page=deliv_list" class="quick-link">
                    <i class="fas fa-truck"></i>
                    <span class="quick-link-text">Deliveries</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            <!-- System Info -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fas fa-info-circle"></i> System Info
                </h5>
                <div style="font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef;">
                        <span>Current Date:</span>
                        <strong><?php echo date('M d, Y'); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef;">
                        <span>Time:</span>
                        <strong><?php echo date('H:i:s'); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>Database:</span>
                        <strong>Connected</strong>
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
                this.style.transform = 'translateY(-5px)';
            }, 100);
        });
    });
</script>
