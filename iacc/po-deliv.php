<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>

<!--<script language="JavaScript" type="text/javascript">
function checkBooking() {
	var frm = document.deliver-form;



	if (frm.first.value == "" ){
			alert("Please check your First Name");frm.first.focus();
return false;
	}
	
	}-->

</script>

</head>

<body><?Php 
$_date = explode("-", date("d-m-Y"));
					$day = $_date[0];
					$month = $_date[1];
					$year = $_date[2];
				
		

?>
<div style="float:left; width:auto"><h2><i class="fa fa fa-truck"></i> <?php if($_GET[action]=="m")echo $xml->make." ".$xml->deliverynote; else echo $xml->create." ".$xml->deliverynote?></h2></div><form action="index.php?page=po_list"  style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>


<?php  $query=mysql_query("select purchase_order.name as name,vendor_id,customer_id,des,DATE_FORMAT(deliver_date,'%d-%m-%Y') as valid_pay,DATE_FORMAT(valid_pay,'%d-%m-%Y') as deliver_date,ref,pic,status from pr join purchase_order on purchase_request.id=purchase_order.ref where purchase_order.id='".$_REQUEST[id]."' and (status='1' or status='2')  and vendor_id='".$_SESSION[company_id]."' and po_id_new=''");
if(mysql_num_rows($query)=="1"){
	$data=mysql_fetch_array($query);
	$vender=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[vendor_id]."'"));
	$customer=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[customer_id]."'"));
	
	
	?>
    <div class="clearfix"></div>
<form action="core-function.php" method="post" id="deliver-form" name="deliver-form" enctype="multipart/form-data">

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
     <div id="box">
		<lable for="name"><?=$xml->validpay?></lable>
		<input readonly class="form-control" name="valid_pay" type="text" value="<?php echo $data[valid_pay];?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->deliverydate?></lable>
		<input required  class="form-control" name="deliver_date" type="text" value="<?php echo $data[deliver_date];?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->payby?></lable>
        <select class="form-control"  name="payby"  id="payby">
        <?php $query_cus=mysql_query("select name_en,id from company where customer='1'");
		
		
		while($fetch_cus=mysql_fetch_array($query_cus)){
					if($fetch_cus[id]==$data[customer_id])
					echo "<option selected value='".$fetch_cus[id]."' >".$fetch_cus[name_en]."</option>"; else 	echo "<option value='".$fetch_cus[id]."' >".$fetch_cus[name_en]."</option>";
				}?>
</select>
	</div>
<div class="clearfix"></div><br><table class="table"><tr><tr><th width="250"><?=$xml->name?></th><th><?=$xml->sn?></th><th width="150"><?=$xml->warranty?></th></tr>
	<?php $que_pro=mysql_query("select product_type.name as name,product.des as des,product.price as price,product_id,discount,model.model_name as model,quantity,pack_quantity,type from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$_REQUEST[id]."'");

$j=0;
	while($data_pro=mysql_fetch_array($que_pro)){
$item=$data_pro[quantity]*$data_pro[pack_quantity];
for($i=0;$i<$item;$i++){
echo "<tr><td>".$data_pro[name]."<br>(".$data_pro[model].")</td>
<td>";
if($_GET[action]=="m"){ echo "
<select required class='form-control' name='sn[".$j."]'><option value='' >-------Please Select Item------</option>";

$query_store=mysql_query("select store.id as store_id, product_type.name as name, s_n from store join product on store.product_id=product.product_id join store_sale on store.id=store_sale.store_id join type on product.type=product_type.id where owner_id='".$_SESSION[company_id]."' and type='".$data_pro[type]."' and sale='0'");
$countpro=mysql_num_rows($query_store);

$tmpstore="";
while($data_store=mysql_fetch_array($query_store)){
	echo "<option value='".$data_store[store_id]."'>".$data_store[name]."(".$data_store[s_n].")</option>";
	}

echo "
</select>
</td></tr>";}else{ 

$maxno=mysql_fetch_array(mysql_query("select max(no) as maxno from store join product on store.product_id=product.product_id where model in (select model from product where product_id='".$data_pro[product_id]."')"));

echo "
<input  class='form-control' name='sn[".$j."]' value='".$data_pro[model]."-".($maxno[maxno]+1)."' type='text'>".$data_pro[des]."</td>";}
echo "<td><input class='form-control' placeholder='dd-mm-yyyy' name='exp[".$j."]'  type='text'><input type='hidden' name='product_id[".$j."]' value='".$data_pro[product_id]."'></td>
</tr>";
$j++;
}
 }
	
	?>
 
    
    </table>
   
	<input type="hidden"  name="method" value="<?php echo $_GET[action];?>">
    <input type="hidden" name="ref" value="<?php echo $data[ref];?>">
	<input type="hidden" name="page" value="deliv_list">
    <input type="hidden" name="purchase_order_id" value="<?php echo $_REQUEST[id];?>">
    <input type="hidden" name="customer_id" value="<?php echo $data[customer_id];?>">
    
	
	<?php if($data[status]=="2"){?><input type="submit" value="<?=$xml->save;?>" class="btn btn-primary"><?php }?>
</form>



<?php 
}else echo "<center>ERROR</center>";?>

</body>
</html>