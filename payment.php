<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
// Security already checked in index.php

// Get current company for multi-tenant isolation
$com_id = sql_int($_SESSION['com_id']);
?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$id = sql_int($_REQUEST['id']);
// SECURITY FIX: Add company_id filter to prevent cross-tenant data access
$query=mysqli_query($db->conn, "select * from payment where id='".$id."' AND com_id='".$com_id."'");
if(mysqli_num_rows($query)==1){
$method="E";
$data=mysqli_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" id="myform">
	<div id="box">
		<lable for="payment_name"><?=$xml->name?></lable>
		<input id="payment_name" name="payment_name" class="form-control" required type="text" value="<?php echo $data[payment_name];?>">
	</div>
    	<div id="box">
		<lable for="payment_des"><?=$xml->description?></lable>
		<input id="payment_des" name="payment_des" class="form-control" required type="text" value="<?php echo $data[payment_des];?>">
	</div>
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="payment">
	<input type="hidden" name="id" value="<?php echo $id;?>">
	<div id="box" style="padding-top:20px;"><input type="submit" value="<?php if($method=="E")echo $xml->edit;else echo $xml->add;?>" class="btn btn-primary"></div>
</form>

</body>
</html>