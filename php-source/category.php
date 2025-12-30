<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysql_query("select * from category where id='".$_REQUEST[id]."'");
if(mysql_num_rows($query)==1){
$method="E";
$data=mysql_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" id="myform">
	<div id="box">
		<lable for="cat_name"><?=$xml->name?></lable>
		<input id="cat_name" name="cat_name" class="form-control" required type="text" value="<?php echo $data[cat_name];?>">
	</div>
    	<div id="box">
		<lable for="des"><?=$xml->description?></lable>
		<input id="des" name="des" class="form-control" required type="text" value="<?php echo $data[des];?>">
	</div>
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="category">
	<input type="hidden" name="id" value="<?php echo $_REQUEST[id];?>">
	<div id="box" style="padding-top:25px;"><input type="submit" value="<?php if($method=="E")echo $xml->edit; else echo $xml->add;?>" class="btn btn-primary"></div>
</form>

</body>
</html>