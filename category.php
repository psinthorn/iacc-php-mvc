<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
// Security already checked in index.php
$cat_id = sql_int($_REQUEST['id'] ?? 0);
?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysqli_query($db->conn, "select * from category where id='".$cat_id."'");
if(mysqli_num_rows($query)==1){
$method="E";
$data=mysqli_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" id="myform">
	<div id="box">
		<lable for="cat_name"><?=$xml->name?></lable>
		<input id="cat_name" name="cat_name" class="form-control" required type="text" value="<?php echo e($data['cat_name'] ?? '');?>">
	</div>
    	<div id="box">
		<lable for="des"><?=$xml->description?></lable>
		<input id="des" name="des" class="form-control" required type="text" value="<?php echo e($data['des'] ?? '');?>">
	</div>
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="category">
	<input type="hidden" name="id" value="<?php echo $cat_id;?>">
	<div id="box" style="padding-top:25px;"><input type="submit" value="<?php if($method=="E")echo $xml->edit; else echo $xml->add;?>" class="btn btn-primary"></div>
</form>

</body>
</html>