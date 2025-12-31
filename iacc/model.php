<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();
$query=mysql_query("select brand.id as id,band_name from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where product_type_id='".$_GET['q']."'");
$tmp='<select name="brand"   class="form-control">';
if(mysql_num_rows($query)==0){$tmp.='<option value="">No brand on this Type</option>';           
              }else {
while($data=mysql_fetch_array($query)){
$tmp.='<option value="'.$data[id].'">'.$data[band_name].'</option>';           
                                
                  }}
				  $tmp.='</select>';
				  echo   $tmp;

?>
