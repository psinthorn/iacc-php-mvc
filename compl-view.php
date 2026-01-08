<?php
// Security already checked in index.php

$_date = explode("-", date("d-m-Y"));
$day = $_date[0];
$month = $_date[1];
$year = $_date[2];

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);

$query = mysqli_query($db->conn, "SELECT po.name as name, ven_id, vat, cus_id, des, payby, over, 
    DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, dis, 
    DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, ref, pic, status 
    FROM pr JOIN po ON pr.id=po.ref 
    WHERE po.id='".$id."' AND status='4' 
    AND (cus_id='".$com_id."' OR ven_id='".$com_id."') AND po_id_new=''");

$hasData = mysqli_num_rows($query) == 1;
if($hasData) {
    $data = mysqli_fetch_array($query);
    $vender = mysqli_fetch_array(mysqli_query($db->conn, "SELECT name_sh, name_en FROM company WHERE id='".$data['ven_id']."'"));
    $customer = mysqli_fetch_array(mysqli_query($db->conn, "SELECT name_sh, name_en FROM company WHERE id='".$data['cus_id']."'"));
    
    // Check for labour columns
    $cklabour = mysqli_fetch_array(mysqli_query($db->conn, "SELECT max(activelabour) as cklabour FROM product JOIN type ON product.type=type.id WHERE po_id='".$id."'"));
    $hasLabour = ($cklabour['cklabour'] == 1);
    
    // Get products and calculate totals
    $que_pro = mysqli_query($db->conn, "SELECT type.name as name, product.price as price, discount, 
        model.model_name as model, quantity, pack_quantity, activelabour, valuelabour 
        FROM product JOIN type ON product.type=type.id JOIN model ON product.model=model.id 
        WHERE po_id='".$id."'");
    
    $products = [];
    $summary = 0;
    while($data_pro = mysqli_fetch_array($que_pro)) {
        if($hasLabour) {
            $equip = $data_pro['price'] * $data_pro['quantity'];
            $labour1 = $data_pro['valuelabour'] * $data_pro['activelabour'];
            $labour = $labour1 * $data_pro['quantity'];
            $total = $equip + $labour;
        } else {
            $total = $data_pro['price'] * $data_pro['quantity'];
            $equip = $total;
            $labour1 = 0;
            $labour = 0;
        }
        $summary += $total;
        $products[] = [
            'model' => $data_pro['model'],
            'name' => $data_pro['name'],
            'quantity' => $data_pro['quantity'],
            'price' => $data_pro['price'],
            'equip' => $equip,
            'labour1' => $labour1,
            'labour' => $labour,
            'total' => $total
        ];
    }
    
    // Calculate totals
    $disc = $summary * $data['dis'] / 100;
    $subt = $summary - $disc;
    
    $overh = 0;
    if($data['over'] > 0) {
        $overh = $subt * $data['over'] / 100;
        $subt = $subt + $overh;
    }
    
    $vat = $subt * $data['vat'] / 100;
    $totalnet = $subt + $vat;
    
    // Get payments
    $payments = [];
    $querypay = mysqli_query($db->conn, "SELECT DATE_FORMAT(date,'%d-%m-%Y') as date, value, id, volumn FROM pay WHERE po_id='".$id."'");
    while($datapays = mysqli_fetch_array($querypay)) {
        $payments[] = $datapays;
    }
    
    // Calculate accumulated/remaining
    $stotals = mysqli_fetch_array(mysqli_query($db->conn, "SELECT sum(volumn) as stotal FROM pay WHERE po_id='".$id."'"));
    $accu = $totalnet - ($stotals['stotal'] ?? 0);
    if($accu < 0.000000000001) $accu = 0;
    
    // Get ref for void
    $refpo = mysqli_fetch_array(mysqli_query($db->conn, "SELECT ref FROM po WHERE id='".$id."'"));
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
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(139, 92, 246, 0.25);
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
    
    .page-header .badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .btn-back {
        background: rgba(255,255,255,0.15);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-back:hover {
        background: rgba(255,255,255,0.25);
        color: white;
        text-decoration: none;
    }
    
    .content-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .card-header i {
        color: #8b5cf6;
    }
    
    .card-body {
        padding: 24px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .info-item label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-item .value {
        font-size: 15px;
        color: #1f2937;
        padding: 12px 16px;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    
    .info-item textarea.value {
        min-height: 80px;
        resize: vertical;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .products-table th {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .products-table th.text-center { text-align: center; }
    .products-table th.text-right { text-align: right; }
    
    .products-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
    }
    
    .products-table td.text-center { text-align: center; }
    .products-table td.text-right { text-align: right; }
    
    .products-table tbody tr:hover {
        background: #faf5ff;
    }
    
    .products-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .summary-section {
        background: #f9fafb;
        border-top: 2px solid #e5e7eb;
        padding: 20px 24px;
    }
    
    .summary-grid {
        display: flex;
        justify-content: flex-end;
    }
    
    .summary-table {
        min-width: 320px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .summary-row:last-child {
        border-bottom: none;
    }
    
    .summary-row .label {
        color: #6b7280;
        font-weight: 500;
    }
    
    .summary-row .amount {
        font-weight: 600;
        color: #374151;
    }
    
    .summary-row.total {
        border-top: 2px solid #8b5cf6;
        margin-top: 10px;
        padding-top: 15px;
    }
    
    .summary-row.total .label,
    .summary-row.total .amount {
        font-size: 18px;
        font-weight: 700;
        color: #8b5cf6;
    }
    
    .payment-history {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed #d1d5db;
    }
    
    .payment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        background: #ecfdf5;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    
    .payment-item .info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .payment-item .date {
        color: #059669;
        font-weight: 500;
    }
    
    .payment-item .method {
        color: #6b7280;
        font-size: 13px;
    }
    
    .payment-item .amount {
        font-weight: 600;
        color: #059669;
    }
    
    .payment-item .print-link {
        color: #8b5cf6;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }
    
    .payment-item .print-link:hover {
        text-decoration: underline;
    }
    
    .payment-form-section {
        background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        border: 2px solid #e9d5ff;
        border-radius: 12px;
        padding: 24px;
        margin-top: 24px;
    }
    
    .payment-form-section h4 {
        margin: 0 0 20px 0;
        color: #7c3aed;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .payment-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        align-items: end;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .form-group label {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
    }
    
    .form-group input,
    .form-group select {
        padding: 14px 16px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.2s;
        min-height: 48px;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
    }
    
    .btn-pay {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-pay:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-void {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-void:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    .btn-complete {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-complete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    
    .btn-print {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .remaining-badge {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .paid-badge {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .error-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 60px 40px;
        text-align: center;
        border: 1px solid #e5e7eb;
    }
    
    .error-icon {
        width: 80px;
        height: 80px;
        background: #fef2f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .error-icon i {
        font-size: 40px;
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

<script>
function paymentcheck() {
    var volumn = parseFloat(document.getElementById("volumn").value);
    var total = parseFloat(document.getElementById("total").value);
    
    if (volumn > total) {
        alert("Payment amount cannot exceed remaining balance");
        document.getElementById("volumn").focus();
        return false;
    }
    return true;
}
</script>

<div class="view-wrapper">

<?php if($hasData): ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2>
                <i class="fa fa-file-text-o"></i>
                <?=$xml->invoice ?? 'Invoice'?>
            </h2>
            <div style="margin-top:8px; opacity:0.9; font-size:14px;">
                <?=htmlspecialchars($data['name'] ?? '')?>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:16px;">
            <?php if($accu != 0): ?>
                <span class="remaining-badge">
                    <i class="fa fa-clock-o"></i>
                    <?=$xml->remaining ?? 'Remaining'?>: ฿<?=number_format($accu, 2)?>
                </span>
            <?php else: ?>
                <span class="paid-badge">
                    <i class="fa fa-check-circle"></i>
                    <?=$xml->fullypaid ?? 'Fully Paid'?>
                </span>
            <?php endif; ?>
            <a href="index.php?page=compl_list" class="btn-back">
                <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?>
            </a>
        </div>
    </div>

    <!-- Invoice Details Card -->
    <div class="content-card">
        <div class="card-header">
            <i class="fa fa-info-circle"></i>
            <?=$xml->details ?? 'Invoice Details'?>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label><?=$xml->vender ?? 'Vendor'?></label>
                    <div class="value"><?=htmlspecialchars($vender['name_en'] ?? $vender['name_sh'] ?? '')?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->customer ?? 'Customer'?></label>
                    <div class="value"><?=htmlspecialchars($customer['name_en'] ?? $customer['name_sh'] ?? '')?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->validpay ?? 'Payment Due'?></label>
                    <div class="value"><?=htmlspecialchars($data['valid_pay'] ?? '')?></div>
                </div>
                <div class="info-item">
                    <label><?=$xml->deliverydate ?? 'Delivery Date'?></label>
                    <div class="value"><?=htmlspecialchars($data['deliver_date'] ?? '')?></div>
                </div>
            </div>
            <?php if(!empty($data['des'])): ?>
            <div class="info-item" style="margin-top:20px;">
                <label><?=$xml->description ?? 'Description'?></label>
                <div class="value"><?=nl2br(htmlspecialchars($data['des'] ?? ''))?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Products Card -->
    <div class="content-card">
        <div class="card-header">
            <i class="fa fa-cube"></i>
            <?=$xml->products ?? 'Products'?>
            <span style="margin-left:auto; background:#8b5cf6; color:white; padding:4px 12px; border-radius:20px; font-size:12px;">
                <?=count($products)?> <?=$xml->items ?? 'items'?>
            </span>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width:15%"><?=$xml->model ?? 'Model'?></th>
                    <th><?=$xml->product ?? 'Product'?></th>
                    <th class="text-center" style="width:8%"><?=$xml->unit ?? 'Qty'?></th>
                    <th class="text-right" style="width:10%"><?=$xml->price ?? 'Price'?></th>
                    <?php if($hasLabour): ?>
                    <th class="text-right" style="width:10%"><?=$xml->equipment ?? 'Equipment'?></th>
                    <th class="text-right" style="width:8%"><?=$xml->labour ?? 'Labour'?></th>
                    <th class="text-right" style="width:10%"><?=$xml->labourtotal ?? 'L.Total'?></th>
                    <?php endif; ?>
                    <th class="text-right" style="width:10%"><?=$xml->amount ?? 'Amount'?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $prod): ?>
                    <tr>
                        <td><?=htmlspecialchars($prod['model'])?></td>
                        <td><?=htmlspecialchars($prod['name'])?></td>
                        <td class="text-center"><?=intval($prod['quantity'])?></td>
                        <td class="text-right"><?=number_format($prod['price'], 2)?></td>
                        <?php if($hasLabour): ?>
                        <td class="text-right"><?=number_format($prod['equip'], 2)?></td>
                        <td class="text-right"><?=number_format($prod['labour1'], 2)?></td>
                        <td class="text-right"><?=number_format($prod['labour'], 2)?></td>
                        <?php endif; ?>
                        <td class="text-right"><?=number_format($prod['total'], 2)?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?=$hasLabour ? 8 : 5?>" style="text-align:center; padding:40px; color:#9ca3af;">
                            <i class="fa fa-inbox" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            No products found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-table">
                    <div class="summary-row">
                        <span class="label"><?=$xml->subtotal ?? 'Subtotal'?></span>
                        <span class="amount"><?=number_format($summary, 2)?></span>
                    </div>
                    <?php if($data['dis'] > 0): ?>
                    <div class="summary-row">
                        <span class="label"><?=$xml->discount ?? 'Discount'?> <?=$data['dis']?>%</span>
                        <span class="amount">- <?=number_format($disc, 2)?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($data['over'] > 0): ?>
                    <div class="summary-row">
                        <span class="label"><?=$xml->overhead ?? 'Overhead'?> <?=$data['over']?>%</span>
                        <span class="amount">+ <?=number_format($overh, 2)?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span class="label"><?=$xml->net ?? 'Net Amount'?></span>
                        <span class="amount"><?=number_format($subt, 2)?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label"><?=$xml->vat ?? 'VAT'?> <?=$data['vat']?>%</span>
                        <span class="amount">+ <?=number_format($vat, 2)?></span>
                    </div>
                    <div class="summary-row total">
                        <span class="label"><?=$xml->grandtotal ?? 'Grand Total'?></span>
                        <span class="amount"><?=number_format($totalnet, 2)?></span>
                    </div>
                    
                    <?php if(count($payments) > 0): ?>
                    <div class="payment-history">
                        <div style="font-size:12px; color:#6b7280; margin-bottom:10px; font-weight:600;">
                            <i class="fa fa-history"></i> <?=$xml->paymenthistory ?? 'Payment History'?>
                        </div>
                        <?php foreach($payments as $pay): ?>
                        <div class="payment-item">
                            <div class="info">
                                <span class="date"><?=$pay['date']?></span>
                                <span class="method"><?=$pay['value']?></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:16px;">
                                <span class="amount"><?=number_format($pay['volumn'], 2)?></span>
                                <a href="sptinv.php?id=<?=$pay['id']?>" target="_blank" class="print-link">
                                    <i class="fa fa-print"></i> <?=$xml->print ?? 'Print'?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if($accu != 0 && $data['ven_id'] == $_SESSION['com_id']): ?>
    <!-- Payment Form Section -->
    <div class="payment-form-section">
        <h4><i class="fa fa-credit-card"></i> <?=$xml->recordpayment ?? 'Record Payment'?></h4>
        <form action="core-function.php" method="post" onsubmit="return paymentcheck();">
            <?= csrf_field() ?>
            <div class="payment-form-grid">
                <div class="form-group">
                    <label><?=$xml->method ?? 'Payment Method'?></label>
                    <select name="payment" class="form-control">
                        <?php 
                        $querycustomer = mysqli_query($db->conn, "SELECT payment_name, id FROM payment WHERE com_id='".$_SESSION['com_id']."'");
                        while($fetch_customer = mysqli_fetch_array($querycustomer)) {
                            echo "<option value='".$fetch_customer['id']."'>".$fetch_customer['payment_name']."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?=$xml->notes ?? 'Notes'?></label>
                    <input type="text" name="remark" placeholder="<?=$xml->notes ?? 'Notes'?>">
                </div>
                <div class="form-group">
                    <label><?=$xml->amount ?? 'Amount'?></label>
                    <input type="text" id="volumn" name="volumn" value="<?=$accu?>" required>
                    <input type="hidden" name="total" id="total" value="<?=$accu?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-pay">
                        <i class="fa fa-check"></i> <?=$xml->pay ?? 'Record Payment'?>
                    </button>
                </div>
            </div>
            <input type="hidden" name="method" value="C">
            <input type="hidden" name="page" value="compl_list">
            <input type="hidden" name="po_id" value="<?=$id?>">
        </form>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="inv.php?id=<?=$id?>" target="_blank" class="btn-print">
            <i class="fa fa-print"></i> <?=$xml->printinvoice ?? 'Print Invoice'?>
        </a>
        
        <?php if($accu != 0): ?>
        <form action="core-function.php" method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="page" value="compl_list2">
            <input type="hidden" name="id" value="<?=$refpo['ref'] ?? ''?>">
            <button type="submit" name="method" value="V" class="btn-void">
                <i class="fa fa-ban"></i> <?=$xml->voidinv ?? 'Void Invoice'?>
            </button>
        </form>
        <?php else: ?>
        <form action="core-function.php" method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="page" value="compl_list2">
            <input type="hidden" name="id" value="<?=$refpo['ref'] ?? ''?>">
            <button type="submit" name="method" value="V" class="btn-void">
                <i class="fa fa-ban"></i> <?=$xml->voidinv ?? 'Void Invoice'?>
            </button>
            <button type="submit" name="method" value="C" class="btn-complete">
                <i class="fa fa-check-circle"></i> <?=$xml->taxinvoicem ?? 'Issue Tax Invoice'?>
            </button>
        </form>
        <?php endif; ?>
    </div>

<?php else: ?>

    <!-- Error State -->
    <div class="error-card">
        <div class="error-icon">
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <h3><?=$xml->error ?? 'Error'?></h3>
        <p><?=$xml->invoicenotfound ?? 'Invoice not found or access denied.'?></p>
        <br>
        <a href="index.php?page=compl_list" class="btn-back" style="background:#8b5cf6; display:inline-flex;">
            <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to List'?>
        </a>
    </div>

<?php endif; ?>

</div>
