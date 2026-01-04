<?php
/**
 * Receipt View Page
 * Read-only professional view of receipt details
 * Supports receipts from quotations, invoices, or manual entry
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/payment-method-helper.php");
$db = new DbConn($config);

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);

// Fetch receipt data with quotation join
$query = mysqli_query($db->conn, "SELECT r.*, c.inv_rw as invoice_no, p.tax as quotation_no 
    FROM receipt r 
    LEFT JOIN complain c ON r.invoice_id = c.id 
    LEFT JOIN po p ON r.quotation_id = p.id 
    WHERE r.id='".$id."' AND r.vender='".$com_id."'");
if(mysqli_num_rows($query) != 1) {
    echo '<div class="alert alert-danger" style="margin:20px;">Receipt not found or access denied.</div>';
    exit;
}
$data = mysqli_fetch_assoc($query);

// Determine source type
$source_type = $data['source_type'] ?? 'manual';
$include_vat = $data['include_vat'] ?? 1;

// Check for linked invoice or quotation data
$linked_source = null;
$use_source_data = false;
$source_id = null;

if ($source_type === 'invoice' && !empty($data['invoice_id'])) {
    $source_id = $data['invoice_id'];
    $inv_query = mysqli_query($db->conn, "
        SELECT po.id, iv.taxrw as doc_no, DATE_FORMAT(iv.createdate, '%d/%m/%Y') as doc_date, 
               po.vat as doc_vat, po.dis as doc_dis,
               company.name_en as cust_name, company.phone as cust_phone, company.email as cust_email
        FROM po
        JOIN pr ON po.ref = pr.id
        JOIN company ON pr.cus_id = company.id
        JOIN iv ON po.id = iv.tex
        WHERE po.id = '".$data['invoice_id']."' AND pr.ven_id = '".$com_id."'
    ");
    if (mysqli_num_rows($inv_query) > 0) {
        $linked_source = mysqli_fetch_array($inv_query);
        $use_source_data = true;
    }
} elseif ($source_type === 'quotation' && !empty($data['quotation_id'])) {
    $source_id = $data['quotation_id'];
    $qa_query = mysqli_query($db->conn, "
        SELECT po.id, po.tax as doc_no, DATE_FORMAT(po.date, '%d/%m/%Y') as doc_date, 
               po.vat as doc_vat, po.dis as doc_dis,
               company.name_en as cust_name, company.phone as cust_phone, company.email as cust_email
        FROM po
        JOIN pr ON po.ref = pr.id
        JOIN company ON pr.cus_id = company.id
        WHERE po.id = '".$data['quotation_id']."' AND pr.ven_id = '".$com_id."' AND po.status = '1'
    ");
    if (mysqli_num_rows($qa_query) > 0) {
        $linked_source = mysqli_fetch_array($qa_query);
        $use_source_data = true;
    }
}

// Fetch products (from source document or receipt)
if ($use_source_data && $source_id) {
    $products_query = mysqli_query($db->conn, "
        SELECT p.*, t.name as type_name, b.brand_name, m.model_name 
        FROM product p 
        LEFT JOIN type t ON p.type = t.id 
        LEFT JOIN brand b ON p.ban_id = b.id 
        LEFT JOIN model m ON p.model = m.id 
        WHERE p.po_id = '".$source_id."'
    ");
} else {
    $products_query = mysqli_query($db->conn, "
        SELECT p.*, t.name as type_name, b.brand_name, m.model_name 
        FROM product p 
        LEFT JOIN type t ON p.type = t.id 
        LEFT JOIN brand b ON p.ban_id = b.id 
        LEFT JOIN model m ON p.model = m.id 
        WHERE p.rep_id = '".$id."'
    ");
}

// Calculate totals
$subtotal = 0;
$products = [];
while($prod = mysqli_fetch_assoc($products_query)) {
    $line_total = $prod['quantity'] * $prod['price'];
    $subtotal += $line_total;
    $prod['line_total'] = $line_total;
    $products[] = $prod;
}

$vat = $use_source_data ? ($linked_source['doc_vat'] ?? 7) : ($data['vat'] ?? 7);
$discount = $use_source_data ? ($linked_source['doc_dis'] ?? 0) : ($data['dis'] ?? 0);

$discount_amount = $subtotal * ($discount / 100);
$after_discount = $subtotal - $discount_amount;

// Only calculate VAT if include_vat is enabled
if ($include_vat) {
    $vat_amount = $after_discount * ($vat / 100);
    $grand_total = $after_discount + $vat_amount;
} else {
    $vat_amount = 0;
    $grand_total = $after_discount;
}

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

// Source type display
$source_labels = [
    'quotation' => ['label' => 'Quotation', 'color' => '#f59e0b', 'prefix' => 'QA-'],
    'invoice' => ['label' => 'Invoice', 'color' => '#27ae60', 'prefix' => 'INV-'],
    'manual' => ['label' => 'Manual Entry', 'color' => '#6b7280', 'prefix' => '']
];
$source_info = $source_labels[$source_type] ?? $source_labels['manual'];
?>
<!DOCTYPE html>
<html>
<head>
<style>
/* Receipt View Styling - Green Theme */
.receipt-view-container { max-width: 1000px; margin: 0 auto; padding: 20px; }

.page-header-rep { 
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); 
    color: #fff; 
    padding: 25px 30px; 
    border-radius: 10px; 
    margin-bottom: 25px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    box-shadow: 0 4px 15px rgba(39,174,96,0.3);
}
.page-header-rep h2 { margin: 0; font-size: 26px; font-weight: 600; }
.page-header-rep .receipt-number { font-size: 14px; opacity: 0.9; margin-top: 5px; }
.header-actions { display: flex; gap: 10px; }
.header-actions .btn { padding: 10px 20px; border-radius: 6px; font-weight: 500; text-decoration: none; transition: all 0.3s; }
.btn-back { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; }
.btn-back:hover { background: rgba(255,255,255,0.3); color: #fff; }
.btn-edit { background: #f39c12; border: none; color: #fff; }
.btn-edit:hover { background: #e67e22; color: #fff; }
.btn-pdf { background: #3498db; border: none; color: #fff; }
.btn-pdf:hover { background: #2980b9; color: #fff; }

/* Invoice Link Banner */
.invoice-banner {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.invoice-banner i { font-size: 24px; }
.invoice-banner .info { flex: 1; }
.invoice-banner .info strong { display: block; font-size: 15px; }
.invoice-banner .info span { font-size: 13px; opacity: 0.9; }
.invoice-banner a { color: white; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 5px; text-decoration: none; }
.invoice-banner a:hover { background: rgba(255,255,255,0.3); }

/* Info Cards */
.info-row { display: flex; gap: 20px; margin-bottom: 20px; }
.info-card { flex: 1; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
.info-card .card-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: 600; color: #333; }
.info-card .card-header i { margin-right: 8px; color: #27ae60; }
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
.products-card .card-header { background: #27ae60; color: #fff; padding: 15px 20px; font-weight: 600; }
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
.summary-row.total { border-top: 2px solid #27ae60; padding-top: 12px; margin-top: 8px; font-size: 18px; font-weight: 700; color: #27ae60; }
.summary-row .label { color: #666; }
.summary-row .value { font-weight: 600; color: #333; }

/* Notes Section */
.notes-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-top: 20px; }
.notes-card h4 { margin: 0 0 10px 0; font-size: 14px; color: #888; text-transform: uppercase; }
.notes-card p { margin: 0; color: #333; line-height: 1.6; }

@media (max-width: 768px) {
    .info-row { flex-direction: column; }
    .info-grid { grid-template-columns: 1fr; }
    .header-actions { flex-wrap: wrap; }
}
</style>
</head>
<body>

<div class="receipt-view-container">

<!-- Page Header -->
<div class="page-header-rep">
    <div>
        <h2><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt ?? 'Receipt'?></h2>
        <div class="receipt-number">REC-<?=htmlspecialchars($data['rep_rw'])?></div>
    </div>
    <div class="header-actions">
        <a href="index.php?page=receipt_list" class="btn btn-back"><i class="glyphicon glyphicon-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        <a href="index.php?page=rep_make&id=<?=$id?>" class="btn btn-edit"><i class="glyphicon glyphicon-pencil"></i> <?=$xml->edit ?? 'Edit'?></a>
        <a href="index.php?page=rep_print&id=<?=$id?>" target="_blank" class="btn btn-pdf"><i class="glyphicon glyphicon-print"></i> <?=$xml->pdf ?? 'PDF'?></a>
    </div>
</div>

<?php if ($linked_source): ?>
<!-- Linked Source Banner (Invoice or Quotation) -->
<div class="invoice-banner" style="background: linear-gradient(135deg, <?=$source_info['color']?> 0%, <?=$source_info['color']?>dd 100%);">
    <i class="<?=$source_type === 'quotation' ? 'fa fa-file-text-o' : 'glyphicon glyphicon-link'?>"></i>
    <div class="info">
        <strong><?=$source_type === 'quotation' ? ($xml->linkedtoquotation ?? 'Linked to Quotation') : ($xml->linkedtoinvoice ?? 'Linked to Invoice')?></strong>
        <span><?=$source_info['prefix']?><?=htmlspecialchars($linked_source['doc_no'])?> - <?=$linked_source['doc_date']?></span>
    </div>
    <?php if ($source_type === 'quotation'): ?>
    <a href="index.php?page=qa_list" target="_blank"><i class="fa fa-eye"></i> <?=$xml->viewquotation ?? 'View Quotation'?></a>
    <?php else: ?>
    <a href="index.php?page=compl_view&id=<?=$data['invoice_id']?>" target="_blank"><i class="glyphicon glyphicon-eye-open"></i> <?=$xml->viewinvoice ?? 'View Invoice'?></a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Customer Info & Receipt Details -->
<div class="info-row">
    <div class="info-card">
        <div class="card-header"><i class="glyphicon glyphicon-user"></i> <?=$xml->customer ?? 'Customer'?></div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label><?=$xml->name ?? 'Name'?></label>
                    <div class="value"><?=htmlspecialchars($use_source_data ? $linked_source['cust_name'] : $data['name'])?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->email ?? 'Email'?></label>
                    <?php $email = $use_source_data ? $linked_source['cust_email'] : $data['email']; ?>
                    <div class="value"><?=$email ? '<a href="mailto:'.$email.'">'.$email.'</a>' : '-'?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->phone ?? 'Phone'?></label>
                    <div class="value"><?=($use_source_data ? $linked_source['cust_phone'] : $data['phone']) ?: '-'?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->status ?? 'Status'?></label>
                    <div class="value">
                        <span class="status-badge <?=$status_info['class']?>">
                            <i class="glyphicon <?=$status_info['icon']?>"></i>
                            <?=ucfirst($data['status'] ?: 'confirmed')?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="info-card">
        <div class="card-header"><i class="glyphicon glyphicon-cog"></i> <?=$xml->receiptdetails ?? 'Receipt Details'?></div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label><?=$xml->receiptnumber ?? 'Receipt No.'?></label>
                    <div class="value" style="font-weight:700; color:#27ae60;">REC-<?=htmlspecialchars($data['rep_rw'])?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->sourcetype ?? 'Source Type'?></label>
                    <div class="value">
                        <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:15px; background:<?=$source_info['color']?>22; color:<?=$source_info['color']?>; font-weight:600; font-size:12px;">
                            <i class="<?=$source_type === 'quotation' ? 'fa fa-file-text-o' : ($source_type === 'invoice' ? 'fa fa-file-text' : 'fa fa-edit')?>"></i>
                            <?=$source_info['label']?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <label><?=$xml->createdate ?? 'Date'?></label>
                    <div class="value"><?=date('d M Y', strtotime($data['createdate']))?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->vatmode ?? 'VAT Mode'?></label>
                    <div class="value">
                        <?php if ($include_vat): ?>
                        <span style="color:#27ae60; font-weight:600;"><i class="fa fa-check-circle"></i> <?=$xml->includevat ?? 'Include VAT'?> (<?=$vat?>%)</span>
                        <?php else: ?>
                        <span style="color:#e74c3c; font-weight:600;"><i class="fa fa-times-circle"></i> <?=$xml->novat ?? 'No VAT'?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-item">
                    <label><?=$xml->paymentmethod ?? 'Payment Method'?></label>
                    <div class="value"><?=htmlspecialchars($payment_display)?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->vat ?? 'VAT'?> / <?=$xml->discount ?? 'Discount'?></label>
                    <div class="value"><?=$vat?>% / <?=$discount?>%</div>
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
        <?php if($discount > 0): ?>
        <div class="summary-row">
            <span class="label"><?=$xml->discount ?? 'Discount'?> (<?=$discount?>%)</span>
            <span class="value">-<?=number_format($discount_amount, 2)?></span>
        </div>
        <?php endif; ?>
        <?php if($include_vat): ?>
        <div class="summary-row">
            <span class="label"><?=$xml->vat ?? 'VAT'?> (<?=$vat?>%)</span>
            <span class="value"><?=number_format($vat_amount, 2)?></span>
        </div>
        <?php else: ?>
        <div class="summary-row">
            <span class="label" style="color:#e74c3c;"><?=$xml->novat ?? 'No VAT'?></span>
            <span class="value" style="color:#e74c3c;">-</span>
        </div>
        <?php endif; ?>
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

</div><!-- /receipt-view-container -->

</body>
</html>