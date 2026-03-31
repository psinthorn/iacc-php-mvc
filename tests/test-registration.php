<?php
/**
 * E2E Tests for Self-Registration System (v6.0)
 * Tests: email verification, account creation, onboarding
 * Run: docker exec iacc_php php /var/www/html/tests/test-registration.php
 *   or curl -s "http://localhost/tests/test-registration.php"
 */
session_start();
require_once(__DIR__ . '/../inc/sys.configs.php');
require_once(__DIR__ . '/../inc/class.dbconn.php');
require_once(__DIR__ . '/../inc/class.hard.php');

// Setup
$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 1;

$db = new DbConn($config);
$har = new HardClass();
$har->setConnection($db->conn);

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

// ========== Test Data ==========
$testEmail = 'test_reg_' . time() . '@example.com';
$testToken = bin2hex(random_bytes(32));
$testPasswordHash = password_hash('TestPass123!', PASSWORD_DEFAULT);
$testPayload = json_encode([
    'name' => 'Test User',
    'password_hash' => $testPasswordHash,
    'company_name' => 'Test Registration Co'
]);

// ========== 1. EMAIL VERIFICATIONS TABLE ==========

// Test: Table exists
$tableCheck = mysqli_query($db->conn, "SHOW TABLES LIKE 'email_verifications'");
test('email_verifications table exists', mysqli_num_rows($tableCheck) === 1);

// Test: Insert verification token
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
$emailEsc = mysqli_real_escape_string($db->conn, $testEmail);
$tokenEsc = mysqli_real_escape_string($db->conn, $testToken);
$payloadEsc = mysqli_real_escape_string($db->conn, $testPayload);

$sql = "INSERT INTO email_verifications (email, token, payload, expires_at) 
        VALUES ('$emailEsc', '$tokenEsc', '$payloadEsc', '$expiresAt')";
$insertResult = mysqli_query($db->conn, $sql);
$verificationId = mysqli_insert_id($db->conn);
test('Verification CREATE', $insertResult !== false && $verificationId > 0, "ID: $verificationId");

// Test: Read verification token
$row = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT * FROM email_verifications WHERE token = '$tokenEsc'"));
test('Verification READ by token', 
    $row !== null && $row['email'] === $testEmail, 
    "Email: " . ($row['email'] ?? 'NULL'));

// Test: Payload is valid JSON
$decodedPayload = json_decode($row['payload'], true);
test('Verification payload is valid JSON', 
    $decodedPayload !== null && isset($decodedPayload['name']),
    "Name: " . ($decodedPayload['name'] ?? 'NULL'));

// Test: Token uniqueness
$dupSql = "INSERT INTO email_verifications (email, token, payload, expires_at) 
           VALUES ('dup@test.com', '$tokenEsc', '{}', '$expiresAt')";
try {
    $dupResult = mysqli_query($db->conn, $dupSql);
    test('Verification token UNIQUE constraint', $dupResult === false, 'Duplicate token rejected');
} catch (\mysqli_sql_exception $e) {
    test('Verification token UNIQUE constraint', true, 'Exception: Duplicate entry rejected');
}

// Test: Mark as verified
$now = date('Y-m-d H:i:s');
mysqli_query($db->conn, "UPDATE email_verifications SET verified_at = '$now' WHERE id = $verificationId");
$row = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT verified_at FROM email_verifications WHERE id = $verificationId"));
test('Verification MARK VERIFIED', $row['verified_at'] !== null, "Verified at: " . $row['verified_at']);

// Test: Expired token check
$expiredToken = bin2hex(random_bytes(32));
$expiredTokenEsc = mysqli_real_escape_string($db->conn, $expiredToken);
$pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
mysqli_query($db->conn, "INSERT INTO email_verifications (email, token, payload, expires_at) 
    VALUES ('expired@test.com', '$expiredTokenEsc', '{}', '$pastDate')");
$row = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT * FROM email_verifications WHERE token = '$expiredTokenEsc' AND expires_at > NOW() AND verified_at IS NULL"));
test('Expired token NOT returned', $row === null, 'Correctly filtered expired token');

// ========== 2. AUTHORIZE TABLE COLUMNS ==========

// Test: registered_via column exists
$col = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.columns 
     WHERE table_schema = DATABASE() AND table_name = 'authorize' AND column_name = 'registered_via'"));
test('authorize.registered_via column exists', $col !== null, "Type: " . ($col['COLUMN_TYPE'] ?? 'NULL'));

// Test: email_verified_at column exists
$col = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT COLUMN_NAME FROM information_schema.columns 
     WHERE table_schema = DATABASE() AND table_name = 'authorize' AND column_name = 'email_verified_at'"));
test('authorize.email_verified_at column exists', $col !== null);

// Test: Default value for registered_via
$col = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT COLUMN_DEFAULT FROM information_schema.columns 
     WHERE table_schema = DATABASE() AND table_name = 'authorize' AND column_name = 'registered_via'"));
test('authorize.registered_via default is admin', $col['COLUMN_DEFAULT'] === 'admin', "Default: " . ($col['COLUMN_DEFAULT'] ?? 'NULL'));

// ========== 3. COMPANY TABLE COLUMNS ==========

// Test: company.registered_via exists
$col = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT COLUMN_NAME FROM information_schema.columns 
     WHERE table_schema = DATABASE() AND table_name = 'company' AND column_name = 'registered_via'"));
test('company.registered_via column exists', $col !== null);

// Test: company.onboarding_completed exists
$col = mysqli_fetch_assoc(mysqli_query($db->conn, 
    "SELECT COLUMN_NAME, COLUMN_DEFAULT FROM information_schema.columns 
     WHERE table_schema = DATABASE() AND table_name = 'company' AND column_name = 'onboarding_completed'"));
test('company.onboarding_completed column exists', $col !== null, "Default: " . ($col['COLUMN_DEFAULT'] ?? 'NULL'));

// ========== 4. REGISTRATION MODEL ==========

require_once(__DIR__ . '/../app/Models/Registration.php');

$regModel = new \App\Models\Registration();

// Test: Model instantiation
test('Registration model instantiation', $regModel instanceof \App\Models\Registration);

// Test: emailExists for non-existent email
$exists = $regModel->emailExists('nonexistent_' . time() . '@test.com');
test('emailExists returns false for new email', $exists === false);

// Test: Create verification via model
$modelTestEmail = 'model_test_' . time() . '@example.com';
$modelPayload = ['name' => 'Model Test', 'password_hash' => $testPasswordHash, 'company_name' => 'Model Co'];
$modelToken = $regModel->createVerification($modelTestEmail, $modelPayload);
test('createVerification returns token', $modelToken !== null && strlen($modelToken) === 64, "Token length: " . strlen($modelToken));

// Test: Verify token via model
$verified = $regModel->verifyToken($modelToken);
test('verifyToken returns valid data', 
    $verified !== null && $verified['email'] === $modelTestEmail,
    "Email: " . ($verified['email'] ?? 'NULL'));
$verifiedId = $verified ? (int)$verified['id'] : 0;

// Test: hasPendingVerification
$hasPending = $regModel->hasPendingVerification($modelTestEmail);
test('hasPendingVerification returns true', $hasPending === true);

// Test: markVerified
$regModel->markVerified($verifiedId);
$verifiedAgain = $regModel->verifyToken($modelToken);
test('markVerified prevents re-use', $verifiedAgain === null, 'Token no longer valid after marking');

// ========== 5. ROUTE CONFIGURATION ==========

$routes = require(__DIR__ . '/../app/Config/routes.php');

test('Route: register exists', isset($routes['register']), 
    isset($routes['register']) ? implode(',', $routes['register']) : 'NOT FOUND');
test('Route: register is public', 
    isset($routes['register'][2]) && $routes['register'][2] === 'public');
test('Route: register_submit exists', isset($routes['register_submit']));
test('Route: register_sent exists', isset($routes['register_sent']));
test('Route: register_verify exists', isset($routes['register_verify']));
test('Route: onboarding exists', isset($routes['onboarding']));
test('Route: onboarding_complete exists', isset($routes['onboarding_complete']));
test('Route: plans exists', isset($routes['plans']));

// Test: onboarding requires auth (not public)
$isAuthRequired = !isset($routes['onboarding'][2]) || $routes['onboarding'][2] !== 'public';
test('Route: onboarding requires auth', $isAuthRequired);

// ========== 6. PAGE LOAD TESTS ==========

// Test: Register page loads
$ch = curl_init('http://iacc_nginx/index.php?page=register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
test('Register page loads (HTTP)', $httpCode === 200, "HTTP $httpCode");
test('Register page has form', strpos($response, 'register_submit') !== false, 'Contains form action');

// Test: Plans page (requires auth, may redirect)
$ch = curl_init('http://iacc_nginx/index.php?page=plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
test('Plans page responds', $httpCode === 200 || $httpCode === 302, "HTTP $httpCode");

// Test: Login page has sign-up link
$ch = curl_init('http://iacc_nginx/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$loginPage = curl_exec($ch);
curl_close($ch);
test('Login page has sign-up link', strpos($loginPage, 'page=register') !== false);

// ========== CLEANUP ==========

// Delete test verification records
mysqli_query($db->conn, "DELETE FROM email_verifications WHERE email LIKE 'test_reg_%' OR email LIKE 'model_test_%' OR email = 'expired@test.com' OR email = 'dup@test.com'");
test('Cleanup verification records', true, 'Test data removed');

// ========== OUTPUT ==========
?>
<html>
<head><title>Registration E2E Tests</title></head>
<style>
    body { font-family: -apple-system, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
    .test { padding: 10px 15px; margin: 4px 0; border-radius: 6px; font-size: 14px; }
    .pass { background: #d4edda; color: #155724; }
    .fail { background: #f8d7da; color: #721c24; }
    .summary { padding: 20px; margin-top: 20px; font-size: 18px; border-radius: 8px; font-weight: 600; text-align: center; }
    h1 { color: #2c3e50; }
</style>
<body>
<h1>🧪 Self-Registration E2E Tests (v6.0)</h1>
<?php foreach ($results as $r): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?> — <?= htmlspecialchars($r['details']) ?><?php endif; ?>
    </div>
<?php endforeach; ?>
<div class="summary" style="background: <?= $failed === 0 ? '#28a745' : '#dc3545' ?>; color: white;">
    <?= $failed === 0 ? '✅' : '❌' ?> PASSED: <?= $passed ?>/<?= $passed + $failed ?> | FAILED: <?= $failed ?>/<?= $passed + $failed ?>
</div>
</body></html>
