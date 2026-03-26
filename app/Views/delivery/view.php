<?php
/**
 * Delivery View (Detail)
 * Variables: $detail, $products, $id, $mode
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-truck"></i> Delivery Note #<?=e($id)?></h2>
    <div>
        <a href="index.php?page=deliv_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        <a href="rec.php?id=<?=$id?>" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-print"></i> Print</a>
    </div>
</div>

<?php if(!$detail): ?>
<div class="alert alert-warning">Delivery note not found.</div>
<?php else: ?>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <?php if($mode === 'ad'): ?>
                <div class="col-md-4"><label>Customer:</label><br><?=e($detail['name_sh'] ?? '')?></div>
                <div class="col-md-4"><label>Description:</label><br><?=e($detail['tmp'] ?? '')?></div>
                <div class="col-md-4"><label>Delivery Date:</label><br><?=e($detail['deliver_date'])?></div>
            <?php else: ?>
                <div class="col-md-3"><label>PO:</label><br><strong><?=e($detail['tax'] ?? '')?></strong> - <?=e($detail['name'] ?? '')?></div>
                <div class="col-md-3"><label>Customer:</label><br><?=e($detail['name_en'] ?? '')?></div>
                <div class="col-md-3"><label>Due Date:</label><br><?=e($detail['valid_pay'] ?? '')?></div>
                <div class="col-md-3"><label>Delivery Date:</label><br><?=e($detail['deliv_date'] ?? '')?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><strong>Products</strong></div>
    <table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th>Product</th><th>Model</th><th>Description</th><th>S/N</th><th>Warranty</th></tr></thead>
        <tbody>
        <?php foreach($products as $i => $p): ?>
            <tr>
                <td><?=$i+1?></td>
                <td><?=e($p['type_name'] ?? '')?></td>
                <td><?=e($p['model_name'] ?? '-')?></td>
                <td><?=e($p['des'] ?? '')?></td>
                <td><?=e($p['s_n'] ?? '-')?></td>
                <td><?=e($p['warranty'] ?? '-')?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Receive button -->
<?php if($mode !== 'ad' && ($detail['status'] ?? '') == '3'): ?>
<form method="post" action="index.php?page=deliv_store" style="display:inline">
    <input type="hidden" name="method" value="R">
    <input type="hidden" name="po_id" value="<?=e($detail['po_id'] ?? '')?>">
    <input type="hidden" name="ref" value="<?=e($detail['pr_id'] ?? '')?>">
    <input type="hidden" name="deliv_id" value="<?=$id?>">
    <?php echo csrf_field(); ?>
    <button type="submit" class="btn btn-success" onclick="return confirm('Confirm receive?')">
        <i class="fa fa-check"></i> Receive Delivery (Create Invoice)
    </button>
</form>
<?php elseif($mode === 'ad'): ?>
<form method="post" action="index.php?page=deliv_store" style="display:inline">
    <input type="hidden" name="method" value="R2">
    <input type="hidden" name="po_id" value="<?=e($detail['id'] ?? '')?>">
    <input type="hidden" name="deliv_id" value="<?=$id?>">
    <?php echo csrf_field(); ?>
    <button type="submit" class="btn btn-success" onclick="return confirm('Confirm receive?')">
        <i class="fa fa-check"></i> Receive
    </button>
</form>
<?php endif; endif; ?>
</div>
