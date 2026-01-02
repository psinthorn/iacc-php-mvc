<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$db = new DbConn($config);

// Only Admin and Super Admin can switch companies
$userLevel = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
if ($userLevel < 1) {
    // Normal users cannot switch companies
    echo "<script>alert('Access denied. You can only access your assigned company.');window.location='index.php';</script>";
    exit;
}

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