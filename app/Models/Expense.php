<?php
namespace App\Models;

/**
 * Expense Model — Expense Tracking & Reporting
 * 
 * Manages company expenses with category linking, tax handling,
 * vendor tracking, and PO/PR references.
 * 
 * @package App\Models
 * @version 1.0.0 — Q3 2026
 */
class Expense extends BaseModel
{
    protected string $table = 'expenses';
    protected bool $useCompanyFilter = true;
    protected string $companyColumn = 'com_id';

    /**
     * Generate next expense number: EXP-YYYYMM-XXXX
     */
    public function generateExpenseNumber(): string
    {
        $prefix = 'EXP-' . date('Ym') . '-';
        $sql = "SELECT expense_number FROM expenses 
                WHERE expense_number LIKE '{$prefix}%' 
                ORDER BY expense_number DESC LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $lastNum = (int) substr($row['expense_number'], -4);
            return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    /**
     * Get expenses with category info, with filters
     */
    public function getExpenses(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["e.com_id = {$comId}", "e.deleted_at IS NULL"];

        if (!empty($filters['status'])) {
            $status = sql_escape($filters['status']);
            $where[] = "e.status = '{$status}'";
        }
        if (!empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $where[] = "e.category_id = {$catId}";
        }
        if (!empty($filters['date_from'])) {
            $from = sql_escape($filters['date_from']);
            $where[] = "e.expense_date >= '{$from}'";
        }
        if (!empty($filters['date_to'])) {
            $to = sql_escape($filters['date_to']);
            $where[] = "e.expense_date <= '{$to}'";
        }
        if (!empty($filters['search'])) {
            $s = sql_escape($filters['search']);
            $where[] = "(e.title LIKE '%{$s}%' OR e.expense_number LIKE '%{$s}%' OR e.vendor_name LIKE '%{$s}%' OR e.reference_no LIKE '%{$s}%')";
        }
        if (!empty($filters['vendor_name'])) {
            $v = sql_escape($filters['vendor_name']);
            $where[] = "e.vendor_name LIKE '%{$v}%'";
        }
        if (!empty($filters['project_name'])) {
            $p = sql_escape($filters['project_name']);
            $where[] = "e.project_name LIKE '%{$p}%'";
        }

        $whereStr = implode(' AND ', $where);
        $orderBy = $filters['order_by'] ?? 'e.expense_date DESC, e.id DESC';
        $limit = '';
        if (!empty($filters['limit'])) {
            $offset = (int) ($filters['offset'] ?? 0);
            $limit = "LIMIT {$offset}, " . (int) $filters['limit'];
        }

        $sql = "SELECT e.*, 
                    ec.name AS category_name, ec.name_th AS category_name_th,
                    ec.icon AS category_icon, ec.color AS category_color,
                    ec.code AS category_code
                FROM expenses e
                LEFT JOIN expense_categories ec ON ec.id = e.category_id
                WHERE {$whereStr}
                ORDER BY {$orderBy}
                {$limit}";

        $result = mysqli_query($this->conn, $sql);
        $expenses = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $expenses[] = $row;
            }
        }
        return $expenses;
    }

    /**
     * Count expenses with same filters (for pagination)
     */
    public function countExpenses(array $filters = []): int
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["e.com_id = {$comId}", "e.deleted_at IS NULL"];

        if (!empty($filters['status'])) {
            $where[] = "e.status = '" . sql_escape($filters['status']) . "'";
        }
        if (!empty($filters['category_id'])) {
            $where[] = "e.category_id = " . (int) $filters['category_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "e.expense_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "e.expense_date <= '" . sql_escape($filters['date_to']) . "'";
        }
        if (!empty($filters['search'])) {
            $s = sql_escape($filters['search']);
            $where[] = "(e.title LIKE '%{$s}%' OR e.expense_number LIKE '%{$s}%' OR e.vendor_name LIKE '%{$s}%')";
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) AS cnt FROM expenses e WHERE {$whereStr}";
        $result = mysqli_query($this->conn, $sql);
        return $result ? (int) mysqli_fetch_assoc($result)['cnt'] : 0;
    }

    /**
     * Get single expense with category info
     */
    public function getExpenseDetail(int $id): ?array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT e.*, 
                    ec.name AS category_name, ec.name_th AS category_name_th,
                    ec.icon AS category_icon, ec.color AS category_color
                FROM expenses e
                LEFT JOIN expense_categories ec ON ec.id = e.category_id
                WHERE e.id = {$id} AND e.com_id = {$comId} AND e.deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        return $result ? (mysqli_fetch_assoc($result) ?: null) : null;
    }

    /**
     * Get summary stats for dashboard
     */
    public function getSummary(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["com_id = {$comId}", "deleted_at IS NULL"];

        if (!empty($filters['date_from'])) {
            $where[] = "expense_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "expense_date <= '" . sql_escape($filters['date_to']) . "'";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT 
                    COUNT(*) AS total_count,
                    COALESCE(SUM(amount), 0) AS total_amount,
                    COALESCE(SUM(vat_amount), 0) AS total_vat,
                    COALESCE(SUM(wht_amount), 0) AS total_wht,
                    COALESCE(SUM(net_amount), 0) AS total_net,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END) AS paid_amount
                FROM expenses
                WHERE {$whereStr}";

        $result = mysqli_query($this->conn, $sql);
        return $result ? (mysqli_fetch_assoc($result) ?: []) : [];
    }

    /**
     * Get expenses grouped by category for report
     */
    public function getByCategory(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["e.com_id = {$comId}", "e.deleted_at IS NULL"];

        if (!empty($filters['date_from'])) {
            $where[] = "e.expense_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "e.expense_date <= '" . sql_escape($filters['date_to']) . "'";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT ec.id, ec.name, ec.name_th, ec.icon, ec.color, ec.code,
                    COUNT(e.id) AS expense_count,
                    COALESCE(SUM(e.net_amount), 0) AS total_amount
                FROM expense_categories ec
                LEFT JOIN expenses e ON e.category_id = ec.id AND {$whereStr}
                WHERE ec.com_id = {$comId} AND ec.deleted_at IS NULL AND ec.is_active = 1
                GROUP BY ec.id
                ORDER BY total_amount DESC";

        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Get monthly expense totals for chart
     */
    public function getMonthlyTotals(int $year): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT MONTH(expense_date) AS month,
                    COUNT(*) AS count,
                    COALESCE(SUM(net_amount), 0) AS total
                FROM expenses
                WHERE com_id = {$comId} AND YEAR(expense_date) = {$year} AND deleted_at IS NULL
                GROUP BY MONTH(expense_date)
                ORDER BY month";

        $result = mysqli_query($this->conn, $sql);
        $data = array_fill(1, 12, ['count' => 0, 'total' => 0]);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[(int)$row['month']] = ['count' => (int)$row['count'], 'total' => (float)$row['total']];
            }
        }
        return $data;
    }

    /**
     * Update expense status
     */
    public function updateStatus(int $id, string $status, ?int $approvedBy = null): bool
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $status = sql_escape($status);
        
        $extra = '';
        if ($status === 'approved' && $approvedBy) {
            $extra = ", approved_by = {$approvedBy}, approved_at = NOW()";
        }
        if ($status === 'paid') {
            $extra .= ", paid_date = CURDATE()";
        }

        $sql = "UPDATE expenses SET status = '{$status}'{$extra}, updated_at = NOW() 
                WHERE id = {$id} AND com_id = {$comId}";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Get distinct vendor names for autocomplete
     */
    public function getVendorNames(): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT DISTINCT vendor_name FROM expenses 
                WHERE com_id = {$comId} AND vendor_name IS NOT NULL AND vendor_name != '' AND deleted_at IS NULL
                ORDER BY vendor_name LIMIT 100";
        $result = mysqli_query($this->conn, $sql);
        $names = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $names[] = $row['vendor_name'];
            }
        }
        return $names;
    }

    /**
     * Get distinct project names for autocomplete
     */
    public function getProjectNames(): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT DISTINCT project_name FROM expenses 
                WHERE com_id = {$comId} AND project_name IS NOT NULL AND project_name != '' AND deleted_at IS NULL
                ORDER BY project_name LIMIT 100";
        $result = mysqli_query($this->conn, $sql);
        $names = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $names[] = $row['project_name'];
            }
        }
        return $names;
    }

    /**
     * Get expenses grouped by project for cost report
     */
    public function getByProject(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["e.com_id = {$comId}", "e.deleted_at IS NULL", "e.project_name IS NOT NULL", "e.project_name != ''"];

        if (!empty($filters['date_from'])) {
            $where[] = "e.expense_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "e.expense_date <= '" . sql_escape($filters['date_to']) . "'";
        }
        if (!empty($filters['status'])) {
            $where[] = "e.status = '" . sql_escape($filters['status']) . "'";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT e.project_name,
                    COUNT(*) AS expense_count,
                    COALESCE(SUM(e.amount), 0) AS total_amount,
                    COALESCE(SUM(e.vat_amount), 0) AS total_vat,
                    COALESCE(SUM(e.wht_amount), 0) AS total_wht,
                    COALESCE(SUM(e.net_amount), 0) AS total_net,
                    MIN(e.expense_date) AS first_expense,
                    MAX(e.expense_date) AS last_expense,
                    SUM(CASE WHEN e.status = 'paid' THEN e.net_amount ELSE 0 END) AS paid_amount,
                    SUM(CASE WHEN e.status = 'pending' OR e.status = 'approved' THEN e.net_amount ELSE 0 END) AS unpaid_amount
                FROM expenses e
                WHERE {$whereStr}
                GROUP BY e.project_name
                ORDER BY total_net DESC";

        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Get expense detail rows for a specific project
     */
    public function getProjectExpenses(string $projectName, array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $pn = sql_escape($projectName);
        $where = ["e.com_id = {$comId}", "e.deleted_at IS NULL", "e.project_name = '{$pn}'"];

        if (!empty($filters['date_from'])) {
            $where[] = "e.expense_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "e.expense_date <= '" . sql_escape($filters['date_to']) . "'";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT e.*, 
                    ec.name AS category_name, ec.name_th AS category_name_th,
                    ec.icon AS category_icon, ec.color AS category_color
                FROM expenses e
                LEFT JOIN expense_categories ec ON ec.id = e.category_id
                WHERE {$whereStr}
                ORDER BY e.expense_date DESC";

        $result = mysqli_query($this->conn, $sql);
        $expenses = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $expenses[] = $row;
            }
        }
        return $expenses;
    }

    /**
     * Get all expenses for CSV/JSON export (with filters)
     */
    public function getExpensesForExport(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["e.com_id = {$comId}", "e.deleted_at IS NULL"];

        if (!empty($filters['status'])) {
            $where[] = "e.status = '" . sql_escape($filters['status']) . "'";
        }
        if (!empty($filters['category_id'])) {
            $where[] = "e.category_id = " . (int) $filters['category_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "e.expense_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where[] = "e.expense_date <= '" . sql_escape($filters['date_to']) . "'";
        }
        if (!empty($filters['project_name'])) {
            $where[] = "e.project_name = '" . sql_escape($filters['project_name']) . "'";
        }
        if (!empty($filters['vendor_name'])) {
            $where[] = "e.vendor_name LIKE '%" . sql_escape($filters['vendor_name']) . "%'";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT e.expense_number, e.title, e.expense_date, e.status,
                    ec.name AS category_name,
                    e.vendor_name, e.vendor_tax_id, e.project_name,
                    e.amount, e.vat_rate, e.vat_amount, e.wht_rate, e.wht_amount, e.net_amount,
                    e.currency_code, e.payment_method, e.reference_no, e.due_date, e.paid_date,
                    e.description
                FROM expenses e
                LEFT JOIN expense_categories ec ON ec.id = e.category_id
                WHERE {$whereStr}
                ORDER BY e.expense_date DESC, e.id DESC";

        $result = mysqli_query($this->conn, $sql);
        $expenses = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $expenses[] = $row;
            }
        }
        return $expenses;
    }
}
