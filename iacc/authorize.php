<?php 
/**
 * Authentication Handler with RBAC Integration
 * 
 * This script handles user login and initializes the RBAC system.
 * Compatible with existing authorize table structure and new RBAC tables.
 */

error_reporting(E_ALL & ~E_NOTICE);
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

// Initialize database connection
$db = new DbConn($config);

// Check if user is already logged in and trying to log out
if(isset($_SESSION['usr_id']) && !empty($_SESSION['usr_id'])){
    session_destroy();
    echo "<script>alert('Logout Success');window.location='login.php';</script>";
    exit;
}

// Check if login form was submitted
if(!isset($_POST['m_user']) || !isset($_POST['m_pass'])){
    error_log("Login attempt: Missing form data");
    echo "<script>alert('Please submit the login form');history.back();</script>";
    exit;
}

// Prevent SQL injection - use prepared statements
$m_user = trim($_POST['m_user']);
$m_pass = trim($_POST['m_pass']);

// Validate input
if(empty($m_user) || empty($m_pass)){
    error_log("Login attempt: Empty credentials from {$_SERVER['REMOTE_ADDR']}");
    echo "<script>alert('Please enter username and password');history.back();</script>";
    exit;
}

error_log("Login attempt for user: {$m_user}");

// Query with prepared statement
$query = "SELECT usr_id, level, lang FROM authorize WHERE usr_name = ? AND usr_pass = ?";
$stmt = $db->conn->prepare($query);

if(!$stmt){
    error_log("Login: Failed to prepare statement - " . $db->conn->error);
    echo "<script>alert('Database error. Please try again.');history.back();</script>";
    exit;
}

// Bind parameters (MD5 for backward compatibility with existing passwords)
$m_pass_hashed = md5($m_pass);
$stmt->bind_param('ss', $m_user, $m_pass_hashed);

if(!$stmt->execute()){
    error_log("Login: Failed to execute statement - " . $stmt->error);
    echo "<script>alert('Database error. Please try again.');history.back();</script>";
    exit;
}

$result = $stmt->get_result();

if($result->num_rows == 1){
    $row = $result->fetch_assoc();
    $usr_id = $row['usr_id'];
    $level = $row['level'];
    $lang = $row['lang'];
    
    // Set session variables
    $_SESSION['usr_id'] = $usr_id;
    $_SESSION['usr_name'] = $m_user;
    $_SESSION['level'] = $level;
    $_SESSION['lang'] = $lang;
    
    error_log("Login SUCCESS for user: {$m_user} (ID: {$usr_id})");
    
    // Load RBAC if Authorization class exists
    if(file_exists("../resources/classes/Authorization.php")){
        require_once("../resources/classes/Authorization.php");
        
        try {
            $auth = new Authorization($db, $usr_id);
            $_SESSION['auth'] = serialize($auth);
            $_SESSION['rbac_enabled'] = true;
            error_log("RBAC loaded for user: {$m_user}");
        } catch(Exception $e){
            error_log("RBAC Load Error for user {$m_user}: " . $e->getMessage());
            $_SESSION['rbac_enabled'] = false;
        }
    } else {
        $_SESSION['rbac_enabled'] = false;
        error_log("Authorization class not found - RBAC disabled");
    }
    
    $stmt->close();
    
    // Redirect to dashboard
    header("Location: index.php?page=dashboard");
    echo "<script>window.location='index.php?page=dashboard';</script>";
    exit;
    
} else {
    // Login failed
    error_log("Login FAILED for user: {$m_user} - Invalid credentials");
    $stmt->close();
    echo "<script>alert('Invalid username or password');history.back();</script>";
    exit;
}
