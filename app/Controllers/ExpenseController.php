<?php
namespace App\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;

/**
 * ExpenseController — Expense Tracking & Management
 * 
 * Full CRUD for company expenses with filtering, status workflow,
 * and summary reporting.
 * 
 * Routes:
 *   expense_list       → index()     — Expense list with filters
 *   expense_form       → form()      — Create/edit form
 *   expense_store      → store()     — POST: save expense
 *   expense_view       → view()      — View expense detail
 *   expense_delete     → delete()    — POST: soft delete
 *   expense_status     → status()    — POST: update status (approve/reject/pay)
 *   expense_summary    → summary()   — Monthly summary / category breakdown
 *   expense_cat_list   → categories()   — Category management
 *   expense_cat_store  → categoryStore() — POST: save category
 *   expense_cat_toggle → categoryToggle() — POST: toggle category active
 *   expense_cat_delete → categoryDelete() — POST: soft delete category
 * 
 * @package App\Controllers
 * @version 1.0.0 — Q3 2026
 */
class ExpenseController extends BaseController
{
    private Expense $model;
    private ExpenseCategory $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Expense();
        $this->categoryModel = new ExpenseCategory();
    }

    /**
     * Expense list with filters
     */
    public function index(): void
    {
        if ($this->user['level'] < 1) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied.</div>';
            return;
        }

        $filters = [
            'status'      => $this->inputStr('status'),
            'category_id' => $this->inputInt('category_id'),
            'date_from'   => $this->inputStr('date_from'),
            'date_to'     => $this->inputStr('date_to'),
            'search'      => $this->inputStr('search'),
        ];

        // Default: current month
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = date('Y-m-01');
            $filters['date_to'] = date('Y-m-t');
        }

        $expenses = $this->model->getExpenses($filters);
        $summary = $this->model->getSummary($filters);
        $categories = $this->categoryModel->getActiveCategories();
        $message = $_GET['msg'] ?? '';
        $lang = $_SESSION['lang'] ?? '2';

        $this->render('expense/list', compact('expenses', 'summary', 'categories', 'filters', 'message', 'lang'));
    }

    /**
     * Create / Edit form
     */
    public function form(): void
    {
        if ($this->user['level'] < 1) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied.</div>';
            return;
        }

        $id = $this->inputInt('id');
        $expense = null;
        if ($id > 0) {
            $expense = $this->model->getExpenseDetail($id);
            if (!$expense) {
                header('Location: index.php?page=expense_list&msg=not_found');
                exit;
            }
        }

        $categories = $this->categoryModel->getActiveCategories();
        $vendors = $this->model->getVendorNames();
        $projects = $this->model->getProjectNames();
        $expenseNumber = $expense ? $expense['expense_number'] : $this->model->generateExpenseNumber();
        $lang = $_SESSION['lang'] ?? '2';
        $message = $_GET['msg'] ?? '';

        $this->render('expense/form', compact('expense', 'categories', 'vendors', 'projects', 'expenseNumber', 'lang', 'message'));
    }

    /**
     * POST: Save expense (create or update)
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=expense_list');
            exit;
        }

        $this->verifyCsrf();

        $id = intval($_POST['id'] ?? 0);
        $comId = $this->getCompanyId();

        // Calculate amounts
        $amount = floatval($_POST['amount'] ?? 0);
        $vatRate = !empty($_POST['vat_rate']) ? floatval($_POST['vat_rate']) : null;
        $vatAmount = $vatRate ? round($amount * $vatRate / 100, 2) : 0;
        $whtRate = !empty($_POST['wht_rate']) ? floatval($_POST['wht_rate']) : null;
        $whtAmount = $whtRate ? round($amount * $whtRate / 100, 2) : 0;
        $netAmount = $amount + $vatAmount - $whtAmount;

        $data = [
            'com_id'         => $comId,
            'expense_number' => trim($_POST['expense_number'] ?? ''),
            'category_id'    => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'title'          => trim($_POST['title'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'amount'         => $amount,
            'vat_rate'       => $vatRate,
            'vat_amount'     => $vatAmount,
            'wht_rate'       => $whtRate,
            'wht_amount'     => $whtAmount,
            'net_amount'     => $netAmount,
            'currency_code'  => trim($_POST['currency_code'] ?? 'THB'),
            'expense_date'   => $_POST['expense_date'] ?? date('Y-m-d'),
            'due_date'       => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'reference_no'   => trim($_POST['reference_no'] ?? ''),
            'vendor_name'    => trim($_POST['vendor_name'] ?? ''),
            'vendor_tax_id'  => trim($_POST['vendor_tax_id'] ?? ''),
            'project_name'   => trim($_POST['project_name'] ?? ''),
            'status'         => $_POST['status'] ?? 'draft',
        ];

        // Handle file upload
        if (!empty($_FILES['receipt_file']['name'])) {
            $uploadDir = 'upload/expense/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
            $filename = 'exp_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $uploadDir . $filename)) {
                $data['receipt_file'] = $uploadDir . $filename;
            }
        }

        if ($id > 0) {
            // Update
            unset($data['com_id'], $data['expense_number']); // Don't change these on update
            $success = $this->model->update($id, $data);
            $msg = $success ? 'updated' : 'error';
        } else {
            // Create
            $data['created_by'] = $this->user['id'] ?? null;
            $newId = $this->model->create($data);
            $msg = $newId ? 'created' : 'error';
            $id = $newId ?: 0;
        }

        header("Location: index.php?page=expense_list&msg={$msg}");
        exit;
    }

    /**
     * View expense detail
     */
    public function view(): void
    {
        $id = $this->inputInt('id');
        $expense = $this->model->getExpenseDetail($id);

        if (!$expense) {
            header('Location: index.php?page=expense_list&msg=not_found');
            exit;
        }

        $lang = $_SESSION['lang'] ?? '2';
        $this->render('expense/view', compact('expense', 'lang'));
    }

    /**
     * POST: Soft delete expense
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=expense_list');
            exit;
        }

        $this->verifyCsrf();
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->model->softDelete($id);
        }

        header('Location: index.php?page=expense_list&msg=deleted');
        exit;
    }

    /**
     * POST: Update expense status (approve, reject, pay, cancel)
     */
    public function status(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=expense_list');
            exit;
        }

        $this->verifyCsrf();
        $id = intval($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $allowed = ['pending', 'approved', 'paid', 'rejected', 'cancelled'];

        if ($id > 0 && in_array($newStatus, $allowed)) {
            $approvedBy = in_array($newStatus, ['approved', 'rejected']) ? ($this->user['id'] ?? null) : null;
            $this->model->updateStatus($id, $newStatus, $approvedBy);
        }

        $redirect = $_POST['redirect'] ?? 'expense_list';
        header("Location: index.php?page={$redirect}&msg=status_updated");
        exit;
    }

    /**
     * Monthly summary / category breakdown
     */
    public function summary(): void
    {
        if ($this->user['level'] < 1) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied.</div>';
            return;
        }

        $year = $this->inputInt('year') ?: (int) date('Y');
        $month = $this->inputInt('month') ?: (int) date('m');

        $filters = [
            'date_from' => sprintf('%04d-%02d-01', $year, $month),
            'date_to'   => date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month))),
        ];

        $summary = $this->model->getSummary($filters);
        $byCategory = $this->model->getByCategory($filters);
        $monthlyTotals = $this->model->getMonthlyTotals($year);
        $lang = $_SESSION['lang'] ?? '2';

        $this->render('expense/summary', compact('summary', 'byCategory', 'monthlyTotals', 'year', 'month', 'lang'));
    }

    // =====================================================
    // Category Management
    // =====================================================

    /**
     * Category list page
     */
    public function categories(): void
    {
        if ($this->user['level'] < 1) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied.</div>';
            return;
        }

        $categories = $this->categoryModel->getAllCategories();
        $message = $_GET['msg'] ?? '';
        $lang = $_SESSION['lang'] ?? '2';

        $this->render('expense/categories', compact('categories', 'message', 'lang'));
    }

    /**
     * POST: Save category (create or update)
     */
    public function categoryStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=expense_cat_list');
            exit;
        }

        $this->verifyCsrf();

        $id = intval($_POST['id'] ?? 0);
        $data = [
            'com_id'      => $this->getCompanyId(),
            'name'        => trim($_POST['name'] ?? ''),
            'name_th'     => trim($_POST['name_th'] ?? ''),
            'code'        => strtoupper(trim($_POST['code'] ?? '')),
            'icon'        => trim($_POST['icon'] ?? 'fa-folder'),
            'color'       => trim($_POST['color'] ?? '#6366f1'),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order'  => intval($_POST['sort_order'] ?? 0),
        ];

        // Validate code uniqueness
        if (!empty($data['code']) && $this->categoryModel->codeExists($data['code'], $id ?: null)) {
            header('Location: index.php?page=expense_cat_list&msg=code_exists');
            exit;
        }

        if ($id > 0) {
            unset($data['com_id']);
            $this->categoryModel->update($id, $data);
            $msg = 'updated';
        } else {
            $this->categoryModel->create($data);
            $msg = 'created';
        }

        header("Location: index.php?page=expense_cat_list&msg={$msg}");
        exit;
    }

    /**
     * POST: Toggle category active/inactive
     */
    public function categoryToggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=expense_cat_list');
            exit;
        }

        $this->verifyCsrf();
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->categoryModel->toggleActive($id);
        }

        header('Location: index.php?page=expense_cat_list&msg=updated');
        exit;
    }

    /**
     * POST: Soft delete category
     */
    public function categoryDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=expense_cat_list');
            exit;
        }

        $this->verifyCsrf();
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->categoryModel->softDelete($id);
        }

        header('Location: index.php?page=expense_cat_list&msg=deleted');
        exit;
    }
}
