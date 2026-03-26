<?php
namespace App\Models;

/**
 * ChannelOrder Model
 * 
 * Manages channel_orders table.
 * Handles CRUD for orders received via Sales Channel API.
 */
class ChannelOrder extends BaseModel
{
    protected string $table = 'channel_orders';
    protected bool $useCompanyFilter = false;

    /**
     * Create a new order
     */
    public function createOrder(array $data): int
    {
        return $this->hard->insertSafe($this->table, $data);
    }

    /**
     * Get order by ID with ownership check
     */
    public function findForCompany(int $id, int $companyId): ?array
    {
        $bid = \sql_int($id);
        $cid = \sql_int($companyId);
        $sql = "SELECT * FROM `{$this->table}` WHERE `id` = '$bid' AND `company_id` = '$cid'";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Find order by idempotency key (for duplicate prevention)
     */
    public function findByIdempotencyKey(int $companyId, string $key): ?array
    {
        $cid = \sql_int($companyId);
        $k = \sql_escape($key);
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `company_id` = '$cid' AND `idempotency_key` = '$k'
                AND `created_at` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Update order fields (for API PUT endpoint)
     */
    public function updateFields(int $id, array $data): bool
    {
        $where = ['id' => $id];
        return $this->hard->updateSafe($this->table, $data, $where);
    }

    /**
     * Update order status and linked records
     */
    public function updateStatus(int $id, string $status, array $extra = []): bool
    {
        $data = array_merge(['status' => $status], $extra);
        if ($status === 'completed' || $status === 'failed') {
            $data['processed_at'] = date('Y-m-d H:i:s');
        }
        $where = ['id' => $id];
        return $this->hard->updateSafe($this->table, $data, $where);
    }

    /**
     * Link order to created iACC records
     */
    public function linkRecords(int $orderId, int $companyId, int $prId, int $poId): bool
    {
        $data = [
            'linked_company_id' => $companyId,
            'linked_pr_id'      => $prId,
            'linked_po_id'      => $poId,
            'status'            => 'completed',
            'processed_at'      => date('Y-m-d H:i:s'),
        ];
        $where = ['id' => $orderId];
        return $this->hard->updateSafe($this->table, $data, $where);
    }

    /**
     * Get paginated orders for a company
     */
    public function getForCompany(int $companyId, array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $cid = \sql_int($companyId);
        $where = "WHERE b.company_id = '$cid'";

        // Status filter
        if (!empty($filters['status'])) {
            $status = \sql_escape($filters['status']);
            $where .= " AND b.status = '$status'";
        }

        // Channel filter
        if (!empty($filters['channel'])) {
            $channel = \sql_escape($filters['channel']);
            $where .= " AND b.channel = '$channel'";
        }

        // Date filter
        if (!empty($filters['date_from'])) {
            $from = \sql_escape($filters['date_from']);
            $where .= " AND b.created_at >= '$from'";
        }
        if (!empty($filters['date_to'])) {
            $to = \sql_escape($filters['date_to']);
            $where .= " AND b.created_at <= '$to 23:59:59'";
        }

        // Search
        if (!empty($filters['search'])) {
            $s = \sql_escape($filters['search']);
            $where .= " AND (b.guest_name LIKE '%$s%' OR b.guest_email LIKE '%$s%' OR b.guest_phone LIKE '%$s%')";
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` b $where";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);

        // Fetch
        $sql = "SELECT b.*, 
                po.name as po_name, po.tax as po_tax
                FROM `{$this->table}` b
                LEFT JOIN `po` ON b.linked_po_id = po.id
                $where
                ORDER BY b.created_at DESC
                LIMIT {$pagination['offset']}, $perPage";

        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }

        return [
            'items'      => $items,
            'total'      => $total,
            'pagination' => $pagination,
        ];
    }

    /**
     * Get recent orders for dashboard widget
     */
    public function getRecent(int $companyId, int $limit = 5): array
    {
        $cid = \sql_int($companyId);
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `company_id` = '$cid' 
                ORDER BY `created_at` DESC 
                LIMIT $limit";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Get order statistics for a company
     */
    public function getStats(int $companyId): array
    {
        $cid = \sql_int($companyId);
        $monthStart = date('Y-m-01');
        
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN created_at >= '$monthStart' THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue
                FROM `{$this->table}` WHERE `company_id` = '$cid'";
        
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        return ['total' => 0, 'completed' => 0, 'pending' => 0, 'failed' => 0, 'this_month' => 0, 'total_revenue' => 0];
    }
}
