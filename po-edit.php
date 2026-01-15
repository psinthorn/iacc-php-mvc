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
$users = $db; // for backward compatibility

// Always load CompanyFilter
require_once("inc/class.company_filter.php");
$companyFilter = CompanyFilter::getInstance();

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);

$_date = explode("-", date("d-m-Y"));
$day = $_date[0];
$month = $_date[1];
$year = $_date[2];

// Pre-load all products for dropdown
$allProducts = [];
$productQuery = "SELECT t.name, t.id, t.des, c.cat_name FROM type t LEFT JOIN category c ON t.cat_id=c.id WHERE 1=1 " . $companyFilter->andCompanyFilter('t') . " ORDER BY t.name";
$queryAllProducts = mysqli_query($db->conn, $productQuery);
if($queryAllProducts){
    while($prod = mysqli_fetch_assoc($queryAllProducts)){
        $allProducts[] = $prod;
    }
}

// Pre-load all models grouped by type_id
$allModels = [];
$modelQuery = "SELECT m.id, m.type_id, m.brand_id, m.model_name, m.des, m.price FROM model m WHERE m.deleted_at IS NULL " . $companyFilter->andCompanyFilter('m') . " ORDER BY m.model_name";
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
$allModelsJson = json_encode($allModels, JSON_HEX_APOS | JSON_HEX_QUOT);

// Build query - using pr.ven_id for vendor filter
$venIdCondition = ($com_id > 0) ? "and (pr.cus_id='".$com_id."' or pr.ven_id='".$com_id."')" : "";
$sql = "select po.id as id,ref,over,bandven,des, po.name as name,vat,adr_tax,city_tax,district_tax,province_tax,zip_tax as zip_des,DATE_FORMAT(po.date,'%d-%m-%Y') as datepo, pr.cus_id,dis,pr.ven_id from po join pr on po.ref=pr.id join company_addr on pr.cus_id=company_addr.com_id where po.id='".$id."' and pr.status='1' ".$venIdCondition." and po_id_new=''";
$query = mysqli_query($db->conn, $sql);
$numRows = $query ? mysqli_num_rows($query) : 0;

if($numRows >= 1):
    $data = mysqli_fetch_array($query);
    $vender = mysqli_fetch_array(mysqli_query($db->conn, "select name_sh,name_en from company where id='".$data['ven_id']."'"));
    $customer = mysqli_fetch_array(mysqli_query($db->conn, "select name_sh,name_en,id from company where id='".$data['cus_id']."'"));
    $limit_day = mysqli_fetch_array(mysqli_query($db->conn, "select limit_day from company_credit where ven_id='".$data['ven_id']."' and cus_id='".$data['cus_id']."'"));
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- smart-dropdown.css removed: using Bootstrap 5 only -->
<style>
    .po-form-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(16, 185, 129, 0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        text-decoration: none;
    }
    
    .btn-back:hover {
        background: rgba(255,255,255,0.3);
        color: white;
        text-decoration: none;
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
        color: #10b981;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    
    .form-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    
    @media (max-width: 992px) {
        .form-grid-3, .form-grid-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .form-grid-3, .form-grid-4 {
            grid-template-columns: 1fr;
        }
    }
    
    .form-group {
        margin-bottom: 0;
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
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
        min-height: 44px;
    }
    
    .form-group .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        outline: none;
    }
    
    .form-group .form-control[readonly],
    .form-group .form-control[disabled] {
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
        padding: 12px 14px;
        font-size: 13px;
        color: #6b7280;
        white-space: nowrap;
        display: flex;
        align-items: center;
        min-height: 44px;
    }
    
    /* Product Items */
    .products-section {
        margin-top: 8px;
    }
    
    .products-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .products-header-left h3 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 600;
        color: #334155;
    }
    
    .products-header-left p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }
    
    .products-header-actions {
        display: flex;
        gap: 8px;
    }
    
    .btn-add-product {
        background: #10b981;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .btn-add-product:hover {
        background: #059669;
        transform: translateY(-1px);
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
        background: #10b981;
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
    
    .product-item-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .product-item-name {
        font-weight: 600;
        color: #374151;
    }
    
    .btn-remove-item {
        background: #fee2e2;
        color: #dc2626;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .btn-remove-item:hover {
        background: #fecaca;
    }
    
    .product-item-grid {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .product-row-main {
        display: grid;
        grid-template-columns: 1.5fr 1.5fr 0.8fr 1fr 1.2fr;
        gap: 12px;
        align-items: end;
    }
    
    @media (max-width: 1200px) {
        .product-row-main {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .product-row-main {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .product-row-main {
            grid-template-columns: 1fr;
        }
    }
    
    .product-row-notes {
        width: 100%;
    }
    
    .product-row-notes textarea {
        resize: vertical;
        min-height: 70px;
    }
    
    textarea.form-control {
        min-height: 70px;
    }
    
    .labour-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .labour-checkbox {
        width: 22px;
        height: 22px;
        accent-color: #10b981;
        cursor: pointer;
    }
    
    select.form-control {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
    }
    
    /* Form Actions */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
        margin-top: 20px;
    }
    
    .btn-primary-custom {
        background: #10b981;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary-custom:hover {
        background: #059669;
        transform: translateY(-1px);
    }
    
    .btn-secondary-custom {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    
    .btn-secondary-custom:hover {
        background: #e5e7eb;
        text-decoration: none;
        color: #374151;
    }
    
    /* Products list container */
    #productsList {
        min-height: 100px;
    }
    
    .empty-products {
        text-align: center;
        padding: 40px;
        color: #9ca3af;
    }
    
    .empty-products i {
        font-size: 48px;
        margin-bottom: 12px;
        display: block;
    }
</style>

<div class="po-form-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2><i class="fa fa-edit"></i> <?=$xml->quotation ?? 'Edit Quotation'?></h2>
            <div class="subtitle"><?=$xml->editquotation ?? 'Modify quotation details and products'?></div>
        </div>
        <a href="index.php?page=qa_list" class="btn-back">
            <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?>
        </a>
    </div>

    <form action="core-function.php" method="post" id="po-edit-form">
        <?= csrf_field() ?>
        <input type="hidden" name="method" value="E">
        <input type="hidden" name="ref" value="<?php echo $data['ref'];?>">
        <input type="hidden" name="page" value="po_list">
        <input type="hidden" name="id" value="<?php echo $id;?>">
        <input type="hidden" id="countloop" name="countloop" value="0">
        
        <!-- Basic Info Card -->
        <div class="form-card">
            <div class="section-title">
                <i class="fa fa-info-circle"></i>
                <?=$xml->basicinfo ?? 'Basic Information'?>
            </div>
            
            <div class="form-grid-3">
                <div class="form-group">
                    <label><?=$xml->name ?? 'Name'?></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($data['name']);?>">
                </div>
                
                <div class="form-group">
                    <label><?=$xml->customer ?? 'Customer'?></label>
                    <select name="cus_id" id="cus_id" class="form-control" onchange="fetadr(this.value,this.id)">
                        <?php 
                        $query_cus = mysqli_query($db->conn, "select name_en,id from company where customer='1'");
                        while($fetch_cus = mysqli_fetch_array($query_cus)){
                            $selected = ($fetch_cus['id'] == $customer['id']) ? 'selected' : '';
                            echo "<option value='".$fetch_cus['id']."' ".$selected.">".$fetch_cus['name_en']."</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?=$xml->brand ?? 'Brand'?></label>
                    <select name="brandven" id="brandven" class="form-control">
                        <option value="0"><?php echo htmlspecialchars($vender['name_sh']);?></option>
                        <?php 
                        $querycustomer = mysqli_query($db->conn, "select brand_name,id from brand where ven_id='".$data['ven_id']."'");
                        while($fetch_customer = mysqli_fetch_array($querycustomer)){
                            $selected = ($fetch_customer['id'] == $data['bandven']) ? 'selected' : '';
                            echo "<option value='".$fetch_customer['id']."' ".$selected.">".$fetch_customer['brand_name']."</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Address Card -->
        <div class="form-card">
            <div class="section-title">
                <i class="fa fa-map-marker"></i>
                <?=$xml->address ?? 'Address Information'?>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><?=$xml->raddress ?? 'Address'?></label>
                    <input type="text" id="adr_tax" class="form-control" disabled value="<?php echo htmlspecialchars($data['adr_tax']);?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->rcity ?? 'City'?></label>
                    <input type="text" id="city_tax" class="form-control" disabled value="<?php echo htmlspecialchars($data['city_tax']);?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->rdistrict ?? 'District'?></label>
                    <input type="text" id="district_tax" class="form-control" disabled value="<?php echo htmlspecialchars($data['district_tax']);?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->rprovince ?? 'Province'?></label>
                    <input type="text" id="province_tax" class="form-control" disabled value="<?php echo htmlspecialchars($data['province_tax']);?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->rzip ?? 'ZIP'?></label>
                    <input type="text" id="zip_tax" class="form-control" disabled value="<?php echo htmlspecialchars($data['zip_des']);?>">
                </div>
            </div>
        </div>
        
        <!-- Pricing & Dates Card -->
        <div class="form-card">
            <div class="section-title">
                <i class="fa fa-calculator"></i>
                <?=$xml->pricingdates ?? 'Pricing & Dates'?>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><?=$xml->vat ?? 'VAT'?></label>
                    <div class="input-with-addon">
                        <input type="text" name="vat" class="form-control" required value="<?php echo $data['vat'];?>">
                        <span class="addon">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label><?=$xml->discount ?? 'Discount'?></label>
                    <div class="input-with-addon">
                        <input type="text" name="dis" class="form-control" required value="<?php echo number_format($data['dis']);?>">
                        <span class="addon">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label><?=$xml->overhead ?? 'Overhead'?></label>
                    <div class="input-with-addon">
                        <input type="text" name="over" class="form-control" required value="<?php echo number_format($data['over']);?>">
                        <span class="addon">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label><?=$xml->validpay ?? 'Valid Until'?></label>
                    <input type="text" name="valid_pay" class="form-control" value="<?php echo date('d-m-Y', mktime(0,0,0, intval($month), (intval($day)+intval($limit_day['limit_day'] ?? 30)), intval($year)));?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->deliverydate ?? 'Delivery Date'?></label>
                    <input type="text" name="deliver_date" class="form-control" value="<?php echo date('d-m-Y', mktime(0,0,0, intval($month), intval($day)+1, intval($year)));?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->createdate ?? 'Created'?></label>
                    <input type="text" name="create_date" class="form-control" readonly value="<?php echo $data['datepo'];?>">
                </div>
            </div>
        </div>
        
        <!-- Products Card -->
        <div class="form-card">
            <div class="products-section">
                <div class="products-header">
                    <div class="products-header-left">
                        <h3><i class="fa fa-cube"></i> <?=$xml->pleaseselectproduct ?? 'Products'?></h3>
                        <p><?=$xml->addproductsquotation ?? 'Add products to this quotation'?></p>
                    </div>
                    <div class="products-header-actions">
                        <button type="button" id="addRow" class="btn-add-product">
                            <i class="fa fa-plus"></i> <?=$xml->addproduct ?? 'Add Product'?>
                        </button>
                    </div>
                </div>
                
                <div id="productsList">
                    <?php 
                    $query_pro = mysqli_query($db->conn, "select * from product where po_id='".$id."'");
                    $i = 0;
                    
                    while($data_pro = mysqli_fetch_array($query_pro)):
                        // If ban_id is 0 or empty, try to get brand from the model
                        $effective_ban_id = $data_pro['ban_id'];
                        if(empty($effective_ban_id) && !empty($data_pro['model'])) {
                            $model_brand = mysqli_fetch_array(mysqli_query($db->conn, "select brand_id from model where id='".$data_pro['model']."'"));
                            if($model_brand && !empty($model_brand['brand_id'])) {
                                $effective_ban_id = $model_brand['brand_id'];
                            }
                        }
                        
                        // Get product name for display
                        $productNameDisplay = '';
                        foreach($allProducts as $prod) {
                            if($prod['id'] == $data_pro['type']) {
                                $productNameDisplay = $prod['name'];
                                break;
                            }
                        }
                    ?>
                    <div class="product-item" id="product-item-<?=$i?>">
                        <div class="product-item-header">
                            <div class="product-item-title">
                                <span class="product-item-number"><?=$i + 1?></span>
                                <span class="product-item-name" id="product-name-<?=$i?>"><?=$productNameDisplay ?: ($xml->product ?? 'Product') . ' #' . ($i + 1)?></span>
                            </div>
                            <button type="button" class="btn-remove-item" onclick="removeProductItem(<?=$i?>)">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="product-item-grid">
                            <div class="product-row-main">
                                <div class="form-group">
                                    <label><?=$xml->product ?? 'Product'?></label>
                                    <select name="type[<?=$i?>]" id="type[<?=$i?>]" class="form-control" required onchange="checkorder(this.value,this.id)">
                                        <option value="">-- <?=$xml->selectproduct ?? 'Select Product'?> --</option>
                                        <?php 
                                        foreach($allProducts as $prod){
                                            $selected = ($data_pro['type'] == $prod['id']) ? 'selected' : '';
                                            echo "<option value='".$prod['id']."' ".$selected.">".htmlspecialchars($prod['name'])."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><?=$xml->model ?? 'Model'?></label>
                                    <select name="model[<?=$i?>]" id="model[<?=$i?>]" class="form-control" required onchange="checkorder3(this.value,this.id)">
                                        <option value="">-- <?=$xml->selectmodel ?? 'Select Model'?> --</option>
                                        <?php 
                                        $type_id = $data_pro['type'];
                                        if(isset($allModels[$type_id])){
                                            foreach($allModels[$type_id] as $model){
                                                $selected = ($data_pro['model'] == $model['id']) ? 'selected' : '';
                                                $price = htmlspecialchars($model['price'] ?? '0');
                                                $des = htmlspecialchars($model['des'] ?? '');
                                                echo "<option value='".$model['id']."' data-price='".$price."' data-des='".$des."' ".$selected.">".htmlspecialchars($model['model_name'])."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><?=$xml->quantity ?? 'Qty'?></label>
                                    <input type="number" name="quantity[<?=$i?>]" id="quantity[<?=$i?>]" class="form-control" required value="<?php echo $data_pro['quantity'];?>" min="1">
                                </div>
                                
                                <div class="form-group">
                                    <label><?=$xml->price ?? 'Price'?></label>
                                    <div class="input-with-addon">
                                        <input type="text" name="price[<?=$i?>]" id="price[<?=$i?>]" class="form-control" required value="<?php echo $data_pro['price'];?>">
                                        <span class="addon"><?=$xml->baht ?? '฿'?></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><?=$xml->labour ?? 'Labour'?></label>
                                    <div class="labour-group">
                                        <input type="checkbox" name="a_labour[<?=$i?>]" id="a_labour[<?=$i?>]" value="1" class="labour-checkbox" <?php if($data_pro['activelabour']=="1") echo "checked";?>>
                                        <input type="text" name="v_labour[<?=$i?>]" id="v_labour[<?=$i?>]" class="form-control" placeholder="0" value="<?=$data_pro['valuelabour'];?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="product-row-notes">
                                <div class="form-group">
                                    <label><?=$xml->notes ?? 'Notes'?></label>
                                    <textarea name="des[<?=$i?>]" id="des[<?=$i?>]" class="form-control" placeholder="<?=$xml->addnotes ?? 'Add notes...'?>"><?=$data_pro['des'];?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="ban_id[<?=$i?>]" id="ban_id[<?=$i?>]" value="<?=$effective_ban_id?>">
                        <input type="hidden" name="pack_quantity[<?=$i?>]" value="1">
                    </div>
                    <?php 
                    $i++;
                    endwhile;
                    ?>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <a href="index.php?page=qa_list" class="btn-secondary-custom"><?=$xml->cancel ?? 'Cancel'?></a>
                <button type="submit" class="btn-primary-custom">
                    <i class="fa fa-save"></i> <?=$xml->save ?? 'Save Changes'?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- smart-dropdown.js removed: using Bootstrap 5 only -->
<script type="text/javascript">
// Store all models data for dynamic population (same approach as po-make.php)
var allModelsData = <?=$allModelsJson?>;
var productCount = <?=$i?>;

// Update counter
document.getElementById('countloop').value = productCount;

// Fetch address when customer changes
function fetadr(value, id) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var parts = xhr.responseText.split(";");
            document.getElementById("adr_tax").value = parts[0] || '';
            document.getElementById("city_tax").value = parts[1] || '';
            document.getElementById("district_tax").value = parts[2] || '';
            document.getElementById("province_tax").value = parts[3] || '';
            document.getElementById("zip_tax").value = parts[4] || '';
        }
    };
    xhr.open("GET", "fetadr.php?id=" + value, true);
    xhr.send();
}

// Update model dropdown based on selected product type (matching po-make.php approach)
function updateModelDropdown(index, typeId) {
    var modelSelect = document.getElementById("model[" + index + "]");
    if (!modelSelect) return;
    
    // Clear existing options
    modelSelect.innerHTML = '<option value="">-- Select Model --</option>';
    
    // Convert typeId to string for object key lookup
    var typeIdStr = String(typeId);
    
    if (typeId && allModelsData[typeIdStr]) {
        allModelsData[typeIdStr].forEach(function(model) {
            var option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.model_name;
            option.setAttribute('data-price', model.price || 0);
            option.setAttribute('data-des', model.des || '');
            modelSelect.appendChild(option);
        });
    }
}

// When product type changes - use client-side model population
function checkorder(value, id) {
    var id1 = id.split("[");
    var index = id1[1].split("]")[0];
    
    // Update model dropdown from pre-loaded data
    updateModelDropdown(index, value);
    
    // Update product name in header
    var select = document.getElementById(id);
    if (select && select.selectedIndex > 0) {
        var productName = select.options[select.selectedIndex].text;
        var nameSpan = document.getElementById('product-name-' + index);
        if (nameSpan) nameSpan.textContent = productName;
    }
}

// checkorder2 is kept for backward compatibility (brand selection)
function checkorder2(value, id) {
    // Not used in simplified flow - models load directly from product
}

// When model changes, get price and description from data attributes
function checkorder3(value, id) {
    var id1 = id.split("[");
    var index = id1[1].split("]")[0];
    
    var modelSelect = document.getElementById("model[" + index + "]");
    if (modelSelect && modelSelect.selectedIndex > 0) {
        var selectedOption = modelSelect.options[modelSelect.selectedIndex];
        var price = selectedOption.getAttribute('data-price') || '';
        var des = selectedOption.getAttribute('data-des') || '';
        
        var priceField = document.getElementById("price[" + index + "]");
        var desField = document.getElementById("des[" + index + "]");
        
        if (priceField && price) priceField.value = price;
        if (desField && des) desField.value = des;
    }
}

// Remove a product item
function removeProductItem(index) {
    var item = document.getElementById('product-item-' + index);
    if (item) {
        item.remove();
        renumberProducts();
    }
}

// Renumber products after removal
function renumberProducts() {
    var items = document.querySelectorAll('.product-item');
    items.forEach(function(item, idx) {
        var numSpan = item.querySelector('.product-item-number');
        if (numSpan) numSpan.textContent = idx + 1;
    });
}

// Add new product row
document.getElementById('addRow').addEventListener('click', function() {
    var index = productCount;
    productCount++;
    document.getElementById('countloop').value = productCount;
    
    var productOptions = '';
    <?php foreach($allProducts as $prod): ?>
    productOptions += '<option value="<?=$prod['id']?>"><?=addslashes(htmlspecialchars($prod['name']))?></option>';
    <?php endforeach; ?>
    
    var html = `
    <div class="product-item" id="product-item-${index}">
        <div class="product-item-header">
            <div class="product-item-title">
                <span class="product-item-number">${document.querySelectorAll('.product-item').length + 1}</span>
                <span class="product-item-name" id="product-name-${index}"><?=$xml->product ?? 'Product'?> #${index + 1}</span>
            </div>
            <button type="button" class="btn-remove-item" onclick="removeProductItem(${index})">
                <i class="fa fa-times"></i>
            </button>
        </div>
        
        <div class="product-item-grid">
            <div class="product-row-main">
                <div class="form-group">
                    <label><?=$xml->product ?? 'Product'?></label>
                    <select name="type[${index}]" id="type[${index}]" class="form-control" required onchange="checkorder(this.value,this.id)">
                        <option value="">-- <?=$xml->selectproduct ?? 'Select Product'?> --</option>
                        ${productOptions}
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?=$xml->model ?? 'Model'?></label>
                    <select name="model[${index}]" id="model[${index}]" class="form-control" required onchange="checkorder3(this.value,this.id)">
                        <option value="">-- <?=$xml->selectmodel ?? 'Select Model'?> --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?=$xml->quantity ?? 'Qty'?></label>
                    <input type="number" name="quantity[${index}]" id="quantity[${index}]" class="form-control" required value="1" min="1">
                </div>
                
                <div class="form-group">
                    <label><?=$xml->price ?? 'Price'?></label>
                    <div class="input-with-addon">
                        <input type="text" name="price[${index}]" id="price[${index}]" class="form-control" required value="">
                        <span class="addon"><?=$xml->baht ?? '฿'?></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?=$xml->labour ?? 'Labour'?></label>
                    <div class="labour-group">
                        <input type="checkbox" name="a_labour[${index}]" id="a_labour[${index}]" value="1" class="labour-checkbox">
                        <input type="text" name="v_labour[${index}]" id="v_labour[${index}]" class="form-control" placeholder="0">
                    </div>
                </div>
            </div>
            
            <div class="product-row-notes">
                <div class="form-group">
                    <label><?=$xml->notes ?? 'Notes'?></label>
                    <textarea name="des[${index}]" id="des[${index}]" class="form-control" placeholder="<?=$xml->addnotes ?? 'Add notes...'?>"></textarea>
                </div>
            </div>
        </div>
        
        <input type="hidden" name="ban_id[${index}]" id="ban_id[${index}]" value="0">
        <input type="hidden" name="pack_quantity[${index}]" value="1">
    </div>
    `;
    
    document.getElementById('productsList').insertAdjacentHTML('beforeend', html);
});
</script>

<?php else: ?>
<div style="font-family: 'Inter', sans-serif; max-width: 600px; margin: 60px auto; text-align: center; padding: 40px;">
    <div style="background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b; margin-bottom: 16px;"></i>
        <h3 style="color: #374151; margin-bottom: 8px;">Quotation Not Found</h3>
        <p style="color: #6b7280; margin-bottom: 24px;">The quotation you're looking for doesn't exist or you don't have access to it.</p>
        <a href="index.php?page=qa_list" style="background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;">
            <i class="fa fa-arrow-left"></i> Back to Quotation List
        </a>
    </div>
</div>
<?php endif; ?>
