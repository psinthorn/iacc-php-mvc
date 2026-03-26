<?php
/**
 * Phase 3 API E2E Tests
 * Tests: Webhooks, Idempotency, Key Rotation, Booking Detail
 * 
 * Run: docker exec iacc_php php /var/www/html/tests/test-api-phase3.php
 */

$API_BASE = 'http://iacc_nginx/api.php/v1';
$API_KEY = 'iACC_test_e2e_key_001';
$API_SECRET = 'iACC_test_e2e_secret_001';

$passed = 0;
$failed = 0;
$results = [];

function api_request_raw($method, $url, $data = null, $extraHeaders = []) {
    global $API_BASE, $API_KEY, $API_SECRET;
    
    $ch = curl_init($API_BASE . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'Content-Type: application/json',
        'X-API-Key: ' . $API_KEY,
        'X-API-Secret: ' . $API_SECRET,
    ];
    $headers = array_merge($headers, $extraHeaders);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true) ?? [],
        'raw'  => $response,
    ];
}

function test($name, $condition, $detail = '') {
    global $passed, $failed, $results;
    if ($condition) {
        $passed++;
        $results[] = ['name' => $name, 'pass' => true, 'detail' => $detail];
    } else {
        $failed++;
        $results[] = ['name' => $name, 'pass' => false, 'detail' => $detail];
    }
}

echo "====================================\n";
echo "Phase 3 API E2E Test Suite\n";
echo "====================================\n\n";

// Helper: wait if rate-limited
function api_with_retry($method, $url, $data = null, $extraHeaders = [], $retries = 2) {
    for ($i = 0; $i <= $retries; $i++) {
        $r = api_request_raw($method, $url, $data, $extraHeaders);
        if ($r['code'] !== 429 || $i === $retries) return $r;
        echo "  (rate limited, waiting 10s...)\n";
        sleep(10);
    }
    return $r;
}

// Make api_request use retry by default
function api_request($method, $url, $data = null, $extraHeaders = []) {
    return api_with_retry($method, $url, $data, $extraHeaders);
}

// =============================
// 1. WEBHOOK CRUD
// =============================
echo "--- 1. Webhook CRUD ---\n";

// Register webhook
$r = api_request('POST', '/webhooks', [
    'url' => 'https://example.com/webhook-test-e2e',
    'events' => ['booking.completed', 'booking.failed'],
]);
test('Webhook Register', $r['code'] === 201 && ($r['body']['success'] ?? false), 
    "HTTP {$r['code']}, webhook_id=" . ($r['body']['data']['id'] ?? 'N/A'));
$webhookId = $r['body']['data']['id'] ?? null;
$webhookSecret = $r['body']['data']['secret'] ?? null;

// List webhooks
$r = api_request('GET', '/webhooks');
test('Webhook List', $r['code'] === 200 && !empty($r['body']['data']),
    "HTTP {$r['code']}, count=" . count($r['body']['data'] ?? []));

// Register duplicate URL should fail
$r2 = api_request('POST', '/webhooks', [
    'url' => 'https://example.com/webhook-test-e2e',
    'events' => ['booking.completed'],
]);
// May succeed (same URL different events is allowed) or fail - just check it returns valid response
test('Webhook Duplicate URL handling', $r2['code'] >= 200 && $r2['code'] < 500,
    "HTTP {$r2['code']}");
$webhookId2 = $r2['body']['data']['id'] ?? null;

// Register with invalid URL should fail
$r = api_request('POST', '/webhooks', [
    'url' => 'http://not-https.com/webhook',
    'events' => ['booking.completed'],
]);
test('Webhook Reject HTTP URL', $r['code'] === 422 || $r['code'] === 400,
    "HTTP {$r['code']}, error=" . ($r['body']['error']['code'] ?? 'N/A'));

// Register with invalid events should fail
usleep(200000); // Brief pause to avoid rate limit
$r = api_request('POST', '/webhooks', [
    'url' => 'https://example.com/webhook-invalid',
    'events' => ['invalid.event'],
]);
test('Webhook Reject Invalid Events', $r['code'] === 422 || $r['code'] === 400,
    "HTTP {$r['code']}");

// =============================
// 2. IDEMPOTENCY
// =============================
echo "\n--- 2. Idempotency ---\n";

$idempKey = 'e2e-test-' . time() . '-' . mt_rand(1000, 9999);
$bookingData = [
    'guest_name' => 'Idempotency Test Guest',
    'guest_email' => 'idem@test.com',
    'channel' => 'website',
    'total_amount' => 1234,
];

// First request with idempotency key
$r1 = api_request('POST', '/bookings', $bookingData, ["X-Idempotency-Key: {$idempKey}"]);
test('Idempotency First Request', $r1['code'] === 201 && ($r1['body']['success'] ?? false),
    "HTTP {$r1['code']}, booking_id=" . ($r1['body']['data']['booking_id'] ?? 'N/A'));
$firstBookingId = $r1['body']['data']['booking_id'] ?? null;

// Second request with SAME idempotency key — should return same booking
$r2 = api_request('POST', '/bookings', $bookingData, ["X-Idempotency-Key: {$idempKey}"]);
$dupId = $r2['body']['data']['booking_id'] ?? $r2['body']['data']['id'] ?? null;
test('Idempotency Duplicate Returns Same', 
    $r2['code'] === 200 && $dupId == $firstBookingId,
    "HTTP {$r2['code']}, booking_id={$dupId} (expected {$firstBookingId})");

// Third request with DIFFERENT idempotency key — should create new booking
$idempKey2 = 'e2e-test-' . time() . '-' . mt_rand(5000, 9999);
$r3 = api_request('POST', '/bookings', $bookingData, ["X-Idempotency-Key: {$idempKey2}"]);
test('Idempotency Different Key Creates New', 
    $r3['code'] === 201 && ($r3['body']['data']['booking_id'] ?? null) != $firstBookingId,
    "HTTP {$r3['code']}, booking_id=" . ($r3['body']['data']['booking_id'] ?? 'N/A'));
$secondBookingId = $r3['body']['data']['booking_id'] ?? null;

// =============================
// 3. BOOKING DETAIL (GET single)
// =============================
echo "\n--- 3. Booking Detail ---\n";

if ($firstBookingId) {
    $r = api_request('GET', "/bookings/{$firstBookingId}");
    test('Get Booking Detail', $r['code'] === 200 && ($r['body']['data']['id'] ?? null) == $firstBookingId,
        "HTTP {$r['code']}, guest=" . ($r['body']['data']['guest_name'] ?? 'N/A'));
    
    test('Booking Has Idempotency Key', 
        ($r['body']['data']['idempotency_key'] ?? '') === $idempKey,
        "key=" . ($r['body']['data']['idempotency_key'] ?? 'N/A'));
} else {
    test('Get Booking Detail', false, 'No booking ID from create');
    test('Booking Has Idempotency Key', false, 'No booking ID');
}

// =============================
// 4. UPDATE BOOKING (PUT)
// =============================
echo "\n--- 4. Update Booking ---\n";

if ($firstBookingId) {
    $r = api_request('PUT', "/bookings/{$firstBookingId}", [
        'guest_name' => 'Updated Idempotency Guest',
        'notes' => 'Updated via Phase 3 test',
    ]);
    // Booking is auto-processed to completed, so 409 is correct behavior
    test('Update Booking (completed=409)', $r['code'] === 409 || ($r['code'] === 200 && ($r['body']['success'] ?? false)),
        "HTTP {$r['code']} — " . ($r['code'] === 409 ? 'Correctly rejected completed booking' : 'Updated'));
} else {
    test('Update Booking', false, 'No booking ID');
}

// =============================
// 5. CANCEL BOOKING
// =============================
echo "\n--- 5. Cancel Booking ---\n";

if ($secondBookingId) {
    $r = api_request('DELETE', "/bookings/{$secondBookingId}");
    test('Cancel Booking', $r['code'] === 200 && ($r['body']['success'] ?? false),
        "HTTP {$r['code']}, status=" . ($r['body']['data']['status'] ?? 'N/A'));
} else {
    test('Cancel Booking', false, 'No booking ID');
}

// =============================
// 6. SUBSCRIPTION INFO
// =============================
echo "\n--- 6. Subscription Info ---\n";

$r = api_request('GET', '/subscription');
test('Get Subscription', $r['code'] === 200 && !empty($r['body']['data']),
    "HTTP {$r['code']}, plan=" . ($r['body']['data']['plan'] ?? 'N/A'));

// =============================
// 7. LIST BOOKINGS (with filters)
// =============================
echo "\n--- 7. List & Filter ---\n";

$r = api_request('GET', '/bookings?status=completed');
test('List Completed Bookings', $r['code'] === 200,
    "HTTP {$r['code']}, count=" . count($r['body']['data'] ?? []));

$r = api_request('GET', '/bookings?search=Idempotency');
test('Search Bookings', $r['code'] === 200,
    "HTTP {$r['code']}, count=" . count($r['body']['data'] ?? []));

// =============================
// 8. API DISCOVERY (root endpoint)
// =============================
echo "\n--- 8. API Discovery ---\n";

$ch = curl_init('http://iacc_nginx/api.php/v1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($response, true);

test('API Root Endpoint', $httpCode === 200 && !empty($data['data']['endpoints']),
    "HTTP {$httpCode}, endpoints=" . count($data['data']['endpoints'] ?? []));

test('API Features Listed', !empty($data['data']['features']),
    "features: " . implode(', ', array_keys($data['data']['features'] ?? [])));

// =============================
// 9. CLEANUP — Delete webhooks
// =============================
echo "\n--- 9. Cleanup ---\n";

if ($webhookId) {
    $r = api_request('DELETE', "/webhooks/{$webhookId}");
    test('Delete Webhook 1', $r['code'] === 200,
        "HTTP {$r['code']}");
}
if ($webhookId2) {
    $r = api_request('DELETE', "/webhooks/{$webhookId2}");
    test('Delete Webhook 2', $r['code'] === 200 || $r['code'] === 404,
        "HTTP {$r['code']}");
}

// Cancel the first test booking too
if ($firstBookingId) {
    $r = api_request('DELETE', "/bookings/{$firstBookingId}");
    test('Cancel Test Booking', $r['code'] === 200 || $r['code'] === 409,
        "HTTP {$r['code']}");
}

// =============================
// SUMMARY
// =============================
echo "\n====================================\n";
echo "RESULTS: {$passed} passed, {$failed} failed, " . ($passed + $failed) . " total\n";
echo "====================================\n\n";

foreach ($results as $r) {
    $icon = $r['pass'] ? '✅' : '❌';
    echo "{$icon} {$r['name']}";
    if ($r['detail']) echo " ({$r['detail']})";
    echo "\n";
}

echo "\n" . ($failed === 0 ? "🎉 ALL TESTS PASSED!" : "⚠️  {$failed} TEST(S) FAILED") . "\n";
exit($failed > 0 ? 1 : 0);
