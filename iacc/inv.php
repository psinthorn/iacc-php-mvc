<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.current.php");
$db=new DbConn($config);
$db->checkSecurity();


 $query=mysqli_query($db->conn, "select po.name as name,po.over,pr.ven_id,po.dis,po.vat, iv.taxrw as tax2,po.tax,pr.cus_id as cus_id,pr.payby,pr.des,po.bandven,po.valid_pay, DATE_FORMAT(iv.createdate,'%d-%m-%Y') as date,DATE_FORMAT(po.deliver_date,'%d-%m-%Y') as deliver_date,po.ref,po.pic,pr.status from pr join po on pr.id=po.ref  join iv on po.id=iv.tex where po.id='".mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '')."' and pr.status>'2' and (pr.cus_id='".mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '')."' or pr.ven_id='".mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '')."')");
 // if (!$dbc || mysqli_num_rows($dbc) == 0)
 if($query && mysqli_num_rows($query) > 0){
	$data=mysqli_fetch_array($query);
	$vender=mysqli_fetch_array(mysqli_query($db->conn, "select name_en,adr_tax,city_tax,district_tax,tax,province_tax,zip_tax,fax,phone,email,term,logo from company join company_addr on company.id=company_addr.com_id where company.id='".mysqli_real_escape_string($db->conn, $data['ven_id'] ?? '')."' and (valid_end IS NULL OR valid_end='0000-00-00')"));
	$customer=mysqli_fetch_array(mysqli_query($db->conn, "select name_en,name_sh,adr_tax,city_tax,district_tax,province_tax,tax,zip_tax,fax,phone,email from company join company_addr on company.id=company_addr.com_id where company.id='".mysqli_real_escape_string($db->conn, $data['payby'] ?? '')."' and (valid_end IS NULL OR valid_end='0000-00-00')"));
	

	if($data['brandven']==0){$logo=$vender['logo'] ?? '';}else{
		$bandlogo=mysqli_fetch_array(mysqli_query($db->conn, "select logo from brand where id='".mysqli_real_escape_string($db->conn, $data['brandven'] ?? '')."'"));
		$logo=$bandlogo['logo'] ?? '';
		
		}

// Build absolute file path for logo image for mPDF
$logo_img = '';
if (!empty($logo)) {
	$logo_path = __DIR__ . '/upload/' . $logo;
	if (file_exists($logo_path)) {
		$logo_img = '<img src="' . $logo_path . '" height="60" />';
	}
}

$html = '
<!-- Header with logo and company info in 2 columns -->
<table style="width:100%; border-collapse:collapse; background-color:#f8f9fa; margin-bottom:8px; border-bottom:3px solid #34495e;">
<tr>
  <td style="width:22%; padding:12px; vertical-align:top; border:none;">'.$logo_img.'</td>
  <td style="width:78%; padding:12px; vertical-align:top; border:none;">
    <div style="font-size:18px; font-weight:700; color:#1a1a1a; margin-bottom:2px; letter-spacing:0.5px;">'.$vender['name_en'].'</div>
    <div style="font-size:10px; color:#555; line-height:1.6; margin-bottom:6px;">
      '.$vender['adr_tax'].' | '.$vender['city_tax'].', '.$vender['district_tax'].', '.$vender['province_tax'].' '.$vender['zip_tax'].'
    </div>
    <div style="font-size:9px; color:#666; border-top:1px solid #ddd; padding-top:4px;">
      <span style="margin-right:15px;"><span style="font-weight:600;">Tel:</span> '.$vender['phone'].'</span>
      <span style="margin-right:15px;"><span style="font-weight:600;">Fax:</span> '.$vender['fax'].'</span>
      <span><span style="font-weight:600;">Email:</span> '.$vender['email'].'</span><br>
      <span style="font-weight:600;">Tax ID:</span> '.$vender['tax'].'
    </div>
  </td>
</tr>
</table>

<!-- Invoice Title -->
<div style="width:100%; background-color:#34495e; color:white; padding:10px; text-align:center; font-size:18px; font-weight:700; margin:0 0 8px 0; letter-spacing:2px;">INVOICE</div>

<!-- Clean 3-Column Invoice Details Grid -->
<table style="width:100%; border-collapse:collapse; font-size:9.5px; margin-bottom:8px;">
<tr style="background-color:#f5f5f5;">
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #ddd;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Customer</span><br><span style="color:#222; font-weight:500;">'.$customer['name_en'].'</span></td>
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #ddd;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Address</span><br><span style="color:#222; font-size:8px; line-height:1.3;">'.$customer['adr_tax'].'<br>'.$customer['city_tax'].', '.$customer['district_tax'].' '.$customer['zip_tax'].'</span></td>
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #ddd;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Invoice No.</span><br><span style="color:#222; font-weight:700; font-size:11px;">INV-'.$data['tax2'].'</span></td>
</tr>
<tr style="background-color:#fff;">
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #e8e8e8;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Tax ID</span><br><span style="color:#222; font-weight:500;">'.$customer['tax'].'</span></td>
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #e8e8e8;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Email</span><br><span style="color:#222; font-size:8px;">'.$customer['email'].'</span></td>
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #e8e8e8;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Create Date</span><br><span style="color:#222;">'.$data['date'].'</span></td>
</tr>
<tr style="background-color:#f5f5f5;">
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #ddd;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Tel/Fax</span><br><span style="color:#222; font-size:8px;">'.$customer['phone'].'<br>'.$customer['fax'].'</span></td>
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #ddd;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Delivery Date</span><br><span style="color:#222;">'.$data['deliver_date'].'</span></td>
  <td style="width:33.33%; padding:5px; border-bottom:1px solid #ddd;"><span style="color:#555; font-weight:600; font-size:8px; text-transform:uppercase;">Ref-Doc (PO)</span><br><span style="color:#222; font-weight:700; font-size:11px;">PO-'.$data['tax'].'</span></td>
</tr>
</table>

<div id="all_font2" style="font-size:12px; margin-bottom:10px;">

<div id="all_font" style="font-size:14px; height:410px;">


<div style="width:100%; background-color:#34495e; color:white; padding:6px 5px; font-weight:bold; font-size:9px; letter-spacing:0.5px; text-transform:uppercase;">
<div style="width:4%; float:left;">No.</div>
<div style="width:15%; float:left;">Model</div>
';
$cklabour=mysqli_fetch_array(mysqli_query($db->conn, "select max(activelabour) as cklabour from product join type on product.type=type.id where po_id='".$_REQUEST['id']."'"));
if($cklabour['cklabour']==1){
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

$html .= '<div class="clearfix" style="height:10px;"></div>';
$que_pro=mysqli_query($db->conn, "select type.name as name,product.price as price,product.des as des,valuelabour,activelabour,discount,model.model_name as model,quantity,pack_quantity from product join type on product.type=type.id join model on product.model=model.id where po_id='".$_REQUEST['id']."'");$summary=0;
$cot=1;
	while($data_pro=mysqli_fetch_array($que_pro)){

if($cklabour['cklabour']==1){	
$equip=$data_pro['price']*$data_pro['quantity'];
$labour1=$data_pro['valuelabour']*$data_pro['activelabour'];
$labour=$labour1*$data_pro['quantity'];
$total=$equip+$labour;
$summary+=$total;
$html .= '<div style="background-color:transparent;">';
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
	
		$html.='</div>';
		}else{
$total=$data_pro['price']*$data_pro['quantity'];

$summary+=$total;
$html .= '<div style="background-color:transparent;">';
$html .= '
<div style="width:4%; float:left;">'.$cot.'</div>
<div style="width:15%; float:left;">'.$data_pro['model'].'</div>
<div style="width:53%;float:left;">'.$data_pro['name'].'</div>
<div style="width:5%; float:left;text-align:right;">'.($data_pro['quantity']).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($data_pro['price'],2).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($total,2).'</div>';
if($data_pro['des']!="")$html .= '
<div style="width:98%; margin-left:2%;font-size:10px;"># '.$data_pro['des'].'</div>';	
	
		$html.='</div>';
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
<div style="width:49%; height:100px; float:left; border-right: solid thin #cccccc; text-align:center;"><div style="text-align:left;">&nbsp;&nbsp;&nbsp;&nbsp;'.$customerc['name_en'].'</div><br><br><br><br>____________________________<br>RECEIVER<BR>Date _______/_______/________</div>
<div style="width:49%; height:100px; float:left; text-align:center;"><div style="text-align:left;">&nbsp;&nbsp;&nbsp;&nbsp;'.$vender['name_en'].'</div><br><br><br><br>____________________________<br>Authorizer Signature<BR>Date _______/_______/________</div>
</div>


';	


//==============================================================
//==============================================================
include("MPDF/mpdf.php");


$mpdf= new mPdf('th', 'A4', '0');
$mpdf->WriteHTML($html);



$mpdf->Output("INV-".$data['tax2']."-".$customer['name_sh'].".pdf","I");
exit;
//==============================================================
//==============================================================

}else echo "<center>ERROR</center>";?>