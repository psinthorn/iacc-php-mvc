<?php
namespace App\Controllers;

use App\Models\TourAgentProfile;

/**
 * TourAgentController — Tour Agent Profile CRUD
 * 
 * Routes:
 *   tour_agent_list   → index()  — List agents with profiles
 *   tour_agent_make   → make()   — Create/edit form
 *   tour_agent_store  → store()  — POST: save profile
 *   tour_agent_delete → delete() — POST: soft delete
 */
class TourAgentController extends BaseController
{
    private TourAgentProfile $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new TourAgentProfile();
    }

    /**
     * Guard: module must be enabled
     */
    private function guardModule(): bool
    {
        if (!isModuleEnabled($this->getCompanyId(), 'tour_operator')) {
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * List agent profiles
     */
    public function index(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $filters = [
            'search'          => $this->inputStr('search'),
            'commission_type' => $this->inputStr('commission_type'),
        ];

        $profiles = $this->model->getProfiles($comId, $filters);
        $message = $_GET['msg'] ?? '';

        $this->render('tour-agent/list', compact('profiles', 'filters', 'message'));
    }

    /**
     * Create / Edit form
     */
    public function make(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');
        $profile = null;

        if ($id > 0) {
            $profile = $this->model->findProfile($id, $comId);
            if (!$profile) {
                $this->redirect('tour_agent_list', ['msg' => 'not_found']);
                return;
            }
        }

        $vendors = $this->model->getAvailableVendors($comId);
        $message = $_GET['msg'] ?? '';

        // Load models grouped by type and existing contract rates
        $modelsByType = $this->model->getModelsGroupedByType($comId);
        $contractRates = [];
        if ($profile) {
            $contractRates = $this->model->getContractRates($profile['company_ref_id'], $comId);
        }

        $this->render('tour-agent/make', compact('profile', 'vendors', 'message', 'modelsByType', 'contractRates'));
    }

    /**
     * POST: Save agent profile (create or update)
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $id = intval($_POST['id'] ?? 0);

        $data = [
            'company_ref_id'    => intval($_POST['company_ref_id'] ?? 0),
            'company_id'        => $comId,
            'commission_type'   => $_POST['commission_type'] ?? 'percentage',
            'commission_adult'  => floatval($_POST['commission_adult'] ?? 0),
            'commission_child'  => floatval($_POST['commission_child'] ?? 0),
            'contract_start'    => trim($_POST['contract_start'] ?? ''),
            'contract_end'      => trim($_POST['contract_end'] ?? ''),
            'contact_line'      => trim($_POST['contact_line'] ?? ''),
            'contact_whatsapp'  => trim($_POST['contact_whatsapp'] ?? ''),
            'notes'             => trim($_POST['notes'] ?? ''),
        ];

        // Validate: vendor must exist and belong to this company
        if ($data['company_ref_id'] <= 0) {
            $this->redirect('tour_agent_make', ['msg' => 'error']);
            return;
        }

        if ($id > 0) {
            $this->model->updateProfile($id, $data, $comId);
            $msg = 'updated';
        } else {
            // Check duplicate
            $existing = $this->model->findByCompanyRef($data['company_ref_id'], $comId);
            if ($existing) {
                $this->redirect('tour_agent_make', ['msg' => 'duplicate']);
                return;
            }
            $this->model->createProfile($data);
            $msg = 'created';
        }

        // Save contract rates if provided
        if (!empty($_POST['rates']) && is_array($_POST['rates'])) {
            $this->model->saveContractRates($data['company_ref_id'], $comId, $_POST['rates']);
        }

        $this->redirect('tour_agent_list', ['msg' => $msg]);
    }

    /**
     * POST: Soft delete agent profile
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $id = intval($_POST['id'] ?? 0);
        $comId = $this->getCompanyId();

        if ($id > 0) {
            $this->model->deleteProfile($id, $comId);
        }

        $this->redirect('tour_agent_list', ['msg' => 'deleted']);
    }
}
