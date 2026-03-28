<?php
/**
 * Billing Make View — Legacy Modern Design
 * Variables: $customer, $inv_id, $unbilled
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .billing-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(139,92,246,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 24px; }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #8b5cf6; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
    .form-card .form-control:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f5f3ff; }
    .data-card tbody tr.selected-row { background: #ede9fe; }
    .invoice-checkbox { width: 18px; height: 18px; accent-color: #8b5cf6; cursor: pointer; }
    .total-display { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .total-display .total-label { font-size: 14px; font-weight: 600; }
    .total-display .total-value { font-size: 28px; font-weight: 700; font-family: 'Courier New', monospace; }
    .amount-col { font-family: 'Courier New', monospace; font-weight: 600; }
    .btn-submit { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(139,92,246,0.35); color: white; }
    .error-card { background: white; border-radius: 12px; padding: 60px 20px; text-align: center; color: #ef4444; border: 1px solid #e5e7eb; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .total-display { flex-direction: column; text-align: center; gap: 8px; } }
</style>

<?php
$cust = $customer ?? null;
$inv_id_param = $inv_id ?? '';
$unbilled_items = $unbilled ?? [];
?>

<div class="billing-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-plus-circle"></i> <?=$xml->create ?? 'Create'?> <?=$xml->billing ?? 'Billing'?></h2>
        <div class="header-actions">
            <a href="index.php?page=billing"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <?php if(!$cust && empty($unbilled_items)): ?>
    <div class="error-card">
        <i class="fa fa-info-circle" style="font-size:48px;display:block;margin-bottom:12px;color:#8b5cf6"></i>
        <p style="font-size:16px;font-weight:600;color:#374151">No unbilled invoices found for this customer.</p>
        <a href="index.php?page=billing" style="display:inline-block;margin-top:16px;color:#8b5cf6;font-weight:600"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to list'?></a>
    </div>
    <?php return; endif; ?>

    <?php if($cust): ?>
    <div class="info-card">
        <h4><i class="fa fa-building"></i> <?=$xml->customerinfo ?? 'Customer Information'?></h4>
        <div class="info-row"><span class="info-label"><?=$xml->name ?? 'Name'?></span><span class="info-value"><?=e($cust['name_en'] ?? '')?></span></div>
        <?php if(!empty($cust['tax'])): ?><div class="info-row"><span class="info-label">Tax ID</span><span class="info-value"><?=e($cust['tax'])?></span></div><?php endif; ?>
        <?php if(!empty($cust['phone'])): ?><div class="info-row"><span class="info-label"><?=$xml->phone ?? 'Phone'?></span><span class="info-value"><?=e($cust['phone'])?></span></div><?php endif; ?>
        <?php if(!empty($cust['email'])): ?><div class="info-row"><span class="info-label"><?=$xml->email ?? 'Email'?></span><span class="info-value"><?=e($cust['email'])?></span></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=billing_store" id="billingForm">
        <input type="hidden" name="method" value="A">
        <?= csrf_field() ?>

        <div class="form-card">
            <div class="card-header"><i class="fa fa-edit" style="color:#8b5cf6;margin-right:8px"></i> Billing <?=$xml->detail ?? 'Details'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><textarea name="des" class="form-control" rows="2" placeholder="Billing description / notes"></textarea></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->datecreate ?? 'Date'?></label><input type="date" name="billing_date" class="form-control" value="<?=date('Y-m-d')?>"></div></div>
                </div>
            </div>
        </div>

        <div class="total-display">
            <span class="total-label"><i class="fa fa-calculator"></i> Selected Total</span>
            <span class="total-value" id="totalAmount">0.00</span>
        </div>

        <div class="data-card">
            <div class="card-header"><i class="fa fa-file-text-o" style="color:#8b5cf6;margin-right:8px"></i> Unbilled Invoices — Select to include</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="invoice-checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th><?=$xml->invoice ?? 'Invoice'?>#</th>
                            <th><?=$xml->description ?? 'Description'?></th>
                            <th><?=$xml->datecreate ?? 'Date'?></th>
                            <th><?=$xml->total ?? 'Subtotal'?></th>
                            <th>VAT</th>
                            <th><?=$xml->grandtotal ?? 'Total'?></th>
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
                            $inv_total = $after_disc + $vat_amt - ($after_disc * $wh_pct / 100);
                            $is_preselected = ($inv_id_param && $inv_id_param == $inv['id']);
                        ?>
                        <tr class="<?=$is_preselected ? 'selected-row' : ''?>">
                            <td><input type="checkbox" name="invoice_ids[]" value="<?=e($inv['id'])?>" class="invoice-checkbox inv-check" data-amount="<?=$inv_total?>" <?=$is_preselected ? 'checked' : ''?> onchange="toggleRow(this);calcTotal()"></td>
                            <td><strong><?=e($inv['id'])?></strong></td>
                            <td><?=e($inv['des'] ?? '')?></td>
                            <td><?=e($inv['iv_date'] ?? '')?></td>
                            <td class="amount-col"><?=number_format($subtotal, 2)?></td>
                            <td><?=$vat_pct ? $vat_pct.'%' : '-'?></td>
                            <td class="amount-col" style="font-weight:700"><?=number_format($inv_total, 2)?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-check-circle" style="font-size:28px;display:block;margin-bottom:8px"></i>All invoices are billed</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if(!empty($unbilled_items)): ?>
        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-check"></i> <?=$xml->create ?? 'Create'?> <?=$xml->billing ?? 'Billing'?></button>
        </div>
        <?php endif; ?>
    </form>
</div>
<script>
function toggleRow(cb) {
    var tr = cb.closest('tr');
    if(cb.checked) { tr.classList.add('selected-row'); } else { tr.classList.remove('selected-row'); }
}
function toggleAll(master) {
    document.querySelectorAll('.inv-check').forEach(function(cb) {
        cb.checked = master.checked;
        toggleRow(cb);
    });
    calcTotal();
}
function calcTotal() {
    var total = 0;
    document.querySelectorAll('.inv-check:checked').forEach(function(cb) { total += parseFloat(cb.dataset.amount) || 0; });
    document.getElementById('totalAmount').textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
// Initialize
calcTotal();
</script>
