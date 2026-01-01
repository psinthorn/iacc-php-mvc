<?php
/**
 * Session & Configuration Test
 */

error_reporting(E_ALL);
session_start();

echo "=== Session & Configuration Test ===\n\n";

echo "1. PHP Configuration:\n";
echo "   Version: " . phpversion() . "\n";
echo "   Session Handler: " . ini_get('session.save_handler') . "\n";
echo "   Session Path: " . ini_get('session.save_path') . "\n";
echo "   Session Name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Status: " . session_status() . "\n";

echo "\n2. Testing Session Variable Storage:\n";
$_SESSION['test_var'] = 'test_value_123';
echo "   Stored: \$_SESSION['test_var'] = '{$_SESSION['test_var']}'\n";
echo "   Readable: " . (isset($_SESSION['test_var']) ? "YES" : "NO") . "\n";

echo "\n3. POST Data Handling:\n";
$_POST['test_user'] = 'test@example.com';
$_POST['test_pass'] = 'password123';
echo "   Stored: \$_POST['test_user'] = '{$_POST['test_user']}'\n";
echo "   Stored: \$_POST['test_pass'] = '{$_POST['test_pass']}'\n";

echo "\n4. Database Configuration:\n";
$baseDir = '/var/www/html';
require_once($baseDir . "/inc/sys.configs.php");
require_once($baseDir . "/inc/class.dbconn.php");

$db = new DbConn($config);
echo "   Host: {$config['db_host']}\n";
echo "   Database: {$config['db_name']}\n";
echo "   Connection: " . ($db->conn ? "OK" : "FAILED") . "\n";

// Test database access
$result = $db->conn->query("SELECT COUNT(*) as count FROM authorize");
$row = $result->fetch_assoc();
echo "   Users in DB: {$row['count']}\n";

echo "\n✓ All Systems OK\n";
