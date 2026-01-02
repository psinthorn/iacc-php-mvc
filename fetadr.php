<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
// Security already checked in index.php
$addr_id = sql_int($_REQUEST['id']);
$data=mysqli_fetch_array(mysqli_query($db->conn, "select adr_tax,city_tax,district_tax,province_tax,zip_tax from company_addr where id='".$addr_id."'"));
echo e($data['adr_tax']).";".e($data['city_tax']).";".e($data['district_tax']).";".e($data['province_tax']).";".e($data['zip_tax']);

?>
