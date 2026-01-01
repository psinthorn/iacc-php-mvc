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

// Debug mode
$debug = false;

// Fetch invoice data with prepared-style query (escaped)
$id_safe = mysqli_real_escape_string($db->conn, $id);
$session_com_id = mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? 0);

$sql = "
    SELECT 
        po.name as name, po.over, pr.ven_id, po.dis, po.vat, 
        iv.taxrw as tax2, po.tax, pr.cus_id as cus_id, pr.payby, pr.des, po.bandven, po.valid_pay,
        DATE_FORMAT(iv.createdate,'%d/%m/%Y') as date,
        DATE_FORMAT(po.deliver_date,'%d/%m/%Y') as deliver_date,
        po.ref, po.pic, pr.status 
    FROM pr 
    JOIN po ON pr.id = po.ref  
    JOIN iv ON po.id = iv.tex 
    WHERE po.id = '{$id_safe}' 
    AND pr.status > '2' 
    AND po.po_id_new = ''
    AND (pr.cus_id = '{$session_com_id}' OR pr.ven_id = '{$session_com_id}' OR pr.payby = '{$session_com_id}')
";

$query = mysqli_query($db->conn, $sql);

if ($debug && (!$query || mysqli_num_rows($query) != 1)) {
    echo "<h2>Debug Info</h2>";
    echo "<p><strong>ID:</strong> {$id_safe}</p>";
    echo "<p><strong>Query:</strong></p><pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p><strong>Error:</strong> " . mysqli_error($db->conn) . "</p>";
    echo "<p><strong>Rows:</strong> " . ($query ? mysqli_num_rows($query) : 'query failed') . "</p>";
    
    // Test simpler query
    $test = mysqli_query($db->conn, "SELECT id, status, po_id_new FROM po WHERE id = '{$id_safe}'");
    echo "<p><strong>PO exists:</strong></p><pre>";
    if ($test && mysqli_num_rows($test) > 0) {
        print_r(mysqli_fetch_assoc($test));
    } else {
        echo "PO not found";
    }
    echo "</pre>";
    
    // Test iv table
    $test2 = mysqli_query($db->conn, "SELECT * FROM iv WHERE tex = '{$id_safe}'");
    echo "<p><strong>IV record:</strong></p><pre>";
    if ($test2 && mysqli_num_rows($test2) > 0) {
        print_r(mysqli_fetch_assoc($test2));
    } else {
        echo "IV not found";
    }
    echo "</pre>";
    exit;
}

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

// Fetch payment methods (bank accounts) for vendor
$paymentMethods = [];
$pm_query = mysqli_query($db->conn, "
    SELECT method_type, method_name, account_name, account_number, branch, qr_image 
    FROM payment_methods 
    WHERE com_id = '" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "' 
    AND is_active = 1 
    ORDER BY is_default DESC, sort_order ASC
");
while ($pm = mysqli_fetch_array($pm_query)) {
    $paymentMethods[] = $pm;
}

// Get logo - column is 'bandven' not 'brandven'
if ($data['bandven'] == 0) {
    $logo = $vender['logo'] ?? '';
} else {
    $bandlogo = mysqli_fetch_array(mysqli_query($db->conn, "
        SELECT logo FROM brand WHERE id = '" . mysqli_real_escape_string($db->conn, $data['bandven']) . "'
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

// Clean Invoice Template with Improved Colors
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    
    /* Header */
    .header { text-align: center; margin-bottom: 10px; }
    .header img { width: 50px; height: 50px; }
    .company-name { font-size: 14px; font-weight: bold; color: #1a5276; margin-top: 5px; }
    .company-addr { font-size: 10px; color: #444; line-height: 1.4; }
    
    /* Title */
    .title { background: #1a5276; color: #fff; text-align: center; padding: 8px; font-size: 16px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
    
    /* Info Section */
    .info-table { width: 100%; margin-bottom: 10px; }
    .info-table td { vertical-align: top; font-size: 10px; }
    .info-left { width: 55%; }
    .info-right { width: 45%; padding-left: 20px; }
    .inv-box { padding: 4px 0; margin-bottom: 6px; }
    .inv-num { font-size: 13px; font-weight: bold; color: #1a5276; margin: 0; }
    .inv-meta { font-size: 9px; color: #666; margin-top: 2px; }
    .lbl { font-weight: bold; color: #555; width: 55px; }
    .cust-name { font-weight: bold; }
    
    /* Items Table */
    .items { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .items th { background: #1a5276; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
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
    .bank-info { width: 55%; font-size: 10px; }
    .bank-title { font-weight: bold; color: #1a5276; margin-bottom: 5px; }
    .bank-item { margin-bottom: 4px; line-height: 1.3; }
    .bank-name { font-weight: bold; }
    .totals-wrap { width: 45%; text-align: right; }
    .totals { width: 220px; margin-left: auto; }
    .totals td { padding: 4px 0; font-size: 10px; }
    .totals .lbl { text-align: right; padding-right: 12px; color: #555; white-space: nowrap; }
    .totals .val { text-align: right; }
    .totals .grand { border-top: 2px solid #1a5276; }
    .totals .grand td { padding: 8px 0; font-size: 12px; font-weight: bold; color: #1a5276; }
    
    /* Words */
    .words { background: #eaf2f8; padding: 8px 10px; font-size: 10px; color: #333; margin: 10px 0; }
    
    /* Terms */
    .terms { border-top: 1px solid #ccc; padding-top: 8px; margin-top: 15px; }
    .terms-title { font-weight: bold; font-size: 10px; color: #1a5276; margin-bottom: 5px; }
    .terms-content { font-size: 9px; color: #555; line-height: 1.4; }
    
    /* Signatures */
    .sigs { margin-top: 30px; }
    .sigs td { width: 50%; text-align: center; padding: 0 25px; vertical-align: bottom; }
    .sig-name { font-size: 9px; color: #666; margin-bottom: 40px; }
    .sig-line { border-top: 1px solid #333; padding-top: 5px; font-size: 10px; font-weight: bold; }
    .sig-date { font-size: 9px; color: #888; margin-top: 3px; }
</style>

<!-- Header -->
<div class="header">
    <img src="upload/' . e($logo) . '" width="50" height="50"><br>
    <div class="company-name">' . e($vender['name_en'] ?? '') . '</div>
    <div class="company-addr">
        ' . e($vender['adr_tax'] ?? '') . ' ' . e($vender['city_tax'] ?? '') . ' ' . e($vender['district_tax'] ?? '') . ' ' . e($vender['province_tax'] ?? '') . ' ' . e($vender['zip_tax'] ?? '') . '<br>
        Tel: ' . e($vender['phone'] ?? '') . ' &nbsp; Fax: ' . e($vender['fax'] ?? '') . ' &nbsp; Email: ' . e($vender['email'] ?? '') . ' &nbsp; Tax ID: ' . e($vender['tax'] ?? '') . '
    </div>
</div>

<!-- Title -->
<div class="title">INVOICE</div>

<!-- Info Section -->
<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="inv-box">
                <div class="inv-num">INV-' . e($data['tax2']) . '</div>
                <div class="inv-meta">Date: ' . e($data['date']) . ' &nbsp;|&nbsp; Ref: PO-' . e($data['tax']) . '</div>
            </div>
            <table>
                <tr><td class="lbl">Customer</td><td class="cust-name">' . e($customer['name_en'] ?? '') . '</td></tr>
                <tr><td class="lbl">Address</td><td>' . e($customer['adr_tax'] ?? '') . ' ' . e($customer['city_tax'] ?? '') . ' ' . e($customer['district_tax'] ?? '') . ' ' . e($customer['province_tax'] ?? '') . ' ' . e($customer['zip_tax'] ?? '') . '</td></tr>
                <tr><td class="lbl">Tax ID</td><td>' . e($customer['tax'] ?? '') . '</td></tr>
            </table>
        </td>
        <td class="info-right">
            <table>
                <tr><td class="lbl">Tel</td><td>' . e($customer['phone'] ?? '') . '</td></tr>
                <tr><td class="lbl">Fax</td><td>' . e($customer['fax'] ?? '') . '</td></tr>
                <tr><td class="lbl">Email</td><td>' . e($customer['email'] ?? '') . '</td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Items -->
<table class="items">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:14%">Model</th>
        <th style="width:' . ($hasLabour ? '28%' : '52%') . '">Description</th>
        <th class="c" style="width:6%">Qty</th>
        <th class="r" style="width:10%">Price</th>';

if ($hasLabour) {
    $html .= '
        <th class="r" style="width:10%">Equipment</th>
        <th class="r" style="width:8%">Labour</th>
        <th class="r" style="width:10%">L.Total</th>';
}

$html .= '
        <th class="r" style="width:10%">Amount</th>
    </tr>';

$cot = 1;
foreach ($products as $prod) {
    $html .= '<tr>
        <td>' . $cot . '</td>
        <td>' . e($prod['model']) . '</td>
        <td>' . e($prod['name']);
    if (!empty($prod['des'])) {
        $safe_des = strip_tags($prod['des'], '<br><b><strong><i><em><u>');
        $html .= '<div class="desc">' . $safe_des . '</div>';
    }
    $html .= '</td>
        <td class="c">' . intval($prod['quantity']) . '</td>
        <td class="r">' . number_format($prod['price'], 2) . '</td>';
    
    if ($hasLabour) {
        $html .= '
        <td class="r">' . number_format($prod['equip'], 2) . '</td>
        <td class="r">' . number_format($prod['labour1'], 2) . '</td>
        <td class="r">' . number_format($prod['labour'], 2) . '</td>';
    }
    
    $html .= '
        <td class="r">' . number_format($prod['total'], 2) . '</td>
    </tr>';
    $cot++;
}
$html .= '</table>

<!-- Summary Section -->
<table class="summary-section">
    <tr>
        <td class="bank-info">
            <div class="bank-title">Payment Information</div>';

// Display payment methods
if (!empty($paymentMethods)) {
    foreach ($paymentMethods as $pm) {
        if ($pm['method_type'] == 'bank') {
            $html .= '
            <div class="bank-item">
                <span class="bank-name">' . e($pm['method_name']) . '</span><br>
                Account: ' . e($pm['account_number']) . '<br>
                Name: ' . e($pm['account_name']) . '
                ' . (!empty($pm['branch']) ? '<br>Branch: ' . e($pm['branch']) : '') . '
            </div>';
        } elseif ($pm['method_type'] == 'qrcode' && !empty($pm['qr_image'])) {
            $html .= '
            <div class="bank-item">
                <span class="bank-name">' . e($pm['method_name']) . '</span><br>
                <img src="' . e($pm['qr_image']) . '" width="80" height="80">
            </div>';
        }
    }
} else {
    $html .= '<div class="bank-item">Please contact us for payment details.</div>';
}

$html .= '
        </td>
        <td class="totals-wrap">
            <table class="totals">
                <tr><td class="lbl">Subtotal</td><td class="val">' . number_format($summary, 2) . '</td></tr>';

if ($data['dis'] > 0) {
    $html .= '<tr><td class="lbl">Discount ' . e($data['dis']) . '%</td><td class="val">-' . number_format($disco, 2) . '</td></tr>';
}

if ($data['over'] > 0) {
    $html .= '<tr><td class="lbl">Overhead ' . e($data['over']) . '%</td><td class="val">+' . number_format($overh, 2) . '</td></tr>';
}

$html .= '
        <tr><td class="lbl">Net Amount</td><td class="val">' . number_format($stotal, 2) . '</td></tr>
        <tr><td class="lbl">VAT ' . e($data['vat']) . '%</td><td class="val">+' . number_format($vat, 2) . '</td></tr>
        <tr class="grand"><td class="lbl">Grand Total</td><td class="val">' . number_format($grandTotal, 2) . '</td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Amount in Words -->
<div class="words"><b>Amount in words:</b> ' . bahtEng($grandTotal) . '</div>

<!-- Terms -->
' . (!empty($vender['term']) ? '
<div class="terms">
    <div class="terms-title">Terms & Conditions</div>
    <div class="terms-content">' . nl2br(e($vender['term'])) . '</div>
</div>' : '') . '

<!-- Signatures -->
<table class="sigs" width="100%">
    <tr>
        <td>
            <div class="sig-name">' . e($customer['name_en'] ?? '') . '</div>
            <div class="sig-line">Authorized Signature</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
        <td>
            <div class="sig-name">' . e($vender['name_en'] ?? '') . '</div>
            <div class="sig-line">Authorized Signature</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
    </tr>
</table>';

// Generate PDF
include("MPDF/mpdf.php");

$mpdf = new mPDF('th', 'A4', 0, 'Arial', 12, 12, 12, 12, 0, 0);
$mpdf->SetDisplayMode('fullpage');
$mpdf->WriteHTML($html);
$mpdf->Output("INV-" . $data['tax2'] . "-" . ($customer['name_sh'] ?? 'invoice') . ".pdf", "I");
exit;

