<?php
// Security already checked in index.php

$_date = explode("-", date("d-m-Y"));
$day = $_date[0];
$month = $_date[1];
$year = $_date[2];

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);
$modep = sql_escape($_REQUEST['modep'] ?? '');

if($modep=="ad"){
    $query=mysqli_query($db->conn, "SELECT sendoutitem.id as id, sendoutitem.tmp as des, ven_id, cus_id, name_sh, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date FROM sendoutitem JOIN deliver ON sendoutitem.id=deliver.out_id JOIN company ON sendoutitem.cus_id=company.id WHERE deliver.id='".$id."' AND (cus_id='".$com_id."' OR ven_id='".$com_id."') AND deliver.id NOT IN (SELECT deliver_id FROM receive)");
} else {
    $query=mysqli_query($db->conn, "SELECT po.name as name, po.id as id, po.tax as tax, ven_id, cus_id, des, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, ref, pic, status FROM pr JOIN po ON pr.id=po.ref JOIN deliver ON po.id=deliver.po_id WHERE deliver.id='".$id."' AND status='3' AND (cus_id='".$com_id."' OR ven_id='".$com_id."') AND po_id_new=''");
}

$hasData = mysqli_num_rows($query) == 1;
if($hasData){
    $data = mysqli_fetch_array($query);
    $vender = mysqli_fetch_array(mysqli_query($db->conn, "SELECT name_sh, name_en FROM company WHERE id='".$data['ven_id']."'"));
    $customer = mysqli_fetch_array(mysqli_query($db->conn, "SELECT name_sh, name_en FROM company WHERE id='".$data['cus_id']."'"));
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .view-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
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
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .ref-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .btn-back {
        background: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-back:hover {
        background: rgba(255,255,255,0.3);
        text-decoration: none;
        color: white;
    }
    
    .btn-print {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        margin-left: 10px;
    }
    
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        text-decoration: none;
        color: white;
    }
    
    .info-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .info-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
        padding: 20px;
    }
    
    .info-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-card-header .icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    
    .info-card-header .icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .info-card-header .icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .info-card-header .icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
    
    .info-card-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #374151;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f9fafb;
    }
    
    .info-row:last-child { border-bottom: none; }
    
    .info-row .label {
        color: #6b7280;
        font-size: 13px;
    }
    
    .info-row .value {
        color: #1f2937;
        font-weight: 500;
        font-size: 14px;
        text-align: right;
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
    
    .products-header h3 i { color: #10b981; }
    
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
    
    .products-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
        vertical-align: middle;
    }
    
    .products-table tbody tr:hover {
        background: rgba(16, 185, 129, 0.02);
    }
    
    .product-model {
        font-size: 12px;
        color: #10b981;
        background: rgba(16, 185, 129, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
    }
    
    .action-section {
        display: flex;
        gap: 16px;
        justify-content: flex-end;
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-receive {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    
    .btn-receive:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
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
        background: #fef2f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 36px;
        color: #ef4444;
    }
    
    .error-card h3 {
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    .error-card p {
        color: #6b7280;
    }
</style>

<div class="view-wrapper">
<?php if($hasData): ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2>
                <i class="fa fa-truck"></i>
                <?=$xml->deliverynote ?? 'Delivery Note'?>
            </h2>
            <div class="subtitle">
                <span class="ref-badge">DN-<?=str_pad($id, 7, "0", STR_PAD_LEFT)?></span>
            </div>
        </div>
        <div>
            <a href="index.php?page=deliv_list" class="btn-back">
                <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?>
            </a>
            <a href="rec.php?id=<?=$id?><?=$modep ? '&modep='.$modep : ''?>" target="_blank" class="btn-print">
                <i class="fa fa-print"></i> <?=$xml->print ?? 'Print PDF'?>
            </a>
        </div>
    </div>
    
    <!-- Info Cards -->
    <div class="info-cards">
        <!-- Order Info -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon green"><i class="fa fa-file-text-o"></i></div>
                <h3><?=$xml->information ?? 'Order Information'?></h3>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->name ?? 'Name'?></span>
                <span class="value"><?=htmlspecialchars($data['name'] ?? $data['des'] ?? '')?></span>
            </div>
            <?php if($modep!="ad"): ?>
            <div class="info-row">
                <span class="label"><?=$xml->validpay ?? 'Valid Pay'?></span>
                <span class="value"><?=htmlspecialchars($data['valid_pay'] ?? '')?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label"><?=$xml->deliverydate ?? 'Delivery Date'?></span>
                <span class="value"><?=htmlspecialchars($data['deliver_date'] ?? '')?></span>
            </div>
        </div>
        
        <!-- Parties -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon blue"><i class="fa fa-building"></i></div>
                <h3><?=$xml->parties ?? 'Parties'?></h3>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->vender ?? 'Vendor'?></span>
                <span class="value"><?=htmlspecialchars($vender['name_en'] ?: $vender['name_sh'])?></span>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->customer ?? 'Customer'?></span>
                <span class="value"><?=htmlspecialchars($customer['name_en'] ?: $customer['name_sh'])?></span>
            </div>
        </div>
        
        <!-- Description -->
        <?php if(!empty($data['des'])): ?>
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon purple"><i class="fa fa-align-left"></i></div>
                <h3><?=$xml->description ?? 'Description'?></h3>
            </div>
            <p style="color:#374151; font-size:14px; line-height:1.6; margin:0;">
                <?=nl2br(htmlspecialchars($data['des']))?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Products Table -->
    <?php 
    if($modep=="ad"){
        $que_pro=mysqli_query($db->conn, "SELECT type.name as name, product.price as price, discount, model.model_name as model, s_n, DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty FROM product JOIN store ON product.pro_id=store.pro_id JOIN type ON product.type=type.id JOIN model ON product.model=model.id JOIN store_sale ON store.id=store_sale.st_id WHERE so_id='".$data['id']."'");
    } else {
        $que_pro=mysqli_query($db->conn, "SELECT type.name as name, product.price as price, discount, model.model_name as model, s_n, DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty FROM product JOIN store ON product.pro_id=store.pro_id JOIN type ON product.type=type.id JOIN model ON product.model=model.id JOIN store_sale ON store.id=store_sale.st_id WHERE po_id='".$data['id']."'");
    }
    ?>
    
    <div class="products-card">
        <div class="products-header">
            <h3><i class="fa fa-barcode"></i> <?=$xml->product ?? 'Products'?></h3>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:25%"><?=$xml->name ?? 'Name'?></th>
                    <th style="width:20%"><?=$xml->model ?? 'Model'?></th>
                    <th style="width:25%"><?=$xml->sn ?? 'Serial Number'?></th>
                    <th style="width:25%"><?=$xml->warranty ?? 'Warranty'?></th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $row_num = 0;
            while($data_pro = mysqli_fetch_array($que_pro)):
                $row_num++;
            ?>
                <tr>
                    <td style="text-align:center; color:#6b7280;"><?=$row_num?></td>
                    <td><?=htmlspecialchars($data_pro['name'])?></td>
                    <td><span class="product-model"><?=htmlspecialchars($data_pro['model'])?></span></td>
                    <td><?=htmlspecialchars($data_pro['s_n'])?></td>
                    <td><?=htmlspecialchars($data_pro['warranty'] ?? '-')?></td>
                </tr>
            <?php endwhile; ?>
            <?php if($row_num == 0): ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280; padding:40px;">
                        <i class="fa fa-inbox" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                        No products found
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        
        <form action="core-function.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="method" value="<?=$modep=="ad" ? "R2" : "R"?>">
            <input type="hidden" name="ref" value="<?=htmlspecialchars($data['ref'] ?? '')?>">
            <input type="hidden" name="po_id" value="<?=htmlspecialchars($data['id'] ?? '')?>">
            <input type="hidden" name="deliv_id" value="<?=htmlspecialchars($id)?>">
            <input type="hidden" name="page" value="deliv_list">
            
            <div class="action-section">
                <button type="submit" class="btn-receive">
                    <i class="fa fa-check"></i> <?=$xml->recieve ?? 'Confirm Receipt'?>
                </button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- Error State -->
    <div class="error-card">
        <div class="error-icon">
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <h3><?=$xml->error ?? 'Error'?></h3>
        <p><?=$xml->deliverynotfound ?? 'Delivery note not found or access denied.'?></p>
        <br>
        <a href="index.php?page=deliv_list" class="btn-back" style="background:#667eea; display:inline-flex;">
            <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to List'?>
        </a>
    </div>

<?php endif; ?>

</div>
