<?php
/**
 * Payment Gateway Test Connection Endpoint
 * Tests API credentials for PayPal and Stripe
 */

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['test_connection'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$gateway = $_POST['gateway'] ?? '';
$configs = $_POST['config'] ?? [];

// Validate required fields
if (empty($gateway) || empty($configs)) {
    echo json_encode(['success' => false, 'message' => 'Missing configuration']);
    exit;
}

$result = ['success' => false, 'message' => 'Unknown error'];

try {
    switch ($gateway) {
        case 'paypal':
            $result = testPayPalConnection($configs);
            break;
            
        case 'stripe':
            $result = testStripeConnection($configs);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Unknown gateway: ' . $gateway];
    }
} catch (Exception $e) {
    $result = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

echo json_encode($result);
exit;

/**
 * Test PayPal API Connection
 */
function testPayPalConnection($configs) {
    $mode = $configs['mode'] ?? 'sandbox';
    $clientId = $configs['client_id'] ?? '';
    $clientSecret = $configs['client_secret'] ?? '';
    
    if (empty($clientId) || empty($clientSecret)) {
        return ['success' => false, 'message' => 'Client ID and Client Secret are required'];
    }
    
    // PayPal API endpoints
    $baseUrl = $mode === 'live' 
        ? 'https://api-m.paypal.com' 
        : 'https://api-m.sandbox.paypal.com';
    
    // Get OAuth token
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
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
        return ['success' => false, 'message' => 'Connection error: ' . $curlError];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && !empty($data['access_token'])) {
        $modeLabel = $mode === 'live' ? 'Live' : 'Sandbox';
        return [
            'success' => true, 
            'message' => "PayPal API connection successful! Mode: {$modeLabel}. Token expires in {$data['expires_in']} seconds."
        ];
    }
    
    $errorMessage = $data['error_description'] ?? $data['error'] ?? 'Invalid credentials';
    return ['success' => false, 'message' => 'PayPal API error: ' . $errorMessage];
}

/**
 * Test Stripe API Connection
 */
function testStripeConnection($configs) {
    $mode = $configs['mode'] ?? 'test';
    $secretKey = $configs['secret_key'] ?? '';
    
    if (empty($secretKey)) {
        return ['success' => false, 'message' => 'Secret Key is required'];
    }
    
    // Verify key format
    if ($mode === 'test' && strpos($secretKey, 'sk_test_') !== 0) {
        return ['success' => false, 'message' => 'Invalid test secret key format. Should start with sk_test_'];
    }
    if ($mode === 'live' && strpos($secretKey, 'sk_live_') !== 0) {
        return ['success' => false, 'message' => 'Invalid live secret key format. Should start with sk_live_'];
    }
    
    // Test Stripe API by getting account info
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.stripe.com/v1/balance',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
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
        return ['success' => false, 'message' => 'Connection error: ' . $curlError];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['object']) && $data['object'] === 'balance') {
        $modeLabel = $mode === 'live' ? 'Live' : 'Test';
        $balance = isset($data['available'][0]) 
            ? number_format($data['available'][0]['amount'] / 100, 2) . ' ' . strtoupper($data['available'][0]['currency'])
            : 'N/A';
        return [
            'success' => true, 
            'message' => "Stripe API connection successful! Mode: {$modeLabel}. Available balance: {$balance}"
        ];
    }
    
    $errorMessage = $data['error']['message'] ?? 'Invalid API key';
    return ['success' => false, 'message' => 'Stripe API error: ' . $errorMessage];
}
