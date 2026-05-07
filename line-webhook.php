<?php
/**
 * LINE OA Webhook Endpoint
 * 
 * URL: /line-webhook.php
 * 
 * Receives events from LINE Platform and processes them:
 * - message: Text, image, sticker → auto-reply, order creation
 * - follow: New friend → welcome message
 * - unfollow: User blocked bot
 * - postback: Button actions (confirm order, etc.)
 * 
 * Configure in LINE Developers Console:
 *   Webhook URL: https://your-domain.com/line-webhook.php?company_id={ID}
 * 
 * Security: Validates X-Line-Signature header using channel secret
 */

// No session needed — this is a webhook
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Load core dependencies
require_once __DIR__ . '/inc/sys.configs.php';
require_once __DIR__ . '/inc/class.dbconn.php';
require_once __DIR__ . '/inc/class.hard.php';
// inc/security.php — needed by App\Models\TourBooking::createBooking and other
// model methods that use sql_escape() / sql_int() / sql_float(). The normal
// page bootstrap (index.php) loads this; the webhook bootstrap was missing it,
// which caused a fatal "Call to undefined function sql_escape()" inside
// agent text-template booking writes.
require_once __DIR__ . '/inc/security.php';

// Autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

// Initialize database
$db = new DbConn($config);

// Get company_id from query parameter
$companyId = intval($_GET['company_id'] ?? 0);
if ($companyId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing company_id']);
    exit;
}

// Load LINE config for this company
$lineModel = new \App\Models\LineOA();
$lineConfig = $lineModel->getConfig($companyId);

if (!$lineConfig || !$lineConfig['is_active']) {
    http_response_code(404);
    echo json_encode(['error' => 'LINE OA not configured or inactive']);
    exit;
}

// Read request body
$body = file_get_contents('php://input');
if (empty($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty body']);
    exit;
}

// Validate signature
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
$lineService = new \App\Services\LineService(
    $lineConfig['channel_access_token'],
    $lineConfig['channel_secret']
);

if (!$lineService->validateSignature($body, $signature)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Parse events
$payload = json_decode($body, true);
$events = $payload['events'] ?? [];

foreach ($events as $event) {
    try {
        processEvent($event, $companyId, $lineModel, $lineService, $lineConfig);
    } catch (\Exception $e) {
        // Log error but continue processing other events
        $lineModel->logWebhookEvent($companyId, 'error', json_encode([
            'event' => $event,
            'error' => $e->getMessage()
        ]));
    }
}

// Always return 200 to LINE
http_response_code(200);
echo json_encode(['status' => 'ok']);

// ========== Event Processing ==========

function processEvent(array $event, int $companyId, \App\Models\LineOA $model, \App\Services\LineService $service, array $config): void
{
    $eventType = $event['type'] ?? '';
    $lineUserId = $event['source']['userId'] ?? '';

    // Log raw event
    $model->logWebhookEvent($companyId, $eventType, json_encode($event));

    if (empty($lineUserId)) return;

    // Get or create LINE user profile
    $profile = $service->getUserProfile($lineUserId);
    $displayName = $profile['displayName'] ?? '';
    $pictureUrl = $profile['pictureUrl'] ?? '';
    $dbUserId = $model->findOrCreateLineUser($companyId, $lineUserId, $displayName, $pictureUrl);

    switch ($eventType) {
        case 'message':
            handleMessage($event, $companyId, $dbUserId, $model, $service, $config);
            break;

        case 'follow':
            handleFollow($event, $companyId, $dbUserId, $displayName, $model, $service, $config);
            break;

        case 'unfollow':
            // User blocked the bot — just log it
            break;

        case 'postback':
            handlePostback($event, $companyId, $dbUserId, $model, $service);
            break;
    }
}

function handleMessage(array $event, int $companyId, int $dbUserId, \App\Models\LineOA $model, \App\Services\LineService $service, array $config): void
{
    $message = $event['message'] ?? [];
    $messageType = $message['type'] ?? 'text';
    $messageId = $message['id'] ?? null;
    $replyToken = $event['replyToken'] ?? '';
    $content = null;
    $mediaUrl = null;

    switch ($messageType) {
        case 'text':
            $content = $message['text'] ?? '';
            break;
        case 'image':
            $content = '[Image]';
            $mediaUrl = 'line://message/' . $messageId;
            break;
        case 'sticker':
            $content = '[Sticker] packageId:' . ($message['packageId'] ?? '') . ' stickerId:' . ($message['stickerId'] ?? '');
            break;
        case 'location':
            $content = json_encode([
                'title' => $message['title'] ?? '',
                'address' => $message['address'] ?? '',
                'latitude' => $message['latitude'] ?? 0,
                'longitude' => $message['longitude'] ?? 0
            ]);
            break;
        default:
            $content = '[' . $messageType . ']';
    }

    // Log inbound message
    $model->logMessage($companyId, $dbUserId, 'inbound', $messageType, $messageId, $replyToken, $content, $mediaUrl);

    // Handle image as potential payment slip
    if ($messageType === 'image') {
        handlePaymentSlip($event, $companyId, $dbUserId, $messageId, $model, $service);
        return;
    }

    // Text message processing
    if ($messageType === 'text' && !empty($content)) {
        // Check for order keywords
        $lowerContent = mb_strtolower($content);

        // v6.3 #120 — Agent text-template booking (intercept before legacy commands).
        // ingestText() returns handled=false when no booking trigger, so we fall
        // through to the existing order/book/status/auto-reply chain.
        $lineUserIdStr = $event['source']['userId'] ?? '';
        if ($lineUserIdStr !== '') {
            $agentResult = \App\Controllers\LineAgentController::ingestText($companyId, $content, $lineUserIdStr);
            if (!empty($agentResult['handled'])) {
                if (!empty($agentResult['reply_messages'])) {
                    $service->replyMessage($replyToken, $agentResult['reply_messages']);
                    foreach ($agentResult['reply_messages'] as $msg) {
                        $msgType = $msg['type'] ?? 'text';
                        $msgContent = $msgType === 'text' ? ($msg['text'] ?? '') : json_encode($msg);
                        $model->logMessage($companyId, $dbUserId, 'outbound', $msgType, null, null, $msgContent);
                    }
                }
                return;
            }
        }

        // Order command: "order <items>"
        if (preg_match('/^(order|สั่ง|สั่งซื้อ)\s+(.+)/iu', $content, $matches)) {
            handleOrderCommand($replyToken, $companyId, $dbUserId, $matches[2], $model, $service);
            return;
        }

        // Booking command: "book <date> <time>"
        if (preg_match('/^(book|booking|จอง|จองคิว)\s+(.+)/iu', $content, $matches)) {
            handleBookingCommand($replyToken, $companyId, $dbUserId, $matches[2], $model, $service);
            return;
        }

        // Status check: "status <ref>"
        if (preg_match('/^(status|สถานะ)\s*(LINE-[\w-]+)?/iu', $content, $matches)) {
            handleStatusCommand($replyToken, $companyId, $dbUserId, $matches[2] ?? '', $model, $service);
            return;
        }

        // Auto-reply matching
        if ($config['auto_reply_enabled']) {
            $reply = $model->findMatchingReply($companyId, $content);
            if ($reply) {
                $service->replyText($replyToken, $reply['reply_content']);
                $model->logMessage($companyId, $dbUserId, 'outbound', 'text', null, null, $reply['reply_content']);
                return;
            }
        }

        // Default reply
        $defaultReply = "Thank you for your message! 🙏\n\nAvailable commands:\n• order <items> - Place an order\n• book <date> <time> - Make a booking\n• status - Check order status\n\nOr type your question and our team will respond shortly.";
        $service->replyText($replyToken, $defaultReply);
        $model->logMessage($companyId, $dbUserId, 'outbound', 'text', null, null, $defaultReply);
    }
}

function handleFollow(array $event, int $companyId, int $dbUserId, string $displayName, \App\Models\LineOA $model, \App\Services\LineService $service, array $config): void
{
    $replyToken = $event['replyToken'] ?? '';
    $greeting = $config['greeting_message'] ?? "Welcome {NAME}! 🎉\n\nThank you for adding us as a friend.\n\nYou can:\n• Send \"order\" to place an order\n• Send \"book\" to make a booking\n• Send a payment slip image\n\nWe're here to help!";
    $greeting = str_replace('{NAME}', $displayName, $greeting);

    $service->replyText($replyToken, $greeting);
    $model->logMessage($companyId, $dbUserId, 'outbound', 'text', null, null, $greeting);
}

function handlePostback(array $event, int $companyId, int $dbUserId, \App\Models\LineOA $model, \App\Services\LineService $service): void
{
    $replyToken = $event['replyToken'] ?? '';
    $postbackData = $event['postback']['data'] ?? '';
    parse_str($postbackData, $params);

    $action = $params['action'] ?? '';

    switch ($action) {
        case 'confirm_order':
            $orderRef = $params['ref'] ?? '';
            $service->replyText($replyToken, "Order {$orderRef} confirmed! ✅\nWe will process it shortly.");
            break;

        case 'cancel_order':
            $orderRef = $params['ref'] ?? '';
            $service->replyText($replyToken, "Order {$orderRef} has been cancelled. ❌");
            break;

        default:
            $service->replyText($replyToken, "Action received. Thank you!");
    }
}

function handleOrderCommand(string $replyToken, int $companyId, int $dbUserId, string $itemsText, \App\Models\LineOA $model, \App\Services\LineService $service): void
{
    // Parse simple item format: "item1 x2, item2 x1"
    $items = [];
    $parts = preg_split('/[,;]\s*/', $itemsText);
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;

        if (preg_match('/^(.+?)\s*[xX×]\s*(\d+)$/u', $part, $m)) {
            $items[] = ['name' => trim($m[1]), 'qty' => (int)$m[2], 'price' => 0];
        } else {
            $items[] = ['name' => $part, 'qty' => 1, 'price' => 0];
        }
    }

    $lineUser = $model->getLineUserById($dbUserId);
    $orderId = $model->createOrder($companyId, $dbUserId, [
        'order_type' => 'customer_order',
        'guest_name' => $lineUser['display_name'] ?? '',
        'items' => $items
    ]);

    $order = $model->getOrder($orderId, $companyId);
    $orderRef = $order['order_ref'] ?? 'LINE-ORDER';

    // Send flex message confirmation
    $flex = $service->buildOrderConfirmFlex($orderRef, $items, 0);
    $service->replyMessage($replyToken, [
        ['type' => 'flex', 'altText' => 'Order ' . $orderRef, 'contents' => $flex]
    ]);
    $model->logMessage($companyId, $dbUserId, 'outbound', 'flex', null, null, 'Order created: ' . $orderRef);
}

function handleBookingCommand(string $replyToken, int $companyId, int $dbUserId, string $bookingText, \App\Models\LineOA $model, \App\Services\LineService $service): void
{
    // Parse booking: "2026-04-15 14:00" or "15 Apr 2pm"
    $bookingDate = null;
    $bookingTime = null;

    // Try standard format first
    if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{1,2}:\d{2})/', $bookingText, $m)) {
        $bookingDate = $m[1];
        $bookingTime = $m[2];
    } else {
        // Try to parse naturally
        $ts = strtotime($bookingText);
        if ($ts && $ts > time()) {
            $bookingDate = date('Y-m-d', $ts);
            $bookingTime = date('H:i', $ts);
        }
    }

    if (!$bookingDate) {
        $service->replyText($replyToken, "Please provide a valid date and time.\nFormat: book 2026-04-15 14:00");
        return;
    }

    $lineUser = $model->getLineUserById($dbUserId);
    $orderId = $model->createOrder($companyId, $dbUserId, [
        'order_type' => 'booking',
        'guest_name' => $lineUser['display_name'] ?? '',
        'booking_date' => $bookingDate,
        'booking_time' => $bookingTime
    ]);

    $order = $model->getOrder($orderId, $companyId);
    $orderRef = $order['order_ref'] ?? 'LINE-BOOKING';

    $flex = $service->buildBookingFlex($orderRef, $bookingDate, $bookingTime, $lineUser['display_name'] ?? '');
    $service->replyMessage($replyToken, [
        ['type' => 'flex', 'altText' => 'Booking ' . $orderRef, 'contents' => $flex]
    ]);
    $model->logMessage($companyId, $dbUserId, 'outbound', 'flex', null, null, 'Booking created: ' . $orderRef);
}

function handleStatusCommand(string $replyToken, int $companyId, int $dbUserId, string $orderRef, \App\Models\LineOA $model, \App\Services\LineService $service): void
{
    if (empty($orderRef)) {
        // Get latest order for this user
        $orders = $model->getOrders($companyId, null, null, 1);
        if (empty($orders)) {
            $service->replyText($replyToken, "No orders found.");
            return;
        }
        $order = $orders[0];
    } else {
        // TODO: Add getOrderByRef method if needed
        $service->replyText($replyToken, "Looking up order {$orderRef}...");
        return;
    }

    $statusEmoji = [
        'pending' => '⏳', 'confirmed' => '✅', 'processing' => '🔄',
        'completed' => '🎉', 'cancelled' => '❌'
    ];
    $emoji = $statusEmoji[$order['status']] ?? '📋';

    $reply = "{$emoji} Order: {$order['order_ref']}\nStatus: {$order['status']}\nType: {$order['order_type']}\nCreated: {$order['created_at']}";
    if ($order['total_amount'] > 0) {
        $reply .= "\nAmount: {$order['currency']} " . number_format($order['total_amount'], 2);
    }

    $service->replyText($replyToken, $reply);
    $model->logMessage($companyId, $dbUserId, 'outbound', 'text', null, null, $reply);
}

function handlePaymentSlip(array $event, int $companyId, int $dbUserId, string $messageId, \App\Models\LineOA $model, \App\Services\LineService $service): void
{
    $replyToken = $event['replyToken'] ?? '';

    // Save image reference (actual download handled by admin when reviewing)
    $mediaUrl = 'line://content/' . $messageId;

    // Find latest pending order for this user
    $orders = $model->getOrders($companyId, 'pending');
    $matchedOrder = null;
    foreach ($orders as $order) {
        if ($order['line_user_id'] == $dbUserId) {
            $matchedOrder = $order;
            break;
        }
    }

    if ($matchedOrder) {
        $model->updatePaymentStatus($matchedOrder['id'], $companyId, 'slip_uploaded', $mediaUrl);
        $orderRef = $matchedOrder['order_ref'];

        $flex = $service->buildPaymentReceivedFlex($orderRef, $matchedOrder['total_amount'], $matchedOrder['currency']);
        $service->replyMessage($replyToken, [
            ['type' => 'flex', 'altText' => 'Payment received for ' . $orderRef, 'contents' => $flex]
        ]);
    } else {
        $service->replyText($replyToken, "Thank you for the payment slip! 🧾\nOur team will review it shortly.");
    }

    $model->logMessage($companyId, $dbUserId, 'outbound', 'text', null, null, 'Payment slip acknowledgement sent');
}
