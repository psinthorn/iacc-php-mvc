<?php
//session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
$users->checkSecurity();
$band_id = sql_int($_REQUEST['id'] ?? 0);
?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysql_query("select * from brand where id='".$band_id."'");
if(mysql_num_rows($query)==1){
$method="E";
$data=mysql_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" enctype="multipart/form-data" id="myform">
	<div id="box">
		<lable for="band_name"><?=$xml->name?></lable>
		<input id="band_name" name="band_name" class="form-control" required type="text" value="<?php echo e($data['band_name'] ?? '');?>">
	</div>
    <div id="box">
		<lable for="des"><?=$xml->description?></lable>
		<input id="des" name="des" class="form-control" required type="text" value="<?php echo e($data['des'] ?? '');?>">
	</div>
    <div id="box">
    <lable for="des"><?=$xml->owner?></lable>
		
    <select id="ven_id" name="ven_id" class="form-control">
    
			<?php 
			echo "<option value='0' >Non Owner</option>";
			
			$querycustomer=mysql_query("select name_en,id from company where vender='1' ");
			
			
				while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($fetch_customer['id']==$data['ven_id'])
					echo "<option selected value='".intval($fetch_customer['id'])."' >".e($fetch_customer['name_en'])."</option>"; else 	echo "<option value='".intval($fetch_customer['id'])."' >".e($fetch_customer['name_en'])."</option>";
				}?>
		</select>
    
    </div>
    <div id="box"><lable for="des"><?=$xml->logo?></lable>
	<?php if(isset($data['logo']) && $data['logo']!=""){?><img width="200" src="upload/<?php echo e($data['logo']);?>"><?php } ?>
        <input id="logo" name="logo" class="form-control" type="file" value=""></div>
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="brand">
	<input type="hidden" name="id" value="<?php echo $band_id;?>"><div class="clearfix"
></div>	<div id="box" style="padding-top:25px;"><input type="submit" value="<?php if($method=="E")echo $xml->save;else echo $xml->add;?>" class="btn btn-primary"></div>
</form>

</body>
</html>