<?php
/**
 * Invoice PDF Generator
 * Modern minimal design with improved security
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.current.php");
require_once("inc/security.php");

$db = new DbConn($config);
$db->checkSecurity();

// Validate input
$id = input('id', 'int', ['required' => true, 'min' => 1]);

if (!$id) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Error</h2><p>Invalid invoice ID</p></div>');
}

// Fetch invoice data with prepared-style query (escaped)
$id_safe = mysqli_real_escape_string($db->conn, $id);
$session_com_id = mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? 0);

$query = mysqli_query($db->conn, "
    SELECT 
        po.name as name, over, ven_id, dis, vat, 
        taxrw as tax2, tax, pr.cus_id as cus_id, payby, des, brandven, valid_pay,
        DATE_FORMAT(iv.createdate,'%d/%m/%Y') as date,
        DATE_FORMAT(deliver_date,'%d/%m/%Y') as deliver_date,
        ref, pic, status 
    FROM pr 
    JOIN po ON pr.id = po.ref  
    JOIN iv ON po.id = iv.tex 
    WHERE po.id = '{$id_safe}' 
    AND status > '2' 
    AND (pr.cus_id = '{$session_com_id}' OR ven_id = '{$session_com_id}') 
    AND po_id_new = ''
");

if (!$query || mysqli_num_rows($query) != 1) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Invoice Not Found</h2><p>The requested invoice does not exist or you do not have permission to view it.</p></div>');
}

$data = mysqli_fetch_array($query);

// Fetch vendor info
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT name_en, adr_tax, city_tax, district_tax, tax, province_tax, zip_tax, fax, phone, email, term, logo 
    FROM company 
    JOIN company_addr ON company.id = company_addr.com_id 
    WHERE company.id = '" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "' 
    AND valid_end = '0000-00-00'
"));

// Fetch customer info
$customer = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT name_en, name_sh, adr_tax, city_tax, district_tax, province_tax, tax, zip_tax, fax, phone, email 
    FROM company 
    JOIN company_addr ON company.id = company_addr.com_id 
    WHERE company.id = '" . mysqli_real_escape_string($db->conn, $data['payby']) . "' 
    AND valid_end = '0000-00-00'
"));

// Get logo
if ($data['brandven'] == 0) {
    $logo = $vender['logo'] ?? '';
} else {
    $bandlogo = mysqli_fetch_array(mysqli_query($db->conn, "
        SELECT logo FROM brand WHERE id = '" . mysqli_real_escape_string($db->conn, $data['brandven']) . "'
    "));
    $logo = $bandlogo['logo'] ?? '';
}

// Check if labour columns needed
$cklabour = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT MAX(activelabour) as cklabour 
    FROM product 
    JOIN type ON product.type = type.id 
    WHERE po_id = '{$id_safe}'
"));
$hasLabour = ($cklabour['cklabour'] == 1);

// Fetch products
$que_pro = mysqli_query($db->conn, "
    SELECT 
        type.name as name, product.price as price, product.des as des,
        valuelabour, activelabour, discount, model.model_name as model,
        quantity, pack_quantity 
    FROM product 
    JOIN type ON product.type = type.id 
    JOIN model ON product.model = model.id 
    WHERE po_id = '{$id_safe}'
");

// Build product rows and calculate totals
$products = [];
$summary = 0;
while ($row = mysqli_fetch_array($que_pro)) {
    $equip = $row['price'] * $row['quantity'];
    $labour1 = $row['valuelabour'] * $row['activelabour'];
    $labour = $labour1 * $row['quantity'];
    $total = $hasLabour ? ($equip + $labour) : $equip;
    $summary += $total;
    
    $products[] = [
        'model' => $row['model'],
        'name' => $row['name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'equip' => $equip,
        'labour1' => $labour1,
        'labour' => $labour,
        'total' => $total,
        'des' => $row['des']
    ];
}

// Calculate totals
$disco = $summary * $data['dis'] / 100;
$stotal = $summary - $disco;

// Overhead calculation
$overh = 0;
if ($data['over'] > 0) {
    $overh = $stotal * $data['over'] / 100;
    $stotal = $stotal + $overh;
}

$vat = $stotal * $data['vat'] / 100;
$grandTotal = round($stotal, 2) + round($vat, 2);

// Modern Minimal HTML Template
$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; line-height: 1.5; }
    .container { max-width: 100%; margin: 0 auto; }
    
    /* Header */
    .header { margin-bottom: 30px; }
    .header-flex { display: table; width: 100%; }
    .logo-cell { display: table-cell; width: 80px; vertical-align: top; }
    .logo-cell img { max-height: 60px; max-width: 75px; }
    .company-cell { display: table-cell; vertical-align: top; text-align: right; padding-left: 20px; }
    .company-name { font-size: 18px; font-weight: bold; color: #1a1a1a; margin-bottom: 5px; }
    .company-details { font-size: 10px; color: #666; line-height: 1.4; }
    
    /* Invoice Title */
    .invoice-title { 
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white; 
        text-align: center; 
        padding: 12px 0; 
        font-size: 22px; 
        font-weight: 600;
        letter-spacing: 3px;
        margin: 20px 0;
        border-radius: 4px;
    }
    
    /* Info Grid */
    .info-grid { display: table; width: 100%; margin-bottom: 20px; font-size: 11px; }
    .info-left { display: table-cell; width: 60%; vertical-align: top; }
    .info-right { display: table-cell; width: 40%; vertical-align: top; padding-left: 20px; }
    .info-row { margin-bottom: 6px; }
    .info-label { font-weight: 600; color: #555; display: inline-block; min-width: 70px; }
    .info-value { color: #333; }
    
    /* Invoice Details Box */
    .invoice-box { 
        background: #f8f9fa; 
        border-left: 4px solid #3498db;
        padding: 12px 15px;
        margin-bottom: 5px;
    }
    .invoice-number { font-size: 16px; font-weight: bold; color: #2c3e50; }
    .invoice-meta { font-size: 10px; color: #666; margin-top: 5px; }
    
    /* Table */
    .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 10px; }
    .items-table th { 
        background: #2c3e50; 
        color: white; 
        padding: 10px 8px; 
        text-align: left;
        font-weight: 500;
        font-size: 10px;
    }
    .items-table th.right { text-align: right; }
    .items-table th.center { text-align: center; }
    .items-table td { padding: 10px 8px; border-bottom: 1px solid #eee; }
    .items-table td.right { text-align: right; }
    .items-table td.center { text-align: center; }
    .items-table tr:nth-child(even) { background: #fafafa; }
    .items-table tr:hover { background: #f0f7ff; }
    .item-desc { font-size: 9px; color: #888; margin-top: 3px; font-style: italic; }
    
    /* Totals */
    .totals-section { margin: 20px 0; }
    .totals-table { width: 280px; margin-left: auto; font-size: 11px; }
    .totals-row { display: table; width: 100%; padding: 6px 0; }
    .totals-label { display: table-cell; width: 60%; text-align: right; padding-right: 15px; color: #666; }
    .totals-value { display: table-cell; width: 40%; text-align: right; font-weight: 500; }
    .totals-divider { border-top: 1px solid #ddd; margin: 8px 0; }
    .grand-total { 
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 12px 15px;
        border-radius: 4px;
        margin-top: 10px;
    }
    .grand-total .totals-label { color: white; font-weight: 500; }
    .grand-total .totals-value { font-size: 14px; font-weight: bold; }
    
    /* Amount in words */
    .amount-words { 
        background: #f8f9fa; 
        padding: 10px 15px; 
        font-size: 10px; 
        color: #555;
        border-radius: 4px;
        margin: 15px 0;
        font-style: italic;
    }
    
    /* Terms */
    .terms { 
        margin: 20px 0; 
        padding: 15px; 
        background: #fff;
        border: 1px solid #eee;
        border-radius: 4px;
    }
    .terms-title { font-weight: 600; font-size: 11px; color: #2c3e50; margin-bottom: 8px; }
    .terms-content { font-size: 9px; color: #666; line-height: 1.6; }
    
    /* Signatures */
    .signatures { display: table; width: 100%; margin-top: 40px; }
    .sig-box { display: table-cell; width: 50%; text-align: center; padding: 0 20px; }
    .sig-company { font-size: 10px; color: #666; margin-bottom: 50px; }
    .sig-line { border-top: 1px solid #333; padding-top: 8px; margin: 0 20px; }
    .sig-title { font-size: 10px; font-weight: 600; color: #333; }
    .sig-date { font-size: 9px; color: #888; margin-top: 5px; }
</style>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-flex">
            <div class="logo-cell">
                <img src="upload/' . e($logo) . '">
            </div>
            <div class="company-cell">
                <div class="company-name">' . e($vender['name_en'] ?? '') . '</div>
                <div class="company-details">
                    ' . e($vender['adr_tax'] ?? '') . '<br>
                    ' . e($vender['city_tax'] ?? '') . ' ' . e($vender['district_tax'] ?? '') . ' ' . e($vender['province_tax'] ?? '') . ' ' . e($vender['zip_tax'] ?? '') . '<br>
                    Tel: ' . e($vender['phone'] ?? '') . ' | Fax: ' . e($vender['fax'] ?? '') . '<br>
                    Email: ' . e($vender['email'] ?? '') . ' | Tax ID: ' . e($vender['tax'] ?? '') . '
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invoice Title -->
    <div class="invoice-title">INVOICE</div>
    
    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-left">
            <div class="invoice-box">
                <div class="invoice-number">INV-' . e($data['tax2']) . '</div>
                <div class="invoice-meta">
                    Date: ' . e($data['date']) . ' | Ref: PO-' . e($data['tax']) . '
                </div>
            </div>
            <div style="margin-top: 15px;">
                <div class="info-row"><span class="info-label">Customer:</span> <strong>' . e($customer['name_en'] ?? '') . '</strong></div>
                <div class="info-row"><span class="info-label">Address:</span> ' . e($customer['adr_tax'] ?? '') . '</div>
                <div class="info-row"><span class="info-label"></span> ' . e($customer['city_tax'] ?? '') . ' ' . e($customer['district_tax'] ?? '') . ' ' . e($customer['province_tax'] ?? '') . ' ' . e($customer['zip_tax'] ?? '') . '</div>
                <div class="info-row"><span class="info-label">Tax ID:</span> ' . e($customer['tax'] ?? '') . '</div>
            </div>
        </div>
        <div class="info-right">
            <div class="info-row"><span class="info-label">Tel:</span> ' . e($customer['phone'] ?? '') . '</div>
            <div class="info-row"><span class="info-label">Fax:</span> ' . e($customer['fax'] ?? '') . '</div>
            <div class="info-row"><span class="info-label">Email:</span> ' . e($customer['email'] ?? '') . '</div>
        </div>
    </div>
    
    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:15%">Model</th>
                <th style="width:' . ($hasLabour ? '22%' : '47%') . '">Description</th>
                <th class="center" style="width:6%">Qty</th>
                <th class="right" style="width:12%">Unit Price</th>';

if ($hasLabour) {
    $html .= '
                <th class="right" style="width:12%">Equipment</th>
                <th class="right" style="width:10%">Labour</th>
                <th class="right" style="width:12%">Labour Total</th>';
}

$html .= '
                <th class="right" style="width:12%">Amount</th>
            </tr>
        </thead>
        <tbody>';

$cot = 1;
foreach ($products as $prod) {
    $html .= '
            <tr>
                <td>' . $cot . '</td>
                <td>' . e($prod['model']) . '</td>
                <td>' . e($prod['name']);
    if (!empty($prod['des'])) {
        $html .= '<div class="item-desc">' . e($prod['des']) . '</div>';
    }
    $html .= '</td>
                <td class="center">' . intval($prod['quantity']) . '</td>
                <td class="right">' . number_format($prod['price'], 2) . '</td>';
    
    if ($hasLabour) {
        $html .= '
                <td class="right">' . number_format($prod['equip'], 2) . '</td>
                <td class="right">' . number_format($prod['labour1'], 2) . '</td>
                <td class="right">' . number_format($prod['labour'], 2) . '</td>';
    }
    
    $html .= '
                <td class="right">' . number_format($prod['total'], 2) . '</td>
            </tr>';
    $cot++;
}

$html .= '
        </tbody>
    </table>
    
    <!-- Totals Section -->
    <div class="totals-section">
        <div class="totals-table">
            <div class="totals-row">
                <div class="totals-label">Subtotal</div>
                <div class="totals-value">' . number_format($summary, 2) . '</div>
            </div>';

if ($data['dis'] > 0) {
    $html .= '
            <div class="totals-row">
                <div class="totals-label">Discount (' . e($data['dis']) . '%)</div>
                <div class="totals-value">-' . number_format($disco, 2) . '</div>
            </div>';
}

if ($data['over'] > 0) {
    $html .= '
            <div class="totals-divider"></div>
            <div class="totals-row">
                <div class="totals-label">After Discount</div>
                <div class="totals-value">' . number_format($stotal - $overh, 2) . '</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">Overhead (' . e($data['over']) . '%)</div>
                <div class="totals-value">+' . number_format($overh, 2) . '</div>
            </div>';
}

$html .= '
            <div class="totals-divider"></div>
            <div class="totals-row">
                <div class="totals-label">Net Amount</div>
                <div class="totals-value">' . number_format($stotal, 2) . '</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">VAT (' . e($data['vat']) . '%)</div>
                <div class="totals-value">+' . number_format($vat, 2) . '</div>
            </div>
            <div class="grand-total">
                <div class="totals-row">
                    <div class="totals-label">Grand Total</div>
                    <div class="totals-value">' . number_format($grandTotal, 2) . '</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Amount in Words -->
    <div class="amount-words">
        <strong>Amount in words:</strong> ' . bahtEng($grandTotal) . '
    </div>
    
    <!-- Terms & Conditions -->
    ' . (!empty($vender['term']) ? '
    <div class="terms">
        <div class="terms-title">Terms & Conditions</div>
        <div class="terms-content">' . nl2br(e($vender['term'])) . '</div>
    </div>' : '') . '
    
    <!-- Signatures -->
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-company">' . e($customer['name_en'] ?? '') . '</div>
            <div class="sig-line">
                <div class="sig-title">Authorized Signature</div>
                <div class="sig-date">Date: ____/____/________</div>
            </div>
        </div>
        <div class="sig-box">
            <div class="sig-company">' . e($vender['name_en'] ?? '') . '</div>
            <div class="sig-line">
                <div class="sig-title">Authorized Signature</div>
                <div class="sig-date">Date: ____/____/________</div>
            </div>
        </div>
    </div>
</div>';

// Generate PDF
include("MPDF/mpdf.php");

$mpdf = new mPDF('th', 'A4', 0, 'Helvetica', 15, 15, 15, 15, 0, 0);
$mpdf->SetDisplayMode('fullpage');
$mpdf->WriteHTML($html);
$mpdf->Output("INV-" . $data['tax2'] . "-" . ($customer['name_sh'] ?? 'invoice') . ".pdf", "I");
exit;
