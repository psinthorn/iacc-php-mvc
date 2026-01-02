<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
$users->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
<script language="javascript">
function copytextbox()
{
alert('Hello ThaiCreate.Com');
}
</script>
</head>

<body>
<body><h2><i class="fa fa-truck"></i> <?=$xml->address?></h2>
<?php
$id = sql_int($_REQUEST['id']);
$query=mysql_query("select * from company_addr where com_id='".$id."' and valid_end='0000-00-00'");
if(mysql_num_rows($query)=="1"){
$method="A2";
$data=mysql_fetch_array($query);?>
<form action="core-function.php" methord="post" id="myform">
	<div id="box">
		<lable for="adr_tax"><?=$xml->raddress?></lable>
		<input id="adr_tax" name="adr_tax" class="form-control" type="text" value="<?php echo $data[adr_tax];?>">
	</div>
	<div id="box">
		<lable for="city_tax"><?=$xml->rdistrict?></lable>
		<input id="city_tax" name="city_tax" class="form-control" type="text" value="<?php echo $data[city_tax];?>">
	</div>
	<div id="box">
		<lable for="district_tax"><?=$xml->rcity?></lable>
		<input id="district_tax" name="district_tax" class="form-control" type="text" value="<?php echo $data[district_tax];?>">
	</div>
	<div id="box">
		<lable for="province_tax"><?=$xml->rprovince?></lable>
		<input id="province_tax" name="province_tax" class="form-control" type="text" value="<?php echo $data[province_tax];?>">
	</div>
	<div id="box">
		<lable for="zip_tax"><?=$xml->rzip?></lable>
		<input id="zip_tax" name="zip_tax" class="form-control" type="text" value="<?php echo $data[zip_tax];?>">
	</div>
  
    
    <div class="clearfix"></div>
	<div id="box">
		<lable for="adr_tax"><?=$xml->baddress?></lable>
		<input id="adr_bil" name="adr_bil" class="form-control" type="text"  value="<?php echo $data[adr_bil];?>">
	</div>
	<div id="box">
		<lable for="city_bil"><?=$xml->bdistrict?></lable>
		<input id="city_bil" name="city_bil" class="form-control" type="text" value="<?php echo $data[city_bil];?>">
	</div>
	<div id="box">
		<lable for="district_bil"><?=$xml->bcity?></lable>
		<input id="district_bil" name="district_bil" class="form-control" type="text" value="<?php echo $data[district_bil];?>">
	</div>
	<div id="box">
		<lable for="province_bil"><?=$xml->bprovince?></lable>
		<input id="province_bil" name="province_bil" class="form-control" type="text" value="<?php echo $data[province_bil];?>">
	</div>
	<div id="box">
		<lable for="zip_bil"><?=$xml->bzip?></lable>
		<input id="zip_bil" name="zip_bil" class="form-control" type="text" value="<?php echo $data[zip_bil];?>">
	</div>
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="company">
    <input type="hidden" name="com_id" value="<?php echo $data[com_id];?>">
	<input type="hidden" name="id" value="<?php echo $id;?>">
	<div id="box" style="padding-top:25px;">
	<input type="submit" value="<?=$xml->save?>" class="btn btn-primary"></div>
</form><?php 
}else echo "<center>ERROR</center>";?>


</body>
</html>