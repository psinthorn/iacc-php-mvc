<?php
/**
 * Debug Login Test
 * Test the authorize.php login mechanism
 */

error_reporting(E_ALL);
session_start();

$baseDir = '/var/www/html';
require_once($baseDir . "/inc/sys.configs.php");
require_once($baseDir . "/inc/class.dbconn.php");

$db = new DbConn($config);

echo "=== Login Debug Test ===\n\n";

// Test 1: Check database connection
echo "1. Database Connection Test...\n";
if($db->conn){
    echo "   ✓ Connected to database\n";
} else {
    echo "   ✗ Database connection failed\n";
    exit;
}

// Test 2: Check if authorize table has data
echo "\n2. Authorize Table Test...\n";
$result = $db->conn->query("SELECT COUNT(*) as count FROM authorize");
$row = $result->fetch_assoc();
echo "   ✓ Found {$row['count']} users\n";

// Test 3: List users
echo "\n3. Available Users...\n";
$result = $db->conn->query("SELECT usr_id, usr_name, usr_pass FROM authorize LIMIT 5");
while($row = $result->fetch_assoc()){
    echo "   - ID:{$row['usr_id']} | Email: {$row['usr_name']} | Hash: " . substr($row['usr_pass'], 0, 10) . "...\n";
}

// Test 4: Test login with known credentials
echo "\n4. Testing Login with Known Credentials...\n";
$test_user = "etatun@directbooking.co.th";
$test_pass = "123456";
$test_pass_hashed = md5($test_pass);

echo "   Testing: {$test_user} / {$test_pass}\n";
echo "   MD5 Hash: {$test_pass_hashed}\n";

// Test with prepared statement
$query = "SELECT usr_id, level, lang FROM authorize WHERE usr_name = ? AND usr_pass = ?";
$stmt = $db->conn->prepare($query);

if(!$stmt){
    echo "   ✗ Prepared statement failed: " . $db->conn->error . "\n";
    exit;
}

$stmt->bind_param('ss', $test_user, $test_pass_hashed);

if(!$stmt->execute()){
    echo "   ✗ Execute failed: " . $stmt->error . "\n";
    exit;
}

$result = $stmt->get_result();
$num_rows = $result->num_rows;

echo "   Query returned: {$num_rows} rows\n";

if($num_rows == 1){
    $row = $result->fetch_assoc();
    echo "   ✓ LOGIN SUCCESS!\n";
    echo "   - usr_id: {$row['usr_id']}\n";
    echo "   - level: {$row['level']}\n";
    echo "   - lang: {$row['lang']}\n";
} else {
    echo "   ✗ LOGIN FAILED - No matching user found\n";
    
    // Debug: Check if user exists at all
    echo "\n   Debug Info:\n";
    $debug_query = "SELECT usr_id, usr_name, usr_pass FROM authorize WHERE usr_name = ?";
    $debug_stmt = $db->conn->prepare($debug_query);
    $debug_stmt->bind_param('s', $test_user);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    
    if($debug_result->num_rows > 0){
        echo "   - User exists but password doesn't match\n";
        $debug_row = $debug_result->fetch_assoc();
        echo "   - Stored hash: {$debug_row['usr_pass']}\n";
        echo "   - Provided hash: {$test_pass_hashed}\n";
        echo "   - Match: " . ($debug_row['usr_pass'] === $test_pass_hashed ? "YES" : "NO") . "\n";
    } else {
        echo "   - User doesn't exist in database\n";
    }
}

$stmt->close();

echo "\n=== Test Complete ===\n";
?>
