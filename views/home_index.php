<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | iACC System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 24px;
            color: white !important;
        }
        
        .user-menu {
            color: white;
        }
        
        .main-container {
            padding: 30px 0;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* KPI Cards */
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
        
        /* Content Cards */
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
        
        /* Tables */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
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
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-draft {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Quick Links */
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
        
        .quick-link-arrow {
            font-size: 16px;
        }
        
        /* Empty State */
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
        
        .empty-state-text {
            font-size: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .kpi-value {
                font-size: 24px;
            }
            
            .main-container {
                padding: 15px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-calculator"></i> iACC System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto user-menu">
                    <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="container-fluid">
            <!-- Page Title -->
            <div class="page-title">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </div>

            <!-- KPI Cards Row -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="kpi-card">
                        <div class="kpi-icon primary">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-label">Sales Today</div>
                        <div class="kpi-value">฿<?php echo number_format($sales_today, 2); ?></div>
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
                        <div class="kpi-value">฿<?php echo number_format($sales_month, 2); ?></div>
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
                        <div class="kpi-value"><?php echo $pending_orders; ?></div>
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
                        <div class="kpi-value"><?php echo $low_stock; ?></div>
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
                        <?php if (!empty($recent_receipts)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Receipt No</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_receipts as $receipt): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($receipt['receipt_no'] ?? 'N/A'); ?></strong></td>
                                                <td><?php echo isset($receipt['date_create']) ? date('M d, Y H:i', strtotime($receipt['date_create'])) : 'N/A'; ?></td>
                                                <td><strong>฿<?php echo number_format($receipt['amount'] ?? 0, 2); ?></strong></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <p class="empty-state-text">No receipts found</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Purchase Orders -->
                    <div class="content-card">
                        <h5 class="card-title">
                            <i class="fas fa-shopping-cart"></i> Pending Purchase Orders
                        </h5>
                        <?php if (!empty($pending_pos)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>PO No</th>
                                            <th>Date</th>
                                            <th>Total Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_pos as $po): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($po['po_no'] ?? 'N/A'); ?></strong></td>
                                                <td><?php echo isset($po['date_create']) ? date('M d, Y H:i', strtotime($po['date_create'])) : 'N/A'; ?></td>
                                                <td><strong>฿<?php echo number_format($po['total_amount'] ?? 0, 2); ?></strong></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">Review</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p class="empty-state-text">✓ All orders processed!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Quick Links -->
                    <div class="content-card">
                        <h5 class="card-title">
                            <i class="fas fa-bolt"></i> Quick Links
                        </h5>
                        <a href="iacc/" class="quick-link">
                            <i class="fas fa-cube"></i>
                            <span class="quick-link-text">Inventory</span>
                            <i class="fas fa-chevron-right quick-link-arrow"></i>
                        </a>
                        <a href="iacc/" class="quick-link">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="quick-link-text">Sales</span>
                            <i class="fas fa-chevron-right quick-link-arrow"></i>
                        </a>
                        <a href="iacc/" class="quick-link">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="quick-link-text">Purchases</span>
                            <i class="fas fa-chevron-right quick-link-arrow"></i>
                        </a>
                        <a href="iacc/" class="quick-link">
                            <i class="fas fa-file-invoice"></i>
                            <span class="quick-link-text">Reports</span>
                            <i class="fas fa-chevron-right quick-link-arrow"></i>
                        </a>
                        <a href="iacc/" class="quick-link">
                            <i class="fas fa-cog"></i>
                            <span class="quick-link-text">Settings</span>
                            <i class="fas fa-chevron-right quick-link-arrow"></i>
                        </a>
                    </div>

                    <!-- Top Products -->
                    <div class="content-card">
                        <h5 class="card-title">
                            <i class="fas fa-star"></i> Top Products
                        </h5>
                        <?php if (!empty($top_products)): ?>
                            <div style="list-style: none; padding: 0;">
                                <?php foreach ($top_products as $index => $product): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef;">
                                        <span>
                                            <strong><?php echo ($index + 1); ?>.</strong>
                                            <?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?>
                                        </span>
                                        <span class="badge bg-primary">
                                            <?php echo $product['qty_out'] ?? 0; ?> sold
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p class="empty-state-text">No data available</p>
                            </div>
                        <?php endif; ?>
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
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Optional: Add interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for quick links
            const quickLinks = document.querySelectorAll('.quick-link');
            quickLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // You can add custom routing logic here
                });
            });
        });
    </script>
</body>
</html>