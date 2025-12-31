<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>

<script language="JavaScript" type="text/javascript">

function paymentcheck() {
	
	
	if (parseFloat(document.getElementById("volumn").value) > parseFloat(document.getElementById("total").value) ){
		alert("Please check Volumn");
		document.getElementById("volumn").focus();
		return false;
	}else{	
		return true;	
	}
}

</script>
</head>

<body><?Php 
$_date = explode("-", date("d-m-Y"));
					$day = $_date[0];
					$month = $_date[1];
					$year = $_date[2];
				
		

?>
<div style="float:left; width:auto"><h2><i class="fa fa-thumbs-up"></i> <?=$xml->invoice?></h2></div><form action="index.php?page=compl_list" style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>


<?php  $query=mysql_query("select purchase_order.name as name,vendor_id, vat,customer_id,des,payby,over, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay,dis, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date,ref,pic,status from pr join purchase_order on purchase_request.id=purchase_order.ref where purchase_order.id='".$_REQUEST[id]."' and status='4'  and (customer_id='".$_SESSION[company_id]."' or vendor_id='".$_SESSION[company_id]."') and po_id_new=''");
if(mysql_num_rows($query)=="1"){
	$data=mysql_fetch_array($query);
	$vender=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[vendor_id]."'"));
	$customer=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[customer_id]."'"));
	
	
	?>
    <div class="clearfix"></div>
<form action="core-function.php"  method="post" id="company-form" enctype="multipart/form-data">

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
		<input readonly class="form-control" name="deliver_date" type="text" value="<?php echo $data[deliver_date];?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->payby?></lable><select class="form-control"  name="payby"  id="payby">
        <?php $query_cus=mysql_query("select name_en,id from company where customer='1'");
		
		
		while($fetch_cus=mysql_fetch_array($query_cus)){
					if($fetch_cus[id]==$data[payby])
					echo "<option selected value='".$fetch_cus[id]."' >".$fetch_cus[name_en]."</option>"; else 	echo "<option value='".$fetch_cus[id]."' >".$fetch_cus[name_en]."</option>";
				}?>
</select>
	</div><button type="submit" name="method" class="btn btn-primary" value="S">Save</button>
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
	$totalnet=0;
	$totalnet=$subt+$vat;
	?>
  <tr><td colspan="4" rowspan="5"></td>
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

<?php $querypay=mysql_query("select DATE_FORMAT(date,'%d-%m-%Y') as date,value,id,volumn from pay where purchase_order_id='".$_REQUEST[id]."'");
while($datapays=mysql_fetch_array($querypay)){?>
<tr><th colspan="6"><?=$xml->pay?> (<?=$datapays['date']?>)<?=$datapays[value]?> <a href="sptinv.php?id=<?=$datapays['id']?>" target="_blank" style="color:#ff0000"><?=$xml->print?></a></th><td colspan="2" align='right'><?=number_format($datapays[volumn],2);?></td></tr>
<?php } ?>
    </table>
    <input type="hidden" name="ref" value="<?php echo $data[ref];?>">
    <input type="hidden" name="id" value="<?=$_REQUEST[id]?>">
	<input type="hidden" name="page" value="compl_view">
	
</form>
<hr>

<?php 


$stotals=mysql_fetch_array(mysql_query("select sum(volumn) as stotal from pay where purchase_order_id='".$_REQUEST[id]."'"));
$accu=$totalnet-$stotals[stotal];
if($accu<0.000000000001)$accu=0;
if($accu!=0){
	
	if($data[vendor_id]==$_SESSION[company_id]){?>
<form action="core-function.php" method="post" id="payment-form" name="payment-form" enctype="multipart/form-data" onSubmit="return paymentcheck();">

	<div id="box">
		<lable for="payment"><?=$xml->method?></lable>
		<select id="payment" name="payment" class="form-control">
    
			<?php 
			
			$querycustomer=mysql_query("select payment_name,id from payment where company_id='".$_SESSION[company_id]."' ");
			
			
				while($fetch_customer=mysql_fetch_array($querycustomer)){
					echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[payment_name]."</option>";
				}?>
		</select>
	</div>
    	<div id="box">
		<lable for="remark"><?=$xml->notes?></lable>
		<input id="remark" name="remark" class="form-control" placeholder="<?=$xml->notes?>"  type="text">
	</div>
    	<div id="box">
		<lable for="volumn" style="width:100%; float:left"><?=$xml->volume?></lable>
		<input id="volumn" name="volumn" style="width:70%; float:left; " class="form-control"  required value="<?=$accu?>"  type="text">
        <input type="hidden" name="total" id="total" value="<?php echo $accu?>">
        <input type="submit" value="<?=$xml->pay?>" class="btn btn-primary" style="width:28%; float:left; margin-left:2%;">
	</div>
    
    	<input type="hidden" name="method" value="C">
	<input type="hidden" name="page" value="compl_list">
	<input type="hidden" name="purchase_order_id" value="<?php echo $_REQUEST[id];?>">

    </form>
	
	
	<form action="core-function.php" method="post" id="payment-form" name="payment-form" enctype="multipart/form-data" onSubmit="return paymentcheck();">
        
         <div id="box" style="float:right; text-align:right;"> <button type="submit" id="method" name="method" value="V" class="btn btn-danger"><?=$xml->voidinv?></button>
         
        
	<input type="hidden" name="page" value="compl_list2">
    <?php $refpo=mysql_fetch_array(mysql_query("select ref from purchase_order where id='".$_REQUEST[id]."'"));?>
	<input type="hidden" name="id" value="<?php echo $refpo[ref];?>">
	</div></form>
	
	<?php } }else{
		?><form action="core-function.php" method="post" id="payment-form" name="payment-form" enctype="multipart/form-data" onSubmit="return paymentcheck();">
        
         <div id="box" style="float:right; text-align:right;"> <button type="submit" id="method" name="method"  value="V" class="btn btn-danger"><?=$xml->voidinv?></button> <button type="submit" id="method" name="method" value="C" class="btn btn-success"><?=$xml->taxinvoicem?></button>
         
   
	<input type="hidden" name="page" value="compl_list2">
    <?php $refpo=mysql_fetch_array(mysql_query("select ref from purchase_order where id='".$_REQUEST[id]."'"));?>
	<input type="hidden" name="id" value="<?php echo $refpo[ref];?>">
	</div></form><?php } 
		 ?>
    
    </div><?php
		
}else echo "<center>ERROR</center>";?>

</body>
</html>