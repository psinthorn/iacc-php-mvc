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

<body><?Php 
$_date = explode("-", date("d-m-Y"));
					$day = $_date[0];
					$month = $_date[1];
					$year = $_date[2];
<div style="float:left; width:auto"><h2><i class="fa fa-truck"></i> <?=$xml->deliverynote?></h2></div><form action="index.php?page=deliv_list" style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>


<?php  


if($_REQUEST[modep]=="ad"){
	$query=mysql_query("select sendoutitem.id as id,sendoutitem.tmp as des,ven_id,cus_id,name_sh,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where deliver.id='".$_REQUEST[id]."' and (cus_id='".$_SESSION[com_id]."' or ven_id='".$_SESSION[com_id]."') and deliver.id not in (select deliver_id from receive) ");
	
	}else{$query=mysql_query("select po.name as name,po.id as id,ven_id,cus_id,des,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date,ref,pic,status from pr join po on pr.id=po.ref join deliver on po.id=deliver.po_id where deliver.id='".$_REQUEST[id]."' and status='3' and (cus_id='".$_SESSION[com_id]."' or ven_id='".$_SESSION[com_id]."') and po_id_new=''");}

if(mysql_num_rows($query)=="1"){
	$data=mysql_fetch_array($query);
	$vender=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[ven_id]."'"));
	$customer=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[cus_id]."'"));
    <div class="clearfix"></div>
<form action="core-function.php" method="post" id="company-form" enctype="multipart/form-data">

	<div id="box">
		<lable for="name"><?=$xml->name?></lable>
		<input id="name" name="name" class="form-control" readonly required value="<?php echo $data[name];?>"  type="text">
	</div>
    <div id="box">
		<lable for="name"><?=$xml->vender?></lable>
		<input class="form-control" type="text" readonly value="<?php echo $vender[name_sh];?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->customer?></lable>
		<input class="form-control" type="text" readonly value="<?php echo $customer[name_sh];?>">
	</div>
	<div id="box"  style="width:100%;" >
		<lable for="des"><?=$xml->description?></lable><textarea id="des" class="form-control" readonly rows="5"><?php echo $data[des];?></textarea>
		
	</div>
     <?php if($_REQUEST[modep]!="ad"){?>
     <div id="box">
		<lable for="name"><?=$xml->validpay?></lable>
		<input readonly class="form-control" name="valid_pay" type="text" value="<?php echo $data[valid_pay];?>">
	</div><?php }?>
     <div id="box">
		<lable for="name"><?=$xml->deliverydate?></lable>
		<input readonly class="form-control" name="deliver_date" type="text" value="<?php echo $data[deliver_date];?>">
	</div>
    <?php if($_REQUEST[modep]!="ad"){?> <div id="box">
		<lable for="name">Upload PO</lable><?php echo "<a href='upload/".$data[pic]."' target='blank' class='form-control'>View PO</a>";?>
	</div><?php } ?>
<div class="clearfix"></div><br><table class="table"><tr><tr><th width="150"><?=$xml->name?></th><th><?=$xml->model?></th><th width="150"><?=$xml->sn?></th></tr>
	  <?php if($_REQUEST[modep]=="ad"){
		   $que_pro=mysql_query("select type.name as name,product.price as price,discount,model.model_name as model,s_n from product join store on product.pro_id=store.pro_id join type on product.type=type.id join model on product.model=model.id where so_id='".$data[id]."'");
	  }else{
	 $que_pro=mysql_query("select type.name as name,product.price as price,discount,model.model_name as model,s_n from product join store on product.pro_id=store.pro_id join type on product.type=type.id join model on product.model=model.id  where po_id='".$data[id]."'");
	  }
	
	while($data_pro=mysql_fetch_array($que_pro)){
$total=$data_pro[price]-$data_pro[discount];
$summary+=$total;
echo "<tr><td>".$data_pro[name]."</td>
<td>".$data_pro[model]."</td>
<td>".$data_pro[s_n]."</td>
</tr>";
 }
    </table>
   
	<input type="hidden" name="method" value="<?php if($_REQUEST[modep]=="ad"){echo "R2";}else { echo "R";}?>">
    <input type="hidden" name="ref" value="<?php echo $data[ref];?>">
    <input type="hidden" name="po_id" value="<?php echo $data[id];?>">
    <input type="hidden" name="deliv_id" value="<?php echo $_REQUEST[id];?>">
	<input type="hidden" name="page" value="deliv_list">
    
	<input type="submit" value="<?=$xml->recieve?>" class="btn btn-primary">
</form>



<?php 
}else echo "<center>ERROR</center>";?>

</body>
</html>