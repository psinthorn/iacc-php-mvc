<?php
/**
 * Receipt View (Detail)
 * Variables: $receipt, $products, $id
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text"></i> Receipt <?=e($receipt['rep_rw'] ?? '')?></h2>
    <div>
        <a href="index.php?page=receipt_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        <?php if($receipt): ?>
            <a href="index.php?page=rep_make&id=<?=$id?>" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> Edit</a>
            <a href="index.php?page=rep_print&id=<?=$id?>" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-print"></i> Print</a>
        <?php endif; ?>
    </div>
</div>

<?php if(!$receipt): ?>
<div class="alert alert-warning">Receipt not found.</div>
<?php else: $sc=match($receipt['status']??''){'confirmed'=>'success','draft'=>'warning','cancelled'=>'danger',default=>'default'}; ?>

<?php if(($receipt['source_type']??'manual') !== 'manual'): ?>
<div class="alert alert-info"><i class="fa fa-info-circle"></i> Source: <strong><?=ucfirst(e($receipt['source_type']))?></strong> #<?=e($receipt['source_id']??'N/A')?></div>
<?php endif; ?>

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3"><label>Receipt#:</label><br><strong><?=e($receipt['rep_rw'])?></strong></div>
            <div class="col-md-3"><label>Status:</label><br><span class="label label-<?=$sc?>"><?=e($receipt['status'])?></span></div>
            <div class="col-md-3"><label>Payment:</label><br><?=e($receipt['payment_method']??'-')?></div>
            <div class="col-md-3"><label>Date:</label><br><?=date('d-m-Y', strtotime($receipt['createdate']))?></div>
        </div>
        <?php if(!empty($receipt['description'])): ?>
        <div class="row" style="margin-top:10px"><div class="col-md-12"><label>Notes:</label><br><?=e($receipt['description'])?></div></div>
        <?php endif; ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><strong>Products</strong></div>
    <table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th>Product</th><th>Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php $subtotal=0; foreach($products as $i=>$p):
            $total = floatval($p['price']??0) * floatval($p['quantity']??1);
            $subtotal += $total; ?>
            <tr>
                <td><?=$i+1?></td>
                <td><?=e($p['type_name']??'')?><?php if(!empty($p['brand_name'])): ?> (<?=e($p['brand_name'])?>)<?php endif; ?></td>
                <td><?=e($p['quantity'])?></td>
                <td class="text-right"><?=number_format(floatval($p['price']??0),2)?></td>
                <td class="text-right"><?=number_format($total,2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
    $dis = floatval($receipt['discount'] ?? 0);
    $afterDis = $subtotal - $dis;
    $vatRate = floatval($receipt['vat'] ?? 0);
    $vatAmt = $afterDis * ($vatRate / 100);
    $grand = $afterDis + $vatAmt;
?>
<div class="panel panel-default"><div class="panel-body"><div class="row"><div class="col-md-6 col-md-offset-6">
    <table class="table table-condensed">
        <tr><td>Subtotal:</td><td class="text-right"><?=number_format($subtotal,2)?></td></tr>
        <?php if($dis>0): ?><tr><td>Discount:</td><td class="text-right">-<?=number_format($dis,2)?></td></tr><?php endif; ?>
        <?php if($vatRate>0): ?><tr><td>VAT (<?=$vatRate?>%):</td><td class="text-right"><?=number_format($vatAmt,2)?></td></tr><?php endif; ?>
        <tr style="font-weight:bold;font-size:1.2em"><td>Grand Total:</td><td class="text-right"><?=number_format($grand,2)?></td></tr>
    </table>
</div></div></div></div>
<?php endif; ?>
</div>
