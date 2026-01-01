<?php
/**
 * Test lang.php compatibility fix
 */

// Simulate environment
$_SESSION['usr_id'] = 1;
$_SESSION['usr_name'] = 'test@example.com';
$_SESSION['lang'] = 0;
$_POST['chlang'] = 1;

// Start test
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

$db = new DbConn($config);

echo "=== lang.php Compatibility Test ===\n\n";

echo "1. Session Variables:\n";
echo "   usr_id: {$_SESSION['usr_id']}\n";
echo "   usr_name: {$_SESSION['usr_name']}\n";
echo "   Current lang: {$_SESSION['lang']}\n";

echo "\n2. POST Data:\n";
echo "   New lang: {$_POST['chlang']}\n";

echo "\n3. Testing Database Update:\n";

// Simulate lang.php logic
if(
    isset($_SESSION['usr_id']) && !empty($_SESSION['usr_id']) &&
    isset($_POST['chlang']) && !empty($_POST['chlang'])
){
    $new_lang = intval($_POST['chlang']);
    $current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 0;
    
    echo "   New lang value: {$new_lang}\n";
    echo "   Current lang value: {$current_lang}\n";
    
    if($new_lang != $current_lang){
        echo "   Language change detected\n";
        
        $user_id = intval($_SESSION['usr_id']);
        $username = $_SESSION['usr_name'];
        
        $query = "UPDATE authorize SET lang = ? WHERE usr_id = ? AND usr_name = ?";
        $stmt = $db->conn->prepare($query);
        
        if($stmt){
            echo "   Statement prepared: OK\n";
            $stmt->bind_param('iis', $new_lang, $user_id, $username);
            if($stmt->execute()){
                echo "   ✓ Update executed successfully\n";
                $_SESSION['lang'] = $new_lang;
                echo "   Session updated: lang = {$_SESSION['lang']}\n";
            } else {
                echo "   ✗ Update failed: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "   ✗ Statement prepare failed\n";
        }
    } else {
        echo "   No language change\n";
    }
}

echo "\n✓ lang.php compatibility test complete\n";
