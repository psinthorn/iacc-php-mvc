<?php
/**
 * Delivery Edit View
 * Variables: $detail, $products, $id, $mode, $customers
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-pencil"></i> Edit Delivery Note</h2>
    <a href="index.php?page=deliv_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if(!$detail): ?>
<div class="alert alert-warning">Delivery not found.</div>
<?php else: ?>
<form method="post" action="index.php?page=deliv_store" id="delivEditForm">
    <input type="hidden" name="method" value="ED">
    <input type="hidden" name="deliv_id" value="<?=$id?>">
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Delivery Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Customer</label>
                    <select name="cus_id" class="form-control">
                        <?php foreach($customers as $c): ?>
                            <option value="<?=$c['id']?>" <?=($c['id']==($detail['cus_id']??''))?'selected':''?>><?=e($c['name_en'])?></option>
                        <?php endforeach; ?>
                    </select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Delivery Date</label>
                    <input type="date" name="deliver_date" class="form-control" value="<?=e($detail['deliver_date'] ?? $detail['deliv_date'] ?? date('Y-m-d'))?>"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Description</label>
                    <textarea name="des" class="form-control" rows="2"><?=e($detail['tmp'] ?? $detail['description'] ?? '')?></textarea></div></div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products</strong></div>
        <table class="table table-bordered">
            <thead><tr><th>Product</th><th>S/N</th><th>Warranty</th><th>Price</th></tr></thead>
            <tbody>
            <?php foreach($products as $i => $p): ?>
            <tr>
                <td><?=e($p['type_name'] ?? '')?> <?=e($p['model_name'] ?? '')?></td>
                <td><input type="text" name="s_n[]" class="form-control input-sm" value="<?=e($p['s_n'] ?? '')?>">
                    <input type="hidden" name="type[]" value="<?=e($p['type'] ?? '')?>">
                    <input type="hidden" name="ban_id[]" value="<?=e($p['ban_id'] ?? 0)?>">
                    <input type="hidden" name="model[]" value="<?=e($p['model'] ?? 0)?>">
                    <input type="hidden" name="quantity[]" value="<?=e($p['quantity'] ?? 1)?>">
                    <input type="hidden" name="pack_quantity[]" value="<?=e($p['pack_quantity'] ?? 1)?>">
                    <input type="hidden" name="price[]" value="<?=e($p['price'] ?? 0)?>">
                    <input type="hidden" name="discount[]" value="<?=e($p['discount'] ?? 0)?>">
                    <input type="hidden" name="des[]" value="<?=e($p['des'] ?? '')?>">
                </td>
                <td><input type="date" name="exp[]" class="form-control input-sm" value="<?=e(isset($p['warranty']) ? date('Y-m-d', strtotime($p['warranty'])) : date('Y-m-d', strtotime('+1 year')))?>"></td>
                <td><?=number_format(floatval($p['price']??0),2)?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> Save Changes</button></div>
</form>
<?php endif; ?>
</div>
