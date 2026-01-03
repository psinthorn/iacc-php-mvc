<?php
/**
 * AJAX endpoint to fetch invoice data for receipt form
 * Returns JSON with invoice details including customer info, products, and totals
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

header('Content-Type: application/json');

$db = new DbConn($config);
$com_id = sql_int($_SESSION['com_id'] ?? 0);
$invoice_id = sql_int($_GET['invoice_id'] ?? 0);

// Debug logging
error_log("fetch-invoice-data.php: invoice_id=$invoice_id, com_id=$com_id, session_id=".session_id());

if (!$invoice_id) {
    echo json_encode(['error' => 'No invoice ID provided']);
    exit;
}

if (!$com_id) {
    echo json_encode(['error' => 'Session expired, please login again', 'debug' => ['session_id' => session_id(), 'session' => $_SESSION]]);
    exit;
}

// Get invoice header data - simplified query first to debug
$sql = "SELECT po.id as invoice_id, iv.taxrw as invoice_no, iv.createdate as invoice_date,
        po.valid_pay as due_date, po.vat, po.dis as discount, po.over as overhead,
        company.name_en as customer_name, company.phone, company.email,
        company.tax as tax_id, pr.des as description
    FROM po
    JOIN pr ON po.ref = pr.id
    JOIN company ON pr.cus_id = company.id
    JOIN iv ON po.id = iv.tex
    WHERE po.id = '$invoice_id' 
    AND pr.ven_id = '$com_id'";

error_log("fetch-invoice-data.php SQL: $sql");

$query = mysqli_query($db->conn, $sql);

if (!$query) {
    echo json_encode(['error' => 'Database error: ' . mysqli_error($db->conn)]);
    exit;
}

if (mysqli_num_rows($query) == 0) {
    // Debug - try without vendor filter
    $debug_sql = "SELECT po.id, pr.ven_id FROM po JOIN pr ON po.ref = pr.id JOIN iv ON po.id = iv.tex WHERE po.id = '$invoice_id'";
    $debug_query = mysqli_query($db->conn, $debug_sql);
    $debug_data = mysqli_fetch_assoc($debug_query);
    echo json_encode([
        'error' => 'Invoice not found',
        'debug' => [
            'invoice_id' => $invoice_id,
            'com_id' => $com_id,
            'actual_ven_id' => $debug_data['ven_id'] ?? 'not found'
        ]
    ]);
    exit;
}

$invoice = mysqli_fetch_assoc($query);

// Get invoice products
$products = [];
$product_sql = "
    SELECT 
        product.pro_id as product_id,
        type.name as product_name,
        brand.brand_name,
        model.model_name,
        product.quantity,
        product.price,
        product.des as description,
        product.type,
        product.ban_id,
        product.model
    FROM product
    JOIN type ON product.type = type.id
    LEFT JOIN brand ON product.ban_id = brand.id
    LEFT JOIN model ON product.model = model.id
    WHERE product.po_id = '$invoice_id'
";

error_log("Product SQL: $product_sql");

$product_query = mysqli_query($db->conn, $product_sql);

if (!$product_query) {
    error_log("Product query error: " . mysqli_error($db->conn));
}

$subtotal = 0;
while ($prod = mysqli_fetch_assoc($product_query)) {
    $amount = floatval($prod['quantity']) * floatval($prod['price']);
    $subtotal += $amount;
    $products[] = [
        'product_id' => $prod['product_id'],
        'product_name' => $prod['product_name'],
        'brand_name' => $prod['brand_name'],
        'model_name' => $prod['model_name'],
        'quantity' => $prod['quantity'],
        'price' => $prod['price'],
        'description' => $prod['description'],
        'type' => $prod['type'],
        'ban_id' => $prod['ban_id'],
        'model' => $prod['model'],
        'amount' => number_format($amount, 2)
    ];
}

// Calculate totals
$discount_amount = $subtotal * floatval($invoice['discount']) / 100;
$after_discount = $subtotal - $discount_amount;

$overhead_amount = 0;
if ($invoice['overhead'] > 0) {
    $overhead_amount = $after_discount * floatval($invoice['overhead']) / 100;
}
$after_overhead = $after_discount + $overhead_amount;

$vat_amount = $after_overhead * floatval($invoice['vat']) / 100;
$grand_total = $after_overhead + $vat_amount;

// Prepare response
$response = [
    'success' => true,
    'invoice' => [
        'id' => $invoice['invoice_id'],
        'invoice_no' => $invoice['invoice_no'],
        'invoice_date' => $invoice['invoice_date'],
        'due_date' => $invoice['due_date'],
        'vat' => $invoice['vat'],
        'discount' => $invoice['discount'],
        'overhead' => $invoice['overhead'],
        'description' => $invoice['description']
    ],
    'customer' => [
        'name' => $invoice['customer_name'],
        'phone' => $invoice['phone'],
        'email' => $invoice['email'],
        'address' => $invoice['adr_tax'],
        'city' => $invoice['city_tax'],
        'district' => $invoice['district_tax'],
        'province' => $invoice['province_tax'],
        'zip' => $invoice['zip_tax'],
        'tax_id' => $invoice['tax_id']
    ],
    'products' => $products,
    'totals' => [
        'subtotal' => number_format($subtotal, 2),
        'discount_percent' => $invoice['discount'],
        'discount_amount' => number_format($discount_amount, 2),
        'after_discount' => number_format($after_discount, 2),
        'overhead_percent' => $invoice['overhead'],
        'overhead_amount' => number_format($overhead_amount, 2),
        'vat_percent' => $invoice['vat'],
        'vat_amount' => number_format($vat_amount, 2),
        'grand_total' => number_format($grand_total, 2),
        'grand_total_raw' => $grand_total
    ]
];

echo json_encode($response);
