<?php
/**
 * Payment Webhook Handler
 * Handles webhook notifications from PayPal and Stripe
 */

// Don't include HTML headers
header('Content-Type: application/json');

// Get the gateway from query parameter
$gateway = $_GET['gateway'] ?? '';

// Get raw request data
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Log incoming webhook for debugging
$logFile = __DIR__ . '/logs/webhooks.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = date('Y-m-d H:i:s') . " | Gateway: {$gateway} | Headers: " . json_encode($headers) . " | Payload: {$payload}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

try {
    // Load database connection
    require_once __DIR__ . '/inc/sys.configs.php';
    require_once __DIR__ . '/inc/class.dbconn.php';
    
    $db = new DbConn($config);
    $conn = $db->conn;
    
    switch ($gateway) {
        case 'paypal':
            handlePayPalWebhook($conn, $headers, $payload);
            break;
            
        case 'stripe':
            handleStripeWebhook($conn, $headers, $payload);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown gateway']);
            exit;
    }
    
} catch (Exception $e) {
    // Log error
    $errorLog = date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorLog, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Handle PayPal Webhook
 */
function handlePayPalWebhook($conn, $headers, $payload) {
    require_once __DIR__ . '/inc/class.paypal.php';
    
    try {
        $paypal = new PayPalService($conn);
        
        // Parse the event
        $event = json_decode($payload, true);
        
        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }
        
        // Verify webhook signature (optional but recommended)
        // $isValid = $paypal->verifyWebhook($headers, $payload);
        
        // Process the event
        $result = $paypal->handleWebhook($event);
        
        // Log result
        $logEntry = date('Y-m-d H:i:s') . " | PayPal Result: " . json_encode($result) . "\n";
        file_put_contents(__DIR__ . '/logs/webhooks.log', $logEntry, FILE_APPEND);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'result' => $result]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle Stripe Webhook
 */
function handleStripeWebhook($conn, $headers, $payload) {
    require_once __DIR__ . '/inc/class.stripe.php';
    
    try {
        $stripe = new StripeService($conn);
        
        // Get Stripe signature header
        $sigHeader = $headers['Stripe-Signature'] ?? $headers['stripe-signature'] ?? '';
        
        // Verify and parse the event
        if ($stripe->isConfigured() && !empty($sigHeader)) {
            $event = $stripe->verifyWebhook($payload, $sigHeader);
        } else {
            // Fallback without signature verification (not recommended for production)
            $event = json_decode($payload, true);
        }
        
        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid event payload']);
            return;
        }
        
        // Process the event
        $result = $stripe->handleWebhook($event);
        
        // Log result
        $logEntry = date('Y-m-d H:i:s') . " | Stripe Result: " . json_encode($result) . "\n";
        file_put_contents(__DIR__ . '/logs/webhooks.log', $logEntry, FILE_APPEND);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'result' => $result]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
