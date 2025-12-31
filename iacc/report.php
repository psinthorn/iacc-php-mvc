<style>
.report-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.report-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    color: #2c3e50;
    letter-spacing: -0.5px;
}

.report-header i {
    font-size: 28px;
    margin-right: 12px;
    color: #667eea;
}

.report-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.report-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.report-table thead th {
    padding: 16px;
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.report-table thead th:first-child {
    text-align: left;
}

.report-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.report-table tbody tr:hover {
    background-color: #f8f9fa;
    box-shadow: inset 0 0 0 1px #e9ecef;
}

.report-table tbody td,
.report-table tbody th {
    padding: 14px 16px;
    color: #495057;
    font-size: 14px;
}

.report-table tbody th {
    font-weight: 600;
    color: #2c3e50;
    text-align: left;
}

.stat-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    min-width: 45px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.25);
}

.stat-badge:empty::before {
    content: '0';
}

.report-table tfoot {
    background-color: #f8f9fa;
    border-top: 2px solid #e9ecef;
}

.report-table tfoot tr {
    border: none;
}

.report-table tfoot th {
    padding: 16px;
    color: #2c3e50;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
}

.report-table tfoot td {
    padding: 16px;
    text-align: center;
    color: white;
    font-size: 16px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    margin: 8px;
}

.report-empty {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.report-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}
</style>

<div class="report-header">
    <i class="glyphicon glyphicon-book"></i>
    <h2><?=$xml->report?></h2>
</div>

<div class="report-container">
    <table class="report-table">
        <thead>
            <tr>
                <th><?=$xml->customer?></th>
                <th><?=$xml->purchasingrequest?></th>
                <th><?=$xml->quotation?></th>
                <th><?=$xml->purchasingorder?></th>
                <th><?=$xml->invoice?></th>
                <th><?=$xml->taxinvoice?></th>
            </tr>
        </thead>
        <tbody>
<?php
$company_id = isset($_SESSION['company_id']) ? mysqli_real_escape_string($db->conn, $_SESSION['company_id']) : '';
$querycom = mysqli_query($db->conn, "SELECT name_en, id FROM company WHERE company.id != '".$company_id."' AND customer='1'");

if (!$querycom) {
    echo '<tr><td colspan="6" class="report-empty"><i class="glyphicon glyphicon-exclamation-sign"></i><p>Error retrieving data</p></td></tr>';
} else {
    $has_rows = false;
    while($fetcom = mysqli_fetch_array($querycom)) {
        $has_rows = true;
        
        $pr = mysqli_fetch_array(mysqli_query($db->conn, "SELECT COUNT(id) as ct FROM purchase_request WHERE vendor_id='".$company_id."' AND customer_id='".$fetcom['id']."'"));
        $qa = mysqli_fetch_array(mysqli_query($db->conn, "SELECT COUNT(id) as ct FROM purchase_request WHERE vendor_id='".$company_id."' AND customer_id='".$fetcom['id']."' AND status>='1'"));
        $po = mysqli_fetch_array(mysqli_query($db->conn, "SELECT COUNT(id) as ct FROM purchase_request WHERE vendor_id='".$company_id."' AND customer_id='".$fetcom['id']."' AND status>='2'"));
        $iv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT COUNT(id) as ct FROM purchase_request WHERE vendor_id='".$company_id."' AND customer_id='".$fetcom['id']."' AND status>='4'"));
        $tx = mysqli_fetch_array(mysqli_query($db->conn, "SELECT COUNT(id) as ct FROM purchase_request WHERE vendor_id='".$company_id."' AND customer_id='".$fetcom['id']."' AND status>='5'"));
        
        $prs = (isset($prs) ? $prs : 0) + (isset($pr['ct']) ? $pr['ct'] : 0);
        $qas = (isset($qas) ? $qas : 0) + (isset($qa['ct']) ? $qa['ct'] : 0);
        $pos = (isset($pos) ? $pos : 0) + (isset($po['ct']) ? $po['ct'] : 0);
        $ivs = (isset($ivs) ? $ivs : 0) + (isset($iv['ct']) ? $iv['ct'] : 0);
        $txs = (isset($txs) ? $txs : 0) + (isset($tx['ct']) ? $tx['ct'] : 0);
?>
            <tr>
                <th><?=$fetcom['name_en']?></th>
                <td style="text-align: center;"><span class="stat-badge"><?=isset($pr['ct']) ? $pr['ct'] : 0?></span></td>
                <td style="text-align: center;"><span class="stat-badge"><?=isset($qa['ct']) ? $qa['ct'] : 0?></span></td>
                <td style="text-align: center;"><span class="stat-badge"><?=isset($po['ct']) ? $po['ct'] : 0?></span></td>
                <td style="text-align: center;"><span class="stat-badge"><?=isset($iv['ct']) ? $iv['ct'] : 0?></span></td>
                <td style="text-align: center;"><span class="stat-badge"><?=isset($tx['ct']) ? $tx['ct'] : 0?></span></td>
            </tr>
<?php 
    }
    
    if (!$has_rows) {
        echo '<tr><td colspan="6" class="report-empty"><p>' . $xml->nodata . '</p></td></tr>';
    }
}
?>
        </tbody>
        <tfoot>
            <tr>
                <th><?=$xml->summary?></th>
                <td><?=isset($prs) ? $prs : 0?></td>
                <td><?=isset($qas) ? $qas : 0?></td>
                <td><?=isset($pos) ? $pos : 0?></td>
                <td><?=isset($ivs) ? $ivs : 0?></td>
                <td><?=isset($txs) ? $txs : 0?></td>
            </tr>
        </tfoot>
    </table>
</div>