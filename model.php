<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
$users->checkSecurity();

$type_id = sql_int($_GET['q']);
$query=mysql_query("select brand.id as id,brand_name from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$type_id."'");
$tmp='<select name="brand"   class="form-control">';
if(mysql_num_rows($query)==0){$tmp.='<option value="">No brand on this Type</option>';           
              }else {
while($data=mysql_fetch_array($query)){
$tmp.='<option value="'.e($data['id']).'">'.e($data['brand_name']).'</option>';           
                                
                  }}
				  $tmp.='</select>';
				  echo   $tmp;

?>
