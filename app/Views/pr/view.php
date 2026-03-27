<?php
/**
 * PR View / Detail
 * Variables: $pr, $products, $id
 */
global $xml;
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text-o"></i> <?=$xml->purchasingrequest ?? 'Purchase Request'?> #<?=e($id)?></h2>
    <a href="index.php?page=pr_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if(!$pr): ?>
<div class="alert alert-warning">Purchase Request not found or you do not have access.</div>
<?php else: ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>PR Details</strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3"><label>Name:</label><br><?=e($pr['name'])?></div>
            <div class="col-md-3"><label>Customer:</label><br><?=e($pr['customer_name'] ?? '')?></div>
            <div class="col-md-3"><label>Vendor:</label><br><?=e($pr['vendor_name'] ?? '')?></div>
            <div class="col-md-3"><label>Date:</label><br><?=e($pr['createdate'] ?? $pr['date'] ?? '')?></div>
        </div>
        <?php if(!empty($pr['des'])): ?>
        <div class="row" style="margin-top:10px"><div class="col-md-12"><label>Description:</label><br><?=nl2br(e($pr['des']))?></div></div>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($products)): ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong><?=$xml->Product ?? 'Products'?></strong></div>
    <table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th><?=$xml->Product ?? 'Product'?></th><th><?=$xml->Unit ?? 'Quantity'?></th><th><?=$xml->Price ?? 'Price'?></th><th><?=$xml->Total ?? 'Total'?></th></tr></thead>
        <tbody>
        <?php $grandTotal=0; foreach($products as $i=>$p):
            $total = floatval($p['quantity']) * floatval($p['price']);
            $grandTotal += $total; ?>
            <tr>
                <td><?=$i+1?></td>
                <td><?=e($p['type_name'] ?? 'Product #'.$p['type'])?></td>
                <td><?=e($p['quantity'])?></td>
                <td><?=number_format(floatval($p['price']),2)?></td>
                <td><?=number_format($total,2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4" class="text-right"><strong><?=$xml->summary ?? 'Total'?>:</strong></td><td><strong><?=number_format($grandTotal,2)?></strong></td></tr></tfoot>
    </table>
</div>
<?php endif; endif; ?>
</div>
