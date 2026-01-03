<?php
/**
 * Stripe Payment Service Class
 * Handles Stripe API integration for payments
 * 
 * @package IACC
 * @version 1.0.0
 */

class StripeService {
    
    private $publishableKey;
    private $secretKey;
    private $webhookSecret;
    private $mode;
    private $currency;
    private $conn;
    
    const API_BASE_URL = 'https://api.stripe.com/v1';
    
    /**
     * Constructor - Initialize Stripe service with database config
     */
    public function __construct($conn = null) {
        $this->conn = $conn ?? $GLOBALS['conn'];
        $this->loadConfig();
    }
    
    /**
     * Load configuration from database
     */
    private function loadConfig() {
        // Get Stripe payment method ID
        $sql = "SELECT id FROM payment_method WHERE code = 'stripe' AND is_active = 1";
        $result = mysqli_query($this->conn, $sql);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            throw new Exception('Stripe payment method not found or inactive');
        }
        
        $paymentMethod = mysqli_fetch_assoc($result);
        $paymentMethodId = $paymentMethod['id'];
        
        // Get configuration
        $sql = "SELECT config_key, config_value FROM payment_gateway_config WHERE payment_method_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $paymentMethodId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $config = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $config[$row['config_key']] = $row['config_value'];
        }
        
        $this->publishableKey = $config['publishable_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->webhookSecret = $config['webhook_secret'] ?? '';
        $this->mode = $config['mode'] ?? 'test';
        $this->currency = $config['currency'] ?? 'thb';
    }
    
    /**
     * Make API Request to Stripe
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        if (empty($this->secretKey)) {
            throw new Exception('Stripe secret key not configured');
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: 2023-10-16'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_BASE_URL . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('Stripe request error: ' . $curlError);
        }
        
        return [
            'httpCode' => $httpCode,
            'data' => json_decode($response, true),
            'raw' => $response
        ];
    }
    
    /**
     * Create a Payment Intent
     * 
     * @param array $paymentData Payment details
     * @return array Payment Intent response
     */
    public function createPaymentIntent($paymentData) {
        // Convert amount to smallest currency unit (satang for THB, cents for USD)
        $amount = intval($paymentData['amount'] * 100);
        $currency = $paymentData['currency'] ?? $this->currency;
        
        $requestData = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'automatic_payment_methods[enabled]' => 'true',
            'metadata[reference_id]' => $paymentData['reference_id'] ?? '',
            'metadata[invoice_id]' => $paymentData['invoice_id'] ?? '',
            'metadata[customer_name]' => $paymentData['customer_name'] ?? '',
            'description' => $paymentData['description'] ?? 'IACC Payment'
        ];
        
        // Add receipt email if provided
        if (!empty($paymentData['email'])) {
            $requestData['receipt_email'] = $paymentData['email'];
        }
        
        $response = $this->makeRequest('/payment_intents', 'POST', $requestData);
        
        if ($response['httpCode'] !== 200) {
            $error = $response['data']['error']['message'] ?? 'Failed to create payment intent';
            throw new Exception('Stripe error: ' . $error);
        }
        
        // Log payment attempt
        $this->logPaymentAttempt($paymentData, $response['data']);
        
        return [
            'success' => true,
            'payment_intent_id' => $response['data']['id'],
            'client_secret' => $response['data']['client_secret'],
            'status' => $response['data']['status'],
            'amount' => $response['data']['amount'] / 100,
            'currency' => $response['data']['currency']
        ];
    }
    
    /**
     * Create a Checkout Session
     * For redirect-based payments
     * 
     * @param array $sessionData Session details
     * @return array Checkout Session response
     */
    public function createCheckoutSession($sessionData) {
        $lineItems = [];
        
        foreach ($sessionData['items'] as $index => $item) {
            $lineItems["line_items[{$index}][price_data][currency]"] = $sessionData['currency'] ?? $this->currency;
            $lineItems["line_items[{$index}][price_data][product_data][name]"] = $item['name'];
            $lineItems["line_items[{$index}][price_data][unit_amount]"] = intval($item['price'] * 100);
            $lineItems["line_items[{$index}][quantity]"] = $item['quantity'];
        }
        
        $requestData = array_merge([
            'mode' => 'payment',
            'success_url' => $this->getAbsoluteUrl($sessionData['success_url'] ?? '/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->getAbsoluteUrl($sessionData['cancel_url'] ?? '/payment/stripe/cancel'),
            'metadata[reference_id]' => $sessionData['reference_id'] ?? '',
            'metadata[invoice_id]' => $sessionData['invoice_id'] ?? ''
        ], $lineItems);
        
        if (!empty($sessionData['email'])) {
            $requestData['customer_email'] = $sessionData['email'];
        }
        
        $response = $this->makeRequest('/checkout/sessions', 'POST', $requestData);
        
        if ($response['httpCode'] !== 200) {
            $error = $response['data']['error']['message'] ?? 'Failed to create checkout session';
            throw new Exception('Stripe error: ' . $error);
        }
        
        return [
            'success' => true,
            'session_id' => $response['data']['id'],
            'checkout_url' => $response['data']['url'],
            'status' => $response['data']['status']
        ];
    }
    
    /**
     * Retrieve Payment Intent
     * 
     * @param string $paymentIntentId Payment Intent ID
     * @return array Payment Intent details
     */
    public function getPaymentIntent($paymentIntentId) {
        $response = $this->makeRequest('/payment_intents/' . $paymentIntentId, 'GET');
        
        if ($response['httpCode'] !== 200) {
            throw new Exception('Failed to retrieve payment intent');
        }
        
        return $response['data'];
    }
    
    /**
     * Retrieve Checkout Session
     * 
     * @param string $sessionId Checkout Session ID
     * @return array Session details
     */
    public function getCheckoutSession($sessionId) {
        $response = $this->makeRequest('/checkout/sessions/' . $sessionId . '?expand[]=payment_intent', 'GET');
        
        if ($response['httpCode'] !== 200) {
            throw new Exception('Failed to retrieve checkout session');
        }
        
        return $response['data'];
    }
    
    /**
     * Confirm Payment Intent (for server-side confirmation)
     * 
     * @param string $paymentIntentId Payment Intent ID
     * @param string $paymentMethodId Payment Method ID
     * @return array Confirmation response
     */
    public function confirmPaymentIntent($paymentIntentId, $paymentMethodId = null) {
        $data = [];
        
        if ($paymentMethodId) {
            $data['payment_method'] = $paymentMethodId;
        }
        
        $response = $this->makeRequest('/payment_intents/' . $paymentIntentId . '/confirm', 'POST', $data);
        
        if ($response['httpCode'] !== 200) {
            $error = $response['data']['error']['message'] ?? 'Payment confirmation failed';
            throw new Exception('Stripe error: ' . $error);
        }
        
        // Update payment log
        $this->updatePaymentLog($paymentIntentId, $response['data']);
        
        return [
            'success' => true,
            'payment_intent_id' => $response['data']['id'],
            'status' => $response['data']['status'],
            'amount' => $response['data']['amount'] / 100,
            'currency' => $response['data']['currency']
        ];
    }
    
    /**
     * Create a refund
     * 
     * @param string $paymentIntentId Payment Intent ID
     * @param float $amount Refund amount (null for full refund)
     * @return array Refund response
     */
    public function createRefund($paymentIntentId, $amount = null) {
        $data = [
            'payment_intent' => $paymentIntentId
        ];
        
        if ($amount !== null) {
            $data['amount'] = intval($amount * 100);
        }
        
        $response = $this->makeRequest('/refunds', 'POST', $data);
        
        if ($response['httpCode'] !== 200) {
            $error = $response['data']['error']['message'] ?? 'Refund failed';
            throw new Exception('Stripe refund error: ' . $error);
        }
        
        return [
            'success' => true,
            'refund_id' => $response['data']['id'],
            'status' => $response['data']['status'],
            'amount' => $response['data']['amount'] / 100,
            'currency' => $response['data']['currency']
        ];
    }
    
    /**
     * Verify Webhook Signature
     * 
     * @param string $payload Raw request body
     * @param string $sigHeader Stripe-Signature header
     * @return array Verified event data
     */
    public function verifyWebhook($payload, $sigHeader) {
        if (empty($this->webhookSecret)) {
            throw new Exception('Webhook secret not configured');
        }
        
        $sigParts = [];
        foreach (explode(',', $sigHeader) as $part) {
            $item = explode('=', $part, 2);
            if (count($item) === 2) {
                $sigParts[$item[0]] = $item[1];
            }
        }
        
        $timestamp = $sigParts['t'] ?? '';
        $signature = $sigParts['v1'] ?? '';
        
        if (empty($timestamp) || empty($signature)) {
            throw new Exception('Invalid webhook signature format');
        }
        
        // Verify timestamp (within 5 minutes)
        $tolerance = 300;
        if (abs(time() - intval($timestamp)) > $tolerance) {
            throw new Exception('Webhook timestamp outside tolerance zone');
        }
        
        // Compute expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSig = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        
        if (!hash_equals($expectedSig, $signature)) {
            throw new Exception('Invalid webhook signature');
        }
        
        return json_decode($payload, true);
    }
    
    /**
     * Handle Webhook Event
     * 
     * @param array $event Webhook event data
     * @return array Processing result
     */
    public function handleWebhook($event) {
        $eventType = $event['type'] ?? '';
        $data = $event['data']['object'] ?? [];
        
        switch ($eventType) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($data);
                
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($data);
                
            case 'checkout.session.completed':
                return $this->handleCheckoutCompleted($data);
                
            case 'charge.refunded':
                return $this->handleChargeRefunded($data);
                
            default:
                return ['handled' => false, 'message' => 'Unhandled event type: ' . $eventType];
        }
    }
    
    private function handlePaymentSucceeded($data) {
        $paymentIntentId = $data['id'] ?? '';
        $amount = ($data['amount'] ?? 0) / 100;
        $metadata = $data['metadata'] ?? [];
        
        // Update related records if invoice_id is in metadata
        if (!empty($metadata['invoice_id'])) {
            // Update your voucher/receipt tables here
        }
        
        // Update payment log
        $this->updatePaymentLog($paymentIntentId, $data);
        
        return [
            'handled' => true,
            'action' => 'payment_succeeded',
            'payment_intent_id' => $paymentIntentId,
            'amount' => $amount
        ];
    }
    
    private function handlePaymentFailed($data) {
        $paymentIntentId = $data['id'] ?? '';
        $error = $data['last_payment_error']['message'] ?? 'Unknown error';
        
        // Update payment log with failure
        $this->updatePaymentLog($paymentIntentId, $data);
        
        return [
            'handled' => true,
            'action' => 'payment_failed',
            'payment_intent_id' => $paymentIntentId,
            'error' => $error
        ];
    }
    
    private function handleCheckoutCompleted($data) {
        $sessionId = $data['id'] ?? '';
        $paymentIntentId = $data['payment_intent'] ?? '';
        $metadata = $data['metadata'] ?? [];
        
        return [
            'handled' => true,
            'action' => 'checkout_completed',
            'session_id' => $sessionId,
            'payment_intent_id' => $paymentIntentId
        ];
    }
    
    private function handleChargeRefunded($data) {
        return [
            'handled' => true,
            'action' => 'charge_refunded',
            'charge_id' => $data['id'] ?? ''
        ];
    }
    
    /**
     * Get absolute URL
     */
    private function getAbsoluteUrl($path) {
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $protocol . '://' . $host . $path;
    }
    
    /**
     * Log payment attempt
     */
    private function logPaymentAttempt($paymentData, $response) {
        // Check if table exists
        $checkTable = mysqli_query($this->conn, "SHOW TABLES LIKE 'payment_log'");
        if (mysqli_num_rows($checkTable) === 0) {
            $this->createPaymentLogTable();
        }
        
        $sql = "INSERT INTO payment_log (gateway, order_id, reference_id, amount, currency, status, request_data, response_data, created_at) 
                VALUES ('stripe', ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        $orderId = $response['id'] ?? '';
        $refId = $paymentData['reference_id'] ?? '';
        $amount = $paymentData['amount'] ?? 0;
        $currency = $paymentData['currency'] ?? $this->currency;
        $status = $response['status'] ?? 'created';
        $requestJson = json_encode($paymentData);
        $responseJson = json_encode($response);
        
        mysqli_stmt_bind_param($stmt, "ssdssss", $orderId, $refId, $amount, $currency, $status, $requestJson, $responseJson);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Update payment log
     */
    private function updatePaymentLog($paymentIntentId, $data) {
        $sql = "UPDATE payment_log SET status = ?, response_data = ?, updated_at = NOW() WHERE order_id = ? AND gateway = 'stripe'";
        $stmt = mysqli_prepare($this->conn, $sql);
        $status = $data['status'] ?? '';
        $responseJson = json_encode($data);
        mysqli_stmt_bind_param($stmt, "sss", $status, $responseJson, $paymentIntentId);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Create payment log table
     */
    private function createPaymentLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS payment_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gateway VARCHAR(50) NOT NULL,
            order_id VARCHAR(100) NOT NULL,
            reference_id VARCHAR(100),
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency VARCHAR(3) DEFAULT 'THB',
            status VARCHAR(50),
            request_data TEXT,
            response_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_gateway (gateway),
            INDEX idx_order_id (order_id),
            INDEX idx_reference_id (reference_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        mysqli_query($this->conn, $sql);
    }
    
    /**
     * Check if Stripe is properly configured
     */
    public function isConfigured() {
        return !empty($this->secretKey);
    }
    
    /**
     * Get current mode
     */
    public function getMode() {
        return $this->mode;
    }
    
    /**
     * Get publishable key (for client-side)
     */
    public function getPublishableKey() {
        return $this->publishableKey;
    }
    
    /**
     * Get default currency
     */
    public function getCurrency() {
        return $this->currency;
    }
}
