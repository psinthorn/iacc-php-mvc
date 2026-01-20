<?php
/**
 * Tax Invoice PDF Generator
 * Uses shared PDF template
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.current.php");
require_once("inc/security.php");
require_once("inc/pdf-template.php");

$db = new DbConn($config);
$db->checkSecurity();

// Validate and sanitize input
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if ($id <= 0) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Invalid Request</h2></div>');
}

$id_safe = mysqli_real_escape_string($db->conn, $id);
$session_com_id = mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? 0);

// Fetch tax invoice data
$sql = "
    SELECT 
        po.name as name, po.over, pr.ven_id, po.dis, 
        iv.taxrw as tax2, po.tax, pr.cus_id as cus_id, pr.payby, pr.des, po.vat,
        DATE_FORMAT(iv.texiv_create,'%d/%m/%Y') as date,
        iv.texiv_rw, po.ref, po.pic, po.po_ref, pr.status, po.bandven
    FROM pr 
    JOIN po ON pr.id = po.ref  
    JOIN iv ON po.id = iv.tex 
    WHERE po.id = '{$id_safe}' 
    AND pr.status = '5' 
    AND (pr.cus_id = '{$session_com_id}' OR pr.ven_id = '{$session_com_id}') 
    AND po.po_id_new = ''
";

$query = mysqli_query($db->conn, $sql);

if (!$query || mysqli_num_rows($query) != 1) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Tax Invoice Not Found</h2><p>The requested tax invoice does not exist or you do not have permission to view it.</p></div>');
}

$data = mysqli_fetch_array($query);

// Fetch vendor info - use LEFT JOIN and get the current/latest valid address
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company_addr.adr_tax, company_addr.city_tax, company_addr.district_tax, 
           company.tax, company_addr.province_tax, company_addr.zip_tax, company.fax, company.phone, 
           company.email, company.term, company.logo 
    FROM company 
    LEFT JOIN company_addr ON company.id = company_addr.com_id 
        AND company_addr.deleted_at IS NULL
    WHERE company.id = '" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "'
    ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC, company_addr.valid_start DESC
    LIMIT 1
"));

// Fetch customer info - use payby if set, otherwise use cus_id
$customer_id = !empty($data['payby']) ? $data['payby'] : $data['cus_id'];
$customer = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company.name_sh, company_addr.adr_tax, company_addr.city_tax, 
           company_addr.district_tax, company_addr.province_tax, company.tax, company_addr.zip_tax, 
           company.fax, company.phone, company.email 
    FROM company 
    LEFT JOIN company_addr ON company.id = company_addr.com_id 
        AND company_addr.deleted_at IS NULL
    WHERE company.id = '" . mysqli_real_escape_string($db->conn, $customer_id) . "'
    ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC, company_addr.valid_start DESC
    LIMIT 1
"));

// Get logo
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

while ($data_pro = mysqli_fetch_array($que_pro)) {
    $qty = intval($data_pro['quantity']);
    $price = floatval($data_pro['price']);
    
    if ($hasLabour) {
        $equip = $price * $qty;
        $labour1 = floatval($data_pro['valuelabour']) * intval($data_pro['activelabour']);
        $labour = $labour1 * $qty;
        $total = $equip + $labour;
    } else {
        $equip = 0;
        $labour1 = 0;
        $labour = 0;
        $total = $price * $qty;
    }
    
    $summary += $total;
    
    $products[] = [
        'model' => $data_pro['model'],
        'name' => $data_pro['name'],
        'des' => $data_pro['des'],
        'quantity' => $qty,
        'price' => $price,
        'equip' => $equip,
        'labour1' => $labour1,
        'labour' => $labour,
        'total' => $total
    ];
}

// Calculate totals
$disco = $summary * floatval($data['dis']) / 100;
$stotal = $summary - $disco;

$overh = 0;
if ($data['over'] > 0) {
    $overh = $stotal * floatval($data['over']) / 100;
    $stotal = $stotal + $overh;
}

$vat = $stotal * floatval($data['vat']) / 100;
$grandTotal = round($stotal, 2) + round($vat, 2);

// Fetch payment methods for vendor
$paymentMethods = [];
$pm_query = mysqli_query($db->conn, "
    SELECT method_type, method_name, account_name, account_number, branch, qr_image 
    FROM payment_methods 
    WHERE com_id = '" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "' 
    AND is_active = 1 
    ORDER BY is_default DESC, sort_order ASC
");
if ($pm_query) {
    while ($pm = mysqli_fetch_array($pm_query)) {
        $paymentMethods[] = $pm;
    }
}

// Prepare totals array
$totals = [
    'summary' => $summary,
    'disco' => $disco,
    'overh' => $overh,
    'stotal' => $stotal,
    'vat' => $vat,
    'grandTotal' => $grandTotal
];

// Generate PDF HTML using shared template
$html = generatePdfHtml(
    'TAX INVOICE / RECEIPT',  // docType
    'TAX',                     // docPrefix
    $data['texiv_rw'],         // docNumber (tax invoice number)
    $data,                     // data
    $vender,                   // vender
    $customer,                 // customer
    $products,                 // products
    $logo,                     // logo
    $paymentMethods,           // paymentMethods
    $totals,                   // totals
    $hasLabour                 // hasLabour
);

// Output PDF
outputPdf($html, "TAX-" . $data['texiv_rw'] . "-" . ($customer['name_sh'] ?? 'taxinvoice') . ".pdf");
