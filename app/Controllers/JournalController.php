<?php
namespace App\Controllers;

use App\Models\JournalVoucher;
use App\Models\ChartOfAccounts;

/**
 * JournalController — Journal Voucher & Chart of Accounts Management
 * 
 * Handles CRUD operations for journal vouchers (double-entry bookkeeping)
 * and chart of accounts management.
 * 
 * @package App\Controllers
 * @version 1.0.0 — Q3 2026
 */
class JournalController extends BaseController
{
    private JournalVoucher $journalModel;
    private ChartOfAccounts $coaModel;

    public function __construct()
    {
        parent::__construct();
        $this->journalModel = new JournalVoucher($this->conn);
        $this->coaModel = new ChartOfAccounts($this->conn);

        // Auto-initialize COA from template if needed
        if (!$this->coaModel->hasAccounts()) {
            $this->coaModel->initializeFromTemplate();
        }
    }

    /**
     * Journal Voucher List — with filters & pagination
     */
    public function index(): void
    {
        $filters = [
            'search' => $this->inputStr('search'),
            'status' => $this->inputStr('status'),
            'voucher_type' => $this->inputStr('voucher_type'),
            'date_from' => $this->inputStr('date_from'),
            'date_to' => $this->inputStr('date_to'),
        ];

        $page = max(1, $this->inputInt('p', 1));
        $perPage = 20;
        $filters['limit'] = $perPage;
        $filters['offset'] = ($page - 1) * $perPage;

        $vouchers = $this->journalModel->getJournalVouchers($filters);
        $totalCount = $this->journalModel->countJournalVouchers($filters);
        $stats = $this->journalModel->getStats();

        $this->render('journal/list', [
            'vouchers' => $vouchers,
            'filters' => $filters,
            'stats' => $stats,
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => ceil($totalCount / $perPage),
        ]);
    }

    /**
     * Journal Voucher Create/Edit Form
     */
    public function form(): void
    {
        $id = $this->inputInt('id');
        $voucher = null;

        if ($id > 0) {
            $voucher = $this->journalModel->getJournalVoucher($id);
            if (!$voucher) {
                $this->redirect('journal_list');
                return;
            }
        }

        $accounts = $this->coaModel->getAccounts(['is_active' => true]);
        $accountsByType = $this->coaModel->getAccountsByType();
        $jvNumber = $voucher ? $voucher['jv_number'] : $this->journalModel->generateJvNumber();

        $this->render('journal/form', [
            'voucher' => $voucher,
            'accounts' => $accounts,
            'accountsByType' => $accountsByType,
            'jvNumber' => $jvNumber,
            'isEdit' => $id > 0,
        ]);
    }

    /**
     * Store (create/update) journal voucher — POST handler
     */
    public function store(): void
    {
        $this->verifyCsrf();

        $id = $this->inputInt('id');
        $header = [
            'jv_number' => $this->inputStr('jv_number'),
            'voucher_type' => $this->inputStr('voucher_type', 'general'),
            'transaction_date' => $this->inputStr('transaction_date'),
            'description' => $this->inputStr('description'),
            'reference' => $this->inputStr('reference'),
            'reference_type' => $this->inputStr('reference_type') ?: null,
            'reference_id' => $this->inputInt('reference_id') ?: null,
        ];

        // Parse entries from form arrays
        $accountIds = $_POST['account_id'] ?? [];
        $debits = $_POST['debit'] ?? [];
        $credits = $_POST['credit'] ?? [];
        $descriptions = $_POST['entry_description'] ?? [];

        $entries = [];
        foreach ($accountIds as $i => $accountId) {
            $debit = (float) ($debits[$i] ?? 0);
            $credit = (float) ($credits[$i] ?? 0);
            if ((int) $accountId > 0 && ($debit > 0 || $credit > 0)) {
                $entries[] = [
                    'account_id' => (int) $accountId,
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $descriptions[$i] ?? '',
                ];
            }
        }

        // Validate
        if (empty($header['transaction_date']) || count($entries) < 2) {
            $this->redirect('journal_form', ['id' => $id, 'error' => 'missing_fields']);
            return;
        }

        // Check balance
        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));
        if (abs($totalDebit - $totalCredit) > 0.01) {
            $this->redirect('journal_form', ['id' => $id, 'error' => 'unbalanced']);
            return;
        }

        if ($id > 0) {
            $success = $this->journalModel->updateWithEntries($id, $header, $entries);
        } else {
            $result = $this->journalModel->createWithEntries($header, $entries);
            $success = $result !== false;
            if ($success) $id = $result;
        }

        if ($success) {
            $this->redirect('journal_view', ['id' => $id]);
        } else {
            $this->redirect('journal_form', ['id' => $id, 'error' => 'save_failed']);
        }
    }

    /**
     * View journal voucher detail
     */
    public function view(): void
    {
        $id = $this->inputInt('id');
        $voucher = $this->journalModel->getJournalVoucher($id);

        if (!$voucher) {
            $this->redirect('journal_list');
            return;
        }

        $this->render('journal/view', [
            'voucher' => $voucher,
        ]);
    }

    /**
     * Post a journal voucher (draft → posted)
     */
    public function post(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');

        if ($this->journalModel->post($id)) {
            $this->redirect('journal_view', ['id' => $id, 'success' => 'posted']);
        } else {
            $this->redirect('journal_view', ['id' => $id, 'error' => 'post_failed']);
        }
    }

    /**
     * Cancel a journal voucher
     */
    public function cancelVoucher(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $reason = $this->inputStr('cancel_reason');

        if ($this->journalModel->cancel($id, $reason)) {
            $this->redirect('journal_view', ['id' => $id, 'success' => 'cancelled']);
        } else {
            $this->redirect('journal_view', ['id' => $id, 'error' => 'cancel_failed']);
        }
    }

    /**
     * Delete (soft delete) a journal voucher — only drafts
     */
    public function delete(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $voucher = $this->journalModel->getJournalVoucher($id);

        if ($voucher && $voucher['status'] === 'draft') {
            $this->journalModel->softDelete($id);
        }
        $this->redirect('journal_list');
    }

    // =============================================
    // Chart of Accounts
    // =============================================

    /**
     * Chart of Accounts list
     */
    public function accounts(): void
    {
        $filters = [
            'search' => $this->inputStr('search'),
            'account_type' => $this->inputStr('account_type'),
        ];

        $accounts = $this->coaModel->getAccounts($filters);
        $trialBalance = $this->coaModel->getTrialBalance();

        $this->render('journal/accounts', [
            'accounts' => $accounts,
            'trialBalance' => $trialBalance,
            'filters' => $filters,
        ]);
    }

    /**
     * Store (create/update) chart of accounts entry
     */
    public function accountStore(): void
    {
        $this->verifyCsrf();

        $id = $this->inputInt('id');
        $data = [
            'account_code' => $this->inputStr('account_code'),
            'account_name' => $this->inputStr('account_name'),
            'account_name_th' => $this->inputStr('account_name_th'),
            'account_type' => $this->inputStr('account_type'),
            'level' => $this->inputInt('level', 2),
            'normal_balance' => $this->inputStr('normal_balance', 'debit'),
            'description' => $this->inputStr('description'),
            'is_active' => $this->inputInt('is_active', 1),
        ];

        // Validate
        if (empty($data['account_code']) || empty($data['account_name']) || empty($data['account_type'])) {
            $this->redirect('journal_accounts', ['error' => 'missing_fields']);
            return;
        }

        // Auto-determine normal balance from account type
        $creditTypes = ['liability', 'equity', 'revenue'];
        $data['normal_balance'] = in_array($data['account_type'], $creditTypes) ? 'credit' : 'debit';

        // Check duplicate code
        if ($this->coaModel->codeExists($data['account_code'], $id > 0 ? $id : null)) {
            $this->redirect('journal_accounts', ['error' => 'duplicate_code']);
            return;
        }

        if ($id > 0) {
            $this->coaModel->update($id, $data);
        } else {
            $this->coaModel->create($data);
        }

        $this->redirect('journal_accounts', ['success' => '1']);
    }

    /**
     * Toggle account active/inactive
     */
    public function accountToggle(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $account = $this->coaModel->getAccount($id);

        if ($account) {
            $this->coaModel->update($id, ['is_active' => $account['is_active'] ? 0 : 1]);
        }

        $this->redirect('journal_accounts');
    }

    /**
     * Trial Balance report
     */
    public function trialBalance(): void
    {
        $trialBalance = $this->coaModel->getTrialBalance();
        
        $this->render('journal/trial-balance', [
            'trialBalance' => $trialBalance,
        ]);
    }
}
