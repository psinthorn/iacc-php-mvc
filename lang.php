<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
new DbConn($config);

if(($_SESSION['user_id']!="")&&($_POST[chlang]!=$_SESSION[lang])){
	
	// SECURITY FIX: Sanitize user input
	$chlang = sql_escape($_POST['chlang'] ?? '');
	$user_id = sql_int($_SESSION['user_id'] ?? 0);
	$user_email = sql_escape($_SESSION['user_email'] ?? '');
	
$query=mysqli_query($db->conn, "update  authorize set lang='".$chlang."' where email='".$user_email."' and id='".$user_id."'");
$_SESSION[lang]=$_POST[chlang];
	
	echo "<script>window.location='index.php';</script>";

	}else{
	
	echo "<script>window.location='index.php';</script>";

}

?>