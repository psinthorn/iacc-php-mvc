<?php session_start();

// Debug: Log all incoming requests to core-function.php
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$logFile = $logDir . '/app.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " CORE-FUNCTION: page=" . ($_REQUEST['page'] ?? 'NOT SET') . ", method=" . ($_REQUEST['method'] ?? 'NOT SET') . "\n", FILE_APPEND);

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");

// CSRF protection for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " CORE-FUNCTION: CSRF FAILED\n", FILE_APPEND);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
}

// Initialize company filter for multi-tenant queries
$companyFilter = CompanyFilter::getInstance();

$users=new DbConn($config);
$db = $users; // Alias for compatibility with legacy code
// Security already checked in index.php

$har=new HardClass();
$har->setConnection($db->conn); // Explicitly set connection
$har->keeplog($_REQUEST);
switch($_REQUEST['page']){	
	
// case "company" — MIGRATED to App\Controllers\CompanyController (Phase 2E)
// case "type" — MIGRATED to App\Controllers\TypeController (Phase 2C)	
// case "category" — MIGRATED to App\Controllers\CategoryController (Phase 2B)

// ====================================================================
// ALL CASES BELOW MIGRATED TO MVC CONTROLLERS (Phase 3A-3E)
// Left commented out for reference. Remove after testing.
// ====================================================================

/*
// case "compl_list" — MIGRATED to App\Controllers\InvoiceController@store (Phase 3A)
// case "compl_view" — MIGRATED to App\Controllers\InvoiceController@store (Phase 3A)
// case "compl_list2" — MIGRATED to App\Controllers\InvoiceController@store (Phase 3A)
// case "payment" — MIGRATED to App\Controllers\PaymentController@store (Phase 3C)
// case "pr_list" — MIGRATED to App\Controllers\PurchaseRequestController@store (Phase 3B)
// case "po_list" — MIGRATED to App\Controllers\PurchaseOrderController@store (Phase 3D)
// case "receipt_list" — MIGRATED to App\Controllers\ReceiptController@store (Phase 3E)
// case "voucher_list" — MIGRATED to App\Controllers\VoucherController@store (Phase 3E)
// case "deliv_list" — MIGRATED to App\Controllers\DeliveryController@store (Phase 3E)
// case "billing" — MIGRATED to App\Controllers\BillingController@store (Phase 3E)
*/

}
exit("<script>window.location = 'index.php?page=".$_REQUEST['page']."'</script>");

?>