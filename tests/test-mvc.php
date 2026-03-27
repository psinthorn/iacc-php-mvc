<?php
/**
 * MVC Integration Test
 * 
 * Tests the new MVC architecture (controllers, models, views) 
 * without requiring browser session.
 */
chdir(__DIR__ . "/.."); // Set working directory to project root

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_level'] = 2;
$_SESSION['com_id'] = 0;
$_SESSION['com_name'] = '';
$_SESSION['lang'] = 'en';

// Load core
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");
require_once("inc/security.php");

$db = new DbConn($config);

echo "<html><head><title>MVC Integration Tests</title>";
echo "<style>body{font-family:monospace;margin:20px;background:#1a1a2e;color:#e0e0e0;} .pass{color:#00ff88;} .fail{color:#ff4444;} .info{color:#4ecdc4;} h1{color:#fff;} h2{color:#a569bd;}</style>";
echo "</head><body>";
echo "<h1>🏗️ MVC Integration Tests</h1>";

$passed = 0;
$failed = 0;

function test($name, $condition, $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "<div class='pass'>✅ $name" . ($detail ? " — $detail" : "") . "</div>";
        $passed++;
    } else {
        echo "<div class='fail'>❌ $name" . ($detail ? " — $detail" : "") . "</div>";
        $failed++;
    }
}

// ===== 1. Autoloading Tests =====
echo "<h2>1. PSR-4 Autoloading</h2>";

test("BaseController autoloads", class_exists('App\Controllers\BaseController'));
test("CategoryController autoloads", class_exists('App\Controllers\CategoryController'));
test("BaseModel autoloads", class_exists('App\Models\BaseModel'));
test("Category model autoloads", class_exists('App\Models\Category'));

// ===== 2. Route Config Tests =====
echo "<h2>2. Route Configuration</h2>";

$routes = require __DIR__ . '/../app/Config/routes.php';
test("Routes file returns array", is_array($routes));
test("Category route is MVC", is_array($routes['category'] ?? null), 
     "route = ['" . ($routes['category'][0] ?? '') . "', '" . ($routes['category'][1] ?? '') . "']");
test("Dashboard route is legacy string", is_string($routes['dashboard'] ?? null), $routes['dashboard'] ?? '');
test("Category store route exists", isset($routes['category_store']));
test("Category delete route exists", isset($routes['category_delete']));

// ===== 3. Category Model CRUD Tests =====
echo "<h2>3. Category Model — CRUD</h2>";

// Create a test company first (FK constraint requires valid company_id)
$ts = time();
mysqli_query($db->conn, "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term) VALUES ('MVC Test Co $ts', 'MVC Test TH', 'MVCTC', 'test', 'test@test.com', '000', '', '', 1, 0, '', '')");
$testCompanyId = mysqli_insert_id($db->conn);
$_SESSION['com_id'] = $testCompanyId;

$cat = new App\Models\Category();

// Create
$testName = 'MVC Test Category ' . time();
$newId = $cat->create([
    'cat_name' => $testName,
    'des' => 'Created by MVC test',
]);
test("Category CREATE", $newId !== false, "ID: $newId");

// Read
$found = $cat->find($newId);
test("Category FIND", $found !== null && $found['cat_name'] === $testName, 
     "cat_name: " . ($found['cat_name'] ?? 'null'));

// Update
$updated = $cat->update($newId, ['cat_name' => 'Updated MVC ' . time(), 'des' => 'Updated by MVC test']);
test("Category UPDATE", $updated === true);

$afterUpdate = $cat->find($newId);
test("Category UPDATE verified", strpos($afterUpdate['cat_name'] ?? '', 'Updated MVC') !== false,
     "cat_name: " . ($afterUpdate['cat_name'] ?? 'null'));

// Paginate
$result = $cat->getPaginated('', 1, 10);
test("Category getPaginated", is_array($result) && isset($result['items']) && isset($result['pagination']),
     "total: " . ($result['total'] ?? 0) . ", items: " . count($result['items'] ?? []));

// Count
$count = $cat->count();
test("Category count", $count > 0, "count: $count");

// Delete
$deleted = $cat->delete($newId);
test("Category DELETE", $deleted === true);

$afterDelete = $cat->find($newId);
test("Category DELETE verified", $afterDelete === null, "find after delete returns null");

// ===== 4. Controller Instantiation =====
echo "<h2>4. Controller Tests</h2>";

$controller = new App\Controllers\CategoryController();
test("CategoryController instantiates", $controller instanceof App\Controllers\CategoryController);
test("CategoryController extends BaseController", $controller instanceof App\Controllers\BaseController);

// ===== 5. View Files Exist =====
echo "<h2>5. View Files</h2>";

test("views/category/list.php exists", file_exists(__DIR__ . '/../views/category/list.php'));
test("views/category/form.php exists", file_exists(__DIR__ . '/../views/category/form.php'));

// ===== Summary =====
echo "<hr>";

// Cleanup test company
mysqli_query($db->conn, "DELETE FROM company WHERE id='$testCompanyId'");

$total = $passed + $failed;
$color = $failed === 0 ? 'pass' : 'fail';
echo "<h2 class='$color'>Results: ✅ $passed passed | ❌ $failed failed | Total: $total</h2>";
echo "</body></html>";
