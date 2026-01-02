<?php
//session_start();
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
$query=mysql_query("select * from brand where id='".$_REQUEST[id]."'");
if(mysql_num_rows($query)==1){
$method="E";
$data=mysql_fetch_array($query);
}else $method="A";?>
<form action="core-function.php" method="post" enctype="multipart/form-data" id="myform">
	<div id="box">
		<lable for="band_name"><?=$xml->name?></lable>
		<input id="band_name" name="band_name" class="form-control" required type="text" value="<?php echo $data[band_name];?>">
	</div>
    <div id="box">
		<lable for="des"><?=$xml->description?></lable>
		<input id="des" name="des" class="form-control" required type="text" value="<?php echo $data[des];?>">
	</div>
    <div id="box">
    <lable for="des"><?=$xml->owner?></lable>
		
    <select id="ven_id" name="ven_id" class="form-control">
    
			<?php 
			echo "<option value='0' >Non Owner</option>";
			
			$querycustomer=mysql_query("select name_en,id from company where vender='1' ");
			
			
				while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($fetch_customer[id]==$data[ven_id])
					echo "<option selected value='".$fetch_customer[id]."' >".$fetch_customer[name_en]."</option>"; else 	echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[name_en]."</option>";
				}?>
		</select>
    
    </div>
    <div id="box"><lable for="des"><?=$xml->logo?></lable>
	<?php if($data[logo]!=""){?><img width="200" src="upload/<?php echo $data[logo];?>"><?php } ?>
        <input id="logo" name="logo" class="form-control" type="file" value=""></div>
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="brand">
	<input type="hidden" name="id" value="<?php echo $_REQUEST[id];?>"><div class="clearfix"
></div>	<div id="box" style="padding-top:25px;"><input type="submit" value="<?php if($method=="E")echo $xml->save;else echo $xml->add;?>" class="btn btn-primary"></div>
</form>

</body>
</html>