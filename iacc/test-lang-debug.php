<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

// Check if logged in
if(!isset($_SESSION['usr_id'])){
    echo "<h2>❌ Not logged in</h2>";
    echo "<p><a href='login.php'>Go to login</a></p>";
    exit;
}

$db = new DbConn($config);

echo "<h2>🧪 Language Switching Debug</h2>";
echo "<pre>";

echo "SESSION DATA:\n";
echo "  usr_id: " . ($_SESSION['usr_id'] ?? 'NOT SET') . "\n";
echo "  usr_name: " . ($_SESSION['usr_name'] ?? 'NOT SET') . "\n";
echo "  lang: " . ($_SESSION['lang'] ?? 'NOT SET') . "\n";
echo "\n";

// Query database for current user's language
$user_id = intval($_SESSION['usr_id']);
$query = "SELECT usr_id, usr_name, lang FROM authorize WHERE usr_id = ?";
$stmt = $db->conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "DATABASE DATA:\n";
if($row){
    echo "  usr_id: " . $row['usr_id'] . "\n";
    echo "  usr_name: " . $row['usr_name'] . "\n";
    echo "  lang: " . $row['lang'] . " (" . ($row['lang'] == 0 ? "English" : "Thai") . ")\n";
} else {
    echo "  ❌ User not found in database!\n";
}

echo "\nXML FILES AVAILABLE:\n";
if(file_exists("inc/string-us.xml")){
    echo "  ✅ inc/string-us.xml exists\n";
} else {
    echo "  ❌ inc/string-us.xml NOT FOUND\n";
}

if(file_exists("inc/string-th.xml")){
    echo "  ✅ inc/string-th.xml exists\n";
} else {
    echo "  ❌ inc/string-th.xml NOT FOUND\n";
}

// Load and test current language XML
$lang = $_SESSION['lang'] ?? 0;
$lang_file = ($lang == 1) ? "inc/string-th.xml" : "inc/string-us.xml";

echo "\nCURRENT LANGUAGE SETTING:\n";
echo "  Selected file: $lang_file\n";
echo "  Language value: " . $lang . " (" . ($lang == 0 ? "English" : "Thai") . ")\n";

if(file_exists($lang_file)){
    $xml = simplexml_load_file($lang_file);
    if($xml){
        echo "\nSAMPLE MENU ITEMS FROM XML:\n";
        echo "  generalinformation: " . $xml->generalinformation . "\n";
        echo "  company: " . $xml->company . "\n";
        echo "  user: " . $xml->user . "\n";
        echo "  category: " . $xml->category . "\n";
        echo "  brand: " . $xml->brand . "\n";
        echo "  payment: " . $xml->payment . "\n";
    } else {
        echo "  ❌ XML parsing failed\n";
    }
} else {
    echo "  ❌ Language file not found\n";
}

echo "\n=== TEST LANGUAGE CHANGE ===\n";
echo "Click buttons to switch language:\n";
echo "</pre>";

// Show the buttons
echo "<form action='lang.php' method='post'>";
$current_lang = isset($_SESSION['lang']) ? intval($_SESSION['lang']) : 0;
echo "<button type='submit' name='chlang' value='0' style='padding: 10px 20px; margin-right: 10px; font-size: 16px;" . ($current_lang == 0 ? "background-color: #4CAF50; color: white;" : "") . "'>";
echo "🇬🇧 English</button>";
echo "<button type='submit' name='chlang' value='1' style='padding: 10px 20px; font-size: 16px;" . ($current_lang == 1 ? "background-color: #4CAF50; color: white;" : "") . "'>";
echo "🇹🇭 ไทย</button>";
echo "</form>";

echo "<p><a href='index.php'>Back to dashboard</a></p>";
?>
