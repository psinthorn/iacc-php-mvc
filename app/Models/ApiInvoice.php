<?php
namespace App\Models;

/**
 * ApiInvoice Model
 *
 * Manages api_invoices table and monthly invoice generation.
 */
class ApiInvoice extends BaseModel
{
    protected string $table = 'api_invoices';
    protected bool $useCompanyFilter = false;

    public function getByCompanyId(int $companyId, int $limit = 24): array
    {
        $cid = \sql_int($companyId);
        $limit = max(1, intval($limit));
        $sql = "SELECT * FROM `{$this->table}` WHERE `company_id` = '$cid' ORDER BY `period_end` DESC LIMIT $limit";
        $result = mysqli_query($this->conn, $sql);

        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function ensureMonthlyInvoice(int $companyId, ?array $subscription, bool $forceCurrent = false): ?int
    {
        if (!$subscription) {
            return null;
        }

        $periodStart = date('Y-m-01');
        $periodEnd = date('Y-m-t');

        if (!$forceCurrent) {
            $existing = $this->findByPeriod($companyId, $periodStart, $periodEnd);
            if ($existing) {
                return intval($existing['id']);
            }
        }

        $planPrices = [
            'trial' => 0,
            'starter' => 990,
            'professional' => 4900,
            'enterprise' => 19900,
        ];

        $plan = $subscription['plan'] ?? 'trial';
        $baseAmount = floatval($planPrices[$plan] ?? 0);

        // Usage for current month
        $usageSql = "SELECT COUNT(*) as cnt FROM `channel_orders`
                     WHERE `company_id` = '" . \sql_int($companyId) . "'
                     AND `created_at` >= '$periodStart'
                     AND `created_at` <= '$periodEnd 23:59:59'
                     AND `status` != 'failed'";
        $usageResult = mysqli_query($this->conn, $usageSql);
        $ordersUsed = $usageResult ? intval(mysqli_fetch_assoc($usageResult)['cnt']) : 0;

        $ordersLimit = intval($subscription['orders_limit'] ?? 0);
        $overageOrders = max(0, $ordersUsed - $ordersLimit);
        $overageRate = 0;
        $overageAmount = $overageOrders * $overageRate;
        $totalAmount = $baseAmount + $overageAmount;

        $invoiceNumber = 'API-' . date('Ym') . '-' . str_pad((string)$companyId, 5, '0', STR_PAD_LEFT);

        $data = [
            'company_id' => $companyId,
            'subscription_id' => intval($subscription['id'] ?? 0),
            'invoice_number' => $invoiceNumber,
            'plan' => $plan,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'orders_limit' => $ordersLimit,
            'orders_used' => $ordersUsed,
            'overage_orders' => $overageOrders,
            'base_amount' => $baseAmount,
            'overage_amount' => $overageAmount,
            'total_amount' => $totalAmount,
            'currency' => 'THB',
            'status' => 'issued',
            'issued_at' => date('Y-m-d H:i:s'),
            'due_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ];

        // Upsert behavior by period
        $existing = $this->findByPeriod($companyId, $periodStart, $periodEnd);
        if ($existing) {
            $this->hard->updateSafe($this->table, $data, ['id' => intval($existing['id'])]);
            return intval($existing['id']);
        }

        return $this->hard->insertSafe($this->table, $data);
    }

    private function findByPeriod(int $companyId, string $periodStart, string $periodEnd): ?array
    {
        $cid = \sql_int($companyId);
        $start = \sql_escape($periodStart);
        $end = \sql_escape($periodEnd);

        $sql = "SELECT * FROM `{$this->table}`
                WHERE `company_id` = '$cid'
                AND `period_start` = '$start'
                AND `period_end` = '$end'
                LIMIT 1";

        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        return null;
    }
}
