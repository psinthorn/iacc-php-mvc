<?php 
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db = new DbConn($config);

if($_SESSION['usr_id']!=""){
	session_destroy();
	echo "<script>alert('Logout Success');window.location='login.php';</script>";

	}else{
	$query=mysqli_query($db->conn, "select usr_id,level,lang from authorize where usr_name='".$_POST['m_user']."' and usr_pass='".MD5($_POST['m_pass'])."'");

	if(mysqli_num_rows($query)==1){
		$tmp=mysqli_fetch_array($query);
		
		$_SESSION['usr_name']=$_POST['m_user'];
		$_SESSION['usr_id']=$tmp['usr_id'];
		$_SESSION['lang']=$tmp['lang'];
		echo "<script>window.location='../';</script>";
	}else { 
		exit("<script>alert('LOGIN FAIL');history.back();</script>");
	}
}

?>