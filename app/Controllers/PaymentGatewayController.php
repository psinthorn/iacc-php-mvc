<?php
namespace App\Controllers;

use App\Models\PaymentGateway;

/**
 * PaymentGatewayController
 * 
 * Handles payment gateway configuration, testing, and webhook processing.
 * Migrated from: payment-gateway-config.php, payment-gateway-test.php, payment-webhook.php
 */
class PaymentGatewayController extends BaseController
{
    private PaymentGateway $gateway;

    public function __construct()
    {
        parent::__construct();
        $this->gateway = new PaymentGateway($this->conn);
    }

    /**
     * Gateway configuration page (GET) + save config (POST)
     */
    public function index(): void
    {
        // Admin access required (level >= 2)
        if ($this->user['level'] < 2) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied. Super Admin privileges required.</div>';
            return;
        }

        $companyId = $this->companyFilter->getSafeCompanyId();
        $message = '';
        $messageType = '';

        // Handle POST: save_config or test_connection
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'save_config') {
                $gatewayId = intval($_POST['gateway_id'] ?? 0);
                $configs = $_POST['config'] ?? [];

                if ($gatewayId > 0 && !empty($configs)) {
                    if ($this->gateway->saveConfig($gatewayId, $companyId, $configs)) {
                        $message = 'Configuration saved successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Error saving configuration.';
                        $messageType = 'danger';
                    }
                }
            }

            if ($action === 'test_connection') {
                $gatewayCode = $_POST['gateway_code'] ?? '';
                $gatewayId = intval($_POST['gateway_id'] ?? 0);
                $testConfigs = $this->gateway->getGatewayConfig($gatewayId, $companyId);
                $result = $this->gateway->testConnection($gatewayCode, $testConfigs);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            }
        }

        // Load gateways and their configs
        $gateways = $this->gateway->getGateways($companyId);
        $gatewayConfigs = [];
        foreach ($gateways as $gw) {
            $gatewayConfigs[$gw['id']] = $this->gateway->getGatewayConfig($gw['id'], $companyId);
        }
        $gatewayFields = PaymentGateway::getGatewayFields();

        $this->render('payment-gateway/config', compact(
            'gateways', 'gatewayConfigs', 'gatewayFields', 'message', 'messageType'
        ));
    }

    /**
     * AJAX test connection endpoint (POST, JSON)
     */
    public function test(): void
    {
        header('Content-Type: application/json');

        if ($this->user['level'] < 2) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $gateway = $_POST['gateway'] ?? '';
        $configs = $_POST['config'] ?? [];

        if (empty($gateway) || empty($configs)) {
            echo json_encode(['success' => false, 'message' => 'Missing configuration']);
            exit;
        }

        $result = $this->gateway->testConnection($gateway, $configs);
        echo json_encode($result);
        exit;
    }

    /**
     * Webhook handler — processes notifications from PayPal / Stripe
     * Note: This is a stateless JSON endpoint, no session/HTML
     */
    public function webhook(): void
    {
        header('Content-Type: application/json');

        $gateway = $_GET['gateway'] ?? '';
        $payload = file_get_contents('php://input');
        $headers = getallheaders();

        // Log incoming webhook
        $logFile = __DIR__ . '/../../logs/payment-webhooks.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logEntry = date('Y-m-d H:i:s') . " | Gateway: {$gateway} | Headers: " . json_encode($headers) . " | Payload: {$payload}\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        try {
            switch ($gateway) {
                case 'paypal':
                    $this->handlePayPalWebhook($headers, $payload);
                    break;
                case 'stripe':
                    $this->handleStripeWebhook($headers, $payload);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Unknown gateway']);
                    exit;
            }
        } catch (\Exception $e) {
            $errorLog = date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n";
            file_put_contents($logFile, $errorLog, FILE_APPEND);
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    private function handlePayPalWebhook(array $headers, string $payload): void
    {
        require_once(__DIR__ . '/../../inc/class.paypal.php');
        $paypal = new \PayPalService($this->conn);
        $event = json_decode($payload, true);

        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }

        $result = $paypal->handleWebhook($event);
        http_response_code(200);
        echo json_encode(['success' => true, 'result' => $result]);
    }

    private function handleStripeWebhook(array $headers, string $payload): void
    {
        require_once(__DIR__ . '/../../inc/class.stripe.php');
        $stripe = new \StripeService($this->conn);
        $sigHeader = $headers['Stripe-Signature'] ?? $headers['stripe-signature'] ?? '';

        if ($stripe->isConfigured() && !empty($sigHeader)) {
            $event = $stripe->verifyWebhook($payload, $sigHeader);
        } else {
            $event = json_decode($payload, true);
        }

        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid event payload']);
            return;
        }

        $result = $stripe->handleWebhook($event);
        http_response_code(200);
        echo json_encode(['success' => true, 'result' => $result]);
    }
}
