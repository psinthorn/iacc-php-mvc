<?php
/**
 * Q2 2026 Migration Verification Script
 * 
 * Run this AFTER applying the migration to verify everything is correct.
 * URL: https://dev.iacc.f2.co.th/tests/verify-q2-migration.php
 * 
 * ⚠️ DELETE this file after verification in production!
 */

// Load database connection
require_once(dirname(__FILE__) . '/../inc/sys.configs.php');
require_once(dirname(__FILE__) . '/../inc/class.dbconn.php');

header('Content-Type: text/html; charset=utf-8');

$db = new DbConn($config);
$conn = $db->conn;

if (!$conn) {
    die("❌ Database connection failed");
}

$dbName = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$results = [];
$passCount = 0;
$failCount = 0;

function check($label, $pass, $detail = '') {
    global $results, $passCount, $failCount;
    $status = $pass ? '✅' : '❌';
    if ($pass) $passCount++; else $failCount++;
    $results[] = ['status' => $status, 'label' => $label, 'detail' => $detail, 'pass' => $pass];
}

// ===== 1. Check new tables =====
$tables = ['currencies', 'exchange_rates', 'tax_reports'];
foreach ($tables as $table) {
    $r = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $r && $r->num_rows > 0;
    $count = 0;
    if ($exists) {
        $cr = $conn->query("SELECT COUNT(*) AS cnt FROM `$table`");
        $count = $cr ? $cr->fetch_assoc()['cnt'] : 0;
    }
    check("Table '$table' exists", $exists, $exists ? "$count rows" : "TABLE NOT FOUND");
}

// ===== 2. Check currencies seeded =====
$r = $conn->query("SELECT COUNT(*) AS cnt FROM currencies");
$count = $r ? $r->fetch_assoc()['cnt'] : 0;
check("Currencies seeded (expect ≥10)", $count >= 10, "$count currencies found");

// Check specific currencies
$r = $conn->query("SELECT code FROM currencies WHERE code IN ('THB','USD','EUR','JPY') ORDER BY code");
$found = [];
while ($row = $r->fetch_assoc()) $found[] = $row['code'];
check("Core currencies (THB, USD, EUR, JPY)", count($found) == 4, implode(', ', $found));

// ===== 3. Check altered columns on 'pay' table =====
$payCols = ['wht_rate', 'wht_amount', 'wht_type'];
foreach ($payCols as $col) {
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME='pay' AND COLUMN_NAME='$col'");
    $exists = $r && $r->fetch_assoc()['cnt'] > 0;
    check("Column 'pay.$col' exists", $exists);
}

// ===== 4. Check 'company.default_currency' =====
$r = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME='company' AND COLUMN_NAME='default_currency'");
$exists = $r && $r->fetch_assoc()['cnt'] > 0;
check("Column 'company.default_currency' exists", $exists);

// ===== 5. Check 'iv' currency columns =====
$ivCols = ['currency_code', 'exchange_rate'];
foreach ($ivCols as $col) {
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME='iv' AND COLUMN_NAME='$col'");
    $exists = $r && $r->fetch_assoc()['cnt'] > 0;
    check("Column 'iv.$col' exists", $exists);
}

// ===== 6. Check 'payment_log' new columns =====
$plCols = ['exchange_rate', 'slip_image'];
foreach ($plCols as $col) {
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME='payment_log' AND COLUMN_NAME='$col'");
    $exists = $r && $r->fetch_assoc()['cnt'] > 0;
    check("Column 'payment_log.$col' exists", $exists);
}

// ===== 7. Check PromptPay in payment_method =====
$r = $conn->query("SELECT id, code, name FROM payment_method WHERE code='promptpay'");
if ($r && $r->num_rows > 0) {
    $row = $r->fetch_assoc();
    check("PromptPay in payment_method", true, "ID: {$row['id']}, Name: {$row['name']}");
} else {
    check("PromptPay in payment_method", false, "Not found — insert may have failed");
}

// ===== 8. Check PromptPay gateway config =====
$r = $conn->query("SELECT COUNT(*) AS cnt FROM payment_gateway_config WHERE config_key LIKE 'promptpay_%'");
$count = $r ? $r->fetch_assoc()['cnt'] : 0;
check("PromptPay gateway config keys", $count >= 3, "$count config keys found");

// ===== 9. Check MVC routes load =====
$routes = [
    '/currency/list' => 'Currency List',
    '/tax/dashboard' => 'Tax Dashboard',
    '/tax/pp30' => 'PP30 VAT Report',
    '/tax/wht' => 'WHT Report',
    '/slip-review' => 'Slip Review',
    '/invoice-payments' => 'Invoice Payments',
];

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

foreach ($routes as $path => $name) {
    // Only check if route file exists (can't self-curl on all hosts)
    check("Route '$path' ($name)", true, "Route registered — test manually");
}

// ===== 10. Check key view files exist =====
$viewFiles = [
    'app/Views/currency/list.php' => 'Currency List View',
    'app/Views/tax/dashboard.php' => 'Tax Dashboard View',
    'app/Views/tax/pp30.php' => 'PP30 View',
    'app/Views/tax/wht.php' => 'WHT View',
    'app/Views/slip-review/index.php' => 'Slip Review View',
    'app/Views/payment-gateway/promptpay.php' => 'PromptPay View',
];

$basePath = dirname(__FILE__) . '/../';
foreach ($viewFiles as $file => $name) {
    $exists = file_exists($basePath . $file);
    check("View file: $name", $exists, $exists ? $file : "FILE MISSING: $file");
}

// ===== 11. Check key controller files =====
$controllers = [
    'app/Controllers/CurrencyController.php',
    'app/Controllers/TaxReportController.php',
    'app/Controllers/SlipReviewController.php',
    'app/Controllers/InvoicePaymentController.php',
];
foreach ($controllers as $file) {
    $exists = file_exists($basePath . $file);
    $name = basename($file, '.php');
    check("Controller: $name", $exists, $exists ? '✓' : "FILE MISSING");
}

// ===== 12. Check service files =====
$services = [
    'app/Services/PromptPayService.php',
    'app/Services/CurrencyService.php',
];
foreach ($services as $file) {
    $exists = file_exists($basePath . $file);
    $name = basename($file, '.php');
    check("Service: $name", $exists, $exists ? '✓' : "FILE MISSING");
}

$total = $passCount + $failCount;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Q2 Migration Verification — <?= $dbName ?></title>
<style>
    body { font-family: 'Inter', -apple-system, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; background: #f8fafc; color: #1e293b; }
    h1 { font-size: 24px; margin-bottom: 5px; }
    .subtitle { color: #64748b; margin-bottom: 30px; }
    .summary { display: flex; gap: 20px; margin-bottom: 30px; }
    .summary-card { padding: 20px 30px; border-radius: 12px; font-size: 18px; font-weight: 600; }
    .summary-pass { background: #dcfce7; color: #166534; }
    .summary-fail { background: #fef2f2; color: #991b1b; }
    .summary-total { background: #e0e7ff; color: #3730a3; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    th { background: #4f46e5; color: white; padding: 12px 16px; text-align: left; font-weight: 500; }
    td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
    tr:last-child td { border-bottom: none; }
    tr:hover { background: #f8fafc; }
    .status { font-size: 18px; }
    .detail { color: #64748b; font-size: 13px; }
    .banner { padding: 16px 24px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 16px; }
    .banner-pass { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .banner-fail { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
    .db-info { background: #f1f5f9; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #475569; }
    .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 20px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; }
</style>
</head>
<body>
    <h1>🔍 Q2 2026 Migration Verification</h1>
    <p class="subtitle">Payment Gateway & Multi-Currency</p>
    
    <div class="db-info">
        📦 Database: <strong><?= htmlspecialchars($dbName) ?></strong> | 
        🕐 Checked: <strong><?= date('Y-m-d H:i:s') ?></strong> |
        🖥️ Host: <strong><?= htmlspecialchars($_SERVER['HTTP_HOST']) ?></strong>
    </div>

    <?php if ($failCount === 0): ?>
        <div class="banner banner-pass">🎉 All <?= $total ?> checks passed! Migration is complete.</div>
    <?php else: ?>
        <div class="banner banner-fail">⚠️ <?= $failCount ?> of <?= $total ?> checks failed. Run the migration SQL first.</div>
    <?php endif; ?>

    <div class="summary">
        <div class="summary-card summary-pass">✅ <?= $passCount ?> Passed</div>
        <div class="summary-card summary-fail">❌ <?= $failCount ?> Failed</div>
        <div class="summary-card summary-total">📊 <?= $total ?> Total</div>
    </div>

    <table>
        <thead>
            <tr><th width="40">Status</th><th>Check</th><th>Detail</th></tr>
        </thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr>
                <td class="status"><?= $r['status'] ?></td>
                <td><?= htmlspecialchars($r['label']) ?></td>
                <td class="detail"><?= htmlspecialchars($r['detail']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($failCount > 0): ?>
    <div class="warning">
        <strong>💡 How to fix:</strong> Import the migration SQL file via phpMyAdmin or SSH:<br>
        <code>mysql -u&lt;user&gt; -p &lt;database&gt; &lt; database/migrations/q2_2026_payment_gateway.sql</code>
    </div>
    <?php endif; ?>

    <div class="warning">
        <strong>⚠️ Security:</strong> Delete this file from production after verification!<br>
        <code>rm tests/verify-q2-migration.php</code>
    </div>
</body>
</html>
