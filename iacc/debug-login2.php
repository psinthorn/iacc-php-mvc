<?php
/**
 * Simulate Login Form POST
 */

error_reporting(E_ALL);
session_start();

$baseDir = '/var/www/html';
require_once($baseDir . "/inc/sys.configs.php");
require_once($baseDir . "/inc/class.dbconn.php");

// Simulate form submission
$_POST['m_user'] = 'etatun@directbooking.co.th';
$_POST['m_pass'] = '123456';

// Initialize database connection
$db = new DbConn($config);

echo "=== Simulating Login Form Submission ===\n\n";

echo "1. Form Data Received:\n";
echo "   Username: {$_POST['m_user']}\n";
echo "   Password: {$_POST['m_pass']}\n";

// Check if login form was submitted
if(!isset($_POST['m_user']) || !isset($_POST['m_pass'])){
    echo "\n✗ Form data missing\n";
    exit;
}

// Prevent SQL injection - use prepared statements
$m_user = trim($_POST['m_user']);
$m_pass = trim($_POST['m_pass']);

echo "\n2. After Trimming:\n";
echo "   Username: '{$m_user}'\n";
echo "   Password: '{$m_pass}'\n";

// Validate input
if(empty($m_user) || empty($m_pass)){
    echo "\n✗ Validation failed - empty fields\n";
    exit;
}

echo "\n3. Input Validation: OK\n";

// Query with prepared statement
$query = "SELECT usr_id, level, lang FROM authorize WHERE usr_name = ? AND usr_pass = ?";
echo "\n4. Preparing Query...\n";
echo "   Query: {$query}\n";

$stmt = $db->conn->prepare($query);

if(!$stmt){
    echo "\n✗ Failed to prepare statement: " . $db->conn->error . "\n";
    exit;
}

echo "   ✓ Statement prepared\n";

// Bind parameters (MD5 for backward compatibility with existing passwords)
$m_pass_hashed = md5($m_pass);
echo "\n5. Parameter Binding:\n";
echo "   Username: {$m_user}\n";
echo "   Password Hash: {$m_pass_hashed}\n";

$stmt->bind_param('ss', $m_user, $m_pass_hashed);

echo "   ✓ Parameters bound\n";

if(!$stmt->execute()){
    echo "\n✗ Failed to execute: " . $stmt->error . "\n";
    exit;
}

echo "\n6. Query Executed: OK\n";

$result = $stmt->get_result();
$num_rows = $result->num_rows;

echo "\n7. Results: {$num_rows} row(s) returned\n";

if($num_rows == 1){
    $row = $result->fetch_assoc();
    $usr_id = $row['usr_id'];
    $level = $row['level'];
    $lang = $row['lang'];
    
    echo "\n✓ LOGIN SUCCESS!\n";
    echo "   User ID: {$usr_id}\n";
    echo "   Level: {$level}\n";
    echo "   Lang: {$lang}\n";
    
    // Set session variables
    $_SESSION['usr_id'] = $usr_id;
    $_SESSION['usr_name'] = $m_user;
    $_SESSION['level'] = $level;
    $_SESSION['lang'] = $lang;
    
    echo "\n8. Session Variables Set:\n";
    echo "   \$_SESSION['usr_id'] = {$_SESSION['usr_id']}\n";
    echo "   \$_SESSION['usr_name'] = {$_SESSION['usr_name']}\n";
    echo "   \$_SESSION['level'] = {$_SESSION['level']}\n";
    echo "   \$_SESSION['lang'] = {$_SESSION['lang']}\n";
    
    // Load RBAC if Authorization class exists
    if(file_exists("resources/classes/Authorization.php")){
        require_once("resources/classes/Authorization.php");
        
        try {
            $auth = new Authorization($db, $usr_id);
            $_SESSION['auth'] = serialize($auth);
            $_SESSION['rbac_enabled'] = true;
            echo "\n9. RBAC Authorization: ✓ Loaded\n";
        } catch(Exception $e){
            error_log("RBAC Load Error: " . $e->getMessage());
            $_SESSION['rbac_enabled'] = false;
            echo "\n9. RBAC Authorization: ✗ Not loaded - " . $e->getMessage() . "\n";
        }
    } else {
        $_SESSION['rbac_enabled'] = false;
        echo "\n9. RBAC Authorization: Not available\n";
    }
    
    echo "\n✓ Ready to redirect to dashboard\n";
    
} else {
    // Login failed
    echo "\n✗ LOGIN FAILED\n";
    echo "   No matching user found\n";
}

$stmt->close();

echo "\n=== Test Complete ===\n";
