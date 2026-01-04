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
    return '<a href="' . $base_url . '&sort=' . $column . '&dir=' . $new_dir . '" class="sort-link">' . $label . $icon . '</a>';
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Modern Report Styling */
.report-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }
.page-header-rep { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(99,102,241,0.3); }
.page-header-rep h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-rep .header-actions { display: flex; gap: 10px; }
.page-header-rep .btn-export { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
.page-header-rep .btn-export:hover { background: rgba(255,255,255,0.3); color: #fff; }

.filter-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.filter-card .filter-body { padding: 20px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }
.period-tabs { display: flex; gap: 8px; }
.period-tabs .btn { border-radius: 20px; padding: 8px 16px; font-size: 13px; font-weight: 500; border: 1px solid #e5e7eb; transition: all 0.2s; background: #fff; color: #374151; }
.period-tabs .btn.active { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; border-color: #4f46e5; }
.period-tabs .btn:hover:not(.active) { background: #f3f4f6; }
.period-label { background: #eef2ff; color: #4338ca; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500; }

.summary-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 992px) { .summary-cards { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 576px) { .summary-cards { grid-template-columns: repeat(2, 1fr); } }
.summary-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; text-align: center; }
.summary-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin: 0 auto 12px; }
.summary-card .icon.pr { background: #fef3c7; color: #d97706; }
.summary-card .icon.qa { background: #dbeafe; color: #2563eb; }
.summary-card .icon.po { background: #dcfce7; color: #16a34a; }
.summary-card .icon.iv { background: #fce7f3; color: #db2777; }
.summary-card .icon.tx { background: #e0e7ff; color: #4338ca; }
.summary-card h3 { margin: 0 0 4px 0; font-size: 28px; font-weight: 700; color: #1f2937; }
.summary-card p { margin: 0; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

.data-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.data-card .card-header { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 15px; color: #3730a3; }

.table-modern { margin-bottom: 0; }
.table-modern thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; }
.table-modern tbody tr { transition: background-color 0.2s; }
.table-modern tbody tr:hover { background-color: #eef2ff; }
.table-modern tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.table-modern tbody th { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 14px; font-weight: 500; color: #1f2937; }
.table-modern tfoot { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
.table-modern tfoot th, .table-modern tfoot td { padding: 14px 16px; font-weight: 700; color: #166534; }
.sort-link { color: #374151; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.sort-link:hover { color: #4f46e5; }

.empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
.empty-state i { font-size: 48px; margin-bottom: 16px; color: #d1d5db; }
</style>

<div class="report-container">

<!-- Page Header -->
<div class="page-header-rep">
    <h2><i class="fa fa-bar-chart-o"></i> <?=$xml->report?></h2>
    <div class="header-actions">
        <a href="report-export.php?period=<?php echo $report_period; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn-export">
            <i class="fa fa-file-excel-o"></i> Excel
        </a>
        <button onclick="window.print();" class="btn-export">
            <i class="fa fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <div class="filter-body">
        <div class="period-tabs">
            <a href="?page=report&period=today&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn <?php echo $report_period == 'today' ? 'active' : ''; ?>">Today</a>
            <a href="?page=report&period=week&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn <?php echo $report_period == 'week' ? 'active' : ''; ?>">7 Days</a>
            <a href="?page=report&period=month&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn <?php echo $report_period == 'month' ? 'active' : ''; ?>">30 Days</a>
            <a href="?page=report&period=year&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn <?php echo $report_period == 'year' ? 'active' : ''; ?>">This Year</a>
            <a href="?page=report&period=all&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?>" class="btn <?php echo $report_period == 'all' ? 'active' : ''; ?>">All Time</a>
        </div>
        <span class="period-label"><i class="fa fa-calendar"></i> <?php echo $period_label; ?></span>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="icon pr"><i class="fa fa-file-text-o"></i></div>
        <h3><?php echo $prs; ?></h3>
        <p><?=$xml->purchasingrequest ?? 'PR'?></p>
    </div>
    <div class="summary-card">
        <div class="icon qa"><i class="fa fa-list-alt"></i></div>
        <h3><?php echo $qas; ?></h3>
        <p><?=$xml->quotation ?? 'QA'?></p>
    </div>
    <div class="summary-card">
        <div class="icon po"><i class="fa fa-shopping-cart"></i></div>
        <h3><?php echo $pos; ?></h3>
        <p><?=$xml->purchasingorder ?? 'PO'?></p>
    </div>
    <div class="summary-card">
        <div class="icon iv"><i class="fa fa-file-text"></i></div>
        <h3><?php echo $ivs; ?></h3>
        <p><?=$xml->invoice ?? 'Invoice'?></p>
    </div>
    <div class="summary-card">
        <div class="icon tx"><i class="fa fa-check-circle"></i></div>
        <h3><?php echo $txs; ?></h3>
        <p><?=$xml->taxinvoice ?? 'Tax Invoice'?></p>
    </div>
</div>

<!-- Data Table -->
<div class="data-card">
    <div class="card-header">
        <i class="fa fa-table"></i> Customer Transaction Summary
    </div>
<div class="table-responsive">
<table class="table table-modern">
<thead>
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
<tfoot>
<tr>
    <th style="text-align:right;"><?=$xml->summary?></th>
    <td class="text-center"><?php echo $prs; ?></td>
    <td class="text-center"><?php echo $qas; ?></td>
    <td class="text-center"><?php echo $pos; ?></td>
    <td class="text-center"><?php echo $ivs; ?></td>
    <td class="text-center"><?php echo $txs; ?></td>
</tr>
</tfoot>
</table>
</div>
</div>

<?php if (count($report_data) == 0): ?>
<div class="alert alert-info" style="border-radius:12px;border:none;background:#eef2ff;color:#4338ca;">
    <i class="fa fa-info-circle"></i> No transactions found for the selected period.
</div>
<?php endif; ?>

</div><!-- /report-container -->