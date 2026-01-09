<?php 
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/audit.php");
$db = new DbConn($config);

// Handle logout first
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != ""){
	// Log logout event
	audit_logout($db->conn);
	
	// Clear remember me token
	clear_remember_token($db->conn, $_SESSION['user_id']);
	session_destroy();
	echo "<script>alert('Logout Success');window.location='login.php';</script>";
	exit;
}

// Verify CSRF token for login attempts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        exit("<script>alert('Invalid request. Please try again.');window.location='login.php';</script>");
    }
    
    // Check rate limiting (IP-based)
    $rateLimit = check_rate_limit($db->conn, 5, 15);
    if ($rateLimit['limited']) {
        $minutes = ceil($rateLimit['retry_after'] / 60);
        exit("<script>alert('Too many login attempts. Please try again in {$minutes} minutes.');window.location='login.php';</script>");
    }
    
    // Sanitize input
    $user_email = sql_escape($_POST['m_user']);
    
    // Check if account is locked
    $lockStatus = is_account_locked($db->conn, $_POST['m_user']);
    if ($lockStatus['locked']) {
        $lockTime = date('H:i', strtotime($lockStatus['until']));
        exit("<script>alert('Account is locked. Try again after {$lockTime}.');window.location='login.php';</script>");
    }
    
    // Get user record (don't check password in SQL anymore)
    $query = mysqli_query($db->conn, "SELECT a.id, a.password, a.level, a.lang, a.company_id, c.name_en as company_name 
                                       FROM authorize a 
                                       LEFT JOIN company c ON a.company_id = c.id 
                                       WHERE a.email='" . $user_email . "'");

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
		    
		    // Record successful login and reset failed attempts
		    record_login_attempt($db->conn, $_POST['m_user'], true);
		    reset_failed_attempts($db->conn, $_POST['m_user']);
		    
		    $_SESSION['user_email'] = $_POST['m_user'];
		    $_SESSION['user_id'] = $tmp['id'];
		    $_SESSION['user_level'] = $tmp['level'];
		    $_SESSION['lang'] = $tmp['lang'];
		    
		    // Set company access based on user level
		    // Level 0 (User): Locked to assigned company
		    // Level 1-2 (Admin/Super Admin): Can access all companies
		    if ($tmp['level'] == 0 && !empty($tmp['company_id'])) {
		        $_SESSION['com_id'] = $tmp['company_id'];
		        $_SESSION['com_name'] = $tmp['company_name'] ?? '';
		    } else {
		        // Admins start with no company selected (can switch)
		        $_SESSION['com_id'] = '';
		        $_SESSION['com_name'] = '';
		    }
		    
		    // Handle "Remember Me"
		    if (isset($_POST['remember']) && $_POST['remember']) {
		        create_remember_token($db->conn, $tmp['id'], 30);
		    }
		    
		    // Load RBAC permissions and roles into session
		    rbac_load_permissions($db->conn, $tmp['id']);
		    rbac_load_roles($db->conn, $tmp['id']);
		    
		    // Regenerate session ID after successful login (security best practice)
		    session_regenerate_id(true);
		    
		    // Regenerate CSRF token
		    csrf_regenerate();
		    
		    // Log successful login
		    audit_login($db->conn, $tmp['id'], $_POST['m_user'], true);
		    
		    echo "<script>window.location='index.php';</script>";
		    exit;
		}
	}
	
	// Record failed login attempt
	record_login_attempt($db->conn, $_POST['m_user'], false);
	
	// Log failed login attempt
	audit_log($db->conn, 'login_failed', 'session', null, $_POST['m_user']);
	
	// Increment failed attempts and check for lockout
	$isLocked = increment_failed_attempts($db->conn, $_POST['m_user'], 10, 30);
	
	if ($isLocked) {
	    exit("<script>alert('Too many failed attempts. Account locked for 30 minutes.');window.location='login.php';</script>");
	}
	
	$remaining = $rateLimit['remaining'] - 1;
	
	if ($remaining <= 2 && $remaining > 0) {
	    exit("<script>alert('LOGIN FAIL. {$remaining} attempts remaining.');history.back();</script>");
	} else {
	    exit("<script>alert('LOGIN FAIL');history.back();</script>");
	}
}

?>