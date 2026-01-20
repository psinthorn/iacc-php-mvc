<?php
// Security already checked in index.php

$_date = explode("-", date("d-m-Y"));
$day = $_date[0];
$month = $_date[1];
$year = $_date[2];

// Default warranty expiry: current date + 1 year
$defaultExpiry = date("d-m-Y", strtotime("+1 year"));

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);
$action = $_GET['action'] ?? 'c';

$query=mysqli_query($db->conn, "select po.name as name,po.tax as tax,ven_id,cus_id,des,DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay,ref,pic,po_ref,status from pr join po on pr.id=po.ref where po.id='".$id."' and (status='1' or status='2') and ven_id='".$com_id."' and po_id_new=''");
$hasData = mysqli_num_rows($query) == 1;

if($hasData){
    $data=mysqli_fetch_array($query);
    $vender=mysqli_fetch_array(mysqli_query($db->conn, "select name_sh,name_en from company where id='".$data['ven_id']."'"));
    $customer=mysqli_fetch_array(mysqli_query($db->conn, "select name_sh,name_en from company where id='".$data['cus_id']."'"));
}
?>
<!DOCTYPE html>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .delivery-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(245, 158, 11, 0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header-left h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header-left .subtitle {
        opacity: 0.9;
        font-size: 14px;
        margin-top: 6px;
    }
    
    .page-header-left .ref-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    
    .btn-back {
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-back:hover {
        background: rgba(255,255,255,0.3);
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
    }
    
    .info-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
    }
    
    .info-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f3f4f6;
    }
    
    .info-card-header .icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    
    .info-card-header .icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .info-card-header .icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .info-card-header .icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    
    .info-card-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #374151;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-row .label {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .info-row .value {
        font-size: 14px;
        color: #1f2937;
        font-weight: 600;
        text-align: right;
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
        padding: 14px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
        min-height: 48px;
    }
    
    .form-group .form-control:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        outline: none;
    }
    
    .products-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .products-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .products-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .products-header h3 i {
        color: #f59e0b;
    }
    
    .products-header .item-count {
        background: #f59e0b;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .products-table thead th {
        background: #f9fafb;
        color: #374151;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 2px solid #e5e7eb;
        text-align: left;
    }
    
    .products-table thead th.text-center { text-align: center; }
    
    .products-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 13px;
        color: #374151;
        vertical-align: middle;
    }
    
    .products-table tbody tr:hover {
        background: rgba(245, 158, 11, 0.02);
    }
    
    .product-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .product-name {
        font-weight: 600;
        color: #1f2937;
    }
    
    .product-model {
        font-size: 12px;
        color: #f59e0b;
        background: rgba(245, 158, 11, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        width: fit-content;
    }
    
    .products-table tbody td .form-control {
        padding: 6px 8px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 12px;
        transition: all 0.2s;
        height: 32px;
        box-sizing: border-box;
        width: 100%;
    }
    
    .products-table tbody td .sn-input {
        max-width: 150px;
    }
    
    .products-table tbody td .exp-input {
        max-width: 110px;
    }
    
    .products-table tbody td .form-control:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        outline: none;
    }
    
    .action-section {
        display: flex;
        gap: 16px;
        justify-content: flex-end;
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    }
    
    .btn-save:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .error-card {
        background: white;
        border-radius: 16px;
        padding: 60px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
    }
    
    .error-card .error-icon {
        width: 80px;
        height: 80px;
        background: #fee2e2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: #ef4444;
        font-size: 36px;
    }
    
    .error-card h3 {
        margin: 0 0 10px;
        color: #1f2937;
        font-size: 20px;
    }
    
    .error-card p {
        color: #6b7280;
        margin: 0;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 16px;
            text-align: center;
        }
        
        .info-cards {
            grid-template-columns: 1fr;
        }
        
        .products-table {
            font-size: 12px;
        }
        
        .products-table thead th,
        .products-table tbody td {
            padding: 10px 8px;
        }
        
        .products-table tbody td .form-control {
            min-width: 120px;
        }
    }
</style>
</head>
<body>

<div class="delivery-wrapper">
<?php if($hasData): ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>
                <i class="fa fa-truck"></i>
                <?php if($action=="m") echo $xml->make." ".$xml->deliverynote; else echo $xml->create." ".$xml->deliverynote; ?>
            </h2>
            <div class="subtitle">
                <span class="ref-badge">PO-<?=htmlspecialchars($data['tax'] ?? $id)?></span>
            </div>
        </div>
        <a href="index.php?page=po_list" class="btn-back">
            <i class="fa fa-arrow-left"></i> <?=$xml->back?>
        </a>
    </div>

    <form action="core-function.php" method="post" id="deliver-form" name="deliver-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    
    <!-- Info Cards -->
    <div class="info-cards">
        <!-- Order Info -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon orange"><i class="fa fa-file-text-o"></i></div>
                <h3><?=$xml->information ?? 'Order Information'?></h3>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->name?></span>
                <span class="value"><?=htmlspecialchars($data['name'])?></span>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->validpay?></span>
                <input type="text" class="form-control date-input" name="valid_pay" value="<?=htmlspecialchars($data['valid_pay'])?>" placeholder="dd-mm-yyyy" style="max-width: 140px; height: 32px; padding: 6px 8px; font-size: 13px;">
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->deliverydate?></span>
                <input type="text" class="form-control date-input" name="deliver_date" value="<?=htmlspecialchars($data['deliver_date'])?>" placeholder="dd-mm-yyyy" style="max-width: 140px; height: 32px; padding: 6px 8px; font-size: 13px;">
            </div>
            <input type="hidden" name="name" value="<?=htmlspecialchars($data['name'])?>">
        </div>
        
        <!-- Parties -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon green"><i class="fa fa-building"></i></div>
                <h3><?=$xml->parties ?? 'Parties'?></h3>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->vender?></span>
                <span class="value"><?=htmlspecialchars($vender['name_en'] ?: $vender['name_sh'])?></span>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->customer?></span>
                <span class="value"><?=htmlspecialchars($customer['name_en'] ?: $customer['name_sh'])?></span>
            </div>
            <?php if(!empty($data['po_ref'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->poref ?? 'PO Reference'?></span>
                <span class="value"><?=htmlspecialchars($data['po_ref'])?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Products Table -->
    <?php 
    $que_pro=mysqli_query($db->conn, "select type.name as name,product.des as des,product.price as price,pro_id,discount,model.model_name as model,model.des as model_des,quantity,pack_quantity,type from product join type on product.type=type.id join model on product.model=model.id where po_id='".$id."' AND product.deleted_at IS NULL");
    $total_items = 0;
    $products = [];
    while($row = mysqli_fetch_array($que_pro)){
        $item_count = $row['quantity'] * $row['pack_quantity'];
        $total_items += $item_count;
        $products[] = $row;
    }
    ?>
    
    <div class="products-card">
        <div class="products-header">
            <h3><i class="fa fa-barcode"></i> <?=$xml->product ?? 'Products'?> - <?=$xml->sn ?? 'Serial Numbers'?></h3>
            <span class="item-count"><?=$total_items?> <?=$total_items == 1 ? 'item' : 'items'?></span>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:20%"><?=$xml->product ?? 'Product'?></th>
                    <th style="width:30%"><?=$xml->description ?? 'Description'?></th>
                    <th style="width:25%"><?=$xml->sn ?? 'Serial Number'?></th>
                    <th style="width:20%"><?=$xml->warranty ?? 'Warranty Expiry'?></th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $j = 0;
            foreach($products as $data_pro):
                $item = $data_pro['quantity'] * $data_pro['pack_quantity'];
                for($i=0; $i<$item; $i++):
                    $j++;
                    
                    // Get max serial number for auto-generation
                    $maxno = mysqli_fetch_array(mysqli_query($db->conn, "select max(no) as maxno from store join product on store.pro_id=product.pro_id where model in (select model from product where pro_id='".$data_pro['pro_id']."')"));
                    $suggestedSN = $data_pro['model']."-".($maxno['maxno']+$i+1);
            ?>
                <tr>
                    <td style="text-align:center; color:#6b7280; font-weight:500;"><?=$j?></td>
                    <td>
                        <span class="product-model" style="font-weight:500; color:#374151;"><?=htmlspecialchars($data_pro['model'])?></span>
                    </td>
                    <td style="color:#374151; font-size:13px;">
                        <?=safe_html($data_pro['des'] ?? '')?>
                    </td>
                    <td>
                        <?php if($action=="m"): ?>
                            <select required class='form-control' name='sn[<?=$j-1?>]'>
                                <option value=''>-- <?=$xml->selectitem ?? 'Select Item'?> --</option>
                                <?php
                                $query_store=mysqli_query($db->conn, "select store.id as st_id, type.name as name, s_n from store join product on store.pro_id=product.pro_id join store_sale on store.id=store_sale.st_id join type on product.type=type.id where own_id='".$com_id."' and type='".$data_pro['type']."' and sale='0'");
                                while($data_store=mysqli_fetch_array($query_store)){
                                    echo "<option value='".$data_store['st_id']."'>".htmlspecialchars($data_store['name'])." (".htmlspecialchars($data_store['s_n']).")</option>";
                                }
                                ?>
                            </select>
                        <?php else: ?>
                            <input class='form-control sn-input' name='sn[<?=$j-1?>]' value='<?=htmlspecialchars($suggestedSN)?>' type='text' placeholder='S/N'>
                        <?php endif; ?>
                        <input type='hidden' name='pro_id[<?=$j-1?>]' value='<?=$data_pro['pro_id']?>'>
                    </td>
                    <td>
                        <input class='form-control exp-input' name='exp[<?=$j-1?>]' type='text' value='<?=$defaultExpiry?>' placeholder='dd-mm-yyyy'>
                    </td>
                </tr>
            <?php 
                endfor;
            endforeach;
            ?>
            </tbody>
        </table>
        
        <?php if($data['status']=="2"): ?>
        <div class="action-section">
            <button type="submit" class="btn-save">
                <i class="fa fa-save"></i> <?=$xml->save ?? 'Save Delivery Note'?>
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <input type="hidden" name="method" value="<?=htmlspecialchars($action)?>">
    <input type="hidden" name="ref" value="<?=htmlspecialchars($data['ref'] ?? '')?>">
    <input type="hidden" name="page" value="deliv_list">
    <input type="hidden" name="po_id" value="<?=htmlspecialchars($id)?>">
    <input type="hidden" name="cus_id" value="<?=htmlspecialchars($data['cus_id'] ?? '')?>">
    </form>

<?php else: ?>

    <!-- Error State -->
    <div class="error-card">
        <div class="error-icon">
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <h3><?=$xml->error ?? 'Error'?></h3>
        <p><?=$xml->quotationnotfound ?? 'Order not found or access denied.'?></p>
        <br>
        <a href="index.php?page=po_list" class="btn-back" style="background:#667eea; display:inline-flex;">
            <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to List'?>
        </a>
    </div>

<?php endif; ?>

</div>

</body>
</html>
