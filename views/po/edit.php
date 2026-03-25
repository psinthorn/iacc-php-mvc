<?php
/**
 * PO Edit View
 * Variables: $po, $id, $products, $types, $models, $brands, $companies
 */
$allModelsJson = json_encode($models);
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-pencil"></i> Edit Purchase Order</h2>
    <a href="index.php?page=qa_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if(!$po): ?>
<div class="alert alert-warning">PO not found or not editable.</div>
<?php else: ?>
<form method="post" action="index.php?page=po_store" id="poEditForm">
    <input type="hidden" name="method" value="E">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="ref" value="<?=e($po['ref'])?>">
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>PO Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Description</label><input type="text" name="name" class="form-control" value="<?=e($po['name'])?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Customer</label>
                    <select name="cus_id" class="form-control">
                        <?php foreach($companies as $c): ?>
                            <option value="<?=$c['id']?>" <?=$c['id']==$po['cus_id']?'selected':''?>><?=e($c['name_en'])?></option>
                        <?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Brand</label>
                    <select name="brandven" class="form-control">
                        <option value="0">--</option>
                        <?php foreach($brands as $b): ?>
                            <option value="<?=$b['id']?>" <?=$b['id']==$po['bandven']?'selected':''?>><?=e($b['brand_name'])?></option>
                        <?php endforeach; ?>
                    </select></div></div>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Date</label><input type="date" name="create_date" class="form-control" value="<?=e($po['date'])?>"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Payment Due</label><input type="date" name="valid_pay" class="form-control" value="<?=e($po['valid_pay'])?>"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Delivery Date</label><input type="date" name="deliver_date" class="form-control" value="<?=e($po['deliver_date'])?>"></div></div>
                <div class="col-md-3">
                    <div class="row">
                        <div class="col-xs-4"><div class="form-group"><label>Disc</label><input type="number" name="dis" class="form-control input-sm" value="<?=e($po['dis'])?>" step="0.01"></div></div>
                        <div class="col-xs-4"><div class="form-group"><label>VAT%</label><input type="number" name="vat" class="form-control input-sm" value="<?=e($po['vat'])?>" step="0.01"></div></div>
                        <div class="col-xs-4"><div class="form-group"><label>W/H%</label><input type="number" name="over" class="form-control input-sm" value="<?=e($po['over'])?>" step="0.01"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products</strong> <button type="button" class="btn btn-xs btn-success pull-right" id="addRow"><i class="fa fa-plus"></i> Add Row</button></div>
        <div class="panel-body" style="overflow-x:auto">
            <table class="table table-bordered" id="productTable">
                <thead><tr><th>Product</th><th>Brand</th><th>Model</th><th>Qty</th><th>Pack</th><th>Price</th><th>Discount</th><th>Desc</th><th>Labour</th><th>L.Price</th><th></th></tr></thead>
                <tbody id="productBody">
                <?php foreach($products as $i => $p): ?>
                <tr class="product-row">
                    <td><select name="type[]" class="form-control input-sm">
                        <option value="">--</option>
                        <?php foreach($types as $t): ?>
                            <option value="<?=$t['id']?>" <?=$t['id']==$p['type']?'selected':''?>><?=e($t['name'])?></option>
                        <?php endforeach; ?>
                    </select></td>
                    <td><select name="ban_id[]" class="form-control input-sm">
                        <option value="0">--</option>
                        <?php foreach($brands as $b): ?>
                            <option value="<?=$b['id']?>" <?=$b['id']==$p['ban_id']?'selected':''?>><?=e($b['brand_name'])?></option>
                        <?php endforeach; ?>
                    </select></td>
                    <td><select name="model[]" class="form-control input-sm">
                        <option value="0">--</option>
                        <?php foreach($models as $m): if($m['brand_id']==$p['ban_id'] || $m['type_id']==$p['type']): ?>
                            <option value="<?=$m['id']?>" <?=$m['id']==$p['model']?'selected':''?>><?=e($m['model_name'])?></option>
                        <?php endif; endforeach; ?>
                    </select></td>
                    <td><input type="number" name="quantity[]" class="form-control input-sm" value="<?=e($p['quantity'])?>" min="1"></td>
                    <td><input type="number" name="pack_quantity[]" class="form-control input-sm" value="<?=e($p['pack_quantity']??1)?>" min="1"></td>
                    <td><input type="number" name="price[]" class="form-control input-sm" value="<?=e($p['price'])?>" step="0.01"></td>
                    <td><input type="number" name="discount[]" class="form-control input-sm" value="<?=e($p['discount']??0)?>" step="0.01"></td>
                    <td><input type="text" name="des[]" class="form-control input-sm" value="<?=e($p['des']??'')?>"></td>
                    <td><select name="a_labour[]" class="form-control input-sm"><option value="0" <?=$p['activelabour']==0?'selected':''?>>No</option><option value="1" <?=$p['activelabour']==1?'selected':''?>>Yes</option></select></td>
                    <td><input type="number" name="v_labour[]" class="form-control input-sm" value="<?=e($p['valuelabour']??0)?>" step="0.01"></td>
                    <td><button type="button" class="btn btn-xs btn-danger removeRow"><i class="fa fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> Save Changes</button></div>
</form>
<?php endif; ?>
</div>

<script>
document.getElementById('addRow')?.addEventListener('click', function() {
    var tbody = document.getElementById('productBody');
    var row = tbody.querySelector('.product-row').cloneNode(true);
    row.querySelectorAll('input').forEach(function(el) { el.value = el.type==='number' ? '0' : ''; });
    row.querySelectorAll('select').forEach(function(el) { el.selectedIndex = 0; });
    tbody.appendChild(row);
});
document.addEventListener('click', function(e) {
    if(e.target.closest('.removeRow')) {
        var rows = document.querySelectorAll('.product-row');
        if(rows.length > 1) e.target.closest('tr').remove();
    }
});
</script>
