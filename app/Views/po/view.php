<?php
/**
 * PO View (Detail) — Legacy Modern Design
 * Variables: $po, $id, $products, $has_labour, $payment_methods
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .po-view-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(102,126,234,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .quo-number { background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; margin-left: 8px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; text-align: right; }
    .products-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .products-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .products-card .table { margin: 0; font-size: 13px; }
    .products-card .table thead th { background: #f9fafb; color: #1f2937; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 12px 16px; border-bottom: 2px solid #e5e7eb; border-top: none; }
    .products-card .table tbody td { padding: 12px 16px; border-color: #f3f4f6; vertical-align: middle; }
    .products-card .table tbody tr:hover { background: rgba(102,126,234,0.03); }
    .summary-grid { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; margin-bottom: 24px; }
    .summary-grid table { width: 100%; max-width: 400px; margin-left: auto; }
    .summary-grid table td { padding: 8px 0; font-size: 14px; }
    .summary-grid .grand-total { font-size: 18px; font-weight: 700; color: #667eea; border-top: 2px solid #e5e7eb; padding-top: 12px; }
    .confirm-card { background: white; border-radius: 12px; border: 2px solid #10b981; padding: 24px; margin-bottom: 24px; }
    .confirm-card h4 { margin: 0 0 16px 0; color: #10b981; font-weight: 600; }
    .confirm-card .btn-confirm { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; }
    .confirm-card .btn-confirm:hover { box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
    .payment-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; margin-bottom: 24px; overflow: hidden; }
    .payment-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
    .payment-methods-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; padding: 20px; }
    .pm-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; transition: box-shadow 0.2s; }
    .pm-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .pm-item .pm-type { display: inline-flex; align-items: center; gap: 6px; background: #f0f9ff; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-bottom: 10px; }
    .pm-item .pm-name { font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .pm-item .pm-detail { font-size: 13px; color: #6b7280; }
    .error-card { background: white; border-radius: 12px; border: 1px solid #fecaca; padding: 40px; text-align: center; }
    .error-card i { font-size: 48px; color: #f87171; margin-bottom: 16px; }
    .status-badge { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.confirmed { background: #dbeafe; color: #3b82f6; }
    .status-badge.delivered { background: #d1fae5; color: #10b981; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .info-cards { grid-template-columns: 1fr; } }
</style>

<div class="po-view-wrapper">
<?php if(!$po): ?>
    <div class="error-card">
        <i class="fa fa-exclamation-triangle"></i>
        <h3><?=$xml->nodata ?? 'PO not found'?></h3>
        <p><a href="index.php?page=qa_list" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a></p>
    </div>
<?php else:
    $isThaiLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1);
    $statusLabels = $isThaiLang
        ? ['0'=>'รอดำเนินการ','1'=>'ใบเสนอราคา','2'=>'ยืนยันแล้ว','3'=>'จัดส่งแล้ว','4'=>'ออกใบแจ้งหนี้แล้ว','5'=>'เสร็จสิ้น']
        : ['0'=>'Pending','1'=>'Quotation','2'=>'Confirmed','3'=>'Delivered','4'=>'Invoiced','5'=>'Completed'];
    $statusClasses = ['0'=>'pending','1'=>'pending','2'=>'confirmed','3'=>'delivered','4'=>'delivered','5'=>'delivered'];
?>
    <div class="page-header">
        <h2>
            <i class="fa fa-file-text"></i> <?=$xml->purchasingorder ?? 'Purchase Order'?>
            <span class="quo-number">QUO-<?=e($po['tax'] ?? '')?></span>
            <span class="status-badge <?=$statusClasses[$po['status']] ?? 'pending'?>"><?=$statusLabels[$po['status']] ?? ($isThaiLang ? 'ไม่ทราบ' : 'Unknown')?></span>
        </h2>
        <div class="header-actions">
            <a href="index.php?page=qa_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
            <?php if($po['status']=='1'): ?>
            <a href="index.php?page=po_edit&id=<?=$id?>"><i class="fa fa-pencil"></i> <?=$xml->edit ?? 'Edit'?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <h4><i class="fa fa-info-circle"></i> <?=$xml->quotation ?? 'Quotation'?> <?=$xml->information ?? 'Info'?></h4>
            <div class="info-row"><span class="info-label"><?=$xml->description ?? 'Description'?></span><span class="info-value"><?=e($po['name'])?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->duedate ?? 'Due Date'?></span><span class="info-value"><?=e($po['valid_pay'])?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->date ?? 'Date'?></span><span class="info-value"><?=e($po['date'] ?? '')?></span></div>
            <?php if(!empty($po['po_ref'])): ?>
            <div class="info-row"><span class="info-label"><?=$xml->po_reference ?? ($isThaiLang ? 'อ้างอิง PO' : 'PO Reference')?></span><span class="info-value"><?=e($po['po_ref'])?></span></div>
            <?php endif; ?>
        </div>

        <div class="info-card">
            <h4><i class="fa fa-building"></i> <?=$xml->customer ?? 'Customer'?></h4>
            <div class="info-row"><span class="info-label"><?=$xml->company ?? 'Company'?></span><span class="info-value"><?=e($po['name_en'] ?? '')?></span></div>
            <?php if(!empty($po['address'])): ?>
            <div class="info-row"><span class="info-label"><?=$xml->address ?? 'Address'?></span><span class="info-value"><?=e($po['address'])?></span></div>
            <?php endif; ?>
        </div>

        <?php if(!empty($po['pic'])): ?>
        <div class="info-card">
            <h4><i class="fa fa-paperclip"></i> <?=$xml->file ?? 'File'?></h4>
            <div class="info-row">
                <span class="info-label"><?=$xml->attachment ?? 'Attachment'?></span>
                <span class="info-value"><a href="upload/<?=e($po['pic'])?>" target="_blank"><i class="fa fa-download"></i> <?=$xml->view ?? 'View'?></a></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Products -->
    <div class="products-card">
        <div class="card-header"><i class="fa fa-cubes"></i> <?=$xml->Product ?? 'Products'?></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr>
                    <th>#</th><th><?=$xml->Product ?? 'Product'?></th><th><?=$xml->model ?? 'Model'?></th>
                    <th><?=$xml->description ?? 'Description'?></th><th><?=$xml->Unit ?? 'Qty'?></th>
                    <th class="text-right"><?=$xml->Price ?? 'Price'?></th>
                    <?php if($has_labour): ?><th><?=$xml->labour ?? ($isThaiLang ? 'ค่าแรง' : 'Labour')?></th><th class="text-right"><?=$xml->labour_price ?? ($isThaiLang ? 'ราคาค่าแรง' : 'L.Price')?></th><?php endif; ?>
                    <th class="text-right"><?=$xml->Total ?? 'Amount'?></th>
                </tr></thead>
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
                    <td><?=e($p['type_name'] ?? '')?><?php if(!empty($p['brand_name'])): ?> <small style="color:#6b7280">(<?=e($p['brand_name'])?>)</small><?php endif; ?></td>
                    <td><?=e($p['model_name'] ?? '-')?></td>
                    <td><?=e($p['des'] ?? '')?></td>
                    <td><?=e($p['quantity'])?></td>
                    <td class="text-right"><?=number_format(floatval($p['price']),2)?></td>
                    <?php if($has_labour): ?>
                    <td><?=$p['activelabour']?'<i class="fa fa-check text-success"></i>':'<i class="fa fa-minus text-muted"></i>'?></td>
                    <td class="text-right"><?=number_format(floatval($p['valuelabour']??0),2)?></td>
                    <?php endif; ?>
                    <td class="text-right"><strong><?=number_format($amount,2)?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
    <div class="summary-grid">
        <table>
            <tr><td><?=$xml->subtotal ?? 'Subtotal'?>:</td><td class="text-right"><?=number_format($subtotal,2)?></td></tr>
            <?php if($dis > 0): ?><tr><td><?=$xml->discount ?? 'Discount'?>:</td><td class="text-right">-<?=number_format($dis,2)?></td></tr><?php endif; ?>
            <?php if($vatRate > 0): ?><tr><td>VAT (<?=$vatRate?>%):</td><td class="text-right"><?=number_format($vatAmount,2)?></td></tr><?php endif; ?>
            <?php if($overRate > 0): ?><tr><td><?=$xml->withholding ?? ($isThaiLang ? 'หัก ณ ที่จ่าย' : 'Withholding')?> (<?=$overRate?>%):</td><td class="text-right">-<?=number_format($overAmount,2)?></td></tr><?php endif; ?>
            <tr class="grand-total"><td><?=$xml->grandtotal ?? 'Grand Total'?>:</td><td class="text-right"><?=number_format($grandTotal,2)?></td></tr>
        </table>
    </div>

    <!-- Confirm Form (status=1) -->
    <?php if($po['status'] == '1'): ?>
    <div class="confirm-card">
        <h4><i class="fa fa-check-circle"></i> <?=$xml->confirm ?? 'Confirm'?> <?=$xml->quotation ?? 'Quotation'?></h4>
        <form method="post" action="index.php?page=po_store" enctype="multipart/form-data">
            <input type="hidden" name="method" value="C">
            <input type="hidden" name="ref" value="<?=e($po['pr_id'])?>">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label><?=$xml->po_reference ?? ($isThaiLang ? 'อ้างอิง PO' : 'PO Reference')?></label><input type="text" name="po_ref" class="form-control" style="border-radius:8px"></div></div>
                <div class="col-md-4"><div class="form-group"><label><?=$xml->file ?? 'Upload File'?></label><input type="file" name="file" class="form-control" style="border-radius:8px"></div></div>
                <div class="col-md-4" style="padding-top:25px"><button type="submit" class="btn-confirm"><i class="fa fa-check"></i> <?=$xml->confirm ?? 'Confirm'?> PO</button></div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Payment Methods -->
    <?php if(!empty($payment_methods)): ?>
    <div class="payment-card">
        <div class="card-header"><i class="fa fa-university"></i> <?=$xml->paymentmethod ?? 'Payment Methods'?></div>
        <div class="payment-methods-grid">
            <?php foreach($payment_methods as $pm): ?>
            <div class="pm-item">
                <div class="pm-type">
                    <i class="fa fa-<?=($pm['method_type']=='bank'?'university':($pm['method_type']=='promptpay'?'qrcode':'credit-card'))?>"></i>
                    <?=e($pm['method_type'])?>
                </div>
                <div class="pm-name"><?=e($pm['method_name'])?></div>
                <div class="pm-detail"><?=e($pm['account_name'])?></div>
                <div class="pm-detail"><strong><?=e($pm['account_number'])?></strong></div>
                <?php if(!empty($pm['branch'])): ?><div class="pm-detail"><?=e($pm['branch'])?></div><?php endif; ?>
                <?php if(!empty($pm['qr_image'])): ?><div style="margin-top:8px"><img src="upload/<?=e($pm['qr_image'])?>" style="max-width:120px;border-radius:8px" alt="QR"></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>
</div>
