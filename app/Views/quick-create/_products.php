<?php
/**
 * Quick Create — Shared product form partial
 * Included by quotation.php, invoice.php, tax-invoice.php
 * Variables: $types, $models, $models_by_type, $brands, $isThaiLang, $xml
 */
$allModelsJson = json_encode($models_by_type ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<!-- Product Items -->
<div class="form-card">
    <div class="section-title"><i class="fa fa-cubes"></i> <?= $xml->product ?? 'Products' ?></div>
    <div class="products-header">
        <h3><?= $isThaiLang ? 'รายการสินค้า / บริการ' : 'Product / Service Items' ?></h3>
        <p><?= $isThaiLang ? 'เพิ่มสินค้าหรือบริการในเอกสารนี้' : 'Add products or services to this document' ?></p>
    </div>

    <div id="productContainer">
        <!-- Default first product row -->
        <div class="product-item" id="fr[0]">
            <div class="product-item-header">
                <div class="product-item-number">1</div>
                <span class="product-item-name"><?= $isThaiLang ? 'รายการที่ 1' : 'Item 1' ?></span>
                <button type="button" class="btn-remove-row" onclick="del_tr(this)"><i class="fa fa-times"></i></button>
            </div>
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="product-row-main">
                    <div class="form-group">
                        <label><?= $xml->product ?? 'Product' ?></label>
                        <select id="type0" name="type[0]" class="form-control product-select smart-dropdown" data-index="0" required>
                            <option value=""><?= $isThaiLang ? '-- เลือก --' : '-- Select --' ?></option>
                            <?php foreach ($types as $prod): ?>
                                <option value="<?= $prod['id'] ?>" data-cat="<?= htmlspecialchars($prod['cat_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>" data-des="<?= htmlspecialchars($prod['des'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="ban_id[0]" value="0">
                        <input type="hidden" name="pack_quantity[0]" value="1">
                    </div>
                    <div class="form-group">
                        <label><?= $xml->model ?? 'Model' ?></label>
                        <select id="model0" name="model[0]" class="form-control model-select smart-dropdown" data-index="0">
                            <option value="0"><?= $isThaiLang ? '-- ไม่มีรุ่น --' : '-- No Model --' ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $xml->category ?? 'Category' ?></label>
                        <input type="text" class="form-control" readonly value="N/A" id="cat_display0">
                    </div>
                    <div class="form-group">
                        <label><?= $xml->unit ?? 'Quantity' ?></label>
                        <div class="input-with-addon">
                            <input type="number" class="form-control" name="quantity[0]" id="quantity0" required value="1" min="1">
                            <span class="addon"><?= $xml->unit ?? 'Unit' ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $xml->price ?? 'Price' ?></label>
                        <div class="input-with-addon">
                            <input type="text" class="form-control" name="price[0]" id="price0" required placeholder="0">
                            <span class="addon"><?= $xml->baht ?? 'Baht' ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $xml->labour ?? 'Labour' ?></label>
                        <div class="labour-field">
                            <input type="checkbox" name="a_labour[0]" id="a_labour0" value="1">
                            <input type="text" class="form-control" name="v_labour[0]" id="v_labour0" placeholder="<?= $isThaiLang ? 'ราคา...' : 'Cost...' ?>">
                        </div>
                    </div>
                </div>
                <div class="product-row-notes">
                    <div class="form-group notes-row">
                        <label><?= $xml->description ?? 'Description' ?></label>
                        <input type="text" class="form-control" name="des_product[0]" id="des0" placeholder="<?= $isThaiLang ? 'รายละเอียด...' : 'Notes...' ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="countloop" value="1">

    <div class="form-actions">
        <button type="button" id="addRow" class="btn-add-row"><i class="fa fa-plus"></i> <?= $isThaiLang ? 'เพิ่มรายการ' : 'Add Item' ?></button>
    </div>
</div>

<!-- Product row JavaScript -->
<script type="text/javascript">
var allModelsData = <?= $allModelsJson ?>;

function del_tr(btn) {
    var row = btn.closest('.product-item');
    if (row) { row.remove(); updateProductNumbers(); }
}

function updateProductNumbers() {
    document.querySelectorAll('.product-item').forEach((item, index) => {
        item.querySelector('.product-item-number').textContent = index + 1;
    });
}

function updateCategoryDisplay(selectElement) {
    var index = $(selectElement).data('index');
    var selectedOption = $(selectElement).find(':selected');
    var catName = selectedOption.data('cat') || 'N/A';
    var productName = selectedOption.text();
    var productDes = selectedOption.data('des') || '';
    var typeId = selectedOption.val();
    
    $('#cat_display' + index).val(catName);
    $(selectElement).closest('.product-item').find('.product-item-name').text(productName);
    $('#des' + index).val(productDes);
    updateModelDropdown(index, typeId);
}

function updateModelDropdown(index, typeId) {
    var modelSelect = $('#model' + index);
    var selectElement = modelSelect[0];
    modelSelect.empty();
    modelSelect.append('<option value="0"><?= $isThaiLang ? "-- ไม่มีรุ่น --" : "-- No Model --" ?></option>');
    
    var typeIdStr = String(typeId);
    if (typeId && allModelsData[typeIdStr]) {
        allModelsData[typeIdStr].forEach(function(model) {
            var escapedName = $('<div>').text(model.model_name).html();
            var escapedDes = model.des ? $('<div>').text(model.des).html() : '';
            var displayText = escapedName + (escapedDes ? ' - ' + escapedDes : '');
            modelSelect.append('<option value="' + model.id + '" data-des="' + escapedDes + '" data-price="' + model.price + '">' + displayText + '</option>');
        });
    }
    if (selectElement && selectElement._smartDropdown) { selectElement._smartDropdown.refresh(); }
}

function updateModelDescription(selectElement) {
    var index = $(selectElement).data('index');
    var selectedOption = $(selectElement).find(':selected');
    var modelDes = selectedOption.data('des') || '';
    var modelPrice = selectedOption.data('price');
    if (modelDes) { $('#des' + index).val(modelDes); }
    if (modelPrice && modelPrice > 0) { $('input[name="price[' + index + ']"]').val(modelPrice); }
}

function initSmartDropdowns(container) {
    if (typeof SmartDropdown !== 'undefined' && container) {
        var selects = container.querySelectorAll('.smart-dropdown');
        selects.forEach(function(select) {
            if (select.style.display !== 'none') { new SmartDropdown(select); }
        });
    }
}

$(document).ready(function() {
    $(document).on('change', '.product-select', function() { updateCategoryDisplay(this); });
    $(document).on('change', '.model-select', function() { updateModelDescription(this); });
});

$(function(){
    $("#addRow").click(function(){
        var indexthis = parseInt(document.getElementById("countloop").value);
        document.getElementById("countloop").value = indexthis + 1;
        
        var NR = `
        <div class="product-item" id="fr[${indexthis}]">
            <div class="product-item-header">
                <div class="product-item-number">${indexthis + 1}</div>
                <span class="product-item-name"><?= $isThaiLang ? 'รายการใหม่' : 'New Item' ?></span>
                <button type="button" class="btn-remove-row" onclick="del_tr(this)"><i class="fa fa-times"></i></button>
            </div>
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="product-row-main">
                    <div class="form-group">
                        <label><?= $xml->product ?? 'Product' ?></label>
                        <select id="type${indexthis}" name="type[${indexthis}]" class="form-control product-select smart-dropdown" data-index="${indexthis}" required>
                            <?php 
                            echo "<option value=''>" . ($isThaiLang ? '-- เลือก --' : '-- Select --') . "</option>";
                            foreach($types as $prod){
                                $escapedCat = htmlspecialchars($prod['cat_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                                $escapedDes = htmlspecialchars($prod['des'] ?? '', ENT_QUOTES, 'UTF-8');
                                $escapedName = htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8');
                                echo "<option value='".$prod['id']."' data-cat='".$escapedCat."' data-des='".$escapedDes."'>".$escapedName."</option>";
                            }?>
                        </select>
                        <input type="hidden" name="ban_id[${indexthis}]" value="0">
                        <input type="hidden" name="pack_quantity[${indexthis}]" value="1">
                    </div>
                    <div class="form-group">
                        <label><?= $xml->model ?? 'Model' ?></label>
                        <select id="model${indexthis}" name="model[${indexthis}]" class="form-control model-select smart-dropdown" data-index="${indexthis}">
                            <option value="0"><?= $isThaiLang ? '-- ไม่มีรุ่น --' : '-- No Model --' ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $xml->category ?? 'Category' ?></label>
                        <input type="text" class="form-control" readonly value="N/A" id="cat_display${indexthis}">
                    </div>
                    <div class="form-group">
                        <label><?= $xml->unit ?? 'Quantity' ?></label>
                        <div class="input-with-addon">
                            <input type="number" class="form-control" name="quantity[${indexthis}]" id="quantity${indexthis}" required value="1" min="1">
                            <span class="addon"><?= $xml->unit ?? 'Unit' ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $xml->price ?? 'Price' ?></label>
                        <div class="input-with-addon">
                            <input type="text" class="form-control" name="price[${indexthis}]" id="price${indexthis}" required placeholder="0">
                            <span class="addon"><?= $xml->baht ?? 'Baht' ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $xml->labour ?? 'Labour' ?></label>
                        <div class="labour-field">
                            <input type="checkbox" name="a_labour[${indexthis}]" id="a_labour${indexthis}" value="1">
                            <input type="text" class="form-control" name="v_labour[${indexthis}]" id="v_labour${indexthis}" placeholder="<?= $isThaiLang ? 'ราคา...' : 'Cost...' ?>">
                        </div>
                    </div>
                </div>
                <div class="product-row-notes">
                    <div class="form-group notes-row">
                        <label><?= $xml->description ?? 'Description' ?></label>
                        <input type="text" class="form-control" name="des_product[${indexthis}]" id="des${indexthis}" placeholder="<?= $isThaiLang ? 'รายละเอียด...' : 'Notes...' ?>">
                    </div>
                </div>
            </div>
        </div>`;
        
        var container = document.getElementById("productContainer");
        container.insertAdjacentHTML('beforeend', NR);
        updateProductNumbers();
        initSmartDropdowns(container.lastElementChild);
    });
});
</script>
