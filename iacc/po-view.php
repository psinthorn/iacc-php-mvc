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
				
		

?>
<div style="float:left; width:auto"><h2><i class="fa fa-shopping-cart"></i> <?=$xml->quotation?></h2></div><form action="index.php?page=qa_list" style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>


<?php  $query=mysql_query("select purchase_order.name as name,vendor_id,customer_id,vat,des,over,dis,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay,DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date,ref,pic,status from pr join purchase_order on purchase_request.id=purchase_order.ref where purchase_order.id='".$_REQUEST[id]."' and (status='1' or status='2')  and (customer_id='".$_SESSION[company_id]."' or vendor_id='".$_SESSION[company_id]."') and po_id_new=''");
if(mysql_num_rows($query)=="1"){
	$data=mysql_fetch_array($query);
	$vender=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[vendor_id]."'"));
	$customer=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[customer_id]."'"));
	
	
	?>
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
     <div id="box">
		<lable for="name"><?=$xml->validpay?></lable>
		<input readonly class="form-control" name="valid_pay" type="date" value="<?php echo $data[valid_pay];?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->deliverydate?></lable>
		<input readonly class="form-control" name="deliver_date" type="date" value="<?php echo $data[deliver_date];?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->uploadquo?></lable><?php if($data[status]=="2")echo "<a href='upload/".$data[pic]."' target='blank' class='form-control'>View QA</a>";else echo '
		<input class="form-control" name="file" id="file" type="file" >';?>
	</div>
<div class="clearfix"></div><br><table class="table" width="100%"><tr>

<?php $cklabour=mysql_fetch_array(mysql_query("select max(activelabour) as cklabour from product join type on product.type=product_type.id where purchase_order_id='".$_REQUEST[id]."'"));
if($cklabour[cklabour]==1){
?><th width="17%"><?=$xml->model?></th><th><?=$xml->product?></th>
<th style='text-align:center' width="8%"><?=$xml->unit?></th>
<th style='text-align:center' width="8%"><?=$xml->price?></th><th style='text-align:right' width="8%"><?=$xml->total?></th>
<th style='text-align:right' width="8%"><?=$xml->labour?></th>
<th style='text-align:right' width="8%"><?=$xml->total?></th>
<th style='text-align:right' width="8%"><?=$xml->amount?></th><?php }else{?><th width="17%"><?=$xml->model?></th><th colspan="4"><?=$xml->product?></th>
<th style='text-align:center' width="8%"><?=$xml->unit?></th>
<th style='text-align:center' width="8%"><?=$xml->price?></th>
<th style='text-align:right' width="8%"><?=$xml->amount?></th><?php }?></tr>
	<?php $que_pro=mysql_query("select product_type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,activelabour,valuelabour from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$_REQUEST[id]."'");$summary=0;

	while($data_pro=mysql_fetch_array($que_pro)){
		if($cklabour[cklabour]==1){
$equip=$data_pro[price]*$data_pro[quantity];
$labour1=$data_pro[valuelabour]*$data_pro[activelabour];
$labour=$labour1*$data_pro[quantity];
$total=$equip+$labour;
$summary+=$total;
echo "<tr><td>".$data_pro[model]."</td>
<td>".$data_pro[name]."</td>
<td style='text-align:center'>".$data_pro[quantity]."</td>
<td style='text-align:right'>".number_format($data_pro[price],2)."</td>
<td style='text-align:right'>".number_format($equip,2)."</td>
<td style='text-align:right'>".number_format($labour1,2)."</td>
<td style='text-align:right'>".number_format($labour,2)."</td>
<td style='text-align:right'>".number_format($total,2)."</td></tr>";}else{
$total=$data_pro[price]*$data_pro[quantity];

$summary+=$total;
echo "<tr><td>".$data_pro[model]."</td>
<td colspan='4'>".$data_pro[name]."</td>
<td style='text-align:center'>".$data_pro[quantity]."</td>
<td style='text-align:right'>".number_format($data_pro[price],2)."</td>
<td style='text-align:right'>".number_format($total,2)."</td></tr>";}
 }
	$disc=$summary*$data[dis]/100;
	$subt=$summary-$disc;
	$vat=$subt*$data[vat]/100;
	$totalnet=$subt+$vat;
	?>
  <tr><td colspan="4" rowspan="7"></td>
  <th colspan="2"><?=$xml->total?></th><td  colspan="2" align='right' ><?php echo number_format($summary,2);?></td></tr>
  <tr><th colspan="2"><?=$xml->discount?> <?php echo $data[dis];?>%</th><td colspan="2" align='right'>- <?php echo number_format($disc,2);?></td></tr>
    <tr><th colspan="2"><?=$xml->subtotal?></th><td colspan="2" align='right'><?php  echo number_format($subt,2);?></td></tr>  
     <?php if($data[over]>0){
		 $overh= $subt*$data[over]/100;
		 $subt=$subt+$overh;
	
		 ?>
      <tr>
      <th colspan="2"><?=$xml->overhead?> <?php echo $data[over];?>%</th><td colspan="2" align='right'>+ <?php echo number_format($overh,2);?></td></tr>
    <tr><th colspan="2"><?=$xml->total?></th><td colspan="2" align='right'><?php  echo number_format($subt,2);?></td></tr>  <?php }
	
	
	$vat=$subt*$data[vat]/100;
	$totalnet=$subt+$vat;
		 ?>
    
     <tr><th colspan="2"><?=$xml->vat?> <?php echo $data[vat];?>%</th><td colspan="2" align='right'>+ <?php echo number_format($vat,2);?></td></tr>
    
    <tr><th colspan="2"><?=$xml->grandtotal?></th><td colspan="2" align='right'><?php echo number_format($totalnet,2);?></td></tr>

    </table>
   
	<input type="hidden" name="method" value="C">
    <input type="hidden" name="ref" value="<?php echo $data[ref];?>">
	<input type="hidden" name="page" value="po_list">
	
	<?php if($data[status]=="1"){?><input type="submit" value="<?=$xml->confirm?>" class="btn btn-primary"><?php }?>
</form>



<?php 
}else echo "<center>ERROR</center>";?>

</body>
</html>