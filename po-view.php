<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
// Security already checked in index.php

$_date = explode("-", date("d-m-Y"));
$day = $_date[0];
$month = $_date[1];
$year = $_date[2];

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);
$query=mysqli_query($db->conn, "select po.id as po_id, po.name as name, po.tax as tax, ven_id, cus_id, vat, des, over, dis, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, ref, pic, po_ref, status from pr join po on pr.id=po.ref where po.id='".$id."' and (status='1' or status='2') and (cus_id='".$com_id."' or ven_id='".$com_id."') and po_id_new=''");
?>
<!DOCTYPE html>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .po-view-wrapper {
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
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .page-header-left .quo-number {
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
    
    .info-card-header .icon.purple { background: rgba(102, 126, 234, 0.1); color: #667eea; }
    .info-card-header .icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .info-card-header .icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
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
    
    .description-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .description-card h3 {
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .description-card h3 i {
        color: #667eea;
    }
    
    .description-content {
        background: #f9fafb;
        border-radius: 12px;
        padding: 16px;
        color: #4b5563;
        font-size: 14px;
        line-height: 1.6;
        min-height: 80px;
        white-space: pre-wrap;
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
        color: #667eea;
    }
    
    .products-header .item-count {
        background: #667eea;
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
    .products-table thead th.text-right { text-align: right; }
    
    .products-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
    }
    
    .products-table tbody tr:hover {
        background: rgba(102, 126, 234, 0.02);
    }
    
    .products-table tbody td.text-center { text-align: center; }
    .products-table tbody td.text-right { text-align: right; font-family: 'SF Mono', 'Consolas', monospace; }
    
    .products-table tbody td.model-cell {
        font-weight: 600;
        color: #667eea;
    }
    
    .summary-section {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 24px;
        border-top: 2px solid #e5e7eb;
    }
    
    .summary-grid {
        max-width: 400px;
        margin-left: auto;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed #d1d5db;
    }
    
    .summary-row:last-child {
        border-bottom: none;
    }
    
    .summary-row .label {
        font-size: 14px;
        color: #6b7280;
    }
    
    .summary-row .value {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
        font-family: 'SF Mono', 'Consolas', monospace;
    }
    
    .summary-row.discount .value { color: #ef4444; }
    .summary-row.add .value { color: #10b981; }
    
    .summary-row.grand-total {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 16px -24px -24px -24px;
        padding: 20px 24px;
        border-radius: 0 0 14px 14px;
    }
    
    .summary-row.grand-total .label,
    .summary-row.grand-total .value {
        color: white;
        font-size: 18px;
        font-weight: 700;
    }
    
    .action-section {
        display: flex;
        gap: 16px;
        justify-content: flex-end;
        margin-top: 24px;
    }
    
    .btn-confirm {
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
    
    .btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .upload-section {
        margin-top: 20px;
    }
    
    .upload-box {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        background: #f9fafb;
        transition: all 0.2s;
    }
    
    .upload-box:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.02);
    }
    
    .upload-box input[type="file"] {
        display: none;
    }
    
    .upload-box label {
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    
    .upload-box .upload-icon {
        width: 48px;
        height: 48px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        font-size: 20px;
    }
    
    .upload-box .upload-text {
        font-size: 14px;
        color: #6b7280;
    }
    
    .upload-box .upload-text strong {
        color: #667eea;
    }
    
    .view-file-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        padding: 12px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .view-file-btn:hover {
        background: #667eea;
        color: white;
        text-decoration: none;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.confirmed { background: #d1fae5; color: #10b981; }
    
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
        
        .summary-grid {
            max-width: 100%;
        }
    }
</style>
</head>
<body>

<div class="po-view-wrapper">
<?php
if(mysqli_num_rows($query)=="1"){
    $data=mysqli_fetch_array($query);
    
    // Fetch vendor info - use LEFT JOIN and get the current/latest valid address (same as exp.php)
    $vender = mysqli_fetch_array(mysqli_query($db->conn, "
        SELECT company.name_en, company.name_sh, company_addr.adr_tax, company_addr.city_tax, 
               company_addr.district_tax, company_addr.province_tax, company.tax, company_addr.zip_tax, 
               company.fax, company.phone, company.email, company.logo, company.term 
        FROM company 
        LEFT JOIN company_addr ON company.id = company_addr.com_id 
            AND company_addr.deleted_at IS NULL
        WHERE company.id = '" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "'
        ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC, company_addr.valid_start DESC
        LIMIT 1
    "));

    // Fetch customer info - use LEFT JOIN and get the current/latest valid address (same as exp.php)
    $customer = mysqli_fetch_array(mysqli_query($db->conn, "
        SELECT company.name_en, company.name_sh, company_addr.adr_tax, company_addr.city_tax, 
               company_addr.district_tax, company_addr.province_tax, company.tax, company_addr.zip_tax, 
               company.fax, company.phone, company.email 
        FROM company 
        LEFT JOIN company_addr ON company.id = company_addr.com_id 
            AND company_addr.deleted_at IS NULL
        WHERE company.id = '" . mysqli_real_escape_string($db->conn, $data['cus_id']) . "'
        ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC, company_addr.valid_start DESC
        LIMIT 1
    "));
    
    // Get product count
    $product_count = mysqli_fetch_array(mysqli_query($db->conn, "select count(*) as cnt from product where po_id='".$id."'"));
    $item_count = $product_count['cnt'] ?? 0;
?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>
                <i class="fa fa-file-text-o"></i> 
                <?=$xml->quotation?>
                <span class="quo-number">QUO-<?=htmlspecialchars($data['tax'])?></span>
            </h2>
            <div class="subtitle">
                <?php if($data['status']=="1"): ?>
                    <span class="status-badge pending"><i class="fa fa-clock-o"></i>&nbsp; Pending Confirmation</span>
                <?php else: ?>
                    <span class="status-badge confirmed"><i class="fa fa-check"></i>&nbsp; Confirmed</span>
                <?php endif; ?>
            </div>
        </div>
        <a href="index.php?page=qa_list" class="btn-back">
            <i class="fa fa-arrow-left"></i> <?=$xml->back?>
        </a>
    </div>

    <form action="core-function.php" method="post" id="company-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    
    <!-- Info Cards -->
    <div class="info-cards">
        <!-- Quotation Info -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon purple"><i class="fa fa-info-circle"></i></div>
                <h3><?=$xml->quotation?> <?=$xml->information ?? 'Information'?></h3>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->name?></span>
                <span class="value"><?=htmlspecialchars($data['name'])?></span>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->validpay?></span>
                <span class="value"><?=htmlspecialchars($data['valid_pay'])?></span>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->deliverydate?></span>
                <span class="value"><?=htmlspecialchars($data['deliver_date'])?></span>
            </div>
        </div>
        
        <!-- Customer Info -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon blue"><i class="fa fa-user"></i></div>
                <h3><?=$xml->customer?></h3>
            </div>
            <div class="info-row">
                <span class="label"><?=$xml->nameen ?? 'Company'?></span>
                <span class="value"><?=htmlspecialchars($customer['name_en'] ?: $customer['name_sh'])?></span>
            </div>
            <?php if(!empty($customer['adr_tax'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->address ?? 'Address'?></span>
                <span class="value" style="text-align:right; max-width:200px;"><?=htmlspecialchars($customer['adr_tax'])?></span>
            </div>
            <?php endif; ?>
            <?php if(!empty($customer['district_tax']) || !empty($customer['city_tax']) || !empty($customer['province_tax'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->rcity ?? 'City/Province'?></span>
                <span class="value"><?=htmlspecialchars(trim($customer['district_tax'] . ' ' . $customer['city_tax'] . ' ' . $customer['province_tax']))?></span>
            </div>
            <?php endif; ?>
            <?php if(!empty($customer['zip_tax'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->rzip ?? 'Postal Code'?></span>
                <span class="value"><?=htmlspecialchars($customer['zip_tax'])?></span>
            </div>
            <?php endif; ?>
            <?php if(!empty($customer['tax'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->tax ?? 'Tax ID'?></span>
                <span class="value"><?=htmlspecialchars($customer['tax'])?></span>
            </div>
            <?php endif; ?>
            <?php if(!empty($customer['phone'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->phone ?? 'Phone'?></span>
                <span class="value"><?=htmlspecialchars($customer['phone'])?></span>
            </div>
            <?php endif; ?>
            <?php if(!empty($customer['email'])): ?>
            <div class="info-row">
                <span class="label"><?=$xml->email ?? 'Email'?></span>
                <span class="value"><?=htmlspecialchars($customer['email'])?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label"><?=$xml->poref ?? 'PO Reference'?></span>
                <?php if($data['status']=="1"): ?>
                <input type="text" name="po_ref" class="form-control" style="width:150px; height:28px; font-size:13px;" value="<?=htmlspecialchars($data['po_ref'] ?? '')?>" placeholder="Customer PO #">
                <?php else: ?>
                <span class="value"><?=htmlspecialchars($data['po_ref'] ?? '-')?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upload/View File -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="icon orange"><i class="fa fa-file-pdf-o"></i></div>
                <h3><?=$xml->uploadpo ?? 'Upload PO'?></h3>
            </div>
            <?php if($data['status']=="2" && !empty($data['pic'])): ?>
                <a href="upload/<?=htmlspecialchars($data['pic'])?>" target="_blank" class="view-file-btn">
                    <i class="fa fa-eye"></i> View Quotation File
                </a>
            <?php else: ?>
                <div class="upload-box">
                    <label for="file-upload">
                        <div class="upload-icon"><i class="fa fa-cloud-upload"></i></div>
                        <div class="upload-text"><strong>Click to upload</strong> or drag and drop</div>
                        <div class="upload-text" style="font-size:12px;">PDF, DOC, XLS (max 10MB)</div>
                    </label>
                    <input type="file" name="file" id="file-upload" onchange="showFileName(this)">
                    <div id="file-name-display" style="display:none; margin-top:10px; padding:8px 12px; background:#e8f5e9; border-radius:6px; color:#2e7d32; font-size:13px;">
                        <i class="fa fa-file-o"></i> <span id="selected-file-name"></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="products-card">
        <div class="products-header">
            <h3><i class="fa fa-cubes"></i> <?=$xml->product ?? 'Products'?></h3>
            <span class="item-count"><?=$item_count?> <?=$item_count == 1 ? 'item' : 'items'?></span>
        </div>
        
        <?php 
        $cklabour=mysqli_fetch_array(mysqli_query($db->conn, "select max(activelabour) as cklabour from product join type on product.type=type.id where po_id='".$id."'"));
        $hasLabour = ($cklabour['cklabour']==1);
        ?>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width:15%"><?=$xml->model?></th>
                    <th <?=$hasLabour ? '' : 'colspan="4"'?>><?=$xml->description ?? 'Description'?></th>
                    <th class="text-center" style="width:8%"><?=$xml->unit?></th>
                    <th class="text-right" style="width:10%"><?=$xml->price?></th>
                    <?php if($hasLabour): ?>
                    <th class="text-right" style="width:10%"><?=$xml->total?></th>
                    <th class="text-right" style="width:10%"><?=$xml->labour?></th>
                    <th class="text-right" style="width:10%"><?=$xml->total?></th>
                    <?php endif; ?>
                    <th class="text-right" style="width:12%"><?=$xml->amount?></th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $que_pro=mysqli_query($db->conn, "select type.name as name, product.price as price, product.des as product_des, discount, model.model_name as model, quantity, pack_quantity, activelabour, valuelabour from product join type on product.type=type.id join model on product.model=model.id where po_id='".$id."'");
            $summary=0;
            
            while($data_pro=mysqli_fetch_array($que_pro)){
                // Use product description (product.des) - same as exp.php
                $product_desc = !empty($data_pro['product_des']) ? $data_pro['product_des'] : $data_pro['name'];
                
                if($hasLabour){
                    $equip=$data_pro['price']*$data_pro['quantity'];
                    $labour1=$data_pro['valuelabour']*$data_pro['activelabour'];
                    $labour=$labour1*$data_pro['quantity'];
                    $total=$equip+$labour;
                    $summary+=$total;
                    echo "<tr>
                        <td class='model-cell'>".htmlspecialchars($data_pro['model'])."</td>
                        <td>".safe_html($product_desc)."</td>
                        <td class='text-center'>".$data_pro['quantity']."</td>
                        <td class='text-right'>".number_format($data_pro['price'],2)."</td>
                        <td class='text-right'>".number_format($equip,2)."</td>
                        <td class='text-right'>".number_format($labour1,2)."</td>
                        <td class='text-right'>".number_format($labour,2)."</td>
                        <td class='text-right'>".number_format($total,2)."</td>
                    </tr>";
                } else {
                    $total=$data_pro['price']*$data_pro['quantity'];
                    $summary+=$total;
                    echo "<tr>
                        <td class='model-cell'>".htmlspecialchars($data_pro['model'])."</td>
                        <td colspan='4'>".safe_html($product_desc)."</td>
                        <td class='text-center'>".$data_pro['quantity']."</td>
                        <td class='text-right'>".number_format($data_pro['price'],2)."</td>
                        <td class='text-right'>".number_format($total,2)."</td>
                    </tr>";
                }
            }
            ?>
            </tbody>
        </table>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-grid">
                <?php
                $disc=$summary*$data['dis']/100;
                $subt=$summary-$disc;
                ?>
                <div class="summary-row">
                    <span class="label"><?=$xml->total?></span>
                    <span class="value"><?=number_format($summary,2)?></span>
                </div>
                <?php if($data['dis'] > 0): ?>
                <div class="summary-row discount">
                    <span class="label"><?=$xml->discount?> (<?=$data['dis']?>%)</span>
                    <span class="value">- <?=number_format($disc,2)?></span>
                </div>
                <div class="summary-row">
                    <span class="label"><?=$xml->subtotal?></span>
                    <span class="value"><?=number_format($subt,2)?></span>
                </div>
                <?php endif; ?>
                
                <?php if($data['over']>0):
                    $overh= $subt*$data['over']/100;
                    $subt=$subt+$overh;
                ?>
                <div class="summary-row add">
                    <span class="label"><?=$xml->overhead?> (<?=$data['over']?>%)</span>
                    <span class="value">+ <?=number_format($overh,2)?></span>
                </div>
                <div class="summary-row">
                    <span class="label"><?=$xml->total?></span>
                    <span class="value"><?=number_format($subt,2)?></span>
                </div>
                <?php endif; ?>
                
                <?php
                $vat=$subt*$data['vat']/100;
                $totalnet=$subt+$vat;
                ?>
                <?php if($data['vat'] > 0): ?>
                <div class="summary-row add">
                    <span class="label"><?=$xml->vat?> (<?=$data['vat']?>%)</span>
                    <span class="value">+ <?=number_format($vat,2)?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row grand-total">
                    <span class="label"><?=$xml->grandtotal?></span>
                    <span class="value"><?=number_format($totalnet,2)?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bank Account / Payment Methods Section -->
    <?php
    // Fetch payment methods for vendor - order by updated_at DESC to get latest updated first
    $paymentMethods = [];
    $pm_query = mysqli_query($db->conn, "
        SELECT method_type, method_name, account_name, account_number, branch, qr_image 
        FROM payment_methods 
        WHERE com_id = '" . mysqli_real_escape_string($db->conn, $data['ven_id']) . "' 
        AND is_active = 1 
        ORDER BY updated_at DESC, created_at DESC
    ");
    if ($pm_query) {
        while ($pm = mysqli_fetch_array($pm_query)) {
            $paymentMethods[] = $pm;
        }
    }
    ?>
    <?php if(!empty($paymentMethods)): ?>
    <div class="products-card" style="margin-top: 24px;">
        <div class="products-header">
            <h3><i class="fa fa-bank"></i> <?=$xml->bankaccount ?? 'Bank Account'?></h3>
            <span class="item-count"><?=count($paymentMethods)?> <?=count($paymentMethods) == 1 ? 'account' : 'accounts'?></span>
        </div>
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <?php foreach($paymentMethods as $pm): ?>
                <div style="background: #f9fafb; border-radius: 12px; padding: 16px; border: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fa fa-<?=$pm['method_type'] == 'bank' ? 'bank' : ($pm['method_type'] == 'promptpay' ? 'mobile' : 'credit-card')?>"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #374151; font-size: 14px;"><?=htmlspecialchars($pm['method_name'])?></div>
                            <div style="font-size: 11px; color: #6b7280; text-transform: uppercase;"><?=htmlspecialchars($pm['method_type'])?></div>
                        </div>
                    </div>
                    <?php if(!empty($pm['account_name'])): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="color: #6b7280; font-size: 12px;"><?=$xml->accountname ?? 'Account Name'?></span>
                        <span style="color: #374151; font-size: 13px; font-weight: 500;"><?=htmlspecialchars($pm['account_name'])?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($pm['account_number'])): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="color: #6b7280; font-size: 12px;"><?=$xml->accountnumber ?? 'Account Number'?></span>
                        <span style="color: #374151; font-size: 13px; font-weight: 600; font-family: 'SF Mono', monospace;"><?=htmlspecialchars($pm['account_number'])?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($pm['branch'])): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                        <span style="color: #6b7280; font-size: 12px;"><?=$xml->branch ?? 'Branch'?></span>
                        <span style="color: #374151; font-size: 13px;"><?=htmlspecialchars($pm['branch'])?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($pm['qr_image'])): ?>
                    <div style="margin-top: 10px; text-align: center;">
                        <img src="upload/<?=htmlspecialchars($pm['qr_image'])?>" alt="QR Code" style="max-width: 120px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <input type="hidden" name="method" value="C">
    <input type="hidden" name="ref" value="<?=$data['ref']?>">
    <input type="hidden" name="page" value="po_list">
    
    <?php if($data['status']=="1"): ?>
    <div class="action-section">
        <button type="submit" class="btn-confirm">
            <i class="fa fa-check-circle"></i> <?=$xml->confirm?>
        </button>
    </div>
    <?php endif; ?>
    
    </form>

<?php 
} else { 
?>
    <div class="error-card">
        <div class="error-icon"><i class="fa fa-exclamation-triangle"></i></div>
        <h3>Quotation Not Found</h3>
        <p>The requested quotation does not exist or you don't have permission to view it.</p>
        <a href="index.php?page=qa_list" class="btn-back" style="margin-top:20px; background:#667eea; color:white;">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
    </div>
<?php } ?>
</div>

<script>
function showFileName(input) {
    var fileNameDisplay = document.getElementById('file-name-display');
    var selectedFileName = document.getElementById('selected-file-name');
    
    if (input.files && input.files[0]) {
        selectedFileName.textContent = input.files[0].name;
        fileNameDisplay.style.display = 'block';
    } else {
        fileNameDisplay.style.display = 'none';
    }
}
</script>

</body>
</html>