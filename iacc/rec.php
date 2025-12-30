<?php
ob_start();
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.current.php");
$users=new DbConn($config);
$users->checkSecurity();


if($_REQUEST[modep]=="ad"){
	$query=mysql_query("select sendoutitem.id as id,sendoutitem.tmp as des,ven_id,cus_id,name_sh,out_id,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where deliver.id='".$_REQUEST[id]."' and (cus_id='".$_SESSION[com_id]."' or ven_id='".$_SESSION[com_id]."') and deliver.id not in (select deliver_id from receive) ");
	}else{
 
 
 $query=mysql_query("select po.name as name,ven_id,dis,tax,cus_id,des,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay,deliver.po_id as po_id,brandven,po.date as date,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date,ref,pic,status from pr join po on pr.id=po.ref  JOIN deliver on deliver.po_id=po.id where deliver.id='".$_REQUEST[id]."' and  status>'2'  and (cus_id='".$_SESSION[com_id]."' or ven_id='".$_SESSION[com_id]."') and po_id_new=''");
 }
if(mysql_num_rows($query)=="1"){
	$data=mysql_fetch_array($query);
	$vender=mysql_fetch_array(mysql_query("select name_en,adr_tax,city_tax,district_tax,tax,province_tax,zip_tax,fax,phone,email,logo,term from company join company_addr on company.id=company_addr.com_id where company.id='".$data[ven_id]."' and valid_end='0000-00-00'"));
	$customer=mysql_fetch_array(mysql_query("select name_en,name_sh,adr_tax,city_tax,district_tax,tax,province_tax,zip_tax,fax,phone,email from company join company_addr on company.id=company_addr.com_id where company.id='".$data[cus_id]."' and valid_end='0000-00-00'"));
	
if($data[brandven]==0){$logo=$vender[logo];}else{
		$bandlogo=mysql_fetch_array(mysql_query("select logo from brand where id='".$data[brandven]."'"));
		$logo=$bandlogo[logo];
		
		}

$logo_path = '';
if (!empty($logo)) {
	$logo_path = dirname(__FILE__) . '/upload/' . $logo;
	if (!file_exists($logo_path)) {
		$logo_path = '';
	}
}

$logo_img = '';
if (!empty($logo_path)) {
	$logo_img = '<img src="'.$logo_path.'" height="60" />';
}

$html = '
<div style="width:20%; float:left;">'.$logo_img.'</div><div style="width:80%;text-align:right "><b>'.$vender[name_en].'</b>
<small><br>'.$vender[adr_tax].'<br>'.$vender[city_tax].' '.$vender[district_tax].' '.$vender[province_tax].' '.$vender[zip_tax].'<br>Tel : '.$vender[phone].'  Fax : '.$vender[fax].' Email: '.$vender[email].'<br>Tax: '.$vender[tax].'</small></div>

<div id="all_font2" style="font-size:12px; margin-bottom:10px; ">
<div style="width:100%; margin-top:10px; margin-bottom:5px; padding:5px;background-color:#000; text-align:center; font-weight:bold; color:#FFF;font-size:18px;">DELIVERY NOTE
</div>

<div style="width:10%; float:left; font-weight:bold;">Customer</div>
<div style="width:54%; float:left;">'.$customer[name_en].'</div>
<div style="width:14%; float:left; text-align:left; padding-left:3px; font-weight:bold;">Delivery Date</div>
<div style="width:20%; float:left; text-align:left;">'.$data['deliver_date'].'</div>


<div style="width:10%; float:left; font-weight:bold;">Address</div>
<div style="width:54%; float:left;">'.$customer[adr_tax].'</div>
<div style="width:14%; float:left; padding-left:3px; font-weight:bold; ">No.</div>
';

if($_REQUEST[modep]!="ad"){
$html.='	
<div style="width:20%; float:left; ">DN-'.str_pad($_REQUEST[id], 7, "0", STR_PAD_LEFT).'</div>
';}else{
$html.='	
<div style="width:20%; float:left; ">
DN-'.str_pad($_REQUEST[id], 7, "0", STR_PAD_LEFT).'(make)</div>
';}




$html.='
<div style="width:10%; height:5px; float:left; font-weight:bold;"></div>
<div style="width:54%; float:left;">'.$customer[city_tax].' '.$customer[district_tax].' '.$customer[province_tax].' '.$customer[zip_tax].'</div>';





if($_REQUEST[modep]!="ad"){
$html.='<div style="width:14%; float:left;  padding-left:3px; font-weight:bold;">Ref-Doc</div>
<div style="width:20%; float:left;">PO-'.$data[tax].'</div>
';}else{
	$html.='<div style="width:14%; height:10px; float:left;  padding-left:3px; font-weight:bold;"> </div>
<div style="width:20%; height:10px; float:left;"> </div>
';
	}
$html.='
<div style="width:10%; float:left; font-weight:bold;">Tax ID</div>
<div style="width:90%; float:left;">'.$customer[tax].'</div>

<div style="width:10%; float:left; font-weight:bold;">Email</div>
<div style="width:90%; float:left;">'.$customer[email].'</div>

<div style="width:10%; float:left; font-weight:bold;">Tel.</div>
<div style="width:22%; float:left;">'.$customer[phone].'</div>
<div style="width:10%; float:left; font-weight:bold;">Fax.</div>
<div style="width:22%; float:left;">'.$customer[fax].'</div>

</div>


<div id="all_font" style="font-size:12px; height:532px;">


<div style="width:100%; border-top: solid thin #CCC; border-bottom: solid thin #CCC; font-weight:bold;">
<div style="width:5%; float:left;">No.</div>
<div style="width:15%; float:left;">Model</div>
<div style="width:36%;float:left;">Product Name</div>

<div style="width:20%; float:left;text-align:left;">S/N</div>
<div style="width:10%; float:left;text-align:left;">Unit</div>

<div style="width:13%; float:left;text-align:left;">Warranty</div>

</div>
';

if($_REQUEST[modep]=="ad"){$que_pro=mysql_query("select type.name as name,model.model_name as model,s_n,DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty,product.des as des, quantity from product join type on product.type=type.id  join store on product.pro_id=store.pro_id join store_sale on store.id=store_sale.st_id join model on product.model=model.id where so_id='".$data[out_id]."'");}else{
$que_pro=mysql_query("select type.name as name,model.model_name as model,quantity,s_n,product.des as des,DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty from product join type on product.type=type.id  join store on product.pro_id=store.pro_id join store_sale on store.id=store_sale.st_id join model on product.model=model.id where po_id='".$data[po_id]."'");}$summary=0;
$cot=1;
	while($data_pro=mysql_fetch_array($que_pro)){
$total=$data_pro[price]-$data_pro[discount];
$summary+=$total;

$html .= '<div style="width:100%">
<div style="width:5%; float:left;">'.$cot.'</div>
<div style="width:15%; float:left;">'.$data_pro[model].'</div>
<div style="width:36%;float:left;">'.$data_pro[name].'</div>
<div style="width:20%; float:left;text-align:left;">'.$data_pro[s_n].'</div>
<div style="width:10%; float:left;text-align:left;">1</div>
<div style="width:13%; float:left;text-align:left;">'.$data_pro[warranty].'</div>

</div>';

	if($data_pro[des]!="")$html .= '
<div style="width:98%; margin-left:2%;font-size:10px;"># '.$data_pro[des].'</div>';	
$cot++;
 }
 
$html .= '</div>
<hr>

<div id="all_font" style="font-size:12px;">


<b>Term & Condition</b><br>'.$vender[term].'<br>
<hr>
<div style="width:33%; height:100px; float:left; border-right: solid thin #cccccc; text-align:center;"><br><br><br><br>____________________________<br>Receive By<BR>Date _______/_______/________</div>
<div style="width:33%; height:100px; float:left; border-right: solid thin #cccccc; text-align:center;"><br><br><br><br>____________________________<br>Delivery By<BR>Date _______/_______/________</div>
<div style="width:33%; height:100px; float:left; text-align:center;"><br><br><br><br>____________________________<br>Authorize Signature<BR>Date _______/_______/________</div>
</div>

';	


//==============================================================
//==============================================================
@include("MPDF/mpdf.php");

@$mpdf= new mPdf('th', 'A4', '0');

$mpdf->WriteHTML($html);

ob_clean();
$mpdf->Output("DN-".str_pad($_REQUEST[id], 7, "0", STR_PAD_LEFT)."-".$customer[name_sh].".pdf","I");
exit;
//==============================================================
//==============================================================

}else echo "<center>ERROR</center>";?>