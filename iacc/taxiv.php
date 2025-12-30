<?php
ini_set('display_errors', '0');
ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT);
ob_start();
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
// require_once("inc/class.current.php");
// $db=new DbConn($config);
// $db->checkSecurity();


 $query=mysqli_query($db->conn, "select po.name as name,over,ven_id,dis, taxrw as tax2,tax,pr.cus_id as cus_id,payby,des,vat,DATE_FORMAT(texiv_create,'%d-%m-%Y') as date,texiv_rw,ref,pic,status from pr join po on pr.id=po.ref  join iv on po.id=iv.tex where po.id='".$_REQUEST['id']."' and status='5' and (pr.cus_id='".$_SESSION['com_id']."' or ven_id='".$_SESSION['com_id']."') and po_id_new=''");
if(mysqli_num_rows($query)=="1"){
	$data=mysqli_fetch_array($query);
	$vender=mysqli_fetch_array(mysqli_query($db->conn, "select name_en,adr_tax,city_tax,district_tax,tax,province_tax,zip_tax,fax,phone,email,term,logo from company join company_addr on company.id=company_addr.com_id where company.id='" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "' and valid_end='0000-00-00'"));
	$customer=mysqli_fetch_array(mysqli_query($db->conn, "select name_en,name_sh,adr_tax,city_tax,district_tax,province_tax,tax,zip_tax,fax,phone,email from company join company_addr on company.id=company_addr.com_id where company.id='" . mysqli_real_escape_string($db->conn, $data['payby']) . "' and valid_end='0000-00-00'"));
	

$html = '
<div style="width:20%; float:left;"><img src="upload/'.$vender['logo'].'"  height="60" ></div><div style="width:80%;text-align:right "><b>'.$vender['name_en'].'</b>
<small><br>'.$vender['adr_tax'].'<br>'.$vender['city_tax'].' '.$vender['district_tax'].' '.$vender['province_tax'].' '.$vender['zip_tax'].'<br>Tel : '.$vender['phone'].'  Fax : '.$vender['fax'].' Email: '.$vender['email'].'<br>Tax: '.$vender['tax'].'</small></div>


<div id="all_font2" style="font-size:12px; margin-bottom:10px; ">
<div style="width:100%; margin-top:10px; margin-bottom:5px; padding:5px;background-color:#000; text-align:center; font-weight:bold; color:#FFF;font-size:18px;">Tax Invoice / Receipt</div>

<div style="width:10%; float:left; font-weight:bold;">Customer</div>
<div style="width:54%; float:left;">'.$customer['name_en'].'</div>
<div style="width:14%; float:left; text-align:left; padding-left:3px; font-weight:bold;">Create Date</div>
<div style="width:20%; float:left; text-align:left;">'.$data['date'].'</div>


<div style="width:10%; float:left; font-weight:bold;">Address</div>
<div style="width:54%; float:left;">'.$customer['adr_tax'].'</div>

<div style="width:14%; float:left; padding-left:3px; font-weight:bold; ">Tex No.</div>
<div style="width:20%; float:left; ">Tax-'.$data['texiv_rw'].'</div>


<div style="width:10%; height:5px; float:left; font-weight:bold;"></div>
<div style="width:54%; float:left;">'.$customer['city_tax'].' '.$customer['district_tax'].' '.$customer['province_tax'].' '.$customer['zip_tax'].'</div>



<div style="width:14%; float:left; padding-left:3px; font-weight:bold; ">Invoice No.</div>
<div style="width:20%; float:left; ">INV-'.str_pad($data['tax2'], 8, "0", STR_PAD_LEFT).'</div>



<div style="width:10%; float:left; font-weight:bold;">Tax ID</div>


<div style="width:54%; float:left;">'.$customer['tax'].'</div>

<div style="width:14%; float:left;  padding-left:3px; font-weight:bold;">Ref-Doc</div>
<div style="width:20%; float:left;">PO-'.$data['tax'].'</div>


<div style="width:10%; float:left; font-weight :bold;">Email</div>
<div style="width:90%; float:left;">'.$customer['email'].'</div>

<div style="width:10%; float:left; font-weight:bold;">Tel.</div>
<div style="width:22%; float:left;">'.$customer['phone'].'</div>
<div style="width:10%; float:left; font-weight:bold;">Fax.</div>
<div style="width:22%; float:left;">'.$customer['fax'].'</div>



</div>

<div id="all_font" style="font-size:12px; height:410px;">


<div style="width:100%; border-top: solid thin #CCC; border-bottom: solid thin #CCC; font-weight:bold;">
<div style="width:4%; float:left;">No.</div>
<div style="width:15%; float:left;">Model</div>
';
$cklabour=mysqli_fetch_array(mysqli_query($db->conn, "select max(activelabour) as cklabour from product join type on product.type=type.id where po_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "'"));
if((isset($cklabour['cklabour']) ? $cklabour['cklabour'] : 0)==1){
$html .= '
<div style="width:22%;float:left;">Product Name</div>
<div style="width:5%; float:left;text-align:center;">QTY</div>
<div style="width:11%; float:left;text-align:right;">Price</div>
<div style="width:11%; float:left;text-align:right;">Total</div>
<div style="width:9%; float:left;text-align:right;">Labour</div>
<div style="width:11%; float:left;text-align:right;">Total</div>
<div style="width:11%; float:left;text-align:right;">Amount</div>';}else{
$html .= '
<div style="width:53%;float:left;">Product Name</div>
<div style="width:5%; float:left;text-align:center;">QTY</div>
<div style="width:11%; float:left;text-align:right;">Price</div>
<div style="width:11%; float:left;text-align:right;">Amount</div>';}

$html .= '
</div>
';

$cklabour=mysqli_fetch_array(mysqli_query($db->conn, "select max(activelabour) as cklabour from product join type on product.type=type.id where po_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "'"));

$html .= '<div class="clearfix" style="height:10px;"></div>';

$que_pro=mysqli_query($db->conn, "select type.name as name,product.price as price,discount,model.model_name as model,quantity,product.des as des,pack_quantity,activelabour,valuelabour from product join type on product.type=type.id join model on product.model=model.id where po_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "'");$summary=0;
$cot=1;
	while($data_pro=mysqli_fetch_array($que_pro)){

if($cklabour['cklabour']==1){	
$equip=$data_pro['price']*$data_pro['quantity'];
$labour1=$data_pro['valuelabour']*$data_pro['activelabour'];
$labour=$labour1*$data_pro['quantity'];
$total=$equip+$labour;
$summary+=$total;
$html .= '
<div style="width:4%; float:left;">'.$cot.'</div>
<div style="width:15%; float:left;">'.$data_pro['model'].'</div>
<div style="width:22%;float:left;">'.$data_pro['name'].'</div>
<div style="width:5%; float:left;text-align:center;">'.($data_pro['quantity']).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($data_pro['price'],2).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($equip,2).'</div>
<div style="width:9%; float:left;text-align:right;">'.number_format($labour1,2).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($labour,2).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($total,2).'</div>';
	if($data_pro['des']!="")$html .= '
<div style="width:98%; margin-left:2%;font-size:10px;"># '.$data_pro['des'].'</div>';	
	}else{
$total=$data_pro['price']*$data_pro['quantity'];

$summary+=$total;
$html .= '
<div style="width:4%; float:left;">'.$cot.'</div>
<div style="width:15%; float:left;">'.$data_pro['model'].'</div>
<div style="width:53%;float:left;">'.$data_pro['name'].'</div>
<div style="width:5%; float:left;text-align:right;">'.($data_pro['quantity']).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($data_pro['price'],2).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($total,2).'</div>';
	
	
	if($data_pro['des']!="")$html .= '
<div style="width:98%; margin-left:2%;font-size:10px;"># '.$data_pro['des'].'</div>';	
	
	}
$cot++;
 }
 $disco=$summary*$data['dis']/100;
 $stotal=$summary-$disco;
$html .= '</div>
<hr>

<div id="all_font" style="font-size:12px;">


<div style="width:12%; float:right;text-align:right;">'.number_format($summary,2).'</div>
<div style="width:12%; float:right;text-align:right;">Total</div>
<br>

<div style="width:12%; float:right;text-align:right;">- '.number_format($disco,2).'</div>
<div style="width:12%; float:right;text-align:right;">Discount '.$data['dis'].'%</div>
<br>


<div style="width:12%; float:right;text-align:right;">'.number_format($stotal,2).'</div>
<div style="width:12%; float:right;text-align:right;">Sub Total</div>
<br>';

if($data['over']>0){
	$overh=$stotal*$data['over']/100;
	$stotal=$stotal+$overh;
$html .= '
<div style="width:12%; float:right;text-align:right;">+ '.number_format($overh,2).'</div>
<div style="width:12%; float:right;text-align:right;">Overhead '.$data['over'].'%</div>
<br>


<div style="width:12%; float:right;text-align:right;">'.number_format($stotal,2).'</div>
<div style="width:12%; float:right;text-align:right;">Total</div>
<br>';}


 $vat=$stotal*$data['vat']/100;
 $total=round($stotal,2)+round($vat,2);


$html .= '
<div style="width:12%; float:right;text-align:right;">+ '.number_format($vat,2).'</div>
<div style="width:12%; float:right;text-align:right;">Vat '.$data['vat'].'%</div>
<br>


<div style="width:70%; float:left;text-align:left;">('.bahtEng($total).')</div>
<div style="width:12%; float:right;text-align:right;">'.number_format($total,2).'</div>
<div style="width:12%; float:right;text-align:right;">Grand Total</div>
<br>
<hr>
<b>Term & Condition</b><br>'.$vender['term'].'<br>
<hr>
<div style="width:49%; height:100px; float:left; border-right: solid thin #cccccc; text-align:center;"></div>
<div style="width:49%; height:100px; float:left; text-align:center;"><div style="text-align:left;">&nbsp;&nbsp;&nbsp;&nbsp;'.$vender['name_en'].'</div><br><br><br><br>____________________________<br>Authorize Signature<BR>Date _______/_______/________</div>
</div>


';	


//==============================================================
//==============================================================
@include("MPDF/mpdf.php");

@$mpdf= new mPdf('th', 'A4', '0');


$mpdf->WriteHTML($html);

ob_clean();
$mpdf->Output("Tax-".$data['texiv_rw']."-".$customer['name_sh'].".pdf","I");
$mpdf->Output();
exit;
//==============================================================
//==============================================================

}else echo "<center>ERROR</center>";?>