<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
// $db=new DbConn($config);
// $db->checkSecurity();

// Security: Sanitize ID parameter
$type_id = intval($_REQUEST['id'] ?? 0);
?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysqli_query($db->conn,"select * from type where id='".$type_id."'");
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
			<lable for="cat_id"><?=$xml->category?></lable>
			<select id="cat_id" name="cat_id" class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select cat_name,id from category");
				
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data['cat_id']==$fetch_customer['id'])$seld=" selected ";else $seld="";
					echo "<option ".$seld." value='".intval($fetch_customer['id'])."' >".htmlspecialchars($fetch_customer['cat_name'])."</option>";
				}?>
			</select>
		</div>

    	<div id="box" style="padding-top:20px;">
			<input type="submit" style="margin-top:5px;" value="<?php if($method=="E")echo $xml->save;else echo $xml->add;?>" class="btn btn-primary">
		</div>
     	<div id="box" style="width:100%;">
			<lable for="cat_id"><?=$xml->description?></lable>
			<textarea name="des" class="form-control"><?php echo $data['des'];?></textarea>
		</div>
       	<div id="box" style="width:100%;"> 
			<label for="st"><?=$xml->brandonthistype?></label><br>
			<?php $query_additional=mysqli_query($db->conn, "select brand.id as id ,brand.brand_name as name  from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$type_id."' order by brand.brand_name");
			while($fet_additional=mysqli_fetch_array($query_additional)){?>
			<input type="checkbox" checked id="<?=intval($fet_additional['id'])?>" name="<?=intval($fet_additional['id'])?>"  class="checkbox" />
			<label style="padding:7px;cursor:pointer !important"   for="<?=intval($fet_additional['id'])?>"><?=htmlspecialchars($fet_additional['name'])?></label><?php }?>
			<?php $query_additional=mysqli_query($db->conn, "select brand.id as id ,brand.brand_name as name from brand where id not in (select brand_id from map_type_to_brand where type_id='".$type_id."') order by brand.brand_name");
		
			while($fet_additional=mysqli_fetch_array($query_additional)){?>
			<input type="checkbox" name="<?=intval($fet_additional['id'])?>" id="<?=intval($fet_additional['id'])?>" class="checkbox" />
			<label style="padding:7px;cursor:pointer !important"  for="<?=intval($fet_additional['id'])?>"><?=htmlspecialchars($fet_additional['name'])?></label><?php }?>
		</div>
		<div class="clearfix"></div> 

		<input type="hidden" name="method" value="<?php echo $method;?>">
		<input type="hidden" name="page" value="type">
		<input type="hidden" name="id" value="<?php echo $type_id;?>">	
	</form>
</body>
</html>