<?php
$pageTitle = 'Invoices — Details';

/**
 * Invoice Detail View (MVC)
 * Replaces legacy compl-view.php
 */
?>
<style>
.view-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
.page-header-v { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(139,92,246,0.25); display: flex; justify-content: space-between; align-items: center; }
.page-header-v h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.btn-back { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
.btn-back:hover { background: rgba(255,255,255,0.25); color: white; }
.content-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.card-hdr { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 24px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; }
.card-hdr i { color: #8b5cf6; }
.card-bdy { padding: 24px; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
.info-item { display: flex; flex-direction: column; gap: 6px; }
.info-item label { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.info-item .value { font-size: 15px; color: #1f2937; padding: 12px 16px; background: #f9fafb; border-radius: 10px; border: 1px solid #e5e7eb; }
.products-table { width: 100%; border-collapse: collapse; }
.products-table th { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 14px 16px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; }
.products-table td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.products-table tbody tr:hover { background: #faf5ff; }
.summary-section { background: #f9fafb; border-top: 2px solid #e5e7eb; padding: 20px 24px; }
.summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; min-width: 320px; }
.summary-row.total { border-top: 2px solid #8b5cf6; margin-top: 10px; padding-top: 15px; }
.summary-row.total .label, .summary-row.total .amount { font-size: 18px; font-weight: 700; color: #8b5cf6; }
.payment-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: #ecfdf5; border-radius: 8px; margin-bottom: 8px; }
.payment-form-section { background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 2px solid #e9d5ff; border-radius: 12px; padding: 24px; margin-top: 24px; }
.remaining-badge { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; padding: 8px 16px; border-radius: 8px; font-weight: 600; }
.paid-badge { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; padding: 8px 16px; border-radius: 8px; font-weight: 600; }
.action-buttons { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
.btn-void { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-complete { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-print-inv { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-print-inv:hover { color: white; }
.btn-pay { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.error-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 60px 40px; text-align: center; border: 1px solid #e5e7eb; }
/* Split Invoice Styles */
.split-info-card { background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 2px solid #e9d5ff; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; }
.split-info-card h4 { margin: 0 0 16px; color: #7c3aed; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.split-sibling-list { display: flex; flex-direction: column; gap: 10px; }
.split-sibling-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: white; border-radius: 8px; border: 1px solid #e5e7eb; }
.split-sibling-item.current { border: 2px solid #8b5cf6; background: #faf5ff; }
.split-type-tag { display: inline-flex; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.split-type-tag.material { background: #dbeafe; color: #1e40af; }
.split-type-tag.labour { background: #fef3c7; color: #92400e; }
.split-type-tag.full { background: #f3f4f6; color: #374151; }
</style>

<script>
function paymentcheck() {
    var v = parseFloat(document.getElementById("volumn").value);
    var t = parseFloat(document.getElementById("total").value);
    if (v > t) { alert("Payment amount cannot exceed remaining balance"); document.getElementById("volumn").focus(); return false; }
    return true;
}
</script>

<div class="view-wrapper">
<?php if($hasData): ?>

<div class="page-header-v">
    <div>
        <h2><i class="fa fa-file-text-o"></i> <?=$xml->invoice ?? 'Invoice'?></h2>
        <div style="margin-top:8px; opacity:0.9; font-size:14px;"><?=htmlspecialchars($data['name'] ?? '')?></div>
    </div>
    <div style="display:flex; align-items:center; gap:16px;">
        <?php if($accu != 0): ?><span class="remaining-badge"><i class="fa fa-clock-o"></i> <?=$xml->remaining ?? 'Remaining'?>: ฿<?=number_format($accu, 2)?></span>
        <?php else: ?><span class="paid-badge"><i class="fa fa-check-circle"></i> <?=$xml->fullypaid ?? 'Fully Paid'?></span><?php endif; ?>
        <a href="index.php?page=compl_list" class="btn-back"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
    </div>
</div>

<?php if (!empty($data['split_group_id']) && !empty($split_siblings) && count($split_siblings) > 1): ?>
<div class="split-info-card">
    <h4><i class="fa fa-clone"></i> <?=$xml->splitinvoicegroup ?? 'Split Invoice Group'?>
        <span class="split-type-tag <?=htmlspecialchars($data['split_type'] ?? 'full')?>"><?= ($data['split_type'] ?? 'full') === 'material' ? ($xml->materials ?? 'Materials') : (($data['split_type'] ?? 'full') === 'labour' ? ($xml->labour ?? 'Labour') : ($xml->full ?? 'Full')) ?></span>
    </h4>
    <div class="split-sibling-list">
    <?php foreach ($split_siblings as $sib):
        $isCurrent = intval($sib['id']) === $id;
        $sibType = $sib['split_type'] ?? 'full';
        $sibLabel = $sibType === 'material' ? ($xml->materials ?? 'Materials') : ($sibType === 'labour' ? ($xml->labour ?? 'Labour') : ($xml->full ?? 'Full'));
        $sibAmount = floatval($sib['subtotal'] ?? 0);
    ?>
        <div class="split-sibling-item <?=$isCurrent ? 'current' : ''?>">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-weight:600; color:#8b5cf6;">INV-<?=htmlspecialchars($sib['inv_no'] ?? $sib['po_tax'] ?? '')?></span>
                <span class="split-type-tag <?=$sibType?>"><?=$sibLabel?></span>
                <?php if ($isCurrent): ?><span style="background:#dcfce7; color:#059669; padding:2px 8px; border-radius:8px; font-size:11px; font-weight:600;"><?=$xml->current ?? 'Current'?></span><?php endif; ?>
                <?php if (floatval($sib['over'] ?? 0) > 0): ?><span style="background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:8px; font-size:11px; font-weight:600;">WHT <?=$sib['over']?>%</span><?php endif; ?>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-weight:600; color:#1f2937;">฿<?=number_format($sibAmount, 2)?></span>
                <?php if (!$isCurrent): ?><a href="index.php?page=compl_view&id=<?=intval($sib['id'])?>" style="color:#8b5cf6; font-size:13px;"><i class="fa fa-external-link"></i> <?=$xml->view ?? 'View'?></a><?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="content-card">
    <div class="card-hdr"><i class="fa fa-info-circle"></i> <?=$xml->details ?? 'Invoice Details'?></div>
    <div class="card-bdy">
        <div class="info-grid">
            <div class="info-item"><label><?=$xml->vender ?? 'Vendor'?></label><div class="value"><?=htmlspecialchars($vendor['name_en'] ?? $vendor['name_sh'] ?? '')?></div></div>
            <div class="info-item"><label><?=$xml->customer ?? 'Customer'?></label><div class="value"><?=htmlspecialchars($customer['name_en'] ?? $customer['name_sh'] ?? '')?></div></div>
            <div class="info-item"><label><?=$xml->validpay ?? 'Payment Due'?></label><div class="value"><?=htmlspecialchars($data['valid_pay'] ?? '')?></div></div>
            <div class="info-item"><label><?=$xml->deliverydate ?? 'Delivery Date'?></label><div class="value"><?=htmlspecialchars($data['deliver_date'] ?? '')?></div></div>
        </div>
        <?php if(!empty($data['des'])): ?>
        <div class="info-item" style="margin-top:20px;"><label><?=$xml->description ?? 'Description'?></label><div class="value"><?=nl2br(htmlspecialchars($data['des'] ?? ''))?></div></div>
        <?php endif; ?>
    </div>
</div>

<div class="content-card">
    <div class="card-hdr"><i class="fa fa-cube"></i> <?=$xml->products ?? 'Products'?>
        <span style="margin-left:auto; background:#8b5cf6; color:white; padding:4px 12px; border-radius:20px; font-size:12px;"><?=count($products)?> <?=$xml->items ?? 'items'?></span>
    </div>
    <table class="products-table"><thead><tr>
        <th style="width:15%"><?=$xml->model ?? 'Model'?></th><th><?=$xml->product ?? 'Product'?></th>
        <th class="text-center" style="width:8%"><?=$xml->unit ?? 'Qty'?></th><th class="text-right" style="width:10%"><?= ($isLabourInvoice ?? false) ? ($xml->labourrate ?? 'Labour Rate') : ($xml->price ?? 'Price') ?></th>
        <?php if($hasLabour): ?><th class="text-right" style="width:10%"><?=$xml->equipment ?? 'Equipment'?></th>
        <th class="text-right" style="width:8%"><?=$xml->labour ?? 'Labour'?></th><th class="text-right" style="width:10%"><?=$xml->labourtotal ?? 'L.Total'?></th><?php endif; ?>
        <th class="text-right" style="width:10%"><?=$xml->amount ?? 'Amount'?></th>
    </tr></thead><tbody>
    <?php foreach($products as $prod): ?>
    <tr>
        <td><?=htmlspecialchars($prod['model'] ?? '')?></td><td><?=htmlspecialchars($prod['name'] ?? '')?></td>
        <td class="text-center"><?=intval($prod['quantity'])?></td><td class="text-right"><?=number_format($prod['price'], 2)?></td>
        <?php if($hasLabour): ?><td class="text-right"><?=number_format($prod['equip'], 2)?></td>
        <td class="text-right"><?=number_format($prod['labour1'], 2)?></td><td class="text-right"><?=number_format($prod['labour'], 2)?></td><?php endif; ?>
        <td class="text-right"><?=number_format($prod['total'], 2)?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    <div class="summary-section"><div style="display:flex; justify-content:flex-end;">
        <div>
            <div class="summary-row"><span class="label"><?=$xml->subtotal ?? 'Subtotal'?></span><span class="amount"><?=number_format($summary, 2)?></span></div>
            <?php if($data['dis'] > 0): ?><div class="summary-row"><span class="label"><?=$xml->discount ?? 'Discount'?> <?=$data['dis']?>%</span><span class="amount">- <?=number_format($disc, 2)?></span></div><?php endif; ?>
            <?php if($data['over'] > 0): ?><div class="summary-row"><span class="label"><?=$xml->overhead ?? 'Overhead'?> <?=$data['over']?>%</span><span class="amount">+ <?=number_format($overh, 2)?></span></div><?php endif; ?>
            <div class="summary-row"><span class="label"><?=$xml->net ?? 'Net Amount'?></span><span class="amount"><?=number_format($subt, 2)?></span></div>
            <div class="summary-row"><span class="label"><?=$xml->vat ?? 'VAT'?> <?=$data['vat']?>%</span><span class="amount">+ <?=number_format($vat, 2)?></span></div>
            <div class="summary-row total"><span class="label"><?=$xml->grandtotal ?? 'Grand Total'?></span><span class="amount"><?=number_format($totalnet, 2)?></span></div>
            <?php if(!empty($payments)): ?>
            <div style="margin-top:16px; padding-top:16px; border-top:1px dashed #d1d5db;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:10px; font-weight:600;"><i class="fa fa-history"></i> <?=$xml->paymenthistory ?? 'Payment History'?></div>
                <?php foreach($payments as $pay): ?>
                <div class="payment-item">
                    <div><span style="color:#059669; font-weight:500;"><?=$pay['date']?></span> <span style="color:#6b7280; font-size:13px;"><?=$pay['value']?></span></div>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <span style="font-weight:600; color:#059669;"><?=number_format($pay['volumn'], 2)?></span>
                        <a href="index.php?page=pdf_split_invoice&id=<?=$pay['id']?>" target="_blank" style="color:#8b5cf6; font-size:13px;"><i class="fa fa-print"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div></div>
</div>

<?php if($accu != 0 && $data['ven_id'] == ($_SESSION['com_id'] ?? 0)): ?>
<div class="payment-form-section">
    <h4 style="margin:0 0 20px; color:#7c3aed; font-weight:600;"><i class="fa fa-credit-card"></i> <?=$xml->recordpayment ?? 'Record Payment'?></h4>
    <form action="index.php?page=invoice_store" method="post" onsubmit="return paymentcheck();">
        <?= csrf_field() ?>
        <input type="hidden" name="source_page" value="compl_list">
        <input type="hidden" name="method" value="C">
        <input type="hidden" name="po_id" value="<?=$id?>">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; align-items:end;">
            <div><label style="font-size:13px; font-weight:600; color:#6b7280;"><?=$xml->method ?? 'Payment Method'?></label>
                <select name="payment" class="form-control" style="border-radius:10px;">
                    <?php foreach($payment_methods as $pm): ?><option value="<?=$pm['id']?>"><?=htmlspecialchars($pm['payment_name'])?></option><?php endforeach; ?>
                </select></div>
            <div><label style="font-size:13px; font-weight:600; color:#6b7280;"><?=$xml->notes ?? 'Notes'?></label><input type="text" name="remark" class="form-control" style="border-radius:10px;"></div>
            <div><label style="font-size:13px; font-weight:600; color:#6b7280;"><?=$xml->amount ?? 'Amount'?></label><input type="text" id="volumn" name="volumn" class="form-control" style="border-radius:10px;" value="<?=$accu?>" required>
                <input type="hidden" name="total" id="total" value="<?=$accu?>"></div>
            <div><button type="submit" class="btn-pay"><i class="fa fa-check"></i> <?=$xml->pay ?? 'Record Payment'?></button></div>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="action-buttons">
    <a href="index.php?page=pdf_invoice&id=<?=$id?>" target="_blank" class="btn-print-inv"><i class="fa fa-print"></i> <?=$xml->printinvoice ?? 'Print Invoice'?></a>
    <?php if($accu != 0): ?>
    <form action="index.php?page=invoice_store" method="post" style="display:inline;">
        <?= csrf_field() ?><input type="hidden" name="source_page" value="compl_list2"><input type="hidden" name="po_id" value="<?=$id?>"><input type="hidden" name="pr_id" value="<?=$refpo['ref'] ?? ''?>">
        <button type="submit" name="method" value="V" class="btn-void"><i class="fa fa-ban"></i> <?=$xml->voidinv ?? 'Void Invoice'?></button>
    </form>
    <?php else: ?>
    <form action="index.php?page=invoice_store" method="post" style="display:inline;">
        <?= csrf_field() ?><input type="hidden" name="source_page" value="compl_list2"><input type="hidden" name="po_id" value="<?=$id?>"><input type="hidden" name="pr_id" value="<?=$refpo['ref'] ?? ''?>">
        <button type="submit" name="method" value="V" class="btn-void"><i class="fa fa-ban"></i> <?=$xml->voidinv ?? 'Void Invoice'?></button>
        <button type="submit" name="method" value="C" class="btn-complete"><i class="fa fa-check-circle"></i> <?=$xml->taxinvoicem ?? 'Issue Tax Invoice'?></button>
    </form>
    <?php endif; ?>
</div>

<?php else: ?>
<div class="error-card">
    <div style="width:80px;height:80px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;"><i class="fa fa-exclamation-triangle" style="font-size:40px;color:#ef4444;"></i></div>
    <h3><?=$xml->error ?? 'Error'?></h3><p style="color:#6b7280;"><?=$xml->invoicenotfound ?? 'Invoice not found or access denied.'?></p><br>
    <a href="index.php?page=compl_list" class="btn-back" style="background:#8b5cf6; display:inline-flex;"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to List'?></a>
</div>
<?php endif; ?>
</div>
