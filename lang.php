<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
new DbConn($config);

if(($_SESSION['user_id']!="")&&($_POST[chlang]!=$_SESSION[lang])){
	
	
$query=mysql_query("update  authorize set lang='".$_POST[chlang]."' where email='".$_SESSION['user_email']."' and id='".$_SESSION['user_id']."'");
$_SESSION[lang]=$_POST[chlang];
	
	echo "<script>window.location='index.php';</script>";

	}else{
	
	echo "<script>window.location='index.php';</script>";

}

?>