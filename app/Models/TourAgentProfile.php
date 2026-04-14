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
     * Get vendor companies that can be agents (vender=1, owned by $comId)
     * Used for the agent dropdown in the form
     */
    public function getAvailableVendors(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT c.id, c.name_en, c.name_th, c.contact, c.phone, c.email,
                       (SELECT tap.id FROM tour_agent_profiles tap 
                        WHERE tap.company_ref_id = c.id AND tap.company_id = '$comId' AND tap.deleted_at IS NULL) as profile_id
                FROM company c
                WHERE c.vender = 1 AND c.company_id = '$comId' AND c.deleted_at IS NULL
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

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
