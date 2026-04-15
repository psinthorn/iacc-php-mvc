<?php
namespace App\Models;

/**
 * TourAgentProfile Model
 * 
 * Agents are existing company records (vender=1).
 * This model manages the tour_agent_profiles extension table
 * that adds commission, contract, and contact details.
 */
class TourAgentProfile extends BaseModel
{
    protected string $table = 'tour_agent_profiles';
    protected bool $useCompanyFilter = true;

    /**
     * Get all agent profiles with company info for a tenant
     */
    public function getProfiles(int $comId, array $filters = []): array
    {
        $comId = \sql_int($comId);
        $conds = '';

        if (!empty($filters['search'])) {
            $s = \sql_escape($filters['search']);
            $conds .= " AND (c.name_en LIKE '%$s%' OR c.name_th LIKE '%$s%' OR c.contact LIKE '%$s%' OR c.phone LIKE '%$s%')";
        }
        if (!empty($filters['commission_type'])) {
            $conds .= " AND tap.commission_type = '" . \sql_escape($filters['commission_type']) . "'";
        }

        $sql = "SELECT tap.*, c.name_en, c.name_th, c.contact, c.phone, c.email, c.fax
                FROM tour_agent_profiles tap
                JOIN company c ON tap.company_ref_id = c.id
                WHERE tap.company_id = '$comId' AND tap.deleted_at IS NULL
                $conds
                ORDER BY c.name_en ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Find a single profile by its ID
     */
    public function findProfile(int $id, int $comId): ?array
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);
        $sql = "SELECT tap.*, c.name_en, c.name_th, c.contact, c.phone, c.email, c.fax
                FROM tour_agent_profiles tap
                JOIN company c ON tap.company_ref_id = c.id
                WHERE tap.id = '$id' AND tap.company_id = '$comId' AND tap.deleted_at IS NULL";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    /**
     * Find profile by company_ref_id (the vendor company record)
     */
    public function findByCompanyRef(int $companyRefId, int $comId): ?array
    {
        $companyRefId = \sql_int($companyRefId);
        $comId = \sql_int($comId);
        $sql = "SELECT tap.*, c.name_en, c.name_th, c.contact, c.phone, c.email
                FROM tour_agent_profiles tap
                JOIN company c ON tap.company_ref_id = c.id
                WHERE tap.company_ref_id = '$companyRefId' AND tap.company_id = '$comId' AND tap.deleted_at IS NULL";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    /**
     * Get customer/vendor companies that can become agents (owned by $comId)
     * Used for the agent dropdown in the form
     */
    public function getAvailableVendors(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT c.id, c.name_en, c.name_th, c.contact, c.phone, c.email,
                       c.customer, c.vender, c.company_type,
                       (SELECT tap.id FROM tour_agent_profiles tap 
                        WHERE tap.company_ref_id = c.id AND tap.company_id = '$comId' AND tap.deleted_at IS NULL) as profile_id
                FROM company c
                WHERE (c.customer = 1 OR c.vender = 1) AND c.company_id = '$comId' AND c.deleted_at IS NULL
                ORDER BY c.name_en ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Create a new agent profile
     */
    public function createProfile(array $data): int
    {
        $sql = "INSERT INTO tour_agent_profiles 
                (company_ref_id, company_id, commission_type, commission_adult, commission_child,
                 contract_start, contract_end, contact_line, contact_whatsapp, notes)
                VALUES (
                    '" . \sql_int($data['company_ref_id']) . "',
                    '" . \sql_int($data['company_id']) . "',
                    '" . \sql_escape($data['commission_type'] ?? 'percentage') . "',
                    '" . floatval($data['commission_adult'] ?? 0) . "',
                    '" . floatval($data['commission_child'] ?? 0) . "',
                    " . (!empty($data['contract_start']) ? "'" . \sql_escape($data['contract_start']) . "'" : "NULL") . ",
                    " . (!empty($data['contract_end']) ? "'" . \sql_escape($data['contract_end']) . "'" : "NULL") . ",
                    " . (!empty($data['contact_line']) ? "'" . \sql_escape($data['contact_line']) . "'" : "NULL") . ",
                    " . (!empty($data['contact_whatsapp']) ? "'" . \sql_escape($data['contact_whatsapp']) . "'" : "NULL") . ",
                    " . (!empty($data['notes']) ? "'" . \sql_escape($data['notes']) . "'" : "NULL") . "
                )";
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    /**
     * Update an existing agent profile
     */
    public function updateProfile(int $id, array $data, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);
        $sql = "UPDATE tour_agent_profiles SET
                    commission_type = '" . \sql_escape($data['commission_type'] ?? 'percentage') . "',
                    commission_adult = '" . floatval($data['commission_adult'] ?? 0) . "',
                    commission_child = '" . floatval($data['commission_child'] ?? 0) . "',
                    contract_start = " . (!empty($data['contract_start']) ? "'" . \sql_escape($data['contract_start']) . "'" : "NULL") . ",
                    contract_end = " . (!empty($data['contract_end']) ? "'" . \sql_escape($data['contract_end']) . "'" : "NULL") . ",
                    contact_line = " . (!empty($data['contact_line']) ? "'" . \sql_escape($data['contact_line']) . "'" : "NULL") . ",
                    contact_whatsapp = " . (!empty($data['contact_whatsapp']) ? "'" . \sql_escape($data['contact_whatsapp']) . "'" : "NULL") . ",
                    notes = " . (!empty($data['notes']) ? "'" . \sql_escape($data['notes']) . "'" : "NULL") . "
                WHERE id = '$id' AND company_id = '$comId' AND deleted_at IS NULL";
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Soft delete an agent profile
     */
    public function deleteProfile(int $id, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);
        $sql = "UPDATE tour_agent_profiles SET deleted_at = NOW() WHERE id = '$id' AND company_id = '$comId'";
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Get agents for dropdown (profiles with company names)
     */
    public function getAgentDropdown(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT tap.id, tap.company_ref_id, c.name_en, c.name_th
                FROM tour_agent_profiles tap
                JOIN company c ON tap.company_ref_id = c.id
                WHERE tap.company_id = '$comId' AND tap.deleted_at IS NULL
                ORDER BY c.name_en ASC";
        return $this->fetchAll($sql);
    }

    // ================================================================
    // Contract Rate Methods
    // ================================================================

    /**
     * Get models grouped by product type for a company
     */
    public function getModelsGroupedByType(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT m.id, m.model_name, m.price, t.id AS type_id, t.name AS type_name
                FROM model m
                JOIN type t ON m.type_id = t.id
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
     * Get all contract rates for an agent, keyed by model_id (0 = default)
     */
    public function getContractRates(int $agentCompanyId, int $comId): array
    {
        $agentCompanyId = \sql_int($agentCompanyId);
        $comId = \sql_int($comId);
        $sql = "SELECT * FROM contract_rate
                WHERE agent_company_id = '$agentCompanyId'
                  AND company_id = '$comId'
                  AND deleted_at IS NULL
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
     * Save contract rates for an agent (upsert: delete removed, update existing, insert new)
     */
    public function saveContractRates(int $agentCompanyId, int $comId, array $rates): void
    {
        $agentCompanyId = \sql_int($agentCompanyId);
        $comId = \sql_int($comId);

        // Soft-delete all existing rates for this agent
        $sqlDel = "UPDATE contract_rate SET deleted_at = NOW()
                   WHERE agent_company_id = '$agentCompanyId' AND company_id = '$comId' AND deleted_at IS NULL";
        mysqli_query($this->conn, $sqlDel);

        // Insert/re-insert each rate
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
            $validFrom  = !empty($rate['valid_from']) ? "'" . \sql_escape($rate['valid_from']) . "'" : "'2026-01-01'";
            $validTo    = !empty($rate['valid_to']) ? "'" . \sql_escape($rate['valid_to']) . "'" : "'2026-12-31'";

            // Skip rows where all rate values are zero (not configured)
            if ($adultDef == 0 && $childDef == 0
                && $adultThai == 0 && $adultFor == 0 && $childThai == 0 && $childFor == 0
                && $entAdultDef == 0 && $entChildDef == 0
                && $entAT == 0 && $entAF == 0 && $entCT == 0 && $entCF == 0) {
                continue;
            }

            $sql = "INSERT INTO contract_rate
                    (company_id, agent_company_id, model_id, rate_type,
                     adult_default, child_default,
                     adult_thai, adult_foreigner, child_thai, child_foreigner,
                     entrance_adult_default, entrance_child_default,
                     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
                     valid_from, valid_to)
                    VALUES ('$comId', '$agentCompanyId', $modelId, '$rateType',
                            '$adultDef', '$childDef',
                            '$adultThai', '$adultFor', '$childThai', '$childFor',
                            '$entAdultDef', '$entChildDef',
                            '$entAT', '$entAF', '$entCT', '$entCF',
                            $validFrom, $validTo)
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
                        valid_from = VALUES(valid_from), valid_to = VALUES(valid_to),
                        deleted_at = NULL";
            mysqli_query($this->conn, $sql);
        }
    }

    /**
     * Apply fallback logic: if a specific Thai/Foreigner field is 0, use the default value
     */
    public function getEffectiveRates(array $rate): array
    {
        $adultDef   = floatval($rate['adult_default'] ?? 0);
        $childDef   = floatval($rate['child_default'] ?? 0);
        $entAdultDef = floatval($rate['entrance_adult_default'] ?? 0);
        $entChildDef = floatval($rate['entrance_child_default'] ?? 0);

        $rate['eff_adult_thai']      = floatval($rate['adult_thai'] ?? 0) ?: $adultDef;
        $rate['eff_adult_foreigner'] = floatval($rate['adult_foreigner'] ?? 0) ?: $adultDef;
        $rate['eff_child_thai']      = floatval($rate['child_thai'] ?? 0) ?: $childDef;
        $rate['eff_child_foreigner'] = floatval($rate['child_foreigner'] ?? 0) ?: $childDef;

        $rate['eff_entrance_adult_thai']      = floatval($rate['entrance_adult_thai'] ?? 0) ?: $entAdultDef;
        $rate['eff_entrance_adult_foreigner'] = floatval($rate['entrance_adult_foreigner'] ?? 0) ?: $entAdultDef;
        $rate['eff_entrance_child_thai']      = floatval($rate['entrance_child_thai'] ?? 0) ?: $entChildDef;
        $rate['eff_entrance_child_foreigner'] = floatval($rate['entrance_child_foreigner'] ?? 0) ?: $entChildDef;

        return $rate;
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
