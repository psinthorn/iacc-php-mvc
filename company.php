<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
// $users=new DbConn($config);
// // Security already checked in index.php?>
<!DOCTYPE html>
<html>

<head>

</head>

<body><h2><i class="fa fa-briefcase"></i> <?=$xml->information?></h2>
<?php
$id = sql_int($_REQUEST['id']);
$query=mysqli_query($db->conn, "select * from company where id='".$id."'");
if(mysqli_num_rows($query)==1){
$method="E";
$data=mysqli_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" enctype="multipart/form-data" id="company-form">
	<div id="box">
		<lable for="name_en"><?=$xml->nameen?></lable>
		<input id="name_en" name="name_en" class="form-control" required  type="text" value="<?php echo $data['name_en'];?>">
	</div>
	<div id="box">
		<lable for="name_th"><?=$xml->nameth?></lable>
		<input id="name_th" name="name_th" class="form-control" required type="text" value="<?php echo $data['name_th'];?>">
	</div>
	<div id="box">
		<lable for="name_sh"><?=$xml->namesh?></lable>
		<input id="name_sh" name="name_sh" class="form-control" required type="text" value="<?php echo $data['name_sh'];?>">
	</div>
	<div id="box">
		<lable for="contact"><?=$xml->contact?></lable>
		<input id="contact" name="contact" class="form-control" required type="text" value="<?php echo $data['contact'];?>">
	</div>
	<div id="box">
		<lable for="email"><?=$xml->email?></lable>
		<input id="email" name="email" class="form-control" required type="email" value="<?php echo $data['email'];?>">
	</div>
	<div id="box">
		<lable for="phone"><?=$xml->phone?></lable>
		<input id="phone" name="phone" class="form-control" required type="text" value="<?php echo $data['phone'];?>">
	</div>
	<div id="box">
		<lable for="fax"><?=$xml->fax?></lable>
		<input id="fax" name="fax" class="form-control" required type="text" value="<?php echo $data['fax'];?>">
	</div>
	<div id="box">
		<lable for="tax"><?=$xml->tax?></lable>
		<input id="phone" name="tax" class="form-control" required type="number" value="<?php echo $data['tax'];?>">
	</div>
    
    <div id="box">
		<lable for="logo"><?=$xml->logo?></lable>
		<?php if($data['logo']!=""){?><img width="200" src="upload/<?php echo $data['logo'];?>"><?php } ?>
        <input id="logo" name="logo" class="form-control" type="file" value="">
	</div>
    
    <div id="box" style="width:100%;">
		<lable for="Term & Condition"><?=$xml->term?></lable>
		<textarea id="term" name="term" class="form-control"><?php echo $data['term'];?></textarea>
	</div><div class="clearfix"></div>

    <?php if($method!="E"){?>
        <div style="width:100%;"><h2><?=$xml->registeraddress?></h2></div>
    
    <div id="box">
		<lable for="adr_tax"><?=$xml->raddress?><</lable>
		<input id="adr_tax" name="adr_tax" class="form-control" type="text" value="<?php echo $data['adr_tax'];?>">
	</div>
	<div id="box">
		<lable for="city_tax"><?=$xml->rcity?>/lable>
		<input id="city_tax" name="city_tax" class="form-control" type="text" value="<?php echo $data['city_tax'];?>">
	</div>
	<div id="box">
		<lable for="district_tax"><?=$xml->rdistrict?></lable>
		<input id="district_tax" name="district_tax" class="form-control" type="text" value="<?php echo $data['district_tax'];?>">
	</div>
	<div id="box">
		<lable for="province_tax"><?=$xml->rprovince?></lable>
		<input id="province_tax" name="province_tax" class="form-control" type="text" value="<?php echo $data['province_tax'];?>">
	</div>
	<div id="box">
		<lable for="zip_tax"><?=$xml->rzip?></lable>
		<input id="zip_tax" name="zip_tax" class="form-control" type="text" value="<?php echo $data['zip_tax'];?>">
	</div>
    
  
    <div class="clearfix"></div>
      <div style="width:100%;"><h2><?=$xml->exitingaddress?></h2></div>
  
	<div id="box">
		<lable for="adr_tax"><?=$xml->baddress?></lable>
		<input id="adr_bil" name="adr_bil" placeholder="<?=$xml->nullforsave?>" class="form-control" type="text" value="<?php echo $data['adr_bil'];?>">
	</div>
	<div id="box">
		<lable for="city_bil"><?=$xml->bcity?></lable>
		<input id="city_bil" name="city_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo $data['city_bil'];?>">
	</div>
	<div id="box">
		<lable for="district_bil"><?=$xml->bdistrict?></lable>
		<input id="district_bil" name="district_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo $data['district_bil'];?>">
	</div>
	<div id="box">
		<lable for="province_bil"><?=$xml->bprovince?></lable>
		<input id="province_bil" name="province_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo $data['province_bil'];?>">
	</div>
	<div id="box">
		<lable for="zip_bil"><?=$xml->bzip?></lable>
		<input id="zip_bil" name="zip_bil" class="form-control" type="text" placeholder="<?=$xml->nullforsave?>" value="<?php echo $data['zip_bil'];?>">
	</div>
    	
	
	<?php }?>
    
    
    <div class="clearfix"></div>
    
	<div id="box" style="padding-top:20px;">
		<input type="checkbox" name="customer" <?php if($data['customer']=="1")echo "checked"; ?> value="1"> <?=$xml->customer?> &nbsp;
		<input type="checkbox" name="vender" <?php if($data['vender']=="1")echo "checked"; ?> value="1"> <?=$xml->vender?> &nbsp;
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="company">
	<input type="hidden" name="id" value="<?php echo $_REQUEST['id'];?>">
	<input type="submit" value="<?php if($method=="E")echo $xml->save; else echo $xml->add?>" class="btn btn-primary"></div>
</form>
<div class="clearfix" style="margin-bottom:40px;"></div>
</body>
</html>