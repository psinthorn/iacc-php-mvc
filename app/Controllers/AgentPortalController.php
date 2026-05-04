<?php
namespace App\Controllers;

use App\Models\AgentPortal;
use App\Services\ContractSyncService;

/**
 * AgentPortalController — Agent-facing portal
 *
 * Routes:
 *   agent_portal_dashboard → dashboard()  — Overview, stats
 *   agent_portal_products  → products()   — Synced product catalog
 *   agent_portal_contracts → contracts()  — Assigned contracts
 *   agent_portal_contract  → contract()   — Single contract detail with rates
 *   agent_portal_bookings  → bookings()   — Bookings created
 *   agent_portal_resync    → resync()     — POST: manual catalog refresh
 */
class AgentPortalController extends BaseController
{
    private AgentPortal $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AgentPortal();
    }

    /**
     * Guard: only show portal if agent's company is registered with at least one operator
     */
    private function guardAgent(): bool
    {
        $agentComId = $this->getCompanyId();
        $operators = $this->model->getOperators($agentComId);
        if (empty($operators)) {
            $this->redirect('dashboard', ['msg' => 'no_operator_access']);
            return false;
        }
        return true;
    }

    /**
     * Dashboard — overview of the agent's account
     */
    public function dashboard(): void
    {
        if (!$this->guardAgent()) return;

        $agentComId = $this->getCompanyId();
        $stats = $this->model->getDashboardStats($agentComId);
        $operators = $this->model->getOperators($agentComId);
        $recentBookings = array_slice($this->model->getBookings($agentComId, 5), 0, 5);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/agent-portal/dashboard.php';
    }

    /**
     * Products — synced catalog from operators
     */
    public function products(): void
    {
        if (!$this->guardAgent()) return;

        $agentComId = $this->getCompanyId();
        $operatorFilter = $this->inputInt('operator_id') ?: null;
        $operators = $this->model->getOperators($agentComId);
        $products = $this->model->getProducts($agentComId, $operatorFilter);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/agent-portal/products.php';
    }

    /**
     * Contracts — list of contracts the agent is assigned to
     */
    public function contracts(): void
    {
        if (!$this->guardAgent()) return;

        $agentComId = $this->getCompanyId();
        $contracts = $this->model->getContracts($agentComId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/agent-portal/contracts.php';
    }

    /**
     * Contract detail — view rates for a specific contract
     */
    public function contract(): void
    {
        if (!$this->guardAgent()) return;

        $agentComId = $this->getCompanyId();
        $contractId = $this->inputInt('contract_id');

        $contract = $this->model->getContractDetail($agentComId, $contractId);
        if (!$contract) {
            $this->redirect('agent_portal_contracts', ['msg' => 'not_found']);
            return;
        }

        $rates = $this->model->getContractRates($agentComId, $contractId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/agent-portal/contract-detail.php';
    }

    /**
     * Bookings — list of bookings the agent has created
     */
    public function bookings(): void
    {
        if (!$this->guardAgent()) return;

        $agentComId = $this->getCompanyId();
        $bookings = $this->model->getBookings($agentComId, 100);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/agent-portal/bookings.php';
    }

    /**
     * POST: agent-initiated catalog resync
     */
    public function resync(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('agent_portal_products');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardAgent()) return;

        $agentComId = $this->getCompanyId();
        $operators = $this->model->getOperators($agentComId);

        $sync = new ContractSyncService();
        foreach ($operators as $op) {
            $sync->syncAgentProducts($agentComId, intval($op['operator_company_id']), 'agent');
        }

        $this->redirect('agent_portal_products', ['msg' => 'resynced']);
    }
}
