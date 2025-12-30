<?php
error_reporting(E_ALL & ~E_NOTICE);
// error_reporting(E_ALL);
session_start();

// Start output buffering to allow header redirects in included pages
ob_start();

header('Content-Type: text/html; charset=utf-8');
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db = new DbConn($config);

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
<html>

<head>
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
				
				if($page=="company")				
				include_once "company-list.php";
				if($page=="category")				
				include_once "category-list.php";
				if($page=="brand")				
				include_once "band-list.php";
				
				if($page=="type")				
				include_once "type-list.php";
				
				if($page=="receipt_list")				
				include_once "rep-list.php";
				if($page=="rep_make")				
				include_once "rep-make.php";
				if($page=="pr_list")				
				include_once "pr-list.php";
				if($page=="pr_create")				
				include_once "pr-create.php";
				if($page=="pr_make")				
				include_once "pr-make.php";
				if($page=="po_make")				
				include_once "po-make.php";
				if($page=="po_list")				
				include_once "po-list.php";
				if($page=="voucher_list")				
				include_once "vou-list.php";
				if($page=="voc_make")				
				include_once "voc-make.php";
				if($page=="po_edit")				
				include_once "po-edit.php";
					if($page=="po_view")				
				include_once "po-view.php";
					if($page=="po_deliv")				
				include_once "po-deliv.php";
				if($page=="deliv_list")				
				include_once "deliv-list.php";
				if($page=="deliv_view")				
				include_once "deliv-view.php";
				if($page=="deliv_make")				
				include_once "deliv-make.php";
				if($page=="deliv_edit")				
				include_once "deliv-edit.php";
				if($page=="compl_list")				
				include_once "compl-list.php";
				if($page=="payment")				
				include_once "payment-list.php";
				if($page=="compl_view")				
				include_once "compl-view.php";
				if($page=="compl_list2")				
				include_once "compl-list2.php";
				if($page=="qa_list")				
				include_once "qa-list.php";
				if($page=="mo_list")				
				include_once "mo-list.php";
				
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
