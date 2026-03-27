<?php
/**
 * Phase 2C MVC Integration Tests
 * Tests Brand, Type, Model, PaymentMethod controllers and models
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/sys.configs.php';
require_once __DIR__ . '/../inc/class.dbconn.php';
require_once __DIR__ . '/../inc/security.php';

// Initialize DB
$db = new DbConn($config);

// Set session context for multi-tenant
session_start();
$_SESSION['com_id'] = '1';
$_SESSION['user_level'] = 2;

$passed = 0;
$failed = 0;
$total = 0;
$results = [];

function test($name, $condition, $details = '') {
    global $passed, $failed, $total, $results;
    $total++;
    if ($condition) {
        $passed++;
        $results[] = ['name' => $name, 'pass' => true, 'details' => $details];
    } else {
        $failed++;
        $results[] = ['name' => $name, 'pass' => false, 'details' => $details];
    }
}

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🧪 Phase 2C MVC Tests</h1>";

// ========== 1. Brand Model Tests ==========
echo "<h2>1. Brand Model</h2>";
try {
    $brand = new App\Models\Brand();
    
    // Test getPaginated
    $result = $brand->getPaginated('', 1, 10);
    test('Brand::getPaginated()', is_array($result) && isset($result['items'], $result['total']), 
         "total=" . ($result['total'] ?? '?') . ", items_count=" . count($result['items'] ?? []));
    
    // Test getVendors
    $vendors = $brand->getVendors(intval($_SESSION['com_id']));
    test('Brand::getVendors()', is_array($vendors), "count=" . count($vendors));
    
    // Test getOwnCompany
    $own = $brand->getOwnCompany(intval($_SESSION['com_id']));
    test('Brand::getOwnCompany()', $own === null || is_array($own), "result=" . ($own ? $own['name_en'] : 'null'));
    
} catch (Throwable $e) {
    test('Brand Model init', false, $e->getMessage());
}

// ========== 2. Type Model Tests ==========
echo "<h2>2. Type Model</h2>";
try {
    $type = new App\Models\Type();
    
    // Test getPaginated
    $result = $type->getPaginated('', 1, 10);
    test('Type::getPaginated()', is_array($result) && isset($result['items'], $result['total']),
         "total=" . ($result['total'] ?? '?') . ", items_count=" . count($result['items'] ?? []));
    
    // Test getCategories
    $cats = $type->getCategories();
    test('Type::getCategories()', is_array($cats), "count=" . count($cats));
    
    // Test getAllBrands
    $brands = $type->getAllBrands();
    test('Type::getAllBrands()', is_array($brands), "count=" . count($brands));
    
} catch (Throwable $e) {
    test('Type Model init', false, $e->getMessage());
}

// ========== 3. ProductModel Tests ==========
echo "<h2>3. ProductModel (Model)</h2>";
try {
    $model = new App\Models\ProductModel();
    
    // Test getPaginated
    $result = $model->getPaginated('', 1, 10);
    test('ProductModel::getPaginated()', is_array($result) && isset($result['items'], $result['total']),
         "total=" . ($result['total'] ?? '?') . ", items_count=" . count($result['items'] ?? []));
    
    // Test getTypes
    $types = $model->getTypes();
    test('ProductModel::getTypes()', is_array($types), "count=" . count($types));
    
    // Test getBrands
    $brands = $model->getBrands();
    test('ProductModel::getBrands()', is_array($brands), "count=" . count($brands));
    
} catch (Throwable $e) {
    test('ProductModel init', false, $e->getMessage());
}

// ========== 4. PaymentMethod Model Tests ==========
echo "<h2>4. PaymentMethod Model</h2>";
try {
    $pm = new App\Models\PaymentMethod();
    
    // Test getFiltered
    $result = $pm->getFiltered('', '', '');
    test('PaymentMethod::getFiltered()', is_array($result), "count=" . count($result));
    
    // Test getStats
    $stats = $pm->getStats();
    test('PaymentMethod::getStats()', is_array($stats) && isset($stats['total']),
         "total=" . $stats['total'] . ", active=" . $stats['active']);
    
    // Test getNextSortOrder
    $next = $pm->getNextSortOrder();
    test('PaymentMethod::getNextSortOrder()', is_numeric($next), "next=" . $next);
    
} catch (Throwable $e) {
    test('PaymentMethod Model init', false, $e->getMessage());
}

// ========== 5. CRUD Integration ==========
echo "<h2>5. CRUD Integration (Brand)</h2>";
try {
    $brand = new App\Models\Brand();
    
    // Create
    $testName = 'MVC Test Brand ' . time();
    $id = $brand->create([
        'brand_name' => $testName,
        'des' => 'Test description',
        'logo' => '',
        'ven_id' => 0
    ]);
    test('Brand CREATE via Model', $id > 0, "id=$id");
    
    // Read
    $data = $brand->find($id);
    test('Brand READ via Model', $data && $data['brand_name'] === $testName, "name=" . ($data['brand_name'] ?? 'null'));
    
    // Update
    $brand->update($id, ['brand_name' => 'Updated ' . $testName]);
    $data = $brand->find($id);
    test('Brand UPDATE via Model', $data && strpos($data['brand_name'], 'Updated') === 0, "name=" . ($data['brand_name'] ?? 'null'));
    
    // Delete
    $brand->delete($id);
    $data = $brand->find($id);
    test('Brand DELETE via Model', $data === null || $data === false, "after delete");
    
} catch (Throwable $e) {
    test('Brand CRUD', false, $e->getMessage());
}

// ========== 6. CRUD Integration (PaymentMethod) ==========
echo "<h2>6. CRUD Integration (PaymentMethod)</h2>";
try {
    $pm = new App\Models\PaymentMethod();
    
    // Create
    $testCode = 'test_mvc_' . time();
    $id = $pm->create([
        'code' => $testCode,
        'name' => 'MVC Test Payment',
        'name_th' => 'ทดสอบ',
        'icon' => 'fa-money',
        'description' => 'test',
        'is_gateway' => 0,
        'is_active' => 1,
        'sort_order' => 999
    ]);
    test('PaymentMethod CREATE via Model', $id > 0, "id=$id");
    
    // Read
    $data = $pm->find($id);
    test('PaymentMethod READ via Model', $data && $data['code'] === $testCode, "code=" . ($data['code'] ?? 'null'));
    
    // Toggle
    $pm->toggleActive($id);
    $data = $pm->find($id);
    test('PaymentMethod TOGGLE via Model', $data && $data['is_active'] == 0, "is_active=" . ($data['is_active'] ?? 'null'));
    
    // Delete
    $pm->delete($id);
    $data = $pm->find($id);
    test('PaymentMethod DELETE via Model', $data === null || $data === false, "after delete");
    
} catch (Throwable $e) {
    test('PaymentMethod CRUD', false, $e->getMessage());
}

// ========== 7. Route Config Test ==========
echo "<h2>7. Route Configuration</h2>";
$routes = require __DIR__ . '/../app/Config/routes.php';
$expectedMvc = ['brand', 'brand_form', 'brand_store', 'brand_delete', 'type', 'type_store', 'type_delete',
    'mo_list', 'mo_list_store', 'mo_list_delete', 'mo_list_brands',
    'payment_method_list', 'payment_method', 'payment_method_store', 'payment_method_delete', 'payment_method_toggle'];

foreach ($expectedMvc as $route) {
    test("Route '$route' is MVC", isset($routes[$route]) && is_array($routes[$route]),
         isset($routes[$route]) ? (is_array($routes[$route]) ? $routes[$route][0] . '::' . $routes[$route][1] : 'legacy') : 'missing');
}

// ========== Summary ==========
echo "<h2>📊 Summary</h2>";
$color = $failed === 0 ? '#27ae60' : '#e74c3c';
echo "<div style='background: $color; color: white; padding: 15px; border-radius: 8px; font-size: 18px;'>";
echo "✅ Passed: $passed | ❌ Failed: $failed | Total: $total | Success Rate: " . round($passed / $total * 100) . "%";
echo "</div>";

echo "<h3>Details</h3>";
foreach ($results as $r) {
    $icon = $r['pass'] ? '✅' : '❌';
    echo "<div style='padding: 5px; margin: 2px 0; background: " . ($r['pass'] ? '#eafaf1' : '#fdecea') . ";'>";
    echo "<strong>$icon {$r['name']}</strong>";
    if ($r['details']) echo " — <em>{$r['details']}</em>";
    echo "</div>";
}
