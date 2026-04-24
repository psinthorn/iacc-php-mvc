<?php
$pageTitle = 'Purchase Requests — New';

/**
 * PR Create View — Full product selection with modal
 * Variables from controller: $customers, $categories (with nested types+prices), $com_id
 * Globals: $xml (language strings)
 */
global $xml;
?>
<!-- Smart Dropdown Component -->
<link href="css/smart-dropdown.css" rel="stylesheet">
<style>
    .pr-form-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header-pr {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
    }
    
    .page-header-pr h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header-pr .subtitle {
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .form-card .section-title {
        font-weight: 600;
        color: #1f2937;
        font-size: 16px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-card .section-title i { color: #667eea; }
    
    .form-row-pr {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .form-group-pr { flex: 1; }
    
    .form-group-pr label {
        display: block;
        font-weight: 500;
        color: #374151;
        font-size: 13px;
        margin-bottom: 6px;
    }
    
    .form-card .form-control-pr {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 14px 16px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
        width: 100%;
        min-height: 48px;
        box-sizing: border-box;
    }
    
    .form-card .form-control-pr:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .product-grid-pr { margin-top: 8px; }
    
    .product-header-pr {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        padding: 12px 16px;
        background: #f9fafb;
        border-radius: 8px 8px 0 0;
        border: 1px solid #e5e7eb;
        border-bottom: none;
        font-weight: 600;
        font-size: 12px;
        color: #1f2937;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-row-pr {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-top: none;
        align-items: center;
    }
    
    .product-row-pr:nth-child(even) { background: #fafafa; }
    .product-row-pr:last-child { border-radius: 0 0 8px 8px; }
    
    .product-row-pr input {
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        padding: 12px 14px;
        font-size: 14px;
        width: 100%;
        min-height: 44px;
        box-sizing: border-box;
    }
    
    .product-row-pr input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .product-row-pr input[readonly] {
        background: #f9fafb;
        color: #6b7280;
        cursor: pointer;
    }
    
    .btn-clear-row {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-clear-row:hover { background: #ef4444; color: white; }
    
    .summary-section {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
    }
    
    .summary-section label { font-weight: 600; color: #1f2937; font-size: 14px; }
    
    .summary-section input {
        width: 180px;
        text-align: right;
        font-weight: 700;
        font-size: 16px;
        color: #10b981;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 10px 14px;
    }
    
    .form-actions-pr {
        margin-top: 20px;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .btn-submit-pr {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 28px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-submit-pr:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    }
    
    /* Product Modal */
    .product-modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }
    
    .product-modal-overlay.active { display: flex; }
    
    .product-modal {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 800px;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        animation: modalSlideIn 0.3s ease;
    }
    
    @keyframes modalSlideIn {
        from { opacity: 0; transform: translateY(-20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .product-modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .product-modal-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
    
    .product-modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 36px; height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s;
    }
    
    .product-modal-close:hover { background: rgba(255, 255, 255, 0.3); }
    
    .product-modal-search {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f9fafb;
    }
    
    .product-modal-search input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
    }
    
    .product-modal-search input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .product-sort-buttons { display: flex; gap: 8px; }
    
    .product-sort-btn {
        flex: 1;
        padding: 8px 12px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex; align-items: center; justify-content: center; gap: 4px;
    }
    
    .product-sort-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
    .product-sort-btn.active { background: #667eea; border-color: #667eea; color: white; }
    
    .product-modal-tabs {
        display: flex;
        gap: 4px;
        padding: 0 24px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        overflow-x: auto;
    }
    
    .product-modal-tab {
        padding: 12px 20px;
        border: none;
        background: transparent;
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        transition: all 0.2s;
    }
    
    .product-modal-tab:hover { color: #667eea; }
    .product-modal-tab.active { color: #667eea; border-bottom-color: #667eea; background: white; }
    
    .product-modal-content { padding: 24px; max-height: 400px; overflow-y: auto; }
    
    .product-grid-modal {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    
    .product-item {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
    }
    
    .product-item:hover {
        border-color: #667eea;
        background: #f5f3ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    .product-item.highlighted {
        border-color: #667eea;
        background: #ede9fe;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
    }
    
    .smart-search-input { font-size: 15px !important; padding: 14px 18px !important; font-weight: 500; }
    .smart-search-input::placeholder { color: #9ca3af; }
    
    .product-item-name { font-weight: 600; color: #1f2937; font-size: 14px; margin-bottom: 8px; }
    .product-item-price { color: #10b981; font-weight: 700; font-size: 16px; }
    
    .no-products { text-align: center; padding: 40px; color: #6b7280; }
    .no-products i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }

    .btn-add-row {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        padding: 10px 20px;
        background: #f0f4ff;
        color: #667eea;
        border: 1px dashed #667eea;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-add-row:hover {
        background: #667eea;
        color: white;
        border-style: solid;
    }
</style>

<script type="text/javascript">
var rowCount = 3; // initial rows shown
var maxRows = 50;

function calprice(id) { 
    if(document.getElementById('quantity'+id).value==0){
        alert('Value Quantity is Not Zero'); 
        document.getElementById('quantity'+id).value=1;
    }
    document.getElementById('total'+id).value=document.getElementById('quantity'+id).value*document.getElementById('price'+id).value;
    sumall();
}
  
function sumall() { 
    var totalsum=0;
    for(var i=0; i<rowCount; i++){
        var el = document.getElementById('total'+i);
        if(el) totalsum += parseFloat(el.value) || 0;
    }
    document.getElementById('totalnet').value=totalsum.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2});
    document.getElementById('row_count').value = rowCount;
}

function addProductRow() {
    if(rowCount >= maxRows) { alert('Maximum ' + maxRows + ' rows reached'); return; }
    var container = document.getElementById('productRowsContainer');
    var i = rowCount;
    var div = document.createElement('div');
    div.className = 'product-row-pr';
    div.id = 'product-row-' + i;
    div.innerHTML = '<input type="text" name="ordername' + i + '" id="ordername' + i + '" readonly placeholder="Click to select product..." onclick="openProductModal(' + i + ');" style="cursor:pointer;"/>'
        + '<input type="hidden" name="id' + i + '" id="id' + i + '" value="0" />'
        + '<input type="text" name="quantity' + i + '" id="quantity' + i + '" onchange="calprice(\'' + i + '\');" required value="1" />'
        + '<input type="text" name="price' + i + '" readonly id="price' + i + '" value="0" />'
        + '<input type="text" name="total' + i + '" readonly id="total' + i + '" value="0" />'
        + '<button type="button" class="btn-clear-row" onclick="removeProductRow(' + i + ')">✕</button>';
    container.appendChild(div);
    rowCount++;
    document.getElementById('row_count').value = rowCount;
}

function removeProductRow(i) {
    var row = document.getElementById('product-row-' + i);
    if(row) {
        row.remove();
        sumall();
    }
}

var currentRowIndex = null;

function openProductModal(rowIndex) {
    currentRowIndex = rowIndex;
    document.getElementById('productModal').classList.add('active');
    var searchInput = document.getElementById('productSearch');
    searchInput.value = '';
    filterProducts('');
    var tabs = document.querySelectorAll('.product-modal-tab');
    if (tabs.length > 0) tabs[0].click();
    setTimeout(function() { searchInput.focus(); }, 100);
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('active');
    currentRowIndex = null;
}

function selectProduct(name, id, price) {
    if (currentRowIndex === null) return;
    document.getElementById('ordername' + currentRowIndex).value = name;
    document.getElementById('id' + currentRowIndex).value = id;
    document.getElementById('price' + currentRowIndex).value = price;
    var qty = document.getElementById('quantity' + currentRowIndex).value || 1;
    document.getElementById('total' + currentRowIndex).value = qty * price;
    sumall();
    closeProductModal();
}

function showCategory(catId, btn) {
    document.querySelectorAll('.product-modal-tab').forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.product-item').forEach(function(item) {
        item.style.display = (catId === 'all' || item.dataset.category === catId) ? 'block' : 'none';
    });
}

var highlightedIndex = -1;
var visibleItems = [];

function filterProducts(search) {
    search = search.toLowerCase();
    visibleItems = [];
    highlightedIndex = -1;
    document.querySelectorAll('.product-item').forEach(function(item) {
        var name = item.querySelector('.product-item-name').textContent.toLowerCase();
        item.classList.remove('highlighted');
        if (name.indexOf(search) !== -1) {
            item.style.display = 'block';
            visibleItems.push(item);
        } else {
            item.style.display = 'none';
        }
    });
    if (visibleItems.length > 0 && search.length > 0) {
        highlightedIndex = 0;
        visibleItems[0].classList.add('highlighted');
        visibleItems[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

function handleSearchKeydown(event) {
    if (visibleItems.length === 0) return;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (highlightedIndex < visibleItems.length - 1) {
            if (highlightedIndex >= 0) visibleItems[highlightedIndex].classList.remove('highlighted');
            highlightedIndex++;
            visibleItems[highlightedIndex].classList.add('highlighted');
            visibleItems[highlightedIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (highlightedIndex > 0) {
            visibleItems[highlightedIndex].classList.remove('highlighted');
            highlightedIndex--;
            visibleItems[highlightedIndex].classList.add('highlighted');
            visibleItems[highlightedIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    } else if (event.key === 'Enter') {
        event.preventDefault();
        if (highlightedIndex >= 0 && visibleItems[highlightedIndex]) visibleItems[highlightedIndex].click();
    } else if (event.key === 'Escape') {
        closeProductModal();
    }
}

function sortProducts(order, btn) {
    document.querySelectorAll('.product-sort-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    var grid = document.querySelector('.product-grid-modal');
    var items = Array.from(grid.querySelectorAll('.product-item'));
    items.sort(function(a, b) {
        var nameA = a.querySelector('.product-item-name').textContent.toLowerCase();
        var nameB = b.querySelector('.product-item-name').textContent.toLowerCase();
        var priceA = parseFloat(a.querySelector('.product-item-price').textContent.replace(/[^\d.-]/g, '')) || 0;
        var priceB = parseFloat(b.querySelector('.product-item-price').textContent.replace(/[^\d.-]/g, '')) || 0;
        switch(order) {
            case 'az': return nameA.localeCompare(nameB);
            case 'za': return nameB.localeCompare(nameA);
            case 'price-asc': return priceA - priceB;
            case 'price-desc': return priceB - priceA;
            default: return 0;
        }
    });
    items.forEach(function(item) { grid.appendChild(item); });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('productModal').addEventListener('click', function(e) {
        if (e.target === this) closeProductModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeProductModal();
    });
});

function clearRow(i) {
    document.getElementById('ordername' + i).value = '';
    document.getElementById('price' + i).value = '0';
    document.getElementById('id' + i).value = '0';
    document.getElementById('quantity' + i).value = '1';
    document.getElementById('total' + i).value = '0';
    sumall();
}
</script>

<div class="pr-form-wrapper">
    <!-- Page Header -->
    <div class="page-header-pr">
        <h2><i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest ?? 'Purchase Request'?></h2>
        <div class="subtitle">Create a new purchase request for a customer</div>
    </div>

    <form action="index.php?page=pr_store" method="post" id="company-form">
        <?= csrf_field() ?>
        <!-- Basic Info Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-info-circle"></i> Request Information</div>
            
            <div class="form-row-pr">
                <div class="form-group-pr">
                    <label for="name"><?=$xml->name ?? 'Name'?></label>
                    <input id="name" name="name" placeholder="<?=$xml->name ?? 'Name'?>" class="form-control-pr" required type="text">
                </div>
                <div class="form-group-pr">
                    <label for="cus_id"><?=$xml->customer ?? 'Customer'?></label>
                    <select id="cus_id" name="cus_id" class="form-control-pr smart-dropdown" data-placeholder="Select Customer..." data-sort-order="asc">
                        <?php foreach($customers as $c): ?>
                            <option value="<?=$c['id']?>"><?=e($c['name_en'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group-pr">
                <label for="des"><?=$xml->Description ?? 'Description'?></label>
                <textarea id="des" name="des" class="form-control-pr" required placeholder="<?=$xml->Description ?? 'Description'?>" rows="3"></textarea>
            </div>
        </div>

        <!-- Products Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-shopping-cart"></i> <?=$xml->Product ?? 'Product'?> Selection</div>
            
            <div class="product-grid-pr">
                <div class="product-header-pr">
                    <div><?=$xml->Product ?? 'Product'?></div>
                    <div><?=$xml->Unit ?? 'Unit'?></div>
                    <div><?=$xml->Price ?? 'Price'?></div>
                    <div><?=$xml->Total ?? 'Total'?></div>
                    <div></div>
                </div>
                
                <div id="productRowsContainer">
                <?php for($i=0; $i<3; $i++): ?>
                <div class="product-row-pr" id="product-row-<?=$i?>">
                    <input type="text" name="ordername<?=$i?>" id="ordername<?=$i?>" readonly placeholder="Click to select product..." onclick="openProductModal(<?=$i?>);" style="cursor: pointer;"/>
                    <input type="hidden" name="id<?=$i?>" id="id<?=$i?>" value="0" />
                    <input type="text" name="quantity<?=$i?>" id="quantity<?=$i?>" onchange="calprice('<?=$i?>');" required value="1" />
                    <input type="text" name="price<?=$i?>" readonly id="price<?=$i?>" value="0" />
                    <input type="text" name="total<?=$i?>" readonly id="total<?=$i?>" value="0" />
                    <button type="button" class="btn-clear-row" onclick="clearRow(<?=$i?>)">✕</button>
                </div>
                <?php endfor; ?>
                </div>
                <input type="hidden" name="row_count" id="row_count" value="3" />
                <button type="button" class="btn-add-row" onclick="addProductRow()">
                    <i class="fa fa-plus"></i> Add Product Row
                </button>
            </div>
            
            <div class="summary-section">
                <label><?=$xml->summary ?? 'Summary'?></label>
                <input type="text" name="totalnet" id="totalnet" readonly value="0"/>
            </div>
            
            <div class="form-actions-pr">
                <input type="hidden" name="method" value="A">
                <input type="hidden" name="page" value="pr_list">
                <input type="hidden" name="ven_id" value="<?=$com_id?>">
                <button type="submit" class="btn-submit-pr"><i class="fa fa-paper-plane"></i> <?=$xml->request ?? 'Submit Request'?></button>
            </div>
        </div>
    </form>
</div>

<!-- Product Selection Modal -->
<div class="product-modal-overlay" id="productModal">
    <div class="product-modal">
        <div class="product-modal-header">
            <h3><i class="fa fa-cube"></i> <?=$xml->Model ?? 'Select Model'?></h3>
            <button type="button" class="product-modal-close" onclick="closeProductModal()">×</button>
        </div>
        
        <div class="product-modal-search">
            <input type="text" id="productSearch" class="smart-search-input" placeholder="🔍 Type to search models..." oninput="filterProducts(this.value)" onkeydown="handleSearchKeydown(event)" autocomplete="off">
            <div class="product-sort-buttons">
                <button type="button" class="product-sort-btn" onclick="sortProducts('az', this)"><i class="fa fa-sort-alpha-asc"></i> A-Z</button>
                <button type="button" class="product-sort-btn" onclick="sortProducts('za', this)"><i class="fa fa-sort-alpha-desc"></i> Z-A</button>
                <button type="button" class="product-sort-btn" onclick="sortProducts('price-asc', this)"><i class="fa fa-sort-amount-asc"></i> Price ↑</button>
                <button type="button" class="product-sort-btn" onclick="sortProducts('price-desc', this)"><i class="fa fa-sort-amount-desc"></i> Price ↓</button>
            </div>
        </div>
        
        <?php if (!empty($categories)): ?>
        <div class="product-modal-tabs">
            <button type="button" class="product-modal-tab active" onclick="showCategory('all', this)">All</button>
            <?php foreach($categories as $cat): ?>
            <button type="button" class="product-modal-tab" onclick="showCategory('<?=$cat['id']?>', this)"><?=e($cat['cat_name'])?></button>
            <?php endforeach; ?>
        </div>
        
        <div class="product-modal-content">
            <div class="product-grid-modal">
                <?php foreach($categories as $cat): ?>
                    <?php foreach($cat['types'] as $type): ?>
                    <div class="product-item" data-category="<?=$cat['id']?>" onclick="selectProduct('<?=e(addslashes($type['name']))?>', '<?=$type['id']?>', '<?=$type['price']?>')">
                        <div class="product-item-name"><?=e($type['name'])?></div>
                        <div class="product-item-price">฿<?=number_format($type['price'])?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="product-modal-content">
            <div class="no-products">
                <i class="fa fa-inbox"></i>
                <p>No models available</p>
                <p style="font-size: 13px;">Please add categories and types in Master Data first.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
