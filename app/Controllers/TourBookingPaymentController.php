<?php
namespace App\Controllers;

use App\Models\TourBookingPayment;
use App\Models\TourBooking;

class TourBookingPaymentController extends BaseController
{
    private TourBookingPayment $paymentModel;
    private TourBooking $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->paymentModel = new TourBookingPayment();
        $this->bookingModel = new TourBooking();
    }

    private function guardModule(): void
    {
        if (!isModuleEnabled($this->user['com_id'], 'tour_operator')) {
            $this->redirect('main');
        }
    }

    // ─── Payments List (standalone JSON or page partial) ───────

    public function index(): void
    {
        $this->guardModule();
        $comId = $this->user['com_id'];
        $bookingId = intval($_GET['booking_id'] ?? 0);

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking) {
            $this->json(['error' => 'Booking not found'], 404);
        }

        $payments = $this->paymentModel->getPayments($bookingId, $comId);
        $summary  = $this->paymentModel->getBookingPaymentSummary($bookingId, $comId);

        $this->render('tour-booking/payments', [
            'booking'  => $booking,
            'payments' => $payments,
            'summary'  => $summary,
        ]);
    }

    // ─── Record Payment (POST → redirect) ─────────────────────

    public function store(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $bookingId = intval($_POST['booking_id'] ?? 0);

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking) {
            $this->redirect('tour_booking_list', ['msg' => 'not_found']);
            return;
        }

        // Validate amount
        $amount = floatval($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'invalid_amount']);
            return;
        }

        // Handle slip upload
        $slipImage = null;
        if (!empty($_FILES['slip_image']['name']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
            $slipImage = $this->handleSlipUpload($_FILES['slip_image'], $comId, $bookingId);
            if (!$slipImage) {
                $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'upload_error']);
                return;
            }
        }

        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');

        // Determine status: manual = completed, with slip = pending_review
        $status = 'completed';
        if ($slipImage) {
            $status = 'pending_review';
        }
        if (!empty($_POST['status']) && in_array($_POST['status'], ['completed', 'pending', 'pending_review'])) {
            $status = $_POST['status'];
        }

        $data = [
            'booking_id'     => $bookingId,
            'company_id'     => $comId,
            'payment_method' => $paymentMethod,
            'gateway'        => trim($_POST['gateway'] ?? ''),
            'amount'         => $amount,
            'currency'       => trim($_POST['currency'] ?? $booking['currency'] ?? 'THB'),
            'reference_id'   => trim($_POST['reference_id'] ?? ''),
            'payment_date'   => trim($_POST['payment_date'] ?? date('Y-m-d')),
            'status'         => $status,
            'payment_type'   => trim($_POST['payment_type'] ?? 'full'),
            'slip_image'     => $slipImage,
            'notes'          => trim($_POST['notes'] ?? ''),
            'created_by'     => $this->user['id'],
        ];

        $id = $this->paymentModel->recordPayment($data);

        $msg = $id ? 'payment_recorded' : 'payment_error';
        $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => $msg]);
    }

    // ─── Delete Payment ────────────────────────────────────────

    public function delete(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $id        = intval($_POST['id'] ?? 0);
        $bookingId = intval($_POST['booking_id'] ?? 0);

        $this->paymentModel->deletePayment($id, $comId);
        $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'payment_deleted']);
    }

    // ─── Approve Slip ──────────────────────────────────────────

    public function approve(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $id        = intval($_POST['id'] ?? 0);
        $bookingId = intval($_POST['booking_id'] ?? 0);

        $this->paymentModel->approvePayment($id, $comId, $this->user['id']);
        $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'payment_approved']);
    }

    // ─── Reject Slip ───────────────────────────────────────────

    public function reject(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $id        = intval($_POST['id'] ?? 0);
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $reason    = trim($_POST['reject_reason'] ?? '');

        $this->paymentModel->rejectPayment($id, $comId, $this->user['id'], $reason);
        $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'payment_rejected']);
    }

    // ─── Record Refund ─────────────────────────────────────────

    public function refund(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $amount    = floatval($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'invalid_amount']);
            return;
        }

        $data = [
            'booking_id'     => $bookingId,
            'company_id'     => $comId,
            'payment_method' => trim($_POST['payment_method'] ?? 'cash'),
            'amount'         => $amount,
            'reference_id'   => trim($_POST['reference_id'] ?? ''),
            'payment_date'   => trim($_POST['payment_date'] ?? date('Y-m-d')),
            'notes'          => trim($_POST['notes'] ?? ''),
            'created_by'     => $this->user['id'],
        ];

        $this->paymentModel->recordRefund($data);
        $this->redirect('tour_booking_view', ['id' => $bookingId, 'msg' => 'refund_recorded']);
    }

    // ─── Slip Upload Handler ───────────────────────────────────

    private function handleSlipUpload(array $file, int $comId, int $bookingId): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($file['type'], $allowed)) {
            return null;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = sprintf('slip_%d_%d_%s.%s', $comId, $bookingId, date('YmdHis'), $ext);
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/payment_slips/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $dest = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'upload/payment_slips/' . $filename;
        }

        return null;
    }
}
