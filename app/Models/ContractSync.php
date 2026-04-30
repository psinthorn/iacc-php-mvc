<?php
namespace App\Models;

/**
 * ContractSync — Data access for contract sync operations
 *
 * Manages tour_operator_agent_products (synced catalog)
 * and tour_contract_sync_log (audit trail).
 */
class ContractSync extends BaseModel
{
    protected string $table = 'tour_operator_agent_products';
    protected bool $useCompanyFilter = false;

    // ─── Agent Products ───────────────────────────────────────

    /**
     * Get all synced products for an agent from a specific contract
     */
    public function getAgentProducts(int $agentCompanyId, int $contractId): array
    {
        $agentCompanyId = intval($agentCompanyId);
        $contractId = intval($contractId);
        $sql = "SELECT p.*, m.model_name, m.des AS model_desc
                FROM tour_operator_agent_products p
                LEFT JOIN model m ON p.model_id = m.id
                WHERE p.agent_company_id = $agentCompanyId
                  AND p.contract_id = $contractId
                  AND p.is_active = 1
                ORDER BY m.model_name";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Get all active products for an agent (across all contracts)
     */
    public function getAllAgentProducts(int $agentCompanyId, int $operatorComId): array
    {
        $agentCompanyId = intval($agentCompanyId);
        $operatorComId = intval($operatorComId);
        $sql = "SELECT p.*, m.model_name, m.des AS model_desc,
                       ac.contract_name
                FROM tour_operator_agent_products p
                LEFT JOIN model m ON p.model_id = m.id
                LEFT JOIN agent_contracts ac ON p.contract_id = ac.id
                WHERE p.agent_company_id = $agentCompanyId
                  AND p.operator_company_id = $operatorComId
                  AND p.is_active = 1
                ORDER BY ac.contract_name, m.model_name";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Upsert a product into agent's catalog
     */
    public function upsertAgentProduct(int $operatorComId, int $agentCompanyId, int $contractId, int $modelId): bool
    {
        $operatorComId = intval($operatorComId);
        $agentCompanyId = intval($agentCompanyId);
        $contractId = intval($contractId);
        $modelId = intval($modelId);

        $sql = "INSERT INTO tour_operator_agent_products
                    (operator_company_id, agent_company_id, contract_id, model_id, is_active, synced_at)
                VALUES ($operatorComId, $agentCompanyId, $contractId, $modelId, 1, NOW())
                ON DUPLICATE KEY UPDATE is_active = 1, synced_at = NOW()";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Deactivate a product for an agent from a contract
     */
    public function deactivateAgentProduct(int $agentCompanyId, int $contractId, int $modelId): bool
    {
        $agentCompanyId = intval($agentCompanyId);
        $contractId = intval($contractId);
        $modelId = intval($modelId);
        $sql = "UPDATE tour_operator_agent_products
                SET is_active = 0, updated_at = NOW()
                WHERE agent_company_id = $agentCompanyId
                  AND contract_id = $contractId
                  AND model_id = $modelId";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Remove all products for an agent from a specific contract
     */
    public function deleteAgentProducts(int $agentCompanyId, int $contractId, int $operatorComId): int
    {
        $agentCompanyId = intval($agentCompanyId);
        $contractId = intval($contractId);
        $operatorComId = intval($operatorComId);
        $sql = "DELETE FROM tour_operator_agent_products
                WHERE agent_company_id = $agentCompanyId
                  AND contract_id = $contractId
                  AND operator_company_id = $operatorComId";
        mysqli_query($this->conn, $sql);
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Get model IDs from a contract's selected types
     */
    public function getContractModelIds(int $contractId, int $comId): array
    {
        $contractId = intval($contractId);
        $comId = intval($comId);
        // Get type IDs for the contract
        $sql = "SELECT type_id FROM agent_contract_types WHERE contract_id = $contractId";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return [];

        $typeIds = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $typeIds[] = intval($row['type_id']);
        }
        if (empty($typeIds)) return [];

        $typeIdList = implode(',', $typeIds);
        $sql2 = "SELECT id FROM model
                 WHERE type_id IN ($typeIdList)
                   AND company_id = $comId
                   AND deleted_at IS NULL
                 ORDER BY model_name";
        $res2 = mysqli_query($this->conn, $sql2);
        if (!$res2) return [];

        $ids = [];
        while ($row = mysqli_fetch_assoc($res2)) {
            $ids[] = intval($row['id']);
        }
        return $ids;
    }

    /**
     * Get assigned agents for a contract
     */
    public function getContractAgentIds(int $contractId): array
    {
        $contractId = intval($contractId);
        $sql = "SELECT agent_company_id FROM tour_contract_agents
                WHERE contract_id = $contractId";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return [];
        $ids = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ids[] = intval($row['agent_company_id']);
        }
        return $ids;
    }

    // ─── Sync Log ─────────────────────────────────────────────

    /**
     * Write a sync log entry
     */
    public function logSync(
        int $comId,
        int $contractId,
        ?int $agentCompanyId,
        string $action,
        string $triggeredBy = 'auto',
        int $productsAdded = 0,
        int $productsRemoved = 0,
        ?string $details = null,
        ?int $createdBy = null
    ): int {
        $comId = intval($comId);
        $contractId = intval($contractId);
        $agentEsc = $agentCompanyId !== null ? intval($agentCompanyId) : 'NULL';
        $actionEsc = mysqli_real_escape_string($this->conn, $action);
        $triggeredByEsc = mysqli_real_escape_string($this->conn, $triggeredBy);
        $detailsEsc = $details !== null ? "'" . mysqli_real_escape_string($this->conn, $details) . "'" : 'NULL';
        $createdByEsc = $createdBy !== null ? intval($createdBy) : 'NULL';

        $sql = "INSERT INTO tour_contract_sync_log
                    (company_id, contract_id, agent_company_id, action, triggered_by,
                     products_added, products_removed, details, created_by)
                VALUES ($comId, $contractId, $agentEsc, '$actionEsc', '$triggeredByEsc',
                        $productsAdded, $productsRemoved, $detailsEsc, $createdByEsc)";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    /**
     * Get sync log for a contract
     */
    public function getSyncLog(int $contractId, int $limit = 50): array
    {
        $contractId = intval($contractId);
        $limit = intval($limit);
        $sql = "SELECT l.*, c.name_en AS agent_name
                FROM tour_contract_sync_log l
                LEFT JOIN company c ON l.agent_company_id = c.id
                WHERE l.contract_id = $contractId
                ORDER BY l.created_at DESC
                LIMIT $limit";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Get last sync time for a contract
     */
    public function getLastSyncTime(int $contractId): ?string
    {
        $contractId = intval($contractId);
        $sql = "SELECT MAX(created_at) AS last_sync
                FROM tour_contract_sync_log
                WHERE contract_id = $contractId AND action IN ('sync','resync')";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['last_sync'] ?? null;
    }

    /**
     * Get sync stats for a contract
     */
    public function getSyncStats(int $contractId): array
    {
        $contractId = intval($contractId);
        $sql = "SELECT
                    COUNT(DISTINCT agent_company_id) AS agent_count,
                    SUM(products_added) AS total_added,
                    SUM(products_removed) AS total_removed,
                    MAX(created_at) AS last_sync
                FROM tour_contract_sync_log
                WHERE contract_id = $contractId";
        $res = mysqli_query($this->conn, $sql);
        return $res ? (mysqli_fetch_assoc($res) ?: []) : [];
    }
}
