<?php
// Error reporting settings
ini_set('display_errors', 1); // Show errors in browser for debug
ini_set('log_errors', 1);     // Enable error logging
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log'); // Log file path
error_reporting(E_ALL);       // Report all errors
/**
 * Voucher View Page
 * Read-only professional view of voucher details
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/payment-method-helper.php");
$db = new DbConn($config);

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);

// Fetch voucher data
$query = mysqli_query($db->conn, "SELECT * FROM voucher WHERE id='".$id."' AND vender='".$com_id."'");
if(mysqli_num_rows($query) != 1) {
    echo '<div class="alert alert-danger" style="margin:20px;">Voucher not found or access denied.</div>';
    exit;
}
$data = mysqli_fetch_assoc($query);

// Fetch products
$products_query = mysqli_query($db->conn, "
    SELECT p.*, t.name as type_name, b.brand_name, m.model_name 
    FROM product p 
    LEFT JOIN type t ON p.type = t.id 
    LEFT JOIN brand b ON p.ban_id = b.id 
    LEFT JOIN model m ON p.model = m.id 
    WHERE p.vo_id = '".$id."'
");

// Calculate totals
$subtotal = 0;
$products = [];
while($prod = mysqli_fetch_assoc($products_query)) {
    $line_total = $prod['quantity'] * $prod['price'];
    $subtotal += $line_total;
    $prod['line_total'] = $line_total;
    $products[] = $prod;
}

$discount_amount = $subtotal * ($data['discount'] / 100);
$after_discount = $subtotal - $discount_amount;
$vat_amount = $after_discount * ($data['vat'] / 100);
$grand_total = $after_discount + $vat_amount;

// Get payment method display name
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$payment_display = getPaymentMethodDisplayName($db->conn, $data['payment_method'], $lang);

// Status classes
$status_classes = [
    'draft' => ['class' => 'warning', 'icon' => 'glyphicon-edit'],
    'confirmed' => ['class' => 'success', 'icon' => 'glyphicon-ok-circle'],
    'cancelled' => ['class' => 'danger', 'icon' => 'glyphicon-remove-circle']
];
$status_info = $status_classes[$data['status']] ?? $status_classes['confirmed'];
?>
<!DOCTYPE html>
<html>
<head>
<style>
/* Voucher View Styling */
.voucher-view-container { max-width: 1000px; margin: 0 auto; padding: 20px; }

.page-header-vou { 
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); 
    color: #fff; 
    padding: 25px 30px; 
    border-radius: 10px; 
    margin-bottom: 25px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    box-shadow: 0 4px 15px rgba(231,76,60,0.3);
}
.page-header-vou h2 { margin: 0; font-size: 26px; font-weight: 600; }
.page-header-vou .voucher-number { font-size: 14px; opacity: 0.9; margin-top: 5px; }
.header-actions { display: flex; gap: 10px; }
.header-actions .btn { padding: 10px 20px; border-radius: 6px; font-weight: 500; text-decoration: none; transition: all 0.3s; }
.btn-back { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; }
.btn-back:hover { background: rgba(255,255,255,0.3); color: #fff; }
.btn-edit { background: #f39c12; border: none; color: #fff; }
.btn-edit:hover { background: #e67e22; color: #fff; }
.btn-pdf { background: #3498db; border: none; color: #fff; }
.btn-pdf:hover { background: #2980b9; color: #fff; }

/* Info Cards */
.info-row { display: flex; gap: 20px; margin-bottom: 20px; }
.info-card { flex: 1; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
.info-card .card-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: 600; color: #333; }
.info-card .card-header i { margin-right: 8px; color: #e74c3c; }
.info-card .card-body { padding: 20px; }

.info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
.info-item { }
.info-item label { display: block; font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 4px; font-weight: 600; letter-spacing: 0.5px; }
.info-item .value { font-size: 15px; color: #333; font-weight: 500; }
.info-item .value a { color: #3498db; }

.status-badge { 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    padding: 6px 14px; 
    border-radius: 20px; 
    font-size: 13px; 
    font-weight: 600; 
}
.status-badge.warning { background: #fff3cd; color: #856404; }
.status-badge.success { background: #d4edda; color: #155724; }
.status-badge.danger { background: #f8d7da; color: #721c24; }

/* Products Table */
.products-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 20px; }
.products-card .card-header { background: #e74c3c; color: #fff; padding: 15px 20px; font-weight: 600; }
.products-card .card-header i { margin-right: 8px; }

.products-table { width: 100%; border-collapse: collapse; }
.products-table th { background: #f8f9fa; padding: 12px 15px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; border-bottom: 2px solid #eee; }
.products-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
.products-table tr:last-child td { border-bottom: none; }
.products-table tr:hover { background: #fafafa; }
.products-table .text-right { text-align: right; }
.products-table .product-name { font-weight: 600; color: #333; }
.products-table .product-meta { font-size: 12px; color: #888; margin-top: 3px; }

/* Summary Section */
.summary-section { display: flex; justify-content: flex-end; }
.summary-box { background: #f8f9fa; border-radius: 10px; padding: 20px 25px; min-width: 300px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.summary-row.subtotal { border-bottom: 1px dashed #ddd; padding-bottom: 12px; margin-bottom: 8px; }
.summary-row.total { border-top: 2px solid #e74c3c; padding-top: 12px; margin-top: 8px; font-size: 18px; font-weight: 700; color: #e74c3c; }
.summary-row .label { color: #666; }
.summary-row .value { font-weight: 600; color: #333; }

/* Notes Section */
.notes-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-top: 20px; }
.notes-card h4 { margin: 0 0 10px 0; font-size: 14px; color: #888; text-transform: uppercase; }
.notes-card p { margin: 0; color: #333; line-height: 1.6; }

/* Timeline */
.timeline-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; }
.timeline-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; }
.timeline-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.timeline-icon.created { background: #e3f2fd; color: #1976d2; }
.timeline-icon.updated { background: #fff3e0; color: #f57c00; }
.timeline-info .title { font-weight: 600; color: #333; font-size: 13px; }
.timeline-info .date { font-size: 12px; color: #888; }

@media (max-width: 768px) {
    .info-row { flex-direction: column; }
    .info-grid { grid-template-columns: 1fr; }
    .header-actions { flex-wrap: wrap; }
}
</style>
</head>
<body>

<div class="voucher-view-container">

<!-- Page Header -->
<div class="page-header-vou">
    <div>
        <h2><i class="glyphicon glyphicon-tags"></i> <?=$xml->voucher ?? 'Voucher'?></h2>
        <div class="voucher-number">VOC-<?=htmlspecialchars($data['vou_rw'])?></div>
    </div>
    <div class="header-actions">
        <a href="index.php?page=voucher_list" class="btn btn-back"><i class="glyphicon glyphicon-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        <a href="index.php?page=voc_make&id=<?=$id?>" class="btn btn-edit"><i class="glyphicon glyphicon-pencil"></i> <?=$xml->edit ?? 'Edit'?></a>
        <a href="index.php?page=vou_print&id=<?=$id?>" target="_blank" class="btn btn-pdf"><i class="glyphicon glyphicon-print"></i> <?=$xml->pdf ?? 'PDF'?></a>
    </div>
</div>

<!-- Vendor Info & Voucher Details -->
<div class="info-row">
    <div class="info-card">
        <div class="card-header"><i class="glyphicon glyphicon-user"></i> <?=$xml->vendorinfo ?? 'Vendor / Payee'?></div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label><?=$xml->name ?? 'Name'?></label>
                    <div class="value"><?=htmlspecialchars($data['name'])?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->email ?? 'Email'?></label>
                    <div class="value"><?=$data['email'] ? '<a href="mailto:'.$data['email'].'">'.$data['email'].'</a>' : '-'?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->phone ?? 'Phone'?></label>
                    <div class="value"><?=$data['phone'] ?: '-'?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->status ?? 'Status'?></label>
                    <div class="value">
                        <span class="status-badge <?=$status_info['class']?>">
                            <i class="glyphicon <?=$status_info['icon']?>"></i>
                            <?=ucfirst($data['status'])?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="info-card">
        <div class="card-header"><i class="glyphicon glyphicon-cog"></i> <?=$xml->voucherdetails ?? 'Voucher Details'?></div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label><?=$xml->vouchernumber ?? 'Voucher No.'?></label>
                    <div class="value" style="font-weight:700; color:#e74c3c;">VOC-<?=htmlspecialchars($data['vou_rw'])?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->createdate ?? 'Date'?></label>
                    <div class="value"><?=date('d M Y', strtotime($data['createdate']))?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->paymentmethod ?? 'Payment Method'?></label>
                    <div class="value"><?=htmlspecialchars($payment_display)?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->vat ?? 'VAT'?> / <?=$xml->discount ?? 'Discount'?></label>
                    <div class="value"><?=$data['vat']?>% / <?=$data['discount']?>%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="products-card">
    <div class="card-header"><i class="glyphicon glyphicon-shopping-cart"></i> <?=$xml->products ?? 'Products / Services'?></div>
    <table class="products-table">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th><?=$xml->product ?? 'Product'?></th>
                <th style="width:100px;" class="text-right"><?=$xml->quantity ?? 'Qty'?></th>
                <th style="width:120px;" class="text-right"><?=$xml->price ?? 'Price'?></th>
                <th style="width:130px;" class="text-right"><?=$xml->total ?? 'Total'?></th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach($products as $prod): ?>
            <tr>
                <td><?=$no++?></td>
                <td>
                    <div class="product-name"><?=htmlspecialchars($prod['type_name'] ?: 'Product')?></div>
                    <div class="product-meta">
                        <?php if($prod['brand_name']): ?>Brand: <?=htmlspecialchars($prod['brand_name'])?><?php endif; ?>
                        <?php if($prod['model_name']): ?> | Model: <?=htmlspecialchars($prod['model_name'])?><?php endif; ?>
                    </div>
                    <?php if($prod['des']): ?><div class="product-meta" style="color:#666; font-style:italic;"><?=htmlspecialchars($prod['des'])?></div><?php endif; ?>
                </td>
                <td class="text-right"><?=number_format($prod['quantity'])?></td>
                <td class="text-right"><?=number_format($prod['price'], 2)?></td>
                <td class="text-right" style="font-weight:600;"><?=number_format($prod['line_total'], 2)?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($products)): ?>
            <tr><td colspan="5" style="text-align:center; padding:30px; color:#888;">No products found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Summary -->
<div class="summary-section">
    <div class="summary-box">
        <div class="summary-row subtotal">
            <span class="label"><?=$xml->subtotal ?? 'Subtotal'?></span>
            <span class="value"><?=number_format($subtotal, 2)?> <?=$xml->baht ?? 'THB'?></span>
        </div>
        <?php if($data['discount'] > 0): ?>
        <div class="summary-row">
            <span class="label"><?=$xml->discount ?? 'Discount'?> (<?=$data['discount']?>%)</span>
            <span class="value">-<?=number_format($discount_amount, 2)?></span>
        </div>
        <?php endif; ?>
        <div class="summary-row">
            <span class="label"><?=$xml->vat ?? 'VAT'?> (<?=$data['vat']?>%)</span>
            <span class="value"><?=number_format($vat_amount, 2)?></span>
        </div>
        <div class="summary-row total">
            <span class="label"><?=$xml->grandtotal ?? 'Grand Total'?></span>
            <span class="value"><?=number_format($grand_total, 2)?> <?=$xml->baht ?? 'THB'?></span>
        </div>
    </div>
</div>

<?php if($data['description']): ?>
<!-- Notes -->
<div class="notes-card">
    <h4><i class="glyphicon glyphicon-comment"></i> <?=$xml->notes ?? 'Notes'?></h4>
    <p><?=nl2br(htmlspecialchars($data['description']))?></p>
</div>
<?php endif; ?>

</div><!-- /voucher-view-container -->

</body>
</html>
