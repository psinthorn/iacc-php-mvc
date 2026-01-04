<?php
/**
 * Receipt PDF Generator
 * Professional design matching Invoice template
 * Supports receipts from quotations, invoices, or manual entry
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.current.php");
require_once("inc/payment-method-helper.php");

$db = new DbConn($config);
$db->checkSecurity();

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);

$query = mysqli_query($db->conn, "SELECT * FROM receipt WHERE id='".$id."' AND vender='".$com_id."'");

if (mysqli_num_rows($query) != 1) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Receipt Not Found</h2><p>The requested receipt does not exist or you do not have permission to view it.</p></div>');
}

$data = mysqli_fetch_array($query);
$filename = $data['rep_rw'];

// Get source type and VAT mode
$source_type = $data['source_type'] ?? 'manual';
$include_vat = $data['include_vat'] ?? 1;

// Fetch vendor info
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT name_en, adr_tax, city_tax, district_tax, province_tax, tax, zip_tax, fax, phone, email, logo, term 
    FROM company 
    JOIN company_addr ON company.id = company_addr.com_id 
    WHERE company.id = '".$com_id."' AND valid_end = '0000-00-00'
"));

// Get logo
if ($data['brand'] == 0) {
    $logo = $vender['logo'] ?? '';
} else {
    $bandlogo = mysqli_fetch_array(mysqli_query($db->conn, "SELECT logo FROM brand WHERE id = '".$data['brand']."'"));
    $logo = $bandlogo['logo'] ?? '';
}

// Check source type and fetch linked data
$linked_source = null;
$source_products = [];
$use_source_data = false;
$customer = null;
$source_doc_no = '';

// Handle quotation source
if ($source_type === 'quotation' && !empty($data['quotation_id'])) {
    $qa_query = mysqli_query($db->conn, "
        SELECT po.id, po.tax as doc_no, DATE_FORMAT(po.date, '%d/%m/%Y') as doc_date, 
               po.vat as doc_vat, po.dis as doc_dis, po.over as doc_over,
               company.name_en as cust_name, company.phone as cust_phone, 
               company.email as cust_email, company_addr.adr_tax, company_addr.city_tax,
               company_addr.district_tax, company_addr.province_tax, company_addr.zip_tax, 
               company.tax as cust_tax, company.fax as cust_fax
        FROM po
        JOIN pr ON po.ref = pr.id
        JOIN company ON pr.cus_id = company.id
        LEFT JOIN company_addr ON company.id = company_addr.com_id AND company_addr.valid_end = '0000-00-00'
        WHERE po.id = '".$data['quotation_id']."' AND pr.ven_id = '".$com_id."' AND po.status = '1'
    ");
    
    if (mysqli_num_rows($qa_query) > 0) {
        $linked_source = mysqli_fetch_array($qa_query);
        $use_source_data = true;
        $source_doc_no = 'QA-' . $linked_source['doc_no'];
        
        // Get quotation products
        $prod_query = mysqli_query($db->conn, "
            SELECT type.name as name, model.model_name as model, product.quantity, product.price, product.des
            FROM product
            JOIN type ON product.type = type.id
            LEFT JOIN model ON product.model = model.id
            WHERE product.po_id = '".$data['quotation_id']."'
        ");
        while ($prod = mysqli_fetch_array($prod_query)) {
            $source_products[] = $prod;
        }
        
        $customer = [
            'name' => $linked_source['cust_name'],
            'phone' => $linked_source['cust_phone'],
            'email' => $linked_source['cust_email'],
            'fax' => $linked_source['cust_fax'],
            'adr_tax' => $linked_source['adr_tax'],
            'city_tax' => $linked_source['city_tax'],
            'district_tax' => $linked_source['district_tax'],
            'province_tax' => $linked_source['province_tax'],
            'zip_tax' => $linked_source['zip_tax'],
            'tax' => $linked_source['cust_tax']
        ];
    }
}
// Handle invoice source
elseif ($source_type === 'invoice' && !empty($data['invoice_id'])) {
    $inv_query = mysqli_query($db->conn, "
        SELECT po.id, iv.taxrw as doc_no, DATE_FORMAT(iv.createdate, '%d/%m/%Y') as doc_date, 
               po.vat as doc_vat, po.dis as doc_dis, po.over as doc_over,
               company.name_en as cust_name, company.phone as cust_phone, 
               company.email as cust_email, company_addr.adr_tax, company_addr.city_tax,
               company_addr.district_tax, company_addr.province_tax, company_addr.zip_tax, 
               company.tax as cust_tax, company.fax as cust_fax
        FROM po
        JOIN pr ON po.ref = pr.id
        JOIN company ON pr.cus_id = company.id
        LEFT JOIN company_addr ON company.id = company_addr.com_id AND company_addr.valid_end = '0000-00-00'
        JOIN iv ON po.id = iv.tex
        WHERE po.id = '".$data['invoice_id']."' AND pr.ven_id = '".$com_id."'
    ");
    
    if (mysqli_num_rows($inv_query) > 0) {
        $linked_source = mysqli_fetch_array($inv_query);
        $use_source_data = true;
        $source_doc_no = 'INV-' . $linked_source['doc_no'];
        
        // Get invoice products
        $prod_query = mysqli_query($db->conn, "
            SELECT type.name as name, model.model_name as model, product.quantity, product.price, product.des
            FROM product
            JOIN type ON product.type = type.id
            LEFT JOIN model ON product.model = model.id
            WHERE product.po_id = '".$data['invoice_id']."'
        ");
        while ($prod = mysqli_fetch_array($prod_query)) {
            $source_products[] = $prod;
        }
        
        $customer = [
            'name' => $linked_source['cust_name'],
            'phone' => $linked_source['cust_phone'],
            'email' => $linked_source['cust_email'],
            'fax' => $linked_source['cust_fax'],
            'adr_tax' => $linked_source['adr_tax'],
            'city_tax' => $linked_source['city_tax'],
            'district_tax' => $linked_source['district_tax'],
            'province_tax' => $linked_source['province_tax'],
            'zip_tax' => $linked_source['zip_tax'],
            'tax' => $linked_source['cust_tax']
        ];
    }
}

// If no linked source, use receipt data
if (!$customer) {
    $customer = [
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'fax' => '',
        'adr_tax' => '',
        'city_tax' => '',
        'district_tax' => '',
        'province_tax' => '',
        'zip_tax' => '',
        'tax' => ''
    ];
}

// Payment method - get display name from database
$payment_display = getPaymentMethodDisplayName($db->conn, $data['payment_method'], 'en');

// Status labels
$status_labels = [
    'draft' => 'Draft',
    'confirmed' => 'Confirmed',
    'cancelled' => 'Cancelled'
];
$status_display = $status_labels[$data['status']] ?? 'Confirmed';

// Source type labels
$source_labels = [
    'quotation' => 'From Quotation',
    'invoice' => 'From Invoice',
    'manual' => 'Manual Entry'
];
$source_display = $source_labels[$source_type] ?? 'Manual Entry';

// Build products array and calculate totals
$products = [];
$summary = 0;

if ($use_source_data && count($source_products) > 0) {
    foreach ($source_products as $prod) {
        $total = floatval($prod['price']) * floatval($prod['quantity']);
        $summary += $total;
        $products[] = [
            'model' => $prod['model'] ?? '',
            'name' => $prod['name'],
            'quantity' => $prod['quantity'],
            'price' => $prod['price'],
            'total' => $total,
            'des' => $prod['des']
        ];
    }
    $dis = $linked_source['doc_dis'];
    $vat_rate = $linked_source['doc_vat'];
    $over = $linked_source['doc_over'];
} else {
    // Use receipt's own products
    $que_pro = mysqli_query($db->conn, "
        SELECT type.name as name, model.model_name as model, quantity, product.price as price, product.des as des 
        FROM product 
        JOIN type ON product.type = type.id 
        LEFT JOIN model ON product.model = model.id
        WHERE re_id='".$id."' AND po_id='0' AND so_id='0'
    ");
    while ($prod = mysqli_fetch_array($que_pro)) {
        $total = floatval($prod['price']) * floatval($prod['quantity']);
        $summary += $total;
        $products[] = [
            'model' => $prod['model'] ?? '',
            'name' => $prod['name'],
            'quantity' => $prod['quantity'],
            'price' => $prod['price'],
            'total' => $total,
            'des' => $prod['des']
        ];
    }
    $dis = $data['dis'] ?? 0;
    $vat_rate = $data['vat'] ?? 7;
    $over = $data['over'] ?? 0;
}

// Calculate totals
$disco = $summary * $dis / 100;
$stotal = $summary - $disco;

$overh = 0;
if ($over > 0) {
    $overh = $stotal * $over / 100;
    $stotal = $stotal + $overh;
}

// Only calculate VAT if include_vat is enabled
if ($include_vat) {
    $vat = $stotal * $vat_rate / 100;
    $grandTotal = round($stotal, 2) + round($vat, 2);
} else {
    $vat = 0;
    $grandTotal = round($stotal, 2);
}

// Professional Receipt Template matching Invoice design
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    
    /* Header */
    .header { text-align: center; margin-bottom: 10px; }
    .header img { width: 50px; height: 50px; }
    .company-name { font-size: 14px; font-weight: bold; color: #27ae60; margin-top: 5px; }
    .company-addr { font-size: 10px; color: #444; line-height: 1.4; }
    
    /* Title */
    .title { background: #27ae60; color: #fff; text-align: center; padding: 8px; font-size: 16px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
    
    /* Info Section */
    .info-table { width: 100%; margin-bottom: 10px; }
    .info-table td { vertical-align: top; font-size: 10px; }
    .info-left { width: 55%; }
    .info-right { width: 45%; padding-left: 20px; }
    .rec-box { padding: 4px 0; margin-bottom: 6px; }
    .rec-num { font-size: 13px; font-weight: bold; color: #27ae60; margin: 0; }
    .rec-meta { font-size: 9px; color: #666; margin-top: 2px; }
    .lbl { font-weight: bold; color: #555; width: 60px; }
    .cust-name { font-weight: bold; }
    
    /* Items Table */
    .items { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .items th { background: #27ae60; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
    .items th.r { text-align: right; }
    .items th.c { text-align: center; }
    .items td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .items td.r { text-align: right; }
    .items td.c { text-align: center; }
    .items tr:nth-child(even) { background: #f8f9fa; }
    .desc { font-size: 9px; color: #666; margin-top: 3px; line-height: 1.3; }
    
    /* Totals */
    .summary-section { width: 100%; margin-top: 10px; }
    .summary-section td { vertical-align: top; }
    .payment-info { width: 55%; font-size: 10px; }
    .payment-title { font-weight: bold; color: #27ae60; margin-bottom: 5px; }
    .payment-item { margin-bottom: 4px; line-height: 1.3; }
    .totals-wrap { width: 45%; text-align: right; }
    .totals { width: 220px; margin-left: auto; }
    .totals td { padding: 4px 0; font-size: 10px; }
    .totals .lbl { text-align: right; padding-right: 12px; color: #555; white-space: nowrap; }
    .totals .val { text-align: right; }
    .totals .grand { border-top: 2px solid #27ae60; }
    .totals .grand td { padding: 8px 0; font-size: 12px; font-weight: bold; color: #27ae60; }
    
    /* Words */
    .words { background: #e8f8f5; padding: 8px 10px; font-size: 10px; color: #333; margin: 10px 0; }
    
    /* Terms */
    .terms { border-top: 1px solid #ccc; padding-top: 8px; margin-top: 15px; }
    .terms-title { font-weight: bold; font-size: 10px; color: #27ae60; margin-bottom: 5px; }
    .terms-content { font-size: 9px; color: #555; line-height: 1.4; }
    
    /* Signatures */
    .sigs { margin-top: 30px; }
    .sigs td { width: 50%; text-align: center; padding: 0 10px; vertical-align: bottom; }
    .sig-space { height: 40px; }
    .sig-line { font-size: 10px; font-weight: bold; padding-top: 5px; border-top: 1px solid #333; }
    .sig-name { font-size: 9px; color: #666; margin-top: 3px; }
    .sig-date { font-size: 9px; color: #888; margin-top: 3px; }
    
    /* Status Badge */
    .status { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
    .status-confirmed { background: #27ae60; color: #fff; }
    .status-draft { background: #f39c12; color: #fff; }
    .status-cancelled { background: #e74c3c; color: #fff; }
    .source-tag { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; margin-left: 5px; }
    .source-quotation { background: #f59e0b; color: #fff; }
    .source-invoice { background: #27ae60; color: #fff; }
    .source-manual { background: #6b7280; color: #fff; }
    .vat-mode { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; margin-left: 5px; }
    .vat-included { background: #d4edda; color: #155724; }
    .vat-excluded { background: #f8d7da; color: #721c24; }
</style>

<!-- Header -->
<div class="header">
    <img src="upload/'.htmlspecialchars($logo).'" width="50" height="50"><br>
    <div class="company-name">'.htmlspecialchars($vender['name_en'] ?? '').'</div>
    <div class="company-addr">
        '.htmlspecialchars($vender['adr_tax'] ?? '').' '.htmlspecialchars($vender['city_tax'] ?? '').' '.htmlspecialchars($vender['district_tax'] ?? '').' '.htmlspecialchars($vender['province_tax'] ?? '').' '.htmlspecialchars($vender['zip_tax'] ?? '').'<br>
        Tel: '.htmlspecialchars($vender['phone'] ?? '').' &nbsp; Fax: '.htmlspecialchars($vender['fax'] ?? '').' &nbsp; Email: '.htmlspecialchars($vender['email'] ?? '').' &nbsp; Tax ID: '.htmlspecialchars($vender['tax'] ?? '').'
    </div>
</div>

<!-- Title -->
<div class="title">RECEIPT</div>

<!-- Info Section -->
<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="rec-box">
                <div class="rec-num">REC-'.htmlspecialchars($data['rep_rw']).' <span class="status status-'.strtolower($data['status'] ?? 'confirmed').'">'.htmlspecialchars($status_display).'</span>
                    <span class="source-tag source-'.$source_type.'">'.htmlspecialchars($source_display).'</span>
                    <span class="vat-mode '.($include_vat ? 'vat-included' : 'vat-excluded').'">'.($include_vat ? 'VAT '.$vat_rate.'%' : 'NO VAT').'</span>
                </div>
                <div class="rec-meta">Date: '.htmlspecialchars($data['createdate']).($source_doc_no ? ' &nbsp;|&nbsp; Ref: '.htmlspecialchars($source_doc_no) : '').'</div>
            </div>
            <table>
                <tr><td class="lbl">Customer</td><td class="cust-name">'.htmlspecialchars($customer['name']).'</td></tr>';

if (!empty($customer['adr_tax'])) {
    $html .= '<tr><td class="lbl">Address</td><td>'.htmlspecialchars($customer['adr_tax']).' '.htmlspecialchars($customer['city_tax']).' '.htmlspecialchars($customer['district_tax']).' '.htmlspecialchars($customer['province_tax']).' '.htmlspecialchars($customer['zip_tax']).'</td></tr>';
}
if (!empty($customer['tax'])) {
    $html .= '<tr><td class="lbl">Tax ID</td><td>'.htmlspecialchars($customer['tax']).'</td></tr>';
}

$html .= '
            </table>
        </td>
        <td class="info-right">
            <table>
                <tr><td class="lbl">Tel</td><td>'.htmlspecialchars($customer['phone'] ?? '').'</td></tr>
                <tr><td class="lbl">Email</td><td>'.htmlspecialchars($customer['email'] ?? '').'</td></tr>
                <tr><td class="lbl">Payment</td><td><b>'.htmlspecialchars($payment_display).'</b></td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Items -->
<table class="items">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:14%">Model</th>
        <th style="width:48%">Description</th>
        <th class="c" style="width:8%">Qty</th>
        <th class="r" style="width:12%">Price</th>
        <th class="r" style="width:14%">Amount</th>
    </tr>';

$cot = 1;
foreach ($products as $prod) {
    $html .= '<tr>
        <td>'.$cot.'</td>
        <td>'.htmlspecialchars($prod['model']).'</td>
        <td>'.htmlspecialchars($prod['name']);
    if (!empty($prod['des'])) {
        $safe_des = strip_tags($prod['des'], '<br><b><strong><i><em><u>');
        $html .= '<div class="desc">'.$safe_des.'</div>';
    }
    $html .= '</td>
        <td class="c">'.intval($prod['quantity']).'</td>
        <td class="r">'.number_format($prod['price'], 2).'</td>
        <td class="r">'.number_format($prod['total'], 2).'</td>
    </tr>';
    $cot++;
}

$html .= '</table>

<!-- Summary Section -->
<table class="summary-section">
    <tr>
        <td class="payment-info">
            <div class="payment-title">Payment Received</div>
            <div class="payment-item">
                <b>Method:</b> '.htmlspecialchars($payment_display).'<br>
                <b>Status:</b> '.htmlspecialchars($status_display).'<br>
                <b>Date:</b> '.htmlspecialchars($data['createdate']).'<br>
                <b>Source:</b> '.htmlspecialchars($source_display).($source_doc_no ? ' ('.$source_doc_no.')' : '').'
            </div>
        </td>
        <td class="totals-wrap">
            <table class="totals">
                <tr><td class="lbl">Subtotal</td><td class="val">'.number_format($summary, 2).'</td></tr>';

if ($dis > 0) {
    $html .= '<tr><td class="lbl">Discount '.htmlspecialchars($dis).'%</td><td class="val">-'.number_format($disco, 2).'</td></tr>';
}

if ($over > 0) {
    $html .= '<tr><td class="lbl">Overhead '.htmlspecialchars($over).'%</td><td class="val">+'.number_format($overh, 2).'</td></tr>';
}

$html .= '
                <tr><td class="lbl">Net Amount</td><td class="val">'.number_format($stotal, 2).'</td></tr>';

// Only show VAT row if include_vat is enabled
if ($include_vat) {
    $html .= '<tr><td class="lbl">VAT '.htmlspecialchars($vat_rate).'%</td><td class="val">+'.number_format($vat, 2).'</td></tr>';
} else {
    $html .= '<tr><td class="lbl" style="color:#e74c3c;">No VAT</td><td class="val" style="color:#e74c3c;">-</td></tr>';
}

$html .= '
                <tr class="grand"><td class="lbl">Total Received</td><td class="val">'.number_format($grandTotal, 2).'</td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Amount in Words -->
<div class="words"><b>Amount in words:</b> '.bahtEng($grandTotal).'</div>

<!-- Terms -->
'.(!empty($vender['term']) ? '
<div class="terms">
    <div class="terms-title">Terms & Conditions</div>
    <div class="terms-content">'.nl2br(htmlspecialchars($vender['term'])).'</div>
</div>' : '').'

<!-- Signatures -->
<table class="sigs" width="100%">
    <tr>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Received By</div>
            <div class="sig-name">'.htmlspecialchars($customer['name']).'</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Authorized Signature</div>
            <div class="sig-name">'.htmlspecialchars($vender['name_en'] ?? '').'</div>
            <div class="sig-date">Date: '.date("d/m/Y").'</div>
        </td>
    </tr>
</table>';

// Generate PDF
include("MPDF/mpdf.php");

$mpdf = new mPDF('th', 'A4', 0, 'Arial', 12, 12, 12, 12, 0, 0);
$mpdf->SetDisplayMode('fullpage');
$mpdf->WriteHTML($html);
$mpdf->Output("REC-".$filename.".pdf", "I");
exit;
?>