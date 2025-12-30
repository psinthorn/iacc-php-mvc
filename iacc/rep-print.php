<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.current.php");
$db=new DbConn($config);
$db->checkSecurity();
	$filename="";

 $query=mysqli_query($db->conn, "select * from receipt where id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "' and vender='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "'");
if(mysqli_num_rows($query)==1){
	$data=mysqli_fetch_array($query);
	$vender=mysqli_fetch_array(mysqli_query($db->conn, "select name_en,adr_tax,city_tax,district_tax,province_tax,tax,zip_tax,fax,phone,email,logo,term from company join company_addr on company.id=company_addr.com_id where company.id='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' and valid_end='0000-00-00'"));
	$filename=$data['rep_rw'];
	
	if($data['brandven']==0){$logo=$vender['logo'];}else{
		$bandlogo=mysqli_fetch_array(mysqli_query($db->conn, "select logo from brand where id='" . mysqli_real_escape_string($db->conn, $data['brandven']) . "'"));
		$logo=$bandlogo['logo'];

<div id="all_font2" style="font-size:12px; margin-bottom:10px; ">
<div style="width:100%; margin-top:10px; margin-bottom:5px; padding:5px; background-color:#000; text-align:center; font-weight:bold; color:#FFF;font-size:18px;">RECEIPT</div>
<div style="width:10%; float:left; font-weight:bold;">Customer</div>
<div style="width:54%; float:left;">'.$data['name'].'</div>
<div style="width:14%; float:left; text-align:left; padding-left:3px; font-weight:bold;">Create Date</div>
<div style="width:20%; float:left; text-align:left;">'.$data['createdate'].'</div>
<div style="width:10%; float:left; font-weight:bold;">Email</div>
<div style="width:27%; float:left;">'.$data['email'].'</div>

<div style="width:5%; float:left; font-weight:bold;">Tel.</div>
<div style="width:22%; float:left;">'.$data['phone'].'</div>



<div style="width:14%; float:left; padding-left:3px; font-weight:bold; ">Reciept No.</div>

<div style="width:20%; float:left; ">REP-'.$data['rep_rw'].'</div>


</div>


<div id="all_font" style="font-size:12px; height:430px;">


<div style="width:100%; border-top: solid thin #CCC; border-bottom: solid thin #CCC; font-weight:bold;">
<div style="width:4%; float:left;">No.</div>';

$html .= '
<div style="width:68%;float:left;">Product Name</div>
<div style="width:5%; float:left;text-align:center;">QTY</div>
<div style="width:11%; float:left;text-align:right;">Price</div>
<div style="width:11%; float:left;text-align:right;">Amount</div>';

$html .= '
</div>
';

$html .= '<div class="clearfix" style="height:10px;"></div>';
$que_pro=mysqli_query($db->conn, "select type.name as name,quantity,product.price as price,product.des as des from product join type on product.type=type.id where re_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "' and po_id='0' and so_id='0' ");$summary=0;
$cot=1;
while($data_pro=mysqli_fetch_array($que_pro))
	{
	{
$total=$data_pro['price']*$data_pro['quantity'];

$summary+=$total;
$html .= '
<div style="width:4%; float:left;">'.$cot.'</div>
<div style="width:68%;float:left;">'.$data_pro['name'].'</div>
<div style="width:5%; float:left;text-align:right;">'.($data_pro['quantity']).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($data_pro['price'],2).'</div>
<div style="width:11%; float:left;text-align:right;">'.number_format($total,2).'</div>';
if(isset($data_pro['des']) && $data_pro['des']!="")$html .= '
<div style="width:98%; margin-left:2%;font-size:10px;"># '.$data_pro['des'].'</div>';	
	
	}
$cot++;
 }
 $disco=$summary*(isset($data['discount']) ? $data['discount'] : 0)/100;
 $stotal=$summary-$disco;
$html .= '</div>
<hr>

<div id="all_font" style="font-size:12px;">


<div style="width:12%; float:right;text-align:right;">'.number_format($summary,2).'</div>
<div style="width:12%; float:right;text-align:right;">Total</div>
<br>

<div style="width:12%; float:right;text-align:right;">- '.number_format($disco,2).'</div>
<div style="width:12%; float:right;text-align:right;">Discount '.(isset($data['discount']) ? $data['discount'] : 0).'%</div>
<br>


<div style="width:12%; float:right;text-align:right;">'.number_format($stotal,2).'</div>
<div style="width:12%; float:right;text-align:right;">Sub Total</div>
<br>';

if((isset($data['over']) ? $data['over'] : 0)>0){
	$overh=$stotal*(isset($data['over']) ? $data['over'] : 0)/100;
	$stotal=$stotal+$overh;
$html .= '
<div style="width:12%; float:right;text-align:right;">+ '.number_format($overh,2).'</div>
<div style="width:12%; float:right;text-align:right;">Overhead '.(isset($data['over']) ? $data['over'] : 0).'%</div>
<br>


<div style="width:12%; float:right;text-align:right;">'.number_format($stotal,2).'</div>
<div style="width:12%; float:right;text-align:right;">Total</div>
<br>';}


 $vat=$stotal*(isset($data['vat']) ? $data['vat'] : 0)/100;
 $total=$stotal+$vat;

$html .= '
<div style="width:12%; float:right;text-align:right;">+ '.number_format($vat,2).'</div>
<div style="width:12%; float:right;text-align:right;">Vat '.(isset($data['vat']) ? $data['vat'] : 0).'%</div>
<br>


<div style="width:70%; float:left;text-align:left;">('. bahtEng($total) .')</div>
<div style="width:12%; float:right;text-align:right;">'.number_format($total,2).'</div>
<div style="width:12%; float:right;text-align:right;">Grand Total</div>


<br>
<hr>
<b>Term & Condition</b><br>'.(isset($vender['term']) ? $vender['term'] : '').'<br>
<hr>
<div style="width:33%; height:100px; float:right; text-align:center;">Approved By<br><br>Sinthorn Pradutnam<br> 087-938-8-938<br>____________________________<br>Signature<BR>Date '.date("d").' / '.date("m").' / '.date("Y").'</div>
</div>

';	


//==============================================================
//==============================================================
ini_set('display_errors', '0');
ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT);
include("MPDF/mpdf.php");
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');

$mpdf= new mPdf('th', 'A4', '0');


$mpdf->WriteHTML($html);



$mpdf->Output("REC-".	$filename.".pdf","I");
exit;
//==============================================================
//==============================================================

}else echo "<center>ERROR</center>";?>