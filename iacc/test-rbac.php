<?php
/**
 * RBAC Integration Test
 * Verifies login works with RBAC authentication system
 */

error_reporting(E_ALL);
session_start();

// Use absolute paths for container execution
$baseDir = '/var/www/html';
require_once($baseDir . "/inc/sys.configs.php");
require_once($baseDir . "/inc/class.dbconn.php");
require_once($baseDir . "/resources/classes/Authorization.php");

$db = new DbConn($config);

echo "=== RBAC Authentication Test ===\n\n";

// Test 1: Check RBAC tables exist
echo "1. Testing RBAC table structure...\n";
$tables = array('roles', 'permissions', 'role_permissions', 'user_roles');
foreach($tables as $table){
    $result = $db->conn->query("SHOW TABLES LIKE '{$table}'");
    if($result->num_rows > 0){
        echo "   ✓ Table '{$table}' exists\n";
    } else {
        echo "   ✗ Table '{$table}' missing\n";
    }
}

// Test 2: Check roles
echo "\n2. Testing roles...\n";
$result = $db->conn->query("SELECT COUNT(*) as count FROM roles");
$row = $result->fetch_assoc();
echo "   Found {$row['count']} roles\n";

// Test 3: Check permissions
echo "\n3. Testing permissions...\n";
$result = $db->conn->query("SELECT COUNT(*) as count FROM permissions");
$row = $result->fetch_assoc();
echo "   Found {$row['count']} permissions\n";

// Test 4: Check user roles mapping
echo "\n4. Testing user roles mapping...\n";
$result = $db->conn->query("SELECT COUNT(*) as count FROM user_roles");
$row = $result->fetch_assoc();
echo "   Found {$row['count']} user-role mappings\n";

// Test 5: Test with a real user
echo "\n5. Testing with existing user (usr_id = 1)...\n";
try {
    $auth = new Authorization($db, 1);
    echo "   ✓ Authorization object created\n";
    
    // Check if user has admin role
    if($auth->hasRole('Admin')){
        echo "   ✓ User has 'Admin' role\n";
    } else {
        echo "   ✗ User doesn't have 'Admin' role\n";
    }
    
    // Check if user can view PO
    if($auth->can('po.view')){
        echo "   ✓ User can view PO\n";
    } else {
        echo "   ✗ User cannot view PO\n";
    }
    
} catch(Exception $e){
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 6: Verify authorize table still works
echo "\n6. Testing authorize table compatibility...\n";
$query = "SELECT usr_id, usr_name, level FROM authorize WHERE usr_id = 1";
$result = $db->conn->query($query);
if($result && $result->num_rows > 0){
    $row = $result->fetch_assoc();
    echo "   ✓ Found user: {$row['usr_name']} (ID: {$row['usr_id']}, Level: {$row['level']})\n";
} else {
    echo "   ✗ User not found\n";
}

echo "\n=== Test Complete ===\n";
?>
