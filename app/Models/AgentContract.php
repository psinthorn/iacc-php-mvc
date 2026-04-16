<?php
namespace App\Models;

/**
 * AgentContract Model
 * 
 * Manages contracts between the tenant company and its agents.
 * Each contract defines which product types the agent can sell,
 * payment terms, and owns the contract rates.
 */
class AgentContract extends BaseModel
{
    protected string $table = 'agent_contracts';
    protected bool $useCompanyFilter = true;

    /**
     * List all contracts for an agent
     */
    public function getContracts(int $agentCompanyId, int $comId): array
    {
        $agentCompanyId = \sql_int($agentCompanyId);
        $comId = \sql_int($comId);
        $sql = "SELECT ac.*,
                       (SELECT COUNT(*) FROM agent_contract_types act WHERE act.contract_id = ac.id) AS type_count,
                       (SELECT COUNT(*) FROM contract_rate cr WHERE cr.contract_id = ac.id AND cr.deleted_at IS NULL) AS rate_count
                FROM agent_contracts ac
                WHERE ac.agent_company_id = '$agentCompanyId'
                  AND ac.company_id = '$comId'
                  AND ac.deleted_at IS NULL
                ORDER BY ac.is_default DESC, ac.created_at DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Get a single contract with its linked type IDs
     */
    public function getContract(int $contractId, int $comId): ?array
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);
        $sql = "SELECT ac.*
                FROM agent_contracts ac
                WHERE ac.id = '$contractId'
                  AND ac.company_id = '$comId'
                  AND ac.deleted_at IS NULL";
        $r = mysqli_query($this->conn, $sql);
        if (!$r || mysqli_num_rows($r) === 0) return null;

        $contract = mysqli_fetch_assoc($r);

        // Load linked type IDs
        $sql2 = "SELECT type_id FROM agent_contract_types WHERE contract_id = '$contractId'";
        $r2 = mysqli_query($this->conn, $sql2);
        $typeIds = [];
        if ($r2) { while ($row = mysqli_fetch_assoc($r2)) $typeIds[] = (int)$row['type_id']; }
        $contract['type_ids'] = $typeIds;

        return $contract;
    }

    /**
     * Create a new contract
     */
    public function createContract(array $data): int
    {
        $comId = \sql_int($data['company_id']);
        $agentId = \sql_int($data['agent_company_id']);
        $contractNumber = $data['contract_number'] ?? $this->generateContractNumber((int)$comId);

        $sql = "INSERT INTO agent_contracts
                (company_id, agent_company_id, contract_number, contract_name, status,
                 valid_from, valid_to, payment_terms, credit_days, deposit_pct,
                 conditions, notes, is_default)
                VALUES (
                    '$comId', '$agentId',
                    '" . \sql_escape($contractNumber) . "',
                    " . (!empty($data['contract_name']) ? "'" . \sql_escape($data['contract_name']) . "'" : "NULL") . ",
                    '" . \sql_escape($data['status'] ?? 'draft') . "',
                    " . (!empty($data['valid_from']) ? "'" . \sql_escape($data['valid_from']) . "'" : "NULL") . ",
                    " . (!empty($data['valid_to']) ? "'" . \sql_escape($data['valid_to']) . "'" : "NULL") . ",
                    " . (!empty($data['payment_terms']) ? "'" . \sql_escape($data['payment_terms']) . "'" : "NULL") . ",
                    '" . intval($data['credit_days'] ?? 0) . "',
                    '" . floatval($data['deposit_pct'] ?? 0) . "',
                    " . (!empty($data['conditions']) ? "'" . \sql_escape($data['conditions']) . "'" : "NULL") . ",
                    " . (!empty($data['notes']) ? "'" . \sql_escape($data['notes']) . "'" : "NULL") . ",
                    '" . intval($data['is_default'] ?? 0) . "'
                )";
        mysqli_query($this->conn, $sql);
        $contractId = mysqli_insert_id($this->conn);

        // Sync type IDs if provided
        if (!empty($data['type_ids']) && is_array($data['type_ids'])) {
            $this->syncContractTypes($contractId, $data['type_ids'], (int)$comId);
        }

        return $contractId;
    }

    /**
     * Update an existing contract
     */
    public function updateContract(int $id, array $data, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);

        $sql = "UPDATE agent_contracts SET
                    contract_name = " . (!empty($data['contract_name']) ? "'" . \sql_escape($data['contract_name']) . "'" : "NULL") . ",
                    status = '" . \sql_escape($data['status'] ?? 'draft') . "',
                    valid_from = " . (!empty($data['valid_from']) ? "'" . \sql_escape($data['valid_from']) . "'" : "NULL") . ",
                    valid_to = " . (!empty($data['valid_to']) ? "'" . \sql_escape($data['valid_to']) . "'" : "NULL") . ",
                    payment_terms = " . (!empty($data['payment_terms']) ? "'" . \sql_escape($data['payment_terms']) . "'" : "NULL") . ",
                    credit_days = '" . intval($data['credit_days'] ?? 0) . "',
                    deposit_pct = '" . floatval($data['deposit_pct'] ?? 0) . "',
                    conditions = " . (!empty($data['conditions']) ? "'" . \sql_escape($data['conditions']) . "'" : "NULL") . ",
                    notes = " . (!empty($data['notes']) ? "'" . \sql_escape($data['notes']) . "'" : "NULL") . "
                WHERE id = '$id' AND company_id = '$comId' AND deleted_at IS NULL";
        $ok = mysqli_query($this->conn, $sql);

        // Sync type IDs if provided
        if (isset($data['type_ids']) && is_array($data['type_ids'])) {
            $this->syncContractTypes($id, $data['type_ids'], $comId);
        }

        return $ok;
    }

    /**
     * Soft-delete a contract and its rates
     */
    public function deleteContract(int $id, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);

        // Soft-delete rates under this contract
        mysqli_query($this->conn,
            "UPDATE contract_rate SET deleted_at = NOW() WHERE contract_id = '$id' AND deleted_at IS NULL");

        // Remove type links
        mysqli_query($this->conn,
            "DELETE FROM agent_contract_types WHERE contract_id = '$id'");

        // Soft-delete the contract itself
        return mysqli_query($this->conn,
            "UPDATE agent_contracts SET deleted_at = NOW() WHERE id = '$id' AND company_id = '$comId' AND deleted_at IS NULL");
    }

    /**
     * Auto-create a default contract with all product types for a new agent
     */
    public function createDefaultContract(int $agentCompanyId, int $comId): int
    {
        // Get all type IDs for this company
        $typeIds = [];
        $r = mysqli_query($this->conn,
            "SELECT id FROM type WHERE company_id = '" . \sql_int($comId) . "' AND deleted_at IS NULL");
        if ($r) { while ($row = mysqli_fetch_assoc($r)) $typeIds[] = (int)$row['id']; }

        return $this->createContract([
            'company_id'       => $comId,
            'agent_company_id' => $agentCompanyId,
            'contract_name'    => 'Default Contract',
            'status'           => 'active',
            'valid_from'       => date('Y-01-01'),
            'valid_to'         => date('Y-12-31'),
            'is_default'       => 1,
            'type_ids'         => $typeIds,
        ]);
    }

    /**
     * Generate a unique contract number: CTR-YYYY-NNNN
     */
    public function generateContractNumber(int $comId): string
    {
        $comId = \sql_int($comId);
        $year = date('Y');
        $sql = "SELECT MAX(CAST(SUBSTRING(contract_number, 10) AS UNSIGNED)) AS max_seq
                FROM agent_contracts
                WHERE company_id = '$comId' AND contract_number LIKE 'CTR-$year-%'";
        $r = mysqli_query($this->conn, $sql);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        $next = intval($row['max_seq'] ?? 0) + 1;
        return 'CTR-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sync contract-type links (replace all)
     */
    private function syncContractTypes(int $contractId, array $typeIds, int $comId): void
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);

        // Remove existing links
        mysqli_query($this->conn,
            "DELETE FROM agent_contract_types WHERE contract_id = '$contractId'");

        // Insert new links
        foreach ($typeIds as $typeId) {
            $typeId = \sql_int($typeId);
            mysqli_query($this->conn,
                "INSERT INTO agent_contract_types (contract_id, type_id, company_id)
                 VALUES ('$contractId', '$typeId', '$comId')");
        }
    }

    /**
     * Get contract rates for a specific contract, keyed by model_id
     */
    public function getContractRates(int $contractId): array
    {
        $contractId = \sql_int($contractId);
        $sql = "SELECT * FROM contract_rate
                WHERE contract_id = '$contractId' AND deleted_at IS NULL
                ORDER BY model_id ASC";
        $rows = $this->fetchAll($sql);

        $keyed = [];
        foreach ($rows as $row) {
            $key = $row['model_id'] ? (int)$row['model_id'] : 0;
            $keyed[$key] = $row;
        }
        return $keyed;
    }

    /**
     * Save contract rates for a specific contract
     */
    public function saveContractRates(int $contractId, int $comId, int $agentCompanyId, array $rates): void
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);
        $agentCompanyId = \sql_int($agentCompanyId);

        // Soft-delete existing rates for this contract
        mysqli_query($this->conn,
            "UPDATE contract_rate SET deleted_at = NOW()
             WHERE contract_id = '$contractId' AND deleted_at IS NULL");

        foreach ($rates as $rate) {
            $modelId    = !empty($rate['model_id']) ? \sql_int($rate['model_id']) : 'NULL';
            $rateType   = \sql_escape($rate['rate_type'] ?? 'net_rate');
            $adultDef   = floatval($rate['adult_default'] ?? 0);
            $childDef   = floatval($rate['child_default'] ?? 0);
            $adultThai  = floatval($rate['adult_thai'] ?? 0);
            $adultFor   = floatval($rate['adult_foreigner'] ?? 0);
            $childThai  = floatval($rate['child_thai'] ?? 0);
            $childFor   = floatval($rate['child_foreigner'] ?? 0);
            $entAdultDef = floatval($rate['entrance_adult_default'] ?? 0);
            $entChildDef = floatval($rate['entrance_child_default'] ?? 0);
            $entAT      = floatval($rate['entrance_adult_thai'] ?? 0);
            $entAF      = floatval($rate['entrance_adult_foreigner'] ?? 0);
            $entCT      = floatval($rate['entrance_child_thai'] ?? 0);
            $entCF      = floatval($rate['entrance_child_foreigner'] ?? 0);

            // Skip all-zero rows
            if ($adultDef == 0 && $childDef == 0
                && $adultThai == 0 && $adultFor == 0 && $childThai == 0 && $childFor == 0
                && $entAdultDef == 0 && $entChildDef == 0
                && $entAT == 0 && $entAF == 0 && $entCT == 0 && $entCF == 0) {
                continue;
            }

            $sql = "INSERT INTO contract_rate
                    (contract_id, company_id, agent_company_id, model_id, rate_type,
                     adult_default, child_default,
                     adult_thai, adult_foreigner, child_thai, child_foreigner,
                     entrance_adult_default, entrance_child_default,
                     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
                     valid_from, valid_to)
                    VALUES ('$contractId', '$comId', '$agentCompanyId', $modelId, '$rateType',
                            '$adultDef', '$childDef',
                            '$adultThai', '$adultFor', '$childThai', '$childFor',
                            '$entAdultDef', '$entChildDef',
                            '$entAT', '$entAF', '$entCT', '$entCF',
                            '2026-01-01', '2026-12-31')
                    ON DUPLICATE KEY UPDATE
                        rate_type = VALUES(rate_type),
                        adult_default = VALUES(adult_default), child_default = VALUES(child_default),
                        adult_thai = VALUES(adult_thai), adult_foreigner = VALUES(adult_foreigner),
                        child_thai = VALUES(child_thai), child_foreigner = VALUES(child_foreigner),
                        entrance_adult_default = VALUES(entrance_adult_default),
                        entrance_child_default = VALUES(entrance_child_default),
                        entrance_adult_thai = VALUES(entrance_adult_thai),
                        entrance_adult_foreigner = VALUES(entrance_adult_foreigner),
                        entrance_child_thai = VALUES(entrance_child_thai),
                        entrance_child_foreigner = VALUES(entrance_child_foreigner),
                        deleted_at = NULL";
            mysqli_query($this->conn, $sql);
        }
    }

    /**
     * Get models grouped by type, filtered to only types in this contract
     */
    public function getModelsForContract(int $contractId, int $comId): array
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);
        $sql = "SELECT m.id, m.model_name, m.price, t.id AS type_id, t.name AS type_name
                FROM model m
                JOIN type t ON m.type_id = t.id
                JOIN agent_contract_types act ON act.type_id = t.id AND act.contract_id = '$contractId'
                WHERE m.company_id = '$comId' AND m.deleted_at IS NULL AND t.deleted_at IS NULL
                ORDER BY t.name ASC, m.model_name ASC";
        $rows = $this->fetchAll($sql);

        $grouped = [];
        foreach ($rows as $row) {
            $tid = $row['type_id'];
            if (!isset($grouped[$tid])) {
                $grouped[$tid] = [
                    'type_id'   => $tid,
                    'type_name' => $row['type_name'],
                    'models'    => [],
                ];
            }
            $grouped[$tid]['models'][] = $row;
        }
        return array_values($grouped);
    }

    /**
     * Get all product types for a company (for type selection checkboxes)
     */
    public function getAllTypes(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT t.id, t.name,
                       (SELECT COUNT(*) FROM model m WHERE m.type_id = t.id AND m.deleted_at IS NULL) AS model_count
                FROM type t
                WHERE t.company_id = '$comId' AND t.deleted_at IS NULL
                ORDER BY t.name ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Find agent profile ID for an agent_company_id
     */
    public function findAgentProfileId(int $agentCompanyId, int $comId): ?int
    {
        $agentCompanyId = \sql_int($agentCompanyId);
        $comId = \sql_int($comId);
        $sql = "SELECT id FROM tour_agent_profiles
                WHERE company_ref_id = '$agentCompanyId' AND company_id = '$comId' AND deleted_at IS NULL
                LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        if ($r && $row = mysqli_fetch_assoc($r)) return (int)$row['id'];
        return null;
    }

    /**
     * Get agent company name
     */
    public function getAgentName(int $agentCompanyId): string
    {
        $agentCompanyId = \sql_int($agentCompanyId);
        $r = mysqli_query($this->conn,
            "SELECT name_en FROM company WHERE id = '$agentCompanyId' LIMIT 1");
        if ($r && $row = mysqli_fetch_assoc($r)) return $row['name_en'] ?? '';
        return '';
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
