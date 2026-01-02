<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
new DbConn($config);

$type = $_POST['type'];
$refer = $_POST['refer'];

$referField = "";
$dataTable = "";
$optionValueField = "";
$optionTextField = "";
$listName = "brand";
$nextType = "";
$displayName = "mySpan";
$actionEvent = "onchange";
$action = "";
$order = "";
switch($type)
{
case "0":exit(); 
case "1": $referField = "1";
				$dataTable = "type";
				$order = "name";
				$optionValueField = "id";
				$optionTextField = "name";			
				break;
case "2":$referField = "type_id";
				$dataTable = "band join map_type_to_brand on brand.id=map_type_to_brand.brand_id";
				$order = "band_name";
				$optionValueField = "id";
				$optionTextField = "band_name";
				break;
case "3":$referField = "city_id";
				$dataTable = "area";
				$order = "area_name";
				$optionValueField = "area_id";
				$optionTextField = "area_name";
				break;
default :  $referField = "";
				$dataTable = "country";
				$order = "country_name";
				$optionValueField = "country_id";
				$optionTextField = "country_name";	
				$type = "1";			
				break;
}
$listName .= $type;
$nextType = $type+1;
$sql = "SELECT * FROM $dataTable where $referField = $refer ORDER BY $order";
$result = mysql_query($sql);
if($type < 3)
$action = "$actionEvent=\"JavaScript:loadList('$nextType',this.value)\"";

echo "<select  class=\"province_check form-control\"   name=\"".$listName."\" id=\"".$listName."\" ".$action."><option selected value=\"106\">Select $dataTable</option>";
while($row = mysql_fetch_array($result))
{
echo "<option value=\"$row[$optionValueField]\">$row[$optionTextField]</option>"; 
}
echo "</select>";


?>