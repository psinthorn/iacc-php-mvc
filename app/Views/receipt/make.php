<?php
/**
 * Receipt Make/Edit View — Legacy Modern Design
 * Variables: $receipt, $products, $id, $types, $quotations, $invoices
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/master-data.css">
<style>
    .receipt-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: visible; }
    .form-card .card-header { border-radius: 12px 12px 0 0; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; height: auto; min-height: 44px; box-sizing: border-box; }
    .form-card select.form-control { min-height: 44px; height: 44px; padding-right: 30px; }
    .form-card input[type="date"].form-control { min-height: 44px; }
    .form-card .form-control:focus { border-color: #27ae60; box-shadow: 0 0 0 3px rgba(39,174,96,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }
    .source-selector { display: flex; gap: 12px; margin-bottom: 16px; }
    .source-btn { padding: 12px 24px; border-radius: 10px; border: 2px solid #e5e7eb; background: white; cursor: pointer; font-weight: 600; font-size: 13px; color: #6b7280; transition: all 0.2s; }
    .source-btn.active { border-color: #27ae60; background: #ecfdf5; color: #059669; }
    .source-btn:hover { border-color: #27ae60; }
    .source-panel { display: none; }
    .source-panel.active { display: block; }
    .product-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative; }
    .product-item .product-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .product-item .product-number { font-weight: 700; color: #27ae60; font-size: 14px; }
    .product-item .btn-remove { background: rgba(239,68,68,0.1); color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; }
    .product-item .btn-remove:hover { background: #ef4444; color: white; }
    .btn-add-product { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-submit { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(39,174,96,0.35); color: white; }
    @media (max-width: 768px) { .master-data-header { padding: 16px 20px; } .master-data-header h2 { font-size: 18px; } }
</style>

<?php
$isEdit = !empty($receipt);
$rc = $receipt ?? [];
$rc_id = $id ?? ($rc['id'] ?? '');
$method = $isEdit ? 'E' : 'A';
$title = $isEdit ? ($xml->edits ?? 'Edit') : ($xml->create ?? 'Create');
$source_type = $rc['source_type'] ?? 'direct';
?>

<div class="receipt-wrapper">
    <div class="master-data-header" data-theme="emerald">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-<?=$isEdit?'pencil':'plus-circle'?>"></i> <?=$title?> <?=$xml->receipt ?? 'Receipt'?><?=$isEdit ? ' #'.e($rc_id) : ''?></h2>
            </div>
            <div class="header-actions">
                <a href="index.php?page=receipt_list" class="btn-header btn-header-primary"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
            </div>
        </div>
    </div>

    <form method="post" action="index.php?page=receipt_store" id="receiptForm">
        <input type="hidden" name="method" value="<?=$method?>">
        <?php if($isEdit): ?><input type="hidden" name="id" value="<?=e($rc_id)?>"><?php endif; ?>
        <?= csrf_field() ?>

        <div class="form-card">
            <div class="card-header"><i class="fa fa-info-circle" style="color:#27ae60;margin-right:8px"></i> <?=$xml->detail ?? 'Details'?></div>
            <div class="card-body">
                <div class="source-selector">
                    <button type="button" class="source-btn <?=$source_type=='direct'?'active':''?>" onclick="toggleSource('direct')"><i class="fa fa-pencil"></i> Manual</button>
                    <?php if(!empty($quotations)): ?><button type="button" class="source-btn <?=$source_type=='quotation'?'active':''?>" onclick="toggleSource('quotation')"><i class="fa fa-file-text-o"></i> <?=$xml->quotation ?? 'Quotation'?></button><?php endif; ?>
                    <?php if(!empty($invoices)): ?><button type="button" class="source-btn <?=$source_type=='invoice'?'active':''?>" onclick="toggleSource('invoice')"><i class="fa fa-file"></i> <?=$xml->invoice ?? 'Invoice'?></button><?php endif; ?>
                </div>
                <input type="hidden" name="source_type" id="sourceType" value="<?=e($source_type)?>">

                <div id="panel_quotation" class="source-panel <?=$source_type=='quotation'?'active':''?>">
                    <div class="form-group"><label><?=$xml->quotation ?? 'Quotation'?></label>
                        <select name="quotation_id" class="form-control smart-dropdown">
                            <option value="">-- <?=$xml->select ?? 'Select'?> --</option>
                            <?php if(!empty($quotations)): foreach($quotations as $q): ?><option value="<?=e($q['id'])?>" <?=($rc['quotation_id'] ?? '') == $q['id'] ? 'selected' : ''?>><?=e($q['name_en'] ?? '')?> — <?=e($q['po_date'] ?? '')?></option><?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div id="panel_invoice" class="source-panel <?=$source_type=='invoice'?'active':''?>">
                    <div class="form-group"><label><?=$xml->invoice ?? 'Invoice'?></label>
                        <select name="invoice_id" class="form-control smart-dropdown">
                            <option value="">-- <?=$xml->select ?? 'Select'?> --</option>
                            <?php if(!empty($invoices)): foreach($invoices as $iv): ?><option value="<?=e($iv['id'])?>" <?=($rc['invoice_id'] ?? '') == $iv['id'] ? 'selected' : ''?>><?=e($iv['name_en'] ?? '')?> — <?=e($iv['iv_date'] ?? '')?></option><?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top:16px">
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->name ?? 'Name'?></label><input type="text" name="name" class="form-control" value="<?=e($rc['name'] ?? '')?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->email ?? 'Email'?></label><input type="email" name="email" class="form-control" value="<?=e($rc['email'] ?? '')?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->phone ?? 'Phone'?></label><input type="text" name="phone" class="form-control" value="<?=e($rc['phone'] ?? '')?>"></div></div>
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->payment ?? 'Payment Method'?></label><input type="text" name="payment_method" class="form-control" value="<?=e($rc['payment_method'] ?? '')?>"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->brand ?? 'Brand'?></label><input type="text" name="brand" class="form-control" value="<?=e($rc['brand'] ?? '')?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->supplier ?? 'Vendor'?></label><input type="text" name="vender" class="form-control" value="<?=e($rc['vender'] ?? '')?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>VAT (%)</label><input type="number" name="include_vat" class="form-control" value="<?=e($rc['include_vat'] ?? '7')?>" step="0.01"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><textarea name="description" class="form-control" rows="2"><?=e($rc['description'] ?? '')?></textarea></div></div>
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->status ?? 'Status'?></label>
                        <select name="status" class="form-control">
                            <option value="confirmed" <?=($rc['status'] ?? 'confirmed') == 'confirmed' ? 'selected' : ''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                            <option value="draft" <?=($rc['status'] ?? '') == 'draft' ? 'selected' : ''?>><?=$xml->draft ?? 'Draft'?></option>
                        </select></div></div>
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->discount ?? 'Discount'?> (%)</label><input type="number" name="discount" class="form-control" value="<?=e($rc['dis'] ?? $rc['discount'] ?? '0')?>" step="0.01"></div></div>
                </div>
            </div>
        </div>

        <div class="form-card" id="panel_direct" style="<?=$source_type!='direct'?'display:none':''?>">
            <div class="card-header">
                <span><i class="fa fa-cubes" style="color:#27ae60;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></span>
                <button type="button" class="btn-add-product" id="addProductRow"><i class="fa fa-plus"></i> <?=$xml->addnew ?? 'Add'?></button>
            </div>
            <div class="card-body" id="productsContainer">
                <?php if(!empty($products)): foreach($products as $i => $p): ?>
                <div class="product-item" data-index="<?=$i?>">
                    <div class="product-header"><span class="product-number">#<?=$i+1?></span><button type="button" class="btn-remove removeRow"><i class="fa fa-times"></i></button></div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->Product ?? 'Product'?></label>
                            <select name="type[]" class="form-control smart-dropdown">
                                <option value="">--</option>
                                <?php if(!empty($types)): foreach($types as $t): ?><option value="<?=$t['id']?>" <?=($p['type'] ?? $p['type_id'] ?? '') == $t['id'] ? 'selected' : ''?>><?=e($t['name'])?></option><?php endforeach; endif; ?>
                            </select></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label><input type="number" name="quantity[]" class="form-control" value="<?=e($p['quantity'] ?? $p['qty'] ?? 1)?>" min="1"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Price ?? 'Price'?></label><input type="number" name="price[]" class="form-control" value="<?=e($p['price'] ?? 0)?>" step="0.01"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->discount ?? 'Discount'?></label><input type="number" name="pdiscount[]" class="form-control" value="<?=e($p['discount'] ?? 0)?>" step="0.01"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Warranty</label><input type="date" name="warranty[]" class="form-control" value="<?=e($p['warranty'] ?? '')?>"></div></div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="product-item" data-index="0">
                    <div class="product-header"><span class="product-number">#1</span><button type="button" class="btn-remove removeRow"><i class="fa fa-times"></i></button></div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->Product ?? 'Product'?></label>
                            <select name="type[]" class="form-control smart-dropdown"><option value="">--</option>
                                <?php if(!empty($types)): foreach($types as $t): ?><option value="<?=$t['id']?>"><?=e($t['name'])?></option><?php endforeach; endif; ?>
                            </select></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label><input type="number" name="quantity[]" class="form-control" value="1" min="1"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Price ?? 'Price'?></label><input type="number" name="price[]" class="form-control" value="0" step="0.01"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->discount ?? 'Discount'?></label><input type="number" name="pdiscount[]" class="form-control" value="0" step="0.01"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Warranty</label><input type="date" name="warranty[]" class="form-control"></div></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-save"></i> <?=$xml->save ?? 'Save'?> <?=$xml->receipt ?? 'Receipt'?></button>
        </div>
    </form>
</div>
<script>
function toggleSource(src) {
    document.getElementById('sourceType').value = src;
    document.querySelectorAll('.source-btn').forEach(b => b.classList.remove('active'));
    event.target.closest('.source-btn').classList.add('active');
    document.querySelectorAll('.source-panel').forEach(p => p.classList.remove('active'));
    var panel = document.getElementById('panel_' + src);
    if(panel) panel.classList.add('active');
    document.getElementById('panel_direct').style.display = src === 'direct' ? '' : 'none';
}
var productIdx = <?=max(count($products ?? []), 1)?>;
document.getElementById('addProductRow')?.addEventListener('click', function() {
    var container = document.getElementById('productsContainer');
    var first = container.querySelector('.product-item');
    var clone = first.cloneNode(true);
    clone.dataset.index = productIdx;
    clone.querySelector('.product-number').textContent = '#' + (productIdx+1);
    clone.querySelectorAll('input').forEach(el => { if(el.type==='number') el.value=el.name.includes('quantity')?'1':'0'; else el.value=''; });
    // Remove cloned SmartDropdown wrappers, restore original selects
    clone.querySelectorAll('.sd-container').forEach(wrapper => {
        var select = wrapper.querySelector('select');
        if (select) {
            select.style.display = '';
            select.selectedIndex = 0;
            select._smartDropdown = null;
            wrapper.parentNode.insertBefore(select, wrapper);
        }
        wrapper.remove();
    });
    clone.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
    container.appendChild(clone);
    // Initialize SmartDropdown on new row
    if (typeof SmartDropdown !== 'undefined') {
        clone.querySelectorAll('.smart-dropdown').forEach(function(select) {
            if (!select._smartDropdown) new SmartDropdown(select);
        });
    }
    productIdx++;
});
document.addEventListener('click', function(e) {
    if(e.target.closest('.removeRow') && document.querySelectorAll('.product-item').length > 1) {
        e.target.closest('.product-item').remove();
        document.querySelectorAll('.product-item').forEach((item, i) => item.querySelector('.product-number').textContent = '#'+(i+1));
    }
});
</script>
