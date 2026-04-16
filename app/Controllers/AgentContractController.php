<?php
namespace App\Controllers;

use App\Models\AgentContract;
use App\Models\TourAgentProfile;

/**
 * AgentContractController — CRUD for agent contracts
 * 
 * Routes:
 *   agent_contract_list   → index()  — List contracts for an agent
 *   agent_contract_make   → make()   — Create/edit contract form
 *   agent_contract_store  → store()  — POST: save contract
 *   agent_contract_delete → delete() — POST: soft delete
 */
class AgentContractController extends BaseController
{
    private AgentContract $model;
    private TourAgentProfile $agentModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AgentContract();
        $this->agentModel = new TourAgentProfile();
    }

    private function guardModule(): bool
    {
        if (!isModuleEnabled($this->getCompanyId(), 'tour_operator')) {
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * List contracts for an agent
     */
    public function index(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $agentCompanyId = $this->inputInt('agent_id');

        if ($agentCompanyId <= 0) {
            $this->redirect('tour_agent_list');
            return;
        }

        $agentName = $this->model->getAgentName($agentCompanyId);
        $profileId = $this->model->findAgentProfileId($agentCompanyId, $comId);
        if (!$profileId) {
            $this->redirect('tour_agent_list', ['msg' => 'not_found']);
            return;
        }

        $contracts = $this->model->getContracts($agentCompanyId, $comId);
        $message = $_GET['msg'] ?? '';

        $this->render('tour-agent/contracts', compact(
            'contracts', 'agentCompanyId', 'agentName', 'profileId', 'message'
        ));
    }

    /**
     * Create / Edit contract form
     */
    public function make(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $agentCompanyId = $this->inputInt('agent_id');
        $contractId = $this->inputInt('contract_id');

        if ($agentCompanyId <= 0) {
            $this->redirect('tour_agent_list');
            return;
        }

        $agentName = $this->model->getAgentName($agentCompanyId);
        $contract = null;
        $contractRates = [];

        if ($contractId > 0) {
            $contract = $this->model->getContract($contractId, $comId);
            if (!$contract || (int)$contract['agent_company_id'] !== $agentCompanyId) {
                $this->redirect('agent_contract_list', ['agent_id' => $agentCompanyId, 'msg' => 'not_found']);
                return;
            }
            $contractRates = $this->model->getContractRates($contractId);
        }

        // All types for the company (for type checkboxes)
        $allTypes = $this->model->getAllTypes($comId);

        // Models grouped by type (only for selected types if editing)
        $modelsByType = $this->agentModel->getModelsGroupedByType($comId);

        $message = $_GET['msg'] ?? '';

        $this->render('tour-agent/contract-make', compact(
            'contract', 'agentCompanyId', 'agentName', 'contractId',
            'allTypes', 'modelsByType', 'contractRates', 'message'
        ));
    }

    /**
     * POST: Save contract
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
        $contractId = intval($_POST['contract_id'] ?? 0);
        $agentCompanyId = intval($_POST['agent_company_id'] ?? 0);

        if ($agentCompanyId <= 0) {
            $this->redirect('tour_agent_list', ['msg' => 'error']);
            return;
        }

        $data = [
            'company_id'       => $comId,
            'agent_company_id' => $agentCompanyId,
            'contract_name'    => trim($_POST['contract_name'] ?? ''),
            'status'           => $_POST['status'] ?? 'draft',
            'valid_from'       => trim($_POST['valid_from'] ?? ''),
            'valid_to'         => trim($_POST['valid_to'] ?? ''),
            'payment_terms'    => trim($_POST['payment_terms'] ?? ''),
            'credit_days'      => intval($_POST['credit_days'] ?? 0),
            'deposit_pct'      => floatval($_POST['deposit_pct'] ?? 0),
            'conditions'       => trim($_POST['conditions'] ?? ''),
            'notes'            => trim($_POST['notes'] ?? ''),
            'type_ids'         => $_POST['type_ids'] ?? [],
        ];

        if ($contractId > 0) {
            // Verify ownership
            $existing = $this->model->getContract($contractId, $comId);
            if (!$existing || (int)$existing['agent_company_id'] !== $agentCompanyId) {
                $this->redirect('tour_agent_list', ['msg' => 'error']);
                return;
            }
            $this->model->updateContract($contractId, $data, $comId);
            $msg = 'updated';
        } else {
            $contractId = $this->model->createContract($data);
            $msg = 'created';
        }

        // Save contract rates if provided
        if (!empty($_POST['rates']) && is_array($_POST['rates'])) {
            $this->model->saveContractRates($contractId, $comId, $agentCompanyId, $_POST['rates']);
        }

        $this->redirect('agent_contract_list', ['agent_id' => $agentCompanyId, 'msg' => $msg]);
    }

    /**
     * POST: Soft delete contract
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);
        $agentCompanyId = intval($_POST['agent_company_id'] ?? 0);

        if ($contractId > 0) {
            $this->model->deleteContract($contractId, $comId);
        }

        $this->redirect('agent_contract_list', ['agent_id' => $agentCompanyId, 'msg' => 'deleted']);
    }
}
