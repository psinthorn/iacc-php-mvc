<?php
namespace App\Controllers;

use App\Models\ModuleManager;

class ModuleManagerController extends BaseController
{
    private ModuleManager $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ModuleManager();
    }

    /**
     * Require super admin (level >= 2)
     */
    private function requireSuperAdmin(): void
    {
        if ($this->user['level'] < 2) {
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    /**
     * Module Manager list page
     */
    public function index(): void
    {
        $this->requireSuperAdmin();

        $search = trim($_GET['search'] ?? '');
        $companies = $this->model->getCompaniesWithModules($search);
        $stats = $this->model->getStats();
        $totalCompanies = $this->model->getTotalCompanyCount();
        $modules = ModuleManager::MODULES;
        $plans = ModuleManager::PLANS;

        $this->render('module-manager/list', compact('companies', 'stats', 'totalCompanies', 'modules', 'plans', 'search'));
    }

    /**
     * AJAX: Toggle module on/off
     */
    public function toggle(): void
    {
        $this->requireSuperAdmin();
        $this->verifyCsrf();

        $companyId = intval($_POST['company_id'] ?? 0);
        $moduleKey = trim($_POST['module_key'] ?? '');

        if ($companyId <= 0 || empty($moduleKey)) {
            $this->json(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $result = $this->model->toggleModule($companyId, $moduleKey);
        $this->json($result);
    }

    /**
     * AJAX: Update module plan/dates
     */
    public function update(): void
    {
        $this->requireSuperAdmin();
        $this->verifyCsrf();

        $companyId = intval($_POST['company_id'] ?? 0);
        $moduleKey = trim($_POST['module_key'] ?? '');

        if ($companyId <= 0 || empty($moduleKey)) {
            $this->json(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $data = [
            'plan'        => $_POST['plan'] ?? '',
            'usage_limit' => $_POST['usage_limit'] ?? null,
            'valid_from'  => $_POST['valid_from'] ?? null,
            'valid_to'    => $_POST['valid_to'] ?? null,
        ];

        $result = $this->model->updateModule($companyId, $moduleKey, $data);
        $this->json($result);
    }
}
