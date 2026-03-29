<?php
/**
 * iACC Template — Booking Handler
 * Receives booking form submissions and creates orders via iACC API
 * 
 * POST book.php → Creates order via POST /api.php/v1/orders
 *   Maps booking form fields to iACC channel_orders format
 *   Saves a local copy in SQLite for confirmation pages
 *   Returns JSON response
 */
session_start();
header('Content-Type: application/json');

$config = require __DIR__ . '/config.php';

if (!($config['configured'] ?? false)) {
    echo json_encode(['success' => false, 'message' => 'Template not configured']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

require_once __DIR__ . '/includes/api-client.php';
require_once __DIR__ . '/includes/database.php';

// Collect and validate booking data
$guestName  = trim($_POST['guest_name'] ?? '');
$guestEmail = trim($_POST['guest_email'] ?? '');
$guestPhone = trim($_POST['guest_phone'] ?? '');
$productId  = intval($_POST['product_id'] ?? 0);
$checkIn    = trim($_POST['check_in'] ?? '');
$checkOut   = trim($_POST['check_out'] ?? '');
$guests     = intval($_POST['guests'] ?? 1);
$notes      = trim($_POST['notes'] ?? '');

// Validation
$errors = [];
if (empty($guestName))  $errors[] = 'Guest name is required';
if (empty($guestEmail) && empty($guestPhone)) $errors[] = 'Email or phone is required';
if ($productId <= 0)     $errors[] = 'Please select a product';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Load product from local DB to get name/price
$db = new LocalDatabase();
$product = $db->getProduct($productId);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found. Please sync products.']);
    exit;
}

// Calculate total amount
// For tour packages: total = price × number of guests
$totalAmount = floatval($product['price']) * max(1, $guests);

// Build order payload for iACC API
$orderData = [
    'guest_name'   => $guestName,
    'guest_email'  => $guestEmail,
    'guest_phone'  => $guestPhone,
    'check_in'     => $checkIn ?: date('Y-m-d'),
    'check_out'    => $checkOut ?: date('Y-m-d', strtotime('+1 day')),
    'room_type'    => $product['name'],  // Maps to product description in PO
    'guests'       => $guests,
    'total_amount' => $totalAmount,
    'currency'     => $config['currency'] ?? 'THB',
    'notes'        => $notes ?: "Booked via website — {$product['name']}",
    'channel'      => 'website',
];

// Create order via iACC API
$client = new IaccApiClient($config['api_url'], $config['api_key'], $config['api_secret']);
$result = $client->createOrder($orderData);

if ($result['success'] ?? false) {
    $orderData_resp = $result['data'] ?? [];
    $orderId = $orderData_resp['order_id'] ?? null;
    $poId    = $orderData_resp['po_id'] ?? null;

    // Save locally in SQLite
    $db->saveBooking([
        'product_id'   => $productId,
        'guest_name'   => $guestName,
        'guest_email'  => $guestEmail,
        'guest_phone'  => $guestPhone,
        'check_in'     => $orderData['check_in'],
        'check_out'    => $orderData['check_out'],
        'guests'       => $guests,
        'total_amount' => $totalAmount,
        'currency'     => $orderData['currency'],
        'status'       => 'confirmed',
        'iacc_order_id' => $orderId,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking confirmed!',
        'booking' => [
            'order_id'     => $orderId,
            'po_id'        => $poId,
            'reference'    => 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
            'product'      => $product['name'],
            'guest_name'   => $guestName,
            'guests'       => $guests,
            'total_amount' => number_format($totalAmount, 2),
            'currency'     => $orderData['currency'],
            'check_in'     => $orderData['check_in'],
            'check_out'    => $orderData['check_out'],
            'status'       => $orderData_resp['status'] ?? 'confirmed',
        ],
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Booking failed: ' . ($client->getLastError() ?: 'Unknown error'),
    ]);
}
