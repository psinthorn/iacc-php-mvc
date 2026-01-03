<?php
/**
 * PayPal Payment Service Class
 * Handles PayPal API integration for payments
 * 
 * @package IACC
 * @version 1.0.0
 */

class PayPalService {
    
    private $clientId;
    private $clientSecret;
    private $mode;
    private $baseUrl;
    private $accessToken;
    private $returnUrl;
    private $cancelUrl;
    private $webhookId;
    private $conn;
    
    /**
     * Constructor - Initialize PayPal service with database config
     */
    public function __construct($conn = null) {
        $this->conn = $conn ?? $GLOBALS['conn'];
        $this->loadConfig();
    }
    
    /**
     * Load configuration from database
     */
    private function loadConfig() {
        // Get PayPal payment method ID
        $sql = "SELECT id FROM payment_method WHERE code = 'paypal' AND is_active = 1";
        $result = mysqli_query($this->conn, $sql);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            throw new Exception('PayPal payment method not found or inactive');
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
        
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->mode = $config['mode'] ?? 'sandbox';
        $this->returnUrl = $config['return_url'] ?? '/payment/paypal/success';
        $this->cancelUrl = $config['cancel_url'] ?? '/payment/paypal/cancel';
        $this->webhookId = $config['webhook_id'] ?? '';
        
        // Set base URL based on mode
        $this->baseUrl = $this->mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
    }
    
    /**
     * Get OAuth2 Access Token
     */
    private function getAccessToken() {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('PayPal credentials not configured');
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('PayPal connection error: ' . $curlError);
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200 || empty($data['access_token'])) {
            $error = $data['error_description'] ?? $data['error'] ?? 'Failed to get access token';
            throw new Exception('PayPal auth error: ' . $error);
        }
        
        $this->accessToken = $data['access_token'];
        return $this->accessToken;
    }
    
    /**
     * Make API Request to PayPal
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $token = $this->getAccessToken();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: ' . uniqid('iacc_', true)
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('PayPal request error: ' . $curlError);
        }
        
        return [
            'httpCode' => $httpCode,
            'data' => json_decode($response, true),
            'raw' => $response
        ];
    }
    
    /**
     * Create a PayPal Order/Payment
     * 
     * @param array $orderData Order details
     * @return array Order creation response
     */
    public function createOrder($orderData) {
        $items = [];
        $itemTotal = 0;
        
        foreach ($orderData['items'] as $item) {
            $items[] = [
                'name' => $item['name'],
                'quantity' => strval($item['quantity']),
                'unit_amount' => [
                    'currency_code' => $orderData['currency'] ?? 'THB',
                    'value' => number_format($item['price'], 2, '.', '')
                ]
            ];
            $itemTotal += $item['price'] * $item['quantity'];
        }
        
        $requestData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $orderData['reference_id'] ?? uniqid('ord_'),
                    'description' => $orderData['description'] ?? 'IACC Payment',
                    'custom_id' => $orderData['custom_id'] ?? '',
                    'invoice_id' => $orderData['invoice_id'] ?? '',
                    'amount' => [
                        'currency_code' => $orderData['currency'] ?? 'THB',
                        'value' => number_format($orderData['total'], 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $orderData['currency'] ?? 'THB',
                                'value' => number_format($itemTotal, 2, '.', '')
                            ]
                        ]
                    ],
                    'items' => $items
                ]
            ],
            'application_context' => [
                'brand_name' => 'IACC System',
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
                'return_url' => $this->getAbsoluteUrl($this->returnUrl),
                'cancel_url' => $this->getAbsoluteUrl($this->cancelUrl)
            ]
        ];
        
        $response = $this->makeRequest('/v2/checkout/orders', 'POST', $requestData);
        
        if ($response['httpCode'] !== 201) {
            $error = $response['data']['details'][0]['description'] ?? 
                     $response['data']['message'] ?? 
                     'Failed to create order';
            throw new Exception('PayPal order error: ' . $error);
        }
        
        // Log payment attempt
        $this->logPaymentAttempt($orderData, $response['data']);
        
        return [
            'success' => true,
            'order_id' => $response['data']['id'],
            'status' => $response['data']['status'],
            'approval_url' => $this->getApprovalUrl($response['data']['links'])
        ];
    }
    
    /**
     * Capture Payment (Execute after customer approves)
     * 
     * @param string $orderId PayPal Order ID
     * @return array Capture response
     */
    public function capturePayment($orderId) {
        $response = $this->makeRequest("/v2/checkout/orders/{$orderId}/capture", 'POST');
        
        if ($response['httpCode'] !== 201) {
            $error = $response['data']['details'][0]['description'] ?? 
                     $response['data']['message'] ?? 
                     'Failed to capture payment';
            throw new Exception('PayPal capture error: ' . $error);
        }
        
        $captureData = $response['data'];
        
        // Update payment log
        $this->updatePaymentLog($orderId, $captureData);
        
        return [
            'success' => true,
            'order_id' => $captureData['id'],
            'status' => $captureData['status'],
            'payer' => $captureData['payer'] ?? [],
            'capture_id' => $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
            'amount' => $captureData['purchase_units'][0]['payments']['captures'][0]['amount'] ?? []
        ];
    }
    
    /**
     * Get Order Details
     * 
     * @param string $orderId PayPal Order ID
     * @return array Order details
     */
    public function getOrderDetails($orderId) {
        $response = $this->makeRequest("/v2/checkout/orders/{$orderId}", 'GET');
        
        if ($response['httpCode'] !== 200) {
            throw new Exception('Failed to get order details');
        }
        
        return $response['data'];
    }
    
    /**
     * Refund a captured payment
     * 
     * @param string $captureId Capture ID
     * @param float $amount Refund amount (null for full refund)
     * @param string $currency Currency code
     * @return array Refund response
     */
    public function refundPayment($captureId, $amount = null, $currency = 'THB') {
        $data = [];
        
        if ($amount !== null) {
            $data['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => $currency
            ];
        }
        
        $response = $this->makeRequest("/v2/payments/captures/{$captureId}/refund", 'POST', $data);
        
        if ($response['httpCode'] !== 201) {
            $error = $response['data']['message'] ?? 'Refund failed';
            throw new Exception('PayPal refund error: ' . $error);
        }
        
        return [
            'success' => true,
            'refund_id' => $response['data']['id'],
            'status' => $response['data']['status'],
            'amount' => $response['data']['amount'] ?? []
        ];
    }
    
    /**
     * Verify Webhook Signature
     * 
     * @param array $headers Request headers
     * @param string $body Raw request body
     * @return bool Verification result
     */
    public function verifyWebhook($headers, $body) {
        if (empty($this->webhookId)) {
            return false;
        }
        
        $verifyData = [
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id' => $this->webhookId,
            'webhook_event' => json_decode($body, true)
        ];
        
        $response = $this->makeRequest('/v1/notifications/verify-webhook-signature', 'POST', $verifyData);
        
        return $response['httpCode'] === 200 && 
               ($response['data']['verification_status'] ?? '') === 'SUCCESS';
    }
    
    /**
     * Handle Webhook Event
     * 
     * @param array $event Webhook event data
     * @return array Processing result
     */
    public function handleWebhook($event) {
        $eventType = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];
        
        switch ($eventType) {
            case 'CHECKOUT.ORDER.APPROVED':
                // Order was approved, capture payment
                return $this->handleOrderApproved($resource);
                
            case 'PAYMENT.CAPTURE.COMPLETED':
                // Payment was captured successfully
                return $this->handlePaymentCompleted($resource);
                
            case 'PAYMENT.CAPTURE.REFUNDED':
                // Payment was refunded
                return $this->handlePaymentRefunded($resource);
                
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                // Payment was denied
                return $this->handlePaymentDenied($resource);
                
            default:
                return ['handled' => false, 'message' => 'Unhandled event type: ' . $eventType];
        }
    }
    
    private function handleOrderApproved($resource) {
        // Log and process approved order
        return ['handled' => true, 'action' => 'order_approved', 'order_id' => $resource['id'] ?? ''];
    }
    
    private function handlePaymentCompleted($resource) {
        // Update receipt/voucher status in database
        $invoiceId = $resource['invoice_id'] ?? '';
        $captureId = $resource['id'] ?? '';
        $amount = $resource['amount']['value'] ?? 0;
        
        if ($invoiceId) {
            // Update related records
            // This would update your voucher/receipt tables
        }
        
        return [
            'handled' => true, 
            'action' => 'payment_completed',
            'capture_id' => $captureId,
            'amount' => $amount
        ];
    }
    
    private function handlePaymentRefunded($resource) {
        return ['handled' => true, 'action' => 'payment_refunded'];
    }
    
    private function handlePaymentDenied($resource) {
        return ['handled' => true, 'action' => 'payment_denied'];
    }
    
    /**
     * Get approval URL from links
     */
    private function getApprovalUrl($links) {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
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
    private function logPaymentAttempt($orderData, $response) {
        // Create payment_log table if needed and log the attempt
        $sql = "INSERT INTO payment_log (gateway, order_id, reference_id, amount, currency, status, request_data, response_data, created_at) 
                VALUES ('paypal', ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()";
        
        // Check if table exists
        $checkTable = mysqli_query($this->conn, "SHOW TABLES LIKE 'payment_log'");
        if (mysqli_num_rows($checkTable) === 0) {
            $this->createPaymentLogTable();
        }
        
        $stmt = mysqli_prepare($this->conn, $sql);
        $orderId = $response['id'] ?? '';
        $refId = $orderData['reference_id'] ?? '';
        $amount = $orderData['total'] ?? 0;
        $currency = $orderData['currency'] ?? 'THB';
        $status = $response['status'] ?? 'CREATED';
        $requestJson = json_encode($orderData);
        $responseJson = json_encode($response);
        
        mysqli_stmt_bind_param($stmt, "ssdssss", $orderId, $refId, $amount, $currency, $status, $requestJson, $responseJson);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Update payment log
     */
    private function updatePaymentLog($orderId, $captureData) {
        $sql = "UPDATE payment_log SET status = ?, response_data = ?, updated_at = NOW() WHERE order_id = ? AND gateway = 'paypal'";
        $stmt = mysqli_prepare($this->conn, $sql);
        $status = $captureData['status'] ?? '';
        $responseJson = json_encode($captureData);
        mysqli_stmt_bind_param($stmt, "sss", $status, $responseJson, $orderId);
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
     * Check if PayPal is properly configured
     */
    public function isConfigured() {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
    
    /**
     * Get current mode
     */
    public function getMode() {
        return $this->mode;
    }
}
