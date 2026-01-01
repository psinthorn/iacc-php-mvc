<?php
session_start();
echo "<h1>Debug Invoice Access</h1>";
echo "<pre>";

// Session info
echo "=== SESSION ===\n";
echo "com_id: " . ($_SESSION['com_id'] ?? 'NOT SET') . "\n";
echo "usr_id: " . ($_SESSION['usr_id'] ?? 'NOT SET') . "\n";
echo "auth: " . ($_SESSION['auth'] ?? 'NOT SET') . "\n\n";

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db = new DbConn($config);
// Skip security check for debugging

echo "=== PO 1923 REQUIRES ===\n";
$q = mysqli_query($db->conn, "SELECT po.id, pr.cus_id, ven_id, payby FROM pr JOIN po ON pr.id = po.ref WHERE po.id = '1923'");
if ($q && mysqli_num_rows($q) > 0) {
    $row = mysqli_fetch_assoc($q);
    echo "cus_id: " . $row['cus_id'] . "\n";
    echo "ven_id: " . $row['ven_id'] . "\n";  
    echo "payby: " . $row['payby'] . "\n\n";
    
    $session_com = $_SESSION['com_id'] ?? 0;
    if ($session_com == $row['cus_id'] || $session_com == $row['ven_id'] || $session_com == $row['payby']) {
        echo "✅ Your com_id ($session_com) HAS ACCESS\n";
    } else {
        echo "❌ Your com_id ($session_com) does NOT have access\n";
        echo "Need: {$row['cus_id']}, {$row['ven_id']}, or {$row['payby']}\n";
    }
}

echo "</pre>";
