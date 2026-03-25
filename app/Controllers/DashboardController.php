<?php

namespace App\Controllers;

use App\Models\Dashboard;

class DashboardController extends BaseController
{
    private Dashboard $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Dashboard();
    }

    /**
     * Dashboard index — admin panel + user dashboard.
     */
    public function index(): void
    {
        $comId     = (int) ($_SESSION['com_id'] ?? 0);
        $comName   = $_SESSION['com_name'] ?? '';
        $userLevel = (int) ($_SESSION['user_level'] ?? 0);

        $isAdmin      = ($userLevel >= 1);
        $isSuperAdmin = ($userLevel >= 2);
        $showAdminPanel    = $isAdmin;
        $showUserDashboard = ($comId > 0 || !$isAdmin);

        // Company filter for SQL joins (pr-based)
        $companyFilterPr = '';
        $companyFilterIv = '';
        if ($comId > 0) {
            $companyFilterPr = " AND (pr.ven_id = $comId OR pr.cus_id = $comId)";
            $companyFilterIv = " AND (pr.ven_id = $comId OR pr.cus_id = $comId)";
        }

        // ============ Admin Data ============
        $adminData = [];
        if ($isAdmin) {
            $adminData['total_users']     = $this->model->getTotalUsers();
            $adminData['users_by_role']   = $this->model->getUsersByRole();
            $adminData['total_companies'] = $this->model->getTotalCompanies();
            $adminData['active_companies'] = $this->model->getActiveCompanies(30);
            $adminData['locked_accounts'] = $this->model->getLockedAccounts();
            $adminData['failed_logins']   = $this->model->getFailedLogins(24);

            // Business summary report
            $reportPeriod = $_GET['report_period'] ?? 'month';
            $dateFilters = [
                'today' => ['DATE(pr.date) = CURDATE()', 'Today'],
                'week'  => ['pr.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)', 'Last 7 Days'],
                'month' => ['pr.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)', 'Last 30 Days'],
                'year'  => ['YEAR(pr.date) = YEAR(CURDATE())', 'This Year'],
                'all'   => ['1=1', 'All Time'],
            ];
            $filter = $dateFilters[$reportPeriod] ?? $dateFilters['month'];

            $adminData['report_period']       = $reportPeriod;
            $adminData['report_period_label'] = $filter[1];
            $adminData['report_summary']      = $this->model->getReportSummary($filter[0]);
            $adminData['top_customers']       = $this->model->getTopCustomers($filter[0], 5);
            $adminData['quick_companies']     = $this->model->getQuickCompanies(8);
        }

        // ============ User Dashboard Data ============
        $userData = [];
        if ($showUserDashboard) {
            $userData['sales_today']      = $this->model->getSalesToday($comId, $companyFilterPr);
            $userData['sales_month']      = $this->model->getSalesMonth($comId, $companyFilterPr);
            $userData['pending_orders']   = $this->model->getPendingOrderCount($companyFilterPr);
            $userData['total_orders']     = $this->model->getTotalOrderCount($companyFilterPr);
            $userData['recent_payments']  = $this->model->getRecentPayments($companyFilterPr, 5);
            $userData['pending_pos']      = $this->model->getPendingPOs($companyFilterPr, 5);
            $userData['completed_orders'] = $this->model->getCompletedOrders($companyFilterPr);
            $userData['total_invoices']   = $this->model->getInvoiceCount($companyFilterIv);
            $userData['total_tax_invoices'] = $this->model->getTaxInvoiceCount($companyFilterIv);
            $userData['recent_invoices']  = $this->model->getRecentInvoices($companyFilterIv, 5);
            $userData['recent_tax_invoices'] = $this->model->getRecentTaxInvoices($companyFilterIv, 5);
        }

        // Docker tools settings (for super admin display)
        $devTools = [];
        if ($isSuperAdmin) {
            $devTools['docker_enabled']       = function_exists('is_docker_tools_enabled') ? is_docker_tools_enabled() : true;
            $devTools['container_mgr_enabled'] = function_exists('is_container_manager_enabled') ? is_container_manager_enabled() : false;
            $devTools['docker_setting']       = function_exists('get_docker_tools_setting') ? get_docker_tools_setting('docker_tools') : 'auto';
            $devTools['container_mgr_setting'] = function_exists('get_docker_tools_setting') ? get_docker_tools_setting('container_manager') : 'off';
            $devTools['is_docker_env']        = function_exists('is_running_in_docker') ? is_running_in_docker() : false;
        }

        // Flash messages from POST (Docker settings)
        $flash = $_SESSION['dashboard_flash'] ?? null;
        unset($_SESSION['dashboard_flash']);

        $this->render('dashboard/index', [
            'com_id'              => $comId,
            'com_name'            => $comName,
            'user_level'          => $userLevel,
            'is_admin'            => $isAdmin,
            'is_super_admin'      => $isSuperAdmin,
            'show_admin_panel'    => $showAdminPanel,
            'show_user_dashboard' => $showUserDashboard,
            'admin'               => $adminData,
            'user'                => $userData,
            'dev_tools'           => $devTools,
            'flash'               => $flash,
        ]);
    }

    /**
     * Handle POST actions (Docker settings).
     */
    public function store(): void
    {
        $userLevel = (int) ($_SESSION['user_level'] ?? 0);
        if ($userLevel < 2) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (function_exists('csrf_verify') && !csrf_verify()) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $flash = [];

        if (isset($_POST['docker_tools_setting'])) {
            $setting = $_POST['docker_tools_setting'];
            if (function_exists('save_docker_tools_setting') && save_docker_tools_setting($setting, 'docker_tools')) {
                $flash[] = ['type' => 'success', 'msg' => 'Docker Tools setting updated to: ' . ucfirst($setting)];
            } else {
                $flash[] = ['type' => 'error', 'msg' => 'Failed to update Docker Tools setting'];
            }
        }

        if (isset($_POST['container_manager_setting'])) {
            $setting = $_POST['container_manager_setting'];
            if (function_exists('save_docker_tools_setting') && save_docker_tools_setting($setting, 'container_manager')) {
                $flash[] = ['type' => 'success', 'msg' => 'Container Manager setting updated to: ' . ucfirst($setting)];
            } else {
                $flash[] = ['type' => 'error', 'msg' => 'Failed to update Container Manager setting'];
            }
        }

        $_SESSION['dashboard_flash'] = $flash;
        header('Location: index.php?page=dashboard');
        exit;
    }
}
