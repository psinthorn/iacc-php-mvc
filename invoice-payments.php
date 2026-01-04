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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Modern Invoice Payments Styling */
.payments-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }
.page-header-pay { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(14,165,233,0.3); }
.page-header-pay h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-pay .header-actions { display: flex; gap: 10px; }
.page-header-pay .btn-export { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
.page-header-pay .btn-export:hover { background: rgba(255,255,255,0.3); color: #fff; }

.filter-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.filter-card .filter-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; }
.filter-card .filter-header i { color: #0ea5e9; }
.filter-card .filter-body { padding: 20px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
.filter-card .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 44px; padding: 10px 16px; font-size: 14px; min-width: 200px; transition: all 0.2s; }
.filter-card .form-control:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }

.status-tabs { display: flex; gap: 8px; }
.status-tabs .btn { border-radius: 20px; padding: 8px 16px; font-size: 13px; font-weight: 500; border: 1px solid #e5e7eb; transition: all 0.2s; }
.status-tabs .btn.active-all { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
.status-tabs .btn.active-paid { background: #10b981; color: #fff; border-color: #10b981; }
.status-tabs .btn.active-partial { background: #f59e0b; color: #fff; border-color: #f59e0b; }
.status-tabs .btn.active-unpaid { background: #ef4444; color: #fff; border-color: #ef4444; }

.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.summary-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
.summary-card .card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
.summary-card .card-icon.total { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }
.summary-card .card-icon.paid { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #16a34a; }
.summary-card .card-icon.partial { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; }
.summary-card .card-icon.unpaid { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; }
.summary-card h3 { margin: 0 0 4px 0; font-size: 28px; font-weight: 700; color: #1f2937; }
.summary-card p { margin: 0; font-size: 13px; color: #6b7280; }

.amount-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
.amount-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
.amount-card.outstanding { border-left: 4px solid #f59e0b; }
.amount-card h4 { margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.amount-card .value { font-size: 24px; font-weight: 700; color: #1f2937; }
.amount-card.outstanding .value { color: #d97706; }

.data-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.data-card .card-header { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 15px; color: #0369a1; }

.table-modern { margin-bottom: 0; }
.table-modern thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
.table-modern tbody tr { transition: background-color 0.2s; }
.table-modern tbody tr:hover { background-color: #f0f9ff; }
.table-modern tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.table-modern .inv-number { font-weight: 600; color: #0ea5e9; }

.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge.paid { background: #dcfce7; color: #166534; }
.status-badge.partial { background: #fef3c7; color: #92400e; }
.status-badge.unpaid { background: #fee2e2; color: #991b1b; }

.btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; margin-right: 4px; transition: all 0.2s; text-decoration: none; }
.btn-action-view { background: #eff6ff; color: #2563eb; }
.btn-action-view:hover { background: #2563eb; color: #fff; }
.btn-action-pdf { background: #f3f4f6; color: #374151; }
.btn-action-pdf:hover { background: #374151; color: #fff; }

.empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
.empty-state i { font-size: 48px; margin-bottom: 16px; color: #d1d5db; }

.progress { height: 6px; border-radius: 3px; background: #e5e7eb; margin-top: 6px; }
.progress-bar { border-radius: 3px; }
</style>

<div class="payments-container">

<!-- Page Header -->
<div class="page-header-pay">
    <h2><i class="fa fa-credit-card"></i> <?=$xml->invoice ?? 'Invoice'?> <?=$xml->payment ?? 'Payments'?> <?=$xml->tracking ?? 'Tracking'?></h2>
    <div class="header-actions">
        <a href="invoice-payments-export.php?status=<?=urlencode($status_filter)?>&search=<?=urlencode($search)?>" class="btn-export">
            <i class="fa fa-file-excel-o"></i> Export Excel
        </a>
        <button onclick="window.print();" class="btn-export">
            <i class="fa fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <div class="filter-header">
        <i class="fa fa-filter"></i> <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="filter-body">
        <form method="get" action="" style="display:contents;">
            <input type="hidden" name="page" value="invoice_payments">
            <input type="text" class="form-control" name="search" 
                   placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Customer..." 
                   value="<?=htmlspecialchars($search)?>">
            <div class="status-tabs">
                <a href="?page=invoice_payments&search=<?=urlencode($search)?>" 
                   class="btn <?= $status_filter == '' ? 'active-all' : 'btn-default'?>">All</a>
                <a href="?page=invoice_payments&status=paid&search=<?=urlencode($search)?>" 
                   class="btn <?= $status_filter == 'paid' ? 'active-paid' : 'btn-default'?>">
                   <i class="fa fa-check"></i> Paid</a>
                <a href="?page=invoice_payments&status=partial&search=<?=urlencode($search)?>" 
                   class="btn <?= $status_filter == 'partial' ? 'active-partial' : 'btn-default'?>">
                   <i class="fa fa-clock-o"></i> Partial</a>
                <a href="?page=invoice_payments&status=unpaid&search=<?=urlencode($search)?>" 
                   class="btn <?= $status_filter == 'unpaid' ? 'active-unpaid' : 'btn-default'?>">
                   <i class="fa fa-times"></i> Unpaid</a>
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:10px;padding:10px 20px;">
                <i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?>
            </button>
            <a href="?page=invoice_payments" class="btn btn-default" style="border-radius:10px;padding:10px 20px;">
                <i class="fa fa-refresh"></i>
            </a>
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

<div class="summary-cards">
    <div class="summary-card">
        <div class="card-icon total"><i class="fa fa-file-text-o"></i></div>
        <h3><?=number_format($summary['total_invoices'] ?? 0)?></h3>
        <p>Total Invoices</p>
    </div>
    <div class="summary-card">
        <div class="card-icon paid"><i class="fa fa-check"></i></div>
        <h3><?=number_format($summary['paid_count'] ?? 0)?></h3>
        <p>Fully Paid</p>
    </div>
    <div class="summary-card">
        <div class="card-icon partial"><i class="fa fa-clock-o"></i></div>
        <h3><?=number_format($summary['partial_count'] ?? 0)?></h3>
        <p>Partial Payment</p>
    </div>
    <div class="summary-card">
        <div class="card-icon unpaid"><i class="fa fa-times"></i></div>
        <h3><?=number_format($summary['unpaid_count'] ?? 0)?></h3>
        <p>Unpaid</p>
    </div>
</div>

<div class="amount-cards">
    <div class="amount-card">
        <h4>Total Amount</h4>
        <div class="value"><?=number_format($summary['total_amount'] ?? 0, 2)?> ฿</div>
    </div>
    <div class="amount-card outstanding">
        <h4>Outstanding</h4>
        <div class="value"><?=number_format($outstanding, 2)?> ฿</div>
    </div>
</div>

<!-- Invoice Payment Table -->
<div class="data-card">
    <div class="card-header">
        <i class="fa fa-table"></i> <?=$xml->invoice ?? 'Invoice'?> <?=$xml->payment ?? 'Payment'?> Details
    </div>
<div class="table-responsive">
<table class="table table-modern">
    <thead>
        <tr>
            <th>Invoice #</th>
            <th><?=$xml->customer ?? 'Customer'?></th>
            <th><?=$xml->name ?? 'Description'?></th>
            <th><?=$xml->date ?? 'Date'?></th>
            <th class="text-right"><?=$xml->amount ?? 'Amount'?></th>
            <th class="text-right"><?=$xml->paid ?? 'Paid'?></th>
            <th class="text-right">Outstanding</th>
            <th class="text-center"><?=$xml->status ?? 'Status'?></th>
            <th width="80"><?=$xml->action ?? 'Actions'?></th>
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
            <td class="inv-number"><strong>INV-<?=htmlspecialchars($row['invoice_id'])?></strong></td>
            <td><?=htmlspecialchars($row['customer_name'] ?: $row['customer_name_th'])?></td>
            <td><?=htmlspecialchars($row['description'])?></td>
            <td><?=date('d/m/Y', strtotime($row['createdate']))?></td>
            <td class="text-right"><?=number_format($row['total_amount'], 2)?></td>
            <td class="text-right" style="color: #16a34a;"><?=number_format($row['paid_amount'], 2)?></td>
            <td class="text-right" style="color: <?= $outstanding_row > 0 ? '#dc2626' : '#16a34a' ?>;">
                <?=number_format($outstanding_row, 2)?>
            </td>
            <td class="text-center">
                <span class="status-badge <?=$status_class?>"><i class="fa <?=$status_icon?>"></i> <?=$status_text?></span>
                <?php if ($progress > 0 && $progress < 100): ?>
                <div class="progress">
                    <div class="progress-bar progress-bar-<?=$status_class?>" style="width: <?=$progress?>%;"></div>
                </div>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <a href="?page=compl_view&id=<?=$row['invoice_id']?>" class="btn-action btn-action-view" title="View">
                    <i class="fa fa-eye"></i>
                </a>
                <a href="inv.php?id=<?=$row['invoice_id']?>" target="_blank" class="btn-action btn-action-pdf" title="PDF">
                    <i class="fa fa-file-text-o"></i>
                </a>
            </td>
        </tr>
<?php 
    endwhile;
else:
?>
        <tr>
            <td colspan="9">
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <p>No invoices found matching your criteria.</p>
                </div>
            </td>
        </tr>
<?php endif; ?>
    </tbody>
</table>
</div>
</div>

<?php if ($row_count >= 100): ?>
<div class="alert alert-info" style="border-radius:12px;border:none;background:#eff6ff;color:#1e40af;">
    <i class="fa fa-info-circle"></i> Showing first 100 results. Use filters to narrow down your search.
</div>
<?php endif; ?>

</div><!-- /payments-container -->
