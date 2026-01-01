<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
new DbConn($config);

if(($_SESSION['usr_id']!="")&&($_POST[chlang]!=$_SESSION[lang])){
	
	
$query=mysql_query("update  authorize set lang='".$_POST[chlang]."' where usr_name='".$_SESSION['usr_name']."' and usr_id='".$_SESSION['usr_id']."'");
$_SESSION[lang]=$_POST[chlang];
	
	echo "<script>window.location='index.php';</script>";

	}else{
	
	echo "<script>window.location='index.php';</script>";

}

?>