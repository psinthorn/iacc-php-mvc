<?php
/**
 * Receipt View — Legacy Modern Design
 * Variables: $receipt, $products, $id
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .receipt-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(39,174,96,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }

    .doc-header-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 24px; margin-bottom: 24px; }
    .doc-header-top { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #f3f4f6; }
    .doc-logo { flex-shrink: 0; }
    .doc-logo img { width: 80px; height: 80px; object-fit: contain; border-radius: 8px; border: 1px solid #e5e7eb; }
    .doc-logo .no-logo { width: 80px; height: 80px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 32px; }
    .doc-company-info { flex: 1; }
    .doc-company-info .company-name { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
    .doc-company-info .company-detail { font-size: 12px; color: #6b7280; line-height: 1.6; }
    .doc-title-badge { flex-shrink: 0; text-align: right; }
    .doc-title-badge .doc-type { background: #27ae60; color: white; padding: 8px 20px; border-radius: 8px; font-size: 16px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; display: inline-block; margin-bottom: 8px; }
    .doc-title-badge .doc-number { font-size: 15px; font-weight: 700; color: #1f2937; }
    .doc-title-badge .doc-date { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .doc-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .doc-party h5 { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #27ae60; margin: 0 0 10px 0; letter-spacing: 0.05em; display: flex; align-items: center; gap: 6px; }
    .doc-party .party-name { font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .doc-party .party-detail { font-size: 12px; color: #6b7280; line-height: 1.6; }
    .doc-meta-row { display: flex; gap: 24px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6; flex-wrap: wrap; }
    .doc-meta-item { font-size: 12px; }
    .doc-meta-item .meta-label { color: #9ca3af; font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em; }
    .doc-meta-item .meta-value { color: #1f2937; font-weight: 600; margin-top: 2px; }

    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #27ae60; display: flex; align-items: center; gap: 8px; }
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
    .source-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .source-invoice { background: #ede9fe; color: #7c3aed; }
    .source-quotation { background: #dbeafe; color: #2563eb; }
    .source-direct { background: #f3f4f6; color: #6b7280; }
    .summary-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 24px; }
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
    .summary-item { text-align: center; padding: 12px; }
    .summary-item .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
    .summary-item .value { font-size: 20px; font-weight: 700; color: #1f2937; }
    .summary-item .value.total { color: #27ae60; font-size: 24px; }
    .error-card { background: white; border-radius: 12px; padding: 60px 20px; text-align: center; color: #ef4444; border: 1px solid #e5e7eb; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<?php if(!$receipt): ?>
<div class="receipt-wrapper">
    <div class="page-header"><h2><i class="fa fa-exclamation-triangle"></i> <?=$xml->error ?? 'Error'?></h2></div>
    <div class="error-card"><i class="fa fa-exclamation-triangle" style="font-size:48px;display:block;margin-bottom:12px"></i><p style="font-size:16px;font-weight:600"><?=$xml->nodata ?? 'Receipt not found'?></p>
        <a href="index.php?page=receipt_list" style="display:inline-block;margin-top:16px;color:#27ae60;font-weight:600"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a></div>
</div>
<?php return; endif; ?>

<?php
$rc = $receipt;
$rc_id = $id ?? ($rc['id'] ?? '');
$st = $rc['status'] ?? 'draft';
$st_class = $st == 'confirmed' ? 'status-confirmed' : ($st == 'cancelled' ? 'status-cancelled' : 'status-draft');
$src = $rc['source_type'] ?? 'direct';
$src_class = $src == 'invoice' ? 'source-invoice' : ($src == 'quotation' ? 'source-quotation' : 'source-direct');
$vat_pct = floatval($rc['include_vat'] ?? 0);
$dis_pct = floatval($rc['dis'] ?? $rc['discount'] ?? 0);
?>

<div class="receipt-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-file-text"></i> <?=$xml->receipt ?? 'Receipt'?> #<?=e($rc['rep_rw'] ?? $rc_id)?></h2>
        <div class="header-actions">
            <a href="index.php?page=receipt_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
            <?php if($st !== 'cancelled'): ?>
            <a href="index.php?page=receipt_make&id=<?=e($rc_id)?>"><i class="fa fa-pencil"></i> <?=$xml->edits ?? 'Edit'?></a>
            <?php endif; ?>
            <a href="index.php?page=receipt_print&id=<?=e($rc_id)?>" target="_blank"><i class="fa fa-print"></i> <?=$xml->print ?? 'Print'?></a>
        </div>
    </div>

    <?php
    // Vendor info
    $v_logo = $rc['vendor_logo'] ?? '';
    $v_name = $rc['vendor_name'] ?? '';
    $v_addr = implode(' ', array_filter([$rc['vendor_address'] ?? '', $rc['vendor_district'] ?? '', $rc['vendor_city'] ?? '', $rc['vendor_province'] ?? '', $rc['vendor_zip'] ?? '']));
    $v_phone = $rc['vendor_phone'] ?? '';
    $v_fax = $rc['vendor_fax'] ?? '';
    $v_email = $rc['vendor_email'] ?? '';
    $v_tax = $rc['vendor_tax'] ?? '';
    // Customer info
    $c_name = $rc['cust_name'] ?? $rc['name'] ?? '';
    $c_addr = implode(' ', array_filter([$rc['cust_address'] ?? '', $rc['cust_district'] ?? '', $rc['cust_city'] ?? '', $rc['cust_province'] ?? '', $rc['cust_zip'] ?? '']));
    $c_phone = $rc['cust_phone'] ?? $rc['phone'] ?? '';
    $c_email = $rc['cust_email'] ?? $rc['email'] ?? '';
    $c_fax = $rc['cust_fax'] ?? '';
    $c_tax = $rc['cust_tax'] ?? '';
    // Source doc
    $source_no = $rc['source_doc_no'] ?? '';
    $source_date = $rc['source_doc_date'] ?? '';
    ?>

    <!-- Document Header -->
    <div class="doc-header-card">
        <div class="doc-header-top">
            <div class="doc-logo">
                <?php if ($v_logo && file_exists("upload/{$v_logo}")): ?>
                    <img src="upload/<?=e($v_logo)?>" alt="<?=e($v_name)?>">
                <?php else: ?>
                    <div class="no-logo"><i class="fa fa-building"></i></div>
                <?php endif; ?>
            </div>
            <div class="doc-company-info">
                <div class="company-name"><?=e($v_name)?></div>
                <div class="company-detail">
                    <?php if ($v_addr): ?><?=e($v_addr)?><br><?php endif; ?>
                    <?php if ($v_phone): ?>Tel: <?=e($v_phone)?><?php endif; ?>
                    <?php if ($v_fax): ?> &nbsp;|&nbsp; Fax: <?=e($v_fax)?><?php endif; ?>
                    <?php if ($v_email): ?> &nbsp;|&nbsp; Email: <?=e($v_email)?><?php endif; ?>
                    <?php if ($v_tax): ?><br>Tax ID: <?=e($v_tax)?><?php endif; ?>
                </div>
            </div>
            <div class="doc-title-badge">
                <div class="doc-type"><?=$xml->receipt ?? 'Receipt'?></div>
                <div class="doc-number">#<?=e($rc['rep_rw'] ?? $rc_id)?></div>
                <div class="doc-date"><?=e($rc['createdate'] ?? '')?></div>
            </div>
        </div>

        <div class="doc-parties">
            <div class="doc-party">
                <h5><i class="fa fa-user"></i> <?=$xml->customerinfo ?? 'Customer'?></h5>
                <div class="party-name"><?=e($c_name)?></div>
                <div class="party-detail">
                    <?php if ($c_addr): ?><?=e($c_addr)?><br><?php endif; ?>
                    <?php if ($c_phone): ?>Tel: <?=e($c_phone)?><?php endif; ?>
                    <?php if ($c_fax): ?> &nbsp;|&nbsp; Fax: <?=e($c_fax)?><?php endif; ?>
                    <?php if ($c_email): ?><br>Email: <?=e($c_email)?><?php endif; ?>
                    <?php if ($c_tax): ?><br>Tax ID: <?=e($c_tax)?><?php endif; ?>
                </div>
            </div>
            <div class="doc-party">
                <h5><i class="fa fa-info-circle"></i> <?=$xml->detail ?? 'Details'?></h5>
                <div class="party-detail">
                    <strong><?=$xml->status ?? 'Status'?>:</strong> <span class="status-badge <?=$st_class?>"><?=e($st)?></span><br>
                    <strong>Source:</strong> <span class="source-badge <?=$src_class?>"><?=e($src)?></span>
                    <?php if ($source_no): ?><br><strong>Ref:</strong> PO-<?=e($source_no)?><?php endif; ?>
                    <?php if ($source_date): ?><br><strong>Ref Date:</strong> <?=e($source_date)?><?php endif; ?>
                    <?php if ($rc['payment_method'] ?? ''): ?><br><strong><?=$xml->payment ?? 'Payment'?>:</strong> <?=e($rc['payment_method'])?><?php endif; ?>
                    <?php if ($rc['payment_ref'] ?? ''): ?><br><strong>Ref#:</strong> <?=e($rc['payment_ref'])?><?php endif; ?>
                    <?php if ($rc['payment_date'] ?? ''): ?><br><strong>Pay Date:</strong> <?=e($rc['payment_date'])?><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-cubes" style="color:#27ae60;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></div>
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
