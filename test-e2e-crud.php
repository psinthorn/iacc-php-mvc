<?php
/**
 * E2E CRUD Test Suite
 * Tests all CRUD operations for main entities
 * 
 * Run via: http://localhost/test-e2e-crud.php
 * Or CLI: docker exec iacc_php php /var/www/html/test-e2e-crud.php
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");

// Test configuration
$_SESSION['com_id'] = 95;  // Test company ID (vendor)
$_SESSION['user_id'] = 1;   // Test user ID

$db = new DbConn($config);
$har = new HardClass();
$har->setConnection($db->conn);

// Test results tracking
$results = [];
$passed = 0;
$failed = 0;

// Helper functions
function test($name, $condition, $details = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $results[] = ['name' => $name, 'status' => 'PASS', 'details' => $details];
        $passed++;
        return true;
    } else {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'details' => $details];
        $failed++;
        return false;
    }
}

function cleanup($db, $table, $id) {
    mysqli_query($db->conn, "DELETE FROM {$table} WHERE id = {$id}");
}

function getLastInsertId($db) {
    return mysqli_insert_id($db->conn);
}

echo "<html><head><title>E2E CRUD Tests</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; }
    .test { padding: 10px; margin: 5px 0; border-radius: 4px; }
    .pass { background: #d4edda; color: #155724; }
    .fail { background: #f8d7da; color: #721c24; }
    .summary { padding: 20px; margin-top: 20px; border-radius: 4px; font-size: 18px; }
    .summary-pass { background: #28a745; color: white; }
    .summary-fail { background: #dc3545; color: white; }
    .details { font-size: 12px; color: #666; margin-top: 5px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🧪 E2E CRUD Test Suite</h1>";
echo "<p>Testing all CRUD operations for iACC PHP MVC</p>";

// ==========================================
// TEST 1: COMPANY CRUD
// ==========================================
echo "<h2>1. Company CRUD</h2>";

// Create Company
$test_company_name = "Test Company E2E " . time();
$sql = "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id) 
        VALUES ('{$test_company_name}', 'บริษัททดสอบ', 'TEST', 'John Doe', 'test@example.com', '0812345678', '021234567', '1234567890123', '1', '0', '', '', '{$_SESSION['com_id']}')";
$result = mysqli_query($db->conn, $sql);
$company_id = getLastInsertId($db);
test("Company CREATE", $result && $company_id > 0, "Created company ID: {$company_id}");

// Read Company
$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM company WHERE id = {$company_id}"));
test("Company READ", $read && $read['name_en'] === $test_company_name, "Read name: " . ($read['name_en'] ?? 'NULL'));

// Update Company
$updated_name = "Updated Company E2E " . time();
$sql = "UPDATE company SET name_en = '{$updated_name}', customer = '0', vender = '1' WHERE id = {$company_id}";
$result = mysqli_query($db->conn, $sql);
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM company WHERE id = {$company_id}"));
test("Company UPDATE", $verify && $verify['name_en'] === $updated_name && $verify['vender'] === '1', "Updated to: {$updated_name}");

// Create Company Address
$sql = "INSERT INTO company_addr (com_id, adr_tax, city_tax, district_tax, province_tax, zip_tax, adr_bil, city_bil, district_bil, province_bil, zip_bil, valid_start, valid_end) 
        VALUES ({$company_id}, '123 Test St', 'Test City', 'Test District', 'Bangkok', '10100', '123 Test St', 'Test City', 'Test District', 'Bangkok', '10100', CURDATE(), '9999-12-31')";
$result = mysqli_query($db->conn, $sql);
$addr_id = getLastInsertId($db);
test("Company Address CREATE", $result && $addr_id > 0, "Created address ID: {$addr_id}");

// Soft Delete Company
$sql = "UPDATE company SET deleted_at = NOW() WHERE id = {$company_id}";
$result = mysqli_query($db->conn, $sql);
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM company WHERE id = {$company_id}"));
test("Company SOFT DELETE", $verify && $verify['deleted_at'] !== null, "Soft deleted at: " . ($verify['deleted_at'] ?? 'NULL'));

// Hard Delete (cleanup)
cleanup($db, 'company_addr', $addr_id);
cleanup($db, 'company', $company_id);
test("Company HARD DELETE (cleanup)", true, "Cleaned up test data");

// ==========================================
// TEST 2: CATEGORY CRUD
// ==========================================
echo "<h2>2. Category CRUD</h2>";

$test_cat_name = "Test Category E2E " . time();
$sql = "INSERT INTO category (company_id, cat_name, des) VALUES ('{$_SESSION['com_id']}', '{$test_cat_name}', 'Test Description')";
$result = mysqli_query($db->conn, $sql);
$cat_id = getLastInsertId($db);
test("Category CREATE", $result && $cat_id > 0, "Created category ID: {$cat_id}");

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM category WHERE id = {$cat_id}"));
test("Category READ", $read && $read['cat_name'] === $test_cat_name, "Read: " . ($read['cat_name'] ?? 'NULL'));

$updated_cat = "Updated Category " . time();
mysqli_query($db->conn, "UPDATE category SET cat_name = '{$updated_cat}' WHERE id = {$cat_id}");
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM category WHERE id = {$cat_id}"));
test("Category UPDATE", $verify && $verify['cat_name'] === $updated_cat, "Updated to: {$updated_cat}");

cleanup($db, 'category', $cat_id);
test("Category DELETE", true, "Cleaned up");

// ==========================================
// TEST 3: TYPE CRUD
// ==========================================
echo "<h2>3. Type (Product Type) CRUD</h2>";

// First create a category for the type
mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES ('{$_SESSION['com_id']}', 'Temp Cat', 'Temp')");
$temp_cat_id = getLastInsertId($db);

$test_type_name = "Test Type E2E " . time();
$sql = "INSERT INTO type (company_id, name, des, cat_id) VALUES ('{$_SESSION['com_id']}', '{$test_type_name}', 'Test Type Desc', {$temp_cat_id})";
$result = mysqli_query($db->conn, $sql);
$type_id = getLastInsertId($db);
test("Type CREATE", $result && $type_id > 0, "Created type ID: {$type_id}");

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM type WHERE id = {$type_id}"));
test("Type READ", $read && $read['name'] === $test_type_name, "Read: " . ($read['name'] ?? 'NULL'));

$updated_type = "Updated Type " . time();
mysqli_query($db->conn, "UPDATE type SET name = '{$updated_type}' WHERE id = {$type_id}");
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM type WHERE id = {$type_id}"));
test("Type UPDATE", $verify && $verify['name'] === $updated_type, "Updated to: {$updated_type}");

cleanup($db, 'type', $type_id);
cleanup($db, 'category', $temp_cat_id);
test("Type DELETE", true, "Cleaned up");

// ==========================================
// TEST 4: BRAND CRUD
// ==========================================
echo "<h2>4. Brand CRUD</h2>";

$test_brand_name = "Test Brand E2E " . time();
$sql = "INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES ('{$_SESSION['com_id']}', '{$test_brand_name}', 'Brand Desc', '', '{$_SESSION['com_id']}')";
$result = mysqli_query($db->conn, $sql);
$brand_id = getLastInsertId($db);
test("Brand CREATE", $result && $brand_id > 0, "Created brand ID: {$brand_id}");

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM brand WHERE id = {$brand_id}"));
test("Brand READ", $read && $read['brand_name'] === $test_brand_name, "Read: " . ($read['brand_name'] ?? 'NULL'));

$updated_brand = "Updated Brand " . time();
mysqli_query($db->conn, "UPDATE brand SET brand_name = '{$updated_brand}' WHERE id = {$brand_id}");
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM brand WHERE id = {$brand_id}"));
test("Brand UPDATE", $verify && $verify['brand_name'] === $updated_brand, "Updated to: {$updated_brand}");

cleanup($db, 'brand', $brand_id);
test("Brand DELETE", true, "Cleaned up");

// ==========================================
// TEST 5: MODEL CRUD
// ==========================================
echo "<h2>5. Model CRUD</h2>";

// Create temp category, type, brand for model
mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES ('{$_SESSION['com_id']}', 'Model Test Cat', 'Temp')");
$temp_cat2 = getLastInsertId($db);
mysqli_query($db->conn, "INSERT INTO type (company_id, name, des, cat_id) VALUES ('{$_SESSION['com_id']}', 'Model Test Type', 'Temp', {$temp_cat2})");
$temp_type = getLastInsertId($db);
mysqli_query($db->conn, "INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES ('{$_SESSION['com_id']}', 'Model Test Brand', 'Temp', '', '{$_SESSION['com_id']}')");
$temp_brand = getLastInsertId($db);

$test_model_name = "Test Model E2E " . time();
$sql = "INSERT INTO model (company_id, type_id, brand_id, model_name, des, price) VALUES ('{$_SESSION['com_id']}', {$temp_type}, {$temp_brand}, '{$test_model_name}', 'Model Desc', '1000.00')";
$result = mysqli_query($db->conn, $sql);
$model_id = getLastInsertId($db);
test("Model CREATE", $result && $model_id > 0, "Created model ID: {$model_id}, Error: " . mysqli_error($db->conn));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM model WHERE id = {$model_id}"));
test("Model READ", $read && $read['model_name'] === $test_model_name, "Read: " . ($read['model_name'] ?? 'NULL'));

$updated_model = "Updated Model " . time();
mysqli_query($db->conn, "UPDATE model SET model_name = '{$updated_model}', price = '2000.00' WHERE id = {$model_id}");
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM model WHERE id = {$model_id}"));
test("Model UPDATE", $verify && $verify['model_name'] === $updated_model && $verify['price'] == '2000.00', "Updated to: {$updated_model}, price: 2000");

cleanup($db, 'model', $model_id);
cleanup($db, 'brand', $temp_brand);
cleanup($db, 'type', $temp_type);
cleanup($db, 'category', $temp_cat2);
test("Model DELETE", true, "Cleaned up");

// ==========================================
// TEST 6: PR/PO WORKFLOW
// ==========================================
echo "<h2>6. PR/PO Workflow (Full Integration)</h2>";

// Setup: Create customer company (all required fields)
$cust_sql = "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id) VALUES ('E2E Test Customer', 'ลูกค้าทดสอบ', 'CUST', 'Test Contact', 'test@test.com', '0800000000', '', '1234567890000', '1', '0', '', '', '{$_SESSION['com_id']}')";
$cust_result = mysqli_query($db->conn, $cust_sql);
$customer_id = getLastInsertId($db);
test("Setup: Create Customer", $cust_result && $customer_id > 0, "Customer ID: {$customer_id}, Error: " . mysqli_error($db->conn));

// Setup: Create product type, model
mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES ('{$_SESSION['com_id']}', 'PR Test Cat', 'Test')");
$pr_cat = getLastInsertId($db);
mysqli_query($db->conn, "INSERT INTO type (company_id, name, des, cat_id) VALUES ('{$_SESSION['com_id']}', 'PR Test Type', 'Test', {$pr_cat})");
$pr_type = getLastInsertId($db);
mysqli_query($db->conn, "INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES ('{$_SESSION['com_id']}', 'PR Test Brand', 'Test', '', '{$_SESSION['com_id']}')");
$pr_brand = getLastInsertId($db);
mysqli_query($db->conn, "INSERT INTO model (company_id, type_id, brand_id, model_name, des, price) VALUES ('{$_SESSION['com_id']}', {$pr_type}, {$pr_brand}, 'PR Test Model', 'Test', '5000.00')");
$pr_model = getLastInsertId($db);

// Create PR - first check if customer was created
if ($customer_id <= 0) {
    $customer_id = 158; // Use existing customer as fallback
}
$pr_name = "Test PR E2E " . time();
$sql = "INSERT INTO pr (company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby) 
        VALUES ('{$_SESSION['com_id']}', '{$pr_name}', 'Test PR Description', '{$_SESSION['user_id']}', {$customer_id}, '{$_SESSION['com_id']}', CURDATE(), '0', '0', '0', '0')";
$result = mysqli_query($db->conn, $sql);
$pr_id = getLastInsertId($db);
test("PR CREATE", $result && $pr_id > 0, "Created PR ID: {$pr_id}, Error: " . mysqli_error($db->conn));

$read_pr = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM pr WHERE id = {$pr_id}"));
test("PR READ", $read_pr && $read_pr['name'] === $pr_name && $read_pr['status'] === '0', "PR status: " . ($read_pr['status'] ?? 'NULL'));

// Create PO using the new column-based insert
$po_max = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT MAX(id) as max_id FROM po"));
$po_next_id = ($po_max['max_id'] ?? 0) + 1;
$tax_number = (date("y") + 43) . str_pad($po_next_id, 6, '0', STR_PAD_LEFT);

$sql = "INSERT INTO po (company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at) 
        VALUES ('{$_SESSION['com_id']}', '', '{$pr_name}', '{$pr_id}', '{$tax_number}', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), '', '', '0', '0', '7', '0', NULL)";
$result = mysqli_query($db->conn, $sql);
$po_id = getLastInsertId($db);
test("PO CREATE (with explicit columns)", $result && $po_id > 0, "Created PO ID: {$po_id}, Tax: {$tax_number}");

// Update PR status to 1 (has quotation)
mysqli_query($db->conn, "UPDATE pr SET status = '1' WHERE id = {$pr_id}");
$verify_pr = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT status FROM pr WHERE id = {$pr_id}"));
test("PR Status Update to 1", $verify_pr && $verify_pr['status'] === '1', "PR status now: " . ($verify_pr['status'] ?? 'NULL'));

// Add product to PO (pro_id is auto-increment)
$sql = "INSERT INTO product (company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id) 
        VALUES ('{$_SESSION['com_id']}', {$po_id}, '5000.00', '0', {$pr_brand}, {$pr_model}, {$pr_type}, '2', '1', '0', 'Test Product', '0', '0', '0', '1970-01-01', '0')";
$result = mysqli_query($db->conn, $sql);
$product_id = getLastInsertId($db);
test("Product CREATE for PO", $result && $product_id > 0, "Created product ID: {$product_id}, Error: " . mysqli_error($db->conn));

// Verify PO with JOIN
$po_with_pr = mysqli_fetch_assoc(mysqli_query($db->conn, "
    SELECT po.*, pr.name as pr_name, pr.status as pr_status 
    FROM po 
    JOIN pr ON po.ref = pr.id 
    WHERE po.id = {$po_id}
"));
test("PO-PR JOIN", $po_with_pr && $po_with_pr['pr_name'] === $pr_name, "PO linked to PR: " . ($po_with_pr['pr_name'] ?? 'NULL'));

// Simulate PO Edit (create new version) - this is what method=E does
$po_max2 = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT MAX(id) as max_id FROM po"));
$po_next_id2 = ($po_max2['max_id'] ?? 0) + 1;
$tax_number2 = (date("y") + 43) . str_pad($po_next_id2, 6, '0', STR_PAD_LEFT);

$sql = "INSERT INTO po (company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at) 
        VALUES ('{$_SESSION['com_id']}', '', '{$pr_name} v2', '{$pr_id}', '{$tax_number2}', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), '', '', '5', '0', '7', '0', NULL)";
$result = mysqli_query($db->conn, $sql);
$po_id_new = getLastInsertId($db);

// Update old PO to point to new one
mysqli_query($db->conn, "UPDATE po SET po_id_new = '{$po_id_new}' WHERE id = {$po_id}");
test("PO EDIT (create new version)", $result && $po_id_new > 0, "Old PO {$po_id} -> New PO {$po_id_new}");

// Verify old PO is now linked
$old_po = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT po_id_new FROM po WHERE id = {$po_id}"));
test("Old PO linked to new", $old_po && $old_po['po_id_new'] == $po_id_new, "po_id_new: " . ($old_po['po_id_new'] ?? 'NULL'));

// Test exp.php visibility logic (should NOT show old PO)
$exp_query = mysqli_query($db->conn, "
    SELECT po.id FROM po 
    JOIN pr ON po.ref = pr.id 
    WHERE po.id = {$po_id} 
    AND pr.status > '0' 
    AND po.po_id_new = ''
");
$exp_visible = mysqli_num_rows($exp_query);
test("Old PO hidden from exp.php", $exp_visible === 0, "Old PO correctly filtered out");

// Test new PO IS visible
$exp_query2 = mysqli_query($db->conn, "
    SELECT po.id FROM po 
    JOIN pr ON po.ref = pr.id 
    WHERE po.id = {$po_id_new} 
    AND pr.status > '0' 
    AND po.po_id_new = ''
");
$exp_visible2 = mysqli_num_rows($exp_query2);
test("New PO visible in exp.php", $exp_visible2 === 1, "New PO correctly visible");

// Cleanup PR/PO test data
echo "<h3>Cleanup</h3>";
mysqli_query($db->conn, "DELETE FROM product WHERE po_id IN ({$po_id}, {$po_id_new})");
mysqli_query($db->conn, "DELETE FROM po WHERE id IN ({$po_id}, {$po_id_new})");
mysqli_query($db->conn, "DELETE FROM pr WHERE id = {$pr_id}");
mysqli_query($db->conn, "DELETE FROM model WHERE id = {$pr_model}");
mysqli_query($db->conn, "DELETE FROM brand WHERE id = {$pr_brand}");
mysqli_query($db->conn, "DELETE FROM type WHERE id = {$pr_type}");
mysqli_query($db->conn, "DELETE FROM category WHERE id = {$pr_cat}");
mysqli_query($db->conn, "DELETE FROM company WHERE id = {$customer_id}");
test("PR/PO Cleanup", true, "All test data removed");

// ==========================================
// TEST 7: Payment Method CRUD
// ==========================================
echo "<h2>7. Payment Method CRUD</h2>";

$test_payment = "Test Payment E2E " . time();
$sql = "INSERT INTO payment (payment_name, payment_des, com_id) VALUES ('{$test_payment}', 'Test payment description', '{$_SESSION['com_id']}')";
$result = mysqli_query($db->conn, $sql);
$payment_id = getLastInsertId($db);
test("Payment CREATE", $result && $payment_id > 0, "Created payment ID: {$payment_id}, Error: " . mysqli_error($db->conn));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM payment WHERE id = {$payment_id}"));
test("Payment READ", $read && $read['payment_name'] === $test_payment, "Read: " . ($read['payment_name'] ?? 'NULL'));

$updated_payment = "Updated Payment " . time();
mysqli_query($db->conn, "UPDATE payment SET payment_name = '{$updated_payment}' WHERE id = {$payment_id}");
$verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM payment WHERE id = {$payment_id}"));
test("Payment UPDATE", $verify && $verify['payment_name'] === $updated_payment, "Updated to: {$updated_payment}");

cleanup($db, 'payment', $payment_id);
test("Payment DELETE", true, "Cleaned up");

// ==========================================
// TEST 8: HardClass Insert Functions
// ==========================================
echo "<h2>8. HardClass Insert Functions (New Column Support)</h2>";

// Test insertDbMax with columns parameter
$args = [
    'table' => 'category',
    'columns' => 'company_id, cat_name, des',
    'value' => "'{$_SESSION['com_id']}', 'HardClass Test Cat', 'Testing insertDbMax with columns'"
];
$result_id = $har->insertDbMax($args);
test("insertDbMax with columns", $result_id !== false && $result_id > 0, "Inserted ID: {$result_id}");

if ($result_id) {
    $verify = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM category WHERE id = {$result_id}"));
    test("insertDbMax data verification", $verify && $verify['cat_name'] === 'HardClass Test Cat', "cat_name: " . ($verify['cat_name'] ?? 'NULL'));
    cleanup($db, 'category', $result_id);
}

// Test insertDb with columns parameter
$args2 = [
    'table' => 'category',
    'columns' => 'id, company_id, cat_name, des, deleted_at',
    'value' => "NULL, '{$_SESSION['com_id']}', 'HardClass Test Cat 2', 'Testing insertDb with columns', NULL"
];
$result2 = $har->insertDb($args2);
$result_id2 = mysqli_insert_id($db->conn);
test("insertDb with columns", $result2 && $result_id2 > 0, "Inserted ID: {$result_id2}");

if ($result_id2) {
    cleanup($db, 'category', $result_id2);
}

// Test legacy insertDbMax (without columns - backward compatibility)
$args3 = [
    'table' => 'category',
    'value' => "'{$_SESSION['com_id']}', 'Legacy Test Cat', 'Testing legacy insertDbMax', NULL"
];
$result_id3 = $har->insertDbMax($args3);
test("insertDbMax legacy (no columns)", $result_id3 !== false && $result_id3 > 0, "Inserted ID: {$result_id3}");

if ($result_id3) {
    cleanup($db, 'category', $result_id3);
}

// ==========================================
// SUMMARY
// ==========================================
echo "<h2>📊 Test Summary</h2>";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "<div class='summary " . ($failed === 0 ? 'summary-pass' : 'summary-fail') . "'>";
echo "✅ Passed: {$passed} | ❌ Failed: {$failed} | Total: {$total} | Success Rate: {$percentage}%";
echo "</div>";

echo "<h3>Detailed Results</h3>";
foreach ($results as $r) {
    $class = $r['status'] === 'PASS' ? 'pass' : 'fail';
    $icon = $r['status'] === 'PASS' ? '✅' : '❌';
    echo "<div class='test {$class}'>";
    echo "<strong>{$icon} {$r['name']}</strong>";
    if ($r['details']) {
        echo "<div class='details'>{$r['details']}</div>";
    }
    echo "</div>";
}

// Check logs for any errors
echo "<h3>Application Logs (last 20 lines)</h3>";
$log_content = @file_get_contents('/var/www/html/logs/app.log');
if ($log_content) {
    $lines = array_slice(explode("\n", trim($log_content)), -20);
    echo "<pre>" . htmlspecialchars(implode("\n", $lines)) . "</pre>";
} else {
    echo "<p>No log entries or log file not accessible</p>";
}

echo "</div></body></html>";
?>
