<?php
// Error reporting settings - MUST NOT output errors to browser in PDF generation
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('display_startup_errors', 0);
ini_set('error_log', __DIR__ . '/../../php-error.log');
error_reporting(E_ALL);

if (ob_get_level()) ob_end_clean();
ob_start();

/**
 * Delivery Note PDF Generator
 * Replaces legacy rec.php
 * Supports PO-based deliveries and standalone sendout deliveries
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.current.php");

if (!isset($config)) {
    $config = [
        'hostname' => getenv('DB_HOST') ?: 'mysql',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: 'root',
        'dbname'   => getenv('DB_DATABASE') ?: 'iacc',
    ];
}
$db = new DbConn($config);
$db->checkSecurity();

$id = sql_int($_REQUEST['id'] ?? 0);
$mode = $_REQUEST['modep'] ?? '';
$com_id = sql_int($_SESSION['com_id']);

// Fetch delivery detail based on mode
if ($mode === 'ad') {
    // Standalone sendout delivery
    $query = mysqli_query($db->conn, "
        SELECT sendoutitem.id, sendoutitem.tmp, sendoutitem.ven_id, sendoutitem.cus_id,
               company.name_en, company.name_sh,
               DATE_FORMAT(deliver.deliver_date,'%d/%m/%Y') as deliver_date,
               deliver.id as deliv_id
        FROM sendoutitem
        JOIN deliver ON sendoutitem.id = deliver.out_id
        JOIN company ON sendoutitem.cus_id = company.id
        WHERE deliver.id = '" . sql_int($id) . "'
        AND (sendoutitem.cus_id = '$com_id' OR sendoutitem.ven_id = '$com_id')
        LIMIT 1
    ");
} else {
    // PO-based delivery
    $query = mysqli_query($db->conn, "
        SELECT po.name, po.id as po_id, po.tax as po_number, pr.ven_id, pr.cus_id, pr.des,
               po.valid_pay, po.vat, po.dis, po.over,
               deliver.id as deliv_id, DATE_FORMAT(deliver.deliver_date,'%d/%m/%Y') as deliver_date,
               company.name_en, company.name_sh
        FROM pr
        JOIN po ON pr.id = po.ref
        JOIN deliver ON po.id = deliver.po_id
        LEFT JOIN company ON pr.cus_id = company.id
        WHERE deliver.id = '" . sql_int($id) . "'
        AND (pr.cus_id = '$com_id' OR pr.ven_id = '$com_id')
        AND po_id_new = ''
        LIMIT 1
    ");
}

if (!$query || mysqli_num_rows($query) != 1) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Delivery Note Not Found</h2><p>The requested delivery note does not exist or you do not have permission to view it.</p></div>');
}

$data = mysqli_fetch_array($query);

// Fetch vendor (logged-in company) info with address
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company_addr.adr_tax, company_addr.city_tax, company_addr.district_tax,
           company_addr.province_tax, company.tax, company_addr.zip_tax, company.fax, company.phone,
           company.email, company.logo, company.term
    FROM company
    LEFT JOIN company_addr ON company.id = company_addr.com_id AND company_addr.deleted_at IS NULL
    WHERE company.id = '$com_id'
    ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC,
             company_addr.valid_start DESC
    LIMIT 1
"));

$logo = $vender['logo'] ?? '';

// Fetch customer info with address
$cust_id = $mode === 'ad' ? $data['cus_id'] : $data['cus_id'];
$customer_query = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company.phone, company.email, company.fax, company.tax,
           company_addr.adr_tax, company_addr.city_tax, company_addr.district_tax,
           company_addr.province_tax, company_addr.zip_tax
    FROM company
    LEFT JOIN company_addr ON company.id = company_addr.com_id AND company_addr.deleted_at IS NULL
    WHERE company.id = '" . sql_int($cust_id) . "'
    ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC,
             company_addr.valid_start DESC
    LIMIT 1
"));

$customer = [
    'name' => $customer_query['name_en'] ?? ($data['name_en'] ?? ''),
    'phone' => $customer_query['phone'] ?? '',
    'email' => $customer_query['email'] ?? '',
    'fax' => $customer_query['fax'] ?? '',
    'tax' => $customer_query['tax'] ?? '',
    'adr_tax' => $customer_query['adr_tax'] ?? '',
    'city_tax' => $customer_query['city_tax'] ?? '',
    'district_tax' => $customer_query['district_tax'] ?? '',
    'province_tax' => $customer_query['province_tax'] ?? '',
    'zip_tax' => $customer_query['zip_tax'] ?? '',
];

// Fetch products with serial numbers
if ($mode === 'ad') {
    $prod_query = mysqli_query($db->conn, "
        SELECT type.name as type_name, product.des, product.price, product.discount,
               model.model_name, store.s_n, DATE_FORMAT(store_sale.warranty,'%d/%m/%Y') as warranty,
               product.quantity, product.pack_quantity
        FROM product
        JOIN store ON product.pro_id = store.pro_id
        LEFT JOIN type ON product.type = type.id
        LEFT JOIN model ON product.model = model.id
        JOIN store_sale ON store.id = store_sale.st_id
        WHERE product.so_id = '" . sql_int($data['id']) . "'
    ");
} else {
    $prod_query = mysqli_query($db->conn, "
        SELECT type.name as type_name, product.des, product.price, product.discount,
               model.model_name, store.s_n, DATE_FORMAT(store_sale.warranty,'%d/%m/%Y') as warranty,
               product.quantity, product.pack_quantity, product.activelabour, product.valuelabour
        FROM product
        JOIN store ON product.pro_id = store.pro_id
        LEFT JOIN type ON product.type = type.id
        LEFT JOIN model ON product.model = model.id
        JOIN store_sale ON store.id = store_sale.st_id
        WHERE product.po_id = '" . sql_int($data['po_id']) . "'
    ");
}

$products = [];
$summary = 0;
while ($prod = mysqli_fetch_array($prod_query)) {
    $qty = intval($prod['quantity']) * max(1, intval($prod['pack_quantity']));
    $price = floatval($prod['price']);
    $disc = floatval($prod['discount']);
    $lineTotal = $qty * $price * (1 - $disc / 100);
    $summary += $lineTotal;
    $products[] = [
        'type_name' => $prod['type_name'] ?? '',
        'model_name' => $prod['model_name'] ?? '',
        's_n' => $prod['s_n'] ?? '',
        'warranty' => $prod['warranty'] ?? '',
        'des' => $prod['des'] ?? '',
        'quantity' => $qty,
        'price' => $price,
        'discount' => $disc,
        'total' => $lineTotal,
    ];
}

// DN number
$dn_number = 'DN-' . str_pad($data['deliv_id'], 8, '0', STR_PAD_LEFT);
$po_number = $data['po_number'] ?? '';
$deliver_date = $data['deliver_date'] ?? '';

// Build HTML
$html = '
<style>
    body { font-family: garuda, Arial, sans-serif; font-size: 11px; color: #333; }
    .header { text-align: center; margin-bottom: 10px; }
    .header img { width: 50px; height: 50px; }
    .company-name { font-size: 14px; font-weight: bold; color: #2980b9; margin-top: 5px; }
    .company-addr { font-size: 10px; color: #444; line-height: 1.4; }
    .title { background: #2980b9; color: #fff; text-align: center; padding: 8px; font-size: 16px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
    .info-table { width: 100%; margin-bottom: 10px; }
    .info-table td { vertical-align: top; font-size: 10px; }
    .info-left { width: 55%; }
    .info-right { width: 45%; padding-left: 20px; }
    .dn-box { padding: 4px 0; margin-bottom: 6px; }
    .dn-num { font-size: 13px; font-weight: bold; color: #2980b9; margin: 0; }
    .dn-meta { font-size: 9px; color: #666; margin-top: 2px; }
    .lbl { font-weight: bold; color: #555; width: 70px; }
    .cust-name { font-weight: bold; }
    .items { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .items th { background: #2980b9; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
    .items th.r { text-align: right; }
    .items th.c { text-align: center; }
    .items td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .items td.r { text-align: right; }
    .items td.c { text-align: center; }
    .items tr:nth-child(even) { background: #f8f9fa; }
    .desc { font-size: 9px; color: #666; margin-top: 3px; line-height: 1.3; }
    .note { background: #eaf2f8; padding: 8px 10px; font-size: 10px; color: #333; margin: 10px 0; }
    .terms { border-top: 1px solid #ccc; padding-top: 8px; margin-top: 15px; }
    .terms-title { font-weight: bold; font-size: 10px; color: #2980b9; margin-bottom: 5px; }
    .terms-content { font-size: 9px; color: #555; line-height: 1.4; }
    .sigs { margin-top: 30px; }
    .sigs td { width: 50%; text-align: center; padding: 0 10px; vertical-align: bottom; }
    .sig-space { height: 40px; }
    .sig-line { font-size: 10px; font-weight: bold; padding-top: 5px; border-top: 1px solid #333; }
    .sig-name { font-size: 9px; color: #666; margin-top: 3px; }
    .sig-date { font-size: 9px; color: #888; margin-top: 3px; }
</style>

<!-- Header -->
<div class="header">
    <img src="upload/' . htmlspecialchars($logo) . '" width="50" height="50"><br>
    <div class="company-name">' . htmlspecialchars($vender['name_en'] ?? '') . '</div>
    <div class="company-addr">
        ' . htmlspecialchars($vender['adr_tax'] ?? '') . ' ' . htmlspecialchars($vender['city_tax'] ?? '') . ' ' . htmlspecialchars($vender['district_tax'] ?? '') . ' ' . htmlspecialchars($vender['province_tax'] ?? '') . ' ' . htmlspecialchars($vender['zip_tax'] ?? '') . '<br>
        Tel: ' . htmlspecialchars($vender['phone'] ?? '') . ' &nbsp; Fax: ' . htmlspecialchars($vender['fax'] ?? '') . ' &nbsp; Email: ' . htmlspecialchars($vender['email'] ?? '') . ' &nbsp; Tax ID: ' . htmlspecialchars($vender['tax'] ?? '') . '
    </div>
</div>

<!-- Title -->
<div class="title">DELIVERY NOTE</div>

<!-- Info Section -->
<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="dn-box">
                <div class="dn-num">' . htmlspecialchars($dn_number) . '</div>
                <div class="dn-meta">Date: ' . htmlspecialchars($deliver_date) . ($po_number ? ' &nbsp;|&nbsp; PO: ' . htmlspecialchars($po_number) : '') . '</div>
            </div>
            <table>
                <tr><td class="lbl">Customer</td><td class="cust-name">' . htmlspecialchars($customer['name']) . '</td></tr>';

if (!empty($customer['adr_tax'])) {
    $html .= '<tr><td class="lbl">Address</td><td>' . htmlspecialchars($customer['adr_tax']) . ' ' . htmlspecialchars($customer['city_tax']) . ' ' . htmlspecialchars($customer['district_tax']) . ' ' . htmlspecialchars($customer['province_tax']) . ' ' . htmlspecialchars($customer['zip_tax']) . '</td></tr>';
}
if (!empty($customer['tax'])) {
    $html .= '<tr><td class="lbl">Tax ID</td><td>' . htmlspecialchars($customer['tax']) . '</td></tr>';
}

$html .= '
            </table>
        </td>
        <td class="info-right">
            <table>
                <tr><td class="lbl">Tel</td><td>' . htmlspecialchars($customer['phone']) . '</td></tr>
                <tr><td class="lbl">Email</td><td>' . htmlspecialchars($customer['email']) . '</td></tr>
                <tr><td class="lbl">Fax</td><td>' . htmlspecialchars($customer['fax']) . '</td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- Products Table -->
<table class="items">
    <tr>
        <th class="c">#</th>
        <th>Product</th>
        <th>Model</th>
        <th>Serial No.</th>
        <th>Warranty</th>
        <th class="c">Qty</th>
        <th class="r">Unit Price</th>
        <th class="r">Amount</th>
    </tr>';

$i = 1;
foreach ($products as $p) {
    $html .= '
    <tr>
        <td class="c">' . $i++ . '</td>
        <td>' . htmlspecialchars($p['type_name']);
    if (!empty($p['des'])) {
        $html .= '<div class="desc">' . htmlspecialchars($p['des']) . '</div>';
    }
    $html .= '</td>
        <td>' . htmlspecialchars($p['model_name']) . '</td>
        <td>' . htmlspecialchars($p['s_n']) . '</td>
        <td>' . htmlspecialchars($p['warranty']) . '</td>
        <td class="c">' . $p['quantity'] . '</td>
        <td class="r">' . number_format($p['price'], 2) . '</td>
        <td class="r">' . number_format($p['total'], 2) . '</td>
    </tr>';
}

$html .= '
</table>';

// Description / notes
if (!empty($data['des'])) {
    $html .= '<div class="note"><b>Description:</b> ' . htmlspecialchars($data['des']) . '</div>';
}

// Terms
if (!empty($vender['term'])) {
    $html .= '
    <div class="terms">
        <div class="terms-title">Terms & Conditions</div>
        <div class="terms-content">' . nl2br(htmlspecialchars($vender['term'])) . '</div>
    </div>';
}

// Signatures
$html .= '
<table class="sigs" width="100%">
    <tr>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Delivered By</div>
            <div class="sig-name">' . htmlspecialchars($vender['name_en'] ?? '') . '</div>
            <div class="sig-date">Date: ' . htmlspecialchars($deliver_date) . '</div>
        </td>
        <td>
            <div class="sig-space"></div>
            <div class="sig-line">Received By</div>
            <div class="sig-name">' . htmlspecialchars($customer['name']) . '</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
    </tr>
</table>';

// Generate PDF
if (ob_get_level()) ob_end_clean();
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'garuda',
    'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12,
    'margin_header' => 0, 'margin_footer' => 0,
    'autoScriptToLang' => true, 'autoLangToFont' => true,
]);
$mpdf->SetDisplayMode('fullpage');
$mpdf->WriteHTML($html);
$mpdf->Output($dn_number . ".pdf", "I");
exit;
?>
