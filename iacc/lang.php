<?php 
/**
 * Language Preference Handler
 * Updates user's language preference in database
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");

$db = new DbConn($config);

// Check if user is logged in and language preference changed
if(
    isset($_SESSION['usr_id']) && !empty($_SESSION['usr_id']) &&
    isset($_POST['chlang']) && !empty($_POST['chlang'])
){
    $new_lang = intval($_POST['chlang']); // Ensure it's an integer
    $current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 0;
    
    // Only update if language changed
    if($new_lang != $current_lang){
        $user_id = intval($_SESSION['usr_id']);
        $username = $_SESSION['usr_name'];
        
        // Use prepared statement to prevent SQL injection
        $query = "UPDATE authorize SET lang = ? WHERE usr_id = ? AND usr_name = ?";
        $stmt = $db->conn->prepare($query);
        
        if($stmt){
            $stmt->bind_param('iis', $new_lang, $user_id, $username);
            if($stmt->execute()){
                // Update session variable
                $_SESSION['lang'] = $new_lang;
            }
            $stmt->close();
        }
    }
}

// Redirect back to index
header("Location: index.php");
echo "<script>window.location='index.php';</script>";
exit;
?>