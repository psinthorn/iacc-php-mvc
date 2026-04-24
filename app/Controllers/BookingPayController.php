<?php
namespace App\Controllers;

use App\Models\TourBooking;
use App\Models\TourBookingPayment;
use App\Models\PaymentGateway;
use App\Services\PromptPayService;

/**
 * BookingPayController — Customer-facing payment page
 *
 * Accessible via a signed URL (no admin login required).
 * URL format: index.php?page=booking_pay&id={bookingId}&token={hmac}
 */
class BookingPayController extends BaseController
{
    private TourBooking $bookingModel;
    private TourBookingPayment $paymentModel;
    private PaymentGateway $gatewayModel;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new TourBooking();
        $this->paymentModel = new TourBookingPayment();
        $this->gatewayModel = new PaymentGateway($this->conn);
    }

    // ─── Token helpers ─────────────────────────────────────────

    public static function makeToken(int $bookingId, int $companyId): string
    {
        $secret = defined('PAYMENT_LINK_SECRET') ? PAYMENT_LINK_SECRET : 'iacc-bk-pay-default';
        return substr(hash_hmac('sha256', "bk:{$bookingId}:{$companyId}", $secret), 0, 40);
    }

    private function verifyToken(int $bookingId, int $companyId, string $token): bool
    {
        return hash_equals(self::makeToken($bookingId, $companyId), $token);
    }

    private function loadBookingAndVerify(): ?array
    {
        $bookingId = intval($_GET['id'] ?? 0);
        $token     = trim($_GET['token'] ?? '');

        if (!$bookingId || !$token) {
            return null;
        }

        // We don't know company_id from the URL — find booking by id across active companies
        $sql = "SELECT b.*, c.name_en AS company_name
                FROM tour_bookings b
                JOIN company c ON b.company_id = c.id
                WHERE b.id = $bookingId AND b.deleted_at IS NULL
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $booking = $res ? mysqli_fetch_assoc($res) : null;

        if (!$booking) return null;

        $companyId = intval($booking['company_id']);
        if (!$this->verifyToken($bookingId, $companyId, $token)) {
            return null;
        }

        return $booking;
    }

    private function baseUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
             . '://' . $_SERVER['HTTP_HOST'];
    }

    private function pageUrl(string $page, array $params = []): string
    {
        $url = 'index.php?page=' . $page;
        foreach ($params as $k => $v) {
            $url .= '&' . $k . '=' . urlencode((string)$v);
        }
        return $url;
    }

    private function abort(string $title, string $message): void
    {
        include __DIR__ . '/../Views/booking-pay/error.php';
        exit;
    }

    // ─── Customer checkout page ────────────────────────────────

    public function index(): void
    {
        $booking = $this->loadBookingAndVerify();
        if (!$booking) {
            $title   = 'Invalid Payment Link';
            $message = 'This payment link is invalid or has expired. Please contact the tour operator for a new link.';
            $this->abort($title, $message);
        }

        $bookingId = intval($booking['id']);
        $companyId = intval($booking['company_id']);
        $token     = trim($_GET['token']);

        $contact  = $this->bookingModel->getBookingContact($bookingId);
        $summary  = $this->paymentModel->getBookingPaymentSummary($bookingId, $companyId);
        $gateways = $this->gatewayModel->getGateways($companyId);
        $flash    = $_SESSION['bpay_flash'] ?? null;
        unset($_SESSION['bpay_flash']);

        // Filter to only enabled + configured gateways
        $activeGateways = [];
        foreach ($gateways as $gw) {
            if (!intval($gw['is_active'])) continue;
            $cfg = $this->gatewayModel->getGatewayConfig($gw['id'], $companyId);
            if (!empty($cfg)) {
                $gw['config'] = $cfg;
                $activeGateways[] = $gw;
            }
        }

        $totalAmount = floatval($booking['total_amount'] ?? 0);
        $netPaid     = floatval($summary['net_paid'] ?? 0);
        $amountDue   = max(0.0, $totalAmount - $netPaid);

        include __DIR__ . '/../Views/booking-pay/checkout.php';
        exit;
    }

    // ─── Initiate gateway payment ──────────────────────────────

    public function checkout(): void
    {
        $this->verifyCsrf();

        $bookingId = intval($_POST['id'] ?? 0);
        $token     = trim($_POST['token'] ?? '');
        $gateway   = trim($_POST['gateway'] ?? '');
        $amount    = floatval($_POST['amount'] ?? 0);
        $type      = trim($_POST['payment_type'] ?? 'full');

        $sql = "SELECT * FROM tour_bookings WHERE id = $bookingId AND deleted_at IS NULL LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $booking = $res ? mysqli_fetch_assoc($res) : null;

        if (!$booking || !$this->verifyToken($bookingId, intval($booking['company_id']), $token)) {
            $title = 'Invalid Request'; $message = 'Token verification failed.';
            $this->abort($title, $message);
        }

        $companyId = intval($booking['company_id']);
        $base      = $this->baseUrl();
        $backUrl   = $this->pageUrl('booking_pay', ['id' => $bookingId, 'token' => $token]);

        try {
            if ($gateway === 'stripe') {
                require_once(__DIR__ . '/../../inc/class.stripe.php');
                $stripe = new \StripeService($this->conn);
                $stripe->loadConfigForCompany($companyId);

                $result = $stripe->createCheckoutSession([
                    'reference_id' => 'BK-' . $booking['booking_number'],
                    'booking_id'   => strval($bookingId),
                    'currency'     => $stripe->getCurrency(),
                    'success_url'  => $base . '/index.php?page=booking_pay_success&gateway=stripe&id=' . $bookingId . '&token=' . urlencode($token) . '&type=' . $type . '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'   => $base . '/index.php?page=booking_pay_cancel&id=' . $bookingId . '&token=' . urlencode($token),
                    'email'        => '',
                    'items'        => [['name' => 'Booking #' . $booking['booking_number'], 'quantity' => 1, 'price' => $amount]],
                ]);
                if ($result['success']) {
                    header('Location: ' . $result['checkout_url']); exit;
                }
                throw new \Exception($result['message'] ?? 'Stripe checkout failed');

            } elseif ($gateway === 'paypal') {
                require_once(__DIR__ . '/../../inc/class.paypal.php');
                $paypal = new \PayPalService($this->conn);
                $paypal->loadConfigForCompany($companyId);

                $result = $paypal->createOrder([
                    'reference_id' => 'BK-' . $booking['booking_number'],
                    'booking_id'   => strval($bookingId),
                    'description'  => 'Booking #' . $booking['booking_number'],
                    'currency'     => 'THB',
                    'total'        => $amount,
                    'items'        => [['name' => 'Booking #' . $booking['booking_number'], 'quantity' => 1, 'price' => $amount]],
                    'return_url'   => $base . '/index.php?page=booking_pay_success&gateway=paypal&id=' . $bookingId . '&token=' . urlencode($token) . '&type=' . $type,
                    'cancel_url'   => $base . '/index.php?page=booking_pay_cancel&id=' . $bookingId . '&token=' . urlencode($token),
                ]);
                if ($result['success']) {
                    header('Location: ' . $result['approval_url']); exit;
                }
                throw new \Exception($result['message'] ?? 'PayPal checkout failed');

            } elseif ($gateway === 'promptpay') {
                $_SESSION['bpay_pp_amount'] = $amount;
                $_SESSION['bpay_pp_type']   = $type;
                header('Location: index.php?page=booking_pay_promptpay&id=' . $bookingId . '&token=' . urlencode($token));
                exit;
            }

            throw new \Exception('Unknown gateway');

        } catch (\Exception $e) {
            error_log('BookingPay checkout error (booking ' . $bookingId . '): ' . $e->getMessage());
            $_SESSION['bpay_flash'] = ['type' => 'error', 'msg' => 'Payment processing failed. Please try again or contact the tour operator.'];
            header('Location: ' . $backUrl); exit;
        }
    }

    // ─── Gateway success callback ──────────────────────────────

    public function success(): void
    {
        $bookingId = intval($_GET['id'] ?? 0);
        $token     = trim($_GET['token'] ?? '');
        $gateway   = trim($_GET['gateway'] ?? '');
        $type      = trim($_GET['type'] ?? 'full');
        $sessionId = trim($_GET['session_id'] ?? '');
        $ppToken   = trim($_GET['token'] ?? ''); // PayPal calls it 'token'... careful, same param name
        $paypalOid = trim($_GET['PayerID'] ? ($_GET['token'] ?? '') : '');

        $sql = "SELECT * FROM tour_bookings WHERE id = $bookingId AND deleted_at IS NULL LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $booking = $res ? mysqli_fetch_assoc($res) : null;

        // Re-read token from GET properly
        $urlToken = trim($_GET['token'] ?? '');
        // PayPal passes its own 'token' (order ID) — detect by PayerID
        $paypalOrderId = isset($_GET['PayerID']) ? trim($_GET['paypalorder'] ?? '') : '';

        if (!$booking || !$this->verifyToken($bookingId, intval($booking['company_id']), $urlToken)) {
            $title = 'Error'; $message = 'Invalid payment session.';
            $this->abort($title, $message);
        }

        $companyId = intval($booking['company_id']);
        $error     = '';
        $amount    = 0;
        $refId     = '';

        try {
            if ($gateway === 'stripe' && $sessionId) {
                require_once(__DIR__ . '/../../inc/class.stripe.php');
                $stripe = new \StripeService($this->conn);
                $stripe->loadConfigForCompany($companyId);
                $session = $stripe->getCheckoutSession($sessionId);
                if (($session['payment_status'] ?? '') !== 'paid') throw new \Exception('Payment not completed');
                $amount = ($session['amount_total'] ?? 0) / 100;
                $refId  = $session['payment_intent'] ?? $sessionId;

            } elseif ($gateway === 'paypal') {
                require_once(__DIR__ . '/../../inc/class.paypal.php');
                $paypal = new \PayPalService($this->conn);
                $paypal->loadConfigForCompany($companyId);
                // PayPal returns order ID as 'token' query param
                $orderId = trim($_GET['token'] ?? '');
                $capture = $paypal->capturePayment($orderId);
                if (!$capture['success'] || ($capture['status'] ?? '') !== 'COMPLETED') throw new \Exception('PayPal capture failed');
                $amount = floatval($capture['amount']['value'] ?? 0);
                $refId  = $capture['capture_id'] ?? $orderId;
            } else {
                throw new \Exception('Unknown gateway callback');
            }

            $this->paymentModel->recordPayment([
                'booking_id'     => $bookingId,
                'company_id'     => $companyId,
                'payment_method' => $gateway,
                'gateway'        => $gateway,
                'amount'         => $amount,
                'currency'       => 'THB',
                'reference_id'   => $refId,
                'payment_date'   => date('Y-m-d'),
                'status'         => 'completed',
                'payment_type'   => $type,
                'notes'          => 'Paid by customer via ' . strtoupper($gateway) . ' payment link',
                'created_by'     => 0,
            ]);

        } catch (\Exception $e) {
            error_log('BookingPay success error (booking ' . $bookingId . ', gateway ' . $gateway . '): ' . $e->getMessage());
            $error = 'Payment verification failed. Please contact the tour operator with your payment reference.';
        }

        $bookingNumber = $booking['booking_number'];
        include __DIR__ . '/../Views/booking-pay/success.php';
        exit;
    }

    // ─── Gateway cancel ────────────────────────────────────────

    public function cancel(): void
    {
        $bookingId = intval($_GET['id'] ?? 0);
        $token     = trim($_GET['token'] ?? '');
        include __DIR__ . '/../Views/booking-pay/cancel.php';
        exit;
    }

    // ─── PromptPay QR page ─────────────────────────────────────

    public function promptpay(): void
    {
        $bookingId = intval($_GET['id'] ?? 0);
        $token     = trim($_GET['token'] ?? '');

        $sql = "SELECT * FROM tour_bookings WHERE id = $bookingId AND deleted_at IS NULL LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $booking = $res ? mysqli_fetch_assoc($res) : null;

        if (!$booking || !$this->verifyToken($bookingId, intval($booking['company_id']), $token)) {
            $title = 'Invalid Link'; $message = 'Token invalid.';
            $this->abort($title, $message);
        }

        $companyId = intval($booking['company_id']);
        $amount    = floatval($_SESSION['bpay_pp_amount'] ?? $booking['amount_due'] ?? $booking['total_amount']);
        $type      = $_SESSION['bpay_pp_type'] ?? 'full';
        unset($_SESSION['bpay_pp_amount'], $_SESSION['bpay_pp_type']);

        try {
            $pp = new PromptPayService($this->conn);
            $pp->loadConfig($companyId);
            $qrData        = $pp->generateQR($amount);
            $promptpayName = $pp->getConfig()['promptpay_name'] ?? '';
        } catch (\Exception $e) {
            $qrData        = ['qr_url' => '', 'amount' => $amount, 'target' => ''];
            $promptpayName = '';
        }

        include __DIR__ . '/../Views/booking-pay/promptpay.php';
        exit;
    }

    // ─── PromptPay slip confirm ────────────────────────────────

    public function promptpayConfirm(): void
    {
        $bookingId = intval($_POST['id'] ?? 0);
        $token     = trim($_POST['token'] ?? '');
        $amount    = floatval($_POST['amount'] ?? 0);
        $type      = trim($_POST['payment_type'] ?? 'full');
        $transRef  = trim($_POST['reference_id'] ?? '');

        $sql = "SELECT * FROM tour_bookings WHERE id = $bookingId AND deleted_at IS NULL LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $booking = $res ? mysqli_fetch_assoc($res) : null;

        if (!$booking || !$this->verifyToken($bookingId, intval($booking['company_id']), $token)) {
            $title = 'Invalid Request'; $message = 'Token verification failed.';
            $this->abort($title, $message);
        }

        $companyId = intval($booking['company_id']);
        $slipImage = null;

        if (!empty($_FILES['slip']['tmp_name'])) {
            $dir      = $_SERVER['DOCUMENT_ROOT'] . '/upload/payment_slips';
            $filename = \App\Helpers\FileUpload::save(
                $_FILES['slip'],
                $dir,
                'slip_' . $companyId . '_' . $bookingId,
                'document'
            );
            if ($filename) {
                $slipImage = 'upload/payment_slips/' . $filename;
            }
        }

        $this->paymentModel->recordPayment([
            'booking_id'     => $bookingId,
            'company_id'     => $companyId,
            'payment_method' => 'promptpay',
            'gateway'        => 'promptpay',
            'amount'         => $amount,
            'currency'       => 'THB',
            'reference_id'   => $transRef,
            'payment_date'   => date('Y-m-d'),
            'status'         => $slipImage ? 'pending_review' : 'pending',
            'payment_type'   => $type,
            'slip_image'     => $slipImage,
            'notes'          => 'PromptPay — submitted by customer via payment link',
            'created_by'     => 0,
        ]);

        $bookingNumber = $booking['booking_number'];
        $error = '';
        include __DIR__ . '/../Views/booking-pay/success.php';
        exit;
    }
}
