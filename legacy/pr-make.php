<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
// $users=new DbConn($config);
// // Security already checked in index.php

// Include CompanyFilter if not already loaded
if (!class_exists('CompanyFilter')) {
    require_once("inc/class.company_filter.php");
}

// Load products data for the modal
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;
$companyFilter = CompanyFilter::getInstance();

// For product selection, we need to show products available
// If a company is selected, show that company's products
// If no company selected (admin), show all products
$companyCondition = '';
if ($com_id > 0) {
    $companyCondition = " AND company_id = " . intval($com_id);
}

// Fetch categories with products (types)
$categories = [];
$querycat = mysqli_query($db->conn, "SELECT * FROM category WHERE 1=1" . $companyCondition);
if ($querycat) {
    while($cat = mysqli_fetch_assoc($querycat)) {
        $cat['types'] = [];
        $query_type = mysqli_query($db->conn, "SELECT * FROM type WHERE cat_id='" . intval($cat['id']) . "'" . $companyCondition);
        if ($query_type) {
            while($type = mysqli_fetch_assoc($query_type)) {
                // Get average price from product history
                $sql = "SELECT COALESCE(SUM(p.price)/NULLIF(SUM(p.quantity),0), 0) as net 
                        FROM product p 
                        WHERE p.type='" . intval($type['id']) . "'";
                $netResult = mysqli_fetch_assoc(mysqli_query($db->conn, $sql));
                $type['price'] = floor($netResult['net'] ?? 0);
                $cat['types'][] = $type;
            }
        }
        // Include category even if it has no types with products
        // This allows selecting types that haven't been ordered before
        if (!empty($cat['types'])) {
            $categories[] = $cat;
        }
    }
}

// If still no categories, try without company filter (for testing/admin)
if (empty($categories) && $com_id == 0) {
    $querycat = mysqli_query($db->conn, "SELECT * FROM category WHERE 1=1 LIMIT 50");
    if ($querycat) {
        while($cat = mysqli_fetch_assoc($querycat)) {
            $cat['types'] = [];
            $query_type = mysqli_query($db->conn, "SELECT * FROM type WHERE cat_id='" . intval($cat['id']) . "' LIMIT 100");
            if ($query_type) {
                while($type = mysqli_fetch_assoc($query_type)) {
                    $sql = "SELECT COALESCE(SUM(p.price)/NULLIF(SUM(p.quantity),0), 0) as net 
                            FROM product p WHERE p.type='" . intval($type['id']) . "'";
                    $netResult = mysqli_fetch_assoc(mysqli_query($db->conn, $sql));
                    $type['price'] = floor($netResult['net'] ?? 0);
                    $cat['types'][] = $type;
                }
            }
            if (!empty($cat['types'])) {
                $categories[] = $cat;
            }
        }
    }
}
?>
<!-- Modern Font - loaded inline for included page -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Smart Dropdown Component -->
<link href="css/smart-dropdown.css" rel="stylesheet">
<style>
    .pr-form-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header .subtitle {
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
    
    .form-card .section-title i {
        color: #667eea;
    }
    
    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        font-weight: 500;
        color: #374151;
        font-size: 13px;
        margin-bottom: 6px;
    }
    
    .form-card .form-control {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 14px 16px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
        width: 100%;
        min-height: 48px;
    }
    
    .form-card .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .product-grid {
        margin-top: 8px;
    }
    
    .product-header {
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
    
    .product-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-top: none;
        align-items: center;
    }
    
    .product-row:nth-child(even) {
        background: #fafafa;
    }
    
    .product-row:last-child {
        border-radius: 0 0 8px 8px;
    }
    
    .product-row input {
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        padding: 12px 14px;
        font-size: 14px;
        width: 100%;
        min-height: 44px;
    }
    
    .product-row input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .product-row input[readonly] {
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
    
    .btn-clear-row:hover {
        background: #ef4444;
        color: white;
    }
    
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
    
    .summary-section label {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
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
    
    .form-actions {
        margin-top: 20px;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .btn-submit {
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
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    }
    
    /* Product Modal Styles */
    .product-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }
    
    .product-modal-overlay.active {
        display: flex;
    }
    
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
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .product-modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .product-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .product-modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .product-modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
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
    
    .product-sort-buttons {
        display: flex;
        gap: 8px;
    }
    
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .product-sort-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    
    .product-sort-btn.active {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }
    
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
    
    .product-modal-tab:hover {
        color: #667eea;
    }
    
    .product-modal-tab.active {
        color: #667eea;
        border-bottom-color: #667eea;
        background: white;
    }
    
    .product-modal-content {
        padding: 24px;
        max-height: 400px;
        overflow-y: auto;
    }
    
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
    
    .smart-search-input {
        font-size: 15px !important;
        padding: 14px 18px !important;
        font-weight: 500;
    }
    
    .smart-search-input::placeholder {
        color: #9ca3af;
    }
    
    .product-item-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .product-item-price {
        color: #10b981;
        font-weight: 700;
        font-size: 16px;
    }
    
    .product-item-category {
        display: none;
    }
    
    .no-products {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    
    .no-products i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
</style>
<script type="text/javascript">
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
	for(i=0;i<9;i++){
		totalsum+=parseFloat(document.getElementById('total'+i).value);
	}
	document.getElementById('totalnet').value=totalsum.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2});
}

// Modern Modal-based Product Selection
var currentRowIndex = null;

function openProductModal(rowIndex) {
    currentRowIndex = rowIndex;
    document.getElementById('productModal').classList.add('active');
    var searchInput = document.getElementById('productSearch');
    searchInput.value = '';
    filterProducts('');
    // Show first tab
    var tabs = document.querySelectorAll('.product-modal-tab');
    if (tabs.length > 0) {
        tabs[0].click();
    }
    // Auto-focus search input after modal animation
    setTimeout(function() {
        searchInput.focus();
    }, 100);
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
    // Update active tab
    document.querySelectorAll('.product-modal-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    
    // Show/hide products
    document.querySelectorAll('.product-item').forEach(item => {
        if (catId === 'all' || item.dataset.category === catId) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

var highlightedIndex = -1;
var visibleItems = [];

function filterProducts(search) {
    search = search.toLowerCase();
    visibleItems = [];
    highlightedIndex = -1;
    
    document.querySelectorAll('.product-item').forEach(item => {
        var name = item.querySelector('.product-item-name').textContent.toLowerCase();
        item.classList.remove('highlighted');
        if (name.includes(search)) {
            item.style.display = 'block';
            visibleItems.push(item);
        } else {
            item.style.display = 'none';
        }
    });
    
    // Auto-highlight first result
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
        if (highlightedIndex >= 0 && visibleItems[highlightedIndex]) {
            visibleItems[highlightedIndex].click();
        }
    } else if (event.key === 'Escape') {
        closeProductModal();
    }
}

function sortProducts(order, btn) {
    // Update active button
    document.querySelectorAll('.product-sort-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    var grid = document.querySelector('.product-grid-modal');
    var items = Array.from(grid.querySelectorAll('.product-item'));
    
    items.sort(function(a, b) {
        var nameA = a.querySelector('.product-item-name').textContent.toLowerCase();
        var nameB = b.querySelector('.product-item-name').textContent.toLowerCase();
        var priceA = parseFloat(a.querySelector('.product-item-price').textContent.replace(/[^\d.-]/g, '')) || 0;
        var priceB = parseFloat(b.querySelector('.product-item-price').textContent.replace(/[^\d.-]/g, '')) || 0;
        
        switch(order) {
            case 'az':
                return nameA.localeCompare(nameB);
            case 'za':
                return nameB.localeCompare(nameA);
            case 'price-asc':
                return priceA - priceB;
            case 'price-desc':
                return priceB - priceA;
            default:
                return 0;
        }
    });
    
    // Reorder items in DOM
    items.forEach(function(item) {
        grid.appendChild(item);
    });
}

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('productModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeProductModal();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeProductModal();
        }
    });
});
</script>

<div class="pr-form-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest?></h2>
        <div class="subtitle">Create a new purchase request for a customer</div>
    </div>

    <form action="core-function.php" method="post" id="company-form">
        <?= csrf_field() ?>
        <!-- Basic Info Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-info-circle"></i> Request Information</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><?=$xml->name?></label>
                    <input id="name" name="name" placeholder="<?=$xml->name?>" class="form-control" required type="text">
                </div>
                <div class="form-group">
                    <label for="cus_id"><?=$xml->customer?></label>
                    <select id="cus_id" name="cus_id" class="form-control smart-dropdown" data-placeholder="Select Customer..." data-sort-order="asc">
                        <?php 
                        $com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;
                        if ($com_id > 0) {
                            $querycustomer = mysqli_query($db->conn, "SELECT name_en, id FROM company 
                                WHERE customer='1' AND company_id = '$com_id' AND deleted_at IS NULL
                                ORDER BY name_en");
                        } else {
                            $querycustomer = mysqli_query($db->conn, "SELECT name_en, id FROM company WHERE customer='1' ORDER BY name_en");
                        }
                        
                        while($fetch_customer=mysqli_fetch_array($querycustomer)){
                            echo "<option value='".$fetch_customer['id']."' >".$fetch_customer['name_en']."</option>";
                        }?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="des"><?=$xml->Description?></label>
                <textarea id="des" name="des" class="form-control" required placeholder="<?=$xml->Description?>" rows="3"></textarea>
            </div>
        </div>

        <!-- Products Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-shopping-cart"></i> <?=$xml->Product?> Selection</div>
            
            <div class="product-grid">
                <div class="product-header">
                    <div><?=$xml->Product?></div>
                    <div><?=$xml->Unit?></div>
                    <div><?=$xml->Price?></div>
                    <div><?=$xml->Total?></div>
                    <div></div>
                </div>
                
                <?php for($i=0;$i<9;$i++){ ?>
                <div class="product-row">
                    <input type='text' name='ordername[<?php echo $i;?>]' id='ordername<?php echo $i;?>' readonly='true' placeholder="Click to select product..." onclick='openProductModal(<?php echo $i;?>);' style="cursor: pointer;"/>
                    <input type='hidden' name='id<?php echo $i;?>' readonly='true'  id='id<?php echo $i;?>' value='0' />
                    <input type='text' name='quantity<?php echo $i;?>' id='quantity<?php echo $i;?>' onchange="calprice('<?php echo $i;?>'); " required value='1' />
                    <input type='text' name='price<?php echo $i;?>' readonly='true' id='price<?php echo $i;?>' value='0' />
                    <input type='text' name='total<?php echo $i;?>' readonly='true' id='total<?php echo $i;?>' value='0' />
                    <button type="button" class="btn-clear-row" onclick="document.getElementById('ordername[<?php echo $i;?>]').value='';
                        document.getElementById('price<?php echo $i;?>').value='0';
                        document.getElementById('id<?php echo $i;?>').value='';
                        document.getElementById('quantity<?php echo $i;?>').value='1';
                        document.getElementById('total<?php echo $i;?>').value='0';sumall();">✕</button>
                </div>
                <?php } ?>
            </div>
            
            <div class="summary-section">
                <label><?=$xml->summary?></label>
                <input type='text' name='totalnet' id='totalnet' readonly='true' value='0'/>
            </div>
            
            <div class="form-actions">
                <input type="hidden" name="method" value="A">
                <input type="hidden" name="page" value="pr_list">
                <input type="hidden" name="ven_id" value="<?php echo isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0; ?>">
                <button type="submit" class="btn-submit"><i class="fa fa-paper-plane"></i> <?=$xml->request?></button>
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
                <button type="button" class="product-sort-btn" onclick="sortProducts('az', this)">
                    <i class="fa fa-sort-alpha-asc"></i> A-Z
                </button>
                <button type="button" class="product-sort-btn" onclick="sortProducts('za', this)">
                    <i class="fa fa-sort-alpha-desc"></i> Z-A
                </button>
                <button type="button" class="product-sort-btn" onclick="sortProducts('price-asc', this)">
                    <i class="fa fa-sort-amount-asc"></i> Price ↑
                </button>
                <button type="button" class="product-sort-btn" onclick="sortProducts('price-desc', this)">
                    <i class="fa fa-sort-amount-desc"></i> Price ↓
                </button>
            </div>
        </div>
        
        <?php if (!empty($categories)): ?>
        <div class="product-modal-tabs">
            <button type="button" class="product-modal-tab active" onclick="showCategory('all', this)">All</button>
            <?php foreach($categories as $cat): ?>
            <button type="button" class="product-modal-tab" onclick="showCategory('<?= $cat['id'] ?>', this)"><?= htmlspecialchars($cat['cat_name']) ?></button>
            <?php endforeach; ?>
        </div>
        
        <div class="product-modal-content">
            <div class="product-grid-modal">
                <?php foreach($categories as $cat): ?>
                    <?php foreach($cat['types'] as $type): ?>
                    <div class="product-item" data-category="<?= $cat['id'] ?>" onclick="selectProduct('<?= htmlspecialchars(addslashes($type['name'])) ?>', '<?= $type['id'] ?>', '<?= $type['price'] ?>')">
                        <div class="product-item-name"><?= htmlspecialchars($type['name']) ?></div>
                        <div class="product-item-price">฿<?= number_format($type['price']) ?></div>
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