<?php
/**
 * Voucher Make/Edit View — Legacy Modern Design
 * Variables: $voucher, $products, $types, $models, $models_by_type, $brands, $vendor_brands, $payment_methods, $id
 * Field names match Voucher::createVoucher() expectations: des, brandven, vat, dis, ban_id[], model[], etc.
 */

// Prepare models JSON for client-side cascading dropdown (same pattern as PO make)
$allModelsJson = json_encode($models_by_type ?? [], JSON_HEX_TAG | JSON_HEX_APOS);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .voucher-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(231,76,60,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; width: 100%; box-sizing: border-box; }
    .form-card .form-control:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.3px; }
    .form-card select.form-control { height: auto; padding: 10px 14px; background: white; }
    .product-section .card-header { background: #e74c3c; color: white; }
    .product-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative; }
    .product-item .product-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .product-item .product-number { font-weight: 700; color: #e74c3c; font-size: 14px; }
    .product-item .btn-remove { background: rgba(239,68,68,0.1); color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.2s; }
    .product-item .btn-remove:hover { background: #ef4444; color: white; }
    .btn-add-product { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-add-product:hover { box-shadow: 0 4px 12px rgba(231,76,60,0.3); }
    .btn-submit { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(231,76,60,0.35); color: white; transform: translateY(-1px); }
    .btn-preview { background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 14px 24px; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-right: 10px; transition: all 0.2s; }
    .btn-preview:hover { box-shadow: 0 4px 12px rgba(52,152,219,0.3); color: white; }
    .product-notes textarea { min-height: 50px; margin-top: 8px; }
    .input-addon { display: flex; align-items: center; }
    .input-addon .form-control { border-radius: 8px 0 0 8px; }
    .input-addon .addon-text { background: #f3f4f6; border: 1px solid #e5e7eb; border-left: none; padding: 10px 12px; border-radius: 0 8px 8px 0; font-size: 13px; color: #6b7280; white-space: nowrap; }
    .action-bar { display: flex; align-items: center; gap: 12px; justify-content: flex-end; margin-bottom: 40px; padding-top: 10px; }
    @media (max-width: 768px) {
        .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; }
        .action-bar { flex-direction: column; } .action-bar button { width: 100%; justify-content: center; }
    }
</style>

<?php
$isEdit = !empty($voucher);
$vc = $voucher ?? [];
$vc_id = $id ?? ($vc['id'] ?? '');
$method = $isEdit ? 'E' : 'A';
$title = $isEdit ? ($xml->edits ?? 'Edit') : ($xml->create ?? 'Create');
?>

<div class="voucher-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-<?=$isEdit?'pencil':'plus-circle'?>"></i> <?=$title?> <?=$xml->voucher ?? 'Voucher'?><?=$isEdit ? ' #'.e($vc['vou_rw'] ?? $vc_id) : ''?></h2>
        <div class="header-actions">
            <a href="index.php?page=voucher_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <form method="post" action="index.php?page=voucher_store" id="voucherForm">
        <input type="hidden" name="method" value="<?=$method?>">
        <?php if($isEdit): ?><input type="hidden" name="id" value="<?=e($vc_id)?>"><?php endif; ?>
        <input type="hidden" name="countloop" id="countloop" value="<?=max(count($products ?? []), 1)?>">
        <?= csrf_field() ?>

        <!-- Vendor / Payee Information -->
        <div class="form-card">
            <div class="card-header"><i class="fa fa-user" style="color:#e74c3c;margin-right:8px"></i> <?=$xml->vendorinfo ?? 'Vendor / Payee Information'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->name ?? 'Name'?> *</label><input type="text" name="name" class="form-control" value="<?=e($vc['name'] ?? '')?>" required placeholder="<?=$xml->name ?? 'Vendor Name'?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->email ?? 'Email'?></label><input type="email" name="email" class="form-control" value="<?=e($vc['email'] ?? '')?>" placeholder="email@example.com"></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->phone ?? 'Phone'?></label><input type="text" name="phone" class="form-control" value="<?=e($vc['phone'] ?? '')?>" placeholder="+66 xxx xxx xxxx"></div></div>
                </div>
            </div>
        </div>

        <!-- Voucher Settings -->
        <div class="form-card">
            <div class="card-header"><i class="fa fa-cog" style="color:#e74c3c;margin-right:8px"></i> <?=$xml->vouchersettings ?? 'Voucher Settings'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->brand ?? 'Brand'?> / Logo</label>
                        <select name="brandven" class="form-control">
                            <option value="0" <?=($vc['brand'] ?? 0) == 0 ? 'selected' : ''?>>Use Default</option>
                            <?php if(!empty($vendor_brands)): foreach($vendor_brands as $vb): ?>
                            <option value="<?=$vb['id']?>" <?=($vc['brand'] ?? 0) == $vb['id'] ? 'selected' : ''?>><?=e($vb['brand_name'])?></option>
                            <?php endforeach; endif; ?>
                        </select></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->vat ?? 'VAT'?> *</label>
                        <div class="input-addon"><input type="number" name="vat" class="form-control" value="<?=e($vc['vat'] ?? '7')?>" step="0.01" required><span class="addon-text">%</span></div></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->discount ?? 'Discount'?></label>
                        <div class="input-addon"><input type="number" name="dis" class="form-control" value="<?=e($vc['discount'] ?? '0')?>" step="0.01"><span class="addon-text">%</span></div></div></div>
                    <div class="col-md-3"><div class="form-group"><label><?=$xml->paymentmethod ?? 'Payment Method'?></label>
                        <select name="payment_method" class="form-control">
                            <?php if(!empty($payment_methods)):
                                $selectedPM = $vc['payment_method'] ?? 'cash';
                                foreach($payment_methods as $pm):
                                    $pmName = ((isset($_SESSION['lang']) && $_SESSION['lang'] == 1) && !empty($pm['name_th'])) ? $pm['name_th'] : $pm['name'];
                            ?>
                            <option value="<?=e($pm['code'])?>" <?=$selectedPM == $pm['code'] ? 'selected' : ''?>><?=e($pmName)?></option>
                            <?php endforeach; else: ?>
                            <option value="cash" <?=($vc['payment_method'] ?? '') == 'cash' ? 'selected' : ''?>>Cash</option>
                            <option value="bank_transfer" <?=($vc['payment_method'] ?? '') == 'bank_transfer' ? 'selected' : ''?>>Bank Transfer</option>
                            <option value="credit_card" <?=($vc['payment_method'] ?? '') == 'credit_card' ? 'selected' : ''?>>Credit Card</option>
                            <option value="cheque" <?=($vc['payment_method'] ?? '') == 'cheque' ? 'selected' : ''?>>Cheque</option>
                            <?php endif; ?>
                        </select></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->status ?? 'Status'?></label>
                        <select name="status" class="form-control">
                            <option value="draft" <?=($vc['status'] ?? '') == 'draft' ? 'selected' : ''?>><?=$xml->draft ?? 'Draft'?></option>
                            <option value="confirmed" <?=(($vc['status'] ?? 'confirmed') == 'confirmed') ? 'selected' : ''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                            <option value="cancelled" <?=($vc['status'] ?? '') == 'cancelled' ? 'selected' : ''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
                        </select></div></div>
                </div>
                <div class="row" style="margin-top:8px">
                    <div class="col-md-12"><div class="form-group"><label><?=$xml->description ?? 'Description'?> / <?=$xml->notes ?? 'Notes'?></label><textarea name="des" class="form-control" rows="2" placeholder="<?=$xml->notes ?? 'Notes'?>"><?=e($vc['description'] ?? '')?></textarea></div></div>
                </div>
            </div>
        </div>

        <!-- Products / Services -->
        <div class="form-card product-section">
            <div class="card-header">
                <span><i class="fa fa-cubes" style="margin-right:8px"></i> <?=$xml->pleaseselectproduct ?? 'Products / Services'?></span>
                <button type="button" class="btn-add-product" id="addProductRow"><i class="fa fa-plus"></i> <?=$xml->addnew ?? 'Add'?></button>
            </div>
            <div class="card-body" id="productsContainer">
                <?php if(!empty($products)): foreach($products as $i => $p): ?>
                <div class="product-item" data-index="<?=$i?>">
                    <div class="product-header"><span class="product-number">#<?=$i+1?></span><button type="button" class="btn-remove removeRow"><i class="fa fa-times"></i></button></div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->product ?? 'Product'?></label>
                            <select name="type[<?=$i?>]" id="type_<?=$i?>" class="form-control type-select" data-index="<?=$i?>" onchange="updateModelDropdown(<?=$i?>, this.value)">
                                <option value="">-- <?=$xml->select ?? 'Select'?> --</option>
                                <?php if(!empty($types)): foreach($types as $t): ?>
                                <option value="<?=$t['id']?>" <?=($p['type'] ?? '') == $t['id'] ? 'selected' : ''?>><?=e($t['name'])?><?=!empty($t['cat_name']) ? ' ('.$t['cat_name'].')' : ''?></option>
                                <?php endforeach; endif; ?>
                            </select></div></div>
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->model ?? 'Model'?></label>
                            <select name="model[<?=$i?>]" id="model_<?=$i?>" class="form-control model-select" data-index="<?=$i?>">
                                <option value="0">-- <?=$xml->model ?? 'Model'?> --</option>
                                <?php
                                // Pre-populate models for selected type on edit
                                $selType = $p['type'] ?? 0;
                                if($selType && !empty($models_by_type[$selType])):
                                    foreach($models_by_type[$selType] as $m): ?>
                                <option value="<?=$m['id']?>" data-price="<?=$m['price']?>" data-des="<?=e($m['des'] ?? '')?>" data-brand="<?=$m['brand_id'] ?? 0?>" <?=($p['model'] ?? 0) == $m['id'] ? 'selected' : ''?>><?=e($m['model_name'])?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <input type="hidden" name="ban_id[<?=$i?>]" value="<?=e($p['ban_id'] ?? 0)?>">
                            </div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label><input type="number" name="quantity[<?=$i?>]" class="form-control" value="<?=e($p['quantity'] ?? 1)?>" min="1"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Price ?? 'Price'?></label>
                            <div class="input-addon"><input type="text" name="price[<?=$i?>]" class="form-control" value="<?=e($p['price'] ?? 0)?>" id="price_<?=$i?>"><span class="addon-text"><?=$xml->baht ?? '฿'?></span></div></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->warranty ?? 'Date'?></label><input type="text" name="warranty[<?=$i?>]" class="form-control" value="<?=e($p['vo_warranty'] ?? $p['warranty'] ?? date('d-m-Y'))?>" placeholder="dd-mm-YYYY"></div></div>
                    </div>
                    <div class="product-notes"><textarea name="des[<?=$i?>]" class="form-control" placeholder="<?=$xml->notes ?? 'Notes'?>"><?=e($p['des'] ?? '')?></textarea></div>
                </div>
                <?php endforeach; else: ?>
                <!-- Default empty product row -->
                <div class="product-item" data-index="0">
                    <div class="product-header"><span class="product-number">#1</span><button type="button" class="btn-remove removeRow"><i class="fa fa-times"></i></button></div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->product ?? 'Product'?></label>
                            <select name="type[0]" id="type_0" class="form-control type-select" data-index="0" onchange="updateModelDropdown(0, this.value)">
                                <option value="">-- <?=$xml->select ?? 'Select'?> --</option>
                                <?php if(!empty($types)): foreach($types as $t): ?>
                                <option value="<?=$t['id']?>"><?=e($t['name'])?><?=!empty($t['cat_name']) ? ' ('.$t['cat_name'].')' : ''?></option>
                                <?php endforeach; endif; ?>
                            </select></div></div>
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->model ?? 'Model'?></label>
                            <select name="model[0]" id="model_0" class="form-control model-select" data-index="0">
                                <option value="0">-- <?=$xml->model ?? 'Model'?> --</option>
                            </select>
                            <input type="hidden" name="ban_id[0]" value="0">
                            </div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label><input type="number" name="quantity[0]" class="form-control" value="1" min="1"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Price ?? 'Price'?></label>
                            <div class="input-addon"><input type="text" name="price[0]" class="form-control" value="" id="price_0"><span class="addon-text"><?=$xml->baht ?? '฿'?></span></div></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->warranty ?? 'Date'?></label><input type="text" name="warranty[0]" class="form-control" value="<?=date('d-m-Y')?>" placeholder="dd-mm-YYYY"></div></div>
                    </div>
                    <div class="product-notes"><textarea name="des[0]" class="form-control" placeholder="<?=$xml->notes ?? 'Notes'?>"></textarea></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-bar">
            <?php if($isEdit && $vc_id): ?>
            <button type="button" class="btn-preview" onclick="window.open('index.php?page=vou_print&id=<?=e($vc_id)?>','_blank')"><i class="fa fa-eye"></i> <?=$xml->preview ?? 'Preview PDF'?></button>
            <?php endif; ?>
            <button type="submit" class="btn-submit"><i class="fa fa-save"></i> <?=$xml->save ?? 'Save'?> <?=$xml->voucher ?? 'Voucher'?></button>
        </div>
    </form>
</div>

<script>
// All models data grouped by type_id for cascading dropdown
var allModelsData = <?=$allModelsJson?>;
var productIdx = <?=max(count($products ?? []), 1)?>;

// Type options HTML template (for dynamic rows)
var typeOptionsHtml = '<option value="">-- <?=addslashes($xml->select ?? "Select")?> --</option>'
<?php if(!empty($types)): foreach($types as $t): ?>
    + '<option value="<?=$t['id']?>"><?=addslashes(e($t['name']))?><?=!empty($t['cat_name']) ? ' ('.addslashes($t['cat_name']).')' : ''?></option>'
<?php endforeach; endif; ?>
;

/**
 * Update model dropdown based on selected type (cascading dropdown)
 * Matches legacy checkorder() behavior but with client-side data
 */
function updateModelDropdown(index, typeId) {
    var modelSelect = document.getElementById('model_' + index);
    if (!modelSelect) return;

    // Clear and reset
    modelSelect.innerHTML = '<option value="0">-- <?=addslashes($xml->model ?? "Model")?> --</option>';

    var typeIdStr = String(typeId);
    if (typeId && allModelsData[typeIdStr]) {
        allModelsData[typeIdStr].forEach(function(model) {
            var opt = document.createElement('option');
            opt.value = model.id;
            opt.textContent = model.model_name;
            opt.setAttribute('data-price', model.price || 0);
            opt.setAttribute('data-des', model.des || '');
            opt.setAttribute('data-brand', model.brand_id || 0);
            modelSelect.appendChild(opt);
        });
    }
}

/**
 * When model is selected, auto-fill price and brand_id
 */
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('model-select')) {
        var sel = e.target;
        var index = sel.getAttribute('data-index');
        var opt = sel.options[sel.selectedIndex];
        if (opt && opt.value != '0') {
            var price = opt.getAttribute('data-price');
            var brandId = opt.getAttribute('data-brand');
            var des = opt.getAttribute('data-des');
            // Auto-fill price if model has a price
            if (price && parseFloat(price) > 0) {
                var priceInput = document.getElementById('price_' + index);
                if (priceInput) priceInput.value = price;
            }
            // Set hidden brand_id
            var banInput = document.querySelector('input[name="ban_id[' + index + ']"]');
            if (banInput && brandId) banInput.value = brandId;
            // Auto-fill description if empty
            if (des) {
                var desArea = document.querySelector('textarea[name="des[' + index + ']"]');
                if (desArea && !desArea.value) desArea.value = des;
            }
        }
    }
});

/**
 * Add new product row
 */
document.getElementById('addProductRow').addEventListener('click', function() {
    var container = document.getElementById('productsContainer');
    var div = document.createElement('div');
    div.className = 'product-item';
    div.setAttribute('data-index', productIdx);
    div.innerHTML = '<div class="product-header"><span class="product-number">#' + (productIdx+1) + '</span><button type="button" class="btn-remove removeRow"><i class="fa fa-times"></i></button></div>'
        + '<div class="row">'
        + '<div class="col-md-3"><div class="form-group"><label><?=addslashes($xml->product ?? "Product")?></label>'
        + '<select name="type[' + productIdx + ']" id="type_' + productIdx + '" class="form-control type-select" data-index="' + productIdx + '" onchange="updateModelDropdown(' + productIdx + ', this.value)">' + typeOptionsHtml + '</select></div></div>'
        + '<div class="col-md-3"><div class="form-group"><label><?=addslashes($xml->model ?? "Model")?></label>'
        + '<select name="model[' + productIdx + ']" id="model_' + productIdx + '" class="form-control model-select" data-index="' + productIdx + '"><option value="0">-- <?=addslashes($xml->model ?? "Model")?> --</option></select>'
        + '<input type="hidden" name="ban_id[' + productIdx + ']" value="0"></div></div>'
        + '<div class="col-md-2"><div class="form-group"><label><?=addslashes($xml->Unit ?? "Qty")?></label><input type="number" name="quantity[' + productIdx + ']" class="form-control" value="1" min="1"></div></div>'
        + '<div class="col-md-2"><div class="form-group"><label><?=addslashes($xml->Price ?? "Price")?></label>'
        + '<div class="input-addon"><input type="text" name="price[' + productIdx + ']" class="form-control" value="" id="price_' + productIdx + '"><span class="addon-text"><?=addslashes($xml->baht ?? "฿")?></span></div></div></div>'
        + '<div class="col-md-2"><div class="form-group"><label><?=addslashes($xml->warranty ?? "Date")?></label><input type="text" name="warranty[' + productIdx + ']" class="form-control" value="<?=date("d-m-Y")?>" placeholder="dd-mm-YYYY"></div></div>'
        + '</div>'
        + '<div class="product-notes"><textarea name="des[' + productIdx + ']" class="form-control" placeholder="<?=addslashes($xml->notes ?? "Notes")?>"></textarea></div>';
    container.appendChild(div);
    document.getElementById('countloop').value = productIdx + 1;
    productIdx++;
});

/**
 * Remove product row
 */
document.addEventListener('click', function(e) {
    if (e.target.closest('.removeRow') && document.querySelectorAll('.product-item').length > 1) {
        e.target.closest('.product-item').remove();
        document.querySelectorAll('.product-item').forEach(function(item, i) {
            item.querySelector('.product-number').textContent = '#' + (i+1);
        });
    }
});
</script>
