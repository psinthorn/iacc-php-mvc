<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

if(!isset($_SESSION['usr_id'])){
    echo "<h2>❌ Not logged in</h2>";
    echo "<p><a href='login.php'>Go to login</a></p>";
    exit;
}

$db = new DbConn($config);

echo "<h2>🔍 Language Switching Debug & Test Page</h2>";
echo "<style>
body { font-family: Arial; margin: 20px; }
.section { background: #f0f0f0; padding: 15px; margin: 15px 0; border-radius: 5px; }
.ok { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.test-button { padding: 10px 20px; margin: 5px; font-size: 16px; cursor: pointer; }
</style>";

echo "<div class='section'>";
echo "<h3>📊 SESSION & DATABASE STATUS</h3>";
echo "<p><strong>Session Values:</strong></p>";
echo "<ul>";
echo "<li>usr_id: <code>" . ($_SESSION['usr_id'] ?? 'NOT SET') . "</code></li>";
echo "<li>usr_name: <code>" . ($_SESSION['usr_name'] ?? 'NOT SET') . "</code></li>";
echo "<li>lang: <code>" . ($_SESSION['lang'] ?? 'NOT SET') . "</code> " . (($_SESSION['lang'] ?? 0) == 0 ? "🇬🇧 English" : "🇹🇭 Thai") . "</li>";
echo "</ul>";

// Check database
$user_id = intval($_SESSION['usr_id']);
$query = "SELECT usr_id, usr_name, lang FROM authorize WHERE usr_id = ?";
$stmt = $db->conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "<p><strong>Database Values:</strong></p>";
if($row){
    echo "<ul>";
    echo "<li>usr_id: <code>" . $row['usr_id'] . "</code></li>";
    echo "<li>usr_name: <code>" . $row['usr_name'] . "</code></li>";
    echo "<li>lang: <code>" . $row['lang'] . "</code> " . ($row['lang'] == 0 ? "🇬🇧 English" : "🇹🇭 Thai") . "</li>";
    echo "</ul>";
    
    if($row['lang'] == ($_SESSION['lang'] ?? 0)){
        echo "<p class='ok'>✅ SESSION and DATABASE are SYNCHRONIZED</p>";
    } else {
        echo "<p class='error'>⚠️ SESSION and DATABASE are OUT OF SYNC!</p>";
        echo "<p>Session: " . ($_SESSION['lang'] ?? 0) . ", Database: " . $row['lang'] . "</p>";
    }
} else {
    echo "<p class='error'>❌ User not found in database!</p>";
}
echo "</div>";

// Check XML files
echo "<div class='section'>";
echo "<h3>📄 XML FILES</h3>";
$lang = $_SESSION['lang'] ?? 0;
$lang_file = ($lang == 1) ? "inc/string-th.xml" : "inc/string-us.xml";
echo "<p>Selected file: <code>$lang_file</code></p>";

if(file_exists($lang_file)){
    echo "<p class='ok'>✅ File exists</p>";
    $xml = simplexml_load_file($lang_file);
    if($xml){
        echo "<p class='ok'>✅ XML parsed successfully</p>";
        echo "<p><strong>Sample menu items:</strong></p>";
        echo "<ul>";
        echo "<li>generalinformation: <code>" . $xml->generalinformation . "</code></li>";
        echo "<li>company: <code>" . $xml->company . "</code></li>";
        echo "<li>category: <code>" . $xml->category . "</code></li>";
        echo "<li>user: <code>" . $xml->user . "</code></li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ XML parsing failed</p>";
    }
} else {
    echo "<p class='error'>❌ File not found</p>";
}
echo "</div>";

// Test buttons
echo "<div class='section'>";
echo "<h3>🧪 TEST LANGUAGE CHANGE</h3>";
echo "<p>Click a button to change language:</p>";
echo "<form action='lang.php' method='post'>";
echo "<button type='submit' name='chlang' value='0' class='test-button' style='background-color: " . ($lang == 0 ? "green; color: white;" : "lightgreen;") . "'>🇬🇧 English</button>";
echo "<button type='submit' name='chlang' value='1' class='test-button' style='background-color: " . ($lang == 1 ? "green; color: white;" : "lightgreen;") . "'>🇹🇭 Thai (ไทย)</button>";
echo "</form>";
echo "</div>";

// Show what will happen
echo "<div class='section'>";
echo "<h3>📋 HOW IT WORKS</h3>";
echo "<ol>";
echo "<li>You click a language button above</li>";
echo "<li>Form submits to <code>lang.php</code> with <code>chlang=0</code> or <code>chlang=1</code></li>";
echo "<li><code>lang.php</code> updates <code>\$_SESSION['lang']</code></li>";
echo "<li><code>lang.php</code> updates database <code>authorize</code> table</li>";
echo "<li><code>lang.php</code> redirects to <code>./index.php</code></li>";
echo "<li><code>index.php</code> loads correct XML based on <code>\$_SESSION['lang']</code></li>";
echo "<li>Menu displays in selected language</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>
