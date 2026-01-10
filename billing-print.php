<?php
/**
 * Billing Note Print/PDF Page (Multi-Invoice Support)
 * Displays a printable billing note with all linked invoices
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

$db = new DbConn($config);
$db->checkSecurity();

// Validate input
$bil_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bil_id <= 0) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Error</h2><p>Invalid billing note ID</p></div>');
}

// Fetch billing note main info
$billing_sql = "
    SELECT 
        b.bil_id,
        b.des as billing_des,
        b.price as billing_amount,
        b.customer_id,
        b.created_at,
        DATE_FORMAT(b.created_at, '%d/%m/%Y') as billing_date
    FROM billing b
    WHERE b.bil_id = '" . mysqli_real_escape_string($db->conn, $bil_id) . "'
";

$billing_result = mysqli_query($db->conn, $billing_sql);

if (!$billing_result || mysqli_num_rows($billing_result) == 0) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Billing Note Not Found</h2><p>The requested billing note does not exist.</p></div>');
}

$billing = mysqli_fetch_assoc($billing_result);

// Fetch all invoices linked to this billing note
// Use subquery to get only the latest iv record for each inv_id (by createdate)
$invoices_sql = "
    SELECT 
        bi.inv_id,
        bi.inv_id as iv_id,
        bi.amount,
        po.tax as po_number,
        DATE_FORMAT(iv.createdate, '%d/%m/%Y') as invoice_date,
        po.name as pr_description,
        pr.cus_id,
        pr.ven_id,
        pr.payby
    FROM billing_items bi
    JOIN iv ON bi.inv_id = iv.id
    JOIN po ON iv.tex = po.id
    JOIN pr ON po.ref = pr.id
    WHERE bi.bil_id = '" . mysqli_real_escape_string($db->conn, $bil_id) . "'
    AND po.po_id_new = ''
    AND iv.createdate = (SELECT MAX(iv2.createdate) FROM iv iv2 WHERE iv2.id = iv.id)
    ORDER BY iv.createdate ASC
";

$invoices_result = mysqli_query($db->conn, $invoices_sql);
$invoices = [];
$total_amount = 0;
$vendor_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;
$customer_id = $billing['customer_id'];

while ($inv = mysqli_fetch_assoc($invoices_result)) {
    $invoices[] = $inv;
    $total_amount += floatval($inv['amount']);
    if ($customer_id == 0) {
        $customer_id = (!empty($inv['payby']) && $inv['payby'] > 0) ? $inv['payby'] : $inv['cus_id'];
    }
}

// If no items found, fallback to legacy mode (single invoice in billing.inv_id)
if (empty($invoices)) {
    $legacy_sql = "
        SELECT 
            b.inv_id,
            b.price as amount,
            iv.id as iv_id,
            po.tax as po_number,
            DATE_FORMAT(iv.createdate, '%d/%m/%Y') as invoice_date,
            pr.des as pr_description,
            pr.cus_id,
            pr.ven_id,
            pr.payby
        FROM billing b
        JOIN iv ON b.inv_id = iv.id
        JOIN po ON iv.tex = po.id
        JOIN pr ON po.ref = pr.id
        WHERE b.bil_id = '" . mysqli_real_escape_string($db->conn, $bil_id) . "'
        AND po.po_id_new = ''
        ORDER BY iv.createdate DESC
        LIMIT 1
    ";
    $legacy_result = mysqli_query($db->conn, $legacy_sql);
    if ($legacy_result && mysqli_num_rows($legacy_result) > 0) {
        $inv = mysqli_fetch_assoc($legacy_result);
        $invoices[] = $inv;
        $total_amount = floatval($inv['amount']);
        // Keep vendor_id from session, only set customer_id
        $customer_id = (!empty($inv['payby']) && $inv['payby'] > 0) ? $inv['payby'] : $inv['cus_id'];
    }
}

if (empty($invoices)) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>No Invoices Found</h2><p>This billing note has no linked invoices.</p></div>');
}

// Fetch customer info
$customer = mysqli_fetch_assoc(mysqli_query($db->conn, "
    SELECT name_en, name_sh, tax, phone, email,
           adr_tax, city_tax, district_tax, province_tax, zip_tax
    FROM company 
    JOIN company_addr ON company.id = company_addr.com_id 
    WHERE company.id = '" . mysqli_real_escape_string($db->conn, $customer_id) . "' 
    AND valid_end = '0000-00-00'
"));

// Fetch vendor info
$vendor = mysqli_fetch_assoc(mysqli_query($db->conn, "
    SELECT name_en, name_sh, tax, logo, phone, email, fax,
           adr_tax, city_tax, district_tax, province_tax, zip_tax
    FROM company 
    JOIN company_addr ON company.id = company_addr.com_id 
    WHERE company.id = '" . mysqli_real_escape_string($db->conn, $vendor_id) . "' 
    AND valid_end = '0000-00-00'
"));

// Use billing total or calculated total
$amount = floatval($billing['billing_amount']) > 0 ? floatval($billing['billing_amount']) : $total_amount;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Billing Note BN-<?=str_pad($billing['bil_id'], 6, '0', STR_PAD_LEFT)?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            font-size: 12px; 
            line-height: 1.5; 
            color: #1f2937; 
            background: #f3f4f6;
            padding: 20px;
        }
        
        .billing-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .billing-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #fff;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .billing-header .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .billing-header .logo-section img {
            max-height: 60px;
            max-width: 150px;
        }
        
        .billing-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .billing-header .subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .billing-info {
            text-align: right;
        }
        
        .billing-info .number {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .billing-info .date {
            opacity: 0.9;
            font-size: 13px;
        }
        
        .billing-body {
            padding: 40px;
        }
        
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .party-box h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .party-box .name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .party-box .details {
            color: #6b7280;
            font-size: 12px;
        }
        
        .party-box .details p {
            margin-bottom: 4px;
        }
        
        .description-section {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }
        
        .description-section h3 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }
        
        .description-section .content {
            color: #4b5563;
            font-size: 14px;
            white-space: pre-line;
        }
        
        /* Invoice List Table */
        .invoices-section {
            margin-bottom: 30px;
        }
        
        .invoices-section h3 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .invoices-section .count-badge {
            background: #8b5cf6;
            color: #fff;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invoice-table th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .invoice-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }
        
        .invoice-table tr:last-child td {
            border-bottom: none;
        }
        
        .invoice-table .amount {
            text-align: right;
            font-weight: 600;
            color: #059669;
        }
        
        .invoice-table .total-row {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }
        
        .invoice-table .total-row td {
            font-weight: 700;
            color: #7c3aed;
            border-top: 2px solid #e9d5ff;
        }
        
        .amount-section {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            border: 2px solid #e9d5ff;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .amount-section .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7c3aed;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .amount-section .amount {
            font-size: 32px;
            font-weight: 700;
            color: #7c3aed;
        }
        
        .billing-footer {
            background: #f9fafb;
            padding: 20px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .billing-footer p {
            color: #6b7280;
            font-size: 11px;
        }
        
        /* Print styles */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            
            .billing-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        /* Action buttons */
        .action-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .action-buttons button {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            margin: 0 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139,92,246,0.3);
        }
        
        .action-buttons a {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-buttons a:hover {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button onclick="window.print()">🖨️ Print</button>
        <a href="index.php?page=billing">← Back to Billing List</a>
    </div>
    
    <div class="billing-container">
        <div class="billing-header">
            <div class="logo-section">
                <?php if (!empty($vendor['logo'])): ?>
                <img src="upload/<?=htmlspecialchars($vendor['logo'])?>" alt="Logo">
                <?php endif; ?>
                <div>
                    <h1>BILLING NOTE</h1>
                    <div class="subtitle"><?=htmlspecialchars($vendor['name_en'] ?? '')?></div>
                </div>
            </div>
            <div class="billing-info">
                <div class="number">BN-<?=str_pad($billing['bil_id'], 6, '0', STR_PAD_LEFT)?></div>
                <div class="date">Date: <?=htmlspecialchars($billing['billing_date'] ?? date('d/m/Y'))?></div>
                <div class="date"><?=count($invoices)?> Invoice(s)</div>
            </div>
        </div>
        
        <div class="billing-body">
            <div class="parties">
                <div class="party-box">
                    <h3>From</h3>
                    <div class="name"><?=htmlspecialchars($vendor['name_en'] ?? '')?></div>
                    <div class="details">
                        <?php if (!empty($vendor['adr_tax'])): ?>
                        <p><?=htmlspecialchars($vendor['adr_tax'])?></p>
                        <?php endif; ?>
                        <?php if (!empty($vendor['city_tax']) || !empty($vendor['province_tax'])): ?>
                        <p><?=htmlspecialchars(trim(($vendor['city_tax'] ?? '') . ' ' . ($vendor['province_tax'] ?? '') . ' ' . ($vendor['zip_tax'] ?? '')))?></p>
                        <?php endif; ?>
                        <?php if (!empty($vendor['tax'])): ?>
                        <p>Tax ID: <?=htmlspecialchars($vendor['tax'])?></p>
                        <?php endif; ?>
                        <?php if (!empty($vendor['phone'])): ?>
                        <p>Tel: <?=htmlspecialchars($vendor['phone'])?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="party-box">
                    <h3>Bill To</h3>
                    <div class="name"><?=htmlspecialchars($customer['name_en'] ?? '')?></div>
                    <div class="details">
                        <?php if (!empty($customer['adr_tax'])): ?>
                        <p><?=htmlspecialchars($customer['adr_tax'])?></p>
                        <?php endif; ?>
                        <?php if (!empty($customer['city_tax']) || !empty($customer['province_tax'])): ?>
                        <p><?=htmlspecialchars(trim(($customer['city_tax'] ?? '') . ' ' . ($customer['province_tax'] ?? '') . ' ' . ($customer['zip_tax'] ?? '')))?></p>
                        <?php endif; ?>
                        <?php if (!empty($customer['tax'])): ?>
                        <p>Tax ID: <?=htmlspecialchars($customer['tax'])?></p>
                        <?php endif; ?>
                        <?php if (!empty($customer['phone'])): ?>
                        <p>Tel: <?=htmlspecialchars($customer['phone'])?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($billing['billing_des'])): ?>
            <div class="description-section">
                <h3>Description</h3>
                <div class="content"><?=htmlspecialchars($billing['billing_des'])?></div>
            </div>
            <?php endif; ?>
            
            <!-- Invoice List -->
            <div class="invoices-section">
                <h3>
                    Invoices Included 
                    <span class="count-badge"><?=count($invoices)?></span>
                </h3>
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice No.</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($invoices as $inv): ?>
                        <tr>
                            <td><?=$i++?></td>
                            <td><strong><?=htmlspecialchars($inv['po_number'])?></strong></td>
                            <td><?=htmlspecialchars($inv['invoice_date'])?></td>
                            <td><?=htmlspecialchars($inv['pr_description'])?></td>
                            <td class="amount">฿<?=number_format(floatval($inv['amount']), 2)?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right;">TOTAL</td>
                            <td class="amount">฿<?=number_format($amount, 2)?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="amount-section">
                <div class="label">Total Billing Amount</div>
                <div class="amount">฿<?=number_format($amount, 2)?></div>
            </div>
        </div>
        
        <div class="billing-footer">
            <p>This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</body>
</html>
