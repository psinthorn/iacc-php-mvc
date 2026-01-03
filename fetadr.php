<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.database.php"); // New prepared statement helper
$users=new DbConn($config);
// Security already checked in index.php
$addr_id = sql_int($_REQUEST['id']);

// Using new database helper with prepared statement
$data = db_fetch_one(
    "SELECT adr_tax, city_tax, district_tax, province_tax, zip_tax 
     FROM company_addr WHERE id = ?", 
    [$addr_id]
);

if ($data) {
    echo e($data['adr_tax']).";".e($data['city_tax']).";".e($data['district_tax']).";".e($data['province_tax']).";".e($data['zip_tax']);
}

?>
