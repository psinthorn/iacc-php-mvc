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

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Secure session cookie settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

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

// ========== Company Search API for Dashboard Smart Search ==========
if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'company_search_api') {
    header('Content-Type: application/json');
    
    $userLevel = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
    if ($userLevel < 1) {
        echo json_encode([]);
        exit;
    }
    
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $search_escaped = sql_escape($query);
    $sql = "SELECT id, name_en, name_th, name_sh, contact, email, logo, customer, vender 
            FROM company 
            WHERE deleted_at IS NULL 
            AND (name_en LIKE '%$search_escaped%' 
                 OR name_th LIKE '%$search_escaped%' 
                 OR name_sh LIKE '%$search_escaped%'
                 OR contact LIKE '%$search_escaped%' 
                 OR email LIKE '%$search_escaped%')
            ORDER BY name_en ASC
            LIMIT 10";
    
    $result = mysqli_query($db->conn, $sql);
    $companies = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $companies[] = [
                'id' => $row['id'],
                'name_en' => $row['name_en'],
                'name_th' => $row['name_th'],
                'contact' => $row['contact'],
                'email' => $row['email'],
                'logo' => $row['logo'],
                'customer' => $row['customer'],
                'vender' => $row['vender']
            ];
        }
    }
    
    echo json_encode($companies);
    exit;
}

// Page routing configuration — loaded from external config
$routes = require __DIR__ . '/app/Config/routes.php';

// Get requested page (sanitized)
$page = isset($_REQUEST['page']) ? preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['page']) : 'dashboard';

// Determine route type: MVC (array) or legacy (string filename)
$route = isset($routes[$page]) ? $routes[$page] : null;

// ========== MVC Controller Dispatch (before any HTML output) ==========
// Routes defined as arrays dispatch to a controller method and may redirect
if (is_array($route)) {
    $controllerName = 'App\\Controllers\\' . $route[0];
    $methodName = $route[1];
    
    // Dispatch before HTML for: POST actions, store/delete methods, AJAX endpoints, and GET actions that redirect
    $earlyDispatchMethods = ['store', 'delete', 'getBrands', 'toggle'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || in_array($methodName, $earlyDispatchMethods)) {
        $controller = new $controllerName();
        $controller->$methodName();
        exit; // Controller handles redirect/response
    }
}

// Determine legacy file path (for non-MVC routes)
// Note: As of Phase 6, all routes are MVC. This is kept for safety only.
$pageFile = is_string($route) ? $route : null;
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once "css.php";?>
    <script src="js/tooltip.js"></script>
</head>

<body class="has-top-nav">

    <div id="wrapper">
		<?php include_once "menu.php";?>

        <div id="page-wrapper">
            <div class="row">
                <?php 
                // Debug routing (development only)
                if ((getenv('APP_ENV') ?: 'development') !== 'production') {
                    $debugRoute = is_array($route) ? ('MVC:' . $route[0] . '::' . $route[1]) : ($pageFile ?? 'null');
                    file_put_contents('logs/app.log', date('Y-m-d H:i:s') . " DEBUG index.php: page=$page, route=$debugRoute\n", FILE_APPEND);
                }
                
                // ========== MVC Controller Rendering (GET requests) ==========
                if (is_array($route)) {
                    $controllerName = 'App\\Controllers\\' . $route[0];
                    $methodName = $route[1];
                    $controller = new $controllerName();
                    $controller->$methodName();
                }
                // ========== Legacy File Include ==========
                elseif ($pageFile && file_exists($pageFile)) {
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