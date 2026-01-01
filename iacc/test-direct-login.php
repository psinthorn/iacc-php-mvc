<?php
/**
 * Test direct HTTP POST to authorize.php
 */

error_reporting(E_ALL);

$baseDir = '/var/www/html';
require_once($baseDir . "/inc/sys.configs.php");
require_once($baseDir . "/inc/class.dbconn.php");

echo "=== Testing Login via Direct HTTP POST ===\n\n";

// Create test data as if it came from HTML form
$_POST['m_user'] = 'etatun@directbooking.co.th';
$_POST['m_pass'] = '123456';

// Start session 
session_start();

$db = new DbConn($config);

echo "1. Testing with: {$_POST['m_user']} / {$_POST['m_pass']}\n";

// Simulate authorize.php logic
$m_user = trim($_POST['m_user']);
$m_pass = trim($_POST['m_pass']);

echo "2. After trim: '{$m_user}' / '{$m_pass}'\n";

$query = "SELECT usr_id, level, lang FROM authorize WHERE usr_name = ? AND usr_pass = ?";
$stmt = $db->conn->prepare($query);

if(!$stmt){
    echo "ERROR: {$db->conn->error}\n";
    exit;
}

$m_pass_hashed = md5($m_pass);
echo "3. Password hash: {$m_pass_hashed}\n";

$stmt->bind_param('ss', $m_user, $m_pass_hashed);
$stmt->execute();
$result = $stmt->get_result();

echo "4. Rows returned: {$result->num_rows}\n";

if($result->num_rows == 1){
    $row = $result->fetch_assoc();
    echo "\n✓ LOGIN SUCCESS!\n";
    echo "   User ID: {$row['usr_id']}\n";
    echo "   Level: {$row['level']}\n";
    echo "   Lang: {$row['lang']}\n";
    
    $_SESSION['usr_id'] = $row['usr_id'];
    $_SESSION['usr_name'] = $m_user;
    $_SESSION['level'] = $row['level'];
    $_SESSION['lang'] = $row['lang'];
    
    // Load RBAC
    if(file_exists("resources/classes/Authorization.php")){
        require_once("resources/classes/Authorization.php");
        try {
            $auth = new Authorization($db, $row['usr_id']);
            $_SESSION['auth'] = serialize($auth);
            $_SESSION['rbac_enabled'] = true;
            echo "\n✓ RBAC Loaded: YES\n";
        } catch(Exception $e){
            $_SESSION['rbac_enabled'] = false;
            echo "\n✓ RBAC Loaded: NO - {$e->getMessage()}\n";
        }
    }
    
    echo "\n✓ Session set - Ready to redirect\n";
    
} else {
    echo "\n✗ LOGIN FAILED\n";
    
    // Debug
    $debug_query = "SELECT usr_id, usr_name, usr_pass FROM authorize WHERE usr_name = ?";
    $debug_stmt = $db->conn->prepare($debug_query);
    $debug_stmt->bind_param('s', $m_user);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    
    if($debug_result->num_rows > 0){
        echo "   User EXISTS in database\n";
        $debug_row = $debug_result->fetch_assoc();
        echo "   Stored hash: {$debug_row['usr_pass']}\n";
        echo "   Test hash: {$m_pass_hashed}\n";
    } else {
        echo "   User NOT FOUND in database\n";
    }
}
