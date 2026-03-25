<?php
/**
 * Voucher View (Detail)
 * Variables: $voucher, $products, $id
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-o"></i> Voucher <?=e($voucher['vou_rw'] ?? '')?></h2>
    <div>
        <a href="index.php?page=voucher_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        <?php if($voucher): ?>
            <a href="index.php?page=voc_make&id=<?=$id?>" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> Edit</a>
            <a href="index.php?page=vou_print&id=<?=$id?>" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-print"></i> Print</a>
        <?php endif; ?>
    </div>
</div>

<?php if(!$voucher): ?>
<div class="alert alert-warning">Voucher not found.</div>
<?php else: $sc=match($voucher['status']??''){'confirmed'=>'success','draft'=>'warning','cancelled'=>'danger',default=>'default'}; ?>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3"><label>Voucher#:</label><br><strong><?=e($voucher['vou_rw'])?></strong></div>
            <div class="col-md-3"><label>Vendor/Payee:</label><br><?=e($voucher['name'])?></div>
            <div class="col-md-3"><label>Status:</label><br><span class="label label-<?=$sc?>"><?=e($voucher['status'])?></span></div>
            <div class="col-md-3"><label>Date:</label><br><?=date('d-m-Y', strtotime($voucher['createdate']))?></div>
        </div>
        <div class="row" style="margin-top:10px">
            <div class="col-md-3"><label>Payment:</label><br><?=e($voucher['payment_method']??'-')?></div>
            <div class="col-md-3"><label>Email:</label><br><?=e($voucher['email']??'-')?></div>
            <div class="col-md-3"><label>Phone:</label><br><?=e($voucher['phone']??'-')?></div>
            <div class="col-md-3"><label>VAT/Discount:</label><br><?=e($voucher['vat']??0)?>% / <?=e($voucher['discount']??0)?></div>
        </div>
        <?php if(!empty($voucher['description'])): ?>
        <div class="row" style="margin-top:10px"><div class="col-md-12"><label>Notes:</label><br><?=e($voucher['description'])?></div></div>
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
                <td><?=e($p['type_name']??'')?><?php if(!empty($p['brand_name'])): ?> <small>(<?=e($p['brand_name'])?>)</small><?php endif; ?><?php if(!empty($p['model_name'])): ?> / <?=e($p['model_name'])?><?php endif; ?></td>
                <td><?=e($p['quantity'])?></td>
                <td class="text-right"><?=number_format(floatval($p['price']??0),2)?></td>
                <td class="text-right"><?=number_format($total,2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
    $dis = floatval($voucher['discount'] ?? 0);
    $afterDis = $subtotal - $dis;
    $vatRate = floatval($voucher['vat'] ?? 0);
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
