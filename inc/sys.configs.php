<?PHP
// Load error handler first to suppress deprecated PHP warnings
require_once(dirname(__FILE__) . "/error-handler.php");

// ============================================================================
// SESSION SECURITY SETTINGS
// Configure these BEFORE session_start() is called
// ============================================================================
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
ini_set('session.use_strict_mode', 1);      // Reject uninitialized session IDs
ini_set('session.cookie_samesite', 'Lax');  // Prevent CSRF via cross-site requests
// ini_set('session.cookie_secure', 1);     // Uncomment when using HTTPS

// ============================================================================
// SERVER : MYSQL Configuration
// ============================================================================
$config["hostname"] = "mysql";
$config["username"] = "root";
//$config["username"] = "theiconn_cms";
$config["password"] = "root";
// $config["dbname"]   = "root";
$config["dbname"]   = "iacc";

// Sets the default timezone
date_default_timezone_set("Asia/Bangkok"); 

// SERVER : MYSQL Cnfiguration
//$config["hostname"] = "localhost";
//$config["username"] = "root";
//$config["username"] = "theiconn_cms";
//$config["password"] = ")q#gLfESG;M(";
//$config["dbname"]   = "ngt-admin";
//$config["dbname"]   = "theiconn_cms";

