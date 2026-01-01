<?php
/**
 * Test Language Switching
 * Verifies that language preference changes are saved and reflected
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

$db = new DbConn($config);

echo "<h2>🧪 Language Switching Test</h2>";
echo "<pre>";

// Simulate being logged in
$_SESSION['usr_id'] = 1;
$_SESSION['usr_name'] = 'testuser';
$_SESSION['lang'] = 0;  // Start with English

echo "=== TEST 1: Check Current Language ===\n";
echo "Session lang value: " . $_SESSION['lang'] . "\n";
echo "Expected: 0 (English)\n\n";

// Simulate language change to Thai
echo "=== TEST 2: Change Language to Thai ===\n";
$_POST['chlang'] = 1;

// Simulate the logic from lang.php
if(
    isset($_SESSION['usr_id']) && !empty($_SESSION['usr_id']) &&
    isset($_POST['chlang']) && !empty($_POST['chlang'])
){
    $new_lang = intval($_POST['chlang']);
    $current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 0;
    
    echo "New lang from POST: " . $new_lang . "\n";
    echo "Current lang in session: " . $current_lang . "\n";
    
    if($new_lang != $current_lang){
        $user_id = intval($_SESSION['usr_id']);
        $username = $_SESSION['usr_name'];
        
        $query = "UPDATE authorize SET lang = ? WHERE usr_id = ? AND usr_name = ?";
        $stmt = $db->conn->prepare($query);
        
        if($stmt){
            $stmt->bind_param('iis', $new_lang, $user_id, $username);
            if($stmt->execute()){
                $_SESSION['lang'] = $new_lang;
                echo "✅ Language updated in database and session\n";
                echo "New session lang: " . $_SESSION['lang'] . "\n";
            } else {
                echo "❌ Database update failed: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "❌ Statement preparation failed: " . $db->conn->error . "\n";
        }
    }
}

echo "\n=== TEST 3: Check Menu Button Status ===\n";
$current_lang = isset($_SESSION['lang']) ? intval($_SESSION['lang']) : 0;
echo "Current language: " . $current_lang . "\n";

if($current_lang == 0) {
    echo "✅ English button would show as ACTIVE\n";
} else if($current_lang == 1) {
    echo "✅ Thai button would show as ACTIVE\n";
}

echo "\n=== TEST 4: Language File Selection ===\n";
$lang = $_SESSION['lang'] ?? 0;
$lang_file = ($lang == 1) ? "inc/string-th.xml" : "inc/string-us.xml";
echo "Selected language file: $lang_file\n";
if(file_exists($lang_file)) {
    echo "✅ Language file exists\n";
} else {
    echo "❌ Language file NOT FOUND\n";
}

echo "</pre>";
?>
