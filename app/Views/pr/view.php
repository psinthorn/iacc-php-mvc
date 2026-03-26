<?php
/**
 * PR View / Detail
 * Variables: $pr, $products, $id
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text-o"></i> Purchase Request #<?=e($id)?></h2>
    <a href="index.php?page=pr_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if(!$pr): ?>
<div class="alert alert-warning">Purchase Request not found.</div>
<?php else: ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>PR Details</strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4"><label>Name:</label> <?=e($pr['name'])?></div>
            <div class="col-md-4"><label>Company:</label> <?=e($pr['company_name'] ?? '')?></div>
            <div class="col-md-4"><label>Date:</label> <?=e($pr['createdate'] ?? '')?></div>
        </div>
        <?php if(!empty($pr['des'])): ?>
        <div class="row" style="margin-top:10px"><div class="col-md-12"><label>Description:</label><br><?=e($pr['des'])?></div></div>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($products)): ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>Products</strong></div>
    <table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
        <?php $grandTotal=0; foreach($products as $i=>$p):
            $total = floatval($p['quantity']) * floatval($p['price']);
            $grandTotal += $total; ?>
            <tr>
                <td><?=$i+1?></td>
                <td><?=e($p['type_name'] ?? '')?></td>
                <td><?=e($p['quantity'])?></td>
                <td><?=number_format(floatval($p['price']),2)?></td>
                <td><?=number_format($total,2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4" class="text-right"><strong>Total:</strong></td><td><strong><?=number_format($grandTotal,2)?></strong></td></tr></tfoot>
    </table>
</div>
<?php endif; endif; ?>
</div>
