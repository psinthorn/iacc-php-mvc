<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.current.php");
$users=new DbConn($config);
$users->checkSecurity();


?>	
	<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <script type="text/javascript">
  
  $('body').on('hidden.bs.modal', '.modal', function () {
  $(this).removeData('bs.modal');
});


</script>
</head>
<body><?php
 switch($_REQUEST[page]){
	case "exp" : {
		
 $fetmail=mysql_fetch_array(mysql_query("select email,purchase_order.tax as tax,vat,dis,over,name_sh,name_en from pr join purchase_order on purchase_request.id=purchase_order.ref join company on purchase_request.customer_id=company.id where purchase_order.id='".$_REQUEST[id]."' and status>'0' and vendor_id='".$_SESSION[company_id]."' and po_id_new=''"));
  $que_pro=mysql_query("select product.des as des,product_type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$_REQUEST[id]."'");
	 $summary=$total=0;
	 while($data_pro=mysql_fetch_array($que_pro)){

$equip=$data_pro[price]*$data_pro[quantity];
$labour1=$data_pro[valuelabour]*$data_pro[activelabour];
$labour=$labour1*$data_pro[quantity];
$total=$equip+$labour;
	 $summary+=$total;

}
	 
 $disco=$summary*$fetmail[dis]/100;
 $stotal=$summary-$disco;
 	$overh=$stotal*$fetmail[over]/100;
	$stotal=$stotal+$overh;
	 $vat=$stotal*$fetmail[vat]/100;
 $total=$stotal+$vat;
	 
 
 $vender=mysql_fetch_array(mysql_query("select name_en from company where id='".$_SESSION[company_id]."'"));
 $subject='QUO-'.$fetmail[tax]."-".$fetmail[name_sh];
 $page="exp-m.php";
 $message = "Dear ".$fetmail[name_en]."
  
Your quotation is attached.
Your price for this Quotation is ".number_format($total,2)." Baht.
If a price is due please confirm quotations

Thank you for your business, we appreciate it very much.
".$vender[name_en];
 }break;
 case "inv" : {
		
 $fetmail=mysql_fetch_array(mysql_query("select email,name_en,vat,dis,over,name_sh, taxrw as tax2 from pr join purchase_order on purchase_request.id=purchase_order.ref  join invoice on purchase_order.id=invoice.tex join company on purchase_request.customer_id=company.id where purchase_order.id='".$_REQUEST[id]."' and status>'2' and  vendor_id='".$_SESSION[company_id]."' and po_id_new=''"));
  $que_pro=mysql_query("select product.des as des,product_type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$_REQUEST[id]."'");
  $summary=$total=0;
	 while($data_pro=mysql_fetch_array($que_pro)){

$equip=$data_pro[price]*$data_pro[quantity];
$labour1=$data_pro[valuelabour]*$data_pro[activelabour];
$labour=$labour1*$data_pro[quantity];
$total=$equip+$labour;
	 $summary+=$total;

}
	 
 $disco=$summary*$fetmail[dis]/100;
 $stotal=$summary-$disco;
 	$overh=$stotal*$fetmail[over]/100;
	$stotal=$stotal+$overh;
	 $vat=$stotal*$fetmail[vat]/100;
 $total=$stotal+$vat;
	 
 
 $vender=mysql_fetch_array(mysql_query("select name_en from company where id='".$_SESSION[company_id]."'"));
 $subject="INV-".$fetmail[tax2]."-".$fetmail[name_sh];
 $page="inv-m.php";
  $message = "Dear ".$fetmail[name_en]."
  
Your invoice is attached.
Your balance for this invoice is ".number_format($total,2)." Baht.
If a balance is due please remit your payment at the agreed terms.

Thank you for your business, we appreciate it very much.
".$vender[name_en];
 }break;
  case "tax" : {
		
 $fetmail=mysql_fetch_array(mysql_query("select email,texiv_rw,name_en,vat,dis,over,name_sh, taxrw as tax2 from pr join purchase_order on purchase_request.id=purchase_order.ref  join invoice on purchase_order.id=invoice.tex join company on purchase_request.customer_id=company.id where purchase_order.id='".$_REQUEST[id]."' and status>'2' and  vendor_id='".$_SESSION[company_id]."' and po_id_new=''"));
 $que_pro=mysql_query("select product.des as des,product_type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$_REQUEST[id]."'");
  $summary=$total=0;
	 while($data_pro=mysql_fetch_array($que_pro)){

$equip=$data_pro[price]*$data_pro[quantity];
$labour1=$data_pro[valuelabour]*$data_pro[activelabour];
$labour=$labour1*$data_pro[quantity];
$total=$equip+$labour;

	 $summary+=$total;

}
	 
 $disco=$summary*$fetmail[dis]/100;
 $stotal=$summary-$disco;
 	$overh=$stotal*$fetmail[over]/100;
	$stotal=$stotal+$overh;
	 $vat=$stotal*$fetmail[vat]/100;
 $total=$stotal+$vat;
	 
 
 $vender=mysql_fetch_array(mysql_query("select name_en from company where id='".$_SESSION[company_id]."'"));
 $subject="Tax-".$fetmail[texiv_rw]."-".$fetmail[name_sh];
 $page="taxiv-m.php";
  $message = "Dear ".$fetmail[name_en]."
  
Your invoice is attached.
Your balance for this invoice is ".number_format($total,2)." Baht.
If a balance is due please remit your payment at the agreed terms.

Thank you for your business, we appreciate it very much.
".$vender[name_en];
 }break;
 
 
	 
	 
	 
	 }

?>
<form action="<?=$page?>"  method="post">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Send Mail</h4>
            </div>			<!-- /modal-header -->
            <div class="modal-body">
    
            
            
        

<div id="box" style="width:100%"> <label for="name">To</label>
    <input type="text" class="form-control" id="to" name="to"  required placeholder="To" value="<?=$fetmail[email]?>;" >
    <input type="hidden" name="id" value="<?=$_REQUEST[id]?>">
	</div>
    <div id="box" style="width:100%"> <label for="name">Cc</label>
    <input type="text" class="form-control" id="cc" name="cc" placeholder="Cc" value="" >
    
	</div>
       <div id="box" style="width:100%"> <label for="name">Subject</label>
    <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required value="<?=$subject?>" > </div>  <div id="box" style="width:100%"> <label for="name">Body</label>
    <textarea class="form-control" rows="10" name="bodymail"><?=$message?></textarea>
    
	</div>
   
 <div class="clearfix"></div>   
<div id="boxbtn">
</div>    

 
            </div>			<!-- /modal-body -->
            <div class="modal-footer">
              <button type="submit" name="mode" value="<?=strtolower($data[method])?>" class="btn btn-warning"><span class="glyphicon glyphicon-send"></span> Send</button> <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> Close</button>
           

            </div>			<!-- /modal-footer -->
              </form>
  
</body>
</html>