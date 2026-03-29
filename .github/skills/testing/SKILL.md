---
name: testing
description: 'Write and run tests for the iACC application. USE FOR: E2E CRUD tests, API integration tests, MVC comprehensive tests, test runner setup, assertion patterns, test data management. Use when: writing tests, running test suites, debugging test failures, adding test coverage, verifying CRUD operations, testing API endpoints, validating calculations.'
argument-hint: 'Describe what to test or which test suite to run'
---

# Testing — iACC Test Suites

## When to Use

- Writing new test cases
- Running existing test suites
- Debugging test failures
- Verifying CRUD operations work correctly
- Testing API endpoints
- Validating calculations (VAT, discounts, totals)

## Test Suites (188 Total)

| Suite             | File                               | Tests | Purpose                  |
| ----------------- | ---------------------------------- | ----- | ------------------------ |
| E2E CRUD          | `tests/test-e2e-crud.php`          | 42    | Database CRUD operations |
| API Phase 3       | `tests/test-api-phase3.php`        | 20    | Sales Channel API        |
| MVC Comprehensive | `tests/test-mvc-comprehensive.php` | 126   | MVC layer integration    |

## Running Tests

```bash
# Via Docker (recommended)
docker exec iacc_php php /var/www/html/tests/test-e2e-crud.php
docker exec iacc_php php /var/www/html/tests/test-api-phase3.php
docker exec iacc_php php /var/www/html/tests/test-mvc-comprehensive.php

# Via HTTP
curl -s "http://localhost/tests/test-e2e-crud.php"

# Via CLI
php tests/test-e2e-crud.php

# PHP syntax check
php -l app/Controllers/MyController.php
find app/ -name "*.php" | xargs -I {} php -l {}
```

## Procedures

### 1. Write a New E2E Test

```php
<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");

// Setup test session
$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 1;

$db = new DbConn($config);
$har = new HardClass();
$har->setConnection($db->conn);

$results = [];
$passed = 0;
$failed = 0;

// Test helper
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

// ========== TESTS ==========

// Test: Create record
$id = $har->Maxid('my_table');
$sql = "INSERT INTO my_table (id, name, company_id) VALUES ({$id}, 'Test', 95)";
$result = mysqli_query($db->conn, $sql);
test('MyTable CREATE', $result !== false, "Created ID: {$id}");

// Test: Read record
$row = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM my_table WHERE id = {$id}"));
test('MyTable READ', $row !== null && $row['name'] === 'Test', "Name: " . ($row['name'] ?? 'NULL'));

// Test: Update record
$sql = "UPDATE my_table SET name = 'Updated' WHERE id = {$id}";
mysqli_query($db->conn, $sql);
$row = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM my_table WHERE id = {$id}"));
test('MyTable UPDATE', $row['name'] === 'Updated');

// Test: Soft delete
$sql = "UPDATE my_table SET deleted_at = NOW() WHERE id = {$id}";
mysqli_query($db->conn, $sql);
$row = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM my_table WHERE id = {$id} AND deleted_at IS NULL"));
test('MyTable SOFT DELETE', $row === null, 'Record hidden after soft delete');

// Cleanup
mysqli_query($db->conn, "DELETE FROM my_table WHERE id = {$id}");

// ========== OUTPUT ==========
?>
<html>
<style>
    .test { padding: 10px; margin: 5px 0; border-radius: 4px; }
    .pass { background: #d4edda; color: #155724; }
    .fail { background: #f8d7da; color: #721c24; }
    .summary { padding: 20px; margin-top: 20px; font-size: 18px; border-radius: 8px; }
</style>
<body>
<?php foreach ($results as $r): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= $r['name'] ?>
        <?php if ($r['details']): ?> — <?= $r['details'] ?><?php endif; ?>
    </div>
<?php endforeach; ?>
<div class="summary" style="background: <?= $failed === 0 ? '#28a745' : '#dc3545' ?>; color: white;">
    ✓ PASSED: <?= $passed ?>/<?= $passed + $failed ?> | FAILED: <?= $failed ?>/<?= $passed + $failed ?>
</div>
</body></html>
```

### 2. Write an API Test

```php
$API_BASE = 'http://iacc_nginx/api.php/v1';
$API_KEY = 'iACC_test_e2e_key_001';
$API_SECRET = 'iACC_test_e2e_secret_001';

function api_request($method, $url, $data = null) {
    global $API_BASE, $API_KEY, $API_SECRET;
    $ch = curl_init($API_BASE . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $API_KEY,
        'X-API-Secret: ' . $API_SECRET,
        'X-Idempotency-Key: ' . uniqid('test_'),
    ]);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($response, true)];
}

// Test: Create order
$r = api_request('POST', '/orders', ['guest_name' => 'Test', 'total_amount' => 5000]);
test('API Create Order', $r['code'] === 201, "HTTP {$r['code']}");

// Test: Rate limit handling (retry on 429)
function api_with_retry($method, $url, $data = null, $retries = 3) {
    for ($i = 0; $i <= $retries; $i++) {
        $r = api_request($method, $url, $data);
        if ($r['code'] !== 429) return $r;
        sleep(15);  // Wait before retry
    }
    return $r;
}
```

### 3. Calculation Tests

```php
// VAT Calculation
$subtotal = 10000;
$vat_rate = 7;
$vat = $subtotal * ($vat_rate / 100);  // 700
test('VAT 7%', $vat === 700.0, "VAT: {$vat}");

// Discount + VAT
$discount_rate = 10;
$discount = $subtotal * ($discount_rate / 100);  // 1000
$after_discount = $subtotal - $discount;          // 9000
$vat = $after_discount * ($vat_rate / 100);       // 630
$total = $after_discount + $vat;                  // 9630
test('Discount+VAT', $total === 9630.0, "Total: {$total}");
```

## Test Categories

| Category         | What to Test                                         |
| ---------------- | ---------------------------------------------------- |
| **CRUD**         | Create, Read, Update, Soft Delete for each table     |
| **Relations**    | Foreign key integrity (PR→PO→Product)                |
| **Calculations** | VAT, discounts, overhead, grand totals               |
| **Multi-tenant** | Company_id filtering, cross-company denial           |
| **Auth**         | Login, session, rate limiting, lockout               |
| **API**          | Endpoints, auth, rate limits, idempotency, webhooks  |
| **MVC**          | Controller loading, route resolution, view rendering |

## Important: Test Cleanup

Always clean up test data to avoid polluting the database:

```php
// At end of test file
mysqli_query($db->conn, "DELETE FROM my_table WHERE name LIKE 'Test%' AND company_id = 95");
```
