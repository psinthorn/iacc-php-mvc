<?php
//session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$db=new DbConn($config);
$db->checkSecurity();
$brand_id = sql_int($_REQUEST['id'] ?? 0);
?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysqli_query($db->conn, "select * from brand where id='".$brand_id."'");
if(mysqli_num_rows($query)==1){
$method="E";
$data=mysqli_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" enctype="multipart/form-data" id="myform">
	<div id="box">
		<lable for="brand_name"><?=$xml->name?></lable>
		<input id="brand_name" name="brand_name" class="form-control" required type="text" value="<?php echo e($data['brand_name'] ?? '');?>">
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
			
			$querycustomer=mysqli_query($db->conn, "select name_en,id from company where vender='1' ");
			
			
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
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
	<input type="hidden" name="id" value="<?php echo $brand_id;?>"><div class="clearfix"
></div>	<div id="box" style="padding-top:25px;"><input type="submit" value="<?php if($method=="E")echo $xml->save;else echo $xml->add;?>" class="btn btn-primary"></div>
</form>

</body>
</html>