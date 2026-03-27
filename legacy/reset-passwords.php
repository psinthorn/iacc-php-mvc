<?php
/**
 * User Password Reset Tool
 * Sets password to '123456' for testing
 */

error_reporting(E_ALL);

$baseDir = '/var/www/html';
require_once($baseDir . "/inc/sys.configs.php");
require_once($baseDir . "/inc/class.dbconn.php");

$db = new DbConn($config);

echo "=== User Password Reset Tool ===\n\n";

// Show all users
echo "Current Users in Database:\n";
echo "---\n";
$result = $db->conn->query("SELECT id, email, password FROM authorize");
while($row = $result->fetch_assoc()){
    echo "ID: {$row['id']}\n";
    echo "Email: {$row['email']}\n";
    echo "Hash: {$row['password']}\n";
    echo "---\n";
}

// Set password to 123456 for all users
$new_pass_hash = md5('123456');
echo "\nSetting all passwords to '123456'...\n";
echo "Hash: {$new_pass_hash}\n\n";

$update_query = "UPDATE authorize SET password = ? WHERE id > 0";
$stmt = $db->conn->prepare($update_query);
$stmt->bind_param('s', $new_pass_hash);
$stmt->execute();

echo "✓ Updated " . $db->conn->affected_rows . " records\n\n";

// Verify
echo "Verification - Updated Users:\n";
echo "---\n";
$result = $db->conn->query("SELECT id, email, password FROM authorize");
while($row = $result->fetch_assoc()){
    echo "ID: {$row['id']}\n";
    echo "Email: {$row['email']}\n";
    echo "Hash: {$row['password']}\n";
    echo "---\n";
}

echo "\nNow you can login with ANY of these emails using password: 123456\n";
echo "Example:\n";
echo "  Email: etatun@directbooking.co.th\n";
echo "  Password: 123456\n";
