<?php
namespace App\Models;

/**
 * ApiUsageLog Model
 * 
 * Manages api_usage_logs table.
 * Tracks every API request for analytics and quota enforcement.
 */
class ApiUsageLog extends BaseModel
{
    protected string $table = 'api_usage_logs';
    protected bool $useCompanyFilter = false;

    /**
     * Log an API request
     */
    public function logRequest(array $data): int
    {
        // Truncate request/response bodies to prevent bloat
        if (isset($data['request_body']) && strlen($data['request_body']) > 5000) {
            $data['request_body'] = substr($data['request_body'], 0, 5000) . '...[truncated]';
        }
        if (isset($data['response_body']) && strlen($data['response_body']) > 5000) {
            $data['response_body'] = substr($data['response_body'], 0, 5000) . '...[truncated]';
        }

        return $this->hard->insertSafe($this->table, $data);
    }

    /**
     * Get paginated logs for a company
     */
    public function getForCompany(int $companyId, int $page = 1, int $perPage = 20): array
    {
        $cid = \sql_int($companyId);
        
        // Count
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE `company_id` = '$cid'";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);

        // Fetch
        $sql = "SELECT l.*, k.key_name, k.api_key
                FROM `{$this->table}` l
                JOIN `api_keys` k ON l.api_key_id = k.id
                WHERE l.company_id = '$cid'
                ORDER BY l.created_at DESC
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
     * Get usage summary by day for the last N days
     */
    public function getDailySummary(int $companyId, int $days = 30): array
    {
        $cid = \sql_int($companyId);
        $sql = "SELECT DATE(created_at) as day, 
                COUNT(*) as requests,
                SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status_code != 200 THEN 1 ELSE 0 END) as errors,
                AVG(processing_ms) as avg_ms
                FROM `{$this->table}` 
                WHERE `company_id` = '$cid' 
                AND `created_at` >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY DATE(created_at) 
                ORDER BY day DESC";
        
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
     * Get channel breakdown for a company
     */
    public function getChannelBreakdown(int $companyId): array
    {
        $cid = \sql_int($companyId);
        $sql = "SELECT channel, COUNT(*) as count 
                FROM `{$this->table}` 
                WHERE `company_id` = '$cid'
                GROUP BY channel 
                ORDER BY count DESC";
        
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
