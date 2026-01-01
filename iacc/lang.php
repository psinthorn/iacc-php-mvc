<?php 
/**
 * Language Preference Handler
 * Updates user's language preference in database and session
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

$db = new DbConn($config);

// Log the request for debugging
error_log("lang.php called - POST data: " . json_encode($_POST) . " - SESSION lang: " . ($_SESSION['lang'] ?? 'not set'));

// Check if user is logged in and language preference submitted
if(
    isset($_SESSION['usr_id']) && !empty($_SESSION['usr_id']) &&
    isset($_POST['chlang'])
){
    // Get the new language value and ensure it's an integer
    $new_lang = intval($_POST['chlang']); // Converts 0 or 1
    $current_lang = isset($_SESSION['lang']) ? intval($_SESSION['lang']) : 0;
    
    error_log("Language change requested: from " . $current_lang . " to " . $new_lang);
    
    // Only update if language changed
    if($new_lang != $current_lang){
        $user_id = intval($_SESSION['usr_id']);
        $username = $_SESSION['usr_name'] ?? '';
        
        error_log("Updating database for user_id=$user_id, username=$username, new_lang=$new_lang");
        
        // Use prepared statement to prevent SQL injection
        $query = "UPDATE authorize SET lang = ? WHERE usr_id = ? AND usr_name = ?";
        $stmt = $db->conn->prepare($query);
        
        if($stmt){
            $stmt->bind_param('iis', $new_lang, $user_id, $username);
            if($stmt->execute()){
                // Update session variable immediately
                $_SESSION['lang'] = $new_lang;
                error_log("✅ Language updated: DATABASE and SESSION updated to " . $new_lang);
            } else {
                error_log("❌ Database execute failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("❌ Statement prepare failed: " . $db->conn->error);
        }
    } else {
        // Even if same language, make sure session is set
        $_SESSION['lang'] = $new_lang;
        error_log("Language already set to " . $new_lang . ", session confirmed");
    }
} else {
    error_log("❌ Not logged in or chlang not in POST");
}

// Clear any output before redirect
if(ob_get_length()) ob_end_clean();

// Redirect back to index with proper header
header("Location: index.php");
exit;
?>

