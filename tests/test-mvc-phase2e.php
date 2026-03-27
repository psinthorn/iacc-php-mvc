<?php
/**
 * Phase 2E MVC Tests - Company Entity
 * 
 * Tests the Company model, controller, routes, and views
 * covering: company CRUD, addresses, credits, soft delete, multi-tenant
 */
session_start();
$_SESSION['com_id'] = '0'; // Admin mode — see all companies
require_once __DIR__ . '/../inc/sys.configs.php';
require_once __DIR__ . '/../inc/class.dbconn.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../vendor/autoload.php';

$db = new DbConn($config);
$passed = 0;
$failed = 0;
$results = [];
$uid = time();

function pass($msg, $detail = '') {
    global $passed, $results;
    $passed++;
    $results[] = ['pass', $msg, $detail];
}
function fail($msg, $detail = '') {
    global $failed, $results;
    $failed++;
    $results[] = ['fail', $msg, $detail];
}
?>
<!DOCTYPE html>
<html><head><title>Phase 2E MVC Tests - Company</title>
<style>
body{font-family:monospace;margin:20px;background:#1a1a2e;color:#e0e0e0;}
.pass{color:#00ff88;} .fail{color:#ff4444;} .info{color:#4ecdc4;}
h1{color:#fff;} h2{color:#a569bd;}
.summary{padding:15px;border-radius:8px;font-size:1.2em;font-weight:bold;margin:20px 0;}
.summary-pass{background:#1a3d2a;border:2px solid #00ff88;color:#00ff88;}
.summary-fail{background:#3d1a1a;border:2px solid #ff4444;color:#ff4444;}
</style></head><body>
<h1>🏢 Phase 2E MVC Tests - Company</h1>

<?php
// ========== 1. Autoloading ==========
echo "<h2>1. Autoloading</h2>";

if (class_exists('App\Models\Company')) { pass('Company model autoloads'); }
else { fail('Company model autoloads'); }

if (class_exists('App\Controllers\CompanyController')) { pass('CompanyController autoloads'); }
else { fail('CompanyController autoloads'); }

// ========== 2. Route Configuration ==========
echo "<h2>2. Route Configuration</h2>";
$routes = require __DIR__ . '/../app/Config/routes.php';

$expectedRoutes = [
    'company'         => ['CompanyController', 'index'],
    'company_form'    => ['CompanyController', 'form'],
    'company_store'   => ['CompanyController', 'store'],
    'company_delete'  => ['CompanyController', 'delete'],
    'company_credits' => ['CompanyController', 'credits'],
];
foreach ($expectedRoutes as $key => $expected) {
    if (isset($routes[$key]) && is_array($routes[$key]) && $routes[$key][0] === $expected[0] && $routes[$key][1] === $expected[1]) {
        pass("Route '$key' is MVC", "{$expected[0]}::{$expected[1]}");
    } else {
        fail("Route '$key' is MVC", "Expected: " . json_encode($expected) . ", Got: " . json_encode($routes[$key] ?? 'missing'));
    }
}

// ========== 3. View Files ==========
echo "<h2>3. View Files</h2>";
$views = ['views/company/list.php', 'views/company/form.php', 'views/company/credits.php'];
foreach ($views as $v) {
    $full = __DIR__ . '/../' . $v;
    if (file_exists($full)) { pass("$v exists"); }
    else { fail("$v exists"); }
}

// ========== 4. Company Model CRUD ==========
echo "<h2>4. Company Model — CRUD</h2>";
$company = new App\Models\Company();

// CREATE
$fields = [
    'name_en'  => "MVC Test Co $uid",
    'name_th'  => "บริษัททดสอบ $uid",
    'name_sh'  => "MVCTST$uid",
    'contact'  => 'John Doe',
    'email'    => "test{$uid}@example.com",
    'phone'    => '0812345678',
    'fax'      => '021234567',
    'tax'      => "TAX{$uid}",
    'term'     => 'Net 30',
    'customer' => '1',
    'vender'   => '0',
];
// Unset FILES to avoid logo upload during test
$_FILES = [];
$newId = $company->createCompany($fields);
if ($newId > 0) { pass("Company CREATE", "ID=$newId"); }
else { fail("Company CREATE", "Returned: $newId"); }

// READ (find)
$found = $company->find($newId);
if ($found && $found['name_en'] === "MVC Test Co $uid") {
    pass("Company FIND", "name_en={$found['name_en']}");
} else {
    fail("Company FIND", "not found or name mismatch");
}

// UPDATE
$fields['name_en'] = "Updated MVC Co $uid";
$updated = $company->updateCompany($newId, $fields);
$found2 = $company->find($newId);
if ($found2 && $found2['name_en'] === "Updated MVC Co $uid") {
    pass("Company UPDATE", "name_en={$found2['name_en']}");
} else {
    fail("Company UPDATE", "Expected 'Updated MVC Co $uid', Got: " . ($found2['name_en'] ?? 'null'));
}

// getPaginated (admin mode — sees all)
$result = $company->getPaginated("MVC Test Co $uid", '', 1, 10);
if ($result['total'] >= 0) {
    pass("Company getPaginated", "total={$result['total']}, items=" . count($result['items']));
} else {
    fail("Company getPaginated");
}

// Stats
if (isset($result['stats']['total']) && isset($result['stats']['vendors']) && isset($result['stats']['customers'])) {
    pass("Company stats", "total={$result['stats']['total']}, vendors={$result['stats']['vendors']}, customers={$result['stats']['customers']}");
} else {
    fail("Company stats");
}

// ========== 5. Address CRUD ==========
echo "<h2>5. Address Operations</h2>";

// Save address (insert)
$addrFields = [
    'adr_tax' => '123 Test Road', 'city_tax' => 'Bangkok', 'district_tax' => 'Sathorn',
    'province_tax' => 'Bangkok', 'zip_tax' => '10120',
    'adr_bil' => '', 'city_bil' => '', 'district_bil' => '', 'province_bil' => '', 'zip_bil' => '',
];
$addrOk = $company->saveAddress($newId, $addrFields, 0);
if ($addrOk) { pass("Address INSERT (new)", "company_id=$newId"); }
else { fail("Address INSERT"); }

// Find with address
$withAddr = $company->findWithAddress($newId);
if ($withAddr && ($withAddr['adr_tax'] ?? '') === '123 Test Road') {
    pass("findWithAddress", "adr_tax={$withAddr['adr_tax']}, addr_id={$withAddr['addr_id']}");
} else {
    fail("findWithAddress", "adr_tax=" . ($withAddr['adr_tax'] ?? 'null'));
}

// Update address
$addrId = $withAddr['addr_id'] ?? 0;
$addrFields['adr_tax'] = '456 Updated Road';
$updOk = $company->saveAddress($newId, $addrFields, $addrId);
if ($updOk) {
    $withAddr2 = $company->findWithAddress($newId);
    if ($withAddr2 && ($withAddr2['adr_tax'] ?? '') === '456 Updated Road') {
        pass("Address UPDATE", "adr_tax={$withAddr2['adr_tax']}");
    } else {
        fail("Address UPDATE", "adr_tax=" . ($withAddr2['adr_tax'] ?? 'null'));
    }
} else {
    fail("Address UPDATE");
}

// Billing auto-fill from tax
$addrFields2 = [
    'adr_tax' => '789 AutoFill Road', 'city_tax' => 'Nonthaburi', 'district_tax' => 'Pak Kret',
    'province_tax' => 'Nonthaburi', 'zip_tax' => '11120',
    'adr_bil' => '', 'city_bil' => '', 'district_bil' => '', 'province_bil' => '', 'zip_bil' => '',
];
$company->saveAddress($newId, $addrFields2, $addrId);
$withAddr3 = $company->findWithAddress($newId);
if ($withAddr3 && ($withAddr3['adr_bil'] ?? '') === '789 AutoFill Road') {
    pass("Billing auto-fill from tax", "adr_bil={$withAddr3['adr_bil']}");
} else {
    fail("Billing auto-fill from tax", "adr_bil=" . ($withAddr3['adr_bil'] ?? 'null'));
}

// Address versioning (A2)
$versionOk = $company->addAddressVersion($newId, [
    'adr_tax' => '999 Version2 Road', 'city_tax' => 'Chiang Mai', 'district_tax' => 'Muang',
    'province_tax' => 'Chiang Mai', 'zip_tax' => '50000',
    'adr_bil' => '', 'city_bil' => '', 'district_bil' => '', 'province_bil' => '', 'zip_bil' => '',
]);
if ($versionOk) {
    $withAddr4 = $company->findWithAddress($newId);
    if ($withAddr4 && ($withAddr4['adr_tax'] ?? '') === '999 Version2 Road') {
        pass("Address version (A2)", "adr_tax={$withAddr4['adr_tax']}");
    } else {
        fail("Address version (A2)", "adr_tax=" . ($withAddr4['adr_tax'] ?? 'null'));
    }
} else {
    fail("Address version (A2)");
}

// ========== 6. Credit Operations ==========
echo "<h2>6. Credit Operations</h2>";

// Create a second company as vendor
$fields2 = [
    'name_en' => "MVC Vendor $uid", 'name_th' => "ผู้ขาย $uid", 'name_sh' => "MVCV$uid",
    'contact' => 'Jane', 'email' => "vendor{$uid}@test.com", 'phone' => '099',
    'fax' => '', 'tax' => "VTAX$uid", 'term' => '', 'customer' => '0', 'vender' => '1',
];
$vendorId = $company->createCompany($fields2);
if ($vendorId > 0) { pass("Vendor company created", "ID=$vendorId"); }
else { fail("Vendor company created"); }

// Save credit (vendor gives credit to customer)
$creditOk = $company->saveCredit([
    'cus_id' => $newId, 'ven_id' => $vendorId,
    'limit_credit' => '50000', 'limit_day' => '30',
]);
if ($creditOk) { pass("Credit CREATE (A3)"); }
else { fail("Credit CREATE (A3)"); }

// Get credit records for customer
$credits = $company->getCreditRecords($newId);
if (!empty($credits['vendor_credits'])) {
    $vc = $credits['vendor_credits'][0];
    pass("getCreditRecords (as customer)", "vendor={$vc['name_sh']}, limit={$vc['limit_credit']}");
} else {
    fail("getCreditRecords (as customer)");
}

// Get credit records for vendor
$vendorCredits = $company->getCreditRecords($vendorId);
if (!empty($vendorCredits['customer_credits'])) {
    $cc = $vendorCredits['customer_credits'][0];
    pass("getCreditRecords (as vendor)", "customer={$cc['name_sh']}, limit={$cc['limit_credit']}");
} else {
    fail("getCreditRecords (as vendor)");
}

// Get available customers for credit
$available = $company->getAvailableCustomersForCredit($vendorId);
// newId is already assigned credit, so should NOT be in list
$foundInAvailable = false;
foreach ($available as $a) {
    if ($a['id'] == $newId) { $foundInAvailable = true; break; }
}
if (!$foundInAvailable) {
    pass("getAvailableCustomersForCredit", "Excludes already-assigned customer");
} else {
    fail("getAvailableCustomersForCredit", "Should exclude customer ID=$newId");
}

// Credit version (A4)
$creditRec = $credits['vendor_credits'][0] ?? null;
if ($creditRec) {
    $vOk = $company->saveCredit([
        'id' => $creditRec['id'], 'cus_id' => $newId, 'ven_id' => $vendorId,
        'limit_credit' => '75000', 'limit_day' => '45',
    ]);
    if ($vOk) {
        $credits2 = $company->getCreditRecords($newId);
        $newCredit = $credits2['vendor_credits'][0] ?? null;
        if ($newCredit && $newCredit['limit_credit'] == '75000') {
            pass("Credit version (A4)", "limit_credit={$newCredit['limit_credit']}, limit_day={$newCredit['limit_day']}");
        } else {
            fail("Credit version (A4)", "limit_credit=" . ($newCredit['limit_credit'] ?? 'null'));
        }
    } else {
        fail("Credit version (A4)");
    }
} else {
    fail("Credit version (A4) — no initial credit found");
}

// ========== 7. Soft Delete ==========
echo "<h2>7. Soft Delete</h2>";

$company->softDeleteCompany($newId);
$deleted = $company->find($newId);
if ($deleted && !empty($deleted['deleted_at'])) {
    pass("Soft delete company", "deleted_at={$deleted['deleted_at']}");
} else {
    fail("Soft delete company");
}

// Check address also soft deleted
$addrCheck = mysqli_query($db->conn, 
    "SELECT COUNT(*) as cnt FROM company_addr WHERE com_id='$newId' AND deleted_at IS NOT NULL"
);
$addrRow = mysqli_fetch_assoc($addrCheck);
if ($addrRow && $addrRow['cnt'] > 0) {
    pass("Addresses cascade soft delete", "count={$addrRow['cnt']}");
} else {
    fail("Addresses cascade soft delete");
}

// ========== 8. Controller Structure ==========
echo "<h2>8. Controller Structure</h2>";

$ref = new ReflectionClass('App\Controllers\CompanyController');
$methods = ['index', 'form', 'store', 'delete', 'credits'];
foreach ($methods as $m) {
    if ($ref->hasMethod($m)) { pass("CompanyController::$m() exists"); }
    else { fail("CompanyController::$m() exists"); }
}

if ($ref->isSubclassOf('App\Controllers\BaseController')) {
    pass("CompanyController extends BaseController");
} else {
    fail("CompanyController extends BaseController");
}

// ========== 9. Legacy Cleanup Verification ==========
echo "<h2>9. Legacy Cleanup</h2>";

// Check legacy files moved
$legacyFiles = ['company-list.php', 'company.php', 'company-addr.php', 'company-credit.php', 'credit-list.php'];
foreach ($legacyFiles as $lf) {
    if (!file_exists(__DIR__ . '/../' . $lf) && file_exists(__DIR__ . '/../legacy/' . $lf)) {
        pass("$lf archived to legacy/");
    } else {
        $inRoot = file_exists(__DIR__ . '/../' . $lf) ? 'still in root' : 'not in root';
        $inLegacy = file_exists(__DIR__ . '/../legacy/' . $lf) ? 'in legacy' : 'NOT in legacy';
        fail("$lf archived", "$inRoot, $inLegacy");
    }
}

// Check core-function.php no longer has company case (moved to legacy/)
$cfPath = __DIR__ . '/../legacy/core-function.php';
if (!file_exists($cfPath)) {
    pass("core-function.php archived to legacy/");
} else {
    $cfContent = file_get_contents($cfPath);
    if (preg_match('/^case\s+"company"\s*:/m', $cfContent) === 0) {
        pass("core-function.php company case removed");
    } else {
        fail("core-function.php still has company case");
    }
}

// ========== Cleanup Test Data ==========
echo "<h2>Cleanup</h2>";
mysqli_query($db->conn, "DELETE FROM company_credit WHERE cus_id='$newId' OR ven_id='$newId' OR cus_id='$vendorId' OR ven_id='$vendorId'");
mysqli_query($db->conn, "DELETE FROM company_addr WHERE com_id='$newId' OR com_id='$vendorId'");
mysqli_query($db->conn, "DELETE FROM company WHERE id='$newId'");
mysqli_query($db->conn, "DELETE FROM company WHERE id='$vendorId'");
echo "<div class='info'>🧹 Cleaned up test companies ID=$newId, $vendorId</div>";

// ========== Summary ==========
$total = $passed + $failed;
$rate = $total > 0 ? round(($passed / $total) * 100) : 0;
$cls = $failed === 0 ? 'summary-pass' : 'summary-fail';
echo "<h2>📊 Summary</h2>";
echo "<div class='summary $cls'>✅ Passed: $passed | ❌ Failed: $failed | Total: $total | Success Rate: $rate%</div>";

echo "<h3>Details</h3>";
foreach ($results as $r) {
    $icon = $r[0] === 'pass' ? '✅' : '❌';
    $cls = $r[0];
    $detail = $r[2] ? " — {$r[2]}" : '';
    echo "<div class='$cls'>$icon {$r[1]}$detail</div>";
}
?>
</body></html>
