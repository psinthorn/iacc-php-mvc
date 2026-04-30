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

    // ─── V2: Operator-Level Contract Methods ───────────────────

    /**
     * List all operator-level contracts (not per-agent)
     */
    public function getOperatorContracts(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT ac.*,
                       (SELECT COUNT(*) FROM agent_contract_types act WHERE act.contract_id = ac.id) AS type_count,
                       (SELECT COUNT(*) FROM contract_rate cr WHERE cr.contract_id = ac.id AND cr.deleted_at IS NULL) AS rate_count,
                       (SELECT COUNT(*) FROM tour_contract_agents tca WHERE tca.contract_id = ac.id) AS agent_count,
                       (SELECT COUNT(DISTINCT cr2.season_name) FROM contract_rate cr2 WHERE cr2.contract_id = ac.id AND cr2.deleted_at IS NULL AND cr2.season_name IS NOT NULL) AS season_count
                FROM agent_contracts ac
                WHERE ac.company_id = '$comId'
                  AND ac.deleted_at IS NULL
                ORDER BY ac.is_default DESC, ac.created_at DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Create an operator-level contract (no agent_company_id)
     */
    public function createOperatorContract(array $data): int
    {
        $comId = \sql_int($data['company_id']);
        $contractNumber = $data['contract_number'] ?? $this->generateContractNumber((int)$comId);

        $sql = "INSERT INTO agent_contracts
                (company_id, agent_company_id, contract_number, contract_name, status,
                 valid_from, valid_to, payment_terms, credit_days, deposit_pct,
                 conditions, notes, is_default, is_operator_level)
                VALUES (
                    '$comId', NULL,
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
                    '" . intval($data['is_default'] ?? 0) . "',
                    1
                )";
        mysqli_query($this->conn, $sql);
        $contractId = mysqli_insert_id($this->conn);

        if (!empty($data['type_ids']) && is_array($data['type_ids'])) {
            $this->syncContractTypes($contractId, $data['type_ids'], (int)$comId);
        }

        return $contractId;
    }

    // ─── V2: Many-to-Many Agent Assignment ─────────────────────

    /**
     * Get agents assigned to a contract
     */
    public function getContractAgents(int $contractId, int $comId): array
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);
        $sql = "SELECT tca.*, c.name_en AS agent_name, c.name_th AS agent_name_th,
                       tap.contact_email, tap.contact_mobile
                FROM tour_contract_agents tca
                JOIN company c ON c.id = tca.agent_company_id
                LEFT JOIN tour_agent_profiles tap ON tap.company_ref_id = tca.agent_company_id AND tap.company_id = '$comId'
                WHERE tca.contract_id = '$contractId' AND tca.company_id = '$comId'
                ORDER BY c.name_en ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Assign an agent to a contract
     */
    public function assignAgent(int $contractId, int $agentCompanyId, int $comId, ?int $userId = null): bool
    {
        $contractId = \sql_int($contractId);
        $agentCompanyId = \sql_int($agentCompanyId);
        $comId = \sql_int($comId);
        $userId = $userId ? \sql_int($userId) : 'NULL';

        $sql = "INSERT IGNORE INTO tour_contract_agents
                (contract_id, agent_company_id, company_id, assigned_by)
                VALUES ('$contractId', '$agentCompanyId', '$comId', $userId)";
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Unassign an agent from a contract
     */
    public function unassignAgent(int $contractId, int $agentCompanyId, int $comId): bool
    {
        $contractId = \sql_int($contractId);
        $agentCompanyId = \sql_int($agentCompanyId);
        $comId = \sql_int($comId);

        return mysqli_query($this->conn,
            "DELETE FROM tour_contract_agents
             WHERE contract_id = '$contractId' AND agent_company_id = '$agentCompanyId' AND company_id = '$comId'");
    }

    /**
     * Get all agents available for assignment (not already on this contract)
     */
    public function getAvailableAgents(int $contractId, int $comId): array
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);
        $sql = "SELECT toa.agent_company_id, c.name_en AS agent_name, c.name_th AS agent_name_th
                FROM tour_operator_agents toa
                JOIN company c ON c.id = toa.agent_company_id
                WHERE toa.operator_company_id = '$comId'
                  AND toa.status = 'approved'
                  AND toa.deleted_at IS NULL
                  AND toa.agent_company_id NOT IN (
                      SELECT agent_company_id FROM tour_contract_agents WHERE contract_id = '$contractId'
                  )
                ORDER BY c.name_en ASC";
        return $this->fetchAll($sql);
    }

    // ─── V2: Default Contract ──────────────────────────────────

    /**
     * Set a contract as the default for new agents
     */
    public function setDefaultContract(int $contractId, int $comId): bool
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);

        // Update company_modules
        mysqli_query($this->conn,
            "UPDATE company_modules SET default_contract_id = '$contractId'
             WHERE company_id = '$comId' AND module_key = 'tour_operator'");

        // Also update is_default flag on contracts
        mysqli_query($this->conn,
            "UPDATE agent_contracts SET is_default = 0 WHERE company_id = '$comId' AND is_operator_level = 1");
        return mysqli_query($this->conn,
            "UPDATE agent_contracts SET is_default = 1 WHERE id = '$contractId' AND company_id = '$comId'");
    }

    /**
     * Get the default contract ID for an operator
     */
    public function getDefaultContractId(int $comId): ?int
    {
        $comId = \sql_int($comId);
        $sql = "SELECT default_contract_id FROM company_modules
                WHERE company_id = '$comId' AND module_key = 'tour_operator' LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        if ($r && $row = mysqli_fetch_assoc($r)) {
            return $row['default_contract_id'] ? (int)$row['default_contract_id'] : null;
        }
        return null;
    }

    // ─── V2: Season Rate Management ────────────────────────────

    /**
     * Get contract rates grouped by season
     * Returns: ['default' => [...rates], 'High Season' => [...rates], ...]
     */
    public function getContractRatesBySeason(int $contractId): array
    {
        $contractId = \sql_int($contractId);
        $sql = "SELECT * FROM contract_rate
                WHERE contract_id = '$contractId' AND deleted_at IS NULL
                ORDER BY priority DESC, season_name ASC, model_id ASC";
        $rows = $this->fetchAll($sql);

        $grouped = [];
        foreach ($rows as $row) {
            $season = $row['season_name'] ?: 'default';
            if (!isset($grouped[$season])) {
                $grouped[$season] = [
                    'season_name'  => $row['season_name'],
                    'season_start' => $row['season_start'],
                    'season_end'   => $row['season_end'],
                    'priority'     => (int)$row['priority'],
                    'rates'        => [],
                ];
            }
            $key = $row['model_id'] ? (int)$row['model_id'] : 0;
            $grouped[$season]['rates'][$key] = $row;
        }
        return $grouped;
    }

    /**
     * Get distinct seasons for a contract
     */
    public function getSeasons(int $contractId): array
    {
        $contractId = \sql_int($contractId);
        $sql = "SELECT DISTINCT season_name, season_start, season_end, priority
                FROM contract_rate
                WHERE contract_id = '$contractId' AND deleted_at IS NULL
                ORDER BY priority DESC, season_name ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Save contract rates with season support
     */
    public function saveSeasonRates(int $contractId, int $comId, array $rates, ?string $seasonName = null,
                                     ?string $seasonStart = null, ?string $seasonEnd = null, int $priority = 0): void
    {
        $contractId = \sql_int($contractId);
        $comId = \sql_int($comId);

        // Soft-delete existing rates for this contract + season
        $seasonWhere = $seasonName
            ? "AND season_name = '" . \sql_escape($seasonName) . "'"
            : "AND season_name IS NULL";
        mysqli_query($this->conn,
            "UPDATE contract_rate SET deleted_at = NOW()
             WHERE contract_id = '$contractId' $seasonWhere AND deleted_at IS NULL");

        $seasonNameSql = $seasonName ? "'" . \sql_escape($seasonName) . "'" : "NULL";
        $seasonStartSql = $seasonStart ? "'" . \sql_escape($seasonStart) . "'" : "NULL";
        $seasonEndSql = $seasonEnd ? "'" . \sql_escape($seasonEnd) . "'" : "NULL";

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
                     season_name, season_start, season_end, priority,
                     adult_default, child_default,
                     adult_thai, adult_foreigner, child_thai, child_foreigner,
                     entrance_adult_default, entrance_child_default,
                     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
                     valid_from, valid_to)
                    VALUES ('$contractId', '$comId', 0, $modelId, '$rateType',
                            $seasonNameSql, $seasonStartSql, $seasonEndSql, '$priority',
                            '$adultDef', '$childDef',
                            '$adultThai', '$adultFor', '$childThai', '$childFor',
                            '$entAdultDef', '$entChildDef',
                            '$entAT', '$entAF', '$entCT', '$entCF',
                            '2026-01-01', '2026-12-31')
                    ON DUPLICATE KEY UPDATE
                        rate_type = VALUES(rate_type),
                        season_start = VALUES(season_start), season_end = VALUES(season_end),
                        priority = VALUES(priority),
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
     * Delete all rates for a specific season in a contract
     */
    public function deleteSeasonRates(int $contractId, string $seasonName): bool
    {
        $contractId = \sql_int($contractId);
        $seasonName = \sql_escape($seasonName);
        return mysqli_query($this->conn,
            "UPDATE contract_rate SET deleted_at = NOW()
             WHERE contract_id = '$contractId' AND season_name = '$seasonName' AND deleted_at IS NULL");
    }

    /**
     * Find the applicable rate for a product on a specific travel date.
     * Priority: season-specific rate (highest priority) > base rate (no season)
     */
    public function findApplicableRate(int $contractId, int $modelId, string $travelDate): ?array
    {
        $contractId = \sql_int($contractId);
        $modelId = \sql_int($modelId);
        $travelDate = \sql_escape($travelDate);

        $sql = "SELECT * FROM contract_rate
                WHERE contract_id = '$contractId'
                  AND model_id = '$modelId'
                  AND deleted_at IS NULL
                  AND (
                      (season_start IS NOT NULL AND season_end IS NOT NULL
                       AND '$travelDate' BETWEEN season_start AND season_end)
                      OR season_name IS NULL
                  )
                ORDER BY
                  CASE WHEN season_name IS NOT NULL THEN 0 ELSE 1 END,
                  priority DESC
                LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        if ($r && $row = mysqli_fetch_assoc($r)) return $row;
        return null;
    }

    // ─── V2: Clone Contract ────────────────────────────────────

    /**
     * Clone a contract with all its rates and type links
     */
    public function cloneContract(int $contractId, int $comId, string $newName = ''): int
    {
        $contract = $this->getContract($contractId, $comId);
        if (!$contract) return 0;

        $newContractId = $this->createOperatorContract([
            'company_id'    => $comId,
            'contract_name' => $newName ?: ($contract['contract_name'] . ' (Copy)'),
            'status'        => 'draft',
            'valid_from'    => $contract['valid_from'],
            'valid_to'      => $contract['valid_to'],
            'payment_terms' => $contract['payment_terms'],
            'credit_days'   => $contract['credit_days'],
            'deposit_pct'   => $contract['deposit_pct'],
            'conditions'    => $contract['conditions'],
            'notes'         => $contract['notes'],
            'type_ids'      => $contract['type_ids'],
        ]);

        if ($newContractId) {
            // Clone rates
            $rates = $this->getContractRatesBySeason($contractId);
            foreach ($rates as $season => $seasonData) {
                $this->saveSeasonRates(
                    $newContractId, $comId, $seasonData['rates'],
                    $seasonData['season_name'], $seasonData['season_start'],
                    $seasonData['season_end'], $seasonData['priority']
                );
            }
        }

        return $newContractId;
    }

    // ─── V2: Soft-delete with sync cleanup ─────────────────────

    /**
     * Soft-delete an operator-level contract + clean up junctions
     */
    public function deleteOperatorContract(int $id, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);

        // Soft-delete rates
        mysqli_query($this->conn,
            "UPDATE contract_rate SET deleted_at = NOW() WHERE contract_id = '$id' AND deleted_at IS NULL");

        // Remove type links
        mysqli_query($this->conn,
            "DELETE FROM agent_contract_types WHERE contract_id = '$id'");

        // Remove agent assignments
        mysqli_query($this->conn,
            "DELETE FROM tour_contract_agents WHERE contract_id = '$id' AND company_id = '$comId'");

        // Remove synced products from this contract
        mysqli_query($this->conn,
            "DELETE FROM tour_operator_agent_products WHERE contract_id = '$id' AND operator_company_id = '$comId'");

        // Soft-delete the contract
        return mysqli_query($this->conn,
            "UPDATE agent_contracts SET deleted_at = NOW() WHERE id = '$id' AND company_id = '$comId' AND deleted_at IS NULL");
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
