<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
$users=new DbConn($config);
// Security already checked in index.php

// Company filter for multi-tenant data isolation
$companyFilter = CompanyFilter::getInstance();

// SECURITY FIX: Sanitize all user inputs to prevent SQL injection
$id = sql_escape($_GET['id'] ?? '');
$value = sql_int($_GET['value'] ?? 0);
$value2 = sql_int($_GET['value2'] ?? 0);
$mode = sql_int($_GET['mode'] ?? 0);

if(($id!="")&&($value!=0)&&($mode==1)){
$query=mysqli_query($db->conn, "select brand.id as id,brand_name from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$value."'" . $companyFilter->andCompanyFilter('brand'));
$tmp='<select name="ban_id['.$id.']" onchange="checkorder2(this.value,this.id)" id="ban_id['.$id.']"   class="form-control">';
if(mysqli_num_rows($query)==0){$tmp.='<option value="">No brand on this Type</option>';           
              }else {
				  $tmp.='<option value="">Please Select Brand</option>'; 
while($data=mysqli_fetch_array($query)){
$tmp.='<option value="'.$data[id].'">'.$data[brand_name].'</option>';           
                                
                  }}
				  $tmp.='</select>';
				  
} else if(($id!="")&&(($value!=0)||($value2!=0))&&($mode==2)){
	if($value2!=0)$condition="where type_id='".$value2."' and brand_id='".$value."'";else $condition=" where type_id='".$value."'";
$query=mysqli_query($db->conn, "select model.id as id,model_name from model ".$condition . $companyFilter->andCompanyFilter('model'));
$tmp='<select name="model['.$id.']" id="model['.$id.']"  onchange="checkorder3(this.value,this.id)" class="form-control">';
if(mysqli_num_rows($query)==0){$tmp.='<option value="">Type or Brand no model</option>';           
              }else {
				  $tmp.='<option value="">Please Select Model</option>'; 
while($data=mysqli_fetch_array($query)){
$tmp.='<option value="'.$data[id].'">'.$data[model_name].'</option>';           
                                
                  }}
				  $tmp.='</select>';
				 
				  
}else if(($id!="")&&($value!=0)&&($mode==3)){
	$querymodel=mysqli_query($db->conn, "select price,des from model where id='".$value."'" . $companyFilter->andCompanyFilter('model'));
	if(mysqli_num_rows($querymodel)){
	$datas=mysqli_fetch_array($querymodel);
	$tmp=$datas[price]."x||x".$datas[des];
	}else{ $tmp=0;}
	
	}
				 echo   $tmp;

?>
