<?php
/**
 * AJAX endpoint to fetch invoice or quotation data for receipt form
 * Returns JSON with details including customer info, products, and totals
 * 
 * Supports:
 * - ?invoice_id=X  - Fetch from invoice (iv table)
 * - ?quotation_id=X - Fetch from quotation (po table with status=1)
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.database.php"); // New prepared statement helper

header('Content-Type: application/json');

$db = new DbConn($config);
$com_id = sql_int($_SESSION['com_id'] ?? 0);
$invoice_id = sql_int($_GET['invoice_id'] ?? 0);
$quotation_id = sql_int($_GET['quotation_id'] ?? 0);

// Debug logging
error_log("fetch-invoice-data.php: invoice_id=$invoice_id, quotation_id=$quotation_id, com_id=$com_id");

if (!$invoice_id && !$quotation_id) {
    echo json_encode(['error' => 'No invoice or quotation ID provided']);
    exit;
}

if (!$com_id) {
    echo json_encode(['error' => 'Session expired, please login again', 'debug' => ['session_id' => session_id(), 'session' => $_SESSION]]);
    exit;
}

// Determine source type and fetch appropriate data
$source_type = $quotation_id ? 'quotation' : 'invoice';
$source_id = $quotation_id ?: $invoice_id;

if ($source_type === 'quotation') {
    // Fetch quotation data (po table with pr.status=1 for quotations)
    $data = db_fetch_one(
        "SELECT po.id as doc_id, po.tax as doc_no, po.date as doc_date,
            po.valid_pay as due_date, po.vat, po.dis as discount, po.over as overhead,
            company.name_en as customer_name, company.phone, company.email,
            company.tax as tax_id, pr.des as description
        FROM po
        JOIN pr ON po.ref = pr.id
        JOIN company ON pr.cus_id = company.id
        WHERE po.id = ? 
        AND pr.ven_id = ?
        AND pr.status = '1'",
        [$quotation_id, $com_id]
    );
    
    if (!$data) {
        echo json_encode(['error' => 'Quotation not found or access denied']);
        exit;
    }
} else {
    // Fetch invoice data (original logic)
    $data = db_fetch_one(
        "SELECT po.id as doc_id, iv.taxrw as doc_no, iv.createdate as doc_date,
            po.valid_pay as due_date, po.vat, po.dis as discount, po.over as overhead,
            company.name_en as customer_name, company.phone, company.email,
            company.tax as tax_id, pr.des as description
        FROM po
        JOIN pr ON po.ref = pr.id
        JOIN company ON pr.cus_id = company.id
        JOIN iv ON po.id = iv.tex
        WHERE po.id = ? 
        AND pr.ven_id = ?",
        [$invoice_id, $com_id]
    );
    
    if (!$data) {
        // Debug - try without vendor filter
        $debug_data = db_fetch_one(
            "SELECT po.id, pr.ven_id FROM po JOIN pr ON po.ref = pr.id JOIN iv ON po.id = iv.tex WHERE po.id = ?",
            [$invoice_id]
        );
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
}

// Get products - using prepared statement
$products = [];
$product_results = db_fetch_all(
    "SELECT 
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
    WHERE product.po_id = ?",
    [$source_id]
);

$subtotal = 0;
foreach ($product_results as $prod) {
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
$discount_amount = $subtotal * floatval($data['discount']) / 100;
$after_discount = $subtotal - $discount_amount;

$overhead_amount = 0;
if ($data['overhead'] > 0) {
    $overhead_amount = $after_discount * floatval($data['overhead']) / 100;
}
$after_overhead = $after_discount + $overhead_amount;

$vat_amount = $after_overhead * floatval($data['vat']) / 100;
$grand_total = $after_overhead + $vat_amount;

// Prepare response (unified for both invoice and quotation)
$response = [
    'success' => true,
    'source_type' => $source_type,
    'invoice' => [  // Keep key name for backward compatibility
        'id' => $data['doc_id'],
        'invoice_no' => $data['doc_no'],
        'invoice_date' => $data['doc_date'],
        'due_date' => $data['due_date'],
        'vat' => $data['vat'],
        'discount' => $data['discount'],
        'overhead' => $data['overhead'],
        'description' => $data['description']
    ],
    'customer' => [
        'name' => $data['customer_name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'address' => $data['adr_tax'] ?? '',
        'city' => $data['city_tax'] ?? '',
        'district' => $data['district_tax'] ?? '',
        'province' => $data['province_tax'] ?? '',
        'zip' => $data['zip_tax'] ?? '',
        'tax_id' => $data['tax_id']
    ],
    'products' => $products,
    'totals' => [
        'subtotal' => number_format($subtotal, 2),
        'discount_percent' => $data['discount'],
        'discount_amount' => number_format($discount_amount, 2),
        'after_discount' => number_format($after_discount, 2),
        'overhead_percent' => $data['overhead'],
        'overhead_amount' => number_format($overhead_amount, 2),
        'vat_percent' => $data['vat'],
        'vat_amount' => number_format($vat_amount, 2),
        'grand_total' => number_format($grand_total, 2),
        'grand_total_raw' => $grand_total
    ]
];

echo json_encode($response);
