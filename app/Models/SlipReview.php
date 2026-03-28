<?php
namespace App\Models;

/**
 * SlipReview Model — PromptPay Slip Review & Payment Confirmation
 * 
 * Queries payment_log records for PromptPay transactions with slip images.
 * Supports admin workflow: pending_review → completed | rejected.
 * 
 * Table: payment_log
 * Key columns: id, gateway, order_id, reference_id, amount, currency,
 *              status, slip_image, request_data, response_data, created_at
 * 
 * @package App\Models
 * @version 1.0.0 — Q2 2026
 */
class SlipReview extends BaseModel
{
    protected string $table = 'payment_log';

    /**
     * Get counts by status for stats cards
     */
    public function getStatusCounts(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_review,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_confirmed
                FROM payment_log 
                WHERE gateway = 'promptpay'";
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        return ['total' => 0, 'pending' => 0, 'pending_review' => 0, 'completed' => 0, 'rejected' => 0, 'total_confirmed' => 0];
    }

    /**
     * Get payment log records with optional filters
     *
     * @param string $status   Filter by status (empty = all)
     * @param string $search   Search in order_id, reference_id
     * @param string $dateFrom Start date filter
     * @param string $dateTo   End date filter
     * @param int    $limit    Records per page
     * @param int    $offset   Offset for pagination
     * @return array
     */
    public function getSlipPayments(string $status = '', string $search = '', string $dateFrom = '', string $dateTo = '', int $limit = 50, int $offset = 0): array
    {
        $where = "WHERE pl.gateway = 'promptpay'";

        if ($status !== '') {
            $status = sql_escape($status);
            $where .= " AND pl.status = '{$status}'";
        }

        if ($search !== '') {
            $search = sql_escape($search);
            $where .= " AND (pl.order_id LIKE '%{$search}%' OR pl.reference_id LIKE '%{$search}%')";
        }

        if ($dateFrom !== '') {
            $dateFrom = sql_escape($dateFrom);
            $where .= " AND DATE(pl.created_at) >= '{$dateFrom}'";
        }

        if ($dateTo !== '') {
            $dateTo = sql_escape($dateTo);
            $where .= " AND DATE(pl.created_at) <= '{$dateTo}'";
        }

        $sql = "SELECT pl.*
                FROM payment_log pl
                {$where}
                ORDER BY 
                    CASE pl.status 
                        WHEN 'pending_review' THEN 1 
                        WHEN 'pending' THEN 2 
                        WHEN 'completed' THEN 3 
                        WHEN 'rejected' THEN 4 
                        ELSE 5 
                    END,
                    pl.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $result = mysqli_query($this->conn, $sql);
        $records = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $records[] = $row;
            }
        }
        return $records;
    }

    /**
     * Get total count for pagination
     */
    public function getSlipPaymentsCount(string $status = '', string $search = '', string $dateFrom = '', string $dateTo = ''): int
    {
        $where = "WHERE gateway = 'promptpay'";

        if ($status !== '') {
            $where .= " AND status = '" . sql_escape($status) . "'";
        }
        if ($search !== '') {
            $s = sql_escape($search);
            $where .= " AND (order_id LIKE '%{$s}%' OR reference_id LIKE '%{$s}%')";
        }
        if ($dateFrom !== '') {
            $where .= " AND DATE(created_at) >= '" . sql_escape($dateFrom) . "'";
        }
        if ($dateTo !== '') {
            $where .= " AND DATE(created_at) <= '" . sql_escape($dateTo) . "'";
        }

        $sql = "SELECT COUNT(*) as cnt FROM payment_log {$where}";
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return (int) $row['cnt'];
        }
        return 0;
    }

    /**
     * Get single payment log by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM payment_log WHERE id = " . intval($id) . " AND gateway = 'promptpay' LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        return ($result && $row = mysqli_fetch_assoc($result)) ? $row : null;
    }

    /**
     * Approve a slip — mark as completed
     */
    public function approve(int $id, int $userId): bool
    {
        $responseData = sql_escape(json_encode([
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $userId,
            'action' => 'approved',
        ]));

        $sql = "UPDATE payment_log 
                SET status = 'completed', 
                    response_data = '{$responseData}',
                    updated_at = NOW()
                WHERE id = " . intval($id) . " 
                AND gateway = 'promptpay' 
                AND status IN ('pending', 'pending_review')";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Reject a slip — mark as rejected with reason
     */
    public function reject(int $id, int $userId, string $reason = ''): bool
    {
        $responseData = sql_escape(json_encode([
            'rejected_at' => date('Y-m-d H:i:s'),
            'rejected_by' => $userId,
            'action' => 'rejected',
            'reason' => $reason,
        ]));

        $sql = "UPDATE payment_log 
                SET status = 'rejected', 
                    response_data = '{$responseData}',
                    updated_at = NOW()
                WHERE id = " . intval($id) . " 
                AND gateway = 'promptpay' 
                AND status IN ('pending', 'pending_review')";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Extract invoice ID from order_id (format: INV-{id})
     */
    public function extractInvoiceId(string $orderId): int
    {
        if (preg_match('/INV-(\d+)/', $orderId, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Get invoice basic info for display
     */
    public function getInvoiceInfo(int $invoiceId): ?array
    {
        if ($invoiceId <= 0) return null;
        
        $sql = "SELECT iv.tex, iv.po_id, iv.date, iv.total, iv.status,
                       po.name as po_name, po.tax as po_number,
                       c.name as customer_name
                FROM iv
                LEFT JOIN po ON iv.po_id = po.id
                LEFT JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                WHERE iv.tex = {$invoiceId}
                LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        return ($result && $row = mysqli_fetch_assoc($result)) ? $row : null;
    }
}
