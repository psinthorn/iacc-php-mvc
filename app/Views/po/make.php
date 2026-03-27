<?php
/**
 * PO Make View (Create PO from PR)
 * Variables: $pr, $id, $types, $models, $brands, $companies, $tmp_products, $credit
 */
$allModelsJson = json_encode($models);
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-plus-circle"></i> Create Purchase Order</h2>
    <a href="index.php?page=pr_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if(!$pr): ?>
<div class="alert alert-warning">Purchase Request not found or not available.</div>
<?php else: ?>
<form method="post" action="index.php?page=po_store" enctype="multipart/form-data" id="poForm">
    <input type="hidden" name="method" value="A">
    <input type="hidden" name="ref" value="<?=$id?>">
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>PO Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="name" class="form-control" value="<?=e($pr['name'])?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Customer</label>
                        <select name="cus_id" class="form-control">
                            <?php foreach($companies as $c): ?>
                                <option value="<?=$c['id']?>" <?=$c['id']==$pr['cus_id']?'selected':''?>><?=e($c['name_en'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Brand/Vendor</label>
                        <select name="brandven" class="form-control">
                            <option value="0">-- None --</option>
                            <?php foreach($brands as $b): ?>
                                <option value="<?=$b['id']?>"><?=e($b['brand_name'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Payment Due</label><input type="date" name="valid_pay" class="form-control" value="<?=date('Y-m-d', strtotime('+'.($credit['limit_day']??30).' days'))?>"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Delivery Date</label><input type="date" name="deliver_date" class="form-control" value="<?=date('Y-m-d', strtotime('+7 days'))?>"></div></div>
                <div class="col-md-2"><div class="form-group"><label>Discount</label><input type="number" name="dis" class="form-control" value="0" step="0.01"></div></div>
                <div class="col-md-2"><div class="form-group"><label>VAT %</label><input type="number" name="vat" class="form-control" value="7" step="0.01"></div></div>
                <div class="col-md-2"><div class="form-group"><label>Withholding %</label><input type="number" name="over" class="form-control" value="0" step="0.01"></div></div>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products</strong> <button type="button" class="btn btn-xs btn-success pull-right" id="addRow"><i class="fa fa-plus"></i> Add Row</button></div>
        <div class="panel-body" style="overflow-x:auto">
            <table class="table table-bordered" id="productTable">
                <thead><tr><th>Product</th><th>Brand</th><th>Model</th><th>Qty</th><th>Price</th><th>Description</th><th>Labour</th><th>Labour Price</th><th></th></tr></thead>
                <tbody id="productBody">
                <?php
                $initRows = !empty($tmp_products) ? $tmp_products : [['type_id'=>'','quantity'=>'1','price'=>'0']];
                foreach($initRows as $i => $tp): ?>
                <tr class="product-row">
                    <td><select name="type[]" class="form-control input-sm" onchange="onTypeChange(this)">
                        <option value="">-- Select --</option>
                        <?php foreach($types as $t): ?>
                            <option value="<?=$t['id']?>" <?=($t['id']==($tp['type_id']??''))?'selected':''?>><?=e($t['name'])?></option>
                        <?php endforeach; ?>
                    </select></td>
                    <td><select name="ban_id[]" class="form-control input-sm"><option value="0">--</option></select></td>
                    <td><select name="model[]" class="form-control input-sm"><option value="0">--</option></select></td>
                    <td><input type="number" name="quantity[]" class="form-control input-sm" value="<?=e($tp['quantity']??1)?>" min="1"></td>
                    <td><input type="number" name="price[]" class="form-control input-sm" value="<?=e($tp['price']??0)?>" step="0.01"></td>
                    <td><input type="text" name="des[]" class="form-control input-sm" value=""></td>
                    <td><select name="a_labour[]" class="form-control input-sm"><option value="0">No</option><option value="1">Yes</option></select></td>
                    <td><input type="number" name="v_labour[]" class="form-control input-sm" value="0" step="0.01"></td>
                    <td><button type="button" class="btn btn-xs btn-danger removeRow"><i class="fa fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> Create PO</button></div>
</form>
<?php endif; ?>
</div>

<script>
var allModels = <?=$allModelsJson?>;
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
function onTypeChange(el) {
    var row = el.closest('tr');
    var brandSel = row.querySelector('[name="ban_id[]"]');
    var modelSel = row.querySelector('[name="model[]"]');
    var typeId = el.value;
    // Load brands via AJAX
    if(typeId) {
        fetch('index.php?page=ajax_options&value='+typeId+'&mode=1').then(r=>r.text()).then(html=>{brandSel.innerHTML='<option value="0">--</option>'+html;});
    }
}
</script>
