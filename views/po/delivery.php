<?php
/**
 * PO Delivery View
 * Variables: $po, $id, $action, $products
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-truck"></i> Process Delivery - <?=e($po['tax'] ?? '')?></h2>
    <a href="index.php?page=po_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if(!$po): ?>
<div class="alert alert-warning">PO not found or not available for delivery.</div>
<?php else: ?>
<form method="post" action="index.php?page=deliv_store" id="delivForm">
    <input type="hidden" name="method" value="<?=e($action)?>">
    <input type="hidden" name="po_id" value="<?=$id?>">
    <input type="hidden" name="ref" value="<?=e($po['pr_id'])?>">
    <input type="hidden" name="cus_id" value="<?=e($po['cus_id'])?>">
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><label>PO:</label> <?=e($po['tax'])?> - <?=e($po['name'])?></div>
                <div class="col-md-4"><label>Customer:</label> <?=e($po['name_en'])?></div>
                <div class="col-md-4"><div class="form-group"><label>Delivery Date</label><input type="date" name="deliver_date" class="form-control" value="<?=date('Y-m-d')?>"></div></div>
            </div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Payment Method</label>
                    <select name="payby" class="form-control"><option value="0">-- Select --</option></select>
                </div></div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products & Serial Numbers</strong></div>
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Product</th><th>Model</th><th>Qty</th><th>Serial Number</th><th>Warranty Expiry</th></tr></thead>
            <tbody>
            <?php foreach($products as $i => $p): ?>
                <?php for($q = 0; $q < intval($p['quantity']); $q++): ?>
                <tr>
                    <td><?=$i+1?>.<?=$q+1?></td>
                    <td><?=e($p['type_name'] ?? '')?></td>
                    <td><?=e($p['model_name'] ?? '')?></td>
                    <td>1</td>
                    <td>
                        <input type="hidden" name="pro_id[]" value="<?=e($p['pro_id'])?>">
                        <?php if($action == 'c'): ?>
                            <input type="text" name="sn[]" class="form-control input-sm" placeholder="Auto-generate if empty">
                        <?php else: ?>
                            <select name="sn[]" class="form-control input-sm"><option value="">-- Select from stock --</option></select>
                        <?php endif; ?>
                    </td>
                    <td><input type="date" name="exp[]" class="form-control input-sm" value="<?=date('Y-m-d', strtotime('+1 year'))?>"></td>
                </tr>
                <?php endfor; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-right"><button type="submit" class="btn btn-success btn-lg"><i class="fa fa-truck"></i> Process Delivery</button></div>
</form>
<?php endif; ?>
</div>
