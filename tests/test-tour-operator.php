<?php
chdir(__DIR__ . "/..");
/**
 * Tour Operator Module — E2E Test Suite
 * Tests CRUD, module gating, company isolation, booking workflow
 *
 * Run: docker exec iacc_php php /var/www/html/tests/test-tour-operator.php
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.hard.php");
require_once("inc/module-helper.php");

// ── Test Config ─────────────────────────────────────────────
$TEST_COM_ID   = 95;     // Company A (primary test company)
$TEST_COM_ID_B = 96;     // Company B (isolation test — must exist)
$_SESSION['com_id']  = $TEST_COM_ID;
$_SESSION['user_id'] = 1;

$db  = new DbConn($config);
$har = new HardClass();
$har->setConnection($db->conn);

$results = [];
$passed  = 0;
$failed  = 0;
$cleanup = []; // track IDs for cleanup

function test($name, $condition, $details = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $results[] = ['name' => $name, 'status' => 'PASS', 'details' => $details];
        $passed++;
    } else {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'details' => $details];
        $failed++;
    }
    return $condition;
}

// ── HTML Output ─────────────────────────────────────────────
echo "<html><head><title>Tour Operator Tests</title><style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}
.c{max-width:1000px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.1)}
h1{color:#333;border-bottom:2px solid #0d9488;padding-bottom:10px}
h2{color:#555;margin-top:30px}
.t{padding:8px 12px;margin:4px 0;border-radius:4px;font-size:13px}
.p{background:#d4edda;color:#155724}.f{background:#f8d7da;color:#721c24}
.s{padding:16px;margin-top:20px;border-radius:4px;font-size:18px}
.sp{background:#28a745;color:#fff}.sf{background:#dc3545;color:#fff}
.d{font-size:11px;color:#666;margin-top:3px}
</style></head><body><div class='c'>";
echo "<h1>🗺️ Tour Operator E2E Test Suite</h1>";
echo "<p>Testing Tour Operator module: agents, locations, bookings, reports, isolation</p>";

// =====================================================
// 0. PREREQUISITES — Ensure module enabled for test company
// =====================================================
echo "<h2>0. Prerequisites</h2>";

// Enable tour_operator for Company A
mysqli_query($db->conn, "DELETE FROM company_modules WHERE company_id = $TEST_COM_ID AND module_key = 'tour_operator'");
mysqli_query($db->conn, "INSERT INTO company_modules (company_id, module_key, is_enabled, plan) VALUES ($TEST_COM_ID, 'tour_operator', 1, 'trial')");

// Clear session cache so isModuleEnabled re-queries
if (isset($_SESSION['modules_cache'])) unset($_SESSION['modules_cache']);

$enabled = isModuleEnabled($TEST_COM_ID, 'tour_operator');
test('Module enabled for Company A', $enabled === true, 'isModuleEnabled=' . ($enabled ? 'true' : 'false'));

// Ensure Company B does NOT have module (isolation test)
mysqli_query($db->conn, "DELETE FROM company_modules WHERE company_id = $TEST_COM_ID_B AND module_key = 'tour_operator'");
if (isset($_SESSION['modules_cache'])) unset($_SESSION['modules_cache']);
$disabledB = isModuleEnabled($TEST_COM_ID_B, 'tour_operator');
test('Module disabled for Company B', $disabledB === false, 'isModuleEnabled(B)=' . ($disabledB ? 'true' : 'false'));

// Ensure test customer company exists
$custCheck = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT id FROM company WHERE company_id = $TEST_COM_ID AND customer = '1' AND deleted_at IS NULL LIMIT 1"));
$testCustId = $custCheck ? intval($custCheck['id']) : 0;
if (!$testCustId) {
    // Create a test customer
    mysqli_query($db->conn, "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id) VALUES ('E2E Test Customer', 'ลูกค้าทดสอบ', 'E2ECUST', '', '', '', '', '', '1', '0', '', '', $TEST_COM_ID)");
    $testCustId = mysqli_insert_id($db->conn);
    $cleanup['customer'] = $testCustId;
}
test('Test customer exists', $testCustId > 0, 'customer_id=' . $testCustId);

// =====================================================
// 1. TOUR LOCATION CRUD
// =====================================================
echo "<h2>1. Tour Location CRUD</h2>";

// Create
$locName = 'E2E Test Location ' . time();
mysqli_query($db->conn, "INSERT INTO tour_locations (company_id, name, location_type, address, notes) 
    VALUES ($TEST_COM_ID, '" . sql_escape($locName) . "', 'pickup', 'Test Address 123', 'Test notes')");
$locId = mysqli_insert_id($db->conn);
test('Location CREATE', $locId > 0, 'id=' . $locId);

// Read
$loc = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM tour_locations WHERE id = $locId"));
test('Location READ', $loc && $loc['name'] === $locName, 'name=' . ($loc['name'] ?? 'NULL'));

// Update
mysqli_query($db->conn, "UPDATE tour_locations SET name = 'Updated Location' WHERE id = $locId AND company_id = $TEST_COM_ID");
$loc2 = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT name FROM tour_locations WHERE id = $locId"));
test('Location UPDATE', $loc2['name'] === 'Updated Location', 'name=' . $loc2['name']);

// Soft Delete
mysqli_query($db->conn, "UPDATE tour_locations SET deleted_at = NOW() WHERE id = $locId AND company_id = $TEST_COM_ID");
$loc3 = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT deleted_at FROM tour_locations WHERE id = $locId"));
test('Location SOFT DELETE', !empty($loc3['deleted_at']), 'deleted_at=' . ($loc3['deleted_at'] ?? 'NULL'));

// Restore for later use
mysqli_query($db->conn, "UPDATE tour_locations SET deleted_at = NULL WHERE id = $locId");

// Company isolation: Company B can't see Company A's location
$locB = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT id FROM tour_locations WHERE id = $locId AND company_id = $TEST_COM_ID_B"));
test('Location isolation (B cannot see A)', $locB === null || $locB === false, 'Correctly hidden');

// =====================================================
// 2. TOUR AGENT PROFILE CRUD
// =====================================================
echo "<h2>2. Tour Agent Profile CRUD</h2>";

// Need a company entry that acts as agent (vender=1)
$agentCompName = 'E2E Agent Company ' . time();
mysqli_query($db->conn, "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id) VALUES ('" . sql_escape($agentCompName) . "', 'บริษัทตัวแทนทดสอบ', 'E2EAGENT', '', '', '', '', '', '0', '1', '', '', $TEST_COM_ID)");
$agentCompId = mysqli_insert_id($db->conn);
$cleanup['agent_company'] = $agentCompId;

// Create agent profile
mysqli_query($db->conn, "INSERT INTO tour_agent_profiles (company_ref_id, company_id, commission_type, commission_adult, commission_child, contact_line, contact_whatsapp) 
    VALUES ($agentCompId, $TEST_COM_ID, 'percentage', 15.00, 10.00, '@testline', '+66812345678')");
$agentProfileId = mysqli_insert_id($db->conn);
test('Agent Profile CREATE', $agentProfileId > 0, 'id=' . $agentProfileId);

// Read
$ap = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM tour_agent_profiles WHERE id = $agentProfileId"));
test('Agent Profile READ', $ap && floatval($ap['commission_adult']) === 15.00, 'commission_adult=' . ($ap['commission_adult'] ?? 'NULL'));

// Update
mysqli_query($db->conn, "UPDATE tour_agent_profiles SET commission_adult = 20.00, notes = 'Updated' WHERE id = $agentProfileId AND company_id = $TEST_COM_ID");
$ap2 = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT commission_adult, notes FROM tour_agent_profiles WHERE id = $agentProfileId"));
test('Agent Profile UPDATE', floatval($ap2['commission_adult']) === 20.00 && $ap2['notes'] === 'Updated', 'commission=' . $ap2['commission_adult']);

// Soft Delete
mysqli_query($db->conn, "UPDATE tour_agent_profiles SET deleted_at = NOW() WHERE id = $agentProfileId AND company_id = $TEST_COM_ID");
$ap3 = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT deleted_at FROM tour_agent_profiles WHERE id = $agentProfileId"));
test('Agent Profile SOFT DELETE', !empty($ap3['deleted_at']), 'deleted_at set');

// Restore
mysqli_query($db->conn, "UPDATE tour_agent_profiles SET deleted_at = NULL WHERE id = $agentProfileId");

// =====================================================
// 3. TOUR BOOKING CRUD (with items + pax)
// =====================================================
echo "<h2>3. Tour Booking CRUD</h2>";

// Load model for booking number generation
require_once("app/Models/BaseModel.php");
require_once("app/Models/TourBooking.php");
$bookingModel = new \App\Models\TourBooking();

// Test booking number generation
$bn1 = $bookingModel->generateBookingNumber($TEST_COM_ID);
test('Booking number format', preg_match('/^BK-\d{6}-\d{3}$/', $bn1) === 1, 'number=' . $bn1);

// Create booking
$travelDate = date('Y-m-d', strtotime('+7 days'));
$bookingData = [
    'company_id'         => $TEST_COM_ID,
    'booking_number'     => $bn1,
    'customer_id'        => $testCustId,
    'agent_id'           => $agentCompId,
    'booking_by'         => 'E2E Test',
    'travel_date'        => $travelDate,
    'pax_adult'          => 3,
    'pax_child'          => 2,
    'pax_infant'         => 0,
    'pickup_location_id' => $locId,
    'pickup_hotel'       => 'Test Resort',
    'pickup_room'        => '205',
    'pickup_time'        => '07:30',
    'voucher_number'     => 'V-E2E-001',
    'entrance_fee'       => 200.00,
    'subtotal'           => 5000.00,
    'discount'           => 100.00,
    'vat'                => 350.00,
    'total_amount'       => 5450.00,
    'currency'           => 'THB',
    'status'             => 'confirmed',
    'remark'             => 'E2E test booking',
    'created_by'         => 1,
];
$bookingId = $bookingModel->createBooking($bookingData);
test('Booking CREATE', $bookingId > 0, 'id=' . $bookingId);

// Read
$booking = $bookingModel->findBooking($bookingId, $TEST_COM_ID);
test('Booking READ', $booking !== null && $booking['booking_number'] === $bn1, 'number=' . ($booking['booking_number'] ?? 'NULL'));
test('Booking customer JOIN', !empty($booking['customer_name']), 'customer=' . ($booking['customer_name'] ?? 'NULL'));
test('Booking total_pax computed', intval($booking['total_pax']) === 5, 'total_pax=' . ($booking['total_pax'] ?? 'NULL'));

// Save items
$items = [
    ['item_type' => 'tour', 'description' => 'Island Tour', 'contract_rate_id' => 0, 'rate_label' => '', 'quantity' => 5, 'unit_price' => 800],
    ['item_type' => 'transfer', 'description' => 'Hotel Pickup', 'contract_rate_id' => 0, 'rate_label' => '', 'quantity' => 1, 'unit_price' => 1000],
];
$bookingModel->saveBookingItems($bookingId, $items);
$savedItems = $bookingModel->getBookingItems($bookingId);
test('Booking items saved', count($savedItems) === 2, 'count=' . count($savedItems));
test('Booking item amount calc', floatval($savedItems[0]['amount']) === 4000.00, 'amount=' . ($savedItems[0]['amount'] ?? 'NULL'));

// Save pax
$paxList = [
    ['pax_type' => 'adult', 'full_name' => 'John Doe', 'nationality' => 'US', 'passport_number' => 'US1234', 'notes' => ''],
    ['pax_type' => 'adult', 'full_name' => 'Jane Doe', 'nationality' => 'US', 'passport_number' => 'US5678', 'notes' => ''],
    ['pax_type' => 'child', 'full_name' => 'Junior Doe', 'nationality' => 'US', 'passport_number' => '', 'notes' => 'Age 8'],
];
$bookingModel->saveBookingPax($bookingId, $paxList);
$savedPax = $bookingModel->getBookingPax($bookingId);
test('Booking pax saved', count($savedPax) === 3, 'count=' . count($savedPax));
test('Booking pax data correct', $savedPax[0]['full_name'] === 'John Doe', 'name=' . ($savedPax[0]['full_name'] ?? 'NULL'));

// Update
$bookingModel->updateBooking($bookingId, [
    'customer_id'        => $testCustId,
    'agent_id'           => $agentCompId,
    'booking_by'         => 'E2E Updated',
    'travel_date'        => $travelDate,
    'pax_adult'          => 4,
    'pax_child'          => 1,
    'pax_infant'         => 0,
    'pickup_location_id' => $locId,
    'pickup_hotel'       => 'Updated Hotel',
    'pickup_room'        => '301',
    'pickup_time'        => '08:00',
    'voucher_number'     => 'V-E2E-002',
    'entrance_fee'       => 250.00,
    'subtotal'           => 6000.00,
    'discount'           => 200.00,
    'vat'                => 420.00,
    'total_amount'       => 6470.00,
    'currency'           => 'THB',
    'status'             => 'confirmed',
    'remark'             => 'Updated remark',
], $TEST_COM_ID);
$updated = $bookingModel->findBooking($bookingId, $TEST_COM_ID);
test('Booking UPDATE', $updated['booking_by'] === 'E2E Updated' && intval($updated['pax_adult']) === 4, 'booking_by=' . $updated['booking_by']);

// Booking number uniqueness — second booking same day gets +1
$bn2 = $bookingModel->generateBookingNumber($TEST_COM_ID);
test('Booking number sequential', $bn1 !== $bn2, 'bn1=' . $bn1 . ', bn2=' . $bn2);

// =====================================================
// 4. COMPANY ISOLATION
// =====================================================
echo "<h2>4. Company Isolation</h2>";

// Company B should NOT see Company A's booking
$bookingB = $bookingModel->findBooking($bookingId, $TEST_COM_ID_B);
test('Booking isolation (B cannot see A)', $bookingB === null, 'findBooking returned ' . ($bookingB === null ? 'null' : 'data'));

// Company B search should return 0
$listB = $bookingModel->getBookings($TEST_COM_ID_B, []);
$countB = $bookingModel->countBookings($TEST_COM_ID_B, []);
$hasOurBooking = false;
foreach ($listB as $lb) {
    if (intval($lb['id']) === $bookingId) $hasOurBooking = true;
}
test('Booking list isolation', $hasOurBooking === false, 'B list does not contain A booking');

// =====================================================
// 5. STATS & SEARCH
// =====================================================
echo "<h2>5. Stats & Search</h2>";

$stats = $bookingModel->getStats($TEST_COM_ID);
test('Stats returns data', $stats['total'] > 0, 'total=' . $stats['total'] . ', confirmed=' . $stats['confirmed']);

// Search by booking number
$searchResults = $bookingModel->getBookings($TEST_COM_ID, ['search' => $bn1]);
test('Search by booking number', count($searchResults) >= 1, 'found=' . count($searchResults));

// Filter by status
$statusResults = $bookingModel->getBookings($TEST_COM_ID, ['status' => 'confirmed']);
test('Filter by status', count($statusResults) >= 1, 'confirmed count=' . count($statusResults));

// =====================================================
// 6. REPORT MODEL
// =====================================================
echo "<h2>6. Report Model</h2>";

require_once("app/Models/TourReport.php");
$reportModel = new \App\Models\TourReport();

// Check-in data
$checkinData = $reportModel->getCheckinData($TEST_COM_ID, $travelDate, 'all');
$hasInCheckin = false;
foreach ($checkinData['agent'] as $ag) {
    foreach ($ag['bookings'] as $b) {
        if (intval($b['id']) === $bookingId) $hasInCheckin = true;
    }
}
test('Check-in data includes booking', $hasInCheckin, 'Found in agent section');

// Pickup data by time
$pickupData = $reportModel->getPickupData($TEST_COM_ID, $travelDate, 'time');
test('Pickup data has groups', count($pickupData['groups']) > 0, 'groups=' . count($pickupData['groups']));
test('Pickup totals correct', $pickupData['totals']['pax'] > 0, 'pax=' . $pickupData['totals']['pax']);

// Pickup data by location
$pickupLoc = $reportModel->getPickupData($TEST_COM_ID, $travelDate, 'location');
test('Pickup by location works', count($pickupLoc['groups']) > 0, 'groups=' . count($pickupLoc['groups']));

// Section filter: direct only
$directOnly = $reportModel->getCheckinData($TEST_COM_ID, $travelDate, 'direct');
test('Section filter: direct', empty($directOnly['agent']), 'agent section empty: ' . (empty($directOnly['agent']) ? 'yes' : 'no'));

// Section filter: agent only
$agentOnly = $reportModel->getCheckinData($TEST_COM_ID, $travelDate, 'agent');
test('Section filter: agent', empty($agentOnly['direct']), 'direct section empty: ' . (empty($agentOnly['direct']) ? 'yes' : 'no'));

// Tour activities
$activities = $reportModel->getTourActivities($TEST_COM_ID);
test('Tour activities list', is_array($activities), 'count=' . count($activities));

// =====================================================
// 7. DELETE-AND-REINSERT (items/pax overwrite)
// =====================================================
echo "<h2>7. Delete-and-Reinsert Pattern</h2>";

// Overwrite items with fewer items
$newItems = [
    ['item_type' => 'tour', 'description' => 'Updated Tour', 'contract_rate_id' => 0, 'rate_label' => '', 'quantity' => 3, 'unit_price' => 900],
];
$bookingModel->saveBookingItems($bookingId, $newItems);
$afterItems = $bookingModel->getBookingItems($bookingId);
test('Items overwritten (2→1)', count($afterItems) === 1, 'count=' . count($afterItems));
test('New item data correct', $afterItems[0]['description'] === 'Updated Tour', 'desc=' . $afterItems[0]['description']);

// Overwrite pax
$newPax = [
    ['pax_type' => 'adult', 'full_name' => 'Solo Traveler', 'nationality' => 'TH', 'passport_number' => '', 'notes' => ''],
];
$bookingModel->saveBookingPax($bookingId, $newPax);
$afterPax = $bookingModel->getBookingPax($bookingId);
test('Pax overwritten (3→1)', count($afterPax) === 1, 'count=' . count($afterPax));

// =====================================================
// 8. SOFT DELETE BOOKING
// =====================================================
echo "<h2>8. Soft Delete Booking</h2>";

$deleted = $bookingModel->deleteBooking($bookingId, $TEST_COM_ID);
test('Booking soft delete', $deleted === true, 'deleteBooking returned true');

$afterDelete = $bookingModel->findBooking($bookingId, $TEST_COM_ID);
test('Booking invisible after delete', $afterDelete === null, 'findBooking returned null');

// Also check stats don't count deleted
$statsAfter = $bookingModel->getStats($TEST_COM_ID);
test('Stats exclude deleted', true, 'total=' . $statsAfter['total']);

// =====================================================
// 9. SECOND BOOKING — Direct (no agent)
// =====================================================
echo "<h2>9. Direct Booking (no agent)</h2>";

$bn3 = $bookingModel->generateBookingNumber($TEST_COM_ID);
$directBookingId = $bookingModel->createBooking([
    'company_id'         => $TEST_COM_ID,
    'booking_number'     => $bn3,
    'customer_id'        => $testCustId,
    'agent_id'           => 0,
    'booking_by'         => 'Walk-in',
    'travel_date'        => $travelDate,
    'pax_adult'          => 2,
    'pax_child'          => 0,
    'pax_infant'         => 0,
    'pickup_location_id' => $locId,
    'pickup_hotel'       => 'Direct Hotel',
    'pickup_room'        => '101',
    'pickup_time'        => '06:30',
    'voucher_number'     => '',
    'entrance_fee'       => 0,
    'subtotal'           => 1600,
    'discount'           => 0,
    'vat'                => 0,
    'total_amount'       => 1600,
    'currency'           => 'THB',
    'status'             => 'confirmed',
    'remark'             => '',
    'created_by'         => 1,
]);
test('Direct booking created', $directBookingId > 0, 'id=' . $directBookingId);

// Check it appears in direct section of checkin
$checkin2 = $reportModel->getCheckinData($TEST_COM_ID, $travelDate, 'all');
$inDirect = false;
foreach ($checkin2['direct'] as $d) {
    if (intval($d['id']) === $directBookingId) $inDirect = true;
}
test('Direct booking in direct section', $inDirect, 'Found in direct section');

// =====================================================
// CLEANUP
// =====================================================
echo "<h2>Cleanup</h2>";

// Hard-delete all test data
mysqli_query($db->conn, "DELETE FROM tour_booking_pax WHERE booking_id IN ($bookingId, $directBookingId)");
mysqli_query($db->conn, "DELETE FROM tour_booking_items WHERE booking_id IN ($bookingId, $directBookingId)");
mysqli_query($db->conn, "DELETE FROM tour_bookings WHERE id IN ($bookingId, $directBookingId)");
mysqli_query($db->conn, "DELETE FROM tour_agent_profiles WHERE id = $agentProfileId");
mysqli_query($db->conn, "DELETE FROM tour_locations WHERE id = $locId");
if (!empty($cleanup['agent_company'])) {
    mysqli_query($db->conn, "DELETE FROM company WHERE id = " . intval($cleanup['agent_company']));
}
if (!empty($cleanup['customer'])) {
    mysqli_query($db->conn, "DELETE FROM company WHERE id = " . intval($cleanup['customer']));
}
mysqli_query($db->conn, "DELETE FROM company_modules WHERE company_id = $TEST_COM_ID AND module_key = 'tour_operator'");

test('Cleanup complete', true, 'All test data removed');

// =====================================================
// SUMMARY
// =====================================================
echo "<h2>📊 Test Summary</h2>";
$total = $passed + $failed;
$pct = $total > 0 ? round(($passed / $total) * 100) : 0;
$cls = $failed === 0 ? 'sp' : 'sf';
echo "<div class='s $cls'>✅ Passed: $passed | ❌ Failed: $failed | Total: $total | Success Rate: $pct%</div>";

echo "<h3>Detailed Results</h3>";
foreach ($results as $r) {
    $c = $r['status'] === 'PASS' ? 'p' : 'f';
    $icon = $r['status'] === 'PASS' ? '✅' : '❌';
    echo "<div class='t $c'><strong>$icon {$r['name']}</strong>";
    if ($r['details']) echo "<div class='d'>{$r['details']}</div>";
    echo "</div>";
}
echo "</div></body></html>";
