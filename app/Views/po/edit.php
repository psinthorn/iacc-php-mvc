<?php
/**
 * PO Edit View — Legacy Modern Design
 * Variables: $po, $id, $products, $types, $models, $brands, $companies
 */
$allModelsJson = json_encode($models);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/smart-dropdown.css">
<style>
    .po-form-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(16,185,129,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; }
    .form-card .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }
    .product-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative; }
    .product-item .product-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .product-item .product-number { font-weight: 700; color: #10b981; font-size: 14px; }
    .product-item .btn-remove { background: rgba(239,68,68,0.1); color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .product-item .btn-remove:hover { background: #ef4444; color: white; }
    .input-addon { position: relative; }
    .input-addon .addon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px; }
    .btn-add-product { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-add-product:hover { box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
    .btn-submit { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(102,126,234,0.35); color: white; }
    .error-card { background: white; border-radius: 12px; border: 1px solid #fecaca; padding: 40px; text-align: center; }
    .error-card i { font-size: 48px; color: #f87171; margin-bottom: 16px; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<div class="po-form-wrapper">
<?php if(!$po): ?>
    <div class="error-card">
        <i class="fa fa-exclamation-triangle"></i>
        <h3>PO not found or not editable</h3>
        <p><a href="index.php?page=qa_list" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a></p>
    </div>
<?php else: ?>
    <div class="page-header">
        <h2><i class="fa fa-pencil-square-o"></i> <?=$xml->edit ?? 'Edit'?> <?=$xml->purchasingorder ?? 'Purchase Order'?></h2>
        <div class="header-actions">
            <a href="index.php?page=qa_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <form method="post" action="index.php?page=po_store" id="poEditForm">
        <input type="hidden" name="method" value="E">
        <input type="hidden" name="id" value="<?=$id?>">
        <input type="hidden" name="ref" value="<?=e($po['ref'])?>">
        <input type="hidden" name="countloop" id="countloop" value="<?=count($products)?>">
        <?= csrf_field() ?>

        <!-- Basic Info -->
        <div class="form-card">
            <div class="card-header"><i class="fa fa-info-circle" style="color:#10b981;margin-right:8px"></i> <?=$xml->information ?? 'Basic Info'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><input type="text" name="name" class="form-control" value="<?=e($po['name'])?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->customer ?? 'Customer'?></label>
                        <select name="cus_id" class="form-control" onchange="fetadr(this.value,this.id)">
                            <?php foreach($companies as $c): ?>
                            <option value="<?=$c['id']?>" <?=$c['id']==$po['cus_id']?'selected':''?>><?=e($c['name_en'])?></option>
                            <?php endforeach; ?>
                        </select></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->brand ?? 'Brand'?></label>
                        <select name="brandven" class="form-control">
                            <option value="0">--</option>
                            <?php foreach($brands as $b): ?>
                            <option value="<?=$b['id']?>" <?=$b['id']==$po['bandven']?'selected':''?>><?=e($b['brand_name'])?></option>
                            <?php endforeach; ?>
                        </select></div></div>
                </div>
            </div>
        </div>

        <!-- Pricing & Dates -->
        <div class="form-card">
            <div class="card-header"><i class="fa fa-calendar" style="color:#10b981;margin-right:8px"></i> <?=$xml->date ?? 'Pricing & Dates'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2"><div class="form-group"><label>VAT %</label><div class="input-addon"><input type="number" name="vat" class="form-control" value="<?=e($po['vat'])?>" step="0.01"><span class="addon">%</span></div></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->discount ?? 'Discount'?></label><div class="input-addon"><input type="number" name="dis" class="form-control" value="<?=e($po['dis'])?>" step="0.01"><span class="addon">%</span></div></div></div>
                    <div class="col-md-2"><div class="form-group"><label>W/H Tax</label><div class="input-addon"><input type="number" name="over" class="form-control" value="<?=e($po['over'])?>" step="0.01"><span class="addon">%</span></div></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->duedate ?? 'Due Date'?></label><input type="date" name="valid_pay" class="form-control" value="<?=e($po['valid_pay'])?>"></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->deliverydate ?? 'Delivery'?></label><input type="date" name="deliver_date" class="form-control" value="<?=e($po['deliver_date'])?>"></div></div>
                    <div class="col-md-2"><div class="form-group"><label><?=$xml->date ?? 'Created'?></label><input type="date" name="create_date" class="form-control" value="<?=e($po['date'])?>" readonly style="background:#f9fafb"></div></div>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="form-card">
            <div class="card-header">
                <span><i class="fa fa-cubes" style="color:#10b981;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></span>
                <button type="button" class="btn-add-product" id="addRow"><i class="fa fa-plus"></i> <?=$xml->addnew ?? 'Add'?></button>
            </div>
            <div class="card-body" id="productsContainer">
                <?php foreach($products as $i => $p): ?>
                <div class="product-item" data-index="<?=$i?>">
                    <div class="product-header">
                        <span class="product-number">#<?=$i+1?> <?=e($p['type_name'] ?? 'Product')?></span>
                        <button type="button" class="btn-remove" onclick="removeProductItem(<?=$i?>)"><i class="fa fa-times"></i></button>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->Product ?? 'Product'?></label>
                            <select name="type[<?=$i?>]" class="form-control" onchange="checkorder(this.value, '<?=$i?>')">
                                <option value="">--</option>
                                <?php foreach($types as $t): ?>
                                <option value="<?=$t['id']?>" <?=$t['id']==$p['type']?'selected':''?>><?=e($t['name'])?></option>
                                <?php endforeach; ?>
                            </select></div></div>
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->model ?? 'Model'?></label>
                            <select name="model[<?=$i?>]" class="form-control" id="model_<?=$i?>" onchange="checkorder3(this.value, '<?=$i?>')">
                                <option value="0">--</option>
                                <?php foreach($models as $m): if($m['type_id']==$p['type']): ?>
                                <option value="<?=$m['id']?>" <?=$m['id']==$p['model']?'selected':''?> data-price="<?=$m['price']??0?>" data-des="<?=$m['des']??''?>"><?=e($m['model_name'])?></option>
                                <?php endif; endforeach; ?>
                            </select></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label><input type="number" name="quantity[<?=$i?>]" class="form-control" value="<?=e($p['quantity'])?>" min="1"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Price ?? 'Price'?></label><input type="number" name="price[<?=$i?>]" class="form-control" id="price_<?=$i?>" value="<?=e($p['price'])?>" step="0.01"></div></div>
                        <div class="col-md-2"><div class="form-group"><label>Labour</label>
                            <div class="row">
                                <div class="col-xs-6"><select name="a_labour[<?=$i?>]" class="form-control"><option value="0" <?=$p['activelabour']==0?'selected':''?>>No</option><option value="1" <?=$p['activelabour']==1?'selected':''?>>Yes</option></select></div>
                                <div class="col-xs-6"><input type="number" name="v_labour[<?=$i?>]" class="form-control" value="<?=e($p['valuelabour']??0)?>" step="0.01"></div>
                            </div>
                        </div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-12"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><textarea name="des[<?=$i?>]" class="form-control" rows="2" id="des_<?=$i?>"><?=e($p['des']??'')?></textarea></div></div>
                    </div>
                    <input type="hidden" name="ban_id[<?=$i?>]" value="<?=e($p['ban_id']??0)?>">
                    <input type="hidden" name="pack_quantity[<?=$i?>]" value="<?=e($p['pack_quantity']??1)?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-check"></i> <?=$xml->save ?? 'Save Changes'?></button>
        </div>
    </form>
<?php endif; ?>
</div>

<script>
var allModelsData = <?=$allModelsJson?>;
var productCount = <?=count($products)?>;
var typesOptions = `<option value="">--</option><?php foreach($types as $t): ?><option value="<?=$t['id']?>"><?=e($t['name'])?></option><?php endforeach; ?>`;

function updateModelDropdown(index, typeId) {
    var sel = document.getElementById('model_' + index);
    if (!sel) return;
    sel.innerHTML = '<option value="0">--</option>';
    allModelsData.forEach(function(m) {
        if (String(m.type_id) === String(typeId)) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.model_name;
            opt.dataset.price = m.price || 0;
            opt.dataset.des = m.des || '';
            sel.appendChild(opt);
        }
    });
}

function checkorder(value, id) {
    updateModelDropdown(id, value);
    var item = document.querySelector('.product-item[data-index="' + id + '"] .product-number');
    if (item) {
        var sel = document.querySelector('select[name="type[' + id + ']"]');
        var text = sel ? sel.options[sel.selectedIndex].text : 'Product';
        item.textContent = '#' + (parseInt(id)+1) + ' ' + text;
    }
}

function checkorder3(value, id) {
    var sel = document.getElementById('model_' + id);
    if (!sel) return;
    var opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.price) document.getElementById('price_' + id).value = opt.dataset.price;
    if (opt && opt.dataset.des) document.getElementById('des_' + id).value = opt.dataset.des;
}

function removeProductItem(index) {
    var items = document.querySelectorAll('.product-item');
    if (items.length <= 1) return;
    var item = document.querySelector('.product-item[data-index="' + index + '"]');
    if (item) item.remove();
    renumberProducts();
}

function renumberProducts() {
    var items = document.querySelectorAll('.product-item');
    items.forEach(function(item, i) {
        item.querySelector('.product-number').textContent = '#' + (i+1) + ' Product';
    });
    document.getElementById('countloop').value = items.length;
}

document.getElementById('addRow')?.addEventListener('click', function() {
    var idx = productCount++;
    var html = `<div class="product-item" data-index="${idx}">
        <div class="product-header">
            <span class="product-number">#${idx+1} New Product</span>
            <button type="button" class="btn-remove" onclick="removeProductItem(${idx})"><i class="fa fa-times"></i></button>
        </div>
        <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Product</label><select name="type[${idx}]" class="form-control" onchange="checkorder(this.value, '${idx}')">${typesOptions}</select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Model</label><select name="model[${idx}]" class="form-control" id="model_${idx}" onchange="checkorder3(this.value, '${idx}')"><option value="0">--</option></select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Qty</label><input type="number" name="quantity[${idx}]" class="form-control" value="1" min="1"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Price</label><input type="number" name="price[${idx}]" class="form-control" id="price_${idx}" value="0" step="0.01"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Labour</label><div class="row"><div class="col-xs-6"><select name="a_labour[${idx}]" class="form-control"><option value="0">No</option><option value="1">Yes</option></select></div><div class="col-xs-6"><input type="number" name="v_labour[${idx}]" class="form-control" value="0" step="0.01"></div></div></div></div>
        </div>
        <div class="row"><div class="col-md-12"><div class="form-group"><label>Description</label><textarea name="des[${idx}]" class="form-control" rows="2" id="des_${idx}"></textarea></div></div></div>
        <input type="hidden" name="ban_id[${idx}]" value="0">
        <input type="hidden" name="pack_quantity[${idx}]" value="1">
    </div>`;
    document.getElementById('productsContainer').insertAdjacentHTML('beforeend', html);
    document.getElementById('countloop').value = document.querySelectorAll('.product-item').length;
});
</script>
