<?php
/**
 * Receipt Make/Edit View
 * Variables: $receipt, $products, $id, $types, $quotations, $invoices
 */
$isEdit = !empty($receipt);
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-<?=$isEdit?'pencil':'plus-circle'?>"></i> <?=$isEdit?'Edit':'Create'?> Receipt</h2>
    <a href="index.php?page=receipt_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="post" action="index.php?page=receipt_store" id="repForm">
    <input type="hidden" name="method" value="<?=$isEdit?'E':'A'?>">
    <?php if($isEdit): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Receipt Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Source Type</label>
                    <select name="source_type" class="form-control" id="sourceType" onchange="toggleSource()">
                        <option value="manual" <?=($receipt['source_type']??'manual')==='manual'?'selected':''?>>Manual Entry</option>
                        <option value="quotation" <?=($receipt['source_type']??'')==='quotation'?'selected':''?>>From Quotation</option>
                        <option value="invoice" <?=($receipt['source_type']??'')==='invoice'?'selected':''?>>From Invoice</option>
                    </select></div></div>
                <div class="col-md-4" id="quotationGroup" style="display:none"><div class="form-group"><label>Quotation</label>
                    <select name="source_id_q" class="form-control">
                        <option value="">--</option>
                        <?php foreach($quotations as $q): ?><option value="<?=$q['id']?>" <?=($receipt['source_id']??'')==$q['id']&&($receipt['source_type']??'')==='quotation'?'selected':''?>><?=e($q['tax'])?></option><?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-4" id="invoiceGroup" style="display:none"><div class="form-group"><label>Invoice</label>
                    <select name="source_id_i" class="form-control">
                        <option value="">--</option>
                        <?php foreach($invoices as $iv): ?><option value="<?=$iv['id']?>" <?=($receipt['source_id']??'')==$iv['id']&&($receipt['source_type']??'')==='invoice'?'selected':''?>><?=e($iv['tax'])?></option><?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach(['draft','confirmed','cancelled'] as $s): ?>
                            <option value="<?=$s?>" <?=($receipt['status']??'draft')===$s?'selected':''?>><?=ucfirst($s)?></option>
                        <?php endforeach; ?>
                    </select></div></div>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <?php foreach(['cash','transfer','cheque','credit_card'] as $pm): ?>
                            <option value="<?=$pm?>" <?=($receipt['payment_method']??'cash')===$pm?'selected':''?>><?=ucfirst(str_replace('_',' ',$pm))?></option>
                        <?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-3"><div class="form-group"><label>VAT (%)</label><input type="number" name="vat" class="form-control" value="<?=e($receipt['vat']??0)?>" step="0.01"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Discount</label><input type="number" name="dis" class="form-control" value="<?=e($receipt['discount']??0)?>" step="0.01"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Brand</label><input type="number" name="brand" class="form-control" value="<?=e($receipt['brand']??0)?>"></div></div>
            </div>
            <div class="row">
                <div class="col-md-12"><div class="form-group"><label>Description</label><textarea name="des" class="form-control" rows="2"><?=e($receipt['description']??'')?></textarea></div></div>
            </div>
        </div>
    </div>

    <!-- Products (for manual) -->
    <div class="panel panel-default" id="manualProducts">
        <div class="panel-heading"><strong>Products</strong> <button type="button" class="btn btn-xs btn-success pull-right" id="addRow"><i class="fa fa-plus"></i></button></div>
        <div class="panel-body" style="overflow-x:auto">
            <table class="table table-bordered"><thead><tr><th>Product</th><th>Brand</th><th>Model</th><th>Qty</th><th>Price</th><th>Desc</th><th></th></tr></thead>
                <tbody id="productBody">
                <?php
                $rows = !empty($products) ? $products : [['type'=>'','ban_id'=>0,'model'=>0,'quantity'=>1,'price'=>0,'des'=>'']];
                foreach($rows as $p): ?>
                <tr class="product-row">
                    <td><select name="type[]" class="form-control input-sm"><option value="">--</option>
                        <?php foreach($types as $t): ?><option value="<?=$t['id']?>" <?=($t['id']==($p['type']??''))?'selected':''?>><?=e($t['name'])?></option><?php endforeach; ?>
                    </select></td>
                    <td><select name="ban_id[]" class="form-control input-sm"><option value="0">--</option></select></td>
                    <td><select name="model[]" class="form-control input-sm"><option value="0">--</option></select></td>
                    <td><input type="number" name="quantity[]" class="form-control input-sm" value="<?=e($p['quantity']??1)?>" min="1"></td>
                    <td><input type="number" name="price[]" class="form-control input-sm" value="<?=e($p['price']??0)?>" step="0.01"></td>
                    <td><input type="text" name="desP[]" class="form-control input-sm" value="<?=e($p['des']??'')?>"></td>
                    <td><button type="button" class="btn btn-xs btn-danger removeRow"><i class="fa fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> <?=$isEdit?'Update':'Create'?> Receipt</button></div>
</form>
</div>
<script>
function toggleSource() {
    var v = document.getElementById('sourceType').value;
    document.getElementById('quotationGroup').style.display = v==='quotation' ? '' : 'none';
    document.getElementById('invoiceGroup').style.display = v==='invoice' ? '' : 'none';
    document.getElementById('manualProducts').style.display = v==='manual' ? '' : 'none';
}
toggleSource();
document.getElementById('addRow')?.addEventListener('click', function() {
    var tbody = document.getElementById('productBody');
    var row = tbody.querySelector('.product-row').cloneNode(true);
    row.querySelectorAll('input').forEach(el => el.value = el.type==='number' ? '0' : '');
    row.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
    tbody.appendChild(row);
});
document.addEventListener('click', function(e) {
    if(e.target.closest('.removeRow') && document.querySelectorAll('.product-row').length > 1) e.target.closest('tr').remove();
});
</script>
