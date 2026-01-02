<?php
// Get filter parameters
$report_period = isset($_GET['period']) ? $_GET['period'] : 'all';
$com_id = sql_int($_SESSION['com_id']);

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
?>

<h2><i class="fa fa-bar-chart-o"></i> <?=$xml->report?></h2>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong>Filter by Period:</strong>
        <div class="btn-group" style="margin-left: 15px;">
            <a href="?page=report&period=today" class="btn btn-sm <?php echo $report_period == 'today' ? 'btn-primary' : 'btn-default'; ?>">Today</a>
            <a href="?page=report&period=week" class="btn btn-sm <?php echo $report_period == 'week' ? 'btn-primary' : 'btn-default'; ?>">7 Days</a>
            <a href="?page=report&period=month" class="btn btn-sm <?php echo $report_period == 'month' ? 'btn-primary' : 'btn-default'; ?>">30 Days</a>
            <a href="?page=report&period=year" class="btn btn-sm <?php echo $report_period == 'year' ? 'btn-primary' : 'btn-default'; ?>">This Year</a>
            <a href="?page=report&period=all" class="btn btn-sm <?php echo $report_period == 'all' ? 'btn-primary' : 'btn-default'; ?>">All Time</a>
        </div>
        <span style="margin-left: 15px; color: #666;">Showing: <strong><?php echo $period_label; ?></strong></span>
    </div>
</div>

<table class="table table-bordered table-hover" width="100%">
<thead style="background: #f5f5f5;">
<tr><th>Customer</th><th class="text-center"><?=$xml->purchasingrequest?></th><th class="text-center"><?=$xml->quotation?></th><th class="text-center"><?=$xml->purchasingorder?></th><th class="text-center"><?=$xml->invoice?></th><th class="text-center"><?=$xml->taxinvoice?></th></tr>
</thead>
<tbody>
<?php
$prs = $qas = $pos = $ivs = $txs = 0;

$querycom = mysqli_query($db->conn, "SELECT name_en, name_th, id FROM company WHERE company.id != '".$com_id."' AND customer='1' ORDER BY name_en");
while($fetcom = mysqli_fetch_array($querycom)){
    $pr = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' $date_filter"));
    $qa = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='1' $date_filter"));
    $po = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='2' $date_filter"));
    $iv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='4' $date_filter"));
    $tx = mysqli_fetch_array(mysqli_query($db->conn, "SELECT count(id) as ct FROM pr WHERE ven_id='".$com_id."' AND cus_id='".$fetcom['id']."' AND status>='5' $date_filter"));
    
    // Only show rows with data
    if ($pr['ct'] > 0) {
?>
<tr>
    <th><?php echo htmlspecialchars($fetcom['name_en'] ?: $fetcom['name_th']); ?></th>
    <td class="text-center"><?php echo $pr['ct']; $prs += $pr['ct']; ?></td>
    <td class="text-center"><?php echo $qa['ct']; $qas += $qa['ct']; ?></td>
    <td class="text-center"><?php echo $po['ct']; $pos += $po['ct']; ?></td>
    <td class="text-center"><?php echo $iv['ct']; $ivs += $iv['ct']; ?></td>
    <td class="text-center"><?php echo $tx['ct']; $txs += $tx['ct']; ?></td>
</tr>
<?php 
    }
} 
?>
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

<?php if ($prs == 0): ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> No transactions found for the selected period.
</div>
<?php endif; ?>