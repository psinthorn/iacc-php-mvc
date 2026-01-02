<?php
// Get filter parameters
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

// Build base URL for sorting links
$base_url = "?page=report&period=" . urlencode($report_period);

// Helper function to generate sort link
function sortLink($column, $label, $current_sort, $current_dir, $base_url) {
    $new_dir = ($current_sort == $column && $current_dir == 'desc') ? 'asc' : 'desc';
    $icon = '';
    if ($current_sort == $column) {
        $icon = $current_dir == 'asc' ? ' <i class="fa fa-sort-asc"></i>' : ' <i class="fa fa-sort-desc"></i>';
    } else {
        $icon = ' <i class="fa fa-sort" style="opacity:0.3;"></i>';
    }
    return '<a href="' . $base_url . '&sort=' . $column . '&dir=' . $new_dir . '" style="color:#333;text-decoration:none;">' . $label . $icon . '</a>';
}

// Check if company is selected
if ($com_id == 0 && !$is_admin) {
    // Non-admin without company - shouldn't happen but handle it
    echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Please select a company to view reports.</div>';
    return;
}

// Collect all data first
$report_data = [];
$prs = $qas = $pos = $ivs = $txs = 0;

// Build vendor filter - if no company selected (admin global view), show all vendors
if ($com_id > 0) {
    $ven_filter = "ven_id='".$com_id."'";
    $company_exclude = "company.id != '".$com_id."' AND";
} else {
    // Admin with no company - show aggregated report by vendor
    $ven_filter = "1=1"; // No vendor filter
    $company_exclude = "";
}

$querycom = mysqli_query($db->conn, "SELECT name_en, name_th, id FROM company WHERE $company_exclude customer='1'");
while($fetcom = mysqli_fetch_array($querycom)){
    if ($com_id > 0) {
        // Normal mode: show customers for selected vendor
        $pr = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' $date_filter"));
        $qa = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='1' $date_filter"));
        $po = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='2' $date_filter"));
        $iv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='4' $date_filter"));
        $tx = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='5' $date_filter"));
    } else {
        // Admin global view: show all transactions for each customer
        $pr = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' $date_filter"));
        $qa = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='1' $date_filter"));
        $po = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='2' $date_filter"));
        $iv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='4' $date_filter"));
        $tx = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE cus_id='".$fetcom['id']."' AND status>='5' $date_filter"));
    };
    
    // Only include rows with data
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
?>

<h2><i class="fa fa-bar-chart-o"></i> <?=$xml->report?></h2>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong>Filter by Period:</strong>
        <div class="btn-group" style="margin-left: 15px;">
            <a href="?page=report&period=today&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn btn-sm <?php echo $report_period == 'today' ? 'btn-primary' : 'btn-default'; ?>">Today</a>
            <a href="?page=report&period=week&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn btn-sm <?php echo $report_period == 'week' ? 'btn-primary' : 'btn-default'; ?>">7 Days</a>
            <a href="?page=report&period=month&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn btn-sm <?php echo $report_period == 'month' ? 'btn-primary' : 'btn-default'; ?>">30 Days</a>
            <a href="?page=report&period=year&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn btn-sm <?php echo $report_period == 'year' ? 'btn-primary' : 'btn-default'; ?>">This Year</a>
            <a href="?page=report&period=all&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn btn-sm <?php echo $report_period == 'all' ? 'btn-primary' : 'btn-default'; ?>">All Time</a>
        </div>
        <span style="margin-left: 15px; color: #666;">Showing: <strong><?php echo $period_label; ?></strong></span>
    </div>
</div>

<table class="table table-bordered table-hover" width="100%">
<thead style="background: #f5f5f5;">
<tr>
    <th><?php echo sortLink('name', 'Customer', $sort_by, $sort_dir, $base_url); ?></th>
    <th class="text-center"><?php echo sortLink('pr', $xml->purchasingrequest ?? 'PR', $sort_by, $sort_dir, $base_url); ?></th>
    <th class="text-center"><?php echo sortLink('qa', $xml->quotation ?? 'QA', $sort_by, $sort_dir, $base_url); ?></th>
    <th class="text-center"><?php echo sortLink('po', $xml->purchasingorder ?? 'PO', $sort_by, $sort_dir, $base_url); ?></th>
    <th class="text-center"><?php echo sortLink('iv', $xml->invoice ?? 'Invoice', $sort_by, $sort_dir, $base_url); ?></th>
    <th class="text-center"><?php echo sortLink('tx', $xml->taxinvoice ?? 'Tax Invoice', $sort_by, $sort_dir, $base_url); ?></th>
</tr>
</thead>
<tbody>
<?php foreach($report_data as $row): ?>
<tr>
    <th><?php echo htmlspecialchars($row['name']); ?></th>
    <td class="text-center"><?php echo $row['pr']; ?></td>
    <td class="text-center"><?php echo $row['qa']; ?></td>
    <td class="text-center"><?php echo $row['po']; ?></td>
    <td class="text-center"><?php echo $row['iv']; ?></td>
    <td class="text-center"><?php echo $row['tx']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot style="background: #e8f5e9; font-weight: bold;">
<tr>
    <th style="text-align:right;"><?=$xml->summary?></th>
    <td class="text-center"><?php echo $prs; ?></td>
    <td class="text-center"><?php echo $qas; ?></td>
    <td class="text-center"><?php echo $pos; ?></td>
    <td class="text-center"><?php echo $ivs; ?></td>
    <td class="text-center" style="color: #28a745;"><?php echo $txs; ?></td>
</tr>
</tfoot>
</table>

<?php if (count($report_data) == 0): ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> No transactions found for the selected period.
</div>
<?php endif; ?>