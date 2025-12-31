<?php 
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db = new DbConn($config);

if($_SESSION['user_id']!=""){
	session_destroy();
	echo "<script>alert('Logout Success');window.location='login.php';</script>";

	}else{
	$query=mysqli_query($db->conn, "select user_id,level,lang from authorize where user_name='".$_POST['m_user']."' and user_password='".MD5($_POST['m_pass'])."'");

	if(mysqli_num_rows($query)==1){
		$tmp=mysqli_fetch_array($query);
		
		$_SESSION['user_name']=$_POST['m_user'];
		$_SESSION['user_id']=$tmp['user_id'];
		$_SESSION['lang']=$tmp['lang'];
		echo "<script>window.location='index.php?page=dashboard';</script>";
	}else { 
		exit("<script>alert('LOGIN FAIL');history.back();</script>");
	}
}

?>