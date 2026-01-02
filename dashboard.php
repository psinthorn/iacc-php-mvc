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
    // For invoices: check pr.ven_id, pr.cus_id, AND iv.cus_id (invoice recipient)
    $company_filter_iv = " AND (pr.ven_id = $com_id OR pr.cus_id = $com_id OR iv.cus_id = $com_id)";
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

// Invoice statistics (filtered by company via pr relationship - iv.id links to pr.id)
$sql_invoices = "SELECT COUNT(DISTINCT iv.tex) as count FROM iv 
                 JOIN pr ON iv.id = pr.id
                 WHERE DATE(iv.createdate) >= '$month_start' $company_filter_iv";
$result_invoices = mysqli_query($db->conn, $sql_invoices);
$row_invoices = mysqli_fetch_assoc($result_invoices);
$total_invoices = $row_invoices['count'] ?? 0;

// Tax Invoice statistics (filtered by company via pr relationship)
$sql_tax_invoices = "SELECT COUNT(DISTINCT iv.texiv) as count FROM iv 
                     JOIN pr ON iv.id = pr.id
                     WHERE iv.texiv > 0 AND DATE(iv.texiv_create) >= '$month_start' $company_filter_iv";
$result_tax_inv = mysqli_query($db->conn, $sql_tax_invoices);
$row_tax_inv = mysqli_fetch_assoc($result_tax_inv);
$total_tax_invoices = $row_tax_inv['count'] ?? 0;

// Recent invoices (filtered by company via pr relationship)
// Get all parties: vendor (issuer), pr customer, and invoice recipient
$sql_recent_inv = "SELECT iv.*, 
                   pr.ven_id, ven.name_en as vendor_name, 
                   pr.cus_id as pr_cus_id, prcus.name_en as pr_customer_name,
                   ivcus.name_en as iv_customer_name
                   FROM iv 
                   JOIN pr ON iv.id = pr.id
                   LEFT JOIN company ven ON pr.ven_id = ven.id
                   LEFT JOIN company prcus ON pr.cus_id = prcus.id
                   LEFT JOIN company ivcus ON iv.cus_id = ivcus.id
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
            <div class="dashboard-subtitle">
                <?php if ($is_admin && $com_id == 0): ?>
                    <i class="fa fa-globe"></i> System Administration - Global View
                    <div style="font-size: 11px; margin-top: 5px; opacity: 0.8;">Select a company below to view company-specific data</div>
                <?php elseif ($is_admin && $com_id > 0): ?>
                    <i class="fa fa-building"></i> Viewing: <?php echo htmlspecialchars($com_name); ?>
                    <div style="font-size: 11px; margin-top: 5px; opacity: 0.8;">
                        <a href="index.php?page=remote" style="color: rgba(255,255,255,0.9);"><i class="fa fa-exchange-alt"></i> Switch company</a> | 
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
                    <i class="fa fa-shield-alt"></i> Admin Control Panel
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
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>PO / Description</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_payments && mysqli_num_rows($recent_payments) > 0): ?>
                                <?php while($payment = mysqli_fetch_assoc($recent_payments)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment['date'])); ?></td>
                                    <td><strong>#<?php echo $payment['po_id']; ?></strong> <?php echo substr($payment['name'] ?? '', 0, 15); ?></td>
                                    <td><strong><?php echo format_currency($payment['volumn']); ?></strong></td>
                                    <td><?php echo !empty($payment['value']) ? ucfirst($payment['value']) : 'Direct'; ?></td>
                                    <td>
                                        <a href="index.php?page=po_view&id=<?php echo $payment['po_id']; ?>" class="action-btn">View</a>
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
                                <th>PO #</th>
                                <th>Description</th>
                                <th>Tax Invoice #</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($pending_pos && mysqli_num_rows($pending_pos) > 0): ?>
                                <?php while($po = mysqli_fetch_assoc($pending_pos)): ?>
                                <tr>
                                    <td><strong><?php echo $po['po_id_new'] ?? $po['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($po['name'], 0, 25)); ?></td>
                                    <td><code><?php echo $po['tax'] ?: '-'; ?></code></td>
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
                                <th>Invoice #</th>
                                <th>Counterparty</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_invoices && mysqli_num_rows($recent_invoices) > 0): ?>
                                <?php while($invoice = mysqli_fetch_assoc($recent_invoices)): ?>
                                <?php 
                                // Determine counterparty - show the OTHER company in this transaction
                                // If current company is the invoice recipient, show vendor
                                // If current company is the vendor, show invoice recipient
                                if ($invoice['cus_id'] == $com_id) {
                                    // We received this invoice, show who sent it (vendor)
                                    $counterparty = $invoice['vendor_name'] ?? 'N/A';
                                    $direction = '<span style="color:#28a745;" title="Received from"><i class="fa fa-arrow-down"></i></span>';
                                } elseif ($invoice['ven_id'] == $com_id) {
                                    // We issued this invoice, show who received it
                                    $counterparty = $invoice['iv_customer_name'] ?? 'N/A';
                                    $direction = '<span style="color:#007bff;" title="Sent to"><i class="fa fa-arrow-up"></i></span>';
                                } else {
                                    // Related via pr.cus_id
                                    $counterparty = $invoice['vendor_name'] ?? 'N/A';
                                    $direction = '<span style="color:#6c757d;"><i class="fa fa-exchange-alt"></i></span>';
                                }
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $invoice['tex'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo $direction; ?> <?php echo htmlspecialchars(substr($counterparty, 0, 20)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['createdate'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_text = ($invoice['status_iv'] == 1) ? 'Approved' : 'Pending';
                                        $status_color = ($invoice['status_iv'] == 1) ? '#28a745' : '#ffc107';
                                        ?>
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
                    <i class="fa fa-file-invoice-dollar"></i> Tax Invoices Issued
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Tax Inv #</th>
                                <th>Counterparty</th>
                                <th>Created</th>
                                <th>Email Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Tax invoices with actual data (filtered by company via pr relationship - iv.id links to pr.id)
                            $sql_tax_inv_detail = "SELECT iv.*, 
                                                  pr.ven_id, ven.name_en as vendor_name,
                                                  ivcus.name_en as iv_customer_name
                                                  FROM iv 
                                                  JOIN pr ON iv.id = pr.id
                                                  LEFT JOIN company ven ON pr.ven_id = ven.id
                                                  LEFT JOIN company ivcus ON iv.cus_id = ivcus.id
                                                  WHERE iv.texiv > 0 $company_filter_iv
                                                  ORDER BY iv.texiv_create DESC LIMIT 5";
                            $tax_inv_results = mysqli_query($db->conn, $sql_tax_inv_detail);
                            ?>
                            <?php if($tax_inv_results && mysqli_num_rows($tax_inv_results) > 0): ?>
                                <?php while($tax_inv = mysqli_fetch_assoc($tax_inv_results)): ?>
                                <?php 
                                // Determine counterparty
                                if ($tax_inv['cus_id'] == $com_id) {
                                    $counterparty = $tax_inv['vendor_name'] ?? 'N/A';
                                    $direction = '<span style="color:#28a745;" title="Received from"><i class="fa fa-arrow-down"></i></span>';
                                } elseif ($tax_inv['ven_id'] == $com_id) {
                                    $counterparty = $tax_inv['iv_customer_name'] ?? 'N/A';
                                    $direction = '<span style="color:#007bff;" title="Sent to"><i class="fa fa-arrow-up"></i></span>';
                                } else {
                                    $counterparty = $tax_inv['vendor_name'] ?? 'N/A';
                                    $direction = '<span style="color:#6c757d;"><i class="fa fa-exchange-alt"></i></span>';
                                }
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $tax_inv['texiv'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo $direction; ?> <?php echo htmlspecialchars(substr($counterparty, 0, 20)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($tax_inv['texiv_create'])); ?></td>
                                    <td>
                                        <?php if($tax_inv['countmailtax'] > 0): ?>
                                            <span class="badge" style="background: #28a745; color: white;">
                                                <i class="fa fa-check"></i> <?php echo $tax_inv['countmailtax']; ?>x
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
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
                    <i class="fa fa-exchange-alt"></i>
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
                    <i class="fa fa-clipboard-list"></i>
                    <span class="quick-link-text">Requests</span>
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
    <?php endif; // End of User Dashboard ?>
