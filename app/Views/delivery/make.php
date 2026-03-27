<?php
/**
 * Delivery Make View (standalone sendout)
 * Variables: $customers, $types
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-plus-circle"></i> Create Standalone Delivery</h2>
    <a href="index.php?page=deliv_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="post" action="index.php?page=deliv_store" id="delivForm">
    <input type="hidden" name="method" value="AD">
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Delivery Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Customer</label>
                    <select name="cus_id" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php foreach($customers as $c): ?><option value="<?=$c['id']?>"><?=e($c['name_en'])?></option><?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Delivery Date</label><input type="date" name="deliver_date" class="form-control" value="<?=date('Y-m-d')?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Description</label><textarea name="des" class="form-control" rows="2"></textarea></div></div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products</strong> <button type="button" class="btn btn-xs btn-success pull-right" id="addRow"><i class="fa fa-plus"></i></button></div>
        <div class="panel-body" style="overflow-x:auto">
            <table class="table table-bordered"><thead><tr><th>Product</th><th>Brand</th><th>Model</th><th>Qty</th><th>Price</th><th>S/N</th><th>Warranty</th><th></th></tr></thead>
                <tbody id="productBody">
                <tr class="product-row">
                    <td><select name="type[]" class="form-control input-sm"><option value="">--</option>
                        <?php foreach($types as $t): ?><option value="<?=$t['id']?>"><?=e($t['name'])?></option><?php endforeach; ?>
                    </select></td>
                    <td><select name="ban_id[]" class="form-control input-sm"><option value="0">--</option></select></td>
                    <td><select name="model[]" class="form-control input-sm"><option value="0">--</option></select></td>
                    <td><input type="number" name="quantity[]" class="form-control input-sm" value="1" min="1"></td>
                    <td><input type="number" name="price[]" class="form-control input-sm" value="0" step="0.01"></td>
                    <td><input type="text" name="s_n[]" class="form-control input-sm" placeholder="Serial#"></td>
                    <td><input type="date" name="warranty[]" class="form-control input-sm" value="<?=date('Y-m-d',strtotime('+1 year'))?>"></td>
                    <td><button type="button" class="btn btn-xs btn-danger removeRow"><i class="fa fa-trash"></i></button></td>
                </tr>
                </tbody>
            </table>
            <input type="hidden" name="discount[]" value="0">
            <input type="hidden" name="pack_quantity[]" value="1">
        </div>
    </div>
    <div class="text-right"><button type="submit" class="btn btn-success btn-lg"><i class="fa fa-truck"></i> Create Delivery</button></div>
</form>
</div>
<script>
document.getElementById('addRow')?.addEventListener('click', function() {
    var tbody = document.getElementById('productBody');
    var row = tbody.querySelector('.product-row').cloneNode(true);
    row.querySelectorAll('input').forEach(el => el.value = el.type==='number' ? (el.name.includes('quantity')?'1':'0') : '');
    row.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
    tbody.appendChild(row);
});
document.addEventListener('click', function(e) {
    if(e.target.closest('.removeRow') && document.querySelectorAll('.product-row').length > 1) e.target.closest('tr').remove();
});
</script>
