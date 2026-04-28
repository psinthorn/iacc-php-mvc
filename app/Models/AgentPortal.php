<?php
namespace App\Models;

/**
 * AgentPortal — Read-only data access for agent-side portal
 *
 * The "agent" is a company in the `company` table.
 * They access products/contracts/bookings shared with them
 * by tour operators via tour_operator_agents.
 */
class AgentPortal extends BaseModel
{
    protected string $table = 'tour_operator_agents';
    protected bool $useCompanyFilter = false;

    /**
     * Get all operators that this agent is approved for
     */
    public function getOperators(int $agentComId): array
    {
        $agentComId = intval($agentComId);
        $sql = "SELECT oa.*,
                       c.name_en AS operator_name_en,
                       c.name_th AS operator_name_th,
                       c.email   AS operator_email,
                       c.phone   AS operator_phone,
                       ac.contract_name AS default_contract_name
                FROM tour_operator_agents oa
                LEFT JOIN company c ON oa.operator_company_id = c.id
                LEFT JOIN agent_contracts ac ON oa.default_contract_id = ac.id
                WHERE oa.agent_company_id = $agentComId
                  AND oa.status = 'approved'
                  AND oa.deleted_at IS NULL
                ORDER BY c.name_en";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Dashboard stats
     */
    public function getDashboardStats(int $agentComId): array
    {
        $agentComId = intval($agentComId);

        // Operators
        $opSql = "SELECT COUNT(*) AS cnt FROM tour_operator_agents
                  WHERE agent_company_id = $agentComId AND status = 'approved' AND deleted_at IS NULL";
        $opRes = mysqli_query($this->conn, $opSql);
        $operators = $opRes ? intval(mysqli_fetch_assoc($opRes)['cnt']) : 0;

        // Active products
        $prSql = "SELECT COUNT(*) AS cnt FROM tour_operator_agent_products
                  WHERE agent_company_id = $agentComId AND is_active = 1";
        $prRes = mysqli_query($this->conn, $prSql);
        $products = $prRes ? intval(mysqli_fetch_assoc($prRes)['cnt']) : 0;

        // Contracts
        $cnSql = "SELECT COUNT(DISTINCT contract_id) AS cnt FROM tour_contract_agents
                  WHERE agent_company_id = $agentComId";
        $cnRes = mysqli_query($this->conn, $cnSql);
        $contracts = $cnRes ? intval(mysqli_fetch_assoc($cnRes)['cnt']) : 0;

        // Bookings (last 30 days)
        $bkSql = "SELECT COUNT(*) AS cnt FROM tour_bookings
                  WHERE agent_id = $agentComId
                    AND deleted_at IS NULL
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $bkRes = mysqli_query($this->conn, $bkSql);
        $bookings30d = $bkRes ? intval(mysqli_fetch_assoc($bkRes)['cnt']) : 0;

        return [
            'operators'    => $operators,
            'products'     => $products,
            'contracts'    => $contracts,
            'bookings_30d' => $bookings30d,
        ];
    }

    /**
     * Products synced to this agent (from all operators)
     */
    public function getProducts(int $agentComId, ?int $operatorComId = null): array
    {
        $agentComId = intval($agentComId);
        $opFilter = '';
        if ($operatorComId !== null) {
            $opFilter = ' AND p.operator_company_id = ' . intval($operatorComId);
        }
        $sql = "SELECT p.*,
                       m.model_name, m.des AS model_desc,
                       t.name AS type_name,
                       ac.contract_name,
                       co.name_en AS operator_name
                FROM tour_operator_agent_products p
                LEFT JOIN model m ON p.model_id = m.id
                LEFT JOIN type t ON m.type_id = t.id
                LEFT JOIN agent_contracts ac ON p.contract_id = ac.id
                LEFT JOIN company co ON p.operator_company_id = co.id
                WHERE p.agent_company_id = $agentComId
                  AND p.is_active = 1
                  $opFilter
                ORDER BY co.name_en, t.name, m.model_name";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Contracts this agent is assigned to
     */
    public function getContracts(int $agentComId): array
    {
        $agentComId = intval($agentComId);
        $sql = "SELECT ac.*,
                       ca.assigned_at,
                       co.name_en AS operator_name,
                       co.id AS operator_id,
                       (SELECT COUNT(*) FROM contract_rate cr WHERE cr.contract_id = ac.id AND cr.deleted_at IS NULL) AS rate_count,
                       (SELECT COUNT(DISTINCT season_name) FROM contract_rate cr WHERE cr.contract_id = ac.id AND cr.season_name IS NOT NULL AND cr.deleted_at IS NULL) AS season_count
                FROM tour_contract_agents ca
                INNER JOIN agent_contracts ac ON ca.contract_id = ac.id
                LEFT JOIN company co ON ac.company_id = co.id
                WHERE ca.agent_company_id = $agentComId
                  AND (ac.deleted_at IS NULL OR ac.deleted_at = '0000-00-00 00:00:00')
                ORDER BY co.name_en, ac.contract_name";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Get a single contract for this agent (with access check)
     */
    public function getContractDetail(int $agentComId, int $contractId): ?array
    {
        $agentComId = intval($agentComId);
        $contractId = intval($contractId);
        $sql = "SELECT ac.*,
                       co.name_en AS operator_name
                FROM tour_contract_agents ca
                INNER JOIN agent_contracts ac ON ca.contract_id = ac.id
                LEFT JOIN company co ON ac.company_id = co.id
                WHERE ca.agent_company_id = $agentComId
                  AND ca.contract_id = $contractId
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    /**
     * Get rates for a specific contract (for agent view)
     */
    public function getContractRates(int $agentComId, int $contractId): array
    {
        // Verify access first
        if (!$this->getContractDetail($agentComId, $contractId)) return [];

        $contractId = intval($contractId);
        $sql = "SELECT cr.*, m.model_name, t.name AS type_name
                FROM contract_rate cr
                LEFT JOIN model m ON cr.model_id = m.id
                LEFT JOIN type t ON m.type_id = t.id
                WHERE cr.contract_id = $contractId
                  AND cr.deleted_at IS NULL
                ORDER BY cr.season_name DESC, t.name, m.model_name";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Bookings this agent has created
     */
    public function getBookings(int $agentComId, int $limit = 50): array
    {
        $agentComId = intval($agentComId);
        $limit = intval($limit);
        $sql = "SELECT b.*,
                       cu.name_en AS customer_name,
                       co.name_en AS operator_name
                FROM tour_bookings b
                LEFT JOIN company cu ON b.customer_id = cu.id
                LEFT JOIN company co ON b.company_id = co.id
                WHERE b.agent_id = $agentComId
                  AND b.deleted_at IS NULL
                ORDER BY b.created_at DESC
                LIMIT $limit";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Check if this agent is approved for a specific operator
     */
    public function isApprovedFor(int $agentComId, int $operatorComId): bool
    {
        $agentComId = intval($agentComId);
        $operatorComId = intval($operatorComId);
        $sql = "SELECT id FROM tour_operator_agents
                WHERE agent_company_id = $agentComId
                  AND operator_company_id = $operatorComId
                  AND status = 'approved'
                  AND deleted_at IS NULL
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        return $res && mysqli_num_rows($res) > 0;
    }

    /**
     * Get the agent's company info (own profile)
     */
    public function getAgentProfile(int $agentComId): ?array
    {
        $agentComId = intval($agentComId);
        $sql = "SELECT * FROM company WHERE id = $agentComId LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }
}
