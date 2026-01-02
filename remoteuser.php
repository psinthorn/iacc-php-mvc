<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$db = new DbConn($config);
if($_SESSION['com_id']==""){
$company_id = sql_int($_GET['id']);
$sql = "select name_en,name_sh from company where id='".$company_id."'";
$comname=mysqli_fetch_array(mysqli_query($db->conn, $sql));
//$comname=mysql_fetch_array(mysql_query($db->conn, $sql));
$_SESSION['com_id']=$company_id;
$_SESSION['com_name']=$comname['name_en'];

}else{$_SESSION['com_id']="";$_SESSION['com_name']="";}
		echo "<script>window.location='index.php';</script>";
?>