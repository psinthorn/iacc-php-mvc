<?php
// Note: session_start() and $db are already initialized by index.php
// Only include these if accessed directly (not recommended)
if (!isset($db)) {
    session_start();
    require_once("inc/sys.configs.php");
    require_once("inc/class.dbconn.php");
    require_once("inc/security.php");
    $db = new DbConn($config);
}
require_once("inc/class.company_filter.php");

// Company filter for multi-tenant data isolation
$companyFilter = CompanyFilter::getInstance();

$id = isset($_REQUEST['id']) ? sql_int($_REQUEST['id']) : 0;
$com_id = isset($_SESSION['com_id']) ? sql_int($_SESSION['com_id']) : 0;

// Pre-load all products for dropdown (load once, use in multiple places)
$allProducts = [];
$productQuery = "SELECT t.name, t.id, t.des, c.cat_name FROM type t LEFT JOIN category c ON t.cat_id=c.id WHERE 1=1 ORDER BY t.name";
$queryAllProducts = mysqli_query($db->conn, $productQuery);
if($queryAllProducts){
    while($prod = mysqli_fetch_assoc($queryAllProducts)){
        $allProducts[] = $prod;
    }
} else {
    $allProducts = [];
}

// Pre-load all models grouped by type_id for dropdown
$allModels = [];
$modelQuery = "SELECT m.id, m.type_id, m.model_name, m.des, m.price FROM model m WHERE m.deleted_at IS NULL ORDER BY m.model_name";
$queryAllModels = mysqli_query($db->conn, $modelQuery);
if($queryAllModels){
    while($model = mysqli_fetch_assoc($queryAllModels)){
        $typeId = $model['type_id'];
        if(!isset($allModels[$typeId])){
            $allModels[$typeId] = [];
        }
        $allModels[$typeId][] = $model;
    }
}
// Convert to JSON for JavaScript
$allModelsJson = json_encode($allModels, JSON_HEX_APOS | JSON_HEX_QUOT);

// Fetch PR data
$query = mysqli_query($db->conn, "SELECT id, name, des, cus_id, ven_id FROM pr WHERE id='".$id."' AND status='0' AND ven_id='".$com_id."'");
$prExists = mysqli_num_rows($query) == 1;
if ($prExists) {
    $data = mysqli_fetch_array($query);
    $vender = mysqli_fetch_array(mysqli_query($db->conn, "SELECT name_sh, name_en FROM company WHERE id='".$data['ven_id']."'"));
    $customer = mysqli_fetch_array(mysqli_query($db->conn, "SELECT name_sh, name_en FROM company WHERE id='".$data['cus_id']."'"));
    $limit_day = mysqli_fetch_array(mysqli_query($db->conn, "SELECT limit_day FROM company_credit WHERE ven_id='".$data['ven_id']."' AND cus_id='".$data['cus_id']."'"));
    
    $_date = explode("-", date("d-m-Y"));
    $day = $_date[0];
    $month = $_date[1];
    $year = $_date[2];
}
?>
<!DOCTYPE html>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .po-form-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }
    
    .page-header .subtitle {
        opacity: 0.9;
        font-size: 14px;
        margin-top: 4px;
    }
    
    .btn-back {
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-back:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .section-title i {
        color: #667eea;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }
    
    .form-group .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    
    .form-group .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-group .form-control[readonly] {
        background: #f9fafb;
        color: #6b7280;
    }
    
    .input-with-addon {
        display: flex;
        align-items: stretch;
    }
    
    .input-with-addon .form-control {
        border-radius: 8px 0 0 8px;
        border-right: none;
    }
    
    .input-with-addon .addon {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-left: none;
        border-radius: 0 8px 8px 0;
        padding: 10px 14px;
        font-size: 13px;
        color: #6b7280;
        white-space: nowrap;
    }
    
    /* Product Items Table */
    .products-section {
        margin-top: 24px;
    }
    
    .products-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
    }
    
    .products-header h3 {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
        color: #334155;
    }
    
    .products-header p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }
    
    .product-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.2s;
    }
    
    .product-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .product-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .product-item-number {
        background: #667eea;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
    }
    
    .product-item-name {
        font-weight: 600;
        color: #374151;
        flex: 1;
        margin-left: 12px;
    }
    
    .product-item-grid {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .product-row-main {
        display: grid;
        grid-template-columns: 1.5fr 1.5fr 1fr 0.8fr 1fr 1.2fr;
        gap: 12px;
        align-items: end;
    }
    
    .product-row-notes {
        width: 100%;
    }
    
    @media (max-width: 1400px) {
        .product-row-main {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 992px) {
        .product-row-main {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .product-row-main {
            grid-template-columns: 1fr;
        }
    }
    
    .form-group {
        margin-bottom: 0;
    }
    
    .form-group label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-group .form-control {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
        height: 46px;
    }
    
    .form-group textarea.form-control {
        height: auto;
        min-height: 70px;
        resize: vertical;
    }
    
    .form-group .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-group .form-control[readonly] {
        background: #f8fafc;
        color: #475569;
        font-weight: 500;
    }
    
    .input-with-addon {
        display: flex;
        align-items: stretch;
    }
    
    .input-with-addon .form-control {
        border-radius: 8px 0 0 8px;
        border-right: none;
    }
    
    .input-with-addon .addon {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-left: none;
        border-radius: 0 8px 8px 0;
        padding: 12px 14px;
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
        display: flex;
        align-items: center;
    }
    
    /* Labour field */
    .labour-field {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 46px;
    }
    
    .labour-field input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #667eea;
        flex-shrink: 0;
    }
    
    .labour-field input[type="text"] {
        flex: 1;
        height: 46px;
    }
    
    /* Notes row */
    .notes-row label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Actions */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-add-row {
        background: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .btn-add-row:hover {
        background: #059669;
    }
    
    .btn-remove-row {
        background: #ef4444;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-remove-row:hover {
        background: #dc2626;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    
    .error-page {
        text-align: center;
        padding: 60px 20px;
    }
    
    .error-page i {
        font-size: 64px;
        color: #e5e7eb;
        margin-bottom: 20px;
    }
    
    .error-page h3 {
        color: #374151;
        margin-bottom: 8px;
    }
    
    .error-page p {
        color: #6b7280;
    }
</style>

<script type="text/javascript">
function del_tr(btn) {
    var row = btn.closest('.product-item');
    if (row) {
        row.remove();
        updateProductNumbers();
    }
}

// All models data from PHP
var allModelsData = <?= $allModelsJson ?>;

function updateCategoryDisplay(selectElement) {
    var index = $(selectElement).data('index');
    var selectedOption = $(selectElement).find(':selected');
    var catName = selectedOption.data('cat') || 'N/A';
    var productName = selectedOption.text();
    var productDes = selectedOption.data('des') || '';
    var typeId = selectedOption.val();
    
    console.log('updateCategoryDisplay called:', {index: index, typeId: typeId, productName: productName});
    console.log('allModelsData for typeId:', allModelsData[typeId]);
    
    // Update category display
    $('#cat_display' + index).val(catName);
    
    // Update header name
    $(selectElement).closest('.product-item').find('.product-item-name').text(productName);
    
    // Update notes with product description
    $('#des' + index).val(productDes);
    
    // Update model dropdown based on selected product
    updateModelDropdown(index, typeId);
}

function updateModelDropdown(index, typeId) {
    var modelSelect = $('#model' + index);
    var selectElement = modelSelect[0];
    modelSelect.empty();
    modelSelect.append('<option value="0">-- No Model --</option>');
    
    // Convert typeId to string for object key lookup (JSON keys are strings)
    var typeIdStr = String(typeId);
    console.log('updateModelDropdown:', {index: index, typeId: typeId, typeIdStr: typeIdStr});
    console.log('Looking for models:', allModelsData[typeIdStr]);
    console.log('allModelsData keys:', Object.keys(allModelsData));
    
    if(typeId && allModelsData[typeIdStr]) {
        allModelsData[typeIdStr].forEach(function(model) {
            var escapedName = $('<div>').text(model.model_name).html();
            var escapedDes = model.des ? $('<div>').text(model.des).html() : '';
            modelSelect.append('<option value="' + model.id + '" data-des="' + escapedDes + '" data-price="' + model.price + '">' + escapedName + '</option>');
        });
    }
    
    // Refresh SmartDropdown if it exists
    if(selectElement && selectElement._smartDropdown) {
        selectElement._smartDropdown.refresh();
    }
}

function updateModelDescription(selectElement) {
    var index = $(selectElement).data('index');
    var selectedOption = $(selectElement).find(':selected');
    var modelDes = selectedOption.data('des') || '';
    var modelPrice = selectedOption.data('price');
    
    // Update notes with model description if available
    if(modelDes) {
        $('#des' + index).val(modelDes);
    }
    
    // Update price if model has a price
    if(modelPrice && modelPrice > 0) {
        $('input[name="price[' + index + ']"]').val(modelPrice);
    }
}

// Initialize SmartDropdown for dynamically added elements only
function initSmartDropdowns(container) {
    if(typeof SmartDropdown !== 'undefined' && container) {
        var selects = container.querySelectorAll('.smart-dropdown');
        selects.forEach(function(select) {
            // Check if already initialized (select is hidden when SmartDropdown is applied)
            if(select.style.display !== 'none') {
                new SmartDropdown(select);
            }
        });
    }
}

// Initialize on page load
$(document).ready(function() {
    // Note: SmartDropdowns are initialized globally by script.php
    // initSmartDropdowns is only used for dynamically added rows
    
    // Handle product selection change
    $(document).on('change', '.product-select', function() {
        updateCategoryDisplay(this);
    });
    
    // Handle model selection change
    $(document).on('change', '.model-select', function() {
        updateModelDescription(this);
    });
});

function updateProductNumbers() {
    document.querySelectorAll('.product-item').forEach((item, index) => {
        item.querySelector('.product-item-number').textContent = index + 1;
    });
}

$(function(){
    $("#addRow").click(function(){
        var indexthis = parseInt(document.getElementById("countloop").value);
        document.getElementById("countloop").value = indexthis + 1;
        
        var NR = `
        <div class="product-item" id="fr[${indexthis}]">
            <div class="product-item-header">
                <div class="product-item-number">${indexthis + 1}</div>
                <span class="product-item-name">New Item</span>
                <button type="button" class="btn-remove-row" onclick="del_tr(this)"><i class="fa fa-times"></i></button>
            </div>
            <div class="product-item-grid">
                <!-- Row 1: Product, Model, Category, Quantity, Price, Labour -->
                <div class="product-row-main">
                    <div class="form-group">
                        <label><?=$xml->product ?? 'Product'?></label>
                        <select id="type${indexthis}" name="type[${indexthis}]" class="form-control product-select" data-index="${indexthis}" required>
                            <?php 
                            echo "<option value=''>-- Select --</option>";
                            foreach($allProducts as $prod){
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
                        <label><?=$xml->model ?? 'Model'?></label>
                        <select id="model${indexthis}" name="model[${indexthis}]" class="form-control model-select" data-index="${indexthis}">
                            <option value="0">-- No Model --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=$xml->category ?? 'Category'?></label>
                        <input type="text" class="form-control" readonly value="N/A" id="cat_display${indexthis}">
                    </div>
                    <div class="form-group">
                        <label><?=$xml->unit ?? 'Quantity'?></label>
                        <div class="input-with-addon">
                            <input type="number" class="form-control" name="quantity[${indexthis}]" id="quantity${indexthis}" required value="1" min="1">
                            <span class="addon"><?=$xml->unit ?? 'Unit'?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=$xml->price ?? 'Price'?></label>
                        <div class="input-with-addon">
                            <input type="text" class="form-control" name="price[${indexthis}]" id="price${indexthis}" required placeholder="0">
                            <span class="addon"><?=$xml->baht ?? 'Baht'?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=$xml->labour ?? 'Labour'?></label>
                        <div class="labour-field">
                            <input type="checkbox" name="a_labour[${indexthis}]" id="a_labour${indexthis}" value="1">
                            <input type="text" class="form-control" name="v_labour[${indexthis}]" id="v_labour${indexthis}" placeholder="Cost...">
                        </div>
                    </div>
                </div>
                <!-- Row 2: Notes -->
                <div class="product-row-notes">
                    <div class="notes-row">
                        <label><?=$xml->note ?? 'Notes'?></label>
                        <textarea name="des[${indexthis}]" id="des${indexthis}" class="form-control" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </div>
        </div>`;
        
        $("#productsList").append(NR);
        
        // Initialize SmartDropdown for newly added selects
        var newRow = document.getElementById('fr[' + indexthis + ']');
        if(newRow) {
            initSmartDropdowns(newRow);
        }
    });
    
    $("#removeRow").click(function(){
        var items = $(".product-item");
        if(items.length > 1){
            items.last().remove();
        } else {
            alert("At least one product is required");
        }
    });
});
</script>
</head>

<body>
<div class="po-form-wrapper">
<?php if ($prExists): ?>
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2><i class="fa fa-file-text-o"></i> <?=$xml->quotation ?? 'Create Quotation'?></h2>
            <div class="subtitle">PR-<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?> → Convert to Purchase Order</div>
        </div>
        <a href="index.php?page=pr_list" class="btn-back"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
    </div>

    <form action="core-function.php" method="post" id="company-form" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        
        <!-- Request Information Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-info-circle"></i> Request Information</div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><?=$xml->name ?? 'Name'?></label>
                    <input id="name" name="name" class="form-control" required value="<?= htmlspecialchars($data['name']) ?>" type="text">
                </div>
                <div class="form-group">
                    <label><?=$xml->brand ?? 'Vendor Brand'?></label>
                    <select id="brandven" name="brandven" class="form-control" required>
                        <option value="0"><?= htmlspecialchars($vender['name_sh'] ?? $vender['name_en']) ?></option>
                        <?php 
                        $querycustomer = mysqli_query($db->conn, "SELECT brand_name, id FROM brand WHERE ven_id='".$data['ven_id']."'");
                        while($fetch_customer = mysqli_fetch_array($querycustomer)){
                            $selected = ($fetch_customer['id'] == ($data['brandven'] ?? '')) ? 'selected' : '';
                            echo "<option value='".$fetch_customer['id']."' $selected>".htmlspecialchars($fetch_customer['brand_name'])."</option>";
                        }?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?=$xml->customer ?? 'Customer'?> / <?=$xml->vendor ?? 'Vendor'?></label>
                    <div style="display: flex; gap: 8px;">
                        <select id="company_type_filter" class="form-control" style="width: 120px; flex-shrink: 0;" onchange="filterCompanyList()">
                            <option value="customer">Customer</option>
                            <option value="vendor">Vendor</option>
                            <option value="all">All</option>
                        </select>
                        <select id="cus_id" name="cus_id" class="form-control" style="flex: 1;" required>
                            <?php 
                            $queryCompanies = mysqli_query($db->conn, "SELECT id, name_sh, name_en, customer, vender FROM company WHERE deleted_at IS NULL ORDER BY name_en");
                            $allCompanies = [];
                            while($comp = mysqli_fetch_array($queryCompanies)){
                                $allCompanies[] = [
                                    'id' => $comp['id'],
                                    'name' => $comp['name_en'] ?: $comp['name_sh'],
                                    'customer' => $comp['customer'],
                                    'vendor' => $comp['vender']
                                ];
                                // Initially show only customers
                                if($comp['customer'] == 1){
                                    $compName = $comp['name_en'] ?: $comp['name_sh'];
                                    $selected = ($comp['id'] == $data['cus_id']) ? 'selected' : '';
                                    echo "<option value='".$comp['id']."' $selected>".htmlspecialchars($compName)."</option>";
                                }
                            }?>
                        </select>
                    </div>
                    <script>
                        var allCompaniesData = <?= json_encode($allCompanies) ?>;
                        
                        function filterCompanyList() {
                            var filterType = document.getElementById('company_type_filter').value;
                            var select = document.getElementById('cus_id');
                            var currentValue = select.value;
                            
                            // Clear existing options
                            select.innerHTML = '';
                            
                            // Filter and add options based on type
                            allCompaniesData.forEach(function(comp) {
                                var show = false;
                                if(filterType === 'all') {
                                    show = true;
                                } else if(filterType === 'customer' && comp.customer == 1) {
                                    show = true;
                                } else if(filterType === 'vendor' && comp.vendor == 1) {
                                    show = true;
                                }
                                
                                if(show) {
                                    var option = document.createElement('option');
                                    option.value = comp.id;
                                    option.textContent = comp.name;
                                    if(comp.id == currentValue) {
                                        option.selected = true;
                                    }
                                    select.appendChild(option);
                                }
                            });
                            
                            // Re-initialize SmartDropdown for this select
                            if(window.SmartDropdown) {
                                // Find and destroy existing SmartDropdown wrapper
                                var wrapper = select.closest('.smart-dropdown-wrapper');
                                if(wrapper) {
                                    var parent = wrapper.parentNode;
                                    parent.insertBefore(select, wrapper);
                                    wrapper.remove();
                                    select.style.display = '';
                                }
                                // Re-initialize
                                new SmartDropdown(select);
                            }
                        }
                    </script>
                </div>
            </div>
            
            <div class="form-group">
                <label><?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" readonly rows="3"><?= htmlspecialchars($data['des']) ?></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><?=$xml->vat ?? 'VAT'?></label>
                    <div class="input-with-addon">
                        <input class="form-control" required name="vat" type="text" value="7">
                        <span class="addon">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label><?=$xml->discount ?? 'Discount'?></label>
                    <div class="input-with-addon">
                        <input class="form-control" required name="dis" type="text" value="0">
                        <span class="addon">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label><?=$xml->overhead ?? 'Overhead'?></label>
                    <div class="input-with-addon">
                        <input class="form-control" required name="over" type="text" value="0">
                        <span class="addon">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label><?=$xml->validpay ?? 'Valid Payment'?></label>
                    <input class="form-control" name="valid_pay" type="text" value="<?= date('d-m-Y', mktime(0,0,0, intval($month), (intval($day)+number_format($limit_day['limit_day'] ?? 30)), intval($year))) ?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->deliverydate ?? 'Delivery Date'?></label>
                    <input class="form-control" name="deliver_date" type="text" value="<?= date('d-m-Y', mktime(0,0,0, intval($month), intval($day)+1, intval($year))) ?>">
                </div>
            </div>
        </div>

        <!-- Products Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-cubes"></i> <?=$xml->pleaseselectproduct ?? 'Product Details'?></div>
            
            <div class="products-header">
                <h3><i class="fa fa-check-circle"></i> Products from Purchase Request</h3>
                <p>These products were selected in the PR. Review and adjust quantities/prices if needed.</p>
            </div>
            
            <div id="productsList">
                <?php 
                $qeurytmpitem = mysqli_query($db->conn, "SELECT tmp_product.*, type.name as type_name, type.des as type_des, type.cat_id, category.cat_name 
                    FROM tmp_product 
                    JOIN type ON tmp_product.type=type.id 
                    LEFT JOIN category ON type.cat_id=category.id 
                    WHERE pr_id='".$id."'");
                $i = 0;
                if(mysqli_num_rows($qeurytmpitem) > 0){
                    while($data_fetitem = mysqli_fetch_array($qeurytmpitem)){ ?>
                
                <div class="product-item" id="fr[<?=$i?>]">
                    <div class="product-item-header">
                        <div class="product-item-number"><?= $i + 1 ?></div>
                        <span class="product-item-name"><?= htmlspecialchars($data_fetitem['type_name']) ?></span>
                        <?php if($i > 0): ?>
                        <button type="button" class="btn-remove-row" onclick="del_tr(this)"><i class="fa fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-item-grid">
                        <!-- Row 1: Product, Model, Category, Quantity, Price, Labour -->
                        <div class="product-row-main">
                            <!-- Product -->
                            <div class="form-group">
                                <label><?=$xml->product ?? 'Product'?></label>
                                <select name="type[<?=$i?>]" id="type<?=$i?>" class="form-control product-select" data-index="<?=$i?>" required>
                                    <?php 
                                    if(empty($allProducts)){
                                        echo "<option value=''>No products available</option>";
                                    } else {
                                        foreach($allProducts as $prod){
                                            $selected = ($prod['id'] == $data_fetitem['type']) ? 'selected' : '';
                                            $escapedCat = htmlspecialchars($prod['cat_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                                            $escapedDes = htmlspecialchars($prod['des'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $escapedName = htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8');
                                            echo "<option value='".$prod['id']."' data-cat='".$escapedCat."' data-des='".$escapedDes."' $selected>".$escapedName."</option>";
                                        }
                                    }?>
                                </select>
                                <input type="hidden" name="ban_id[<?=$i?>]" value="0">
                                <input type="hidden" name="pack_quantity[<?=$i?>]" value="1">
                            </div>
                            
                            <!-- Model -->
                            <div class="form-group">
                                <label><?=$xml->model ?? 'Model'?></label>
                                <select name="model[<?=$i?>]" id="model<?=$i?>" class="form-control model-select" data-index="<?=$i?>">
                                    <option value="0">-- No Model --</option>
                                    <?php 
                                    $typeId = $data_fetitem['type'];
                                    if(isset($allModels[$typeId])){
                                        foreach($allModels[$typeId] as $model){
                                            $escapedModelName = htmlspecialchars($model['model_name'], ENT_QUOTES, 'UTF-8');
                                            $escapedModelDes = htmlspecialchars($model['des'] ?? '', ENT_QUOTES, 'UTF-8');
                                            echo "<option value='".$model['id']."' data-des='".$escapedModelDes."' data-price='".$model['price']."'>".$escapedModelName."</option>";
                                        }
                                    }?>
                                </select>
                            </div>
                            
                            <!-- Category -->
                            <div class="form-group">
                                <label><?=$xml->category ?? 'Category'?></label>
                                <input type="text" class="form-control" readonly id="cat_display<?=$i?>" value="<?= htmlspecialchars($data_fetitem['cat_name'] ?? 'N/A') ?>">
                            </div>
                            
                            <!-- Quantity -->
                            <div class="form-group">
                                <label><?=$xml->unit ?? 'Quantity'?></label>
                                <div class="input-with-addon">
                                    <input type="number" class="form-control" name="quantity[<?=$i?>]" value="<?= $data_fetitem['quantity'] ?>" id="quantity[<?=$i?>]" required min="1">
                                    <span class="addon"><?=$xml->unit ?? 'Unit'?></span>
                                </div>
                            </div>
                            
                            <!-- Price -->
                            <div class="form-group">
                                <label><?=$xml->price ?? 'Price'?></label>
                                <div class="input-with-addon">
                                    <input type="text" class="form-control" name="price[<?=$i?>]" id="price[<?=$i?>]" required value="<?= $data_fetitem['price'] ?>">
                                    <span class="addon"><?=$xml->baht ?? 'Baht'?></span>
                                </div>
                            </div>
                            
                            <!-- Labour -->
                            <div class="form-group">
                                <label><?=$xml->labour ?? 'Labour'?></label>
                                <div class="labour-field">
                                    <input type="checkbox" name="a_labour[<?=$i?>]" id="a_labour[<?=$i?>]" value="1">
                                    <input type="text" class="form-control" name="v_labour[<?=$i?>]" id="v_labour[<?=$i?>]" placeholder="Cost...">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Row 2: Notes -->
                        <div class="product-row-notes">
                            <div class="notes-row">
                                <label><?=$xml->note ?? 'Notes'?></label>
                                <textarea name="des[<?=$i?>]" id="des<?=$i?>" class="form-control" placeholder="Additional notes for this item..."><?= htmlspecialchars($data_fetitem['type_des'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php $i++; }}?>
            </div>
            
            <div class="form-actions">
                <button type="button" id="addRow" class="btn-add-row"><i class="fa fa-plus"></i> Add Product</button>
                <button type="button" id="removeRow" class="btn-remove-row"><i class="fa fa-minus"></i> Remove Last</button>
                
                <input type="hidden" id="countloop" name="countloop" value="<?=$i?>">
                <input type="hidden" name="method" value="A">
                <input type="hidden" name="ref" value="<?= $data['id'] ?>">
                <input type="hidden" name="page" value="po_list">
                
                <button type="submit" class="btn-submit"><i class="fa fa-check"></i> <?=$xml->save ?? 'Create Order'?></button>
            </div>
        </div>
    </form>

<?php else: ?>
    <!-- Error State -->
    <div class="form-card error-page">
        <i class="fa fa-exclamation-circle"></i>
        <h3>Purchase Request Not Found</h3>
        <p>The requested PR does not exist, has already been processed, or you don't have permission to access it.</p>
        <a href="index.php?page=pr_list" class="btn-back" style="display:inline-block; margin-top:20px; background:#667eea; color:white; padding:12px 24px; border-radius:8px; text-decoration:none;">
            <i class="fa fa-arrow-left"></i> Back to PR List
        </a>
    </div>
<?php endif; ?>
</div>
</body>
</html>