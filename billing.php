<?php
/**
 * Billing Note List Page
 * Displays and manages billing notes linked to invoices
 */
require_once("inc/security.php");

$com_id = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0;

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build search condition
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
    $date_cond .= " AND iv.createdate <= '$date_to'";
}

// Company filter for multi-tenant
$company_filter = '';
if ($com_id > 0) {
    $company_filter = " AND (pr.ven_id = '$com_id' OR pr.cus_id = '$com_id')";
}

// Query for invoices that can have billing notes
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
    ORDER BY iv.createdate DESC, iv.id DESC
";

$result = mysqli_query($db->conn, $sql);

// Calculate totals
$total_invoices = 0;
$total_with_billing = 0;
$total_without_billing = 0;
$total_amount = 0;

$invoices = [];
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
        $invoices[] = $row;
        
        $total_invoices++;
        $total_amount += $total;
        
        if (!empty($row['bil_id'])) {
            $total_with_billing++;
        } else {
            $total_without_billing++;
        }
    }
}

// Filter by status if needed
if ($status_filter === 'with_billing') {
    $invoices = array_filter($invoices, fn($inv) => !empty($inv['bil_id']));
} elseif ($status_filter === 'without_billing') {
    $invoices = array_filter($invoices, fn($inv) => empty($inv['bil_id']));
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
            <h3><?=$total_invoices?></h3>
            <p><?=$xml->totalinvoices ?? 'Total Invoices'?></p>
        </div>
        <div class="summary-card">
            <div class="card-icon with-billing"><i class="fa fa-check-circle"></i></div>
            <h3><?=$total_with_billing?></h3>
            <p><?=$xml->withbillingnote ?? 'With Billing Note'?></p>
        </div>
        <div class="summary-card">
            <div class="card-icon without-billing"><i class="fa fa-exclamation-circle"></i></div>
            <h3><?=$total_without_billing?></h3>
            <p><?=$xml->withoutbillingnote ?? 'Without Billing Note'?></p>
        </div>
        <div class="summary-card">
            <div class="card-icon amount"><i class="fa fa-money"></i></div>
            <h3>฿<?=number_format($total_amount, 0)?></h3>
            <p><?=$xml->totalamount ?? 'Total Amount'?></p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="data-card">
        <div class="card-header">
            <i class="fa fa-list"></i> <?=$xml->billinglist ?? 'Billing Note List'?>
        </div>
        
        <?php if (count($invoices) > 0): ?>
        <table class="table table-modern">
            <thead>
                <tr>
                    <th><?=$xml->invoiceno ?? 'Invoice #'?></th>
                    <th><?=$xml->customer ?? 'Customer'?></th>
                    <th><?=$xml->description ?? 'Description'?></th>
                    <th><?=$xml->date ?? 'Date'?></th>
                    <th style="text-align: right;"><?=$xml->amount ?? 'Amount'?></th>
                    <th><?=$xml->billingstatus ?? 'Billing Status'?></th>
                    <th width="120"><?=$xml->actions ?? 'Actions'?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td class="inv-number"><?=htmlspecialchars($inv['po_number'])?></td>
                    <td><?=htmlspecialchars($inv['customer_name'])?></td>
                    <td><?=htmlspecialchars(mb_substr($inv['po_name'], 0, 40))?><?=mb_strlen($inv['po_name']) > 40 ? '...' : ''?></td>
                    <td><?=htmlspecialchars($inv['invoice_date'])?></td>
                    <td class="amount-display">฿<?=number_format($inv['total_amount'], 2)?></td>
                    <td>
                        <?php if (!empty($inv['bil_id'])): ?>
                            <span class="status-badge has-billing"><i class="fa fa-check"></i> <?=$xml->hasbilling ?? 'Has Billing'?></span>
                        <?php else: ?>
                            <span class="status-badge no-billing"><i class="fa fa-clock-o"></i> <?=$xml->nobilling ?? 'No Billing'?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($inv['bil_id'])): ?>
                            <a href="billing-view.php?id=<?=$inv['bil_id']?>" class="btn-action btn-action-view" title="<?=$xml->view ?? 'View'?>"><i class="fa fa-eye"></i></a>
                            <a href="billing-print.php?id=<?=$inv['bil_id']?>" class="btn-action btn-action-print" title="<?=$xml->print ?? 'Print'?>" target="_blank"><i class="fa fa-print"></i></a>
                        <?php else: ?>
                            <a href="index.php?page=billing_make&inv_id=<?=$inv['iv_id']?>" class="btn-action btn-action-create" title="<?=$xml->createbilling ?? 'Create Billing Note'?>"><i class="fa fa-plus"></i></a>
                        <?php endif; ?>
                        <a href="inv.php?id=<?=$inv['po_id']?>" class="btn-action btn-action-print" title="<?=$xml->viewinvoice ?? 'View Invoice'?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-file-text-o"></i>
            <p><?=$xml->nobillingsyet ?? 'No invoices found. Create invoices first to add billing notes.'?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
