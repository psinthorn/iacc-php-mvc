<?php
/**
 * Report Export - CSV/Excel Export
 * Exports report data to CSV format (opens in Excel)
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
$report_period = isset($_GET['period']) ? $_GET['period'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_dir = isset($_GET['dir']) ? $_GET['dir'] : 'asc';
$com_id = sql_int($_SESSION['com_id']);
$user_level = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
$is_admin = ($user_level >= 1);

// Validate sort parameters
$valid_sorts = ['name', 'pr', 'qa', 'po', 'iv', 'tx'];
if (!in_array($sort_by, $valid_sorts)) $sort_by = 'name';
if (!in_array($sort_dir, ['asc', 'desc'])) $sort_dir = 'asc';

// Build date filter
switch ($report_period) {
    case 'today':
        $date_filter = "AND DATE(pr.date) = CURDATE()";
        $period_label = "Today";
        break;
    case 'week':
        $date_filter = "AND pr.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period_label = "Last 7 Days";
        break;
    case 'month':
        $date_filter = "AND pr.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $period_label = "Last 30 Days";
        break;
    case 'year':
        $date_filter = "AND YEAR(pr.date) = YEAR(CURDATE())";
        $period_label = "This Year";
        break;
    default:
        $date_filter = "";
        $period_label = "All Time";
}

// Check if company is selected
if ($com_id == 0 && !$is_admin) {
    die('Access denied');
}

// Collect all data
$report_data = [];
$prs = $qas = $pos = $ivs = $txs = 0;

// Build vendor filter
if ($com_id > 0) {
    $ven_filter = "ven_id='".$com_id."'";
    $company_exclude = "company.id != '".$com_id."' AND";
} else {
    $ven_filter = "1=1";
    $company_exclude = "";
}

$querycom = mysqli_query($db->conn, "SELECT name_en, name_th, id FROM company WHERE $company_exclude customer='1'");
while($fetcom = mysqli_fetch_array($querycom)){
    if ($com_id > 0) {
        $pr = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' $date_filter"));
        $qa = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='1' $date_filter"));
        $po = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='2' $date_filter"));
        $iv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='4' $date_filter"));
        $tx = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='5' $date_filter"));
    } else {
        $pr = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' $date_filter"));
        $qa = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='1' $date_filter"));
        $po = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='2' $date_filter"));
        $iv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='4' $date_filter"));
        $tx = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='5' $date_filter"));
    }
    
    if ($pr['ct'] > 0) {
        $report_data[] = [
            'name' => $fetcom['name_en'] ?: $fetcom['name_th'],
            'pr' => (int)$pr['ct'],
            'qa' => (int)$qa['ct'],
            'po' => (int)$po['ct'],
            'iv' => (int)$iv['ct'],
            'tx' => (int)$tx['ct']
        ];
        $prs += $pr['ct'];
        $qas += $qa['ct'];
        $pos += $po['ct'];
        $ivs += $iv['ct'];
        $txs += $tx['ct'];
    }
}

// Sort data
usort($report_data, function($a, $b) use ($sort_by, $sort_dir) {
    if ($sort_by == 'name') {
        $cmp = strcasecmp($a['name'], $b['name']);
    } else {
        $cmp = $a[$sort_by] - $b[$sort_by];
    }
    return $sort_dir == 'asc' ? $cmp : -$cmp;
});

// Generate filename
$filename = 'report_' . $report_period . '_' . date('Y-m-d_His') . '.csv';

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
fputcsv($output, ['iAcc Report Export']);
fputcsv($output, ['Period: ' . $period_label]);
fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []); // Empty row

// Column headers
fputcsv($output, ['Customer', 'PR', 'QA', 'PO', 'Invoice', 'Tax Invoice']);

// Data rows
foreach ($report_data as $row) {
    fputcsv($output, [
        $row['name'],
        $row['pr'],
        $row['qa'],
        $row['po'],
        $row['iv'],
        $row['tx']
    ]);
}

// Summary row
fputcsv($output, []); // Empty row
fputcsv($output, ['TOTAL', $prs, $qas, $pos, $ivs, $txs]);

fclose($output);
exit;
