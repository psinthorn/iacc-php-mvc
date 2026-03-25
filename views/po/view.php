<?php
/**
 * PO View (Detail)
 * Variables: $po, $id, $products, $has_labour, $payment_methods
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text"></i> Purchase Order <?=e($po['tax'] ?? '')?></h2>
    <div>
        <a href="index.php?page=qa_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        <?php if($po && $po['status']=='1'): ?>
            <a href="index.php?page=po_edit&id=<?=$id?>" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> Edit</a>
        <?php endif; ?>
    </div>
</div>

<?php if(!$po): ?>
<div class="alert alert-warning">PO not found.</div>
<?php else: ?>

<!-- PO Info -->
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3"><label>PO Number:</label><br><strong><?=e($po['tax'])?></strong></div>
            <div class="col-md-3"><label>Customer:</label><br><?=e($po['name_en'] ?? '')?></div>
            <div class="col-md-3"><label>Date:</label><br><?=e($po['date'])?></div>
            <div class="col-md-3"><label>Due Date:</label><br><?=e($po['valid_pay'])?></div>
        </div>
        <div class="row" style="margin-top:10px">
            <div class="col-md-3"><label>Description:</label><br><?=e($po['name'])?></div>
            <div class="col-md-3"><label>Status:</label><br>
                <?php $sl=['0'=>'Pending','1'=>'Quotation','2'=>'Confirmed','3'=>'Delivered','4'=>'Invoiced','5'=>'Completed']; ?>
                <span class="label label-info"><?=$sl[$po['status']]??'Unknown'?></span>
            </div>
            <?php if(!empty($po['pic'])): ?>
            <div class="col-md-3"><label>File:</label><br><a href="upload/<?=e($po['pic'])?>" target="_blank"><i class="fa fa-file"></i> View</a></div>
            <?php endif; ?>
            <?php if(!empty($po['po_ref'])): ?>
            <div class="col-md-3"><label>PO Ref:</label><br><?=e($po['po_ref'])?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Products -->
<div class="panel panel-default">
    <div class="panel-heading"><strong>Products</strong></div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th><th>Product</th><th>Model</th><th>Description</th><th>Qty</th><th>Price</th>
                <?php if($has_labour): ?><th>Labour</th><th>L.Price</th><?php endif; ?>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php $subtotal = 0; foreach($products as $i => $p):
            $lineTotal = floatval($p['price']) * floatval($p['quantity']);
            $labourTotal = floatval($p['valuelabour'] ?? 0) * intval($p['activelabour'] ?? 0) * floatval($p['quantity']);
            $discount = floatval($p['discount'] ?? 0) * floatval($p['quantity']);
            $amount = $lineTotal + $labourTotal - $discount;
            $subtotal += $amount;
        ?>
            <tr>
                <td><?=$i+1?></td>
                <td><?=e($p['type_name'] ?? '')?><?php if(!empty($p['brand_name'])): ?> <small class="text-muted">(<?=e($p['brand_name'])?>)</small><?php endif; ?></td>
                <td><?=e($p['model_name'] ?? '-')?></td>
                <td><?=e($p['des'] ?? '')?></td>
                <td><?=e($p['quantity'])?></td>
                <td class="text-right"><?=number_format(floatval($p['price']),2)?></td>
                <?php if($has_labour): ?>
                    <td><?=$p['activelabour']?'Yes':'No'?></td>
                    <td class="text-right"><?=number_format(floatval($p['valuelabour']??0),2)?></td>
                <?php endif; ?>
                <td class="text-right"><?=number_format($amount,2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Summary -->
<?php
    $dis = floatval($po['dis'] ?? 0);
    $afterDiscount = $subtotal - $dis;
    $vatRate = floatval($po['vat'] ?? 0);
    $vatAmount = $afterDiscount * ($vatRate / 100);
    $overRate = floatval($po['over'] ?? 0);
    $overAmount = $afterDiscount * ($overRate / 100);
    $grandTotal = $afterDiscount + $vatAmount - $overAmount;
?>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row"><div class="col-md-6 col-md-offset-6">
            <table class="table table-condensed">
                <tr><td>Subtotal:</td><td class="text-right"><?=number_format($subtotal,2)?></td></tr>
                <?php if($dis > 0): ?><tr><td>Discount:</td><td class="text-right">-<?=number_format($dis,2)?></td></tr><?php endif; ?>
                <?php if($vatRate > 0): ?><tr><td>VAT (<?=$vatRate?>%):</td><td class="text-right"><?=number_format($vatAmount,2)?></td></tr><?php endif; ?>
                <?php if($overRate > 0): ?><tr><td>Withholding (<?=$overRate?>%):</td><td class="text-right">-<?=number_format($overAmount,2)?></td></tr><?php endif; ?>
                <tr style="font-size:1.2em;font-weight:bold"><td>Grand Total:</td><td class="text-right"><?=number_format($grandTotal,2)?></td></tr>
            </table>
        </div></div>
    </div>
</div>

<!-- Confirm Form (for status=1, vendor view) -->
<?php if($po['status'] == '1'): ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>Confirm Quotation</strong></div>
    <div class="panel-body">
        <form method="post" action="index.php?page=po_store" enctype="multipart/form-data">
            <input type="hidden" name="method" value="C">
            <input type="hidden" name="ref" value="<?=e($po['pr_id'])?>">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>PO Reference</label><input type="text" name="po_ref" class="form-control"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Upload File</label><input type="file" name="file" class="form-control"></div></div>
                <div class="col-md-4" style="padding-top:25px"><button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Confirm PO</button></div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Payment Methods -->
<?php if(!empty($payment_methods)): ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>Bank Account Information</strong></div>
    <table class="table table-bordered">
        <thead><tr><th>Type</th><th>Bank</th><th>Account Name</th><th>Account Number</th></tr></thead>
        <tbody>
        <?php foreach($payment_methods as $pm): ?>
            <tr>
                <td><?=e($pm['method_type'])?></td>
                <td><?=e($pm['method_name'])?></td>
                <td><?=e($pm['account_name'])?></td>
                <td><?=e($pm['account_number'])?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; endif; ?>
</div>
