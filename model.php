<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.database.php"); // New prepared statement helper
$users=new DbConn($config);
// Security already checked in index.php

$type_id = sql_int($_GET['q']);

// Using new database helper with prepared statement for better security
$brands = db_fetch_all(
    "SELECT brand.id, brand_name FROM brand 
     JOIN map_type_to_brand ON brand.id = map_type_to_brand.brand_id 
     WHERE type_id = ?", 
    [$type_id]
);

$tmp = '<select name="brand" class="form-control">';
if (empty($brands)) {
    $tmp .= '<option value="">No brand on this Type</option>';           
} else {
    foreach ($brands as $data) {
        $tmp .= '<option value="'.e($data['id']).'">'.e($data['brand_name']).'</option>';           
    }
}
$tmp .= '</select>';
echo $tmp;

?>
