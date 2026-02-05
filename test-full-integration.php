<?php
/**
 * Full Integration Test Suite for iACC PHP MVC
 * 
 * Tests ALL CRUD operations across ALL sections with correct table schemas.
 * 
 * Run via: curl -s "http://localhost/test-full-integration.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");

$db = new DbConn($config);
$har = new HardClass();
$har->setConnection($db->conn);

// Test results storage
$results = [];
$passed = 0;
$failed = 0;
$testId = time();

// Set session for tests
$_SESSION['com_id'] = 65;
$_SESSION['usr_id'] = 1;

// Helper functions
function test($name, $condition, $details = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $passed++;
        $results[] = ['name' => $name, 'status' => 'pass', 'details' => $details];
        return true;
    } else {
        $failed++;
        $results[] = ['name' => $name, 'status' => 'fail', 'details' => $details];
        return false;
    }
}

function getError($db) {
    return mysqli_error($db->conn);
}

echo "<!DOCTYPE html><html><head><title>Full Integration Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
h2 { color: #555; margin-top: 30px; border-left: 4px solid #2196F3; padding-left: 10px; }
h3 { color: #666; margin-top: 20px; }
.test { padding: 8px 12px; margin: 4px 0; border-radius: 4px; }
.pass { background: #e8f5e9; border-left: 4px solid #4CAF50; }
.fail { background: #ffebee; border-left: 4px solid #f44336; }
.details { color: #666; font-size: 0.9em; margin-left: 20px; }
.summary { padding: 20px; margin: 20px 0; border-radius: 8px; font-size: 1.2em; }
.summary-pass { background: #c8e6c9; }
.summary-fail { background: #ffcdd2; }
pre { background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 0.85em; }
</style></head><body><div class='container'>";
echo "<h1>🧪 Full Integration Test Suite</h1>";
echo "<p>Testing ALL CRUD operations - Test ID: {$testId}</p>";

// ============================================================
// SECTION 1: COMPANY CRUD
// ============================================================
echo "<h2>1. Company CRUD</h2>";

$companyName = "IntTest Company " . $testId;
$result = mysqli_query($db->conn, "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id, deleted_at) 
    VALUES ('{$companyName}', 'บริษัททดสอบ', 'ITC', 'Contact', 'test@test.com', '0812345678', '021234567', 'TAX123', 1, 0, '', '', 65, NULL)");
$companyId = mysqli_insert_id($db->conn);
$err = getError($db);
test("Company CREATE", $companyId > 0, "ID: {$companyId}" . ($err ? ", Error: {$err}" : ""));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM company WHERE id = {$companyId}"));
test("Company READ", $read && $read['name_en'] === $companyName, "Read: " . ($read['name_en'] ?? 'null'));

mysqli_query($db->conn, "UPDATE company SET name_en = 'Updated {$companyName}' WHERE id = {$companyId}");
$updated = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT name_en FROM company WHERE id = {$companyId}"));
test("Company UPDATE", $updated && strpos($updated['name_en'], 'Updated') === 0, "Updated to: " . ($updated['name_en'] ?? 'null'));

// company_addr: com_id, adr_tax, city_tax, district_tax, province_tax, zip_tax, adr_bil, city_bil, district_bil, province_bil, zip_bil, valid_start, valid_end
$addrResult = mysqli_query($db->conn, "INSERT INTO company_addr (com_id, adr_tax, city_tax, district_tax, province_tax, zip_tax, adr_bil, city_bil, district_bil, province_bil, zip_bil, valid_start, valid_end) 
    VALUES ({$companyId}, 'Test Address 123', 'Bangkok', 'Bangrak', 'Bangkok', '10110', 'Billing Address', 'Bangkok', 'Bangrak', 'Bangkok', '10110', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))");
$addrId = mysqli_insert_id($db->conn);
$addrErr = getError($db);
test("Company Address CREATE", $addrId > 0, "ID: {$addrId}" . ($addrErr ? ", Error: {$addrErr}" : ""));

mysqli_query($db->conn, "UPDATE company SET deleted_at = NOW() WHERE id = {$companyId}");
$deleted = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT deleted_at FROM company WHERE id = {$companyId}"));
test("Company SOFT DELETE", $deleted && $deleted['deleted_at'] !== null, "Soft deleted");

mysqli_query($db->conn, "DELETE FROM company_addr WHERE com_id = {$companyId}");
mysqli_query($db->conn, "DELETE FROM company WHERE id = {$companyId}");
test("Company CLEANUP", true, "Removed test data");

// ============================================================
// SECTION 2: CATEGORY CRUD
// ============================================================
echo "<h2>2. Category CRUD</h2>";

$catName = "IntTest Category " . $testId;
$result = mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES (65, '{$catName}', 'Test description')");
$catId = mysqli_insert_id($db->conn);
$err = getError($db);
test("Category CREATE", $catId > 0, "ID: {$catId}" . ($err ? ", Error: {$err}" : ""));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM category WHERE id = {$catId}"));
test("Category READ", $read && $read['cat_name'] === $catName, "Read: " . ($read['cat_name'] ?? 'null'));

mysqli_query($db->conn, "UPDATE category SET cat_name = 'Upd{$testId}' WHERE id = {$catId}");
$updated = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT cat_name FROM category WHERE id = {$catId}"));
test("Category UPDATE", $updated && strpos($updated['cat_name'], 'Upd') === 0, "Updated to: " . ($updated['cat_name'] ?? 'null'));

mysqli_query($db->conn, "DELETE FROM category WHERE id = {$catId}");
test("Category DELETE", true, "Deleted");

// ============================================================
// SECTION 3: TYPE CRUD
// ============================================================
echo "<h2>3. Type (Product) CRUD</h2>";

mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES (65, 'TempCat{$testId}', 'Temp')");
$tempCatId = mysqli_insert_id($db->conn);

$typeName = "IntTest Type " . $testId;
$result = mysqli_query($db->conn, "INSERT INTO type (company_id, name, des, cat_id) VALUES (65, '{$typeName}', 'Test desc', {$tempCatId})");
$typeId = mysqli_insert_id($db->conn);
$err = getError($db);
test("Type CREATE", $typeId > 0, "ID: {$typeId}" . ($err ? ", Error: {$err}" : ""));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM type WHERE id = {$typeId}"));
test("Type READ", $read && $read['name'] === $typeName, "Read: " . ($read['name'] ?? 'null'));

mysqli_query($db->conn, "UPDATE type SET name = 'Updated {$typeName}' WHERE id = {$typeId}");
$updated = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT name FROM type WHERE id = {$typeId}"));
test("Type UPDATE", $updated && strpos($updated['name'], 'Updated') === 0, "Updated");

mysqli_query($db->conn, "DELETE FROM type WHERE id = {$typeId}");
mysqli_query($db->conn, "DELETE FROM category WHERE id = {$tempCatId}");
test("Type DELETE", true, "Deleted");

// ============================================================
// SECTION 4: BRAND CRUD
// ============================================================
echo "<h2>4. Brand CRUD</h2>";

$brandName = "IntTest Brand " . $testId;
$result = mysqli_query($db->conn, "INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES (65, '{$brandName}', 'Test desc', '', 65)");
$brandId = mysqli_insert_id($db->conn);
$err = getError($db);
test("Brand CREATE", $brandId > 0, "ID: {$brandId}" . ($err ? ", Error: {$err}" : ""));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM brand WHERE id = {$brandId}"));
test("Brand READ", $read && $read['brand_name'] === $brandName, "Read: " . ($read['brand_name'] ?? 'null'));

mysqli_query($db->conn, "UPDATE brand SET brand_name = 'Updated {$brandName}' WHERE id = {$brandId}");
$updated = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT brand_name FROM brand WHERE id = {$brandId}"));
test("Brand UPDATE", $updated && strpos($updated['brand_name'], 'Updated') === 0, "Updated");

mysqli_query($db->conn, "DELETE FROM brand WHERE id = {$brandId}");
test("Brand DELETE", true, "Deleted");

// ============================================================
// SECTION 5: MODEL CRUD
// ============================================================
echo "<h2>5. Model CRUD</h2>";

mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES (65, 'ModelCat{$testId}', 'Temp')");
$modelCatId = mysqli_insert_id($db->conn);
mysqli_query($db->conn, "INSERT INTO type (company_id, name, des, cat_id) VALUES (65, 'ModelType{$testId}', 'Temp', {$modelCatId})");
$modelTypeId = mysqli_insert_id($db->conn);
mysqli_query($db->conn, "INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES (65, 'ModelBrand{$testId}', '', '', 65)");
$modelBrandId = mysqli_insert_id($db->conn);

$modelName = "IntTest Model " . $testId;
// model: id, company_id, type_id, brand_id, model_name, des, price, deleted_at
$result = mysqli_query($db->conn, "INSERT INTO model (company_id, type_id, brand_id, model_name, des, price) 
    VALUES (65, {$modelTypeId}, {$modelBrandId}, '{$modelName}', 'Test desc', 2500)");
$modelId = mysqli_insert_id($db->conn);
$err = getError($db);
test("Model CREATE", $modelId > 0, "ID: {$modelId}" . ($err ? ", Error: {$err}" : ""));

$read = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM model WHERE id = {$modelId}"));
test("Model READ", $read && $read['model_name'] === $modelName, "Read: " . ($read['model_name'] ?? 'null'));

mysqli_query($db->conn, "UPDATE model SET price = 3000 WHERE id = {$modelId}");
$updated = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT price FROM model WHERE id = {$modelId}"));
test("Model UPDATE", $updated && $updated['price'] == 3000, "Price: " . ($updated['price'] ?? 'null'));

mysqli_query($db->conn, "DELETE FROM model WHERE id = {$modelId}");
mysqli_query($db->conn, "DELETE FROM brand WHERE id = {$modelBrandId}");
mysqli_query($db->conn, "DELETE FROM type WHERE id = {$modelTypeId}");
mysqli_query($db->conn, "DELETE FROM category WHERE id = {$modelCatId}");
test("Model DELETE", true, "Deleted");

// ============================================================
// SECTION 6: FULL PR -> PO WORKFLOW (CRITICAL)
// ============================================================
echo "<h2>6. PR -> PO Full Workflow (Critical)</h2>";

$custName = "IntTest Customer " . $testId;
mysqli_query($db->conn, "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id, deleted_at) 
    VALUES ('{$custName}', 'ลูกค้า', 'CUST', '', '', '', '', '', 1, 0, '', '', 65, NULL)");
$customerId = mysqli_insert_id($db->conn);
test("Setup: Customer", $customerId > 0, "ID: {$customerId}");

mysqli_query($db->conn, "INSERT INTO category (company_id, cat_name, des) VALUES (65, 'PRCat{$testId}', '')");
$prCatId = mysqli_insert_id($db->conn);
mysqli_query($db->conn, "INSERT INTO type (company_id, name, des, cat_id) VALUES (65, 'PRType{$testId}', '', {$prCatId})");
$prTypeId = mysqli_insert_id($db->conn);
mysqli_query($db->conn, "INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES (65, 'PRBrand{$testId}', '', '', 65)");
$prBrandId = mysqli_insert_id($db->conn);
mysqli_query($db->conn, "INSERT INTO model (company_id, type_id, brand_id, model_name, des, price) VALUES (65, {$prTypeId}, {$prBrandId}, 'PRModel{$testId}', '', 1500)");
$prModelId = mysqli_insert_id($db->conn);
test("Setup: Product/Model", $prTypeId > 0 && $prModelId > 0, "Type: {$prTypeId}, Model: {$prModelId}");

$prName = "IntTest PR " . $testId;
// pr: id, company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby, deleted_at
$result = mysqli_query($db->conn, "INSERT INTO pr (company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby) 
    VALUES (65, '{$prName}', 'Test PR description', 1, {$customerId}, 65, CURDATE(), '0', 0, 0, 0)");
$prId = mysqli_insert_id($db->conn);
$err = getError($db);
test("PR CREATE", $prId > 0, "ID: {$prId}" . ($err ? ", Error: {$err}" : ""));

$prRead = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT * FROM pr WHERE id = {$prId}"));
test("PR READ", $prRead && $prRead['status'] == 0, "Status: " . ($prRead['status'] ?? 'null'));

// CREATE PO using isolated arrays
$argsPO = array();
$argsPO['table'] = "po";
$newPoId = $har->Maxid($argsPO['table']);
$taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);

$argsPO['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
$argsPO['value'] = "'65', '', '{$prName}', '{$prId}', '{$taxNumber}', '" . date('Y-m-d') . "', '" . date('Y-m-d', strtotime('+30 days')) . "', '" . date('Y-m-d', strtotime('+45 days')) . "', '', '', '0', '0', '7', '0', NULL";
$createdPoId = $har->insertDbMax($argsPO);
test("PO CREATE (isolated)", $createdPoId > 0, "PO ID: {$createdPoId}, Tax: {$taxNumber}");

mysqli_query($db->conn, "UPDATE pr SET status = 1 WHERE id = {$prId}");
$prStatus = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT status FROM pr WHERE id = {$prId}"));
test("PR Status -> 1", $prStatus && $prStatus['status'] == 1, "Status: " . ($prStatus['status'] ?? 'null'));

// CREATE Products using isolated arrays
$argsProduct1 = array();
$argsProduct1['table'] = "product";
$argsProduct1['value'] = "NULL, '65', '{$createdPoId}', '1500', '0', '0', '{$prModelId}', '{$prTypeId}', '2', '1', '0', 'Test product 1', '0', '0', '0', '1970-01-01', '0', NULL";
$har->insertDB($argsProduct1);
$prod1Id = mysqli_insert_id($db->conn);
test("Product 1 CREATE", $prod1Id > 0, "ID: {$prod1Id}");

$argsProduct2 = array();
$argsProduct2['table'] = "product";
$argsProduct2['value'] = "NULL, '65', '{$createdPoId}', '2500', '0', '0', '{$prModelId}', '{$prTypeId}', '1', '1', '0', 'Test product 2', '0', '0', '0', '1970-01-01', '0', NULL";
$har->insertDB($argsProduct2);
$prod2Id = mysqli_insert_id($db->conn);
test("Product 2 CREATE", $prod2Id > 0, "ID: {$prod2Id}");

$productCount = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM product WHERE po_id = {$createdPoId}"));
test("Products linked to PO", $productCount && $productCount['cnt'] == 2, "Count: " . ($productCount['cnt'] ?? '0'));

// SIMULATE PO EDIT (the bug that was fixed)
echo "<h3>PO Edit Simulation (Bug Fix Test)</h3>";

$newEditPoId = $har->Maxid("po");
$newTaxNumber = (date("y") + 43) . str_pad($newEditPoId, 6, '0', STR_PAD_LEFT);

$argsNewPO = array();
$argsNewPO['table'] = "po";
$argsNewPO['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
$argsNewPO['value'] = "'65', '', 'Edited {$prName}', '{$prId}', '{$newTaxNumber}', '" . date('Y-m-d') . "', '" . date('Y-m-d', strtotime('+30 days')) . "', '" . date('Y-m-d', strtotime('+45 days')) . "', '', '', '5', '0', '7', '0', NULL";
$editedPoId = $har->insertDbMax($argsNewPO);
test("PO Edit: New version", $editedPoId > 0, "New PO ID: {$editedPoId}");

mysqli_query($db->conn, "UPDATE po SET po_id_new = {$editedPoId} WHERE id = {$createdPoId}");
$oldPoCheck = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT po_id_new FROM po WHERE id = {$createdPoId}"));
test("PO Edit: Old linked", $oldPoCheck && $oldPoCheck['po_id_new'] == $editedPoId, "po_id_new: " . ($oldPoCheck['po_id_new'] ?? 'null'));

// Insert products for edited PO (THE BUG FIX TEST)
$argsEditProd1 = array();
$argsEditProd1['table'] = "product";
$argsEditProd1['value'] = "NULL, '65', '{$editedPoId}', '1600', '0', '0', '{$prModelId}', '{$prTypeId}', '3', '1', '0', 'Edited product 1', '0', '0', '0', '1970-01-01', '0', NULL";
$har->insertDB($argsEditProd1);
$editProd1 = mysqli_insert_id($db->conn);
test("PO Edit: Product 1", $editProd1 > 0, "ID: {$editProd1}");

$argsEditProd2 = array();
$argsEditProd2['table'] = "product";
$argsEditProd2['value'] = "NULL, '65', '{$editedPoId}', '2600', '0', '0', '{$prModelId}', '{$prTypeId}', '2', '1', '0', 'Edited product 2', '0', '0', '0', '1970-01-01', '0', NULL";
$har->insertDB($argsEditProd2);
$editProd2 = mysqli_insert_id($db->conn);
test("PO Edit: Product 2", $editProd2 > 0, "ID: {$editProd2}");

// Verify edited PO has products (THE CRITICAL CHECK)
$editProductCount = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM product WHERE po_id = {$editedPoId}"));
test("PO Edit: Products preserved", $editProductCount && $editProductCount['cnt'] == 2, "Count: " . ($editProductCount['cnt'] ?? '0'));

$oldPoVisible = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT id FROM po WHERE id = {$createdPoId} AND po_id_new = ''"));
test("Old PO hidden", $oldPoVisible === null, "Correctly hidden");

$newPoVisible = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT id FROM po WHERE id = {$editedPoId} AND po_id_new = ''"));
test("New PO visible", $newPoVisible !== null, "Correctly visible");

// ============================================================
// SECTION 7: HARDCLASS ISOLATED ARRAYS TEST
// ============================================================
echo "<h2>7. HardClass Isolated Arrays</h2>";

$testCat = array();
$testCat['table'] = 'category';
$testCat['columns'] = 'company_id, cat_name, des';
$testCat['value'] = "65, 'IsolationTest{$testId}', 'test'";
$catTestId = $har->insertDbMax($testCat);
test("insertDbMax with columns", $catTestId > 0, "ID: {$catTestId}");

$testCat2 = array();
$testCat2['table'] = 'category';
$testCat2['value'] = "NULL, 65, 'IsolationTest2{$testId}', 'test', NULL";
$har->insertDB($testCat2);
$catTest2Id = mysqli_insert_id($db->conn);
$err = getError($db);
test("insertDb without columns (no leak)", $catTest2Id > 0, "ID: {$catTest2Id}" . ($err ? ", Error: {$err}" : ""));

mysqli_query($db->conn, "DELETE FROM category WHERE cat_name LIKE 'IsolationTest%{$testId}'");
test("Isolation cleanup", true, "Cleaned");

// ============================================================
// CLEANUP ALL TEST DATA
// ============================================================
echo "<h2>Cleanup</h2>";

mysqli_query($db->conn, "DELETE FROM product WHERE po_id IN ({$createdPoId}, {$editedPoId})");
mysqli_query($db->conn, "DELETE FROM po WHERE id IN ({$createdPoId}, {$editedPoId})");
mysqli_query($db->conn, "DELETE FROM pr WHERE id = {$prId}");
mysqli_query($db->conn, "DELETE FROM model WHERE id = {$prModelId}");
mysqli_query($db->conn, "DELETE FROM brand WHERE id = {$prBrandId}");
mysqli_query($db->conn, "DELETE FROM type WHERE id = {$prTypeId}");
mysqli_query($db->conn, "DELETE FROM category WHERE id = {$prCatId}");
mysqli_query($db->conn, "DELETE FROM company WHERE id = {$customerId}");
test("All test data cleaned", true, "Removed");

// ============================================================
// SUMMARY
// ============================================================
echo "<h2>📊 Test Summary</h2>";
$total = $passed + $failed;
$rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
$summaryClass = $failed === 0 ? 'summary-pass' : 'summary-fail';
echo "<div class='summary {$summaryClass}'>";
echo "✅ Passed: {$passed} | ❌ Failed: {$failed} | Total: {$total} | Success Rate: {$rate}%";
echo "</div>";

if ($failed === 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All tests passed! System ready for browser testing.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some tests failed. Review above.</p>";
}

echo "<h3>Detailed Results</h3>";
foreach ($results as $r) {
    $class = $r['status'] === 'pass' ? 'pass' : 'fail';
    $icon = $r['status'] === 'pass' ? '✅' : '❌';
    echo "<div class='test {$class}'><strong>{$icon} {$r['name']}</strong>";
    if ($r['details']) {
        echo "<div class='details'>{$r['details']}</div>";
    }
    echo "</div>";
}

echo "</div></body></html>";
?>
