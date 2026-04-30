<?php
namespace App\Controllers;

use App\Models\AgentContract;
use App\Models\TourAgentProfile;
use App\Services\ContractSyncService;

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

    // ─── V2: Operator-Level Contract Management ────────────────

    /**
     * List all operator-level contracts
     */
    public function contractList(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $contracts = $this->model->getOperatorContracts($comId);
        $defaultContractId = $this->model->getDefaultContractId($comId);
        $message = $_GET['msg'] ?? '';

        $this->render('tour-agent/contract-list-v2', compact(
            'contracts', 'defaultContractId', 'message'
        ));
    }

    /**
     * Create / Edit operator-level contract form
     */
    public function contractMake(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $contractId = $this->inputInt('contract_id');

        $contract = null;
        $contractRates = [];
        $contractAgents = [];
        $seasons = [];

        if ($contractId > 0) {
            $contract = $this->model->getContract($contractId, $comId);
            if (!$contract) {
                $this->redirect('tour_contract_list', ['msg' => 'not_found']);
                return;
            }
            $contractRates = $this->model->getContractRatesBySeason($contractId);
            $contractAgents = $this->model->getContractAgents($contractId, $comId);
            $seasons = $this->model->getSeasons($contractId);
        }

        $allTypes = $this->model->getAllTypes($comId);
        $modelsByType = $this->agentModel->getModelsGroupedByType($comId);
        $availableAgents = $contractId > 0
            ? $this->model->getAvailableAgents($contractId, $comId)
            : [];
        $message = $_GET['msg'] ?? '';

        $this->render('tour-agent/contract-make-v2', compact(
            'contract', 'contractId', 'allTypes', 'modelsByType',
            'contractRates', 'contractAgents', 'availableAgents',
            'seasons', 'message'
        ));
    }

    /**
     * POST: Save operator-level contract
     */
    public function contractStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_contract_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);

        $data = [
            'company_id'    => $comId,
            'contract_name' => trim($_POST['contract_name'] ?? ''),
            'status'        => $_POST['status'] ?? 'draft',
            'valid_from'    => trim($_POST['valid_from'] ?? ''),
            'valid_to'      => trim($_POST['valid_to'] ?? ''),
            'payment_terms' => trim($_POST['payment_terms'] ?? ''),
            'credit_days'   => intval($_POST['credit_days'] ?? 0),
            'deposit_pct'   => floatval($_POST['deposit_pct'] ?? 0),
            'conditions'    => trim($_POST['conditions'] ?? ''),
            'notes'         => trim($_POST['notes'] ?? ''),
            'type_ids'      => $_POST['type_ids'] ?? [],
        ];

        if ($contractId > 0) {
            $existing = $this->model->getContract($contractId, $comId);
            if (!$existing) {
                $this->redirect('tour_contract_list', ['msg' => 'error']);
                return;
            }
            $this->model->updateContract($contractId, $data, $comId);
            $msg = 'updated';
        } else {
            $contractId = $this->model->createOperatorContract($data);
            $msg = 'created';
        }

        // Save season rates if provided
        if (!empty($_POST['season_rates']) && is_array($_POST['season_rates'])) {
            foreach ($_POST['season_rates'] as $season) {
                $seasonName  = !empty($season['season_name']) ? trim($season['season_name']) : null;
                $seasonStart = !empty($season['season_start']) ? trim($season['season_start']) : null;
                $seasonEnd   = !empty($season['season_end']) ? trim($season['season_end']) : null;
                $priority    = intval($season['priority'] ?? 0);
                $rates       = $season['rates'] ?? [];

                if (!empty($rates)) {
                    $this->model->saveSeasonRates($contractId, $comId, $rates,
                        $seasonName, $seasonStart, $seasonEnd, $priority);
                }
            }
        }

        // Trigger sync if contract has agents assigned
        $this->triggerSync($contractId, $comId);

        $this->redirect('tour_contract_make', ['contract_id' => $contractId, 'msg' => $msg]);
    }

    /**
     * POST: Delete operator-level contract
     */
    public function contractDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_contract_list');
            return;
        }
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);

        if ($contractId > 0) {
            $this->model->deleteOperatorContract($contractId, $comId);
        }

        $this->redirect('tour_contract_list', ['msg' => 'deleted']);
    }

    /**
     * POST: Assign agent to contract
     */
    public function assignAgent(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);
        $agentCompanyId = intval($_POST['agent_company_id'] ?? 0);
        $userId = $this->user['id'] ?? null;

        if ($contractId > 0 && $agentCompanyId > 0) {
            $this->model->assignAgent($contractId, $agentCompanyId, $comId, $userId);
            $this->triggerSync($contractId, $comId);
        }

        $this->redirect('tour_contract_make', ['contract_id' => $contractId, 'msg' => 'agent_assigned']);
    }

    /**
     * POST: Unassign agent from contract
     */
    public function unassignAgent(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);
        $agentCompanyId = intval($_POST['agent_company_id'] ?? 0);

        if ($contractId > 0 && $agentCompanyId > 0) {
            $this->model->unassignAgent($contractId, $agentCompanyId, $comId);
            // Remove synced products for this agent from this contract
            $syncModel = new \App\Models\ContractSync();
            $removed = $syncModel->deleteAgentProducts($agentCompanyId, $contractId, $comId);
            $syncModel->logSync($comId, $contractId, $agentCompanyId, 'contract_unassigned', 'operator', 0, $removed);
        }

        $this->redirect('tour_contract_make', ['contract_id' => $contractId, 'msg' => 'agent_unassigned']);
    }

    /**
     * POST: Set contract as default
     */
    public function setDefault(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);

        if ($contractId > 0) {
            $this->model->setDefaultContract($contractId, $comId);
        }

        $this->redirect('tour_contract_list', ['msg' => 'default_set']);
    }

    /**
     * POST: Clone a contract
     */
    public function cloneContract(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);
        $newName = trim($_POST['new_name'] ?? '');

        if ($contractId > 0) {
            $newId = $this->model->cloneContract($contractId, $comId, $newName);
            if ($newId) {
                $this->redirect('tour_contract_make', ['contract_id' => $newId, 'msg' => 'cloned']);
                return;
            }
        }

        $this->redirect('tour_contract_list', ['msg' => 'error']);
    }

    /**
     * POST: Manual resync for a contract
     */
    public function resync(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!$this->guardModule()) return;
        $this->verifyCsrf();

        $comId = $this->getCompanyId();
        $contractId = intval($_POST['contract_id'] ?? 0);

        if ($contractId > 0) {
            $this->triggerSync($contractId, $comId);
        }

        $this->redirect('tour_contract_make', ['contract_id' => $contractId, 'msg' => 'resynced']);
    }

    /**
     * Trigger sync for a contract — rebuild agent product catalogs
     */
    private function triggerSync(int $contractId, int $comId): void
    {
        $syncService = new ContractSyncService();
        $syncService->syncContractToAgents($contractId, $comId, 'operator');
    }
}
