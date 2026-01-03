<?php
// Security already checked in index.php
$com_id = sql_int($_SESSION['com_id']);

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
?>

<h2><i class="fa fa-credit-card"></i> <?=$xml->invoice ?? 'Invoice'?> <?=$xml->payment ?? 'Payments'?> <?=$xml->tracking ?? 'Tracking'?></h2>

<!-- Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="invoice_payments">
            
            <div class="form-group" style="margin-right: 15px;">
                <label for="search" style="margin-right: 5px;"><i class="fa fa-search"></i></label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Customer..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 200px;">
            </div>
            
            <div class="form-group" style="margin-right: 15px;">
                <label style="margin-right: 5px;"><?=$xml->status ?? 'Status'?>:</label>
                <div class="btn-group">
                    <a href="?page=invoice_payments&search=<?=urlencode($search)?>" 
                       class="btn btn-sm <?= $status_filter == '' ? 'btn-primary' : 'btn-default'?>">All</a>
                    <a href="?page=invoice_payments&status=paid&search=<?=urlencode($search)?>" 
                       class="btn btn-sm <?= $status_filter == 'paid' ? 'btn-success' : 'btn-default'?>">
                       <i class="fa fa-check"></i> Paid</a>
                    <a href="?page=invoice_payments&status=partial&search=<?=urlencode($search)?>" 
                       class="btn btn-sm <?= $status_filter == 'partial' ? 'btn-warning' : 'btn-default'?>">
                       <i class="fa fa-clock-o"></i> Partial</a>
                    <a href="?page=invoice_payments&status=unpaid&search=<?=urlencode($search)?>" 
                       class="btn btn-sm <?= $status_filter == 'unpaid' ? 'btn-danger' : 'btn-default'?>">
                       <i class="fa fa-times"></i> Unpaid</a>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=invoice_payments" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            
            <span style="margin-left: 20px;">
                <a href="invoice-payments-export.php?status=<?=urlencode($status_filter)?>&search=<?=urlencode($search)?>" 
                   class="btn btn-success" title="Export to Excel/CSV">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
                <button onclick="window.print();" class="btn btn-info" title="Print">
                    <i class="fa fa-print"></i> Print
                </button>
            </span>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<?php
// Get summary statistics
$sql_summary = "SELECT 
    COUNT(DISTINCT iv.tex) as total_invoices,
    SUM(CASE WHEN paid.paid_amount >= prod.total_amount AND prod.total_amount > 0 THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN paid.paid_amount > 0 AND paid.paid_amount < prod.total_amount THEN 1 ELSE 0 END) as partial_count,
    SUM(CASE WHEN (paid.paid_amount IS NULL OR paid.paid_amount = 0) AND prod.total_amount > 0 THEN 1 ELSE 0 END) as unpaid_count,
    COALESCE(SUM(prod.total_amount), 0) as total_amount,
    COALESCE(SUM(paid.paid_amount), 0) as total_paid
FROM iv
JOIN po ON iv.tex = po.id
JOIN pr ON po.ref = pr.id
LEFT JOIN company ON pr.cus_id = company.id
LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
WHERE iv.deleted_at IS NULL $company_filter $search_cond";

$result_summary = mysqli_query($db->conn, $sql_summary);
$summary = mysqli_fetch_assoc($result_summary);
$outstanding = ($summary['total_amount'] ?? 0) - ($summary['total_paid'] ?? 0);
?>

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-3">
        <div class="panel panel-info">
            <div class="panel-heading"><i class="fa fa-file-text-o"></i> Total Invoices</div>
            <div class="panel-body text-center" style="font-size: 24px; font-weight: bold;">
                <?=number_format($summary['total_invoices'] ?? 0)?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-success">
            <div class="panel-heading"><i class="fa fa-check"></i> Fully Paid</div>
            <div class="panel-body text-center" style="font-size: 24px; font-weight: bold; color: #28a745;">
                <?=number_format($summary['paid_count'] ?? 0)?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-heading"><i class="fa fa-clock-o"></i> Partial Payment</div>
            <div class="panel-body text-center" style="font-size: 24px; font-weight: bold; color: #ffc107;">
                <?=number_format($summary['partial_count'] ?? 0)?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-danger">
            <div class="panel-heading"><i class="fa fa-times"></i> Unpaid</div>
            <div class="panel-body text-center" style="font-size: 24px; font-weight: bold; color: #dc3545;">
                <?=number_format($summary['unpaid_count'] ?? 0)?>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-6">
        <div class="well" style="margin-bottom: 0;">
            <strong>Total Amount:</strong> 
            <span style="font-size: 18px;"><?=number_format($summary['total_amount'] ?? 0, 2)?> ฿</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="well" style="margin-bottom: 0; background-color: <?= $outstanding > 0 ? '#fff3cd' : '#d4edda' ?>;">
            <strong>Outstanding:</strong> 
            <span style="font-size: 18px; color: <?= $outstanding > 0 ? '#856404' : '#155724' ?>;">
                <?=number_format($outstanding, 2)?> ฿
            </span>
        </div>
    </div>
</div>

<!-- Invoice Payment Table -->
<table class="table table-bordered table-hover">
    <thead style="background: #f5f5f5;">
        <tr>
            <th>Invoice #</th>
            <th><?=$xml->customer ?? 'Customer'?></th>
            <th><?=$xml->name ?? 'Description'?></th>
            <th><?=$xml->date ?? 'Date'?></th>
            <th class="text-right"><?=$xml->amount ?? 'Amount'?></th>
            <th class="text-right"><?=$xml->paid ?? 'Paid'?></th>
            <th class="text-right">Outstanding</th>
            <th class="text-center"><?=$xml->status ?? 'Status'?></th>
            <th width="100"><?=$xml->action ?? 'Actions'?></th>
        </tr>
    </thead>
    <tbody>
<?php
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
        ORDER BY iv.createdate DESC
        LIMIT 100";

$result = mysqli_query($db->conn, $sql);
$row_count = 0;

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)):
        $row_count++;
        $outstanding_row = $row['total_amount'] - $row['paid_amount'];
        
        // Determine status
        if ($row['total_amount'] <= 0) {
            $status_class = 'default';
            $status_text = 'N/A';
            $status_icon = 'fa-question';
        } elseif ($row['paid_amount'] >= $row['total_amount']) {
            $status_class = 'success';
            $status_text = 'Paid';
            $status_icon = 'fa-check';
        } elseif ($row['paid_amount'] > 0) {
            $status_class = 'warning';
            $status_text = 'Partial';
            $status_icon = 'fa-clock-o';
        } else {
            $status_class = 'danger';
            $status_text = 'Unpaid';
            $status_icon = 'fa-times';
        }
        
        // Progress percentage
        $progress = $row['total_amount'] > 0 ? min(100, ($row['paid_amount'] / $row['total_amount']) * 100) : 0;
?>
        <tr>
            <td><strong>INV-<?=htmlspecialchars($row['invoice_id'])?></strong></td>
            <td><?=htmlspecialchars($row['customer_name'] ?: $row['customer_name_th'])?></td>
            <td><?=htmlspecialchars($row['description'])?></td>
            <td><?=date('d/m/Y', strtotime($row['createdate']))?></td>
            <td class="text-right"><?=number_format($row['total_amount'], 2)?></td>
            <td class="text-right" style="color: #28a745;"><?=number_format($row['paid_amount'], 2)?></td>
            <td class="text-right" style="color: <?= $outstanding_row > 0 ? '#dc3545' : '#28a745' ?>;">
                <?=number_format($outstanding_row, 2)?>
            </td>
            <td class="text-center">
                <span class="label label-<?=$status_class?>"><i class="fa <?=$status_icon?>"></i> <?=$status_text?></span>
                <?php if ($progress > 0 && $progress < 100): ?>
                <div class="progress" style="margin: 5px 0 0 0; height: 5px;">
                    <div class="progress-bar progress-bar-<?=$status_class?>" style="width: <?=$progress?>%;"></div>
                </div>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <a href="?page=compl_view&id=<?=$row['invoice_id']?>" class="btn btn-xs btn-info" title="View">
                    <i class="fa fa-eye"></i>
                </a>
                <a href="inv.php?id=<?=$row['invoice_id']?>" target="_blank" class="btn btn-xs btn-default" title="PDF">
                    <i class="fa fa-file-text-o"></i>
                </a>
            </td>
        </tr>
<?php 
    endwhile;
else:
?>
        <tr>
            <td colspan="9" class="text-center text-muted" style="padding: 40px;">
                <i class="fa fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                <p style="margin-top: 15px;">No invoices found matching your criteria.</p>
            </td>
        </tr>
<?php endif; ?>
    </tbody>
</table>

<?php if ($row_count >= 100): ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> Showing first 100 results. Use filters to narrow down your search.
</div>
<?php endif; ?>
