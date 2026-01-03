<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
// $db=new DbConn($config);
// $db->checkSecurity();
require_once("inc/class.company_filter.php");

// Security: Sanitize ID parameter
$type_id = intval($_REQUEST['id'] ?? 0);
$companyFilter = CompanyFilter::getInstance();
?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysqli_query($db->conn,"SELECT * FROM type WHERE id='".$type_id."' " . $companyFilter->andCompanyFilter());
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
				<?php $querycustomer=mysqli_query($db->conn, "SELECT cat_name, id FROM category " . $companyFilter->whereCompanyFilter());
				
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
			<?php $query_additional=mysqli_query($db->conn, "SELECT brand.id as id, brand.brand_name as name FROM brand JOIN map_type_to_brand ON brand.id=map_type_to_brand.brand_id WHERE type_id='".$type_id."' " . $companyFilter->andCompanyFilter('brand') . " ORDER BY brand.brand_name");
			while($fet_additional=mysqli_fetch_array($query_additional)){?>
			<input type="checkbox" checked id="<?=intval($fet_additional['id'])?>" name="<?=intval($fet_additional['id'])?>"  class="checkbox" />
			<label style="padding:7px;cursor:pointer !important"   for="<?=intval($fet_additional['id'])?>"><?=htmlspecialchars($fet_additional['name'])?></label><?php }?>
			<?php $query_additional=mysqli_query($db->conn, "SELECT brand.id as id, brand.brand_name as name FROM brand WHERE id NOT IN (SELECT brand_id FROM map_type_to_brand WHERE type_id='".$type_id."') " . $companyFilter->andCompanyFilter() . " ORDER BY brand.brand_name");
		
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