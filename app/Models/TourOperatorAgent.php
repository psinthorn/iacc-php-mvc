<?php
namespace App\Models;

/**
 * TourOperatorAgent — Agent registration & approval workflow
 *
 * Manages tour_operator_agents table:
 *   - Agent self-registration & invitation
 *   - Status flow: pending → approved → suspended → rejected
 *   - Default contract assignment on approval
 */
class TourOperatorAgent extends BaseModel
{
    protected string $table = 'tour_operator_agents';
    protected bool $useCompanyFilter = false;

    /**
     * List agents for an operator with optional status filter
     */
    public function listForOperator(int $operatorComId, ?string $status = null): array
    {
        $operatorComId = intval($operatorComId);
        $statusFilter = '';
        if ($status !== null && in_array($status, ['pending', 'approved', 'suspended', 'rejected'], true)) {
            $statusEsc = mysqli_real_escape_string($this->conn, $status);
            $statusFilter = "AND oa.status = '$statusEsc'";
        }

        $sql = "SELECT oa.*,
                       c.name_en AS agent_name_en,
                       c.name_th AS agent_name_th,
                       c.email AS agent_email,
                       c.phone AS agent_phone,
                       c.address AS agent_address,
                       ac.contract_name AS default_contract_name,
                       au.email AS approver_email
                FROM tour_operator_agents oa
                LEFT JOIN company c ON oa.agent_company_id = c.id
                LEFT JOIN agent_contracts ac ON oa.default_contract_id = ac.id
                LEFT JOIN authorize au ON oa.approved_by = au.id
                WHERE oa.operator_company_id = $operatorComId
                  AND oa.deleted_at IS NULL
                  $statusFilter
                ORDER BY
                    CASE oa.status
                        WHEN 'pending' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'suspended' THEN 3
                        WHEN 'rejected' THEN 4
                    END,
                    oa.created_at DESC";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Get status counts for operator dashboard
     */
    public function getStatusCounts(int $operatorComId): array
    {
        $operatorComId = intval($operatorComId);
        $sql = "SELECT status, COUNT(*) AS cnt
                FROM tour_operator_agents
                WHERE operator_company_id = $operatorComId AND deleted_at IS NULL
                GROUP BY status";
        $res = mysqli_query($this->conn, $sql);
        $counts = ['pending' => 0, 'approved' => 0, 'suspended' => 0, 'rejected' => 0, 'total' => 0];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $counts[$row['status']] = intval($row['cnt']);
                $counts['total'] += intval($row['cnt']);
            }
        }
        return $counts;
    }

    /**
     * Get a single registration record
     */
    public function getRegistration(int $id, int $operatorComId): ?array
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $sql = "SELECT oa.*,
                       c.name_en AS agent_name_en, c.name_th AS agent_name_th,
                       c.email AS agent_email, c.phone AS agent_phone, c.address AS agent_address
                FROM tour_operator_agents oa
                LEFT JOIN company c ON oa.agent_company_id = c.id
                WHERE oa.id = $id AND oa.operator_company_id = $operatorComId
                  AND oa.deleted_at IS NULL
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    /**
     * Find existing registration by operator + agent
     */
    public function findByOperatorAgent(int $operatorComId, int $agentComId): ?array
    {
        $operatorComId = intval($operatorComId);
        $agentComId = intval($agentComId);
        $sql = "SELECT * FROM tour_operator_agents
                WHERE operator_company_id = $operatorComId
                  AND agent_company_id = $agentComId
                  AND deleted_at IS NULL
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    /**
     * Find by invitation token (for accept-invite flow)
     */
    public function findByToken(string $token): ?array
    {
        $tokenEsc = mysqli_real_escape_string($this->conn, $token);
        $sql = "SELECT oa.*, c.name_en AS operator_name
                FROM tour_operator_agents oa
                LEFT JOIN company c ON oa.operator_company_id = c.id
                WHERE oa.invitation_token = '$tokenEsc'
                  AND oa.invitation_expires > NOW()
                  AND oa.deleted_at IS NULL
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    /**
     * Create a registration record (self-register or operator-add)
     *
     * @param array $data Required: operator_company_id, agent_company_id
     *                    Optional: status, registered_via, notes
     */
    public function createRegistration(array $data): int
    {
        $operatorComId = intval($data['operator_company_id'] ?? 0);
        $agentComId = intval($data['agent_company_id'] ?? 0);
        $status = mysqli_real_escape_string($this->conn, $data['status'] ?? 'pending');
        $via = mysqli_real_escape_string($this->conn, $data['registered_via'] ?? 'self');
        $notes = isset($data['notes']) ? "'" . mysqli_real_escape_string($this->conn, $data['notes']) . "'" : 'NULL';

        if (!$operatorComId || !$agentComId) return 0;

        // Idempotency: if a record already exists, return its ID
        $existing = $this->findByOperatorAgent($operatorComId, $agentComId);
        if ($existing) {
            return intval($existing['id']);
        }

        $sql = "INSERT INTO tour_operator_agents
                    (operator_company_id, agent_company_id, status, registered_via, notes)
                VALUES ($operatorComId, $agentComId, '$status', '$via', $notes)";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    /**
     * Create an invitation (operator invites a not-yet-registered agent)
     */
    public function createInvitation(int $operatorComId, int $agentComId, ?string $notes = null): array
    {
        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', strtotime('+14 days'));

        $operatorComIdEsc = intval($operatorComId);
        $agentComIdEsc = intval($agentComId);
        $tokenEsc = mysqli_real_escape_string($this->conn, $token);
        $expiresEsc = mysqli_real_escape_string($this->conn, $expires);
        $notesEsc = $notes !== null ? "'" . mysqli_real_escape_string($this->conn, $notes) . "'" : 'NULL';

        // Check for existing
        $existing = $this->findByOperatorAgent($operatorComId, $agentComId);
        if ($existing) {
            // Refresh the token
            $sql = "UPDATE tour_operator_agents
                    SET invitation_token = '$tokenEsc',
                        invitation_expires = '$expiresEsc',
                        registered_via = 'invitation'
                    WHERE id = " . intval($existing['id']);
            mysqli_query($this->conn, $sql);
            return ['id' => intval($existing['id']), 'token' => $token, 'expires' => $expires];
        }

        $sql = "INSERT INTO tour_operator_agents
                    (operator_company_id, agent_company_id, status, registered_via,
                     invitation_token, invitation_expires, notes)
                VALUES ($operatorComIdEsc, $agentComIdEsc, 'pending', 'invitation',
                        '$tokenEsc', '$expiresEsc', $notesEsc)";
        mysqli_query($this->conn, $sql);
        return [
            'id' => (int)mysqli_insert_id($this->conn),
            'token' => $token,
            'expires' => $expires,
        ];
    }

    /**
     * Approve a registration. Optionally assigns a default contract.
     */
    public function approve(int $id, int $operatorComId, int $approvedBy, ?int $defaultContractId = null): bool
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $approvedBy = intval($approvedBy);
        $contractClause = '';
        if ($defaultContractId !== null) {
            $contractClause = ', default_contract_id = ' . intval($defaultContractId);
        }
        $sql = "UPDATE tour_operator_agents
                SET status = 'approved',
                    approved_at = NOW(),
                    approved_by = $approvedBy
                    $contractClause
                WHERE id = $id AND operator_company_id = $operatorComId";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Reject a pending registration
     */
    public function reject(int $id, int $operatorComId, ?string $reason = null): bool
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $reasonClause = '';
        if ($reason !== null) {
            $reasonEsc = mysqli_real_escape_string($this->conn, $reason);
            $reasonClause = ", notes = CONCAT(IFNULL(notes,''), '\n[Rejected: $reasonEsc]')";
        }
        $sql = "UPDATE tour_operator_agents
                SET status = 'rejected'
                    $reasonClause
                WHERE id = $id AND operator_company_id = $operatorComId";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Suspend an approved agent
     */
    public function suspend(int $id, int $operatorComId, ?string $reason = null): bool
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $reasonClause = '';
        if ($reason !== null) {
            $reasonEsc = mysqli_real_escape_string($this->conn, $reason);
            $reasonClause = ", notes = CONCAT(IFNULL(notes,''), '\n[Suspended: $reasonEsc]')";
        }
        $sql = "UPDATE tour_operator_agents
                SET status = 'suspended'
                    $reasonClause
                WHERE id = $id AND operator_company_id = $operatorComId";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Reactivate a suspended agent
     */
    public function reactivate(int $id, int $operatorComId): bool
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $sql = "UPDATE tour_operator_agents
                SET status = 'approved'
                WHERE id = $id AND operator_company_id = $operatorComId
                  AND status = 'suspended'";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Soft-delete a registration
     */
    public function softDeleteRegistration(int $id, int $operatorComId): bool
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $sql = "UPDATE tour_operator_agents
                SET deleted_at = NOW()
                WHERE id = $id AND operator_company_id = $operatorComId";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Check if an agent company is approved for an operator
     */
    public function isApprovedAgent(int $operatorComId, int $agentComId): bool
    {
        $rec = $this->findByOperatorAgent($operatorComId, $agentComId);
        return $rec !== null && $rec['status'] === 'approved';
    }

    /**
     * Get all approved agent company IDs for an operator
     */
    public function getApprovedAgentIds(int $operatorComId): array
    {
        $operatorComId = intval($operatorComId);
        $sql = "SELECT agent_company_id
                FROM tour_operator_agents
                WHERE operator_company_id = $operatorComId
                  AND status = 'approved'
                  AND deleted_at IS NULL";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return [];
        $ids = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $ids[] = intval($row['agent_company_id']);
        }
        return $ids;
    }
}
