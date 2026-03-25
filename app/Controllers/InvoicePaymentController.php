<?php
namespace App\Controllers;

use App\Models\InvoicePayment;

/**
 * InvoicePaymentController
 * 
 * Handles invoice online payment flow: checkout, success callback, cancel callback.
 * Migrated from: inv-checkout.php, inv-payment-success.php, inv-payment-cancel.php
 * 
 * Note: checkout/success/cancel pages are standalone HTML pages (not inside the admin layout)
 * because they're customer-facing.
 */
class InvoicePaymentController extends BaseController
{
    private InvoicePayment $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new InvoicePayment($this->conn);
    }

    /**
     * Checkout page — customer selects PayPal or Stripe to pay an invoice
     */
    public function checkout(): void
    {
        $invoiceId = intval($_GET['id'] ?? 0);
        if (!$invoiceId) {
            die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Error</h2><p>Invalid invoice ID</p></div>');
        }

        $invoice = $this->model->getInvoiceForCheckout($invoiceId);
        if (!$invoice) {
            die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Invoice Not Found</h2><p>The invoice does not exist, is already paid, or has been deleted.</p></div>');
        }

        $totals = $this->model->calculateTotal($invoiceId, $invoice);
        $gateways = $this->model->getActiveGateways();
        $error = '';

        // Handle payment initiation POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gateway'])) {
            $selectedGateway = $_POST['gateway'];
            try {
                if ($selectedGateway === 'paypal') {
                    require_once(__DIR__ . '/../../inc/class.paypal.php');
                    $paypal = new \PayPalService($this->conn);
                    $orderData = [
                        'reference_id' => 'INV-' . $invoice['tex'],
                        'invoice_id' => strval($invoice['tex']),
                        'description' => 'Invoice #' . $invoice['tex'] . ' - ' . $invoice['po_name'],
                        'currency' => 'THB',
                        'total' => $totals['amountDue'],
                        'items' => [['name' => 'Invoice #' . $invoice['tex'], 'quantity' => 1, 'price' => $totals['amountDue']]]
                    ];
                    $result = $paypal->createOrder($orderData);
                    if ($result['success']) {
                        $this->model->updatePaymentGateway($invoice['tex'], 'paypal', $result['order_id']);
                        header('Location: ' . $result['approval_url']);
                        exit;
                    }
                } elseif ($selectedGateway === 'stripe') {
                    require_once(__DIR__ . '/../../inc/class.stripe.php');
                    $stripe = new \StripeService($this->conn);
                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                    $sessionData = [
                        'reference_id' => 'INV-' . $invoice['tex'],
                        'invoice_id' => strval($invoice['tex']),
                        'currency' => $stripe->getCurrency(),
                        'success_url' => $baseUrl . '/index.php?page=inv_payment_success&session_id={CHECKOUT_SESSION_ID}&invoice_id=' . $invoice['tex'],
                        'cancel_url' => $baseUrl . '/index.php?page=inv_payment_cancel&invoice_id=' . $invoice['tex'],
                        'email' => $invoice['customer_email'],
                        'items' => [['name' => 'Invoice #' . $invoice['tex'] . ' - ' . $invoice['po_name'], 'quantity' => 1, 'price' => $totals['amountDue']]]
                    ];
                    $result = $stripe->createCheckoutSession($sessionData);
                    if ($result['success']) {
                        $this->model->updatePaymentGateway($invoice['tex'], 'stripe', $result['session_id']);
                        header('Location: ' . $result['checkout_url']);
                        exit;
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        // Render standalone checkout page
        include __DIR__ . '/../../views/invoice-payment/checkout.php';
        exit; // standalone page — don't render admin layout
    }

    /**
     * Payment success callback (from PayPal/Stripe)
     */
    public function success(): void
    {
        $invoiceId = intval($_GET['invoice_id'] ?? 0);
        $sessionId = $_GET['session_id'] ?? '';
        $paypalOrderId = $_GET['token'] ?? '';

        $gateway = '';
        if ($sessionId) $gateway = 'stripe';
        elseif ($paypalOrderId) $gateway = 'paypal';

        $error = '';
        $success = false;
        $receiptId = null;

        try {
            if (!$invoiceId && !$sessionId && !$paypalOrderId) {
                throw new \Exception('Missing payment information');
            }

            // Resolve invoice ID from gateway if needed
            if ($sessionId && !$invoiceId) {
                require_once(__DIR__ . '/../../inc/class.stripe.php');
                $stripe = new \StripeService($this->conn);
                $session = $stripe->getCheckoutSession($sessionId);
                $invoiceId = intval($session['metadata']['invoice_id'] ?? 0);
            }
            if ($paypalOrderId && !$invoiceId) {
                require_once(__DIR__ . '/../../inc/class.paypal.php');
                $paypal = new \PayPalService($this->conn);
                $order = $paypal->getOrderDetails($paypalOrderId);
                $invoiceId = intval($order['purchase_units'][0]['invoice_id'] ?? 0);
            }

            if (!$invoiceId) throw new \Exception('Could not determine invoice ID');

            $invoice = $this->model->getInvoiceForDisplay($invoiceId);
            if (!$invoice) throw new \Exception('Invoice not found');

            if ($invoice['payment_status'] === 'paid') {
                $success = true;
                $receiptId = $this->model->getExistingReceipt($invoiceId);
            } else {
                $totals = $this->model->calculateTotal($invoiceId, $invoice);

                // Verify payment with gateway
                $paymentVerified = false;
                $transactionId = '';
                $paidAmount = $totals['grandTotal'];

                if ($gateway === 'stripe' && $sessionId) {
                    require_once(__DIR__ . '/../../inc/class.stripe.php');
                    $stripe = new \StripeService($this->conn);
                    $session = $stripe->getCheckoutSession($sessionId);
                    if ($session['payment_status'] === 'paid') {
                        $paymentVerified = true;
                        $transactionId = $session['payment_intent'] ?? $sessionId;
                        $paidAmount = ($session['amount_total'] ?? 0) / 100;
                    }
                } elseif ($gateway === 'paypal') {
                    require_once(__DIR__ . '/../../inc/class.paypal.php');
                    $paypal = new \PayPalService($this->conn);
                    $orderId = $paypalOrderId ?: $invoice['payment_order_id'];
                    if ($orderId) {
                        $order = $paypal->getOrderDetails($orderId);
                        if ($order['status'] === 'APPROVED') {
                            $captureResult = $paypal->capturePayment($orderId);
                            if ($captureResult['success'] && $captureResult['status'] === 'COMPLETED') {
                                $paymentVerified = true;
                                $transactionId = $captureResult['capture_id'] ?? $orderId;
                                $paidAmount = floatval($captureResult['amount']['value'] ?? $totals['grandTotal']);
                            }
                        } elseif ($order['status'] === 'COMPLETED') {
                            $paymentVerified = true;
                            $transactionId = $orderId;
                            $captures = $order['purchase_units'][0]['payments']['captures'] ?? [];
                            if (!empty($captures)) $paidAmount = floatval($captures[0]['amount']['value'] ?? $totals['grandTotal']);
                        }
                    }
                }

                if (!$paymentVerified) throw new \Exception('Payment could not be verified. Please contact support.');

                $receiptId = $this->model->markPaid($invoiceId, $paidAmount, $gateway, $transactionId, $invoice);
                $success = true;
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $invoiceData = $invoiceId ? $this->model->getInvoiceBasicDisplay($invoiceId) : null;

        include __DIR__ . '/../../views/invoice-payment/success.php';
        exit;
    }

    /**
     * Payment cancel/failed callback
     */
    public function cancel(): void
    {
        $invoiceId = intval($_GET['invoice_id'] ?? 0);
        $reason = $_GET['reason'] ?? 'cancelled';

        $invoiceData = null;
        if ($invoiceId) {
            $invoiceData = $this->model->getInvoiceBasicDisplay($invoiceId);
            $this->model->clearPendingPayment($invoiceId);
        }

        $title = 'Payment Cancelled';
        $message = 'Your payment was cancelled. No charges have been made to your account.';
        $icon = 'fa-times-circle';

        if ($reason === 'failed') {
            $title = 'Payment Failed';
            $message = 'Your payment could not be processed. Please try again or use a different payment method.';
            $icon = 'fa-exclamation-triangle';
        } elseif ($reason === 'expired') {
            $title = 'Payment Session Expired';
            $message = 'Your payment session has expired. Please start a new payment.';
            $icon = 'fa-clock-o';
        }

        include __DIR__ . '/../../views/invoice-payment/cancel.php';
        exit;
    }
}
