<?php
error_reporting(E_ALL & ~E_NOTICE);
// error_reporting(E_ALL);

// Set charset header FIRST before anything else
header('Content-Type: text/html; charset=utf-8');

session_start();

// Start output buffering to allow header redirects in included pages
ob_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db = new DbConn($config);

// Set audit context for all database operations
require_once("core-function.php");
set_audit_context();
// Check security - redirect to login if not authenticated
if (!isset($_SESSION['usr_id']) || $_SESSION['usr_id'] === "") {
    header("Location: login.php");
    exit;
}

// Load language/translation XML
$lang = $_SESSION['lang'] ?? 0;  // 0 = English, 1 = Thai
$lang_file = ($lang == 1) ? "inc/string-th.xml" : "inc/string-us.xml";
if (file_exists($lang_file)) {
    $xml = simplexml_load_file($lang_file);
} else {
    // Fallback if file not found
    $xml = new SimpleXMLElement('<?xml version="1.0"?><note></note>');
}

// ============================================
// PHASE 3: AUTHORIZATION & AUDIT LOGGING
// ============================================
// Load Authorization and AuditLog classes
require_once("resources/classes/Authorization.php");
require_once("resources/classes/AuditLog.php");

// Load authorization middleware and helpers
require_once("resources/views/middleware/authorization.php");
require_once("resources/views/helpers.php");

// Initialize Authorization instance (global)
$user_id = $_SESSION['usr_id'] ?? null;
if ($user_id) {
    $authorization = new Authorization($db, $user_id);
    // Make available globally - both as $authorization and $auth
    $GLOBALS['authorization'] = $authorization;
    $auth = $authorization;  // Alias for middleware/helpers
    $GLOBALS['auth'] = $auth;
} else {
    $authorization = null;
    $auth = null;
    $GLOBALS['authorization'] = null;
    $GLOBALS['auth'] = null;
}

// Initialize AuditLog instance (global)
if ($user_id) {
    $audit = new AuditLog($db, $user_id);
    $GLOBALS['audit'] = $audit;
} else {
    $audit = null;
    $GLOBALS['audit'] = null;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/tooltip.js"></script>
	<?php include_once "css.php";?>
</head>

<body >

    <div id="wrapper">
		<?php include_once "menu.php";?>

        <div id="page-wrapper">
            <div class="row">
                <?php 
				$page = $_REQUEST['page'] ?? '';
				
				if($page=="dashboard")				
				include_once "dashboard.php";
				else if($page=="company")				
				include_once "company-list.php";
				else if($page=="category")				
				include_once "category-list.php";
				else if($page=="brand")				
				include_once "brand-list.php";
				else if($page=="rep_list")				
				include_once "rep-list.php";
				else if($page=="rep_make")				
				include_once "rep-make.php";
				else if($page=="pr_list")				
				include_once "pr-list.php";
				else if($page=="pr_create")				
				include_once "pr-create.php";
				else if($page=="pr_make")				
				include_once "pr-make.php";
				else if($page=="po_make")				
				include_once "po-make.php";
				else if($page=="po_list")				
				include_once "po-list.php";
				else if($page=="voucher_list")				
				include_once "vou-list.php";
				else if($page=="voc_make")				
				include_once "voc-make.php";
				else if($page=="po_edit")				
				include_once "po-edit.php";
				else if($page=="po_view")				
				include_once "po-view.php";
				else if($page=="po_deliv")				
				include_once "po-deliv.php";
				else if($page=="deliv_list")				
				include_once "deliv-list.php";
				else if($page=="deliv_view")				
				include_once "deliv-view.php";
				else if($page=="deliv_make")				
				include_once "deliv-make.php";
				else if($page=="deliv_edit")				
				include_once "deliv-edit.php";
				else if($page=="compl_list")				
				include_once "compl-list.php";
				else if($page=="payment")				
				include_once "payment-list.php";
				else if($page=="compl_view")				
				include_once "compl-view.php";
				else if($page=="compl_list2")				
				include_once "compl-list2.php";
				else if($page=="qa_list")				
				include_once "qa-list.php";
				else if($page=="mo_list")				
				include_once "mo-list.php";
				else {
					// Default to dashboard if no valid page specified
					include_once "dashboard.php";
				}
				?>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
          
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->

    </div>
     <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      Error
    </div>
  </div>
</div>

    <!-- /#wrapper -->
		<?php include_once "script.php";?>
  
</body>

</html>
<?php
// Flush output buffer (allows header redirects in included pages to work)
ob_end_flush();
?>
