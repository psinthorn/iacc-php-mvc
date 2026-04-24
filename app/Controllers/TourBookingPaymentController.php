<?php
namespace App\Controllers;

use App\Models\TourBookingPayment;
use App\Models\TourBooking;
use App\Models\PaymentGateway;
use App\Services\PromptPayService;

class TourBookingPaymentController extends BaseController
{
    private TourBookingPayment $paymentModel;
    private TourBooking $bookingModel;

    private PaymentGateway $gatewayModel;

    public function __construct()
    {
        parent::__construct();
        $this->paymentModel = new TourBookingPayment();
        $this->bookingModel = new TourBooking();
        $this->gatewayModel = new PaymentGateway($this->conn);
    }

    private function guardModule(): void
    {
        if (!isModuleEnabled($this->user['com_id'], 'tour_operator')) {
            $this->redirect('main');
        }
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

    // ─── Index: load gateways too ─────────────────────────────

    public function index(): void
    {
        $this->guardModule();
        $comId     = $this->user['com_id'];
        $bookingId = intval($_GET['booking_id'] ?? 0);

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking) {
            $this->json(['error' => 'Booking not found'], 404);
        }

        $payments = $this->paymentModel->getPayments($bookingId, $comId);
        $summary  = $this->paymentModel->getBookingPaymentSummary($bookingId, $comId);
        $gateways = $this->gatewayModel->getGateways($comId);

        // Only show gateways that are enabled + configured
        $configuredGateways = [];
        foreach ($gateways as $gw) {
            if (!intval($gw['is_active'])) continue;
            $cfg = $this->gatewayModel->getGatewayConfig($gw['id'], $comId);
            if (!empty($cfg)) {
                $gw['config'] = $cfg;
                $configuredGateways[] = $gw;
            }
        }

        $flash = $_SESSION['pay_flash'] ?? null;
        unset($_SESSION['pay_flash']);

        $this->render('tour-booking/payments', [
            'booking'   => $booking,
            'payments'  => $payments,
            'summary'   => $summary,
            'gateways'  => $configuredGateways,
            'flash'     => $flash,
        ]);
    }

    // ─── Gateway Checkout (POST → redirect to gateway) ────────

    public function checkout(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $gateway   = trim($_POST['gateway'] ?? '');
        $amount    = floatval($_POST['amount'] ?? 0);
        $type      = trim($_POST['payment_type'] ?? 'full');

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking || $amount <= 0) {
            $_SESSION['pay_flash'] = ['type' => 'error', 'msg' => 'Invalid booking or amount.'];
            $this->redirect('tour_booking_payments', ['booking_id' => $bookingId]);
            return;
        }

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                 . '://' . $_SERVER['HTTP_HOST'];

        try {
            if ($gateway === 'stripe') {
                require_once(__DIR__ . '/../../inc/class.stripe.php');
                $stripe = new \StripeService($this->conn);
                $stripe->loadConfigForCompany($comId);

                $result = $stripe->createCheckoutSession([
                    'reference_id' => 'BK-' . $booking['booking_number'],
                    'booking_id'   => strval($bookingId),
                    'currency'     => $stripe->getCurrency(),
                    'success_url'  => $baseUrl . '/index.php?page=tour_booking_payment_gw_success&gateway=stripe&booking_id=' . $bookingId . '&type=' . $type . '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'   => $baseUrl . '/index.php?page=tour_booking_payment_gw_cancel&booking_id=' . $bookingId,
                    'email'        => $booking['contact']['contact_email'] ?? '',
                    'items'        => [[
                        'name'     => 'Booking #' . $booking['booking_number'],
                        'quantity' => 1,
                        'price'    => $amount,
                    ]],
                ]);

                if ($result['success']) {
                    header('Location: ' . $result['checkout_url']);
                    exit;
                }
                throw new \Exception($result['message'] ?? 'Stripe checkout failed');

            } elseif ($gateway === 'paypal') {
                require_once(__DIR__ . '/../../inc/class.paypal.php');
                $paypal = new \PayPalService($this->conn);
                $paypal->loadConfigForCompany($comId);

                $result = $paypal->createOrder([
                    'reference_id' => 'BK-' . $booking['booking_number'],
                    'booking_id'   => strval($bookingId),
                    'description'  => 'Booking #' . $booking['booking_number'],
                    'currency'     => 'THB',
                    'total'        => $amount,
                    'items'        => [[
                        'name'     => 'Booking #' . $booking['booking_number'],
                        'quantity' => 1,
                        'price'    => $amount,
                    ]],
                    'return_url'   => $baseUrl . '/index.php?page=tour_booking_payment_gw_success&gateway=paypal&booking_id=' . $bookingId . '&type=' . $type,
                    'cancel_url'   => $baseUrl . '/index.php?page=tour_booking_payment_gw_cancel&booking_id=' . $bookingId,
                ]);

                if ($result['success']) {
                    header('Location: ' . $result['approval_url']);
                    exit;
                }
                throw new \Exception($result['message'] ?? 'PayPal checkout failed');

            } elseif ($gateway === 'promptpay') {
                // Store amount + type in session, redirect to QR page
                $_SESSION['pp_booking']  = $bookingId;
                $_SESSION['pp_amount']   = $amount;
                $_SESSION['pp_type']     = $type;
                header('Location: index.php?page=tour_booking_payment_promptpay&booking_id=' . $bookingId);
                exit;
            }

            throw new \Exception('Unknown gateway: ' . htmlspecialchars($gateway));

        } catch (\Exception $e) {
            $_SESSION['pay_flash'] = ['type' => 'error', 'msg' => 'Payment initiation failed: ' . $e->getMessage()];
            $this->redirect('tour_booking_payments', ['booking_id' => $bookingId]);
        }
    }

    // ─── Gateway Success Callback ──────────────────────────────

    public function gatewaySuccess(): void
    {
        $this->guardModule();
        $comId     = $this->user['com_id'];
        $bookingId = intval($_GET['booking_id'] ?? 0);
        $gateway   = trim($_GET['gateway'] ?? '');
        $type      = trim($_GET['payment_type'] ?? $_GET['type'] ?? 'full');
        $sessionId = trim($_GET['session_id'] ?? '');
        $token     = trim($_GET['token'] ?? '');  // PayPal order ID

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking) {
            $this->redirect('tour_booking_list');
            return;
        }

        try {
            $amount      = 0;
            $referenceId = '';

            if ($gateway === 'stripe' && $sessionId) {
                require_once(__DIR__ . '/../../inc/class.stripe.php');
                $stripeGw = new \StripeService($this->conn);
                $stripeGw->loadConfigForCompany($comId);
                $session = $stripeGw->getCheckoutSession($sessionId);

                if (($session['payment_status'] ?? '') !== 'paid') {
                    throw new \Exception('Stripe payment not completed.');
                }
                $amount      = ($session['amount_total'] ?? 0) / 100;
                $referenceId = $session['payment_intent'] ?? $sessionId;

            } elseif ($gateway === 'paypal' && $token) {
                require_once(__DIR__ . '/../../inc/class.paypal.php');
                $paypalGw = new \PayPalService($this->conn);
                $paypalGw->loadConfigForCompany($comId);
                $capture = $paypalGw->capturePayment($token);

                if (!$capture['success'] || ($capture['status'] ?? '') !== 'COMPLETED') {
                    throw new \Exception('PayPal capture failed.');
                }
                $amount      = floatval($capture['amount']['value'] ?? 0);
                $referenceId = $capture['capture_id'] ?? $token;
            } else {
                throw new \Exception('Unknown or missing gateway callback.');
            }

            if ($amount <= 0) {
                throw new \Exception('Invalid payment amount received.');
            }

            // Record the payment as completed
            $this->paymentModel->recordPayment([
                'booking_id'     => $bookingId,
                'company_id'     => $comId,
                'payment_method' => $gateway,
                'gateway'        => $gateway,
                'amount'         => $amount,
                'currency'       => 'THB',
                'reference_id'   => $referenceId,
                'payment_date'   => date('Y-m-d'),
                'status'         => 'completed',
                'payment_type'   => $type,
                'notes'          => 'Auto-confirmed via ' . strtoupper($gateway),
                'created_by'     => $this->user['id'],
            ]);

            $_SESSION['pay_flash'] = [
                'type' => 'success',
                'msg'  => strtoupper($gateway) . ' payment of ฿' . number_format($amount, 2) . ' confirmed.',
            ];

        } catch (\Exception $e) {
            $_SESSION['pay_flash'] = ['type' => 'error', 'msg' => 'Payment verification failed: ' . $e->getMessage()];
        }

        $this->redirect('tour_booking_payments', ['booking_id' => $bookingId]);
    }

    // ─── Gateway Cancel Callback ───────────────────────────────

    public function gatewayCancel(): void
    {
        $bookingId = intval($_GET['booking_id'] ?? 0);
        $_SESSION['pay_flash'] = ['type' => 'warning', 'msg' => 'Payment was cancelled. No charge was made.'];
        $this->redirect('tour_booking_payments', ['booking_id' => $bookingId]);
    }

    // ─── PromptPay QR Page ─────────────────────────────────────

    public function promptpayPage(): void
    {
        $this->guardModule();
        $comId     = $this->user['com_id'];
        $bookingId = intval($_GET['booking_id'] ?? 0);

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking) {
            $this->redirect('tour_booking_list');
            return;
        }

        $amount = floatval($_SESSION['pp_amount'] ?? $booking['amount_due'] ?? $booking['total_amount']);
        $type   = $_SESSION['pp_type'] ?? 'full';
        unset($_SESSION['pp_booking'], $_SESSION['pp_amount'], $_SESSION['pp_type']);

        try {
            $promptpay = new PromptPayService($this->conn);
            $promptpay->loadConfig($comId);
            $qrData        = $promptpay->generateQR($amount);
            $promptpayName = $promptpay->getConfig()['promptpay_name'] ?? '';
        } catch (\Exception $e) {
            $qrData        = ['qr_url' => '', 'amount' => $amount, 'target' => ''];
            $promptpayName = '';
        }

        $this->render('tour-booking/promptpay', [
            'booking'       => $booking,
            'amount'        => $amount,
            'paymentType'   => $type,
            'qrData'        => $qrData,
            'promptpayName' => $promptpayName,
        ]);
    }

    // ─── PromptPay Slip Upload & Confirm ──────────────────────

    public function promptpayConfirm(): void
    {
        $this->guardModule();
        $this->verifyCsrf();

        $comId     = $this->user['com_id'];
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $amount    = floatval($_POST['amount'] ?? 0);
        $type      = trim($_POST['payment_type'] ?? 'full');
        $transRef  = trim($_POST['reference_id'] ?? '');

        $booking = $this->bookingModel->findBooking($bookingId, $comId);
        if (!$booking || $amount <= 0) {
            $_SESSION['pay_flash'] = ['type' => 'error', 'msg' => 'Invalid booking or amount.'];
            $this->redirect('tour_booking_payments', ['booking_id' => $bookingId]);
            return;
        }

        $slipImage = null;
        if (!empty($_FILES['slip']['name']) && $_FILES['slip']['error'] === UPLOAD_ERR_OK) {
            $slipImage = $this->handleSlipUpload($_FILES['slip'], $comId, $bookingId);
        }

        $this->paymentModel->recordPayment([
            'booking_id'     => $bookingId,
            'company_id'     => $comId,
            'payment_method' => 'promptpay',
            'gateway'        => 'promptpay',
            'amount'         => $amount,
            'currency'       => 'THB',
            'reference_id'   => $transRef,
            'payment_date'   => date('Y-m-d'),
            'status'         => $slipImage ? 'pending_review' : 'pending',
            'payment_type'   => $type,
            'slip_image'     => $slipImage,
            'notes'          => 'PromptPay transfer — awaiting admin approval',
            'created_by'     => $this->user['id'],
        ]);

        $_SESSION['pay_flash'] = [
            'type' => 'success',
            'msg'  => 'PromptPay payment of ฿' . number_format($amount, 2) . ' submitted. Awaiting approval.',
        ];
        $this->redirect('tour_booking_payments', ['booking_id' => $bookingId]);
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
