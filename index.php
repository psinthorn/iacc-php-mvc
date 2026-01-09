<?php
/**
 * iAcc Main Entry Point
 * Refactored with array-based routing
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set UTF-8 encoding for proper Thai/Unicode support
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();

// Load core files
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

// Initialize database and check authentication
$db = new DbConn($config);
$db->checkSecurity();

// ========== Handle Company Switching (Admin/Super Admin only) ==========
// This must happen before any HTML output so we can redirect
if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'remote') {
    $userLevel = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
    
    if ($userLevel < 1) {
        // Normal users cannot switch companies
        header('Location: index.php?page=dashboard&error=access_denied');
        exit;
    }
    
    // Handle clear company (back to admin panel)
    if (isset($_GET['clear']) && $_GET['clear'] == '1') {
        $_SESSION['com_id'] = "";
        $_SESSION['com_name'] = "";
        header('Location: index.php?page=dashboard');
        exit;
    }
    
    // Handle quick company selection from dashboard
    if (isset($_GET['select_company'])) {
        $company_id = sql_int($_GET['select_company']);
        $sql = "SELECT name_en, name_sh FROM company WHERE id='" . $company_id . "' AND deleted_at IS NULL";
        $result = mysqli_query($db->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $comname = mysqli_fetch_array($result);
            $_SESSION['com_id'] = $company_id;
            $_SESSION['com_name'] = $comname['name_en'] ?: $comname['name_sh'];
        }
        header('Location: index.php?page=dashboard');
        exit;
    }
    
    // Handle toggle from company list (legacy id parameter)
    if (isset($_GET['id'])) {
        $company_id = sql_int($_GET['id']);
        if ($_SESSION['com_id'] == $company_id) {
            // If same company, clear it
            $_SESSION['com_id'] = "";
            $_SESSION['com_name'] = "";
        } else {
            // Set new company
            $sql = "SELECT name_en, name_sh FROM company WHERE id='" . $company_id . "'";
            $comname = mysqli_fetch_array(mysqli_query($db->conn, $sql));
            $_SESSION['com_id'] = $company_id;
            $_SESSION['com_name'] = $comname['name_en'] ?: $comname['name_sh'];
        }
        header('Location: index.php?page=dashboard');
        exit;
    }
    
    // If no action specified, go to dashboard
    header('Location: index.php?page=dashboard');
    exit;
}

// Page routing configuration - maps page parameter to file
$routes = [
    // Dashboard
    'dashboard'     => 'dashboard.php',
    
    // Master Data
    'company'       => 'company-list.php',
    'user'          => 'user-list.php',      // Super Admin only
    'category'      => 'category-list.php',
    'type'          => 'type-list.php',
    'brand'         => 'brand-list.php',
    
    // Purchase Requisition
    'pr_list'       => 'pr-list.php',
    'pr_create'     => 'pr-create.php',
    'pr_make'       => 'pr-make.php',
    
    // Purchase Order
    'po_make'       => 'po-make.php',
    'po_list'       => 'po-list.php',
    'po_edit'       => 'po-edit.php',
    'po_view'       => 'po-view.php',
    'po_deliv'      => 'po-deliv.php',
    
    // Voucher
    'voucher_list'  => 'vou-list.php',
    'voc_make'      => 'voc-make.php',
    'voc_view'      => 'voc-view.php',
    'vou_print'     => 'vou-print.php',
    
    // Delivery
    'deliv_list'    => 'deliv-list.php',
    'deliv_view'    => 'deliv-view.php',
    'deliv_make'    => 'deliv-make.php',
    'deliv_edit'    => 'deliv-edit.php',
    
    // Complaint / QA
    'compl_list'    => 'compl-list.php',
    'compl_list2'   => 'compl-list2.php',
    'compl_view'    => 'compl-view.php',
    'qa_list'       => 'qa-list.php',
    
    // Payment & Reports
    'payment'           => 'payment-list.php',
    'invoice_payments'  => 'invoice-payments.php',
    'mo_list'           => 'mo-list.php',
    'report'            => 'report.php',
    'receipt_list'      => 'rep-list.php',
    'rep_make'          => 'rep-make.php',
    'rep_view'          => 'rep-view.php',
    'rep_print'         => 'rep-print.php',
    
    // Admin Tools
    'audit_log'             => 'audit-log.php',
    'monitoring'            => 'admin-monitoring.php',
    'containers'            => 'admin-containers.php',
    'payment_method_list'   => 'payment-method-list.php',
    'payment_method'        => 'payment-method.php',
    
    // Payment Gateway
    'payment_gateway_config' => 'payment-gateway-config.php',
    'payment_gateway_test'   => 'payment-gateway-test.php',
    'payment_webhook'        => 'payment-webhook.php',
    
    // Developer Tools (Admin Only)
    'test_crud'              => 'test-crud.php',
    'test_crud_ai'           => 'test-crud-ai.php',
    'test_rbac'              => 'test-rbac.php',
    'ai_settings'            => 'ai-settings.php',
    'ai_chat_history'        => 'ai-chat-history.php',
    'ai_schema_browser'      => 'ai-schema-browser.php',
    'ai_action_log'          => 'ai-action-log.php',
    'ai_schema_refresh'      => 'ai-schema-refresh.php',
    'ai_documentation'       => 'ai-documentation.php',
    'debug_session'          => 'debug-session.php',
    'debug_invoice'          => 'debug-invoice.php',
    'docker_test'            => 'docker-test.php',
    'test_containers'        => 'test-containers.php',
    'api_lang_debug'         => 'api-lang-debug.php',
    
    // AI Chat API
    'ai_chat'                => 'ai/chat-handler.php',
    
    // Invoice Payment
    'inv_checkout'           => 'inv-checkout.php',
    'inv_payment_success'    => 'inv-payment-success.php',
    'inv_payment_cancel'     => 'inv-payment-cancel.php',
    
    // User Account
    'profile'                => 'profile.php',
    'settings'               => 'settings.php',
    'help'                   => 'help.php',
];

// Get requested page (sanitized)
$page = isset($_REQUEST['page']) ? preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['page']) : 'dashboard';

// Determine which file to include
$pageFile = isset($routes[$page]) ? $routes[$page] : null;
?>
<!DOCTYPE html>
<html>

<head>
    <script src="js/tooltip.js"></script>
	<?php include_once "css.php";?>
</head>

<body class="has-top-nav">

    <div id="wrapper">
		<?php include_once "menu.php";?>

        <div id="page-wrapper">
            <div class="row">
                <?php 
                // Debug routing
                file_put_contents('logs/app.log', date('Y-m-d H:i:s') . " DEBUG index.php: page=$page, pageFile=$pageFile, exists=" . (file_exists($pageFile) ? 'yes' : 'no') . "\n", FILE_APPEND);
                
                // Include the page file if route exists
                if ($pageFile && file_exists($pageFile)) {
                    include_once $pageFile;
                } else {
                    // 404 - Page not found
                    echo '<div class="col-lg-12">';
                    echo '<div class="alert alert-warning">';
                    echo '<h4><i class="fa fa-exclamation-triangle"></i> Page Not Found</h4>';
                    echo '<p>The requested page "' . e($page) . '" does not exist.</p>';
                    echo '<a href="index.php" class="btn btn-primary">Go to Dashboard</a>';
                    echo '</div>';
                    echo '</div>';
                }
				?>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
          
            <!-- Footer -->
            <?php include_once "inc/footer.php"; ?>
            
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
