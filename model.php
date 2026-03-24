<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.database.php"); // New prepared statement helper
require_once("inc/class.company_filter.php");
$users=new DbConn($config);
$db = $users; // Alias needed by Database::getInstance()
// Security already checked in index.php

// Company filter for multi-tenant data isolation
$companyFilter = CompanyFilter::getInstance();
$companyId = $companyFilter->getSafeCompanyId();

$type_id = sql_int($_GET['q']);

// Using new database helper with prepared statement for better security
$brands = db_fetch_all(
    "SELECT brand.id, brand_name FROM brand 
     JOIN map_type_to_brand ON brand.id = map_type_to_brand.brand_id 
     WHERE type_id = ? AND brand.company_id = ?", 
    [$type_id, $companyId]
);

// Return only <option> elements (not full <select>), because the AJAX caller
// replaces innerHTML of the existing <select id="brand"> in mo-list.php
$tmp = '<option value="">-- Select Brand --</option>';
if (!empty($brands)) {
    foreach ($brands as $data) {
        $tmp .= '<option value="'.e($data['id']).'">'.e($data['brand_name']).'</option>';           
    }
} else {
    $tmp .= '<option value="">No brand for this Type</option>';
}
echo $tmp;

?>
