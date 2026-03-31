<?php
/**
 * Dashboard View — Admin Panel + User Dashboard
 *
 * Variables from DashboardController::index():
 *   $com_id, $com_name, $user_level, $is_admin, $is_super_admin
 *   $show_admin_panel, $show_user_dashboard
 *   $admin  — array of admin stats (only if $is_admin)
 *   $dashboard — array of user/company stats (only if $show_user_dashboard)
 *   $dev_tools — array of docker settings (only if $is_super_admin)
 *   $flash  — flash messages from POST
 */

// Language setup
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$t = [
    'en' => [
        'today' => 'Today',
        '7_days' => '7 Days',
        '30_days' => '30 Days',
        'this_year' => 'This Year',
        'all_time' => 'All Time',
        'last_30_days' => 'Last 30 Days',
        'period' => 'Period',
        'business_summary' => 'Business Summary Report',
        'stage' => 'Stage',
        'count' => 'Count',
        'conversion' => 'Conversion',
        'purchase_requests' => 'Purchase Requests',
        'quotations' => 'Quotations',
        'purchase_orders' => 'Purchase Orders',
        'invoices' => 'Invoices',
        'tax_invoices' => 'Tax Invoices',
        'top_customers' => 'Top Customers',
        'customer' => 'Customer',
        'no_data' => 'No data',
        'full_report' => 'Full Report',
        'sales_today' => 'Sales Today',
        'last_24h' => 'Last 24 hours',
        'month_sales' => 'Month Sales',
        'current_month' => 'Current month',
        'pending_orders' => 'Pending Orders',
        'action_needed' => 'Action needed',
        'all_clear' => 'All clear',
        'total_orders' => 'Total Orders',
        'invoices_month' => 'Invoices (This Month)',
        'customer_invoices' => 'Customer invoices',
        'tax_invoices_month' => 'Tax Invoices (This Month)',
        'tax_docs_issued' => 'Tax documents issued',
        'revenue_expenses' => 'Revenue vs Expenses (12 Months)',
        'payment_status' => 'Payment Status',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'unpaid' => 'Unpaid',
        'order_status' => 'Order Status',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'recent_payments' => 'Recent Payments',
        'view_all' => 'View All',
        'po_no' => 'PO #',
        'description' => 'Description',
        'date' => 'Date',
        'amount' => 'Amount',
        'payment_method' => 'Payment Method',
        'recent_invoices' => 'Recent Invoices',
        'inv_no' => 'INV #',
        'status' => 'Status',
        'active' => 'Active',
        'unknown' => 'Unknown',
        'system_monitor' => 'System Monitor',
        'developer_tools' => 'Developer Tools',
    ],
    'th' => [
        'today' => 'วันนี้',
        '7_days' => '7 วัน',
        '30_days' => '30 วัน',
        'this_year' => 'ปีนี้',
        'all_time' => 'ทั้งหมด',
        'last_30_days' => '30 วันล่าสุด',
        'period' => 'ช่วงเวลา',
        'business_summary' => 'รายงานสรุปธุรกิจ',
        'stage' => 'ขั้นตอน',
        'count' => 'จำนวน',
        'conversion' => 'อัตราแปลง',
        'purchase_requests' => 'คำขอซื้อ',
        'quotations' => 'ใบเสนอราคา',
        'purchase_orders' => 'ใบสั่งซื้อ',
        'invoices' => 'ใบแจ้งหนี้',
        'tax_invoices' => 'ใบกำกับภาษี',
        'top_customers' => 'ลูกค้าอันดับต้น',
        'customer' => 'ลูกค้า',
        'no_data' => 'ไม่มีข้อมูล',
        'full_report' => 'รายงานฉบับเต็ม',
        'sales_today' => 'ยอดขายวันนี้',
        'last_24h' => '24 ชั่วโมงล่าสุด',
        'month_sales' => 'ยอดขายเดือนนี้',
        'current_month' => 'เดือนปัจจุบัน',
        'pending_orders' => 'คำสั่งซื้อที่รอ',
        'action_needed' => 'ต้องดำเนินการ',
        'all_clear' => 'เรียบร้อย',
        'total_orders' => 'คำสั่งซื้อทั้งหมด',
        'invoices_month' => 'ใบแจ้งหนี้ (เดือนนี้)',
        'customer_invoices' => 'ใบแจ้งหนี้ลูกค้า',
        'tax_invoices_month' => 'ใบกำกับภาษี (เดือนนี้)',
        'tax_docs_issued' => 'เอกสารภาษีที่ออก',
        'revenue_expenses' => 'รายรับ vs รายจ่าย (12 เดือน)',
        'payment_status' => 'สถานะการชำระเงิน',
        'paid' => 'ชำระแล้ว',
        'partial' => 'ชำระบางส่วน',
        'unpaid' => 'ค้างชำระ',
        'order_status' => 'สถานะคำสั่งซื้อ',
        'pending' => 'รอดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'recent_payments' => 'การชำระเงินล่าสุด',
        'view_all' => 'ดูทั้งหมด',
        'po_no' => 'PO #',
        'description' => 'รายละเอียด',
        'date' => 'วันที่',
        'amount' => 'จำนวนเงิน',
        'payment_method' => 'วิธีชำระเงิน',
        'recent_invoices' => 'ใบแจ้งหนี้ล่าสุด',
        'inv_no' => 'INV #',
        'status' => 'สถานะ',
        'active' => 'ใช้งาน',
        'unknown' => 'ไม่ทราบ',
        'system_monitor' => 'ระบบติดตาม',
        'developer_tools' => 'เครื่องมือนักพัฒนา',
    ],
][$lang];

// Helper functions
function format_currency($amount) {
    return '฿' . number_format($amount, 2);
}

function get_status_badge($status) {
    global $t;
    if ($status == 0) {
        return '<span class="badge" style="background: #ffc107; color: black;">' . $t['active'] . '</span>';
    } else if ($status == 1) {
        return '<span class="badge" style="background: #28a745; color: white;">' . $t['completed'] . '</span>';
    }
    return '<span class="badge" style="background: #6c757d; color: white;">' . $t['unknown'] . '</span>';
}
?>
<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; }
    .dashboard-wrapper { padding: 20px; max-width: 1400px; margin: 0 auto; }
    .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25); color: white; }
    .dashboard-title { font-size: 28px; font-weight: 700; margin: 0; }
    .dashboard-subtitle { font-size: 14px; opacity: 0.9; margin-top: 5px; }
    .kpi-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: transform 0.2s ease, box-shadow 0.2s ease; border-left: 4px solid #667eea; }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
    .kpi-card.alert { border-left-color: #ef4444; }
    .kpi-card.success { border-left-color: #10b981; }
    .kpi-card.warning { border-left-color: #f59e0b; }
    .kpi-icon { font-size: 28px; margin-bottom: 10px; display: inline-block; }
    .kpi-icon.primary { color: #667eea; }
    .kpi-icon.success { color: #10b981; }
    .kpi-icon.warning { color: #f59e0b; }
    .kpi-icon.danger { color: #ef4444; }
    .kpi-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .kpi-value { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
    .kpi-change { font-size: 12px; color: #6b7280; }
    .content-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .card-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #1f2937; padding-bottom: 12px; border-bottom: 2px solid #667eea; }
    .card-title i { margin-right: 10px; color: #667eea; }
    .table-responsive { border-radius: 8px; overflow: hidden; }
    .table { margin-bottom: 0; font-size: 13px; }
    .table thead th { background: #f9fafb; color: #1f2937; font-weight: 600; border: none; padding: 14px 12px; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .table tbody td { padding: 14px 12px; border-color: #e5e7eb; vertical-align: middle; }
    .table tbody tr:hover { background-color: rgba(102, 126, 234, 0.03); }
    .quick-link { display: flex; align-items: center; padding: 14px 16px; border-radius: 10px; background: white; border: 1px solid #e5e7eb; color: #1f2937; text-decoration: none; margin-bottom: 10px; transition: all 0.2s ease; }
    .quick-link:hover { transform: translateY(-2px); border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); color: #1f2937; text-decoration: none; }
    .quick-link i { font-size: 18px; margin-right: 12px; min-width: 25px; text-align: center; color: #667eea; }
    .quick-link-text { flex: 1; font-weight: 600; font-size: 14px; }
    .empty-state { text-align: center; padding: 30px 20px; color: #6b7280; }
    .empty-state i { font-size: 40px; margin-bottom: 12px; color: #9ca3af; }
    .empty-state p { margin: 0; font-size: 14px; }
    .kpi-row { margin-bottom: 20px; }
    .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.25); }
    .stat-value { font-size: 32px; font-weight: 700; margin: 10px 0; }
    .stat-label { font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge { display: inline-flex; align-items: center; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-success { background-color: #d1fae5; color: #10b981; }
    .badge-warning { background-color: #fef3c7; color: #d97706; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; margin: 0 2px; font-size: 12px; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; background: rgba(102, 126, 234, 0.1); color: #667eea; text-decoration: none; }
    .action-btn:hover { background: #667eea; color: white; }
</style>

<div class="dashboard-wrapper">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title"><i class="fa fa-tachometer"></i> <?=$xml->dashboard ?? 'Dashboard'?></h2>
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

    <?php if (!empty($flash)): ?>
    <?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?php echo $msg['type'] === 'success' ? 'success' : 'danger'; ?>" style="border-radius: 8px;">
        <i class="fa fa-<?php echo $msg['type'] === 'success' ? 'check' : 'times'; ?>"></i>
        <?php echo htmlspecialchars($msg['msg']); ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($show_admin_panel): ?>
    <!-- Admin Management Panel -->
    <div class="row kpi-row">
        <div class="col-md-12">
            <div class="content-card" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; margin-bottom: 20px;">
                <h5 style="color: #fff; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 15px;">
                <i class="fa fa-shield"></i> <?=$xml->admin_management_panel ?? 'Admin Control Panel'?>
                </h5>
                <div class="row">
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: #667eea;"><?php echo $admin['total_users']; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Total Users</div>
                            <div style="font-size: 10px; margin-top: 5px;">
                                <span style="color: #51cf66;"><?php echo $admin['users_by_role'][0]; ?> Users</span> |
                                <span style="color: #ffd43b;"><?php echo $admin['users_by_role'][1]; ?> Admins</span> |
                                <span style="color: #ff6b6b;"><?php echo $admin['users_by_role'][2]; ?> Super</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: #51cf66;"><?php echo $admin['total_companies']; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Companies</div>
                            <div style="font-size: 10px; margin-top: 5px; color: #51cf66;">
                                <?php echo $admin['active_companies']; ?> active (30d)
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: <?php echo $admin['locked_accounts'] > 0 ? '#ff6b6b' : '#51cf66'; ?>;"><?php echo $admin['locked_accounts']; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Locked Accounts</div>
                            <?php if ($admin['locked_accounts'] > 0): ?>
                            <div style="font-size: 10px; margin-top: 5px; color: #ff6b6b;">
                                <a href="index.php?page=user" style="color: #ff6b6b;">View & Unlock</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div style="text-align: center; padding: 15px;">
                            <div style="font-size: 32px; color: <?php echo $admin['failed_logins'] > 10 ? '#ff6b6b' : '#ffd43b'; ?>;"><?php echo $admin['failed_logins']; ?></div>
                            <div style="font-size: 12px; color: #aaa;">Failed Logins (24h)</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-8">
                        <div style="padding: 15px;">
                            <div style="font-size: 12px; color: #aaa; margin-bottom: 10px;">Quick Admin Actions</div>
                            <?php if ($is_super_admin): ?>
                            <a href="index.php?page=user" class="btn btn-sm" style="background: #667eea; color: white; margin: 2px;">
                                <i class="fa fa-users"></i> <?=$xml->manage_users ?? 'Manage Users'?>
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
            <div class="company-selector-card">
                <div class="company-selector-header">
                    <div class="selector-title">
                        <i class="fa fa-building"></i>
                        <div>
                            <h5>Select Company to View Data</h5>
                            <p>Choose a company to view their specific business data</p>
                        </div>
                    </div>
                    <?php if($com_id > 0): ?>
                    <a href="index.php?page=remote&clear=1" class="btn-clear-company">
                        <i class="fa fa-times"></i> Clear Selection
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Smart Search -->
                <div class="company-search-box">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" id="companySearchInput" class="company-search-input" 
                           placeholder="<?=$xml->search_companies_placeholder ?? 'Search companies by name, contact, email...'?>" 
                           autocomplete="off">
                    <div id="companySearchResults" class="company-search-results"></div>
                </div>
                
                <!-- Quick Selection Grid -->
                <div class="quick-selection-label">
                    <i class="fa fa-clock-o"></i> Recently Active Companies
                </div>
                <?php $quick_companies = $admin['quick_companies'] ?? null; ?>
                <div class="company-quick-grid">
                    <?php if($quick_companies && mysqli_num_rows($quick_companies) > 0): ?>
                        <?php while($qc = mysqli_fetch_assoc($quick_companies)): ?>
                        <a href="index.php?page=remote&select_company=<?php echo $qc['id']; ?>" class="company-quick-card <?php echo ($com_id == $qc['id']) ? 'active' : ''; ?>">
                            <div class="company-quick-logo">
                                <?php if(!empty($qc['logo'])): ?>
                                <img src="upload/<?php echo htmlspecialchars($qc['logo']); ?>" alt="">
                                <?php else: ?>
                                <i class="fa fa-building"></i>
                                <?php endif; ?>
                            </div>
                            <div class="company-quick-info">
                                <div class="company-quick-name"><?php echo htmlspecialchars(substr($qc['name_en'] ?: $qc['name_th'], 0, 25)); ?></div>
                                <div class="company-quick-meta">
                                    <?php if($qc['vender'] == '1' && $qc['customer'] == '1'): ?>
                                    <span class="badge-both">Both</span>
                                    <?php elseif($qc['vender'] == '1'): ?>
                                    <span class="badge-vendor">Vendor</span>
                                    <?php elseif($qc['customer'] == '1'): ?>
                                    <span class="badge-customer">Customer</span>
                                    <?php endif; ?>
                                    <?php if($qc['last_activity']): ?>
                                    <span class="last-activity"><?php echo date('M d', strtotime($qc['last_activity'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if($com_id == $qc['id']): ?>
                            <div class="company-selected-badge"><i class="fa fa-check"></i></div>
                            <?php endif; ?>
                        </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                
                <div class="company-selector-footer">
                    <a href="index.php?page=company" class="btn-browse-all">
                        <i class="fa fa-th-list"></i> Browse All Companies
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .company-selector-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; overflow: hidden; }
    .company-selector-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; color: #fff; }
    .selector-title { display: flex; align-items: center; gap: 14px; }
    .selector-title > i { font-size: 28px; opacity: 0.9; }
    .selector-title h5 { margin: 0; font-size: 18px; font-weight: 700; }
    .selector-title p { margin: 4px 0 0 0; font-size: 13px; opacity: 0.85; }
    .btn-clear-company { background: rgba(255,255,255,0.2); color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; }
    .btn-clear-company:hover { background: rgba(255,255,255,0.3); color: #fff; text-decoration: none; }
    .company-search-box { padding: 20px 24px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; position: relative; }
    .company-search-input { width: 100%; height: 48px; padding: 12px 16px 12px 46px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; background: #fff; transition: all 0.2s ease; }
    .company-search-input:focus { border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,0.12); outline: none; }
    .company-search-box .search-icon { position: absolute; left: 40px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; }
    .company-search-results { position: absolute; top: 100%; left: 24px; right: 24px; background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); border: 1px solid #e5e7eb; max-height: 320px; overflow-y: auto; z-index: 1000; display: none; }
    .company-search-results.show { display: block; }
    .search-result-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; transition: background 0.15s ease; text-decoration: none; color: inherit; }
    .search-result-item:hover { background: #f1f5f9; text-decoration: none; }
    .search-result-item .result-logo { width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .search-result-item .result-logo img { width: 100%; height: 100%; object-fit: cover; }
    .search-result-item .result-logo i { color: #94a3b8; }
    .search-result-item .result-info { flex: 1; }
    .search-result-item .result-name { font-weight: 600; color: #1e293b; font-size: 14px; }
    .search-result-item .result-meta { font-size: 12px; color: #64748b; }
    .search-no-results { padding: 24px; text-align: center; color: #64748b; }
    .search-no-results i { font-size: 32px; margin-bottom: 8px; opacity: 0.5; }
    .quick-selection-label { padding: 16px 24px 8px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px; }
    .company-quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 12px 24px 24px; }
    @media (max-width: 1200px) { .company-quick-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 900px) { .company-quick-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .company-quick-grid { grid-template-columns: 1fr; } }
    .company-quick-card { display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8fafc; border: 2px solid #e5e7eb; border-radius: 12px; text-decoration: none; color: inherit; transition: all 0.2s ease; position: relative; }
    .company-quick-card:hover { border-color: #667eea; background: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.15); text-decoration: none; }
    .company-quick-card.active { border-color: #667eea; background: linear-gradient(135deg, rgba(102,126,234,0.08) 0%, rgba(118,75,162,0.08) 100%); }
    .company-quick-logo { width: 44px; height: 44px; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
    .company-quick-logo img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
    .company-quick-logo i { font-size: 18px; color: #94a3b8; }
    .company-quick-info { flex: 1; min-width: 0; }
    .company-quick-name { font-weight: 600; font-size: 14px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .company-quick-meta { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
    .company-quick-meta .badge-vendor, .company-quick-meta .badge-customer, .company-quick-meta .badge-both { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
    .company-quick-meta .badge-vendor { background: #dbeafe; color: #1d4ed8; }
    .company-quick-meta .badge-customer { background: #dcfce7; color: #15803d; }
    .company-quick-meta .badge-both { background: #fef3c7; color: #b45309; }
    .company-quick-meta .last-activity { font-size: 11px; color: #94a3b8; }
    .company-selected-badge { position: absolute; top: -6px; right: -6px; width: 22px; height: 22px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; box-shadow: 0 2px 8px rgba(102,126,234,0.4); }
    .company-selector-footer { padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e5e7eb; text-align: center; }
    .btn-browse-all { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s ease; }
    .btn-browse-all:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(102,126,234,0.4); color: #fff; text-decoration: none; }
    </style>
    
    <script>
    // Smart Company Search
    (function() {
        const searchInput = document.getElementById('companySearchInput');
        const resultsContainer = document.getElementById('companySearchResults');
        let searchTimeout;
        
        if (!searchInput || !resultsContainer) return;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);
            if (query.length < 2) { resultsContainer.classList.remove('show'); return; }
            searchTimeout = setTimeout(function() {
                fetch('index.php?page=company_search_api&q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            resultsContainer.innerHTML = '<div class="search-no-results"><i class="fa fa-search"></i><div>No companies found</div></div>';
                        } else {
                            resultsContainer.innerHTML = data.map(company => `
                                <a href="index.php?page=remote&select_company=${company.id}" class="search-result-item">
                                    <div class="result-logo">
                                        ${company.logo ? `<img src="upload/${company.logo}" alt="">` : '<i class="fa fa-building"></i>'}
                                    </div>
                                    <div class="result-info">
                                        <div class="result-name">${company.name_en || company.name_th}</div>
                                        <div class="result-meta">${company.contact || ''} ${company.email ? '• ' + company.email : ''}</div>
                                    </div>
                                </a>
                            `).join('');
                        }
                        resultsContainer.classList.add('show');
                    })
                    .catch(err => console.error('Search error:', err));
            }, 300);
        });
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.remove('show');
            }
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && resultsContainer.innerHTML) {
                resultsContainer.classList.add('show');
            }
        });
    })();
    </script>
    
    <!-- Business Summary Report -->
    <div class="row kpi-row">
        <div class="col-md-12">
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-bar-chart-o"></i> <?= $t['business_summary'] ?>
                    <div style="float: right;">
                        <?php $rp = $admin['report_period'] ?? 'month'; ?>
                        <a href="?page=dashboard&report_period=today" class="btn btn-sm <?php echo $rp == 'today' ? 'btn-primary' : 'btn-default'; ?>"><?= $t['today'] ?></a>
                        <a href="?page=dashboard&report_period=week" class="btn btn-sm <?php echo $rp == 'week' ? 'btn-primary' : 'btn-default'; ?>"><?= $t['7_days'] ?></a>
                        <a href="?page=dashboard&report_period=month" class="btn btn-sm <?php echo $rp == 'month' ? 'btn-primary' : 'btn-default'; ?>"><?= $t['30_days'] ?></a>
                        <a href="?page=dashboard&report_period=year" class="btn btn-sm <?php echo $rp == 'year' ? 'btn-primary' : 'btn-default'; ?>"><?= $t['this_year'] ?></a>
                        <a href="?page=dashboard&report_period=all" class="btn btn-sm <?php echo $rp == 'all' ? 'btn-primary' : 'btn-default'; ?>"><?= $t['all_time'] ?></a>
                    </div>
                </h5>
                <p style="color: #6c757d; margin-bottom: 15px;"><?= $t['period'] ?>: <strong><?php echo $admin['report_period_label'] ?? $t['last_30_days']; ?></strong></p>
                
                <?php $rs = $admin['report_summary'] ?? []; $pr_count = $rs['total_pr'] ?? 0; ?>
                <div class="row">
                    <div class="col-md-7">
                        <table class="table table-bordered" style="font-size: 13px;">
                            <thead style="background: #f8f9fa;">
                                <tr><th><?= $t['stage'] ?></th><th class="text-center"><?= $t['count'] ?></th><th class="text-center"><?= $t['conversion'] ?></th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fa fa-file-o" style="color: #667eea;"></i> <?= $t['purchase_requests'] ?></td>
                                    <td class="text-center"><strong><?php echo $rs['total_pr'] ?? 0; ?></strong></td>
                                    <td class="text-center">-</td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-check" style="color: #17a2b8;"></i> <?= $t['quotations'] ?></td>
                                    <td class="text-center"><strong><?php echo $rs['total_qa'] ?? 0; ?></strong></td>
                                    <td class="text-center"><?php echo $pr_count > 0 ? round(($rs['total_qa'] ?? 0) / $pr_count * 100) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-shopping-cart" style="color: #ffc107;"></i> <?= $t['purchase_orders'] ?></td>
                                    <td class="text-center"><strong><?php echo $rs['total_po'] ?? 0; ?></strong></td>
                                    <td class="text-center"><?php echo $pr_count > 0 ? round(($rs['total_po'] ?? 0) / $pr_count * 100) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-file-text-o" style="color: #28a745;"></i> <?= $t['invoices'] ?></td>
                                    <td class="text-center"><strong><?php echo $rs['total_iv'] ?? 0; ?></strong></td>
                                    <td class="text-center"><?php echo $pr_count > 0 ? round(($rs['total_iv'] ?? 0) / $pr_count * 100) : 0; ?>%</td>
                                </tr>
                                <tr style="background: #e8f5e9;">
                                    <td><i class="fa fa-money" style="color: #28a745;"></i> <strong><?= $t['tax_invoices'] ?></strong></td>
                                    <td class="text-center"><strong style="color: #28a745;"><?php echo $rs['total_tax'] ?? 0; ?></strong></td>
                                    <td class="text-center"><strong style="color: #28a745;"><?php echo $pr_count > 0 ? round(($rs['total_tax'] ?? 0) / $pr_count * 100) : 0; ?>%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="col-md-5">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-users"></i> <?= $t['top_customers'] ?></h6>
                        <table class="table table-sm" style="font-size: 12px;">
                            <thead><tr><th><?= $t['customer'] ?></th><th class="text-center">TX</th><th class="text-center">INV</th></tr></thead>
                            <tbody>
                                <?php $top_customers = $admin['top_customers'] ?? null; ?>
                                <?php if($top_customers && mysqli_num_rows($top_customers) > 0): ?>
                                    <?php while($tc = mysqli_fetch_assoc($top_customers)): ?>
                                    <tr>
                                        <td><a href="index.php?page=remote&select_company=<?php echo $tc['id']; ?>" style="color: #333;"><?php echo htmlspecialchars(substr($tc['name_en'] ?: $tc['name_th'], 0, 20)); ?></a></td>
                                        <td class="text-center"><?php echo $tc['tx_count']; ?></td>
                                        <td class="text-center"><?php echo $tc['invoice_count']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted"><?= $t['no_data'] ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <a href="index.php?page=report" class="btn btn-sm btn-default btn-block">
                            <i class="fa fa-external-link"></i> <?= $t['full_report'] ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Developer Tools Panel (Super Admin Only) -->
    <?php if ($is_super_admin): ?>
    <?php 
        $dt = $dev_tools;
        $docker_enabled = $dt['docker_enabled'] ?? false;
        $container_mgr_enabled = $dt['container_mgr_enabled'] ?? false;
        $docker_setting = $dt['docker_setting'] ?? 'auto';
        $container_mgr_setting = $dt['container_mgr_setting'] ?? 'off';
        $is_docker_env = $dt['is_docker_env'] ?? false;
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
                        <form method="POST" action="index.php?page=dashboard_store" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                            <span style="color: #333; font-weight: 500; min-width: 160px;"><i class="fa fa-bug"></i> Docker Debug:</span>
                            <select name="docker_tools_setting" style="padding: 5px 10px; border: 1px solid #ced4da; border-radius: 4px; background: white; min-width: 180px;">
                                <option value="auto" <?= $docker_setting === 'auto' ? 'selected' : '' ?>>🔄 Auto <?= $is_docker_env ? '(Docker ✓)' : '(No Docker)' ?></option>
                                <option value="on" <?= $docker_setting === 'on' ? 'selected' : '' ?>>✅ On</option>
                                <option value="off" <?= $docker_setting === 'off' ? 'selected' : '' ?>>❌ Off</option>
                            </select>
                            <button type="submit" style="padding: 5px 12px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Save</button>
                        </form>
                        
                        <form method="POST" action="index.php?page=dashboard_store" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                            <span style="color: #333; font-weight: 500; min-width: 160px;"><i class="fa fa-server"></i> Container Manager:</span>
                            <select name="container_manager_setting" style="padding: 5px 10px; border: 1px solid #ced4da; border-radius: 4px; background: white; min-width: 180px;">
                                <option value="off" <?= $container_mgr_setting === 'off' ? 'selected' : '' ?>>❌ Off (Default)</option>
                                <option value="auto" <?= $container_mgr_setting === 'auto' ? 'selected' : '' ?>>🔄 Auto <?= $is_docker_env ? '(Docker ✓)' : '(No Docker)' ?></option>
                                <option value="on" <?= $container_mgr_setting === 'on' ? 'selected' : '' ?>>✅ On</option>
                            </select>
                            <button type="submit" style="padding: 5px 12px; background: #8e44ad; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Save</button>
                        </form>
                    </div>
                    <p style="color: #6c757d; font-size: 11px; margin: 10px 0 0 0;">
                        <strong>Docker Debug</strong> (Docker Test, Container Debug): Read-only debug tools, default Auto. 
                        <strong>Container Manager</strong>: Has start/stop/restart actions, default Off for safety.
                    </p>
                </div>
                
                <p style="color: #6c757d; margin-bottom: 15px;">Testing, debugging, and infrastructure monitoring tools</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-bug"></i> Debug</h6>
                        <a href="index.php?page=test_crud" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-database" style="color: #3498db;"></i> <strong>CRUD Test</strong><br><small style="color: #6c757d;">Test database operations</small>
                        </a>
                        <a href="index.php?page=debug_session" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-key" style="color: #e67e22;"></i> <strong>Session Debug</strong><br><small style="color: #6c757d;">View session variables</small>
                        </a>
                        <a href="index.php?page=debug_invoice" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-file-text-o" style="color: #27ae60;"></i> <strong>Invoice Debug</strong><br><small style="color: #6c757d;">Debug invoice access</small>
                        </a>
                        <a href="index.php?page=api_lang_debug" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-language" style="color: #2980b9;"></i> <strong>Language Debug</strong><br><small style="color: #6c757d;">Debug localization API</small>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-server"></i> Infrastructure</h6>
                        <?php if ($docker_enabled): ?>
                        <a href="index.php?page=docker_test" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-cloud" style="color: #1abc9c;"></i> <strong>Docker Test</strong><br><small style="color: #6c757d;">Test Docker socket</small>
                        </a>
                        <a href="index.php?page=test_containers" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-cube" style="color: #9b59b6;"></i> <strong>Container Debug</strong><br><small style="color: #6c757d;">Raw container data</small>
                        </a>
                        <?php endif; ?>
                        <a href="index.php?page=containers" class="btn btn-block" style="background: <?= $container_mgr_enabled ? '#f8f9fa' : '#f0f0f0' ?>; border: 1px solid <?= $container_mgr_enabled ? '#dee2e6' : '#e0e0e0' ?>; color: <?= $container_mgr_enabled ? '#333' : '#999' ?>; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-server" style="color: <?= $container_mgr_enabled ? '#8e44ad' : '#ccc' ?>;"></i> 
                            <strong>Container Manager</strong>
                            <?php if (!$container_mgr_enabled): ?>
                            <span style="font-size: 10px; background: #e74c3c; color: white; padding: 2px 6px; border-radius: 3px; margin-left: 5px;">Off</span>
                            <?php endif; ?>
                            <br><small style="color: #6c757d;"><?= $container_mgr_enabled ? 'Manage containers' : 'Click to enable' ?></small>
                        </a>
                        <a href="index.php?page=monitoring" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-dashboard" style="color: #e74c3c;"></i> <strong>System Monitor</strong><br><small style="color: #6c757d;">Health & performance</small>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <h6 style="color: #333; margin-bottom: 10px;"><i class="fa fa-shield"></i> Admin Quick Links</h6>
                        <a href="index.php?page=user" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-users" style="color: #3498db;"></i> <strong>User Management</strong><br><small style="color: #6c757d;">Manage system users</small>
                        </a>
                        <a href="index.php?page=audit_log" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-history" style="color: #c0392b;"></i> <strong>Audit Log</strong><br><small style="color: #6c757d;">View activity history</small>
                        </a>
                        <a href="index.php?page=payment_method_list" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-credit-card-alt" style="color: #27ae60;"></i> <strong>Payment Methods</strong><br><small style="color: #6c757d;">Configure payments</small>
                        </a>
                        <a href="index.php?page=payment_gateway_config" class="btn btn-block" style="background: #f8f9fa; border: 1px solid #dee2e6; color: #333; text-align: left; padding: 10px 15px; margin-bottom: 5px;">
                            <i class="fa fa-cogs" style="color: #8e44ad;"></i> <strong>Gateway Config</strong><br><small style="color: #6c757d;">Payment gateway settings</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; // End of Admin Panel ?>

    <?php if ($show_user_dashboard): ?>
    <!-- ============ USER DASHBOARD (Company-specific data) ============ -->
    <?php $u = $dashboard; ?>
    
    <!-- KPI Cards Row -->
    <div class="row kpi-row">
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card">
                <div class="kpi-icon primary"><i class="fa fa-dollar-sign"></i></div>
                <div class="kpi-label"><?= $t['sales_today'] ?></div>
                <div class="kpi-value"><?php echo format_currency($u['sales_today']); ?></div>
                <div class="kpi-change"><i class="fa fa-arrow-up"></i> <?= $t['last_24h'] ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card success">
                <div class="kpi-icon success"><i class="fa fa-chart-line"></i></div>
                <div class="kpi-label"><?= $t['month_sales'] ?></div>
                <div class="kpi-value"><?php echo format_currency($u['sales_month']); ?></div>
                <div class="kpi-change"><?= $t['current_month'] ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card warning">
                <div class="kpi-icon warning"><i class="fa fa-hourglass-half"></i></div>
                <div class="kpi-label"><?= $t['pending_orders'] ?></div>
                <div class="kpi-value"><?php echo $u['pending_orders']; ?></div>
                <div class="kpi-change">
                    <?php if($u['pending_orders'] > 0): ?>
                        <span class="badge badge-warning"><?= $t['action_needed'] ?></span>
                    <?php else: ?>
                        <span class="badge badge-success"><?= $t['all_clear'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card alert">
                <div class="kpi-icon danger"><i class="fa fa-shopping-cart"></i></div>
                <div class="kpi-label"><?= $t['total_orders'] ?></div>
                <div class="kpi-value"><?php echo $u['total_orders']; ?></div>
                <div class="kpi-change"><?= $t['all_time'] ?></div>
            </div>
        </div>
    </div>

    <!-- Invoice KPI Cards Row -->
    <div class="row kpi-row">
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card" style="border-left: 4px solid #4caf50;">
                <div class="kpi-icon" style="color: #4caf50;"><i class="fa fa-file-invoice"></i></div>
                <div class="kpi-label"><?= $t['invoices_month'] ?></div>
                <div class="kpi-value"><?php echo $u['total_invoices']; ?></div>
                <div class="kpi-change"><?= $t['customer_invoices'] ?></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kpi-card" style="border-left: 4px solid #2196f3;">
                <div class="kpi-icon" style="color: #2196f3;"><i class="fa fa-receipt"></i></div>
                <div class="kpi-label"><?= $t['tax_invoices_month'] ?></div>
                <div class="kpi-value"><?php echo $u['total_tax_invoices']; ?></div>
                <div class="kpi-change"><?= $t['tax_docs_issued'] ?></div>
            </div>
        </div>
    </div>

    <!-- ============ CHARTS SECTION ============ -->
    <div class="row kpi-row">
        <div class="col-md-8">
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-line-chart"></i> <?= $t['revenue_expenses'] ?>
                </h5>
                <div style="position: relative; height: 320px;">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-pie-chart"></i> <?= $t['payment_status'] ?>
                </h5>
                <div style="position: relative; height: 200px;">
                    <canvas id="paymentStatusChart"></canvas>
                </div>
                <div style="margin-top: 12px; font-size: 12px;">
                    <?php $ps = $u['payment_status'] ?? ['paid'=>0,'partial'=>0,'unpaid'=>0]; ?>
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div><span style="display: inline-block; width: 10px; height: 10px; background: #10b981; border-radius: 50%; margin-right: 4px;"></span><?= $t['paid'] ?>: <strong><?php echo $ps['paid']; ?></strong></div>
                        <div><span style="display: inline-block; width: 10px; height: 10px; background: #f59e0b; border-radius: 50%; margin-right: 4px;"></span><?= $t['partial'] ?>: <strong><?php echo $ps['partial']; ?></strong></div>
                        <div><span style="display: inline-block; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; margin-right: 4px;"></span><?= $t['unpaid'] ?>: <strong><?php echo $ps['unpaid']; ?></strong></div>
                    </div>
                </div>
            </div>
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-tasks"></i> <?= $t['order_status'] ?>
                </h5>
                <div style="position: relative; height: 160px;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
                <?php $os = $u['order_status'] ?? ['pending'=>0,'completed'=>0]; ?>
                <div style="margin-top: 12px; font-size: 12px; display: flex; justify-content: space-around; text-align: center;">
                    <div><span style="display: inline-block; width: 10px; height: 10px; background: #f59e0b; border-radius: 50%; margin-right: 4px;"></span><?= $t['pending'] ?>: <strong><?php echo $os['pending']; ?></strong></div>
                    <div><span style="display: inline-block; width: 10px; height: 10px; background: #10b981; border-radius: 50%; margin-right: 4px;"></span><?= $t['completed'] ?>: <strong><?php echo $os['completed']; ?></strong></div>
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
                    <i class="fa fa-money-bill-wave"></i> <?= $t['recent_payments'] ?>
                    <a href="index.php?page=payment_list" style="float: right; font-size: 11px; color: #667eea;"><?= $t['view_all'] ?> <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead><tr><th><?= $t['po_no'] ?></th><th><?= $t['description'] ?></th><th><?= $t['date'] ?></th><th><?= $t['amount'] ?></th><th><?= $t['payment_method'] ?></th><th></th></tr></thead>
                        <tbody>
                            <?php $recent_payments = $u['recent_payments'] ?? null; ?>
                            <?php if($recent_payments && mysqli_num_rows($recent_payments) > 0): ?>
                                <?php while($payment = mysqli_fetch_assoc($recent_payments)): ?>
                                <tr>
                                    <td><strong>#<?php echo $payment['po_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($payment['name'] ?? '', 0, 25)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['date'])); ?></td>
                                    <td><?php echo format_currency($payment['volumn']); ?></td>
                                    <td><?php echo !empty($payment['value']) ? ucfirst($payment['value']) : 'Direct'; ?></td>
                                    <td><a href="index.php?page=po_view&id=<?php echo $payment['po_id']; ?>" class="action-btn" title="View Details"><i class="fa fa-eye"></i></a></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><p>No payments recorded yet</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Purchase Orders -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-shopping-cart"></i> <?= $t['purchase_orders'] ?>
                    <a href="index.php?page=po_list" style="float: right; font-size: 11px; color: #667eea;"><?= $t['view_all'] ?> <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead><tr><th><?= $t['po_no'] ?></th><th><?= $t['description'] ?></th><th>Tax Invoice #</th><th><?= $t['date'] ?></th><th><?= $t['status'] ?></th><th></th></tr></thead>
                        <tbody>
                            <?php $pending_pos = $u['pending_pos'] ?? null; ?>
                            <?php if($pending_pos && mysqli_num_rows($pending_pos) > 0): ?>
                                <?php while($po = mysqli_fetch_assoc($pending_pos)): ?>
                                <tr>
                                    <td><strong>#<?php echo $po['po_id_new'] ?? $po['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($po['name'], 0, 25)); ?></td>
                                    <td><code><?php echo $po['tax'] ?: '-'; ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($po['date'])); ?></td>
                                    <td><?php echo get_status_badge($po['over']); ?></td>
                                    <td><a href="index.php?page=po_view&id=<?php echo $po['id']; ?>" class="action-btn" title="View Details"><i class="fa fa-eye"></i></a></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-check-circle"></i><p>✓ All orders completed</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-file-invoice"></i> <?= $t['recent_invoices'] ?>
                    <a href="index.php?page=compl_list" style="float: right; font-size: 11px; color: #667eea;"><?= $t['view_all'] ?> <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead><tr><th><?= $t['inv_no'] ?></th><th><?= $t['description'] ?></th><th><?= $t['date'] ?></th><th><?= $t['amount'] ?></th><th></th></tr></thead>
                        <tbody>
                            <?php $recent_invoices = $u['recent_invoices'] ?? null; ?>
                            <?php if($recent_invoices && mysqli_num_rows($recent_invoices) > 0): ?>
                                <?php while($invoice = mysqli_fetch_assoc($recent_invoices)): ?>
                                <tr>
                                    <td><strong>#<?php echo $invoice['po_id'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($invoice['description'] ?? '', 0, 25)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['createdate'])); ?></td>
                                    <td><?php echo number_format($invoice['subtotal'] ?? 0, 2); ?></td>
                                    <td>
                                        <a href="index.php?page=compl_view&id=<?php echo $invoice['po_id']; ?>" class="action-btn" title="View Details"><i class="fa fa-eye"></i></a>
                                        <a href="index.php?page=pdf_invoice&id=<?php echo $invoice['po_id']; ?>" class="action-btn" title="Download PDF" target="_blank"><i class="fa fa-file-pdf-o"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5"><div class="empty-state"><i class="fa fa-file"></i><p>No invoices found</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Tax Invoices -->
            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-file-invoice-dollar"></i> <?= $t['tax_invoices'] ?>
                    <a href="index.php?page=compl_list" style="float: right; font-size: 11px; color: #667eea;"><?= $t['view_all'] ?> <i class="fa fa-arrow-right"></i></a>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" style="font-size: 12px;">
                        <thead><tr><th>Tax Inv #</th><th>Description</th><th>Created</th><th>Amount</th><th></th></tr></thead>
                        <tbody>
                            <?php $tax_inv_results = $u['recent_tax_invoices'] ?? null; ?>
                            <?php if($tax_inv_results && mysqli_num_rows($tax_inv_results) > 0): ?>
                                <?php while($tax_inv = mysqli_fetch_assoc($tax_inv_results)): ?>
                                <tr>
                                    <td><strong>#<?php echo $tax_inv['texiv'] ?? 'N/A'; ?></strong></td>
                                    <td><?php echo htmlspecialchars(mb_substr($tax_inv['description'] ?? '', 0, 25)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($tax_inv['texiv_create'])); ?></td>
                                    <td><?php echo number_format($tax_inv['subtotal'] ?? 0, 2); ?></td>
                                    <td><a href="index.php?page=pdf_tax_invoice&id=<?php echo $tax_inv['po_id']; ?>" class="action-btn" title="View PDF" target="_blank"><i class="fa fa-file-text-o"></i></a></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5"><div class="empty-state"><i class="fa fa-receipt"></i><p>No tax invoices found</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <?php if ($is_admin && $com_id > 0): ?>
            <div class="content-card" style="background: #f8f9fa; border-left: 4px solid #667eea;">
                <h5 class="card-title"><i class="fa fa-cog"></i> Admin Actions</h5>
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

            <div class="content-card">
                <h5 class="card-title"><i class="fa fa-bolt"></i> Quick Links</h5>
                <a href="index.php?page=po_list" class="quick-link"><i class="fa fa-shopping-cart"></i><span class="quick-link-text">Purchase Orders</span><i class="fa fa-chevron-right"></i></a>
                <a href="index.php?page=pr_list" class="quick-link"><i class="fa fa-clipboard"></i><span class="quick-link-text">Requests</span><i class="fa fa-chevron-right"></i></a>
                <a href="index.php?page=deliv_list" class="quick-link"><i class="fa fa-truck"></i><span class="quick-link-text">Deliveries</span><i class="fa fa-chevron-right"></i></a>
                <a href="index.php?page=report" class="quick-link"><i class="fa fa-bar-chart-o"></i><span class="quick-link-text">Reports</span><i class="fa fa-chevron-right"></i></a>
            </div>

            <div class="content-card">
                <h5 class="card-title">
                    <i class="fa fa-info-circle"></i> <?php echo $com_id > 0 ? 'Company Stats' : 'System Stats'; ?>
                </h5>
                <div style="font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Total Orders:</strong></span>
                        <span><?php echo $u['total_orders']; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Completed:</strong></span>
                        <span><?php echo $u['completed_orders']; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Pending:</strong></span>
                        <span style="color: <?php echo $u['pending_orders'] > 0 ? '#dc3545' : '#28a745'; ?>;"><?php echo $u['pending_orders']; ?></span>
                    </div>
                    <?php if ($com_id > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span><strong>Viewing:</strong></span>
                        <span style="color: #667eea;"><?php echo htmlspecialchars(substr($com_name, 0, 15)); ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span><strong>Updated:</strong></span>
                        <span><?php echo date('H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if ($show_user_dashboard): ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function() {
    // ---- Revenue vs Expense Bar/Line Chart ----
    <?php
    $rev = $dashboard['monthly_revenue'] ?? [];
    $exp = $dashboard['monthly_expenses'] ?? [];
    $chartLabels = [];
    $chartRevenue = [];
    $chartExpenses = [];
    foreach ($rev as $month => $val) {
        $chartLabels[] = date('M Y', strtotime($month . '-01'));
        $chartRevenue[] = $val;
        $chartExpenses[] = $exp[$month] ?? 0;
    }
    ?>
    var labels = <?php echo json_encode($chartLabels); ?>;
    var revenueData = <?php echo json_encode($chartRevenue); ?>;
    var expenseData = <?php echo json_encode($chartExpenses); ?>;

    var ctx1 = document.getElementById('revenueExpenseChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue (฿)',
                        data: revenueData,
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2
                    },
                    {
                        label: 'Expenses (฿)',
                        data: expenseData,
                        type: 'line',
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#ef4444',
                        fill: true,
                        tension: 0.3,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ฿' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) { return '฿' + (v >= 1000 ? (v/1000).toFixed(0) + 'K' : v); }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ---- Payment Status Doughnut ----
    <?php $ps = $dashboard['payment_status'] ?? ['paid'=>0,'partial'=>0,'unpaid'=>0]; ?>
    var ctx2 = document.getElementById('paymentStatusChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Partial', 'Unpaid'],
                datasets: [{
                    data: [<?php echo $ps['paid']; ?>, <?php echo $ps['partial']; ?>, <?php echo $ps['unpaid']; ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // ---- Order Status Doughnut ----
    <?php $os = $dashboard['order_status'] ?? ['pending'=>0,'completed'=>0]; ?>
    var ctx3 = document.getElementById('orderStatusChart');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Completed'],
                datasets: [{
                    data: [<?php echo $os['pending']; ?>, <?php echo $os['completed']; ?>],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
})();
</script>
<?php endif; ?>
