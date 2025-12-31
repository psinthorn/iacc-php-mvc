<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();
$data=mysql_fetch_array(mysql_query("select address_tax,city_tax,district_tax,province_tax,zip_tax from company_addr where id='".$_REQUEST[id]."'"));
				 echo   $data[address_tax].";".$data[city_tax].";".$data[district_tax].";".$data[province_tax].";".$data[zip_tax];

?>
