<?php
/**
 * Billing Make View — Legacy Modern Design
 * Variables: $customer, $inv_id, $unbilled, $customers
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="css/smart-dropdown.css" rel="stylesheet">
<style>
    .billing-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(139,92,246,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }

    /* Customer Selection */
    .customer-select-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 24px; }
    .customer-select-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #8b5cf6; display: flex; align-items: center; gap: 8px; }
    .customer-select-card .select-row { display: flex; gap: 12px; align-items: flex-end; }
    .customer-select-card .select-row .field { flex: 1; }
    .customer-select-card .select-row .field label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
    .customer-select-card .select-row .field select { width: 100%; border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; min-height: 44px; }
    .customer-select-card .select-row .field select:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); outline: none; }
    .customer-select-card .btn-load { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; min-height: 44px; white-space: nowrap; }
    .customer-select-card .btn-load:hover { box-shadow: 0 4px 12px rgba(139,92,246,0.35); }

    /* Info Card */
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 24px; }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #8b5cf6; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; }

    /* Form Card */
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
    .form-card .form-control:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }

    /* Column Toggle Bar */
    .column-toggle-bar { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 14px 20px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .column-toggle-bar .toggle-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.05em; white-space: nowrap; }
    .column-toggle-bar .toggle-chips { display: flex; gap: 6px; flex-wrap: wrap; }
    .col-chip { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1.5px solid #e5e7eb; background: white; color: #6b7280; transition: all 0.2s; user-select: none; }
    .col-chip.active { background: #8b5cf6; color: white; border-color: #8b5cf6; }
    .col-chip:hover { border-color: #8b5cf6; }
    .col-chip i { font-size: 10px; }

    /* Data Card */
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f5f3ff; }
    .data-card tbody tr.selected-row { background: #ede9fe; }
    .invoice-checkbox { width: 18px; height: 18px; accent-color: #8b5cf6; cursor: pointer; }

    /* Total Display */
    .total-display { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .total-display .total-label { font-size: 14px; font-weight: 600; }
    .total-display .total-value { font-size: 28px; font-weight: 700; font-family: 'Courier New', monospace; }
    .amount-col { font-family: 'Courier New', monospace; font-weight: 600; }
    .btn-submit { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(139,92,246,0.35); color: white; }
    .error-card { background: white; border-radius: 12px; padding: 60px 20px; text-align: center; border: 1px solid #e5e7eb; }
    .invoice-count-badge { background: rgba(139,92,246,0.1); color: #8b5cf6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }

    /* Filter Card (compl_list style) */
    .filter-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
    .filter-card .filter-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; }
    .filter-card .filter-body { padding: 20px; }
    .filter-card .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 44px; }
    .filter-card .form-control:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); outline: none; }
    .filter-card .btn-primary { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
    .filter-card .btn-primary:hover { box-shadow: 0 4px 12px rgba(139,92,246,0.35); }
    .date-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .date-presets .btn { border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 500; }
    .date-presets .btn.active { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border-color: #7c3aed; }

    /* Per-Page & Pagination */
    .pagination-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .per-page-select { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; }
    .per-page-select select { border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 8px; font-size: 13px; cursor: pointer; }
    .per-page-select select:focus { border-color: #8b5cf6; outline: none; }
    .pagination-info-text { font-size: 13px; color: #6b7280; font-weight: 500; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .total-display { flex-direction: column; text-align: center; gap: 8px; } .customer-select-card .select-row { flex-direction: column; } }
</style>

<?php
$cust = $customer ?? null;
$inv_id_param = $inv_id ?? '';
$unbilled_items = $unbilled ?? [];
$customers_list = $customers ?? [];
$pg = $pagination ?? null;
$total_rec = $total_records ?? 0;
$pp = $per_page ?? 20;
$df = $date_from ?? '';
$dt = $date_to ?? '';
$date_preset = $date_preset ?? '';
$search = $search ?? '';
?>

<div class="billing-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-plus-circle"></i> <?=$xml->create ?? 'Create'?> <?=$xml->billing ?? 'Billing'?></h2>
        <div class="header-actions">
            <a href="index.php?page=billing"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <!-- Customer Selection with Smart Search -->
    <div class="customer-select-card">
        <h4><i class="fa fa-search"></i> <?=$xml->customer ?? 'Customer'?> <?=$xml->Model ?? 'Selection'?></h4>
        <div class="select-row">
            <div class="field">
                <label for="customer_select"><?=$xml->customer ?? 'Customer'?> (<?=count($customers_list)?> with unbilled invoices)</label>
                <select id="customer_select" name="customer_select" class="form-control smart-dropdown" data-placeholder="🔍 <?=$xml->search ?? 'Search'?> <?=$xml->customer ?? 'customer'?>..." data-sort-order="asc">
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->customer ?? 'Customer'?> --</option>
                    <?php foreach($customers_list as $c): ?>
                    <option value="<?=e($c['id'])?>" <?=($cust && $cust['id'] == $c['id']) ? 'selected' : ''?>><?=e($c['name_en'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn-load" onclick="loadCustomerInvoices()"><i class="fa fa-refresh"></i> Load</button>
        </div>
    </div>

    <?php if($cust): ?>
    <!-- Customer Info -->
    <div class="info-card">
        <h4><i class="fa fa-building"></i> <?=$xml->customerinfo ?? 'Customer Information'?></h4>
        <div class="info-row"><span class="info-label"><?=$xml->name ?? 'Name'?></span><span class="info-value"><?=e($cust['name_en'] ?? '')?></span></div>
        <?php if(!empty($cust['tax'])): ?><div class="info-row"><span class="info-label">Tax ID</span><span class="info-value"><?=e($cust['tax'])?></span></div><?php endif; ?>
        <?php if(!empty($cust['phone'])): ?><div class="info-row"><span class="info-label"><?=$xml->phone ?? 'Phone'?></span><span class="info-value"><?=e($cust['phone'])?></span></div><?php endif; ?>
        <?php if(!empty($cust['email'])): ?><div class="info-row"><span class="info-label"><?=$xml->email ?? 'Email'?></span><span class="info-value"><?=e($cust['email'])?></span></div><?php endif; ?>
    </div>

    <!-- Search & Date Range Filter (compl_list style) - OUTSIDE billing form to avoid nested form -->
    <div class="filter-card">
        <div class="filter-header"><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></div>
        <div class="filter-body">
            <form method="get" action="" id="filterForm">
                <input type="hidden" name="page" value="billing_make">
                <input type="hidden" name="customer_id" value="<?=e($cust['id'] ?? '')?>">
                <input type="hidden" name="per_page" value="<?=$pp?>">
                <div class="date-presets"><?= render_date_presets($date_preset, 'billing_make') ?></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-3" style="margin-bottom:12px;">
                        <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Description..." value="<?=htmlspecialchars($search)?>">
                    </div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($df)?>" placeholder="From"></div>
                    <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($dt)?>" placeholder="To"></div>
                    <div class="col-xs-12 col-sm-5" style="margin-bottom:12px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                        <a href="?page=billing_make&customer_id=<?=e($cust['id'] ?? '')?>" class="btn btn-default"><i class="fa fa-refresh"></i></a>
                        <?= render_per_page_selector($pp) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form method="post" action="index.php?page=billing_store" id="billingForm">
        <input type="hidden" name="method" value="A">
        <input type="hidden" name="customer_id" value="<?=e($cust['id'] ?? '')?>">
        <?= csrf_field() ?>

        <!-- Billing Details -->
        <div class="form-card">
            <div class="card-header"><i class="fa fa-edit" style="color:#8b5cf6;margin-right:8px"></i> Billing <?=$xml->detail ?? 'Details'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><textarea name="des" class="form-control" rows="2" placeholder="Billing description / notes"></textarea></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->datecreate ?? 'Date'?></label><input type="date" name="billing_date" class="form-control" value="<?=date('Y-m-d')?>"></div></div>
                </div>
            </div>
        </div>

        <!-- Selected Total -->
        <div class="total-display">
            <span class="total-label"><i class="fa fa-calculator"></i> Selected Total</span>
            <span class="total-value" id="totalAmount">0.00</span>
        </div>

        <!-- Column Visibility Toggle -->
        <div class="column-toggle-bar">
            <span class="toggle-label"><i class="fa fa-columns"></i> Columns</span>
            <div class="toggle-chips">
                <span class="col-chip active" data-col="col-select" onclick="toggleColumn(this)"><i class="fa fa-check"></i> Select</span>
                <span class="col-chip active" data-col="col-invno" onclick="toggleColumn(this)"><i class="fa fa-check"></i> Invoice No.</span>
                <span class="col-chip active" data-col="col-customer" onclick="toggleColumn(this)"><i class="fa fa-check"></i> <?=$xml->customer ?? 'Customer'?></span>
                <span class="col-chip active" data-col="col-desc" onclick="toggleColumn(this)"><i class="fa fa-check"></i> <?=$xml->description ?? 'Description'?></span>
                <span class="col-chip active" data-col="col-date" onclick="toggleColumn(this)"><i class="fa fa-check"></i> <?=$xml->datecreate ?? 'Date'?></span>
                <span class="col-chip active" data-col="col-total" onclick="toggleColumn(this)"><i class="fa fa-check"></i> Total</span>
                <span class="col-chip active" data-col="col-vat" onclick="toggleColumn(this)"><i class="fa fa-check"></i> VAT</span>
                <span class="col-chip active" data-col="col-grand" onclick="toggleColumn(this)"><i class="fa fa-check"></i> <?=$xml->grandtotal ?? 'Grand Total'?></span>
            </div>
        </div>

        <!-- Invoice Table -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-file-text-o" style="color:#8b5cf6;margin-right:8px"></i> Unbilled Invoices</span>
                <span class="invoice-count-badge"><?=$total_rec?> invoice<?=$total_rec != 1 ? 's' : ''?><?php if($df || $dt || $search) echo ' (filtered)'; ?></span>
            </div>
            <div class="table-responsive">
                <table id="invoiceTable">
                    <thead>
                        <tr>
                            <th class="col-select" style="width:40px"><input type="checkbox" class="invoice-checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th class="col-invno">Invoice No.</th>
                            <th class="col-customer"><?=$xml->customer ?? 'Customer'?></th>
                            <th class="col-desc"><?=$xml->description ?? 'Description'?></th>
                            <th class="col-date"><?=$xml->datecreate ?? 'Date'?></th>
                            <th class="col-total">Total</th>
                            <th class="col-vat">VAT</th>
                            <th class="col-grand"><?=$xml->grandtotal ?? 'Grand Total'?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($unbilled_items)): foreach($unbilled_items as $i => $inv):
                            $subtotal = floatval($inv['subtotal'] ?? 0);
                            $vat_pct = floatval($inv['vat'] ?? 0);
                            $dis_pct = floatval($inv['discount'] ?? 0);
                            $wh_pct = floatval($inv['withholding'] ?? 0);
                            $after_disc = $subtotal * (1 - $dis_pct / 100);
                            $vat_amt = $after_disc * ($vat_pct / 100);
                            $wh_amt = $after_disc * ($wh_pct / 100);
                            $inv_total = $after_disc + $vat_amt;
                            $is_preselected = ($inv_id_param && $inv_id_param == $inv['id']);
                        ?>
                        <tr class="<?=$is_preselected ? 'selected-row' : ''?>">
                            <td class="col-select"><input type="checkbox" name="invoices[]" value="<?=e($inv['id'])?>" class="invoice-checkbox inv-check" data-amount="<?=$inv_total?>" <?=$is_preselected ? 'checked' : ''?> onchange="toggleRow(this);calcTotal()"></td>
                            <td class="col-invno" style="font-weight:600;color:#8b5cf6"><?=e($inv['inv_no'] ?? $inv['id'])?></td>
                            <td class="col-customer"><?=e($cust['name_en'] ?? '')?></td>
                            <td class="col-desc"><?=e($inv['des'] ?? '')?></td>
                            <td class="col-date"><?=e($inv['iv_date'] ?? '')?></td>
                            <td class="col-total amount-col"><?=number_format($subtotal, 2)?></td>
                            <td class="col-vat amount-col"><?=$vat_amt > 0 ? number_format($vat_amt, 2) : '-'?></td>
                            <td class="col-grand amount-col" style="font-weight:700"><?=number_format($inv_total, 2)?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-check-circle" style="font-size:28px;display:block;margin-bottom:8px"></i>All invoices are billed</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Bar -->
        <?php if($pg && $pg['total_pages'] > 0): ?>
        <div style="text-align:center;margin-bottom:24px">
            <div class="pagination-info-text" style="margin-bottom:8px">Showing <?=$pg['start_record']?>-<?=$pg['end_record']?> of <?=$pg['total_records']?> invoices</div>
            <?php if($pg['total_pages'] > 1): ?>
            <?= render_pagination($pg, 'index.php?page=billing_make', ['customer_id' => $cust['id'] ?? '', 'date_from' => $df, 'date_to' => $dt, 'search' => $search, 'per_page' => $pp]) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if(!empty($unbilled_items)): ?>
        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-check"></i> <?=$xml->create ?? 'Create'?> <?=$xml->billing ?? 'Billing'?></button>
        </div>
        <?php endif; ?>
    </form>

    <?php elseif(!empty($customers_list)): ?>
    <!-- No customer selected yet -->
    <div class="error-card">
        <i class="fa fa-hand-pointer-o" style="font-size:48px;display:block;margin-bottom:12px;color:#8b5cf6"></i>
        <p style="font-size:16px;font-weight:600;color:#374151"><?=$xml->select ?? 'Select'?> a <?=$xml->customer ?? 'customer'?> above to view unbilled invoices</p>
        <p style="font-size:13px;color:#6b7280;margin-top:8px"><?=count($customers_list)?> customers have unbilled invoices</p>
    </div>

    <?php else: ?>
    <!-- No customers with unbilled invoices -->
    <div class="error-card">
        <i class="fa fa-check-circle" style="font-size:48px;display:block;margin-bottom:12px;color:#10b981"></i>
        <p style="font-size:16px;font-weight:600;color:#374151">All invoices are billed!</p>
        <a href="index.php?page=billing" style="display:inline-block;margin-top:16px;color:#8b5cf6;font-weight:600"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to list'?></a>
    </div>
    <?php endif; ?>
</div>

<script>
/* Customer selection — navigate to load invoices */
function loadCustomerInvoices() {
    var sel = document.getElementById('customer_select');
    var customerId = sel.value;
    if (!customerId) { alert('Please select a customer'); return; }
    var url = 'index.php?page=billing_make&customer_id=' + customerId;
    window.location.href = url;
}

/* Per-page selector - update hidden field and submit form */
function changePerPage(val) {
    var form = document.getElementById('filterForm');
    if (form) {
        var ppField = form.querySelector('input[name="per_page"]');
        if (ppField) ppField.value = val;
        form.submit();
    }
}

/* Also load on select change */
document.getElementById('customer_select').addEventListener('change', function() {
    if (this.value) loadCustomerInvoices();
});

/* Checkbox row toggle */
function toggleRow(cb) {
    var tr = cb.closest('tr');
    if (cb.checked) { tr.classList.add('selected-row'); } else { tr.classList.remove('selected-row'); }
}

/* Select All / Deselect All */
function toggleAll(master) {
    document.querySelectorAll('.inv-check').forEach(function(cb) {
        cb.checked = master.checked;
        toggleRow(cb);
    });
    calcTotal();
}

/* Calculate selected total */
function calcTotal() {
    var total = 0;
    document.querySelectorAll('.inv-check:checked').forEach(function(cb) {
        total += parseFloat(cb.dataset.amount) || 0;
    });
    var el = document.getElementById('totalAmount');
    if (el) el.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* Column visibility toggle */
function toggleColumn(chip) {
    var col = chip.getAttribute('data-col');
    chip.classList.toggle('active');
    var isVisible = chip.classList.contains('active');
    var icon = chip.querySelector('i');
    icon.className = isVisible ? 'fa fa-check' : 'fa fa-eye-slash';

    document.querySelectorAll('.' + col).forEach(function(cell) {
        cell.style.display = isVisible ? '' : 'none';
    });
    saveColumnState();
}

function saveColumnState() {
    var state = {};
    document.querySelectorAll('.col-chip').forEach(function(chip) {
        state[chip.getAttribute('data-col')] = chip.classList.contains('active');
    });
    localStorage.setItem('billing_make_columns', JSON.stringify(state));
}

function restoreColumnState() {
    var saved = localStorage.getItem('billing_make_columns');
    if (!saved) return;
    try {
        var state = JSON.parse(saved);
        document.querySelectorAll('.col-chip').forEach(function(chip) {
            var col = chip.getAttribute('data-col');
            if (state.hasOwnProperty(col)) {
                var isVisible = state[col];
                if (isVisible) {
                    chip.classList.add('active');
                    chip.querySelector('i').className = 'fa fa-check';
                } else {
                    chip.classList.remove('active');
                    chip.querySelector('i').className = 'fa fa-eye-slash';
                }
                document.querySelectorAll('.' + col).forEach(function(cell) {
                    cell.style.display = isVisible ? '' : 'none';
                });
            }
        });
    } catch(e) {}
}

// Initialize
calcTotal();
restoreColumnState();
</script>
