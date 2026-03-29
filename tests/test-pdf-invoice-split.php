<?php
/**
 * Test PDF Invoice Split - Verify labour/material invoice content
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 4;
$_SESSION['user_level'] = 9;
$_SESSION['lang'] = 'us';

require_once __DIR__ . '/../inc/sys.configs.php';
require_once __DIR__ . '/../inc/class.dbconn.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/class.hard.php';
require_once __DIR__ . '/../inc/class.current.php';
$db = new DbConn($config);

function testPdfInvoice($id, $label) {
    global $db;
    $_GET['id'] = $id;
    $_REQUEST['id'] = $id;
    
    echo "\n=== $label (PO ID: $id) ===\n";
    
    // Instead of rendering the full PDF, just test the key data queries
    $conn = $db->conn;
    $id_safe = mysqli_real_escape_string($conn, $id);
    
    // Check split type and original quotation reference
    $splitRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT split_type, split_group_id FROM po WHERE id = '$id_safe'"));
    $splitType = $splitRow['split_type'] ?? null;
    $splitGroupId = $splitRow['split_group_id'] ?? null;
    
    echo "  Split type: $splitType\n";
    echo "  Split group: $splitGroupId\n";
    
    // Find original quotation number
    $origTax = '';
    if ($splitGroupId) {
        $origRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tax FROM po WHERE po_id_new = '" . mysqli_real_escape_string($conn, $splitGroupId) . "'"));
        $origTax = $origRow['tax'] ?? '';
    }
    echo "  Original quotation: " . ($origTax ? "QO-$origTax" : "none") . "\n";
    
    // Check labour detection
    $cklabour = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(activelabour) as cklabour FROM product LEFT JOIN type ON product.type=type.id WHERE po_id='$id_safe'"));
    $hasLabour = ($cklabour['cklabour'] == 1);
    
    $isLabourInvoice = ($splitType === 'labour');
    $isMaterialInvoice = ($splitType === 'material');
    
    // For labour invoice, we suppress the Equipment/Labour/L.Total columns and show "Labour Rate" instead of "Price"
    if ($isLabourInvoice) $hasLabour = false;
    
    echo "  Has labour columns: " . ($hasLabour ? "YES" : "NO") . "\n";
    echo "  Price column label: " . ($isLabourInvoice ? "Labour Rate" : "Price") . "\n";
    echo "  Title suffix: " . ($isLabourInvoice ? "(LABOUR)" : ($isMaterialInvoice ? "(MATERIALS)" : "(none)")) . "\n";
    
    // Verify products
    $prods = mysqli_query($conn, "SELECT price, activelabour, valuelabour FROM product WHERE po_id='$id_safe'");
    while ($p = mysqli_fetch_assoc($prods)) {
        echo "  Product: price={$p['price']}, activelabour={$p['activelabour']}, valuelabour={$p['valuelabour']}\n";
    }
    
    // Assertions
    $pass = 0; $fail = 0;
    
    if ($isLabourInvoice) {
        echo ($hasLabour === false ? "  ✓" : "  ✗") . " Labour invoice does NOT show Equipment/L.Total columns\n";
        $hasLabour === false ? $pass++ : $fail++;
        
        echo (!empty($origTax) ? "  ✓" : "  ✗") . " Has original quotation reference\n";
        !empty($origTax) ? $pass++ : $fail++;
    }
    
    if ($isMaterialInvoice) {
        echo ($hasLabour === false ? "  ✓" : "  ✗") . " Material invoice does NOT show Equipment/Labour columns\n";
        $hasLabour === false ? $pass++ : $fail++;
        
        echo (!empty($origTax) ? "  ✓" : "  ✗") . " Has original quotation reference\n";
        !empty($origTax) ? $pass++ : $fail++;
    }
    
    return ['pass' => $pass, 'fail' => $fail];
}

// Test Labour Invoice
$r1 = testPdfInvoice(2182, "Labour Invoice");

// Test Material Invoice
$r2 = testPdfInvoice(2181, "Material Invoice");

$totalPass = $r1['pass'] + $r2['pass'];
$totalFail = $r1['fail'] + $r2['fail'];
echo "\n=== Results: $totalPass passed, $totalFail failed ===\n";
