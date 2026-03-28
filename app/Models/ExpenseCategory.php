<?php
namespace App\Models;

/**
 * ExpenseCategory Model — Expense Category Management
 * 
 * @package App\Models
 * @version 1.0.0 — Q3 2026
 */
class ExpenseCategory extends BaseModel
{
    protected string $table = 'expense_categories';
    protected bool $useCompanyFilter = true;

    /**
     * Get active categories for dropdown/form select
     */
    public function getActiveCategories(): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT * FROM expense_categories 
                WHERE com_id = {$comId} AND is_active = 1 AND deleted_at IS NULL
                ORDER BY sort_order, name";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    /**
     * Get all categories including inactive (for admin)
     */
    public function getAllCategories(): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT ec.*, 
                    (SELECT COUNT(*) FROM expenses e WHERE e.category_id = ec.id AND e.deleted_at IS NULL) AS expense_count
                FROM expense_categories ec
                WHERE ec.com_id = {$comId} AND ec.deleted_at IS NULL
                ORDER BY ec.sort_order, ec.name";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    /**
     * Toggle active status
     */
    public function toggleActive(int $id): bool
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "UPDATE expense_categories SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() 
                WHERE id = {$id} AND com_id = {$comId}";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Check if code already exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $code = sql_escape($code);
        $exclude = $excludeId ? " AND id != {$excludeId}" : '';
        $sql = "SELECT COUNT(*) AS cnt FROM expense_categories 
                WHERE com_id = {$comId} AND code = '{$code}' AND deleted_at IS NULL{$exclude}";
        $result = mysqli_query($this->conn, $sql);
        return $result && (int) mysqli_fetch_assoc($result)['cnt'] > 0;
    }
}
