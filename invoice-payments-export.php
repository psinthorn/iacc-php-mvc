<?php
/**
 * Invoice Payments Export - CSV/Excel Export
 * Exports invoice payment tracking data to CSV format
 */

session_start();

// Load core files
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

// Initialize database and check authentication
$db = new DbConn($config);
$db->checkSecurity();

// Get parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$com_id = sql_int($_SESSION['com_id']);

// Build conditions
$status_cond = '';
if ($status_filter === 'paid') {
    $status_cond = " HAVING paid_amount >= total_amount AND total_amount > 0";
} elseif ($status_filter === 'partial') {
    $status_cond = " HAVING paid_amount > 0 AND paid_amount < total_amount";
} elseif ($status_filter === 'unpaid') {
    $status_cond = " HAVING (paid_amount IS NULL OR paid_amount = 0) AND total_amount > 0";
}

$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR po.tax LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%')";
}

// Company filter
if ($com_id > 0) {
    $company_filter = " AND (pr.ven_id = '$com_id' OR pr.cus_id = '$com_id')";
} else {
    $company_filter = "";
}

// Query data
$sql = "SELECT iv.tex as invoice_id, iv.createdate, po.name as description, po.tax as po_number,
        company.name_en as customer_name, company.name_th as customer_name_th,
        COALESCE(prod.total_amount, 0) as total_amount,
        COALESCE(paid.paid_amount, 0) as paid_amount
        FROM iv
        JOIN po ON iv.tex = po.id
        JOIN pr ON po.ref = pr.id
        LEFT JOIN company ON pr.cus_id = company.id
        LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
        LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
        WHERE iv.deleted_at IS NULL $company_filter $search_cond
        GROUP BY iv.tex
        $status_cond
        ORDER BY iv.createdate DESC";

$result = mysqli_query($db->conn, $sql);

// Generate filename
$status_suffix = $status_filter ? '_' . $status_filter : '';
$filename = 'invoice_payments' . $status_suffix . '_' . date('Y-m-d_His') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add report header info
fputcsv($output, ['Invoice Payment Tracking Export']);
fputcsv($output, ['Status Filter: ' . ($status_filter ?: 'All')]);
fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []); // Empty row

// Column headers
fputcsv($output, ['Invoice #', 'Customer', 'Description', 'Date', 'Total Amount', 'Paid Amount', 'Outstanding', 'Status']);

// Data rows and totals
$total_amount_sum = 0;
$paid_amount_sum = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $outstanding = $row['total_amount'] - $row['paid_amount'];
        
        // Determine status
        if ($row['total_amount'] <= 0) {
            $status = 'N/A';
        } elseif ($row['paid_amount'] >= $row['total_amount']) {
            $status = 'Paid';
        } elseif ($row['paid_amount'] > 0) {
            $status = 'Partial';
        } else {
            $status = 'Unpaid';
        }
        
        fputcsv($output, [
            'INV-' . $row['invoice_id'],
            $row['customer_name'] ?: $row['customer_name_th'],
            $row['description'],
            date('Y-m-d', strtotime($row['createdate'])),
            number_format($row['total_amount'], 2),
            number_format($row['paid_amount'], 2),
            number_format($outstanding, 2),
            $status
        ]);
        
        $total_amount_sum += $row['total_amount'];
        $paid_amount_sum += $row['paid_amount'];
    }
}

// Summary row
fputcsv($output, []); // Empty row
fputcsv($output, [
    'TOTAL', '', '', '',
    number_format($total_amount_sum, 2),
    number_format($paid_amount_sum, 2),
    number_format($total_amount_sum - $paid_amount_sum, 2),
    ''
]);

fclose($output);
exit;
