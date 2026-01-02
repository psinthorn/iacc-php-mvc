<?php 
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$db = new DbConn($config);

// Handle logout first
if($_SESSION['user_id']!=""){
	session_destroy();
	echo "<script>alert('Logout Success');window.location='login.php';</script>";
	exit;
}

// Verify CSRF token for login attempts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        exit("<script>alert('Invalid request. Please try again.');window.location='login.php';</script>");
    }
    
    // Check rate limiting
    $rateLimit = check_rate_limit($db->conn, 5, 15);
    if ($rateLimit['limited']) {
        $minutes = ceil($rateLimit['retry_after'] / 60);
        exit("<script>alert('Too many login attempts. Please try again in {$minutes} minutes.');window.location='login.php';</script>");
    }
    
    // Sanitize input
    $user_email = sql_escape($_POST['m_user']);
    
    // Get user record (don't check password in SQL anymore)
    $query = mysqli_query($db->conn, "SELECT id, password, level, lang FROM authorize WHERE email='" . $user_email . "'");

	if(mysqli_num_rows($query)==1){
		$tmp = mysqli_fetch_array($query);
		$needsRehash = false;
		
		// Verify password (supports both MD5 and bcrypt)
		if (password_verify_secure($_POST['m_pass'], $tmp['password'], $needsRehash)) {
		    
		    // Migrate password to bcrypt if needed
		    if ($needsRehash) {
		        $newHash = password_hash_secure($_POST['m_pass']);
		        password_migrate($db->conn, $tmp['id'], $newHash, 'id');
		    }
		    
		    // Record successful login
		    record_login_attempt($db->conn, $_POST['m_user'], true);
		    
		    $_SESSION['user_email'] = $_POST['m_user'];
		    $_SESSION['user_id'] = $tmp['id'];
		    $_SESSION['lang'] = $tmp['lang'];
		    
		    // Regenerate session ID after successful login (security best practice)
		    session_regenerate_id(true);
		    
		    // Regenerate CSRF token
		    csrf_regenerate();
		    
		    echo "<script>window.location='index.php';</script>";
		    exit;
		}
	}
	
	// Record failed login attempt
	record_login_attempt($db->conn, $_POST['m_user'], false);
	$remaining = $rateLimit['remaining'] - 1;
	
	if ($remaining <= 2 && $remaining > 0) {
	    exit("<script>alert('LOGIN FAIL. {$remaining} attempts remaining.');history.back();</script>");
	} else {
	    exit("<script>alert('LOGIN FAIL');history.back();</script>");
	}
}

?>