<?php 
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$db = new DbConn($config);

// Handle logout first
if($_SESSION['usr_id']!=""){
	session_destroy();
	echo "<script>alert('Logout Success');window.location='login.php';</script>";
	exit;
}

// Verify CSRF token for login attempts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        exit("<script>alert('Invalid request. Please try again.');window.location='login.php';</script>");
    }
    
    // Sanitize input
    $user_email = sql_escape($_POST['m_user']);
    $user_pass = MD5($_POST['m_pass']);
    
    $query = mysqli_query($db->conn, "SELECT usr_id, level, lang FROM authorize WHERE usr_name='" . $user_email . "' AND usr_pass='" . $user_pass . "'");

	if(mysqli_num_rows($query)==1){
		$tmp=mysqli_fetch_array($query);
		
		$_SESSION['usr_name']=$_POST['m_user'];
		$_SESSION['usr_id']=$tmp['usr_id'];
		$_SESSION['lang']=$tmp['lang'];
		
		// Regenerate session ID after successful login (security best practice)
		session_regenerate_id(true);
		
		// Regenerate CSRF token
		csrf_regenerate();
		
		echo "<script>window.location='index.php';</script>";
	} else { 
		exit("<script>alert('LOGIN FAIL');history.back();</script>");
	}
}

?>