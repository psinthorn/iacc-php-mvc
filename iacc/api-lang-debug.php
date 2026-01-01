<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

if(!isset($_SESSION['usr_id'])){
    echo "❌ Not logged in";
    exit;
}

$db = new DbConn($config);

// Get current session language
$session_lang = $_SESSION['lang'] ?? 0;

// Get database language
$user_id = intval($_SESSION['usr_id']);
$query = "SELECT lang FROM authorize WHERE usr_id = ?";
$stmt = $db->conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$db_lang = $row['lang'] ?? 0;

// Load XML files
$lang_us_file = "inc/string-us.xml";
$lang_th_file = "inc/string-th.xml";

$xml_us = simplexml_load_file($lang_us_file);
$xml_th = simplexml_load_file($lang_th_file);

// Get current XML based on session
$current_xml = ($session_lang == 1) ? $xml_th : $xml_us;

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'session_lang' => $session_lang,
    'db_lang' => $db_lang,
    'synced' => ($session_lang == $db_lang),
    'current_lang_name' => ($session_lang == 0 ? 'English' : 'Thai'),
    'sample_menu_items' => [
        'generalinformation' => (string)$current_xml->generalinformation,
        'company' => (string)$current_xml->company,
        'category' => (string)$current_xml->category,
        'user' => (string)$current_xml->user,
    ],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
?>
