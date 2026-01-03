<?php
/**
 * Invoice Payment Success Callback
 * Handles successful payments from PayPal/Stripe
 * - Updates invoice status to "paid"
 * - Auto-creates a receipt
 */

session_start();
require_once __DIR__ . '/inc/sys.configs.php';
require_once __DIR__ . '/inc/class.dbconn.php';

$db = new DbConn($config);
$conn = $db->conn;

$error = '';
$success = false;
$receiptId = null;
$invoiceId = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : ''; // Stripe
$paypalOrderId = isset($_GET['token']) ? $_GET['token'] : ''; // PayPal

// Determine gateway from URL or invoice
$gateway = '';
if ($sessionId) {
    $gateway = 'stripe';
} elseif ($paypalOrderId) {
    $gateway = 'paypal';
}

try {
    if (!$invoiceId && !$sessionId && !$paypalOrderId) {
        throw new Exception('Missing payment information');
    }
    
    // If we have session_id but no invoice_id, look it up from Stripe
    if ($sessionId && !$invoiceId) {
        require_once __DIR__ . '/inc/class.stripe.php';
        $stripe = new StripeService($conn);
        $session = $stripe->getCheckoutSession($sessionId);
        $invoiceId = intval($session['metadata']['invoice_id'] ?? 0);
    }
    
    // If we have PayPal token but no invoice_id, look it up
    if ($paypalOrderId && !$invoiceId) {
        require_once __DIR__ . '/inc/class.paypal.php';
        $paypal = new PayPalService($conn);
        $order = $paypal->getOrderDetails($paypalOrderId);
        $invoiceId = intval($order['purchase_units'][0]['invoice_id'] ?? 0);
    }
    
    if (!$invoiceId) {
        throw new Exception('Could not determine invoice ID');
    }
    
    // Fetch invoice details
    $sql = "
        SELECT 
            iv.tex, iv.payment_status, iv.payment_gateway, iv.payment_order_id, iv.paid_amount,
            po.id as po_id, po.name as po_name, po.dis, po.vat, po.over,
            pr.ven_id, pr.cus_id, pr.payby,
            cus.name_en as customer_name, cus.phone as customer_phone, cus.email as customer_email,
            br.id as brand_id
        FROM iv 
        JOIN po ON iv.tex = po.id
        JOIN pr ON po.ref = pr.id
        LEFT JOIN company cus ON pr.payby = cus.id
        LEFT JOIN brand br ON po.bandven = br.id
        WHERE iv.tex = ?
        AND iv.deleted_at IS NULL
    ";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $invoiceId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $invoice = mysqli_fetch_assoc($result);
    
    if (!$invoice) {
        throw new Exception('Invoice not found');
    }
    
    // Already paid?
    if ($invoice['payment_status'] === 'paid') {
        $success = true;
        // Find existing receipt
        $recSql = "SELECT id FROM receipt WHERE invoice_id = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1";
        $recStmt = mysqli_prepare($conn, $recSql);
        mysqli_stmt_bind_param($recStmt, "i", $invoiceId);
        mysqli_stmt_execute($recStmt);
        $recResult = mysqli_stmt_get_result($recStmt);
        $existingReceipt = mysqli_fetch_assoc($recResult);
        $receiptId = $existingReceipt['id'] ?? null;
    } else {
        // Calculate invoice total
        $productsSql = "
            SELECT product.price, product.quantity, product.valuelabour, type.activelabour
            FROM product 
            JOIN type ON product.type = type.id 
            WHERE product.po_id = ?
        ";
        $prodStmt = mysqli_prepare($conn, $productsSql);
        mysqli_stmt_bind_param($prodStmt, "i", $invoiceId);
        mysqli_stmt_execute($prodStmt);
        $prodResult = mysqli_stmt_get_result($prodStmt);
        
        $subtotal = 0;
        while ($prod = mysqli_fetch_assoc($prodResult)) {
            $equip = $prod['price'] * $prod['quantity'];
            $labour = ($prod['valuelabour'] * $prod['activelabour']) * $prod['quantity'];
            $subtotal += $equip + $labour;
        }
        
        $discount = $subtotal * ($invoice['dis'] / 100);
        $afterDiscount = $subtotal - $discount;
        
        if ($invoice['over'] > 0) {
            $overhead = $afterDiscount * ($invoice['over'] / 100);
            $afterDiscount += $overhead;
        }
        
        $vatAmount = $afterDiscount * ($invoice['vat'] / 100);
        $grandTotal = round($afterDiscount + $vatAmount, 2);
        
        // Verify payment with gateway
        $paymentVerified = false;
        $transactionId = '';
        $paidAmount = $grandTotal;
        
        if ($gateway === 'stripe' && $sessionId) {
            require_once __DIR__ . '/inc/class.stripe.php';
            $stripe = new StripeService($conn);
            $session = $stripe->getCheckoutSession($sessionId);
            
            if ($session['payment_status'] === 'paid') {
                $paymentVerified = true;
                $transactionId = $session['payment_intent'] ?? $sessionId;
                $paidAmount = ($session['amount_total'] ?? 0) / 100;
            }
            
        } elseif ($gateway === 'paypal') {
            require_once __DIR__ . '/inc/class.paypal.php';
            $paypal = new PayPalService($conn);
            
            // For PayPal, we need to capture the payment
            $orderId = $paypalOrderId ?: $invoice['payment_order_id'];
            
            if ($orderId) {
                // Check order status first
                $order = $paypal->getOrderDetails($orderId);
                
                if ($order['status'] === 'APPROVED') {
                    // Capture the payment
                    $captureResult = $paypal->capturePayment($orderId);
                    
                    if ($captureResult['success'] && $captureResult['status'] === 'COMPLETED') {
                        $paymentVerified = true;
                        $transactionId = $captureResult['capture_id'] ?? $orderId;
                        $paidAmount = floatval($captureResult['amount']['value'] ?? $grandTotal);
                    }
                } elseif ($order['status'] === 'COMPLETED') {
                    // Already captured
                    $paymentVerified = true;
                    $transactionId = $orderId;
                    $captures = $order['purchase_units'][0]['payments']['captures'] ?? [];
                    if (!empty($captures)) {
                        $paidAmount = floatval($captures[0]['amount']['value'] ?? $grandTotal);
                    }
                }
            }
        }
        
        if (!$paymentVerified) {
            throw new Exception('Payment could not be verified. Please contact support.');
        }
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Update invoice status
            $updateIvSql = "UPDATE iv SET 
                payment_status = 'paid', 
                paid_amount = ?, 
                paid_date = NOW(),
                payment_gateway = ?,
                payment_order_id = ?
                WHERE tex = ?";
            $updateStmt = mysqli_prepare($conn, $updateIvSql);
            mysqli_stmt_bind_param($updateStmt, "dssi", $paidAmount, $gateway, $transactionId, $invoiceId);
            mysqli_stmt_execute($updateStmt);
            
            // 2. Generate receipt number
            $year = date('Y') + 543; // Buddhist year
            $yearPrefix = substr($year, -2) . '000';
            
            $maxRepSql = "SELECT MAX(rep_no) as max_no FROM receipt WHERE rep_rw LIKE ?";
            $maxStmt = mysqli_prepare($conn, $maxRepSql);
            $yearPattern = $yearPrefix . '%';
            mysqli_stmt_bind_param($maxStmt, "s", $yearPattern);
            mysqli_stmt_execute($maxStmt);
            $maxResult = mysqli_stmt_get_result($maxStmt);
            $maxRow = mysqli_fetch_assoc($maxResult);
            
            $newRepNo = ($maxRow['max_no'] ?? 0) + 1;
            $repRw = $yearPrefix . str_pad($newRepNo, 4, '0', STR_PAD_LEFT);
            
            // 3. Create receipt
            $insertRecSql = "INSERT INTO receipt (
                name, phone, email, createdate, description, 
                payment_method, status, invoice_id, vender, 
                rep_no, rep_rw, brand, vat, dis,
                payment_source, payment_transaction_id
            ) VALUES (?, ?, ?, NOW(), ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $description = "Auto-generated receipt for Invoice #" . $invoiceId . " - Paid via " . ucfirst($gateway);
            $paymentMethod = $gateway;
            
            $insertStmt = mysqli_prepare($conn, $insertRecSql);
            mysqli_stmt_bind_param($insertStmt, "sssssiiisiiiss",
                $invoice['customer_name'],
                $invoice['customer_phone'],
                $invoice['customer_email'],
                $description,
                $paymentMethod,
                $invoiceId,
                $invoice['ven_id'],
                $newRepNo,
                $repRw,
                $invoice['brand_id'] ?: 0,
                $invoice['vat'],
                $invoice['dis'],
                $gateway,
                $transactionId
            );
            mysqli_stmt_execute($insertStmt);
            $receiptId = mysqli_insert_id($conn);
            
            // 4. Log to audit
            if (function_exists('audit_log')) {
                audit_log('create', 'receipt', $receiptId, null, [
                    'invoice_id' => $invoiceId,
                    'gateway' => $gateway,
                    'amount' => $paidAmount,
                    'auto_created' => true
                ]);
            }
            
            mysqli_commit($conn);
            $success = true;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            throw $e;
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Fetch invoice details for display
$invoiceData = null;
if ($invoiceId) {
    $sql = "SELECT iv.tex, po.name as po_name, iv.paid_amount, c.name_en as vendor_name
            FROM iv 
            JOIN po ON iv.tex = po.id
            JOIN pr ON po.ref = pr.id
            LEFT JOIN company c ON pr.ven_id = c.id
            WHERE iv.tex = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $invoiceId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $invoiceData = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'Payment Successful' : 'Payment Error'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        body.success { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); }
        body.error { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        
        .result-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            max-width: 500px;
            width: 100%;
            text-align: center;
            overflow: hidden;
        }
        
        .result-icon {
            padding: 40px 30px 20px;
        }
        
        .result-icon i {
            font-size: 80px;
        }
        
        .result-icon.success i { color: #27ae60; }
        .result-icon.error i { color: #e74c3c; }
        
        .checkmark-animation {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(39, 174, 96, 0.1);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .result-content {
            padding: 0 30px 30px;
        }
        
        .result-content h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .result-content p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .payment-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { color: #666; }
        .detail-value { font-weight: 600; color: #333; }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 25px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .receipt-badge {
            display: inline-block;
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .receipt-badge i { margin-right: 5px; }
    </style>
</head>
<body class="<?php echo $success ? 'success' : 'error'; ?>">
    <div class="result-container">
        <div class="result-icon <?php echo $success ? 'success' : 'error'; ?>">
            <?php if ($success): ?>
            <div class="checkmark-animation">
                <i class="fa fa-check-circle"></i>
            </div>
            <?php else: ?>
            <i class="fa fa-times-circle"></i>
            <?php endif; ?>
        </div>
        
        <div class="result-content">
            <?php if ($success): ?>
                <h1>Payment Successful!</h1>
                <p>Thank you for your payment. A receipt has been automatically generated.</p>
                
                <?php if ($receiptId): ?>
                <div class="receipt-badge">
                    <i class="fa fa-file-text-o"></i>
                    Receipt #<?php echo $receiptId; ?> Created
                </div>
                <?php endif; ?>
                
                <?php if ($invoiceData): ?>
                <div class="payment-details">
                    <div class="detail-row">
                        <span class="detail-label">Invoice</span>
                        <span class="detail-value">#<?php echo $invoiceData['tex']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Description</span>
                        <span class="detail-value"><?php echo htmlspecialchars($invoiceData['po_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Amount Paid</span>
                        <span class="detail-value" style="color:#27ae60;">฿<?php echo number_format($invoiceData['paid_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value"><?php echo ucfirst($gateway); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <?php if ($receiptId): ?>
                    <a href="rep-print.php?id=<?php echo $receiptId; ?>" target="_blank" class="btn btn-primary">
                        <i class="fa fa-print"></i> Print Receipt
                    </a>
                    <?php endif; ?>
                    <a href="inv.php?id=<?php echo $invoiceId; ?>" target="_blank" class="btn btn-secondary">
                        <i class="fa fa-file-text-o"></i> View Invoice
                    </a>
                </div>
                
            <?php else: ?>
                <h1>Payment Error</h1>
                <p><?php echo htmlspecialchars($error); ?></p>
                
                <div class="action-buttons">
                    <?php if ($invoiceId): ?>
                    <a href="index.php?page=inv_checkout&id=<?php echo $invoiceId; ?>" class="btn btn-primary">
                        <i class="fa fa-refresh"></i> Try Again
                    </a>
                    <?php endif; ?>
                    <a href="index.php?page=dashboard" class="btn btn-secondary">
                        <i class="fa fa-home"></i> Go to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
