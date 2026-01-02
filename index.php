<?php
/**
 * iAcc Main Entry Point
 * Refactored with array-based routing
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Load core files
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

// Initialize database and check authentication
$db = new DbConn($config);
$db->checkSecurity();

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
    'payment'       => 'payment-list.php',
    'mo_list'       => 'mo-list.php',
    'report'        => 'report.php',
    'receipt_list'  => 'rep-list.php',
    'rep_make'      => 'rep-make.php',
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

<body >

    <div id="wrapper">
		<?php include_once "menu.php";?>

        <div id="page-wrapper">
            <div class="row">
                <?php 
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
