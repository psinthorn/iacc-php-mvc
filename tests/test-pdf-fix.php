<?php
/**
 * Test: PDF view files work correctly through PdfController (global $db fix)
 * This simulates the index.php → PdfController → View flow
 */

// Simulate index.php initialization
session_start();
$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 1;
$_SESSION['user_level'] = 5;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['lang'] = 'en';
$_SESSION['com_name'] = 'Test';

$_REQUEST['id'] = 1910;
$_GET['id'] = 1910;

chdir('/var/www/html');

require_once 'inc/sys.configs.php';
require_once 'inc/class.dbconn.php';
require_once 'inc/class.current.php';
require_once 'inc/security.php';

// Create global $db (as index.php does)
$db = new DbConn($config);

echo "=== PDF Fix Test ===\n\n";

// Test 1: Verify global $db is accessible inside a function scope (simulates PdfController method)
function testGlobalDb() {
    global $db;
    if ($db && $db->conn) {
        echo "✅ Test 1: global \$db accessible in function scope\n";
        return true;
    } else {
        echo "❌ Test 1: global \$db NOT accessible\n";
        return false;
    }
}

// Test 2: Verify quotation query works with global $db
function testQuotationQuery() {
    global $db;
    $id = 1910;
    $id_safe = mysqli_real_escape_string($db->conn, $id);
    $com_id = mysqli_real_escape_string($db->conn, $_SESSION['com_id']);
    
    $sql = "SELECT po.name as name, pr.ven_id, pr.cus_id, pr.status 
            FROM pr JOIN po ON pr.id = po.ref 
            WHERE po.id = '{$id_safe}' 
            AND pr.status > '0' 
            AND (pr.cus_id = '{$com_id}' OR pr.ven_id = '{$com_id}') 
            AND po.po_id_new = ''";
    
    $query = mysqli_query($db->conn, $sql);
    if ($query && mysqli_num_rows($query) == 1) {
        $data = mysqli_fetch_array($query);
        echo "✅ Test 2: Quotation query works - PO '{$data['name']}' (ven={$data['ven_id']}, cus={$data['cus_id']})\n";
        return true;
    } else {
        echo "❌ Test 2: Quotation query failed\n";
        return false;
    }
}

// Test 3: Include quotation.php and check it generates PDF
function testQuotationPdf() {
    ob_start();
    try {
        include __DIR__ . '/../app/Views/pdf/quotation.php';
        $output = ob_get_clean();
        $len = strlen($output);
        $hasPdf = (strpos($output, '%PDF') !== false);
        
        if ($hasPdf && $len > 1000) {
            echo "✅ Test 3: PDF generated successfully ({$len} bytes)\n";
            return true;
        } else {
            echo "❌ Test 3: PDF not generated (output={$len} bytes, hasPDF=" . ($hasPdf ? 'yes' : 'no') . ")\n";
            if ($len < 500) echo "   Output: " . substr($output, 0, 200) . "\n";
            return false;
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Test 3: Exception - " . $e->getMessage() . "\n";
        return false;
    }
}

// Run tests
$pass = 0;
$total = 3;

if (testGlobalDb()) $pass++;
if (testQuotationQuery()) $pass++;
if (testQuotationPdf()) $pass++;

echo "\n=== Results: {$pass}/{$total} passed ===\n";
exit($pass === $total ? 0 : 1);
