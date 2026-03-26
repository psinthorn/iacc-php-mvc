<?php
/**
 * iACC Booking API Entry Point
 * 
 * Separate from index.php — handles REST API requests only.
 * No session, no cookies, no HTML — just JSON.
 * 
 * URL:    /api.php/v1/{resource}[/{id}]
 * Auth:   X-API-Key + X-API-Secret headers
 * 
 * Endpoints:
 *   POST   /api.php/v1/bookings          Create a booking
 *   GET    /api.php/v1/bookings           List bookings
 *   GET    /api.php/v1/bookings/{id}      Get booking by ID
 *   PUT    /api.php/v1/bookings/{id}      Update a booking
 *   DELETE /api.php/v1/bookings/{id}      Cancel booking
 *   POST   /api.php/v1/bookings/{id}/retry  Retry failed booking
 *   GET    /api.php/v1/subscription       Subscription info & usage
 *   POST   /api.php/v1/webhooks           Register a webhook
 *   GET    /api.php/v1/webhooks            List webhooks
 *   DELETE /api.php/v1/webhooks/{id}       Delete a webhook
 * 
 * Example:
 *   curl -X POST http://localhost/api.php/v1/bookings \
 *     -H "Content-Type: application/json" \
 *     -H "X-API-Key: iACC_abc123..." \
 *     -H "X-API-Secret: def456..." \
 *     -d '{"guest_name":"John Doe","check_in":"2026-04-01","check_out":"2026-04-03","room_type":"Deluxe","total_amount":5000}'
 */

// Error handling — log but never show in API responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// UTF-8
mb_internal_encoding('UTF-8');

// CORS headers — allow any origin for API access
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-API-Secret, X-Idempotency-Key');
header('Access-Control-Max-Age: 86400');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// Load core dependencies (minimal — no session, no XML, no menu)
// ============================================================
require_once __DIR__ . '/inc/sys.configs.php';
require_once __DIR__ . '/inc/class.dbconn.php';
require_once __DIR__ . '/inc/security.php';

// Initialize database (no session check)
$db = new DbConn($config);

// Load DB abstraction (HardClass)
require_once __DIR__ . '/inc/class.hard.php';

// ============================================================
// Parse URL path: /api.php/v1/{resource}[/{id}]
// ============================================================
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$pathParts = array_values(array_filter(explode('/', $pathInfo)));

// Validate version prefix
if (empty($pathParts) || $pathParts[0] !== 'v1') {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'INVALID_VERSION',
            'message' => 'API version not found. Use /api.php/v1/{resource}',
        ],
    ]);
    exit;
}

$resource   = $pathParts[1] ?? '';
$resourceId = isset($pathParts[2]) ? intval($pathParts[2]) : null;
$subAction  = $pathParts[3] ?? null;
$method     = $_SERVER['REQUEST_METHOD'];

if (empty($resource)) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'name'    => 'iACC Sales Channel API',
            'version' => 'v1',
            'endpoints' => [
                'POST /api.php/v1/bookings'              => 'Create a booking',
                'GET /api.php/v1/bookings'               => 'List bookings',
                'GET /api.php/v1/bookings/{id}'           => 'Get booking by ID',
                'PUT /api.php/v1/bookings/{id}'           => 'Update a booking',
                'DELETE /api.php/v1/bookings/{id}'        => 'Cancel a booking',
                'POST /api.php/v1/bookings/{id}/retry'    => 'Retry failed booking',
                'GET /api.php/v1/subscription'            => 'Subscription info & usage',
                'POST /api.php/v1/webhooks'              => 'Register a webhook',
                'GET /api.php/v1/webhooks'               => 'List webhooks',
                'DELETE /api.php/v1/webhooks/{id}'        => 'Delete a webhook',
            ],
            'features' => [
                'rate_limiting'  => 'Per-minute limits by plan (X-RateLimit headers)',
                'idempotency'   => 'Send X-Idempotency-Key header to prevent duplicates',
                'webhooks'      => 'Real-time notifications on booking status changes',
                'key_rotation'  => 'Rotate API keys with grace period via admin panel',
            ],
            'docs' => '/index.php?page=api_docs',
        ],
    ]);
    exit;
}

// ============================================================
// Load and dispatch to BookingApiController
// ============================================================
require_once __DIR__ . '/app/Models/BaseModel.php';
require_once __DIR__ . '/app/Models/ApiKey.php';
require_once __DIR__ . '/app/Models/ApiUsageLog.php';
require_once __DIR__ . '/app/Models/Booking.php';
require_once __DIR__ . '/app/Models/Subscription.php';
require_once __DIR__ . '/app/Models/Webhook.php';
require_once __DIR__ . '/app/Services/BookingService.php';
require_once __DIR__ . '/app/Controllers/BookingApiController.php';

$controller = new \App\Controllers\BookingApiController();
$controller->handleRequest($method, $resource, $resourceId, $subAction);
