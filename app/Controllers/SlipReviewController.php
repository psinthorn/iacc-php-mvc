<?php
namespace App\Controllers;

use App\Models\SlipReview;

/**
 * SlipReviewController — Admin PromptPay Slip Review Workflow
 * 
 * Admin can review, approve, or reject PromptPay payment slips
 * uploaded by customers. Pending slips are prioritized in the list.
 * 
 * Routes:
 *   slip_review       → index()   — List all PromptPay payments
 *   slip_review_approve → approve() — Approve a pending slip
 *   slip_review_reject  → reject()  — Reject a pending slip
 * 
 * @package App\Controllers
 * @version 1.0.0 — Q2 2026
 */
class SlipReviewController extends BaseController
{
    private SlipReview $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new SlipReview();
    }

    /**
     * List all PromptPay payment slips with filters
     */
    public function index(): void
    {
        // Admin access required (level >= 1)
        if ($this->user['level'] < 1) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied. Admin required.</div>';
            return;
        }

        // Filters from query string
        $status   = trim($_GET['status'] ?? '');
        $search   = trim($_GET['search'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to'] ?? '');
        $page     = max(1, intval($_GET['p'] ?? 1));
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;

        // Get data
        $stats    = $this->model->getStatusCounts();
        $payments = $this->model->getSlipPayments($status, $search, $dateFrom, $dateTo, $perPage, $offset);
        $total    = $this->model->getSlipPaymentsCount($status, $search, $dateFrom, $dateTo);
        $totalPages = max(1, ceil($total / $perPage));

        // Enrich each record with invoice info
        foreach ($payments as &$pay) {
            $invoiceId = $this->model->extractInvoiceId($pay['order_id']);
            $pay['invoice_info'] = $invoiceId > 0 ? $this->model->getInvoiceInfo($invoiceId) : null;
            $pay['invoice_id'] = $invoiceId;
        }
        unset($pay);

        // Flash messages
        $successMsg = $_SESSION['slip_success'] ?? '';
        $errorMsg   = $_SESSION['slip_error'] ?? '';
        unset($_SESSION['slip_success'], $_SESSION['slip_error']);

        $lang = $_SESSION['lang'] ?? 'th';

        include __DIR__ . '/../Views/slip-review/index.php';
    }

    /**
     * Approve a pending PromptPay payment
     */
    public function approve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=slip_review');
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $userId = intval($_SESSION['user_id'] ?? 0);

        if (!$id) {
            $_SESSION['slip_error'] = 'Invalid payment ID';
            header('Location: index.php?page=slip_review');
            exit;
        }

        $record = $this->model->getById($id);
        if (!$record) {
            $_SESSION['slip_error'] = 'Payment record not found';
            header('Location: index.php?page=slip_review');
            exit;
        }

        if (!in_array($record['status'], ['pending', 'pending_review'])) {
            $_SESSION['slip_error'] = 'Payment is not in pending status';
            header('Location: index.php?page=slip_review');
            exit;
        }

        if ($this->model->approve($id, $userId)) {
            $lang = $_SESSION['lang'] ?? 'th';
            $_SESSION['slip_success'] = $lang === 'th' 
                ? "อนุมัติการชำระเงิน #{$id} สำเร็จ" 
                : "Payment #{$id} approved successfully";
        } else {
            $_SESSION['slip_error'] = 'Failed to approve payment';
        }

        header('Location: index.php?page=slip_review');
        exit;
    }

    /**
     * Reject a pending PromptPay payment
     */
    public function reject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=slip_review');
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $userId = intval($_SESSION['user_id'] ?? 0);

        if (!$id) {
            $_SESSION['slip_error'] = 'Invalid payment ID';
            header('Location: index.php?page=slip_review');
            exit;
        }

        $record = $this->model->getById($id);
        if (!$record) {
            $_SESSION['slip_error'] = 'Payment record not found';
            header('Location: index.php?page=slip_review');
            exit;
        }

        if (!in_array($record['status'], ['pending', 'pending_review'])) {
            $_SESSION['slip_error'] = 'Payment is not in pending status';
            header('Location: index.php?page=slip_review');
            exit;
        }

        if ($this->model->reject($id, $userId, $reason)) {
            $lang = $_SESSION['lang'] ?? 'th';
            $_SESSION['slip_success'] = $lang === 'th'
                ? "ปฏิเสธการชำระเงิน #{$id}"
                : "Payment #{$id} rejected";
        } else {
            $_SESSION['slip_error'] = 'Failed to reject payment';
        }

        header('Location: index.php?page=slip_review');
        exit;
    }
}
