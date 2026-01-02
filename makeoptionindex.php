<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
$users->checkSecurity();

if(($_GET[id]!="")&&($_GET[value]!="")&&($_GET[mode]=="1")){
$query=mysql_query("select brand.id as id,band_name from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$_GET['value']."'");
$tmp='<select name="ban_id['.$_GET['id'].']" onchange="checkorder2(this.value,this.id)" id="ban_id['.$_GET['id'].']"   class="form-control">';
if(mysql_num_rows($query)==0){$tmp.='<option value="">No brand on this Type</option>';           
              }else {
				  $tmp.='<option value="">Please Select Brand</option>'; 
while($data=mysql_fetch_array($query)){
$tmp.='<option value="'.$data[id].'">'.$data[band_name].'</option>';           
                                
                  }}
				  $tmp.='</select>';
				  
} else if(($_GET[id]!="")&&(($_GET[value]!="")||($_GET[value2]!=""))&&($_GET[mode]=="2")){
	if($_GET[value2]!="")$condition="where type_id='".$_GET['value2']."' and brand_id='".$_GET['value']."'";else $condition=" where type_id='".$_GET['value']."'";
$query=mysql_query("select model.id as id,model_name from model ".$condition);
$tmp='<select name="model['.$_GET['id'].']" id="model['.$_GET['id'].']"  onchange="checkorder3(this.value,this.id)" class="form-control">';
if(mysql_num_rows($query)==0){$tmp.='<option value="">Type or Brand no model</option>';           
              }else {
				  $tmp.='<option value="">Please Select Model</option>'; 
while($data=mysql_fetch_array($query)){
$tmp.='<option value="'.$data[id].'">'.$data[model_name].'</option>';           
                                
                  }}
				  $tmp.='</select>';
				 
				  
}else if(($_GET[id]!="")&&($_GET[value]!="")&&($_GET[mode]=="3")){
	$querymodel=mysql_query("select price,des from model where id='".$_GET[value]."'");
	if(mysql_num_rows($querymodel)){
	$datas=mysql_fetch_array($querymodel);
	$tmp=$datas[price]."x||x".$datas[des];
	}else{ $tmp=0;}
	
	}
				 echo   $tmp;

?>
