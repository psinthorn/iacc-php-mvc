<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
// $db=new DbConn($config);
// $db->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysqli_query($db->conn,"select * from type where id='".$_REQUEST['id']."'");
if(mysqli_num_rows($query)==1){
	$method="E";
	$data=mysqli_fetch_array($query);
}else $method="A";?>
	<form action="core-function.php" method="post" id="myform">
		<div id="box">
			<lable for="type_name"><?=$xml->name?></lable>
			<input id="type_name" name="type_name" class="form-control" required type="text" value="<?php echo $data['name'];?>">
		</div>
       
		<div id="box">
			<lable for="category_id"><?=$xml->category?></lable>
			<select id="category_id" name="category_id" class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select cat_name,id from category");
				
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data['category_id']==$fetch_customer['id'])$seld=" selected ";else $seld="";
					echo "<option ".$seld." value='".$fetch_customer['id']."' >".$fetch_customer['cat_name']."</option>";
				}?>
			</select>
		</div>

    	<div id="box" style="padding-top:20px;">
			<input type="submit" style="margin-top:5px;" value="<?php if($method=="E")echo $xml->save;else echo $xml->add;?>" class="btn btn-primary">
		</div>
     	<div id="box" style="width:100%;">
			<lable for="category_id"><?=$xml->description?></lable>
			<textarea name="des" class="form-control"><?php echo $data['des'];?></textarea>
		</div>
       	<div id="box" style="width:100%;"> 
			<label for="st"><?=$xml->brandonthistype?></label><br>
			<?php $query_additional=mysqli_query($db->conn, "select brand.id as id ,band.brand_name as name  from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where product_type_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "' order by band.brand_name");
			while($fet_additional=mysqli_fetch_array($query_additional)){?>
			<input type="checkbox" checked id="<?=$fet_additional['id']?>" name="<?=$fet_additional['id']?>"  class="checkbox" />
			<label style="padding:7px;cursor:pointer !important"   for="<?=$fet_additional['id']?>"><?=$fet_additional['name']?></label><?php }?>
			<?php $query_additional=mysqli_query($db->conn, "select brand.id as id ,band.brand_name as name from brand where id not in (select brand_id from map_type_to_brand where product_type_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "') order by band.brand_name");
		
			while($fet_additional=mysqli_fetch_array($query_additional)){?>
			<input type="checkbox" name="<?=$fet_additional['id']?>" id="<?=$fet_additional['id']?>" class="checkbox" />
			<label style="padding:7px;cursor:pointer !important"  for="<?=$fet_additional['id']?>"><?=$fet_additional['name]?></label><?php }?>
		</div>
		<div class="clearfix"></div> 

		<input type="hidden" name="method" value="<?php echo $method;?>">
		<input type="hidden" name="page" value="type">
		<input type="hidden" name="id" value="<?php echo $_REQUEST[id];?>">	
	</form>
</body>
</html>