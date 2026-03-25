<?php
namespace App\Models;

/**
 * PaymentMethod Model
 * 
 * Manages the `payment_method` table with company-based multi-tenancy.
 * Table columns: id, company_id, code, name, name_th, icon, description,
 *                is_gateway, is_active, sort_order, created_at, updated_at
 */
class PaymentMethod extends BaseModel
{
    protected string $table = 'payment_method';
    protected bool $useCompanyFilter = true;

    /**
     * Get all payment methods with optional filters
     */
    public function getFiltered(string $search = '', string $type = '', string $status = ''): array
    {
        $companyId = intval($_SESSION['com_id'] ?? 0);
        $companyCondition = $companyId > 0 
            ? "company_id = '" . \sql_int($companyId) . "'" 
            : '1=1';

        $where = "WHERE $companyCondition";

        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $where .= " AND (code LIKE '%$escaped%' OR name LIKE '%$escaped%' OR name_th LIKE '%$escaped%')";
        }
        if ($type !== '') {
            $where .= " AND is_gateway = '" . \sql_int($type) . "'";
        }
        if ($status !== '') {
            $where .= " AND is_active = '" . \sql_int($status) . "'";
        }

        $sql = "SELECT * FROM `{$this->table}` $where ORDER BY sort_order ASC, id ASC";
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }
        return $items;
    }

    /**
     * Get statistics (total, active, inactive, gateway counts)
     */
    public function getStats(): array
    {
        $companyId = intval($_SESSION['com_id'] ?? 0);
        $companyCondition = $companyId > 0 
            ? "company_id = '" . \sql_int($companyId) . "'" 
            : '1=1';

        $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'gateway' => 0];

        $queries = [
            'total'    => "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE $companyCondition",
            'active'   => "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE $companyCondition AND is_active = 1",
            'inactive' => "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE $companyCondition AND is_active = 0",
            'gateway'  => "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE $companyCondition AND is_gateway = 1",
        ];

        foreach ($queries as $key => $sql) {
            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $stats[$key] = intval(mysqli_fetch_assoc($result)['cnt']);
            }
        }
        return $stats;
    }

    /**
     * Toggle active status for a payment method
     */
    public function toggleActive(int $id): bool
    {
        $companyId = intval($_SESSION['com_id'] ?? 0);
        $companyCondition = $companyId > 0 
            ? " AND company_id = '" . \sql_int($companyId) . "'" 
            : '';

        $sql = "UPDATE `{$this->table}` SET is_active = NOT is_active 
                WHERE id = '" . \sql_int($id) . "' $companyCondition";
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Get next sort order value
     */
    public function getNextSortOrder(): int
    {
        $companyId = intval($_SESSION['com_id'] ?? 0);
        $companyCondition = $companyId > 0 
            ? "WHERE company_id = '" . \sql_int($companyId) . "'" 
            : '';

        $sql = "SELECT MAX(sort_order) as max_sort FROM `{$this->table}` $companyCondition";
        $result = mysqli_query($this->conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return intval($row['max_sort'] ?? 0) + 1;
        }
        return 1;
    }
}
