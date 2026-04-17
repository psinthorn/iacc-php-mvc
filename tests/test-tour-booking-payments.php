<?php
/**
 * E2E Test — Tour Booking Payments CRUD
 * 
 * Tests: record payment, sync status, approve, reject, refund, delete
 * 
 * Run: docker exec iacc_php php /var/www/html/tests/test-tour-booking-payments.php
 *  or: curl -s http://localhost/tests/test-tour-booking-payments.php
 */
session_start();
require_once __DIR__ . '/../inc/sys.configs.php';
require_once __DIR__ . '/../inc/class.dbconn.php';
require_once __DIR__ . '/../inc/class.hard.php';
require_once __DIR__ . '/../inc/security.php';

$db = new DbConn($config);
$conn = $db->conn;
$har = new HardClass();
$har->setConnection($conn);

// Test session
$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 1;
$comId = 95;

$results = [];
$passed = 0;
$failed = 0;

function test($name, $condition, $details = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $results[] = ['name' => $name, 'status' => 'PASS', 'details' => $details];
        $passed++;
    } else {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'details' => $details];
        $failed++;
    }
}

// ========== SETUP: Create test booking ==========
// Clean up any leftover test data from previous runs
mysqli_query($conn, "DELETE FROM tour_booking_payments WHERE booking_id IN (SELECT id FROM tour_bookings WHERE booking_number='BK-TEST-PAY' AND company_id=$comId)");
mysqli_query($conn, "DELETE FROM tour_bookings WHERE booking_number='BK-TEST-PAY' AND company_id=$comId");

$bookingId = $har->Maxid('tour_bookings');
$sql = "INSERT INTO tour_bookings (id, company_id, booking_number, booking_date, travel_date, total_amount, status, payment_status, amount_paid, amount_due, deposit_amount)
        VALUES ($bookingId, $comId, 'BK-TEST-PAY', '" . date('Y-m-d') . "', '" . date('Y-m-d') . "', 10000.00, 'confirmed', 'unpaid', 0, 10000.00, 0)";
$setupOk = mysqli_query($conn, $sql);
test('SETUP: Create test booking', $setupOk !== false, "booking_id=$bookingId, total=10000");

// ========== Load Model ==========
require_once __DIR__ . '/../app/Models/BaseModel.php';
require_once __DIR__ . '/../app/Models/TourBookingPayment.php';
$model = new \App\Models\TourBookingPayment();

// ========== TEST 1: Record deposit payment ==========
$payId1 = $model->recordPayment([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'bank_transfer',
    'amount'         => 3000,
    'currency'       => 'THB',
    'reference_id'   => 'TRF-001',
    'payment_date'   => date('Y-m-d'),
    'status'         => 'completed',
    'payment_type'   => 'deposit',
    'notes'          => 'Test deposit payment',
    'created_by'     => 1,
]);
test('1. Record deposit payment', $payId1 > 0, "payment_id=$payId1");

// ====== TEST 2: Booking status synced to 'deposit' ==========
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_status, amount_paid, amount_due, deposit_amount FROM tour_bookings WHERE id=$bookingId"));
test('2. Booking synced: deposit status', $row['payment_status'] === 'deposit', "status={$row['payment_status']}");
test('3. Booking synced: amount_paid=3000', floatval($row['amount_paid']) == 3000, "amount_paid={$row['amount_paid']}");
test('4. Booking synced: amount_due=7000', floatval($row['amount_due']) == 7000, "amount_due={$row['amount_due']}");
test('5. Booking synced: deposit_amount=3000', floatval($row['deposit_amount']) == 3000, "deposit={$row['deposit_amount']}");

// ========== TEST 6: Record partial payment ==========
$payId2 = $model->recordPayment([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'cash',
    'amount'         => 2000,
    'payment_date'   => date('Y-m-d'),
    'status'         => 'completed',
    'payment_type'   => 'partial',
    'created_by'     => 1,
]);
test('6. Record partial payment', $payId2 > 0, "payment_id=$payId2");

$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_status, amount_paid, amount_due FROM tour_bookings WHERE id=$bookingId"));
test('7. Status now partial', $row['payment_status'] === 'partial', "status={$row['payment_status']}");
test('8. Amount paid=5000', floatval($row['amount_paid']) == 5000, "paid={$row['amount_paid']}");

// ========== TEST 9: Record remaining balance ==========
$payId3 = $model->recordPayment([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'credit_card',
    'amount'         => 5000,
    'payment_date'   => date('Y-m-d'),
    'status'         => 'completed',
    'payment_type'   => 'full',
    'created_by'     => 1,
]);
test('9. Record final payment', $payId3 > 0, "payment_id=$payId3");

$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_status, amount_paid, amount_due FROM tour_bookings WHERE id=$bookingId"));
test('10. Status now paid', $row['payment_status'] === 'paid', "status={$row['payment_status']}");
test('11. Amount paid=10000', floatval($row['amount_paid']) == 10000, "paid={$row['amount_paid']}");
test('12. Amount due=0', floatval($row['amount_due']) == 0, "due={$row['amount_due']}");

// ========== TEST 13: Get payments list ==========
$payments = $model->getPayments($bookingId, $comId);
test('13. getPayments returns 3 records', count($payments) === 3, "count=" . count($payments));

// ========== TEST 14: Find single payment ==========
$single = $model->findPayment($payId1, $comId);
test('14. findPayment returns record', $single !== null && intval($single['id']) === $payId1, "found id=" . ($single['id'] ?? 'NULL'));

// ========== TEST 15: Payment summary ==========
$summary = $model->getBookingPaymentSummary($bookingId, $comId);
test('15. Summary total_paid=10000', $summary['total_paid'] == 10000, "total_paid={$summary['total_paid']}");
test('16. Summary payment_count=3', $summary['payment_count'] == 3, "count={$summary['payment_count']}");

// ========== TEST 17: Record refund ==========
$refundId = $model->recordRefund([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'bank_transfer',
    'amount'         => 10000,
    'payment_date'   => date('Y-m-d'),
    'notes'          => 'Full refund test',
    'created_by'     => 1,
]);
test('17. Record refund', $refundId > 0, "refund_id=$refundId");

$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_status, amount_paid, amount_due FROM tour_bookings WHERE id=$bookingId"));
test('18. Status now refunded', $row['payment_status'] === 'refunded', "status={$row['payment_status']}");

// ========== TEST 19: Delete payment ==========
$delOk = $model->deletePayment($payId3, $comId);
test('19. Soft delete payment', $delOk === true);

$deleted = $model->findPayment($payId3, $comId);
test('20. Deleted payment hidden', $deleted === null, "findPayment returns null");

// ========== TEST 21: Pending review flow ==========
$pendingId = $model->recordPayment([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'bank_transfer',
    'amount'         => 500,
    'payment_date'   => date('Y-m-d'),
    'status'         => 'pending_review',
    'payment_type'   => 'partial',
    'slip_image'     => 'upload/payment_slips/test_slip.jpg',
    'created_by'     => 1,
]);
test('21. Create pending_review payment', $pendingId > 0, "id=$pendingId");

$pending = $model->findPayment($pendingId, $comId);
test('22. Status is pending_review', $pending['status'] === 'pending_review');

// ========== TEST 23: Approve slip ==========
$approveOk = $model->approvePayment($pendingId, $comId, 1);
test('23. Approve payment', $approveOk === true);

$approved = $model->findPayment($pendingId, $comId);
test('24. Status changed to completed', $approved['status'] === 'completed', "status={$approved['status']}");
test('25. approved_by set', intval($approved['approved_by']) === 1);

// ========== TEST 26: Reject flow ==========
$pendingId2 = $model->recordPayment([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'promptpay',
    'amount'         => 200,
    'payment_date'   => date('Y-m-d'),
    'status'         => 'pending_review',
    'payment_type'   => 'partial',
    'created_by'     => 1,
]);
$rejectOk = $model->rejectPayment($pendingId2, $comId, 1, 'Unclear slip image');
test('26. Reject payment', $rejectOk === true);

$rejected = $model->findPayment($pendingId2, $comId);
test('27. Status is rejected', $rejected['status'] === 'rejected', "status={$rejected['status']}");
test('28. Reject reason stored', $rejected['reject_reason'] === 'Unclear slip image');

// ========== TEST 29: Pending slip reviews ==========
$pendingId3 = $model->recordPayment([
    'booking_id'     => $bookingId,
    'company_id'     => $comId,
    'payment_method' => 'bank_transfer',
    'amount'         => 100,
    'payment_date'   => date('Y-m-d'),
    'status'         => 'pending_review',
    'payment_type'   => 'partial',
    'created_by'     => 1,
]);
$pendingReviews = $model->getPendingSlipReviews($comId);
test('29. getPendingSlipReviews returns results', count($pendingReviews) >= 1, "count=" . count($pendingReviews));

// ========== TEST 30: Static label helpers ==========
$methods = \App\Models\TourBookingPayment::getMethodLabels(false);
test('30. Method labels (EN)', isset($methods['cash']) && $methods['cash'] === 'Cash');

$types = \App\Models\TourBookingPayment::getTypeLabels(true);
test('31. Type labels (TH)', isset($types['deposit']) && $types['deposit'] === 'มัดจำ');

$statuses = \App\Models\TourBookingPayment::getStatusConfig(false);
test('32. Status config has colors', isset($statuses['completed']['color']));

// ========== TEST 33: Company isolation ==========
$otherCompanyPayment = $model->findPayment($payId1, 999);
test('33. Company isolation: other company cannot see payment', $otherCompanyPayment === null);

$otherPayments = $model->getPayments($bookingId, 999);
test('34. Company isolation: other company gets empty list', count($otherPayments) === 0);

// ========== CLEANUP ==========
mysqli_query($conn, "DELETE FROM tour_booking_payments WHERE booking_id = $bookingId");
mysqli_query($conn, "DELETE FROM tour_bookings WHERE id = $bookingId");
test('CLEANUP: Test data removed', true);

// ========== OUTPUT ==========
$total = $passed + $failed;
?>
<html>
<head><title>Tour Booking Payments — E2E Test</title></head>
<style>
    body { font-family: -apple-system, sans-serif; max-width: 800px; margin: 20px auto; padding: 0 20px; }
    h1 { font-size: 20px; }
    .test { padding: 8px 12px; margin: 4px 0; border-radius: 6px; font-size: 13px; }
    .pass { background: #d1fae5; color: #065f46; }
    .fail { background: #fee2e2; color: #991b1b; font-weight: bold; }
    .summary { padding: 16px; margin-top: 16px; font-size: 16px; border-radius: 8px; color: white; font-weight: bold; }
</style>
<body>
<h1>Tour Booking Payments — E2E Tests</h1>
<?php foreach ($results as $r): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?> — <?= htmlspecialchars($r['details']) ?><?php endif; ?>
    </div>
<?php endforeach; ?>
<div class="summary" style="background: <?= $failed === 0 ? '#059669' : '#dc2626' ?>;">
    <?= $failed === 0 ? '✓ ALL PASSED' : '✗ FAILURES DETECTED' ?>: <?= $passed ?>/<?= $total ?> passed | <?= $failed ?> failed
</div>
</body></html>
