<?php
/**
 * Create Billing Note Page (Multi-Invoice Support)
 * Creates a billing note that can include multiple invoices from the same customer
 */
require_once("inc/security.php");

$com_id = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0;

// Get customer_id from URL or initial invoice
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$inv_id = isset($_GET['inv_id']) ? intval($_GET['inv_id']) : 0;

// If inv_id is provided, get the customer from that invoice
if ($inv_id > 0 && $customer_id == 0) {
    $inv_sql = "
        SELECT 
            CASE WHEN pr.payby > 0 THEN pr.payby ELSE pr.cus_id END as customer_id
        FROM iv
        JOIN po ON iv.tex = po.id
        JOIN pr ON po.ref = pr.id
        WHERE iv.id = '" . mysqli_real_escape_string($db->conn, $inv_id) . "'
        AND po.po_id_new = ''
        ORDER BY iv.createdate DESC
        LIMIT 1
    ";
    $inv_result = mysqli_query($db->conn, $inv_sql);
    if ($inv_result && mysqli_num_rows($inv_result) > 0) {
        $inv_row = mysqli_fetch_assoc($inv_result);
        $customer_id = intval($inv_row['customer_id']);
    }
}

if ($customer_id <= 0) {
    echo '<div style="text-align:center;padding:50px;font-family:Arial;">
        <h2>Select Customer</h2>
        <p>Please select a customer to create a billing note.</p>
        <a href="index.php?page=billing" class="btn btn-primary">Back to Billing List</a>
    </div>';
    return;
}

// Fetch customer info
$customer = mysqli_fetch_assoc(mysqli_query($db->conn, "
    SELECT id, name_en, name_sh, tax, phone, email
    FROM company 
    WHERE id = '" . mysqli_real_escape_string($db->conn, $customer_id) . "'
"));

if (!$customer) {
    echo '<div style="text-align:center;padding:50px;font-family:Arial;">
        <h2>Customer Not Found</h2>
        <a href="index.php?page=billing" class="btn btn-primary">Back to Billing List</a>
    </div>';
    return;
}

// Fetch all unbilled invoices for this customer
$invoices_sql = "
    SELECT 
        iv.id as iv_id,
        po.id as po_id,
        po.tax as po_number,
        DATE_FORMAT(iv.createdate, '%d/%m/%Y') as invoice_date,
        iv.createdate as raw_date,
        pr.des as pr_description,
        pr.ven_id,
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
    WHERE (CASE WHEN pr.payby > 0 THEN pr.payby ELSE pr.cus_id END) = '" . mysqli_real_escape_string($db->conn, $customer_id) . "'
    AND po.po_id_new = ''
    AND pr.status >= 3
    AND iv.id NOT IN (SELECT inv_id FROM billing_items)
    ORDER BY iv.createdate DESC
";

$invoices_result = mysqli_query($db->conn, $invoices_sql);
$invoices = [];
$total_available = 0;

while ($row = mysqli_fetch_assoc($invoices_result)) {
    $subtotal = floatval($row['subtotal'] ?? 0);
    $vat_percent = floatval($row['vat'] ?? 0);
    $discount = floatval($row['discount'] ?? 0);
    $withholding = floatval($row['over'] ?? 0);
    
    $after_discount = $subtotal - $discount;
    $vat_amount = $after_discount * ($vat_percent / 100);
    $withholding_amount = $after_discount * ($withholding / 100);
    $total = $after_discount + $vat_amount - $withholding_amount;
    
    $row['total'] = $total;
    $row['selected'] = ($row['iv_id'] == $inv_id); // Pre-select if passed via URL
    $invoices[] = $row;
    $total_available += $total;
}

// Get vendor info from first invoice
$vendor_id = !empty($invoices) ? $invoices[0]['ven_id'] : 0;
$vendor = mysqli_fetch_assoc(mysqli_query($db->conn, "
    SELECT name_en, logo FROM company WHERE id = '" . mysqli_real_escape_string($db->conn, $vendor_id) . "'
"));
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Billing Make Page Styles - Multi Invoice */
.billing-make-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1000px; margin: 0 auto; }

.page-header-billing { 
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); 
    color: #fff; 
    padding: 24px 28px; 
    border-radius: 16px; 
    margin-bottom: 24px; 
    box-shadow: 0 4px 20px rgba(139,92,246,0.3); 
}
.page-header-billing h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-billing .subtitle { margin-top: 6px; opacity: 0.9; font-size: 14px; }

.form-card { 
    background: #fff; 
    border-radius: 16px; 
    box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
    margin-bottom: 24px; 
    border: 1px solid #e5e7eb; 
    overflow: hidden; 
}
.form-card .card-header { 
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); 
    padding: 16px 20px; 
    border-bottom: 1px solid #e5e7eb; 
    font-weight: 600; 
    color: #7c3aed; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
}
.form-card .card-body { padding: 20px; }

.customer-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.customer-info .info-item label {
    font-size: 11px;
    text-transform: uppercase;
    color: #6b7280;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.customer-info .info-item .value {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    margin-top: 4px;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
}
.invoice-table th {
    background: #f9fafb;
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e5e7eb;
}
.invoice-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
}
.invoice-table tr:hover {
    background: #faf5ff;
}
.invoice-table tr.selected {
    background: #f3e8ff;
}
.invoice-table .checkbox-cell {
    width: 40px;
    text-align: center;
}
.invoice-table input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #8b5cf6;
}
.invoice-table .amount {
    font-weight: 600;
    color: #059669;
    text-align: right;
}
.invoice-table .date {
    color: #6b7280;
}

.summary-box {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
    border: 2px solid #e9d5ff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
}
.summary-box .label {
    font-size: 14px;
    color: #7c3aed;
    font-weight: 600;
}
.summary-box .selected-count {
    font-size: 13px;
    color: #6b7280;
    margin-top: 4px;
}
.summary-box .total {
    font-size: 28px;
    font-weight: 700;
    color: #7c3aed;
}

.form-group { margin-bottom: 20px; }
.form-group label { 
    display: block; 
    font-weight: 600; 
    color: #374151; 
    margin-bottom: 8px; 
    font-size: 14px; 
}
.form-group .form-control { 
    width: 100%; 
    padding: 12px 16px; 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    font-size: 14px; 
    transition: all 0.2s; 
    box-sizing: border-box;
}
.form-group .form-control:focus { 
    outline: none; 
    border-color: #8b5cf6; 
    box-shadow: 0 0 0 3px rgba(139,92,246,0.15); 
}
.form-group textarea.form-control { 
    min-height: 100px; 
    resize: vertical; 
}

.form-actions { 
    display: flex; 
    gap: 12px; 
    justify-content: flex-end; 
    padding-top: 16px; 
    border-top: 1px solid #e5e7eb; 
    margin-top: 24px; 
}
.btn-submit { 
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); 
    color: #fff; 
    border: none; 
    padding: 12px 28px; 
    border-radius: 10px; 
    font-weight: 600; 
    font-size: 14px; 
    cursor: pointer; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    transition: all 0.2s; 
}
.btn-submit:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 6px 20px rgba(139,92,246,0.35); 
}
.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.btn-cancel { 
    background: #f3f4f6; 
    color: #6b7280; 
    border: 1px solid #e5e7eb; 
    padding: 12px 28px; 
    border-radius: 10px; 
    font-weight: 600; 
    font-size: 14px; 
    cursor: pointer; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    transition: all 0.2s; 
}
.btn-cancel:hover { 
    background: #e5e7eb; 
    color: #374151; 
}

.no-invoices {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}
.no-invoices i {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
}
</style>

<div class="billing-make-container">
    <!-- Page Header -->
    <div class="page-header-billing">
        <h2><i class="fa fa-plus-circle"></i> <?=$xml->createbillingnote ?? 'Create Billing Note'?></h2>
        <div class="subtitle"><?=$xml->multibillingsubtitle ?? 'Select one or more invoices to include in this billing note'?></div>
    </div>

    <!-- Customer Info Card -->
    <div class="form-card">
        <div class="card-header">
            <i class="fa fa-user"></i> <?=$xml->customerinfo ?? 'Customer Information'?>
        </div>
        <div class="card-body">
            <div class="customer-info">
                <div class="info-item">
                    <label><?=$xml->customername ?? 'Customer Name'?></label>
                    <div class="value"><?=htmlspecialchars($customer['name_en'])?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->taxid ?? 'Tax ID'?></label>
                    <div class="value"><?=htmlspecialchars($customer['tax'] ?? '-')?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($invoices)): ?>
    <div class="form-card">
        <div class="card-body no-invoices">
            <i class="fa fa-check-circle"></i>
            <h3><?=$xml->nounbilledinvoices ?? 'No Unbilled Invoices'?></h3>
            <p><?=$xml->allinvoicesbilled ?? 'All invoices for this customer have already been billed.'?></p>
            <a href="index.php?page=billing" class="btn-cancel" style="margin-top: 16px;"><i class="fa fa-arrow-left"></i> Back to Billing List</a>
        </div>
    </div>
    <?php else: ?>

    <form action="core-function.php" method="post" id="billingForm">
        <?= csrf_field() ?>
        <input type="hidden" name="page" value="billing">
        <input type="hidden" name="method" value="A">
        <input type="hidden" name="customer_id" value="<?=$customer_id?>">
        
        <!-- Invoice Selection Card -->
        <div class="form-card">
            <div class="card-header">
                <i class="fa fa-file-text-o"></i> <?=$xml->selectinvoices ?? 'Select Invoices'?>
                <span style="margin-left: auto; font-size: 12px; font-weight: normal; color: #6b7280;">
                    <?=count($invoices)?> <?=$xml->unbilledinvoices ?? 'unbilled invoice(s) available'?>
                </span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" id="selectAll" title="Select All">
                            </th>
                            <th><?=$xml->invoiceno ?? 'Invoice No.'?></th>
                            <th><?=$xml->date ?? 'Date'?></th>
                            <th><?=$xml->description ?? 'Description'?></th>
                            <th style="text-align: right;"><?=$xml->amount ?? 'Amount'?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                        <tr class="<?=$inv['selected'] ? 'selected' : ''?>">
                            <td class="checkbox-cell">
                                <input type="checkbox" name="invoices[]" value="<?=$inv['iv_id']?>" 
                                       data-amount="<?=$inv['total']?>" 
                                       class="invoice-checkbox"
                                       <?=$inv['selected'] ? 'checked' : ''?>>
                            </td>
                            <td><strong><?=htmlspecialchars($inv['po_number'])?></strong></td>
                            <td class="date"><?=htmlspecialchars($inv['invoice_date'])?></td>
                            <td><?=htmlspecialchars($inv['pr_description'])?></td>
                            <td class="amount">฿<?=number_format($inv['total'], 2)?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="summary-box">
                    <div>
                        <div class="label"><?=$xml->selectedtotal ?? 'Selected Total'?></div>
                        <div class="selected-count"><span id="selectedCount">0</span> <?=$xml->invoicesselected ?? 'invoice(s) selected'?></div>
                    </div>
                    <div class="total">฿<span id="selectedTotal">0.00</span></div>
                </div>
            </div>
        </div>

        <!-- Billing Details Card -->
        <div class="form-card">
            <div class="card-header">
                <i class="fa fa-edit"></i> <?=$xml->billingnotedetails ?? 'Billing Note Details'?>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="billing_des"><?=$xml->billingdescription ?? 'Billing Description'?> <span style="color:#9ca3af;font-weight:normal;">(<?=$xml->optional ?? 'Optional'?>)</span></label>
                    <textarea id="billing_des" name="des" class="form-control" 
                              placeholder="<?=$xml->billingdescriptionplaceholder ?? 'Enter billing note description, payment terms, due date, etc.'?>"></textarea>
                </div>
                
                <input type="hidden" name="price" id="totalPrice" value="0">
                
                <div class="form-actions">
                    <a href="index.php?page=billing" class="btn-cancel"><i class="fa fa-times"></i> <?=$xml->cancel ?? 'Cancel'?></a>
                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <i class="fa fa-save"></i> <?=$xml->createbillingnote ?? 'Create Billing Note'?>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        const selectAll = document.getElementById('selectAll');
        const selectedCount = document.getElementById('selectedCount');
        const selectedTotal = document.getElementById('selectedTotal');
        const totalPrice = document.getElementById('totalPrice');
        const submitBtn = document.getElementById('submitBtn');
        
        function updateTotals() {
            let count = 0;
            let total = 0;
            
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    count++;
                    total += parseFloat(cb.dataset.amount) || 0;
                    cb.closest('tr').classList.add('selected');
                } else {
                    cb.closest('tr').classList.remove('selected');
                }
            });
            
            selectedCount.textContent = count;
            selectedTotal.textContent = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            totalPrice.value = total.toFixed(2);
            submitBtn.disabled = count === 0;
            
            // Update select all checkbox state
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const someChecked = Array.from(checkboxes).some(cb => cb.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        }
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotals);
        });
        
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateTotals();
        });
        
        // Initial calculation
        updateTotals();
    });
    </script>
    <?php endif; ?>
</div>
