<?php
/**
 * Voucher Make/Edit View
 * Variables: $voucher, $products, $types, $id
 */
$isEdit = !empty($voucher);
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-<?=$isEdit?'pencil':'plus-circle'?>"></i> <?=$isEdit?'Edit':'Create'?> Voucher</h2>
    <a href="index.php?page=voucher_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="post" action="index.php?page=voucher_store" id="vocForm">
    <input type="hidden" name="method" value="<?=$isEdit?'E':'A'?>">
    <?php if($isEdit): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Voucher Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Vendor/Payee Name</label><input type="text" name="name" class="form-control" value="<?=e($voucher['name']??'')?>" required></div></div>
                <div class="col-md-4"><div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?=e($voucher['email']??'')?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?=e($voucher['phone']??'')?>"></div></div>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Brand</label><input type="number" name="brandven" class="form-control" value="<?=e($voucher['brand']??0)?>"></div></div>
                <div class="col-md-3"><div class="form-group"><label>VAT</label><input type="number" name="vat" class="form-control" value="<?=e($voucher['vat']??0)?>" step="0.01"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Discount</label><input type="number" name="dis" class="form-control" value="<?=e($voucher['discount']??0)?>" step="0.01"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach(['draft','confirmed','cancelled'] as $s): ?>
                            <option value="<?=$s?>" <?=($voucher['status']??'draft')===$s?'selected':''?>><?=ucfirst($s)?></option>
                        <?php endforeach; ?>
                    </select></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <?php foreach(['cash','transfer','cheque','credit_card'] as $pm): ?>
                            <option value="<?=$pm?>" <?=($voucher['payment_method']??'cash')===$pm?'selected':''?>><?=ucfirst(str_replace('_',' ',$pm))?></option>
                        <?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Description</label><textarea name="des" class="form-control" rows="2"><?=e($voucher['description']??'')?></textarea></div></div>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products</strong> <button type="button" class="btn btn-xs btn-success pull-right" id="addRow"><i class="fa fa-plus"></i></button></div>
        <div class="panel-body" style="overflow-x:auto">
            <table class="table table-bordered"><thead><tr><th>Product</th><th>Brand</th><th>Model</th><th>Qty</th><th>Price</th><th>Desc</th><th>Warranty</th><th></th></tr></thead>
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
                    <td><input type="text" name="des[]" class="form-control input-sm" value="<?=e($p['des']??'')?>"></td>
                    <td><input type="date" name="warranty[]" class="form-control input-sm" value="<?=e(isset($p['vo_warranty'])?date('Y-m-d',strtotime($p['vo_warranty'])):date('Y-m-d'))?>"></td>
                    <td><button type="button" class="btn btn-xs btn-danger removeRow"><i class="fa fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> <?=$isEdit?'Update':'Create'?> Voucher</button></div>
</form>
</div>
<script>
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
