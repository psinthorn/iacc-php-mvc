<?php
namespace App\Models;

/**
 * JournalVoucher Model — Double-Entry Journal Voucher Management
 * 
 * Manages journal vouchers and their entries (debit/credit lines).
 * Supports general, payment, receipt, adjustment, opening, and closing vouchers.
 * 
 * @package App\Models
 * @version 1.0.0 — Q3 2026
 */
class JournalVoucher extends BaseModel
{
    protected string $table = 'journal_vouchers';
    protected bool $useCompanyFilter = true;

    /**
     * Generate next JV number: JV-YYYYMM-XXXX
     */
    public function generateJvNumber(): string
    {
        $prefix = 'JV-' . date('Ym') . '-';
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT jv_number FROM journal_vouchers 
                WHERE jv_number LIKE '{$prefix}%' AND com_id = {$comId}
                ORDER BY jv_number DESC LIMIT 1";
        $result = mysqli_query($this->conn, $sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $lastNum = (int) substr($row['jv_number'], -4);
            return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    /**
     * Get journal vouchers with filters
     */
    public function getJournalVouchers(array $filters = []): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["jv.com_id = {$comId}", "jv.deleted_at IS NULL"];

        if (!empty($filters['status'])) {
            $status = sql_escape($filters['status']);
            $where[] = "jv.status = '{$status}'";
        }
        if (!empty($filters['voucher_type'])) {
            $type = sql_escape($filters['voucher_type']);
            $where[] = "jv.voucher_type = '{$type}'";
        }
        if (!empty($filters['date_from'])) {
            $from = sql_escape($filters['date_from']);
            $where[] = "jv.transaction_date >= '{$from}'";
        }
        if (!empty($filters['date_to'])) {
            $to = sql_escape($filters['date_to']);
            $where[] = "jv.transaction_date <= '{$to}'";
        }
        if (!empty($filters['search'])) {
            $s = sql_escape($filters['search']);
            $where[] = "(jv.jv_number LIKE '%{$s}%' OR jv.description LIKE '%{$s}%' OR jv.reference LIKE '%{$s}%')";
        }

        $whereStr = implode(' AND ', $where);
        $orderBy = $filters['order_by'] ?? 'jv.transaction_date DESC, jv.id DESC';
        $limit = '';
        if (!empty($filters['limit'])) {
            $offset = (int) ($filters['offset'] ?? 0);
            $limit = "LIMIT {$offset}, " . (int) $filters['limit'];
        }

        $sql = "SELECT jv.*, 
                    u.name as created_by_name
                FROM journal_vouchers jv
                LEFT JOIN authorize u ON jv.created_by = u.id
                WHERE {$whereStr}
                ORDER BY {$orderBy}
                {$limit}";
        $result = mysqli_query($this->conn, $sql);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    /**
     * Count journal vouchers with filters
     */
    public function countJournalVouchers(array $filters = []): int
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $where = ["com_id = {$comId}", "deleted_at IS NULL"];

        if (!empty($filters['status'])) {
            $status = sql_escape($filters['status']);
            $where[] = "status = '{$status}'";
        }
        if (!empty($filters['voucher_type'])) {
            $type = sql_escape($filters['voucher_type']);
            $where[] = "voucher_type = '{$type}'";
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as cnt FROM journal_vouchers WHERE {$whereStr}";
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : ['cnt' => 0];
        return (int) $row['cnt'];
    }

    /**
     * Get a single journal voucher with its entries
     */
    public function getJournalVoucher(int $id): ?array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT jv.*, 
                    u.name as created_by_name,
                    pu.name as posted_by_name,
                    cu.name as cancelled_by_name
                FROM journal_vouchers jv
                LEFT JOIN authorize u ON jv.created_by = u.id
                LEFT JOIN authorize pu ON jv.posted_by = pu.id
                LEFT JOIN authorize cu ON jv.cancelled_by = cu.id
                WHERE jv.id = {$id} AND jv.com_id = {$comId} AND jv.deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        $voucher = $result ? mysqli_fetch_assoc($result) : null;
        if (!$voucher) return null;

        // Get entries with account info
        $voucher['entries'] = $this->getEntries($id);
        return $voucher;
    }

    /**
     * Get entries for a journal voucher
     */
    public function getEntries(int $journalVoucherId): array
    {
        $sql = "SELECT je.*, 
                    coa.account_code, coa.account_name, coa.account_name_th, coa.account_type
                FROM journal_entries je
                JOIN chart_of_accounts coa ON je.account_id = coa.id
                WHERE je.journal_voucher_id = {$journalVoucherId}
                ORDER BY je.sort_order ASC, je.id ASC";
        $result = mysqli_query($this->conn, $sql);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    /**
     * Create journal voucher with entries (transaction)
     */
    public function createWithEntries(array $header, array $entries): int|false
    {
        $conn = $this->conn;
        mysqli_begin_transaction($conn);

        try {
            // Calculate totals
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($entries as $entry) {
                $totalDebit += (float) ($entry['debit'] ?? 0);
                $totalCredit += (float) ($entry['credit'] ?? 0);
            }

            // Validate: debits must equal credits
            if (abs($totalDebit - $totalCredit) > 0.01) {
                return false;
            }

            // Insert header
            $comId = (int) ($_SESSION['com_id'] ?? 0);
            $jvNumber = sql_escape($header['jv_number']);
            $voucherType = sql_escape($header['voucher_type'] ?? 'general');
            $transDate = sql_escape($header['transaction_date']);
            $description = sql_escape($header['description'] ?? '');
            $reference = sql_escape($header['reference'] ?? '');
            $refType = !empty($header['reference_type']) ? "'" . sql_escape($header['reference_type']) . "'" : 'NULL';
            $refId = !empty($header['reference_id']) ? (int) $header['reference_id'] : 'NULL';
            $createdBy = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

            $sql = "INSERT INTO journal_vouchers 
                    (com_id, jv_number, voucher_type, transaction_date, description, reference, reference_type, reference_id, total_debit, total_credit, status, created_by)
                    VALUES ({$comId}, '{$jvNumber}', '{$voucherType}', '{$transDate}', '{$description}', '{$reference}', {$refType}, {$refId}, {$totalDebit}, {$totalCredit}, 'draft', {$createdBy})";
            
            if (!mysqli_query($conn, $sql)) {
                throw new \Exception('Failed to insert journal voucher: ' . mysqli_error($conn));
            }
            $jvId = mysqli_insert_id($conn);

            // Insert entries
            foreach ($entries as $i => $entry) {
                $accountId = (int) $entry['account_id'];
                $debit = (float) ($entry['debit'] ?? 0);
                $credit = (float) ($entry['credit'] ?? 0);
                $desc = sql_escape($entry['description'] ?? '');
                $sortOrder = $i + 1;

                $entrySql = "INSERT INTO journal_entries 
                            (journal_voucher_id, account_id, description, debit, credit, sort_order)
                            VALUES ({$jvId}, {$accountId}, '{$desc}', {$debit}, {$credit}, {$sortOrder})";
                if (!mysqli_query($conn, $entrySql)) {
                    throw new \Exception('Failed to insert journal entry: ' . mysqli_error($conn));
                }
            }

            mysqli_commit($conn);
            return $jvId;

        } catch (\Exception $e) {
            mysqli_rollback($conn);
            error_log("JournalVoucher::createWithEntries error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update journal voucher with entries (only if draft)
     */
    public function updateWithEntries(int $id, array $header, array $entries): bool
    {
        $conn = $this->conn;
        $existing = $this->getJournalVoucher($id);
        if (!$existing || $existing['status'] !== 'draft') return false;

        mysqli_begin_transaction($conn);

        try {
            // Calculate totals
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($entries as $entry) {
                $totalDebit += (float) ($entry['debit'] ?? 0);
                $totalCredit += (float) ($entry['credit'] ?? 0);
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                return false;
            }

            // Update header
            $voucherType = sql_escape($header['voucher_type'] ?? $existing['voucher_type']);
            $transDate = sql_escape($header['transaction_date'] ?? $existing['transaction_date']);
            $description = sql_escape($header['description'] ?? '');
            $reference = sql_escape($header['reference'] ?? '');
            $refType = !empty($header['reference_type']) ? "'" . sql_escape($header['reference_type']) . "'" : 'NULL';
            $refId = !empty($header['reference_id']) ? (int) $header['reference_id'] : 'NULL';

            $sql = "UPDATE journal_vouchers SET 
                    voucher_type = '{$voucherType}',
                    transaction_date = '{$transDate}',
                    description = '{$description}',
                    reference = '{$reference}',
                    reference_type = {$refType},
                    reference_id = {$refId},
                    total_debit = {$totalDebit},
                    total_credit = {$totalCredit}
                    WHERE id = {$id}";
            
            if (!mysqli_query($conn, $sql)) {
                throw new \Exception('Failed to update journal voucher');
            }

            // Delete old entries
            mysqli_query($conn, "DELETE FROM journal_entries WHERE journal_voucher_id = {$id}");

            // Insert new entries
            foreach ($entries as $i => $entry) {
                $accountId = (int) $entry['account_id'];
                $debit = (float) ($entry['debit'] ?? 0);
                $credit = (float) ($entry['credit'] ?? 0);
                $desc = sql_escape($entry['description'] ?? '');
                $sortOrder = $i + 1;

                $entrySql = "INSERT INTO journal_entries 
                            (journal_voucher_id, account_id, description, debit, credit, sort_order)
                            VALUES ({$id}, {$accountId}, '{$desc}', {$debit}, {$credit}, {$sortOrder})";
                if (!mysqli_query($conn, $entrySql)) {
                    throw new \Exception('Failed to insert journal entry');
                }
            }

            mysqli_commit($conn);
            return true;

        } catch (\Exception $e) {
            mysqli_rollback($conn);
            error_log("JournalVoucher::updateWithEntries error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Post a journal voucher (draft → posted)
     */
    public function post(int $id): bool
    {
        $voucher = $this->getJournalVoucher($id);
        if (!$voucher || $voucher['status'] !== 'draft') return false;

        // Verify balanced
        if (abs((float) $voucher['total_debit'] - (float) $voucher['total_credit']) > 0.01) return false;

        // Must have at least 2 entries
        if (count($voucher['entries']) < 2) return false;

        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
        $sql = "UPDATE journal_vouchers SET 
                status = 'posted', posted_at = NOW(), posted_by = {$userId}
                WHERE id = {$id}";
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Cancel a journal voucher
     */
    public function cancel(int $id, string $reason = ''): bool
    {
        $voucher = $this->getJournalVoucher($id);
        if (!$voucher || $voucher['status'] === 'cancelled') return false;

        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
        $reason = sql_escape($reason);
        $sql = "UPDATE journal_vouchers SET 
                status = 'cancelled', cancelled_at = NOW(), cancelled_by = {$userId}, cancel_reason = '{$reason}'
                WHERE id = {$id}";
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Get summary statistics for dashboard
     */
    public function getStats(): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(CASE WHEN status = 'posted' THEN total_debit ELSE 0 END) as total_posted_amount
                FROM journal_vouchers 
                WHERE com_id = {$comId} AND deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        return $result ? (mysqli_fetch_assoc($result) ?: []) : [];
    }
}
