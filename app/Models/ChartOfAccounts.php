<?php
namespace App\Models;

/**
 * ChartOfAccounts Model — Chart of Accounts Management
 * 
 * Manages the chart of accounts for double-entry bookkeeping.
 * Supports template accounts (com_id=0) that can be cloned per company.
 * 
 * @package App\Models
 * @version 1.0.0 — Q3 2026
 */
class ChartOfAccounts extends BaseModel
{
    protected string $table = 'chart_of_accounts';
    protected bool $useCompanyFilter = true;

    /**
     * Get all active accounts for the current company, ordered by code
     */
    public function getAccounts(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["com_id = {$comId}", "deleted_at IS NULL"];

        if (!empty($filters['account_type'])) {
            $type = sql_escape($filters['account_type']);
            $where[] = "account_type = '{$type}'";
        }
        if (!empty($filters['is_active'])) {
            $where[] = "is_active = 1";
        }
        if (!empty($filters['search'])) {
            $s = sql_escape($filters['search']);
            $where[] = "(account_code LIKE '%{$s}%' OR account_name LIKE '%{$s}%' OR account_name_th LIKE '%{$s}%')";
        }
        if (!empty($filters['level'])) {
            $level = (int) $filters['level'];
            $where[] = "level = {$level}";
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT * FROM chart_of_accounts WHERE {$whereStr} ORDER BY account_code ASC";
        $result = mysqli_query($this->conn, $sql);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    /**
     * Get accounts grouped by type for dropdowns
     */
    public function getAccountsByType(): array
    {
        $accounts = $this->getAccounts(['is_active' => true]);
        $grouped = [
            'asset' => [],
            'liability' => [],
            'equity' => [],
            'revenue' => [],
            'expense' => []
        ];
        foreach ($accounts as $acc) {
            $grouped[$acc['account_type']][] = $acc;
        }
        return $grouped;
    }

    /**
     * Check if company has accounts initialized
     */
    public function hasAccounts(): bool
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT COUNT(*) as cnt FROM chart_of_accounts WHERE com_id = {$comId} AND deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : ['cnt' => 0];
        return (int) $row['cnt'] > 0;
    }

    /**
     * Initialize company accounts from template (com_id=0)
     */
    public function initializeFromTemplate(): int
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        if ($comId <= 0 || $this->hasAccounts()) return 0;

        $sql = "INSERT INTO chart_of_accounts 
                (com_id, account_code, account_name, account_name_th, account_type, parent_id, level, normal_balance, description, is_active)
                SELECT {$comId}, account_code, account_name, account_name_th, account_type, parent_id, level, normal_balance, description, is_active
                FROM chart_of_accounts WHERE com_id = 0 AND deleted_at IS NULL";
        mysqli_query($this->conn, $sql);
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Get a single account by ID (with company filter)
     */
    public function getAccount(int $id): ?array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT * FROM chart_of_accounts WHERE id = {$id} AND com_id = {$comId} AND deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        return $result ? (mysqli_fetch_assoc($result) ?: null) : null;
    }

    /**
     * Check if account code already exists for this company
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $code = sql_escape($code);
        $sql = "SELECT id FROM chart_of_accounts WHERE com_id = {$comId} AND account_code = '{$code}' AND deleted_at IS NULL";
        if ($excludeId) {
            $sql .= " AND id != {$excludeId}";
        }
        $result = mysqli_query($this->conn, $sql);
        return $result && mysqli_num_rows($result) > 0;
    }

    /**
     * Get account balance from journal entries
     */
    public function getAccountBalance(int $accountId): float
    {
        $sql = "SELECT 
                    COALESCE(SUM(je.debit), 0) as total_debit,
                    COALESCE(SUM(je.credit), 0) as total_credit
                FROM journal_entries je
                JOIN journal_vouchers jv ON je.journal_voucher_id = jv.id
                WHERE je.account_id = {$accountId} 
                AND jv.status = 'posted'
                AND jv.deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        if (!$result) return 0.0;
        $row = mysqli_fetch_assoc($result);

        // Get account's normal balance side
        $account = $this->getAccount($accountId);
        if (!$account) return 0.0;

        if ($account['normal_balance'] === 'debit') {
            return (float) $row['total_debit'] - (float) $row['total_credit'];
        }
        return (float) $row['total_credit'] - (float) $row['total_debit'];
    }

    /**
     * Get trial balance — all accounts with debit/credit totals
     */
    public function getTrialBalance(): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT 
                    coa.id, coa.account_code, coa.account_name, coa.account_name_th,
                    coa.account_type, coa.normal_balance, coa.level,
                    COALESCE(SUM(je.debit), 0) as total_debit,
                    COALESCE(SUM(je.credit), 0) as total_credit
                FROM chart_of_accounts coa
                LEFT JOIN journal_entries je ON coa.id = je.account_id
                LEFT JOIN journal_vouchers jv ON je.journal_voucher_id = jv.id AND jv.status = 'posted' AND jv.deleted_at IS NULL
                WHERE coa.com_id = {$comId} AND coa.deleted_at IS NULL AND coa.is_active = 1
                GROUP BY coa.id, coa.account_code, coa.account_name, coa.account_name_th,
                         coa.account_type, coa.normal_balance, coa.level
                ORDER BY coa.account_code ASC";
        $result = mysqli_query($this->conn, $sql);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }
}
