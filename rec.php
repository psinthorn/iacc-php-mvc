<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.current.php");
$db=new DbConn($config);
// Security already checked in index.php

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id'] ?? 0);
$modep = sql_escape($_REQUEST['modep'] ?? '');

if($modep=="ad"){
	$query=mysqli_query($db->conn, "select sendoutitem.id as id,sendoutitem.tmp as des,ven_id,cus_id,name_sh,out_id,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where deliver.id='".$id."' and (cus_id='".$com_id."' or ven_id='".$com_id."') and deliver.id not in (select deliver_id from receive) ");
	}else{
 
 
 $query=mysqli_query($db->conn, "select po.name as name,po.tax as tax,ven_id,dis,cus_id,des,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay,deliver.po_id as po_id,bandven,po.date as date,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date,ref,pic,po_ref,status from pr join po on pr.id=po.ref  JOIN deliver on deliver.po_id=po.id where deliver.id='".$id."' and  status>'2'  and (cus_id='".$com_id."' or ven_id='".$com_id."') and po_id_new=''");
 }
if(mysqli_num_rows($query)=="1"){
	$data=mysqli_fetch_array($query);
	
	// Fetch vendor info - use LEFT JOIN and get the current/latest valid address
	$vender=mysqli_fetch_array(mysqli_query($db->conn, "
		SELECT company.name_en, company_addr.adr_tax, company_addr.city_tax, company_addr.district_tax, 
		       company.tax, company_addr.province_tax, company_addr.zip_tax, company.fax, company.phone, 
		       company.email, company.logo, company.term 
		FROM company 
		LEFT JOIN company_addr ON company.id = company_addr.com_id 
		    AND company_addr.deleted_at IS NULL
		WHERE company.id = '".$data['ven_id']."'
		ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC, company_addr.valid_start DESC
		LIMIT 1
	"));
	
	// Fetch customer info - use LEFT JOIN and get the current/latest valid address
	$customer=mysqli_fetch_array(mysqli_query($db->conn, "
		SELECT company.name_en, company.name_sh, company_addr.adr_tax, company_addr.city_tax, 
		       company_addr.district_tax, company.tax, company_addr.province_tax, company_addr.zip_tax, 
		       company.fax, company.phone, company.email 
		FROM company 
		LEFT JOIN company_addr ON company.id = company_addr.com_id 
		    AND company_addr.deleted_at IS NULL
		WHERE company.id = '".$data['cus_id']."'
		ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC, company_addr.valid_start DESC
		LIMIT 1
	"));
	
if($data['bandven']==0){$logo=$vender['logo'];}else{
		$bandlogo=mysqli_fetch_array(mysqli_query($db->conn, "select logo from brand where id='".$data['bandven']."'"));
		$logo=$bandlogo['logo'];
		
		}

// Build DN Number
$dn_number = 'DN-' . str_pad($id, 7, "0", STR_PAD_LEFT);
if($modep == "ad") {
    $dn_number .= ' (make)';
}

// Modern Clean Template matching inv.php style
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    
    /* Header */
    .header { text-align: center; margin-bottom: 10px; }
    .header img { width: 50px; height: 50px; }
    .company-name { font-size: 14px; font-weight: bold; color: #059669; margin-top: 5px; }
    .company-addr { font-size: 10px; color: #444; line-height: 1.4; }
    
    /* Title */
    .title { background: #059669; color: #fff; text-align: center; padding: 8px; font-size: 16px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
    
    /* Info Section */
    .info-table { width: 100%; margin-bottom: 10px; }
    .info-table td { vertical-align: top; font-size: 10px; }
    .info-left { width: 55%; }
    .info-right { width: 45%; padding-left: 20px; }
    .dn-box { padding: 4px 0; margin-bottom: 6px; }
    .dn-num { font-size: 13px; font-weight: bold; color: #059669; margin: 0; }
    .dn-meta { font-size: 9px; color: #666; margin-top: 2px; }
    .lbl { font-weight: bold; color: #555; width: 55px; }
    .cust-name { font-weight: bold; }
    
    /* Items Table */
    .items { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .items th { background: #059669; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
    .items th.r { text-align: right; }
    .items th.c { text-align: center; }
    .items td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .items td.r { text-align: right; }
    .items td.c { text-align: center; }
    .items tr:nth-child(even) { background: #f0fdf4; }
    
    /* Terms */
    .terms { border-top: 1px solid #ccc; padding-top: 8px; margin-top: 15px; }
    .terms-title { font-weight: bold; font-size: 10px; color: #059669; margin-bottom: 5px; }
    .terms-content { font-size: 9px; color: #555; line-height: 1.4; }
    
    /* Signatures */
    .sigs { margin-top: 30px; }
    .sigs td { width: 33%; text-align: center; padding: 0 10px; vertical-align: bottom; }
    .sig-space { height: 40px; }
    .sig-line { font-size: 10px; font-weight: bold; padding-top: 5px; border-top: 1px solid #333; }
    .sig-date { font-size: 9px; color: #888; margin-top: 3px; }
</style>

<!-- Header -->
<div class="header">
    <img src="upload/' . $logo . '" width="50" height="50"><br>
    <div class="company-name">' . ($vender['name_en'] ?? '') . '</div>
    <div class="company-addr">
        ' . ($vender['adr_tax'] ?? '') . ' ' . ($vender['city_tax'] ?? '') . ' ' . ($vender['district_tax'] ?? '') . ' ' . ($vender['province_tax'] ?? '') . ' ' . ($vender['zip_tax'] ?? '') . '<br>
        Tel: ' . ($vender['phone'] ?? '') . ' &nbsp; Fax: ' . ($vender['fax'] ?? '') . ' &nbsp; Email: ' . ($vender['email'] ?? '') . ' &nbsp; Tax ID: ' . ($vender['tax'] ?? '') . '
    </div>
</div>

<!-- Title -->
<div class="title">DELIVERY NOTE</div>

<!-- Info Section -->
<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="dn-box">
                <div class="dn-num">' . $dn_number . '</div>
                <div class="dn-meta">Date: ' . $data['deliver_date'] . ($modep != "ad" ? ' &nbsp;|&nbsp; PO: PO-' . $data['tax'] . (!empty($data['po_ref']) ? ' &nbsp;|&nbsp; PO Ref: ' . htmlspecialchars($data['po_ref']) : '') : '') . '</div>
            </div>
            <table>
                <tr><td class="lbl">Customer</td><td class="cust-name">' . ($customer['name_en'] ?? '') . '</td></tr>
                <tr><td class="lbl">Address</td><td>' . ($customer['adr_tax'] ?? '') . ' ' . ($customer['city_tax'] ?? '') . ' ' . ($customer['district_tax'] ?? '') . ' ' . ($customer['province_tax'] ?? '') . ' ' . ($customer['zip_tax'] ?? '') . '</td></tr>
                <tr><td class="lbl">Tax ID</td><td>' . ($customer['tax'] ?? '') . '</td></tr>
            </table>
        </td>
        <td class="info-right">
            <table>
                <tr><td class="lbl">Tel</td><td>' . ($customer['phone'] ?? '') . '</td></tr>
                <tr><td class="lbl">Fax</td><td>' . ($customer['fax'] ?? '') . '</td></tr>
                <tr><td class="lbl">Email</td><td>' . ($customer['email'] ?? '') . '</td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Items -->
<table class="items">
    <tr>
        <th style="width:5%">#</th>
        <th style="width:15%">Model</th>
        <th style="width:35%">Product Name</th>
        <th style="width:20%">S/N</th>
        <th class="c" style="width:8%">Unit</th>
        <th style="width:17%">Warranty</th>
    </tr>';

if($_REQUEST['modep']=="ad"){
    $que_pro = mysqli_query($db->conn, "SELECT type.name as name, model.model_name as model, s_n, DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty, product.des as des, quantity FROM product JOIN type ON product.type=type.id JOIN store ON product.pro_id=store.pro_id JOIN store_sale ON store.id=store_sale.st_id JOIN model ON product.model=model.id WHERE so_id='".$data['out_id']."'");
} else {
    $que_pro = mysqli_query($db->conn, "SELECT type.name as name, model.model_name as model, quantity, s_n, product.des as des, DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty FROM product JOIN type ON product.type=type.id JOIN store ON product.pro_id=store.pro_id JOIN store_sale ON store.id=store_sale.st_id JOIN model ON product.model=model.id WHERE po_id='".$data['po_id']."'");
}

$cot = 1;
while($data_pro = mysqli_fetch_array($que_pro)) {
    $html .= '<tr>
        <td>' . $cot . '</td>
        <td>' . ($data_pro['model'] ?? '') . '</td>
        <td>' . ($data_pro['name'] ?? '') . '</td>
        <td>' . ($data_pro['s_n'] ?? '') . '</td>
        <td class="c">1</td>
        <td>' . ($data_pro['warranty'] ?? '') . '</td>
    </tr>';
    // Add description row if exists
    if(!empty($data_pro['des'])) {
        $html .= '<tr style="background:#f9fafb;">
            <td></td>
            <td colspan="5" style="font-size:9px; color:#666; padding:4px 8px; border-bottom:1px solid #ddd;"><em>' . htmlspecialchars($data_pro['des']) . '</em></td>
        </tr>';
    }
    $cot++;
}

$html .= '</table>

<!-- Terms -->
' . (!empty($vender['term']) ? '
<div class="terms">
    <div class="terms-title">Terms & Conditions</div>
    <div class="terms-content">' . nl2br($vender['term'] ?? '') . '</div>
</div>' : '') . '

<!-- Signatures -->
<table class="sigs" width="100%">
    <tr>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Received By</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Delivered By</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Authorized Signature</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
    </tr>
</table>';	


//==============================================================
//==============================================================
include("MPDF/mpdf.php");

$mpdf= new mPdf('th', 'A4', '0');

$mpdf->WriteHTML($html);




$mpdf->Output("DN-".str_pad($id, 7, "0", STR_PAD_LEFT)."-".$customer[name_sh].".pdf","I");
exit;
//==============================================================
//==============================================================

}else echo "<center>ERROR</center>";?>