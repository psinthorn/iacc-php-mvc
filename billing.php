<?php
/**
 * Billing Note List Page
 * Displays all invoices - those with billing notes are grouped, those without show individually
 */
require_once("inc/security.php");
require_once("inc/pagination.php");

$com_id = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0;

// Pagination settings
$per_page = 15;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build search condition for invoices
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR po.tax LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%')";
}

// Date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND iv.createdate >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND iv.createdate <= '$date_to 23:59:59'";
}

// Company filter for multi-tenant
$company_filter = '';
if ($com_id > 0) {
    $company_filter = " AND (pr.ven_id = '$com_id' OR pr.cus_id = '$com_id')";
}

// Status filter condition
$status_cond = '';
if ($status_filter === 'with_billing') {
    $status_cond = " AND bi.bil_id IS NOT NULL";
} elseif ($status_filter === 'without_billing') {
    $status_cond = " AND bi.bil_id IS NULL";
}

// ============== COUNT TOTAL ITEMS ==============
// For pagination, count unique rows (billing notes count as 1, invoices without billing count as 1 each)
$count_sql = "
    SELECT COUNT(*) as total FROM (
        SELECT DISTINCT COALESCE(bi.bil_id, CONCAT('inv_', iv.id)) as row_key
        FROM iv
        JOIN po ON iv.tex = po.id
        JOIN pr ON po.ref = pr.id
        JOIN company ON (CASE WHEN pr.payby > 0 THEN pr.payby ELSE pr.cus_id END) = company.id
        LEFT JOIN billing_items bi ON bi.inv_id = iv.id
        WHERE pr.status >= 3 
        AND po.po_id_new = ''
        $company_filter
        $search_cond
        $date_cond
        $status_cond
    ) as counted
";
$count_result = mysqli_query($db->conn, $count_sql);
$total_items = $count_result ? intval(mysqli_fetch_assoc($count_result)['total']) : 0;
$total_pages = max(1, ceil($total_items / $items_per_page));

// ============== QUERY ALL INVOICES ==============
$sql = "
    SELECT 
        iv.id as iv_id,
        iv.tex as po_id,
        po.tax as po_number,
        po.name as po_name,
        DATE_FORMAT(iv.createdate, '%d/%m/%Y') as invoice_date,
        iv.createdate as raw_date,
        company.name_en as customer_name,
        pr.cus_id,
        pr.ven_id,
        pr.payby,
        bi.bil_id,
        b.des as billing_des,
        DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') as billing_date,
        bi.amount as billing_amount,
        (SELECT SUM(
            (product.price * product.quantity) + 
            (product.valuelabour * product.activelabour * product.quantity) -
            (product.discount * product.quantity)
        ) FROM product WHERE product.po_id = po.id) as subtotal,
        po.vat,
        po.dis as discount,
        po.over as withholding
    FROM iv
    JOIN po ON iv.tex = po.id
    JOIN pr ON po.ref = pr.id
    JOIN company ON (CASE WHEN pr.payby > 0 THEN pr.payby ELSE pr.cus_id END) = company.id
    LEFT JOIN billing_items bi ON bi.inv_id = iv.id
    LEFT JOIN billing b ON bi.bil_id = b.bil_id
    WHERE pr.status >= 3 
    AND po.po_id_new = ''
    $company_filter
    $search_cond
    $date_cond
    $status_cond
    ORDER BY COALESCE(b.created_at, iv.createdate) DESC, bi.bil_id DESC, iv.id DESC
";

$result = mysqli_query($db->conn, $sql);

// Process results - group by billing note ID or keep as individual
$all_invoices = [];
$billing_groups = []; // bil_id => array of invoices
$ungrouped_invoices = []; // invoices without billing

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate invoice total
        $subtotal = floatval($row['subtotal'] ?? 0);
        $vat_percent = floatval($row['vat'] ?? 0);
        $discount = floatval($row['discount'] ?? 0);
        $withholding = floatval($row['over'] ?? 0);
        
        $after_discount = $subtotal - $discount;
        $vat_amount = $after_discount * ($vat_percent / 100);
        $withholding_amount = $after_discount * ($withholding / 100);
        $total = $after_discount + $vat_amount - $withholding_amount;
        
        $row['total_amount'] = $total;
        
        if (!empty($row['bil_id'])) {
            // Group by billing note
            if (!isset($billing_groups[$row['bil_id']])) {
                $billing_groups[$row['bil_id']] = [
                    'bil_id' => $row['bil_id'],
                    'billing_des' => $row['billing_des'],
                    'billing_date' => $row['billing_date'],
                    'customer_name' => $row['customer_name'],
                    'invoices' => [],
                    'total_amount' => 0,
                    'row_type' => 'billing_group',
                    'sort_date' => $row['raw_date']
                ];
            }
            $billing_groups[$row['bil_id']]['invoices'][] = $row;
            $billing_groups[$row['bil_id']]['total_amount'] += floatval($row['billing_amount'] ?? $total);
        } else {
            // Individual invoice without billing
            $row['row_type'] = 'single_invoice';
            $row['sort_date'] = $row['raw_date'];
            $ungrouped_invoices[] = $row;
        }
    }
}

// Merge billing groups and ungrouped invoices into a single list
$display_rows = [];
foreach ($billing_groups as $group) {
    $display_rows[] = $group;
}
foreach ($ungrouped_invoices as $inv) {
    $display_rows[] = $inv;
}

// Sort by date descending
usort($display_rows, function($a, $b) {
    return strcmp($b['sort_date'], $a['sort_date']);
});

// Use pagination helper
$total_items = count($display_rows);
$pagination = paginate($total_items, $per_page, $current_page);
$offset = $pagination['offset'];
$total_pages = $pagination['total_pages'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['pg']);

// Apply pagination to the merged list
$paginated_rows = array_slice($display_rows, $offset, $per_page);

// ============== SUMMARY STATS ==============
$total_invoices = count($all_invoices) ?: (count($billing_groups) > 0 ? array_sum(array_map(fn($g) => count($g['invoices']), $billing_groups)) : 0) + count($ungrouped_invoices);
$total_with_billing = count($billing_groups) > 0 ? array_sum(array_map(fn($g) => count($g['invoices']), $billing_groups)) : 0;
$total_without_billing = count($ungrouped_invoices);
$total_billing_notes = count($billing_groups);

// Total billed amount
$total_billed = 0;
foreach ($billing_groups as $group) {
    $total_billed += $group['total_amount'];
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Billing Page Styles */
.billing-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }

.page-header-billing { 
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); 
    color: #fff; 
    padding: 24px 28px; 
    border-radius: 16px; 
    margin-bottom: 24px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 4px 20px rgba(139,92,246,0.3); 
}
.page-header-billing h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-billing .subtitle { margin-top: 6px; opacity: 0.9; font-size: 14px; }

.filter-card { 
    background: #fff; 
    border-radius: 16px; 
    box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
    margin-bottom: 24px; 
    border: 1px solid #e5e7eb; 
    overflow: hidden; 
}
.filter-card .filter-header { 
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
    padding: 16px 20px; 
    border-bottom: 1px solid #e5e7eb; 
    font-weight: 600; 
    color: #374151; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
}
.filter-card .filter-header i { color: #8b5cf6; }
.filter-card .filter-body { padding: 20px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
.filter-card .form-control { 
    border-radius: 10px; 
    border: 1px solid #e5e7eb; 
    height: 44px; 
    padding: 10px 16px; 
    font-size: 14px; 
    min-width: 180px; 
    transition: all 0.2s; 
}
.filter-card .form-control:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.15); }

.status-tabs { display: flex; gap: 8px; }
.status-tabs .btn { 
    border-radius: 20px; 
    padding: 8px 16px; 
    font-size: 13px; 
    font-weight: 500; 
    border: 1px solid #e5e7eb; 
    background: #fff;
    color: #6b7280;
    transition: all 0.2s; 
    text-decoration: none;
}
.status-tabs .btn:hover { background: #f3f4f6; }
.status-tabs .btn.active { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }
.status-tabs .btn.active-with { background: #10b981; color: #fff; border-color: #10b981; }
.status-tabs .btn.active-without { background: #f59e0b; color: #fff; border-color: #f59e0b; }

.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.summary-card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
    border: 1px solid #e5e7eb; 
}
.summary-card .card-icon { 
    width: 48px; 
    height: 48px; 
    border-radius: 12px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 20px; 
    margin-bottom: 12px; 
}
.summary-card .card-icon.total { background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #7c3aed; }
.summary-card .card-icon.with-billing { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #16a34a; }
.summary-card .card-icon.without-billing { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; }
.summary-card .card-icon.amount { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }
.summary-card h3 { margin: 0 0 4px 0; font-size: 28px; font-weight: 700; color: #1f2937; }
.summary-card p { margin: 0; font-size: 13px; color: #6b7280; }

.data-card { 
    background: #fff; 
    border-radius: 16px; 
    box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
    margin-bottom: 24px; 
    border: 1px solid #e5e7eb; 
    overflow: hidden; 
}
.data-card .card-header { 
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); 
    padding: 16px 20px; 
    border-bottom: 1px solid #e5e7eb; 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    font-weight: 600; 
    font-size: 15px; 
    color: #7c3aed; 
}

.table-modern { margin-bottom: 0; }
.table-modern thead th { 
    background: #f8fafc; 
    color: #374151; 
    font-weight: 600; 
    font-size: 12px; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
    padding: 14px 16px; 
    border-bottom: 2px solid #e5e7eb; 
    white-space: nowrap; 
}
.table-modern tbody tr { transition: background-color 0.2s; }
.table-modern tbody tr:hover { background-color: #faf5ff; }
.table-modern tbody td { 
    padding: 14px 16px; 
    vertical-align: middle; 
    border-bottom: 1px solid #f3f4f6; 
    font-size: 14px; 
}
.table-modern .inv-number { font-weight: 600; color: #8b5cf6; }

.status-badge { 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    padding: 6px 12px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: 600; 
}
.status-badge.has-billing { background: #dcfce7; color: #166534; }
.status-badge.no-billing { background: #fef3c7; color: #92400e; }

.status-tabs { display: flex; gap: 4px; margin-left: 8px; }
.status-tabs .btn { border-radius: 20px; font-size: 13px; font-weight: 500; }
.status-tabs .btn.active { background: #7c3aed; color: #fff; }
.status-tabs .btn.active-with { background: #16a34a; color: #fff; }
.status-tabs .btn.active-without { background: #f59e0b; color: #fff; }

.billing-badge { 
    display: inline-block;
    background: #7c3aed; 
    color: #fff; 
    padding: 4px 10px; 
    border-radius: 6px; 
    font-size: 12px; 
    font-weight: 600; 
}

.btn-action { 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    width: 32px; 
    height: 32px; 
    border-radius: 8px; 
    margin-right: 4px; 
    transition: all 0.2s; 
    text-decoration: none; 
}
.btn-action-create { background: #ede9fe; color: #7c3aed; }
.btn-action-create:hover { background: #7c3aed; color: #fff; }
.btn-action-view { background: #eff6ff; color: #2563eb; }
.btn-action-view:hover { background: #2563eb; color: #fff; }
.btn-action-print { background: #f3f4f6; color: #374151; }
.btn-action-print:hover { background: #374151; color: #fff; }

.empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
.empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.4; }
.empty-state p { margin: 0; font-size: 15px; }

.amount-display { font-weight: 600; color: #1f2937; text-align: right; }
</style>

<div class="billing-container">
    <!-- Page Header -->
    <div class="page-header-billing">
        <div>
            <h2><i class="fa fa-file-text-o"></i> <?=$xml->billingnote ?? 'Billing Note'?></h2>
            <div class="subtitle"><?=$xml->billingsubtitle ?? 'Manage billing notes for invoices'?></div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <div class="filter-header">
            <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        </div>
        <div class="filter-body">
            <form method="get" action="" class="form-inline" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                <input type="hidden" name="page" value="billing">
                
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Invoice, Customer..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 220px;">
                
                <div class="status-tabs">
                    <a href="?page=billing&status=all" class="btn <?=$status_filter === 'all' ? 'active' : ''?>"><?=$xml->all ?? 'All'?></a>
                    <a href="?page=billing&status=with_billing" class="btn <?=$status_filter === 'with_billing' ? 'active-with' : ''?>"><?=$xml->withbilling ?? 'With Billing'?></a>
                    <a href="?page=billing&status=without_billing" class="btn <?=$status_filter === 'without_billing' ? 'active-without' : ''?>"><?=$xml->withoutbilling ?? 'Without Billing'?></a>
                </div>
                
                <label style="margin-left: 12px; color: #6b7280; font-weight: 500;"><?=$xml->from ?? 'From'?>:</label>
                <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>" style="width: 150px;">
                
                <label style="color: #6b7280; font-weight: 500;"><?=$xml->to ?? 'To'?>:</label>
                <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>" style="width: 150px;">
                
                <button type="submit" class="btn btn-primary" style="height: 44px; border-radius: 10px;"><i class="fa fa-search"></i> <?=$xml->filter ?? 'Filter'?></button>
                <a href="?page=billing" class="btn btn-default" style="height: 44px; border-radius: 10px; line-height: 24px;"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-icon total"><i class="fa fa-file-text-o"></i></div>
            <h3><?=$total_billing_notes?></h3>
            <p><?=$xml->totalbillingnotes ?? 'Billing Notes'?></p>
        </div>
        <div class="summary-card">
            <div class="card-icon with-billing"><i class="fa fa-check-circle"></i></div>
            <h3><?=$total_with_billing?></h3>
            <p><?=$xml->invoiceswithbilling ?? 'Invoices with Billing'?></p>
        </div>
        <div class="summary-card">
            <div class="card-icon without-billing"><i class="fa fa-exclamation-circle"></i></div>
            <h3><?=$total_without_billing?></h3>
            <p><?=$xml->invoiceswithoutbilling ?? 'Invoices without Billing'?></p>
        </div>
        <div class="summary-card">
            <div class="card-icon amount"><i class="fa fa-money"></i></div>
            <h3>฿<?=number_format($total_billed, 0)?></h3>
            <p><?=$xml->totalbilledamount ?? 'Total Billed Amount'?></p>
        </div>
    </div>

    <!-- Action Bar -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
        <a href="index.php?page=billing_make" class="btn btn-primary" style="border-radius: 10px; padding: 10px 20px; font-weight: 600;">
            <i class="fa fa-plus"></i> <?=$xml->createbilling ?? 'Create Billing Note'?>
        </a>
    </div>

    <!-- Data Table -->
    <div class="data-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fa fa-list"></i> <?=$xml->invoiceandbillinglist ?? 'Invoice & Billing List'?></span>
            <span style="font-size: 13px; font-weight: 500; color: #6b7280;">
                <?=$xml->showing ?? 'Showing'?> <?=count($paginated_rows)?> <?=$xml->of ?? 'of'?> <?=$total_items?> <?=$xml->items ?? 'items'?>
            </span>
        </div>
        
        <?php if (count($paginated_rows) > 0): ?>
        <table class="table table-modern">
            <thead>
                <tr>
                    <th width="140"><?=$xml->invoiceno ?? 'Invoice #'?></th>
                    <th><?=$xml->customer ?? 'Customer'?></th>
                    <th><?=$xml->description ?? 'Description'?></th>
                    <th><?=$xml->date ?? 'Date'?></th>
                    <th style="text-align: right;"><?=$xml->amount ?? 'Amount'?></th>
                    <th><?=$xml->status ?? 'Status'?></th>
                    <th width="140"><?=$xml->actions ?? 'Actions'?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paginated_rows as $row): ?>
                    <?php if ($row['row_type'] === 'billing_group'): ?>
                    <!-- Billing Group Row -->
                    <tr class="billing-row" style="background: #faf5ff;">
                        <td class="inv-number">
                            <span class="billing-badge">BIL-<?=str_pad($row['bil_id'], 5, '0', STR_PAD_LEFT)?></span>
                        </td>
                        <td><?=htmlspecialchars($row['customer_name'] ?? '-')?></td>
                        <td><?=htmlspecialchars(mb_substr($row['billing_des'] ?? '-', 0, 40))?><?=mb_strlen($row['billing_des'] ?? '') > 40 ? '...' : ''?></td>
                        <td><?=htmlspecialchars($row['billing_date'])?></td>
                        <td class="amount-display">฿<?=number_format($row['total_amount'] ?? 0, 2)?></td>
                        <td>
                            <span class="invoice-count-badge" onclick="toggleInvoices(<?=$row['bil_id']?>)" style="cursor: pointer;">
                                <i class="fa fa-file-text-o"></i> <?=count($row['invoices'])?> <?=$xml->invoices ?? 'Invoices'?>
                                <i class="fa fa-chevron-down expand-icon" id="expand-icon-<?=$row['bil_id']?>"></i>
                            </span>
                        </td>
                        <td>
                            <a href="billing-print.php?id=<?=$row['bil_id']?>" class="btn-action btn-action-print" title="<?=$xml->print ?? 'Print'?>" target="_blank"><i class="fa fa-print"></i></a>
                            <a href="core-function.php?page=billing&method=D&bil_id=<?=$row['bil_id']?>" class="btn-action btn-action-delete" title="<?=$xml->delete ?? 'Delete'?>" onclick="return confirm('<?=$xml->confirmdelete ?? 'Are you sure you want to delete this billing note?'?>')"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                    <!-- Invoice sub-rows (hidden by default) -->
                    <tr class="invoice-group" id="invoices-<?=$row['bil_id']?>" style="display: none;">
                        <td colspan="7" style="padding: 0; background: #f8fafc;">
                            <div style="padding: 12px 20px 12px 40px;">
                                <table class="table" style="margin: 0; font-size: 13px;">
                                    <thead>
                                        <tr style="background: #ede9fe;">
                                            <th style="padding: 8px 12px;"><?=$xml->invoiceno ?? 'Invoice #'?></th>
                                            <th style="padding: 8px 12px;"><?=$xml->description ?? 'Description'?></th>
                                            <th style="padding: 8px 12px;"><?=$xml->invoicedate ?? 'Invoice Date'?></th>
                                            <th style="padding: 8px 12px; text-align: right;"><?=$xml->amount ?? 'Amount'?></th>
                                            <th style="padding: 8px 12px;" width="60"><?=$xml->view ?? 'View'?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($row['invoices'] as $inv): ?>
                                        <tr>
                                            <td style="padding: 8px 12px; font-weight: 600; color: #7c3aed;"><?=htmlspecialchars($inv['po_number'])?></td>
                                            <td style="padding: 8px 12px;"><?=htmlspecialchars(mb_substr($inv['po_name'], 0, 50))?><?=mb_strlen($inv['po_name']) > 50 ? '...' : ''?></td>
                                            <td style="padding: 8px 12px;"><?=htmlspecialchars($inv['invoice_date'])?></td>
                                            <td style="padding: 8px 12px; text-align: right; font-weight: 600;">฿<?=number_format($inv['billing_amount'] ?? $inv['total_amount'], 2)?></td>
                                            <td style="padding: 8px 12px;">
                                                <a href="inv.php?id=<?=$inv['po_id']?>" class="btn-action btn-action-view" title="<?=$xml->viewinvoice ?? 'View Invoice'?>" target="_blank" style="width: 28px; height: 28px;"><i class="fa fa-file-pdf-o"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <!-- Single Invoice Row (without billing) -->
                    <tr>
                        <td class="inv-number"><?=htmlspecialchars($row['po_number'])?></td>
                        <td><?=htmlspecialchars($row['customer_name'])?></td>
                        <td><?=htmlspecialchars(mb_substr($row['po_name'], 0, 40))?><?=mb_strlen($row['po_name']) > 40 ? '...' : ''?></td>
                        <td><?=htmlspecialchars($row['invoice_date'])?></td>
                        <td class="amount-display">฿<?=number_format($row['total_amount'], 2)?></td>
                        <td>
                            <span class="status-badge no-billing"><i class="fa fa-clock-o"></i> <?=$xml->nobilling ?? 'No Billing'?></span>
                        </td>
                        <td>
                            <a href="index.php?page=billing_make&inv_id=<?=$row['iv_id']?>" class="btn-action btn-action-create" title="<?=$xml->createbilling ?? 'Create Billing Note'?>"><i class="fa fa-plus"></i></a>
                            <a href="inv.php?id=<?=$row['po_id']?>" class="btn-action btn-action-print" title="<?=$xml->viewinvoice ?? 'View Invoice'?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?= render_pagination($pagination, '?page=billing', $query_params, 'pg') ?>
        
        <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-file-text-o"></i>
            <p><?=$xml->nobillingsyet ?? 'No billing notes found. Create billing notes to see them here.'?></p>
            <a href="index.php?page=billing_make" class="btn btn-primary" style="margin-top: 16px; border-radius: 10px;"><i class="fa fa-plus"></i> <?=$xml->createbilling ?? 'Create Billing Note'?></a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleInvoices(bilId) {
    var row = document.getElementById('invoices-' + bilId);
    var icon = document.getElementById('expand-icon-' + bilId);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        row.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>

<style>
.invoice-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ede9fe;
    color: #7c3aed;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
}
.invoice-count-badge:hover {
    background: #ddd6fe;
}
.btn-action-delete { background: #fee2e2; color: #dc2626; }
.btn-action-delete:hover { background: #dc2626; color: #fff; }
.billing-row:hover + .invoice-group { background: #faf5ff !important; }
</style>
