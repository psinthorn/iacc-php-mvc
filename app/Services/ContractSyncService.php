<?php
namespace App\Services;

use App\Models\ContractSync;
use App\Models\AgentContract;

/**
 * ContractSyncService — Sync contract products to agent catalogs
 *
 * Responsibilities:
 *   - Rebuild agent product catalogs when contracts change
 *   - Season-aware rate resolution
 *   - Audit trail for all sync operations
 *
 * Called from:
 *   - AgentContractController (store, assign, unassign, resync)
 *   - AgentPortalController (agent-initiated resync)
 *   - API endpoints (contract resync)
 */
class ContractSyncService
{
    private \mysqli $conn;
    private ContractSync $syncModel;
    private AgentContract $contractModel;

    public function __construct()
    {
        global $db;
        $this->conn = $db->conn;
        $this->syncModel = new ContractSync();
        $this->contractModel = new AgentContract();
    }

    /**
     * Sync all products from a contract to all assigned agents.
     *
     * For each agent: determine which model IDs the contract covers
     * (via type_ids), then upsert into tour_operator_agent_products.
     *
     * @return array ['success' => bool, 'data' => [...], 'error' => ?string]
     */
    public function syncContractToAgents(int $contractId, int $comId, string $triggeredBy = 'auto'): array
    {
        $contract = $this->contractModel->getContract($contractId, $comId);
        if (!$contract) {
            return ['success' => false, 'data' => [], 'error' => 'Contract not found'];
        }

        $agentIds = $this->syncModel->getContractAgentIds($contractId);
        if (empty($agentIds)) {
            return ['success' => true, 'data' => ['agents' => 0, 'products' => 0], 'error' => null];
        }

        $modelIds = $this->syncModel->getContractModelIds($contractId, $comId);

        mysqli_begin_transaction($this->conn);
        try {
            $totalAdded = 0;
            $totalRemoved = 0;
            $userId = intval($_SESSION['user_id'] ?? 0) ?: null;

            foreach ($agentIds as $agentId) {
                $result = $this->syncAgentFromContract($contractId, $agentId, $comId, $modelIds);
                $totalAdded += $result['added'];
                $totalRemoved += $result['removed'];
            }

            // Log the sync
            $this->syncModel->logSync(
                $comId, $contractId, null, 'sync', $triggeredBy,
                $totalAdded, $totalRemoved,
                json_encode([
                    'agents' => count($agentIds),
                    'models' => count($modelIds),
                ]),
                $userId
            );

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'data' => [
                    'agents' => count($agentIds),
                    'products' => count($modelIds),
                    'added' => $totalAdded,
                    'removed' => $totalRemoved,
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync products for a single agent from a specific contract.
     *
     * 1. Get model IDs the contract covers
     * 2. Upsert each model into agent's product catalog
     * 3. Deactivate models that are no longer in the contract
     *
     * @return array ['added' => int, 'removed' => int]
     */
    private function syncAgentFromContract(int $contractId, int $agentCompanyId, int $comId, array $modelIds): array
    {
        $added = 0;
        $removed = 0;

        // Current products for this agent+contract
        $existing = $this->syncModel->getAgentProducts($agentCompanyId, $contractId);
        $existingModelIds = array_column($existing, 'model_id');
        $existingModelIds = array_map('intval', $existingModelIds);

        // Add new products
        foreach ($modelIds as $modelId) {
            if (!in_array($modelId, $existingModelIds)) {
                $this->syncModel->upsertAgentProduct($comId, $agentCompanyId, $contractId, $modelId);
                $added++;
            }
        }

        // Deactivate products no longer in contract
        foreach ($existingModelIds as $existingModelId) {
            if (!in_array($existingModelId, $modelIds)) {
                $this->syncModel->deactivateAgentProduct($agentCompanyId, $contractId, $existingModelId);
                $removed++;
            }
        }

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * Sync all products for a specific agent across all their contracts.
     */
    public function syncAgentProducts(int $agentCompanyId, int $comId, string $triggeredBy = 'agent'): array
    {
        // Get all contracts assigned to this agent
        $sql = "SELECT contract_id FROM tour_contract_agents
                WHERE agent_company_id = " . intval($agentCompanyId) . "
                  AND company_id = " . intval($comId);
        $res = mysqli_query($this->conn, $sql);
        if (!$res) {
            return ['success' => false, 'data' => [], 'error' => 'Query failed'];
        }

        $contractIds = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $contractIds[] = intval($row['contract_id']);
        }

        if (empty($contractIds)) {
            return ['success' => true, 'data' => ['contracts' => 0, 'products' => 0], 'error' => null];
        }

        $totalAdded = 0;
        $totalRemoved = 0;
        $userId = intval($_SESSION['user_id'] ?? 0) ?: null;

        mysqli_begin_transaction($this->conn);
        try {
            foreach ($contractIds as $cid) {
                $modelIds = $this->syncModel->getContractModelIds($cid, $comId);
                $result = $this->syncAgentFromContract($cid, $agentCompanyId, $comId, $modelIds);
                $totalAdded += $result['added'];
                $totalRemoved += $result['removed'];

                $this->syncModel->logSync(
                    $comId, $cid, $agentCompanyId, 'sync', $triggeredBy,
                    $result['added'], $result['removed'], null, $userId
                );
            }

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'data' => [
                    'contracts' => count($contractIds),
                    'added' => $totalAdded,
                    'removed' => $totalRemoved,
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Add a single product to all agents assigned to a contract.
     * Called when a new product type/model is added to a contract.
     */
    public function addProductToAgents(int $contractId, int $modelId, int $comId): array
    {
        $agentIds = $this->syncModel->getContractAgentIds($contractId);
        $added = 0;
        $userId = intval($_SESSION['user_id'] ?? 0) ?: null;

        foreach ($agentIds as $agentId) {
            if ($this->syncModel->upsertAgentProduct($comId, $agentId, $contractId, $modelId)) {
                $added++;
            }
        }

        $this->syncModel->logSync(
            $comId, $contractId, null, 'product_added', 'auto',
            $added, 0,
            json_encode(['model_id' => $modelId]),
            $userId
        );

        return ['success' => true, 'data' => ['added' => $added], 'error' => null];
    }

    /**
     * Remove a single product from all agents assigned to a contract.
     * Called when a product type/model is removed from a contract.
     */
    public function removeProductFromAgents(int $contractId, int $modelId, int $comId): array
    {
        $agentIds = $this->syncModel->getContractAgentIds($contractId);
        $removed = 0;
        $userId = intval($_SESSION['user_id'] ?? 0) ?: null;

        foreach ($agentIds as $agentId) {
            if ($this->syncModel->deactivateAgentProduct($agentId, $contractId, $modelId)) {
                $removed++;
            }
        }

        $this->syncModel->logSync(
            $comId, $contractId, null, 'product_removed', 'auto',
            0, $removed,
            json_encode(['model_id' => $modelId]),
            $userId
        );

        return ['success' => true, 'data' => ['removed' => $removed], 'error' => null];
    }

    /**
     * Full resync for an entire operator — all contracts, all agents.
     */
    public function resyncAll(int $comId): array
    {
        $contracts = $this->contractModel->getOperatorContracts($comId);
        $totalAdded = 0;
        $totalRemoved = 0;

        foreach ($contracts as $c) {
            $result = $this->syncContractToAgents((int)$c['id'], $comId, 'system');
            if ($result['success']) {
                $totalAdded += $result['data']['added'] ?? 0;
                $totalRemoved += $result['data']['removed'] ?? 0;
            }
        }

        return [
            'success' => true,
            'data' => [
                'contracts' => count($contracts),
                'added' => $totalAdded,
                'removed' => $totalRemoved,
            ],
            'error' => null,
        ];
    }

    /**
     * Resolve the applicable rate for a product on a given travel date.
     *
     * Priority: season match (higher priority first) → base rate (NULL season).
     */
    public function getResolvedRate(int $contractId, int $modelId, string $travelDate): ?array
    {
        return $this->contractModel->findApplicableRate($contractId, $modelId, $travelDate);
    }

    // ─── Convenience Accessors ────────────────────────────────

    /**
     * Get sync log for a contract
     */
    public function getSyncLog(int $contractId, int $limit = 50): array
    {
        return $this->syncModel->getSyncLog($contractId, $limit);
    }

    /**
     * Get last sync time for a contract
     */
    public function getLastSyncTime(int $contractId): ?string
    {
        return $this->syncModel->getLastSyncTime($contractId);
    }

    /**
     * Get sync stats for a contract
     */
    public function getSyncStats(int $contractId): array
    {
        return $this->syncModel->getSyncStats($contractId);
    }
}
