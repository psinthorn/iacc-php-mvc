<?php
/**
 * Voucher View — Legacy Modern Design
 * Variables: $voucher, $products, $id
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .voucher-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(231,76,60,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #e74c3c; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; text-align: right; max-width: 60%; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f9fafb; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-draft { background: #fef3c7; color: #d97706; }
    .status-confirmed { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    .summary-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 40px; }
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
    .summary-item { text-align: center; padding: 12px; }
    .summary-item .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
    .summary-item .value { font-size: 20px; font-weight: 700; color: #1f2937; }
    .summary-item .value.total { color: #e74c3c; font-size: 24px; }
    .error-card { background: white; border-radius: 12px; padding: 60px 20px; text-align: center; color: #ef4444; border: 1px solid #e5e7eb; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<?php if(!$voucher): ?>
<div class="voucher-wrapper">
    <div class="page-header"><h2><i class="fa fa-exclamation-triangle"></i> <?=$xml->error ?? 'Error'?></h2></div>
    <div class="error-card"><i class="fa fa-exclamation-triangle" style="font-size:48px;display:block;margin-bottom:12px"></i><p style="font-size:16px;font-weight:600"><?=$xml->nodata ?? 'Voucher not found'?></p>
        <a href="index.php?page=voucher_list" style="display:inline-block;margin-top:16px;color:#e74c3c;font-weight:600"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a></div>
</div>
<?php return; endif; ?>

<?php
$vc = $voucher;
$vc_id = $id ?? ($vc['id'] ?? '');
$st = $vc['status'] ?? 'draft';
$st_class = $st == 'confirmed' ? 'status-confirmed' : ($st == 'cancelled' ? 'status-cancelled' : 'status-draft');
$vat_pct = floatval($vc['include_vat'] ?? 0);
$dis_pct = floatval($vc['dis'] ?? $vc['discount'] ?? 0);
?>

<div class="voucher-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-credit-card"></i> <?=$xml->voucher ?? 'Voucher'?> #<?=e($vc['vou_rw'] ?? $vc_id)?></h2>
        <div class="header-actions">
            <a href="index.php?page=voucher_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
            <?php if($st !== 'cancelled'): ?>
            <a href="index.php?page=voucher_make&id=<?=e($vc_id)?>"><i class="fa fa-pencil"></i> <?=$xml->edits ?? 'Edit'?></a>
            <?php endif; ?>
            <a href="index.php?page=voucher_print&id=<?=e($vc_id)?>" target="_blank"><i class="fa fa-print"></i> <?=$xml->print ?? 'Print'?></a>
        </div>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <h4><i class="fa fa-info-circle"></i> <?=$xml->voucher ?? 'Voucher'?> <?=$xml->detail ?? 'Details'?></h4>
            <div class="info-row"><span class="info-label"><?=$xml->voucher ?? 'Voucher'?>#</span><span class="info-value"><?=e($vc['vou_rw'] ?? $vc_id)?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->datecreate ?? 'Date'?></span><span class="info-value"><?=e($vc['createdate'] ?? '')?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->status ?? 'Status'?></span><span class="info-value"><span class="status-badge <?=$st_class?>"><?=e($st)?></span></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->description ?? 'Description'?></span><span class="info-value"><?=e($vc['description'] ?? '')?></span></div>
        </div>
        <div class="info-card">
            <h4><i class="fa fa-user"></i> <?=$xml->supplier ?? 'Vendor'?> / Payee</h4>
            <div class="info-row"><span class="info-label"><?=$xml->name ?? 'Name'?></span><span class="info-value"><?=e($vc['name'] ?? '')?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->email ?? 'Email'?></span><span class="info-value"><?=e($vc['email'] ?? '')?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->phone ?? 'Phone'?></span><span class="info-value"><?=e($vc['phone'] ?? '')?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->payment ?? 'Payment'?></span><span class="info-value"><?=e($vc['payment_method'] ?? '')?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->supplier ?? 'Vendor'?></span><span class="info-value"><?=e($vc['vender'] ?? '')?></span></div>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-cubes" style="color:#e74c3c;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>#</th><th><?=$xml->Product ?? 'Product'?></th><th><?=$xml->brand ?? 'Brand'?></th><th><?=$xml->model ?? 'Model'?></th><th><?=$xml->Unit ?? 'Qty'?></th><th><?=$xml->Price ?? 'Price'?></th><th><?=$xml->discount ?? 'Disc.'?></th><th><?=$xml->total ?? 'Amount'?></th></tr>
                </thead>
                <tbody>
                    <?php
                    $subtotal = 0;
                    if(!empty($products)): foreach($products as $i => $p):
                        $qty = floatval($p['quantity'] ?? $p['qty'] ?? 0);
                        $price = floatval($p['price'] ?? 0);
                        $disc = floatval($p['discount'] ?? 0);
                        $amount = ($price * $qty) - ($disc * $qty);
                        $subtotal += $amount;
                    ?>
                    <tr>
                        <td><?=$i+1?></td>
                        <td><strong><?=e($p['type_name'] ?? '')?></strong></td>
                        <td><?=e($p['brand_name'] ?? '-')?></td>
                        <td><?=e($p['model_name'] ?? '-')?></td>
                        <td><?=number_format($qty)?></td>
                        <td><?=number_format($price, 2)?></td>
                        <td><?=$disc > 0 ? number_format($disc, 2) : '-'?></td>
                        <td><strong><?=number_format($amount, 2)?></strong></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox"></i> <?=$xml->nodata ?? 'No products'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $discount_amt = $subtotal * ($dis_pct / 100);
    $after_disc = $subtotal - $discount_amt;
    $vat_amt = $after_disc * ($vat_pct / 100);
    $grand = $after_disc + $vat_amt;
    ?>
    <div class="summary-card">
        <div class="summary-grid">
            <div class="summary-item"><div class="label"><?=$xml->total ?? 'Subtotal'?></div><div class="value"><?=number_format($subtotal, 2)?></div></div>
            <div class="summary-item"><div class="label"><?=$xml->discount ?? 'Discount'?> (<?=$dis_pct?>%)</div><div class="value" style="color:#ef4444">-<?=number_format($discount_amt, 2)?></div></div>
            <div class="summary-item"><div class="label">VAT (<?=$vat_pct?>%)</div><div class="value" style="color:#d97706">+<?=number_format($vat_amt, 2)?></div></div>
            <div class="summary-item"><div class="label"><?=$xml->grandtotal ?? 'Grand Total'?></div><div class="value total"><?=number_format($grand, 2)?></div></div>
        </div>
    </div>
</div>
