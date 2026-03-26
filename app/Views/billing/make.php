<?php
/**
 * Billing Make/Edit View
 * Variables: $billing, $invoices (unbilled), $billing_invoices (for edit), $customer, $id
 */
$isEdit = !empty($billing);
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-<?=$isEdit?'pencil':'plus-circle'?>"></i> <?=$isEdit?'Edit':'Create'?> Billing</h2>
    <a href="index.php?page=billing" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="post" action="index.php?page=billing_store" id="billingForm">
    <input type="hidden" name="method" value="<?=$isEdit?'E':'A'?>">
    <?php if($isEdit): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
    <?php echo csrf_field(); ?>

    <?php if($isEdit && $customer): ?>
    <div class="alert alert-info"><strong>Customer:</strong> <?=e($customer['name_en']??'')?></div>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Select Invoices</strong></div>
        <div class="panel-body">
            <?php if(empty($invoices) && !$isEdit): ?>
                <div class="alert alert-warning">No unbilled invoices found.</div>
            <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead><tr><th style="width:40px"><input type="checkbox" id="checkAll"></th><th>Invoice#</th><th>Customer</th><th>PO Ref</th><th class="text-right">Amount</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                $displayInvoices = $isEdit ? ($billing_invoices ?? []) : ($invoices ?? []);
                foreach($displayInvoices as $iv):
                    $checked = $isEdit ? 'checked' : '';
                ?>
                    <tr class="invoice-row" onclick="toggleRow(this)">
                        <td><input type="checkbox" name="invoice_ids[]" value="<?=$iv['id']?>" class="inv-check" <?=$checked?>></td>
                        <td><?=e($iv['tax'] ?? '')?></td>
                        <td><?=e($iv['name_en'] ?? '')?></td>
                        <td><?=e($iv['po_ref'] ?? '-')?></td>
                        <td class="text-right inv-amount" data-amount="<?=floatval($iv['calculated_amount'] ?? $iv['amount'] ?? 0)?>"><?=number_format(floatval($iv['calculated_amount'] ?? $iv['amount'] ?? 0), 2)?></td>
                        <td><?=e($iv['createdate'] ?? '')?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Description</label><textarea name="des" class="form-control" rows="2"><?=e($billing['description']??'')?></textarea></div></div>
                <div class="col-md-6">
                    <div class="well" style="text-align:right;font-size:1.5em">
                        <strong>Total: <span id="totalAmount">0.00</span></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> <?=$isEdit?'Update':'Create'?> Billing</button></div>
</form>
</div>
<script>
function toggleRow(tr) {
    var cb = tr.querySelector('.inv-check');
    if(event.target !== cb) cb.checked = !cb.checked;
    calcTotal();
}
function calcTotal() {
    var total = 0;
    document.querySelectorAll('.inv-check:checked').forEach(function(cb) {
        var row = cb.closest('tr');
        total += parseFloat(row.querySelector('.inv-amount').dataset.amount || 0);
    });
    document.getElementById('totalAmount').textContent = total.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
}
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.inv-check').forEach(cb => cb.checked = this.checked);
    calcTotal();
});
document.querySelectorAll('.inv-check').forEach(cb => cb.addEventListener('change', calcTotal));
calcTotal();
</script>
