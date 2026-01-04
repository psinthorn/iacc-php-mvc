<?php
// Dashboard page for iacc system - REAL DATA WIRED
// Pulls actual data from database tables

$cur_date = date('Y-m-d');
$month_start = date('Y-m-01');

// Get current company filter and user level
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;
$com_name = isset($_SESSION['com_name']) ? $_SESSION['com_name'] : '';
$user_level = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
$is_admin = ($user_level >= 1);
$is_super_admin = ($user_level >= 2);

// Handle Docker tools setting update (Super Admin only)
if ($is_super_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify') && csrf_verify()) {
        // Handle Docker Tools setting (for Docker Test & Container Debug)
        if (isset($_POST['docker_tools_setting'])) {
            $new_setting = $_POST['docker_tools_setting'];
            if (function_exists('save_docker_tools_setting') && save_docker_tools_setting($new_setting, 'docker_tools')) {
                $docker_settings_message = 'Docker Tools setting updated to: ' . ucfirst($new_setting);
                $docker_settings_success = true;
            } else {
                $docker_settings_message = 'Failed to update Docker Tools setting';
                $docker_settings_success = false;
            }
        }
        // Handle Container Manager setting (separate)
        if (isset($_POST['container_manager_setting'])) {
            $new_setting = $_POST['container_manager_setting'];
            if (function_exists('save_docker_tools_setting') && save_docker_tools_setting($new_setting, 'container_manager')) {
                $container_manager_message = 'Container Manager setting updated to: ' . ucfirst($new_setting);
                $container_manager_success = true;
            } else {
                $container_manager_message = 'Failed to update Container Manager setting';
                $container_manager_success = false;
            }
        }
    }
}

// Determine dashboard mode:
// - Admin/Super Admin: Always show Admin Panel
// - Admin/Super Admin with company selected: Also show User Dashboard (company data)
// - Normal User: Show User Dashboard only (their company data)
$show_admin_panel = $is_admin;  // Admin always sees admin panel
$show_user_dashboard = $com_id > 0 || !$is_admin;  // Show user dashboard if company selected OR not admin

// Build company filter condition for queries
// If com_id is set, filter by vendor or customer company
// If com_id is empty (admin viewing all), show all data
$company_filter_pr = "";
$company_filter_iv = "";
if ($com_id > 0) {
    $company_filter_pr = " AND (pr.ven_id = $com_id OR pr.cus_id = $com_id)";
    // For invoices: join via po.ref = pr.id, filter on pr
    $company_filter_iv = " AND (pr.ven_id = $com_id OR pr.cus_id = $com_id)";
}

// ============ ADMIN SYSTEM STATS ============
if ($is_admin) {
    // Total users
    $sql_users = "SELECT COUNT(*) as count FROM authorize";
    $result_users = mysqli_query($db->conn, $sql_users);
    $total_users = mysqli_fetch_assoc($result_users)['count'] ?? 0;
    
    // Users by role
    $sql_users_role = "SELECT level, COUNT(*) as count FROM authorize GROUP BY level";
    $result_users_role = mysqli_query($db->conn, $sql_users_role);
    $users_by_role = [0 => 0, 1 => 0, 2 => 0];
    while ($row = mysqli_fetch_assoc($result_users_role)) {
        $users_by_role[$row['level']] = $row['count'];
    }
    
    // Total companies
    $sql_companies = "SELECT COUNT(*) as count FROM company WHERE deleted_at IS NULL";
    $result_companies = mysqli_query($db->conn, $sql_companies);
    $total_companies = mysqli_fetch_assoc($result_companies)['count'] ?? 0;
    
    // Active companies (with recent transactions)
    $sql_active_companies = "SELECT COUNT(DISTINCT company_id) as count FROM (
        SELECT ven_id as company_id FROM pr WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        UNION
        SELECT cus_id as company_id FROM pr WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ) as active";
    $result_active = mysqli_query($db->conn, $sql_active_companies);
    $active_companies = mysqli_fetch_assoc($result_active)['count'] ?? 0;
    
    // Locked accounts
    $sql_locked = "SELECT COUNT(*) as count FROM authorize WHERE locked_until > NOW()";
    $result_locked = mysqli_query($db->conn, $sql_locked);
    $locked_accounts = mysqli_fetch_assoc($result_locked)['count'] ?? 0;
    
    // Recent login attempts (failed)
    $sql_failed = "SELECT COUNT(*) as count FROM login_attempts 
                   WHERE success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result_failed = mysqli_query($db->conn, $sql_failed);
    $failed_logins_24h = mysqli_fetch_assoc($result_failed)['count'] ?? 0;
    
    // Recent users (last 5 registered)
    $sql_recent_users = "SELECT id, email, level, 
                         CASE level WHEN 0 THEN 'User' WHEN 1 THEN 'Admin' WHEN 2 THEN 'Super Admin' END as role_name
                         FROM authorize ORDER BY id DESC LIMIT 5";
    $recent_users = mysqli_query($db->conn, $sql_recent_users);
    
    // ============ BUSINESS SUMMARY REPORT DATA ============
    // Get date filter from request (default: this month)
    $report_period = isset($_GET['report_period']) ? $_GET['report_period'] : 'month';
    switch ($report_period) {
        case 'today':
            $report_date_filter = "DATE(pr.date) = CURDATE()";
            $report_period_label = "Today";
            break;
        case 'week':
            $report_date_filter = "pr.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $report_period_label = "Last 7 Days";
            break;
        case 'month':
            $report_date_filter = "pr.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $report_period_label = "Last 30 Days";
            break;
        case 'year':
            $report_date_filter = "YEAR(pr.date) = YEAR(CURDATE())";
            $report_period_label = "This Year";
            break;
        case 'all':
            $report_date_filter = "1=1";
            $report_period_label = "All Time";
            break;
        default:
            $report_date_filter = "pr.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $report_period_label = "Last 30 Days";
    }
    
    // Overall summary counts
    $sql_report_summary = "SELECT 
        COUNT(DISTINCT pr.id) as total_pr,
        SUM(CASE WHEN pr.status >= 1 THEN 1 ELSE 0 END) as total_qa,
        SUM(CASE WHEN pr.status >= 2 THEN 1 ELSE 0 END) as total_po,
        SUM(CASE WHEN pr.status >= 4 THEN 1 ELSE 0 END) as total_iv,
        SUM(CASE WHEN pr.status >= 5 THEN 1 ELSE 0 END) as total_tax
        FROM pr WHERE $report_date_filter";
    $report_summary = mysqli_fetch_assoc(mysqli_query($db->conn, $sql_report_summary));
    
    // Top 5 customers by transaction count
    $sql_top_customers = "SELECT c.id, c.name_en, c.name_th,
        COUNT(pr.id) as tx_count,
        SUM(CASE WHEN pr.status >= 4 THEN 1 ELSE 0 END) as invoice_count
        FROM pr 
        JOIN company c ON pr.cus_id = c.id
        WHERE $report_date_filter
        GROUP BY c.id, c.name_en, c.name_th
        ORDER BY tx_count DESC
        LIMIT 5";
    $top_customers = mysqli_query($db->conn, $sql_top_customers);
}

// ============ FETCH KPI DATA FROM DATABASE ============
// Only fetch user dashboard data if we need to show it
if ($show_user_dashboard) {

// Sales Today - from pay table (payment volume) - filtered by company via PO->PR
$sql_today = "SELECT IFNULL(SUM(pay.volumn), 0) as total FROM pay 
              JOIN po ON pay.po_id = po.id
              JOIN pr ON po.ref = pr.id
              WHERE DATE(pay.date) = '$cur_date' $company_filter_pr";
$result_today = mysqli_query($db->conn, $sql_today);
$row_today = mysqli_fetch_assoc($result_today);
$sales_today = $row_today['total'] ?? 0;

// Sales This Month - from pay table
$sql_month = "SELECT IFNULL(SUM(pay.volumn), 0) as total FROM pay 
              JOIN po ON pay.po_id = po.id
              JOIN pr ON po.ref = pr.id
              WHERE DATE(pay.date) >= '$month_start' AND DATE(pay.date) <= '$cur_date' $company_filter_pr";
$result_month = mysqli_query($db->conn, $sql_month);
$row_month = mysqli_fetch_assoc($result_month);
$sales_month = $row_month['total'] ?? 0;

// Pending Purchase Orders - from po table (filtered by company)
$sql_pending = "SELECT COUNT(po.id) as count FROM po 
                JOIN pr ON po.ref = pr.id
                WHERE po.over = 0 $company_filter_pr";
$result_pending = mysqli_query($db->conn, $sql_pending);
$row_pending = mysqli_fetch_assoc($result_pending);
$pending_orders = $row_pending['count'] ?? 0;

// Total PO count (filtered by company)
$sql_total_po = "SELECT COUNT(po.id) as count FROM po
                 JOIN pr ON po.ref = pr.id
                 WHERE 1=1 $company_filter_pr";
$result_total = mysqli_query($db->conn, $sql_total_po);
$row_total = mysqli_fetch_assoc($result_total);
$total_orders = $row_total['count'] ?? 0;

// Recent payments - from pay table (filtered by company)
$sql_recent_pay = "SELECT pay.*, po.name, po.tax 
                   FROM pay 
                   LEFT JOIN po ON pay.po_id = po.id 
                   LEFT JOIN pr ON po.ref = pr.id
                   WHERE 1=1 $company_filter_pr
                   ORDER BY pay.date DESC LIMIT 5";
$recent_payments = mysqli_query($db->conn, $sql_recent_pay);

// Pending POs - from po table where over = 0 (filtered by company)
$sql_pending_po = "SELECT po.*, 
                   (SELECT SUM(volumn) FROM pay WHERE po_id = po.id) as paid_amount
                   FROM po 
                   JOIN pr ON po.ref = pr.id
                   WHERE po.over = 0 $company_filter_pr
                   ORDER BY po.date DESC LIMIT 5";
$pending_pos = mysqli_query($db->conn, $sql_pending_po);

// Completed orders this month (filtered by company)
$sql_completed = "SELECT COUNT(po.id) as count FROM po 
                  JOIN pr ON po.ref = pr.id
                  WHERE po.over = 1 AND DATE(po.date) >= '$month_start' $company_filter_pr";
$result_completed = mysqli_query($db->conn, $sql_completed);
$row_completed = mysqli_fetch_assoc($result_completed);
$completed_orders = $row_completed['count'] ?? 0;

// Invoice statistics (filtered by company via po -> pr relationship)
$sql_invoices = "SELECT COUNT(DISTINCT iv.tex) as count FROM iv 
                 JOIN po ON iv.tex = po.id
                 JOIN pr ON po.ref = pr.id
                 WHERE DATE(iv.createdate) >= '$month_start' $company_filter_iv";
$result_invoices = mysqli_query($db->conn, $sql_invoices);
$row_invoices = mysqli_fetch_assoc($result_invoices);
$total_invoices = $row_invoices['count'] ?? 0;

// Tax Invoice statistics (filtered by company via po -> pr relationship)
$sql_tax_invoices = "SELECT COUNT(DISTINCT iv.texiv) as count FROM iv 
                     JOIN po ON iv.tex = po.id
                     JOIN pr ON po.ref = pr.id
                     WHERE iv.texiv > 0 AND DATE(iv.texiv_create) >= '$month_start' $company_filter_iv";
$result_tax_inv = mysqli_query($db->conn, $sql_tax_invoices);
$row_tax_inv = mysqli_fetch_assoc($result_tax_inv);
$total_tax_invoices = $row_tax_inv['count'] ?? 0;

// Recent invoices (filtered by company via po -> pr relationship)
$sql_recent_inv = "SELECT iv.tex as po_id, iv.createdate, iv.status_iv,
                   po.name as description,
                   (SELECT SUM(price*quantity) FROM product WHERE po_id = po.id) as subtotal
                   FROM iv 
                   JOIN po ON iv.tex = po.id
                   JOIN pr ON po.ref = pr.id
                   WHERE 1=1 $company_filter_iv
                   ORDER BY iv.createdate DESC LIMIT 5";
$recent_invoices = mysqli_query($db->conn, $sql_recent_inv);

} // End of $show_user_dashboard condition

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
?>
<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Dashboard Page Styles - Modern Update */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }
    
    .dashboard-wrapper {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
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
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid #e5e7eb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-left: 4px solid #667eea;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .kpi-card.alert {
        border-left-color: #ef4444;
    }

    .kpi-card.success {
        border-left-color: #10b981;
    }

    .kpi-card.warning {
        border-left-color: #f59e0b;
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
        color: #10b981;
    }

    .kpi-icon.warning {
        color: #f59e0b;
    }

    .kpi-icon.danger {
        color: #ef4444;
    }

    .kpi-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }

    .kpi-change {
        font-size: 12px;
        color: #6b7280;
    }

    .content-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .card-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1f2937;
        padding-bottom: 12px;
        border-bottom: 2px solid #667eea;
    }

    .card-title i {
        margin-right: 10px;
        color: #667eea;
    }

    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }

    .table {
        margin-bottom: 0;
        font-size: 13px;
    }

    .table thead th {
        background: #f9fafb;
        color: #1f2937;
        font-weight: 600;
        border: none;
        padding: 14px 12px;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        padding: 14px 12px;
        border-color: #e5e7eb;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.03);
    }

    .quick-link {
        display: flex;
        align-items: center;
        padding: 14px 16px;
        border-radius: 10px;
        background: white;
        border: 1px solid #e5e7eb;
        color: #1f2937;
        text-decoration: none;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }

    .quick-link:hover {
        transform: translateY(-2px);
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        color: #1f2937;
        text-decoration: none;
    }

    .quick-link i {
        font-size: 18px;
        margin-right: 12px;
        min-width: 25px;
        text-align: center;
        color: #667eea;
    }

    .quick-link-text {
        flex: 1;
        font-weight: 600;
        font-size: 14px;
    }

    .empty-state {
        text-align: center;
        padding: 30px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 40px;
        margin-bottom: 12px;
        color: #9ca3af;
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
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.25);
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
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #10b981;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #d97706;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        margin: 0 2px;
        font-size: 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #667eea;
        color: white;
    }
</style>

<div class="dashboard-wrapper">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title"><i class="fa fa-tachometer"></i> Dashboard</h2>
            <div class="dashboard-subtitle">
                <?php if ($is_admin && $com_id == 0): ?>
                    <i class="fa fa-globe"></i> System Administration - Global View
                    <div style="font-size: 11px; margin-top: 5px; opacity: 0.8;">Select a company below to view company-specific data</div>
                <?php elseif ($is_admin && $com_id > 0): ?>
                    <i class="fa fa-building"></i> Viewing: <?php echo htmlspecialchars($com_name); ?>
                    <div style="font-size: 11px; margin-top: 5px; opacity: 0.8;">
                        <a href="index.php?page=remote" style="color: rgba(255,255,255,0.9);"><i class="fa fa-exchange"></i> Switch company</a> | 
                        <a href="index.php?page=remote&clear=1" style="color: rgba(255,255,255,0.9);"><i class="fa fa-times"></i> Clear selection</a>
                    </div>
                <?php elseif ($com_id > 0): ?>
                    <i class="fa fa-building"></i> <?php echo htmlspecialchars($com_name); ?>
                <?php else: ?>
                    Welcome back! Here's your business overview
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align: right;">
            <small><?php echo date('l, F j, Y'); ?></small><br>
            <?php if ($is_super_admin): ?>
                <span class="badge" style="background: #dc3545;">Super Admin</span>
            <?php elseif ($is_admin): ?>
                <span class="badge" style="background: #17a2b8;">Admin</span>
            <?php endif; ?>
            <?php if ($show_admin_panel): ?>
                <span class="badge" style="background: rgba(255,255,255,0.2);">Admin Panel</span>
            <?php endif; ?>
            <?php if ($com_id > 0): ?>
                <span class="badge" style="background: rgba(255,255,255,0.2);">Company View</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($show_admin_panel): ?>
    <!-- Admin Management Panel -->
    <div class="row kpi-row">
        <div class="col-md-12">
            <div class="content-card" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; margin-bottom: 20px;">
                <h5 style="color: #fff; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 15px;">
                    <i class="fa fa-shield"></i> Admin Control Panel
                </h5>
                <div class="row">
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: #667eea;"><?php echo $total_users; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Total Users</div>
                            <div style="font-size: 10px; margin-top: 5px;">
                                <span style="color: #51cf66;"><?php echo $users_by_role[0]; ?> Users</span> |
                                <span style="color: #ffd43b;"><?php echo $users_by_role[1]; ?> Admins</span> |
                                <span style="color: #ff6b6b;"><?php echo $users_by_role[2]; ?> Super</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: #51cf66;"><?php echo $total_companies; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Companies</div>
                            <div style="font-size: 10px; margin-top: 5px; color: #51cf66;">
                                <?php echo $active_companies; ?> active (30d)
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: <?php echo $locked_accounts > 0 ? '#ff6b6b' : '#51cf66'; ?>;"><?php echo $locked_accounts; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Locked Accounts</div>
                            <?php if ($locked_accounts > 0): ?>
                            <div style="font-size: 10px; margin-top: 5px; color: #ff6b6b;">
                                <a href="index.php?page=user" style="color: #ff6b6b;">View & Unlock</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: <?php echo $failed_logins_24h > 10 ? '#ff6b6b' : '#ffd43b'; ?>;"><?php echo $failed_logins_24h; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Failed Logins (24h)</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-8">
                        <div style="padding: 15px;">
                            <div style="font-size: 12px; color: #aaa; margin-bottom: 10px;">Quick Admin Actions</div>
                            <?php if ($is_super_admin): ?>
                            <a href="index.php?page=user" class="btn btn-sm" style="background: #667eea; color: white; margin: 2px;">
                                <i class="fa fa-users"></i> Manage Users
                            </a>
                            <?php endif; ?>
                            <a href="index.php?page=company" class="btn btn-sm" style="background: #51cf66; color: white; margin: 2px;">
                                <i class="fa fa-building"></i> Companies
                            </a>
                            <a href="index.php?page=report" class="btn btn-sm" style="background: #ffd43b; color: #333; margin: 2px;">
                                <i class="fa fa-chart-bar"></i> Reports
                            </a>
                            <a href="index.php?page=remote" class="btn btn-sm" style="background: #17a2b8; color: white; margin: 2px;">
                                <i class="fa fa-building"></i> Select Company
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Company Selection for Admin -->
    <div class="row kpi-row">
        <div class="col-md-12">
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-building"></i> Select Company to View Data
                </h5>
                <p style="color: #6c757d; margin-bottom: 15px;">Choose a company to view their specific business data (sales, orders, invoices)</p>
                <?php
                // Get recent active companies for quick selection
                $sql_quick_companies = "SELECT DISTINCT c.id, c.name_en, c.name_th,
                    (SELECT MAX(pr.date) FROM pr WHERE pr.ven_id = c.id OR pr.cus_id = c.id) as last_activity
                    FROM company c
                    WHERE c.deleted_at IS NULL
                    ORDER BY last_activity DESC
                    LIMIT 8";
                $quick_companies = mysqli_query($db->conn, $sql_quick_companies);
                ?>
                <div class="row">
                    <?php if($quick_companies && mysqli_num_rows($quick_companies) > 0): ?>
                        <?php while($qc = mysqli_fetch_assoc($quick_companies)): ?>
                        <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
                            <a href="index.php?page=remote&select_company=<?php echo $qc['id']; ?>" 
                               class="btn btn-block" 
                               style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 12px 15px;">
                                <i class="fa fa-building" style="color: #667eea;"></i>
                                <strong><?php echo htmlspecialchars(substr($qc['name_en'] ?: $qc['name_th'], 0, 20)); ?></strong>
                                <?php if($qc['last_activity']): ?>
                                <br><small style="color: #6c757d;">Last: <?php echo date('M d', strtotime($qc['last_activity'])); ?></small>
                                <?php endif; ?>
                            </a>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
                        <a href="index.php?page=remote" class="btn btn-block" style="background: #667eea; color: white; text-align: center; padding: 20px 15px;">
                            <i class="fa fa-search"></i> Browse All Companies
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Business Summary Report -->
    <div class="row kpi-row">
        <div class="col-md-12">
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-bar-chart-o"></i> Business Summary Report
                    <div style="float: right;">
                        <a href="?page=dashboard&report_period=today" class="btn btn-sm <?php echo $report_period == 'today' ? 'btn-primary' : 'btn-default'; ?>">Today</a>
                        <a href="?page=dashboard&report_period=week" class="btn btn-sm <?php echo $report_period == 'week' ? 'btn-primary' : 'btn-default'; ?>">7 Days</a>
                        <a href="?page=dashboard&report_period=month" class="btn btn-sm <?php echo $report_period == 'month' ? 'btn-primary' : 'btn-default'; ?>">30 Days</a>
                        <a href="?page=dashboard&report_period=year" class="btn btn-sm <?php echo $report_period == 'year' ? 'btn-primary' : 'btn-default'; ?>">This Year</a>
                        <a href="?page=dashboard&report_period=all" class="btn btn-sm <?php echo $report_period == 'all' ? 'btn-primary' : 'btn-default'; ?>">All Time</a>
                    </div>
                </h5>
                <p style="color: #6c757d; margin-bottom: 15px;">Period: <strong><?php echo $report_period_label; ?></strong></p>
                
                <div class="row">
                    <!-- Summary Stats -->
                    <div class="col-md-7">
                        <table class="table table-bordered" style="font-size: 13px;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Stage</th>
                                    <th class="text-center">Count</th>
                                    <th class="text-center">Conversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fa fa-file-o" style="color: #667eea;"></i> Purchase Requests</td>
                                    <td class="text-center"><strong><?php echo $report_summary['total_pr'] ?? 0; ?></strong></td>
                                    <td class="text-center">-</td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-check" style="color: #17a2b8;"></i> Quotations</td>
                                    <td class="text-center"><strong><?php echo $report_summary['total_qa'] ?? 0; ?></strong></td>
                                    <td class="text-center">
                                        <?php 
                                        $pr_count = $report_summary['total_pr'] ?? 0;
                                        $qa_rate = $pr_count > 0 ? round(($report_summary['total_qa'] ?? 0) / $pr_count * 100) : 0;
                                        echo $qa_rate . '%';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-shopping-cart" style="color: #ffc107;"></i> Purchase Orders</td>
                                    <td class="text-center"><strong><?php echo $report_summary['total_po'] ?? 0; ?></strong></td>
                                    <td class="text-center">
                                        <?php 
                                        $po_rate = $pr_count > 0 ? round(($report_summary['total_po'] ?? 0) / $pr_count * 100) : 0;
                                        echo $po_rate . '%';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-file-text-o" style="color: #28a745;"></i> Invoices</td>
                                    <td class="text-center"><strong><?php echo $report_summary['total_iv'] ?? 0; ?></strong></td>
                                    <td class="text-center">
                                        <?php 
                                        $iv_rate = $pr_count > 0 ? round(($report_summary['total_iv'] ?? 0) / $pr_count * 100) : 0;
                                        echo $iv_rate . '%';
                                        ?>
                                    </td>
                                </tr>
                                <tr style="background: #e8f5e9;">
                                    <td><i class="fa fa-money" style="color: #28a745;"></i> <strong>Tax Invoices</strong></td>
                                    <td class="text-center"><strong style="color: #28a745;"><?php echo $report_summary['total_tax'] ?? 0; ?></strong></td>
                                    <td class="text-center">
                                        <?php 
                                        $tax_rate = $pr_count > 0 ? round(($report_summary['total_tax'] ?? 0) / $pr_count * 100) : 0;
                                        echo '<strong style="color: #28a745;">' . $tax_rate . '%</strong>';
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Top Customers -->
                    <div class="col-md-5">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-users"></i> Top Customers</h6>
                        <table class="table table-sm" style="font-size: 12px;">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-center">TX</th>
                                    <th class="text-center">INV</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($top_customers && mysqli_num_rows($top_customers) > 0): ?>
                                    <?php while($tc = mysqli_fetch_assoc($top_customers)): ?>
                                    <tr>
                                        <td>
                                            <a href="index.php?page=remote&select_company=<?php echo $tc['id']; ?>" style="color: #333;">
                                                <?php echo htmlspecialchars(substr($tc['name_en'] ?: $tc['name_th'], 0, 20)); ?>
                                            </a>
                                        </td>
                                        <td class="text-center"><?php echo $tc['tx_count']; ?></td>
                                        <td class="text-center"><?php echo $tc['invoice_count']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <a href="index.php?page=report" class="btn btn-sm btn-default btn-block">
                            <i class="fa fa-external-link"></i> Full Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Developer Tools Panel (Super Admin Only) -->
    <?php if ($is_super_admin): ?>
    <?php 
        $docker_enabled = function_exists('is_docker_tools_enabled') ? is_docker_tools_enabled() : true;
        $container_mgr_enabled = function_exists('is_container_manager_enabled') ? is_container_manager_enabled() : false;
        $docker_status = function_exists('get_docker_tools_status') ? get_docker_tools_status() : ['mode_text' => 'N/A', 'setting' => 'auto', 'is_docker_environment' => false];
        $docker_setting = function_exists('get_docker_tools_setting') ? get_docker_tools_setting('docker_tools') : 'auto';
        $container_mgr_setting = function_exists('get_docker_tools_setting') ? get_docker_tools_setting('container_manager') : 'off';
        $is_docker_env = function_exists('is_running_in_docker') ? is_running_in_docker() : false;
    ?>
    <div class="row kpi-row">
        <div class="col-md-12">
            <div class="content-card" style="border-left: 4px solid #e74c3c;">
                <h5 class="card-title">
                    <i class="fa fa-wrench" style="color: #e74c3c;"></i> Developer Tools
                    <span class="badge" style="background: #e74c3c; color: white; margin-left: 10px;">Super Admin</span>
                    <?php if ($docker_enabled): ?>
                    <span class="badge" style="background: #1abc9c; color: white; margin-left: 5px;" title="Docker Debug: <?= ucfirst($docker_setting) ?>"><i class="fa fa-cloud"></i> Docker Debug</span>
                    <?php endif; ?>
                    <?php if ($container_mgr_enabled): ?>
                    <span class="badge" style="background: #8e44ad; color: white; margin-left: 5px;" title="Container Manager: <?= ucfirst($container_mgr_setting) ?>"><i class="fa fa-server"></i> Container Mgr</span>
                    <?php endif; ?>
                    <?php if (!$docker_enabled && !$container_mgr_enabled): ?>
                    <span class="badge" style="background: #95a5a6; color: white; margin-left: 5px;"><i class="fa fa-server"></i> cPanel Mode</span>
                    <?php endif; ?>
                </h5>
                
                <!-- Docker Settings -->
                <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <!-- Docker Test & Container Debug -->
                        <form method="POST" action="index.php?page=dashboard" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                            <span style="color: #333; font-weight: 500; min-width: 160px;"><i class="fa fa-bug"></i> Docker Debug:</span>
                            <select name="docker_tools_setting" style="padding: 5px 10px; border: 1px solid #ced4da; border-radius: 4px; background: white; min-width: 180px;">
                                <option value="auto" <?= $docker_setting === 'auto' ? 'selected' : '' ?>>🔄 Auto <?= $is_docker_env ? '(Docker ✓)' : '(No Docker)' ?></option>
                                <option value="on" <?= $docker_setting === 'on' ? 'selected' : '' ?>>✅ On</option>
                                <option value="off" <?= $docker_setting === 'off' ? 'selected' : '' ?>>❌ Off</option>
                            </select>
                            <button type="submit" style="padding: 5px 12px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Save</button>
                            <?php if (isset($docker_settings_message)): ?>
                            <span style="color: <?= $docker_settings_success ? '#27ae60' : '#e74c3c' ?>; font-size: 12px;"><i class="fa <?= $docker_settings_success ? 'fa-check' : 'fa-times' ?>"></i> <?= htmlspecialchars($docker_settings_message) ?></span>
                            <?php endif; ?>
                        </form>
                        
                        <!-- Container Manager (separate, default OFF) -->
                        <form method="POST" action="index.php?page=dashboard" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                            <span style="color: #333; font-weight: 500; min-width: 160px;"><i class="fa fa-server"></i> Container Manager:</span>
                            <select name="container_manager_setting" style="padding: 5px 10px; border: 1px solid #ced4da; border-radius: 4px; background: white; min-width: 180px;">
                                <option value="off" <?= $container_mgr_setting === 'off' ? 'selected' : '' ?>>❌ Off (Default)</option>
                                <option value="auto" <?= $container_mgr_setting === 'auto' ? 'selected' : '' ?>>🔄 Auto <?= $is_docker_env ? '(Docker ✓)' : '(No Docker)' ?></option>
                                <option value="on" <?= $container_mgr_setting === 'on' ? 'selected' : '' ?>>✅ On</option>
                            </select>
                            <button type="submit" style="padding: 5px 12px; background: #8e44ad; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Save</button>
                            <?php if (isset($container_manager_message)): ?>
                            <span style="color: <?= $container_manager_success ? '#27ae60' : '#e74c3c' ?>; font-size: 12px;"><i class="fa <?= $container_manager_success ? 'fa-check' : 'fa-times' ?>"></i> <?= htmlspecialchars($container_manager_message) ?></span>
                            <?php endif; ?>
                        </form>
                    </div>
                    <p style="color: #6c757d; font-size: 11px; margin: 10px 0 0 0;">
                        <strong>Docker Debug</strong> (Docker Test, Container Debug): Read-only debug tools, default Auto. 
                        <strong>Container Manager</strong>: Has start/stop/restart actions, default Off for safety.
                    </p>
                </div>
                
                <p style="color: #6c757d; margin-bottom: 15px;">Testing, debugging, and infrastructure monitoring tools</p>
                
                <div class="row">
                    <!-- Debug Tools -->
                    <div class="col-md-4">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-bug"></i> Debug</h6>
                        <a href="index.php?page=test_crud" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-database" style="color: #3498db;"></i> <strong>CRUD Test</strong>
                            <br><small style="color: #6c757d;">Test database operations</small>
                        </a>
                        <a href="index.php?page=debug_session" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-key" style="color: #e67e22;"></i> <strong>Session Debug</strong>
                            <br><small style="color: #6c757d;">View session variables</small>
                        </a>
                        <a href="index.php?page=debug_invoice" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-file-text-o" style="color: #27ae60;"></i> <strong>Invoice Debug</strong>
                            <br><small style="color: #6c757d;">Debug invoice access</small>
                        </a>
                        <a href="index.php?page=api_lang_debug" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-language" style="color: #2980b9;"></i> <strong>Language Debug</strong>
                            <br><small style="color: #6c757d;">Debug localization API</small>
                        </a>
                    </div>
                    
                    <!-- Docker/Infrastructure Tools -->
                    <div class="col-md-4">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-server"></i> Infrastructure</h6>
                        <?php if ($docker_enabled): ?>
                        <a href="index.php?page=docker_test" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-cloud" style="color: #1abc9c;"></i> <strong>Docker Test</strong>
                            <br><small style="color: #6c757d;">Test Docker socket</small>
                        </a>
                        <a href="index.php?page=test_containers" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-cube" style="color: #9b59b6;"></i> <strong>Container Debug</strong>
                            <br><small style="color: #6c757d;">Raw container data</small>
                        </a>
                        <?php endif; ?>
                        <!-- Container Manager always visible, but styled based on enabled state -->
                        <a href="index.php?page=containers" class="btn btn-block" style="background: <?= $container_mgr_enabled ? '#f8f9fa' : '#f0f0f0' ?>; border: 1px solid <?= $container_mgr_enabled ? '#dee2e6' : '#e0e0e0' ?>; color: <?= $container_mgr_enabled ? '#333' : '#999' ?>; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-server" style="color: <?= $container_mgr_enabled ? '#8e44ad' : '#ccc' ?>;"></i> 
                            <strong>Container Manager</strong>
                            <?php if (!$container_mgr_enabled): ?>
                            <span style="font-size: 10px; background: #e74c3c; color: white; padding: 2px 6px; border-radius: 3px; margin-left: 5px;">Off</span>
                            <?php endif; ?>
                            <br><small style="color: #6c757d;"><?= $container_mgr_enabled ? 'Manage containers' : 'Click to enable' ?></small>
                        </a>
                        <a href="index.php?page=monitoring" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-dashboard" style="color: #e74c3c;"></i> <strong>System Monitor</strong>
                            <br><small style="color: #6c757d;">Health & performance</small>
                        </a>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="col-md-4">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-shield"></i> Admin Quick Links</h6>
                        <a href="index.php?page=user" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-users" style="color: #3498db;"></i> <strong>User Management</strong>
                            <br><small style="color: #6c757d;">Manage system users</small>
                        </a>
                        <a href="index.php?page=audit_log" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-history" style="color: #c0392b;"></i> <strong>Audit Log</strong>
                            <br><small style="color: #6c757d;">View activity history</small>
                        </a>
                        <a href="index.php?page=payment_method_list" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-credit-card-alt" style="color: #27ae60;"></i> <strong>Payment Methods</strong>
                            <br><small style="color: #6c757d;">Configure payments</small>
                        </a>
                        <a href="index.php?page=payment_gateway_config" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-cogs" style="color: #8e44ad;"></i> <strong>Gateway Config</strong>
                            <br><small style="color: #6c757d;">Payment gateway settings</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; // End of Developer Tools Panel ?>
    
    <?php endif; // End of Admin Panel ?>

    <?php if ($show_user_dashboard): ?>
    <!-- ============ USER DASHBOARD (Company-specific data) ============ -->
    
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
                    <i class="fa fa-money-bill-wave"></i> Recent Payments
                    <a href="index.php?page=payment_list" style="float: right; font-size: 11px; color: #667eea;">View All <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_payments && mysqli_num_rows($recent_payments) > 0): ?>
                                <?php while($payment = mysqli_fetch_assoc($recent_payments)): ?>
                                <tr>
                                    <td><strong>#<?php echo $payment['po_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($payment['name'] ?? '', 0, 25)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['date'])); ?></td>
                                    <td><?php echo format_currency($payment['volumn']); ?></td>
                                    <td><?php echo !empty($payment['value']) ? ucfirst($payment['value']) : 'Direct'; ?></td>
                                    <td>
                                        <a href="index.php?page=po_view&id=<?php echo $payment['po_id']; ?>" class="action-btn" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
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
                    <a href="index.php?page=po_list" style="float: right; font-size: 11px; color: #667eea;">View All <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Description</th>
                                <th>Tax Invoice #</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($pending_pos && mysqli_num_rows($pending_pos) > 0): ?>
                                <?php while($po = mysqli_fetch_assoc($pending_pos)): ?>
                                <tr>
                                    <td><strong>#<?php echo $po['po_id_new'] ?? $po['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($po['name'], 0, 25)); ?></td>
                                    <td><code><?php echo $po['tax'] ?: '-'; ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($po['date'])); ?></td>
                                    <td><?php echo get_status_badge($po['over']); ?></td>
                                    <td>
                                        <a href="index.php?page=po_view&id=<?php echo $po['id']; ?>" class="action-btn" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
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
                    <a href="index.php?page=compl_list" style="float: right; font-size: 11px; color: #667eea;">View All <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_invoices && mysqli_num_rows($recent_invoices) > 0): ?>
                                <?php while($invoice = mysqli_fetch_assoc($recent_invoices)): ?>
                                <tr>
                                    <td><strong>#<?php echo $invoice['po_id'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($invoice['description'] ?? '', 0, 25)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['createdate'])); ?></td>
                                    <td><?php echo number_format($invoice['subtotal'] ?? 0, 2); ?></td>
                                    <td>
                                        <a href="index.php?page=compl_view&id=<?php echo $invoice['po_id']; ?>" class="action-btn" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="inv.php?id=<?php echo $invoice['po_id']; ?>" class="action-btn" title="Download PDF" target="_blank">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
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
                    <i class="fa fa-file-invoice-dollar"></i> Recent Tax Invoices
                    <a href="index.php?page=compl_list" style="float: right; font-size: 11px; color: #667eea;">View All <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Tax Inv #</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Tax invoices with correct join: iv.tex = po.id, po.ref = pr.id
                            $sql_tax_inv_detail = "SELECT iv.texiv, iv.tex as po_id, iv.texiv_create, iv.countmailtax,
                                                  po.name as description,
                                                  (SELECT SUM(price*quantity) FROM product WHERE po_id = po.id) as subtotal
                                                  FROM iv 
                                                  JOIN po ON iv.tex = po.id
                                                  JOIN pr ON po.ref = pr.id
                                                  WHERE iv.texiv > 0 $company_filter_iv
                                                  ORDER BY iv.texiv_create DESC LIMIT 5";
                            $tax_inv_results = mysqli_query($db->conn, $sql_tax_inv_detail);
                            ?>
                            <?php if($tax_inv_results && mysqli_num_rows($tax_inv_results) > 0): ?>
                                <?php while($tax_inv = mysqli_fetch_assoc($tax_inv_results)): ?>
                                <tr>
                                    <td><strong>#<?php echo $tax_inv['texiv'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($tax_inv['description'] ?? '', 0, 25)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($tax_inv['texiv_create'])); ?></td>
                                    <td><?php echo number_format($tax_inv['subtotal'] ?? 0, 2); ?></td>
                                    <td>
                                        <a href="taxiv.php?id=<?php echo $tax_inv['po_id']; ?>" class="action-btn" title="View PDF" target="_blank">
                                            <i class="fa fa-file-text-o"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fa fa-receipt"></i>
                                            <p>No tax invoices found</p>
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
            <?php if ($is_admin && $com_id > 0): ?>
            <!-- Admin Quick Actions (only when viewing company data) -->
            <div class="content-card" style="background: #f8f9fa; border-left: 4px solid #667eea;">
                <h5 class="card-title">
                    <i class="fa fa-cog"></i> Admin Actions
                </h5>
                <a href="index.php?page=remote&clear=1" class="quick-link" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);">
                    <i class="fa fa-arrow-left"></i>
                    <span class="quick-link-text">Back to Admin Panel</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=remote" class="quick-link" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <i class="fa fa-exchange"></i>
                    <span class="quick-link-text">Switch Company</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>

            <!-- Quick Links -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-bolt"></i> Quick Links
                </h5>
                <a href="index.php?page=po_list" class="quick-link">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="quick-link-text">Purchase Orders</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=pr_list" class="quick-link">
                    <i class="fa fa-clipboard"></i>
                    <span class="quick-link-text">Requests</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=deliv_list" class="quick-link">
                    <i class="fa fa-truck"></i>
                    <span class="quick-link-text">Deliveries</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=report" class="quick-link">
                    <i class="fa fa-bar-chart-o"></i>
                    <span class="quick-link-text">Reports</span>
                    <i class="fa fa-chevron-right"></i>
                </a>
            </div>

            <!-- System Info -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-info-circle"></i> <?php echo $com_id > 0 ? 'Company Stats' : 'System Stats'; ?>
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
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Pending:</strong></span>
                        <span style="color: <?php echo $pending_orders > 0 ? '#dc3545' : '#28a745'; ?>;"><?php echo $pending_orders; ?></span>
                    </div>
                    <?php if ($com_id > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Viewing:</strong></span>
                        <span style="color: #667eea;"><?php echo htmlspecialchars(substr($com_name, 0, 15)); ?></span>
                    </div>
                    <?php endif; ?>
                        <span><strong>Updated:</strong></span>
                        <span><?php echo date('H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; // End of User Dashboard ?>
