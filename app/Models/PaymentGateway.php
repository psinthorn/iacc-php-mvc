<?php
namespace App\Models;

/**
 * PaymentGateway Model
 * 
 * Database operations for payment gateway configuration (PayPal, Stripe).
 * Extracted from payment-gateway-config.php, payment-gateway-test.php, payment-webhook.php
 */
class PaymentGateway
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get all payment gateways for a company
     */
    public function getGateways(int $companyId): array
    {
        $sql = "SELECT pm.* FROM payment_method pm 
                WHERE pm.is_gateway = 1 AND pm.company_id = $companyId 
                ORDER BY pm.sort_order, pm.name";
        $result = mysqli_query($this->conn, $sql);
        $gateways = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $gateways[] = $row;
        }
        return $gateways;
    }

    /**
     * Get config values for a gateway
     */
    public function getGatewayConfig(int $gatewayId, int $companyId): array
    {
        $sql = "SELECT config_key, config_value FROM payment_gateway_config 
                WHERE payment_method_id = " . intval($gatewayId) . " AND company_id = $companyId";
        $result = mysqli_query($this->conn, $sql);
        $configs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $configs[$row['config_key']] = $row['config_value'];
        }
        return $configs;
    }

    /**
     * Enable or disable a gateway (set is_active on payment_method)
     */
    public function toggleActive(int $gatewayId, int $companyId, bool $active): bool
    {
        $v   = $active ? 1 : 0;
        $gid = intval($gatewayId);
        $cid = intval($companyId);
        $sql = "UPDATE payment_method SET is_active = $v WHERE id = $gid AND company_id = $cid AND is_gateway = 1";
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Save gateway config (upsert each key/value pair)
     */
    public function saveConfig(int $gatewayId, int $companyId, array $configs): bool
    {
        $success = true;
        foreach ($configs as $key => $value) {
            $key = sql_escape($key);
            $value = sql_escape($value);

            $checkSql = "SELECT id FROM payment_gateway_config 
                         WHERE payment_method_id = $gatewayId AND config_key = '$key' AND company_id = $companyId";
            $checkResult = mysqli_query($this->conn, $checkSql);

            if (mysqli_num_rows($checkResult) > 0) {
                $sql = "UPDATE payment_gateway_config SET config_value = '$value', updated_at = NOW() 
                        WHERE payment_method_id = $gatewayId AND config_key = '$key' AND company_id = $companyId";
            } else {
                $isEncrypted = in_array($key, ['client_secret', 'secret_key', 'webhook_secret']) ? 1 : 0;
                $sql = "INSERT INTO payment_gateway_config (payment_method_id, config_key, config_value, is_encrypted, company_id) 
                        VALUES ($gatewayId, '$key', '$value', $isEncrypted, $companyId)";
            }

            if (!mysqli_query($this->conn, $sql)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Test PayPal connection
     */
    public function testPayPal(array $configs): array
    {
        $mode = $configs['mode'] ?? 'sandbox';
        $clientId = $configs['client_id'] ?? '';
        $clientSecret = $configs['client_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            return ['success' => false, 'message' => 'Client ID and Client Secret are required'];
        }

        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'Connection error: ' . $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && !empty($data['access_token'])) {
            $modeLabel = $mode === 'live' ? 'Live' : 'Sandbox';
            return ['success' => true, 'message' => "PayPal API connection successful! Mode: {$modeLabel}. Token expires in {$data['expires_in']} seconds."];
        }

        $errorMessage = $data['error_description'] ?? $data['error'] ?? 'Invalid credentials';
        return ['success' => false, 'message' => 'PayPal API error: ' . $errorMessage];
    }

    /**
     * Test Stripe connection
     */
    public function testStripe(array $configs): array
    {
        $mode = $configs['mode'] ?? 'test';
        $secretKey = $configs['secret_key'] ?? '';

        if (empty($secretKey)) {
            return ['success' => false, 'message' => 'Secret Key is required'];
        }

        if ($mode === 'test' && strpos($secretKey, 'sk_test_') !== 0) {
            return ['success' => false, 'message' => 'Invalid test secret key format. Should start with sk_test_'];
        }
        if ($mode === 'live' && strpos($secretKey, 'sk_live_') !== 0) {
            return ['success' => false, 'message' => 'Invalid live secret key format. Should start with sk_live_'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.stripe.com/v1/balance',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey, 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'Connection error: ' . $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['object']) && $data['object'] === 'balance') {
            $modeLabel = $mode === 'live' ? 'Live' : 'Test';
            $balance = isset($data['available'][0])
                ? number_format($data['available'][0]['amount'] / 100, 2) . ' ' . strtoupper($data['available'][0]['currency'])
                : 'N/A';
            return ['success' => true, 'message' => "Stripe API connection successful! Mode: {$modeLabel}. Available balance: {$balance}"];
        }

        $errorMessage = $data['error']['message'] ?? 'Invalid API key';
        return ['success' => false, 'message' => 'Stripe API error: ' . $errorMessage];
    }

    /**
     * Test a gateway connection by code
     */
    public function testConnection(string $gatewayCode, array $configs): array
    {
        return match ($gatewayCode) {
            'paypal'    => $this->testPayPal($configs),
            'stripe'    => $this->testStripe($configs),
            'promptpay' => $this->testPromptPay($configs),
            default     => ['success' => false, 'message' => 'Unknown gateway: ' . $gatewayCode],
        };
    }

    /**
     * Test PromptPay configuration (validate PromptPay ID format)
     */
    public function testPromptPay(array $configs): array
    {
        $promptpayId = $configs['promptpay_id'] ?? '';
        if (empty($promptpayId)) {
            return ['success' => false, 'message' => 'PromptPay ID is required'];
        }

        // Clean and validate
        $clean = preg_replace('/[^0-9]/', '', $promptpayId);

        // Phone: 10 digits, NID: 13 digits, Tax ID: 13 digits
        if (strlen($clean) === 10 || strlen($clean) === 13) {
            $type = strlen($clean) === 10 ? 'Phone Number' : 'National ID / Tax ID';
            return ['success' => true, 'message' => "PromptPay ID is valid ({$type}: {$clean}). QR code generation ready."];
        }

        return ['success' => false, 'message' => 'Invalid PromptPay ID. Must be 10-digit phone or 13-digit national/tax ID.'];
    }

    /**
     * Get gateway field definitions
     */
    public static function getGatewayFields(): array
    {
        return [
            'paypal' => [
                'mode' => ['label' => 'Mode', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox (Testing)', 'live' => 'Live (Production)'], 'required' => true, 'help' => 'Use Sandbox for testing, Live for production.'],
                'client_id' => ['label' => 'Client ID', 'type' => 'text', 'placeholder' => 'Your PayPal Client ID', 'required' => true, 'help' => 'Get this from PayPal Developer Dashboard'],
                'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'placeholder' => 'Your PayPal Client Secret', 'required' => true, 'encrypted' => true, 'help' => 'Keep this secret!'],
                'webhook_id' => ['label' => 'Webhook ID', 'type' => 'text', 'placeholder' => 'PayPal Webhook ID (optional)', 'required' => false, 'help' => 'For receiving payment notifications'],
                'return_url' => ['label' => 'Return URL', 'type' => 'text', 'placeholder' => '/payment/paypal/success', 'required' => true, 'help' => 'URL after successful payment'],
                'cancel_url' => ['label' => 'Cancel URL', 'type' => 'text', 'placeholder' => '/payment/paypal/cancel', 'required' => true, 'help' => 'URL when payment is cancelled'],
            ],
            'stripe' => [
                'mode' => ['label' => 'Mode', 'type' => 'select', 'options' => ['test' => 'Test Mode', 'live' => 'Live (Production)'], 'required' => true, 'help' => 'Use Test for development, Live for production.'],
                'publishable_key' => ['label' => 'Publishable Key', 'type' => 'text', 'placeholder' => 'pk_test_... or pk_live_...', 'required' => true, 'help' => 'This key is safe to use in frontend JavaScript'],
                'secret_key' => ['label' => 'Secret Key', 'type' => 'password', 'placeholder' => 'sk_test_... or sk_live_...', 'required' => true, 'encrypted' => true, 'help' => 'Keep this secret! Never expose in frontend'],
                'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'whsec_... (optional)', 'required' => false, 'encrypted' => true, 'help' => 'For verifying webhook signatures'],
                'currency' => ['label' => 'Default Currency', 'type' => 'select', 'options' => ['thb' => 'THB - Thai Baht', 'usd' => 'USD - US Dollar', 'eur' => 'EUR - Euro', 'gbp' => 'GBP - British Pound'], 'required' => true, 'help' => 'Default currency for payments'],
            ],
            'promptpay' => [
                'promptpay_id' => ['label' => 'PromptPay ID', 'type' => 'text', 'placeholder' => 'Phone (0812345678) or National/Tax ID (1234567890123)', 'required' => true, 'help' => '10-digit phone number or 13-digit national/tax ID'],
                'promptpay_name' => ['label' => 'Account Name', 'type' => 'text', 'placeholder' => 'Name shown to customers', 'required' => true, 'help' => 'Display name for the PromptPay account holder'],
                'promptpay_auto_confirm' => ['label' => 'Auto Confirm', 'type' => 'select', 'options' => ['0' => 'Manual (Admin confirms)', '1' => 'Auto (with bank API)'], 'required' => false, 'help' => 'Manual: admin reviews transfer slip. Auto: requires bank API integration.'],
            ],
        ];
    }
}
