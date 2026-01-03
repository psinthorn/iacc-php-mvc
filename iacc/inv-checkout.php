<?php
/**
 * Invoice Payment Checkout Page
 * Allows customers to pay invoices via PayPal or Stripe
 */

// This page can be accessed without full authentication (for customer payments)
// But needs a valid invoice token or invoice ID

session_start();
require_once __DIR__ . '/inc/sys.configs.php';
require_once __DIR__ . '/inc/class.dbconn.php';
require_once __DIR__ . '/inc/payment-method-helper.php';

$db = new DbConn($config);
$conn = $db->conn;

// Get invoice ID
$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token']) : '';

if (!$invoiceId) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Error</h2><p>Invalid invoice ID</p></div>');
}

// Fetch invoice data
$sql = "
    SELECT 
        po.id as po_id, po.name as po_name, po.dis, po.vat, po.over,
        iv.tex, iv.createdate, iv.payment_status, iv.paid_amount,
        pr.ven_id, pr.cus_id, pr.payby,
        ven.name_en as vendor_name, ven.logo as vendor_logo,
        cus.name_en as customer_name, cus.email as customer_email
    FROM po 
    JOIN pr ON po.ref = pr.id
    JOIN iv ON po.id = iv.tex
    LEFT JOIN company ven ON pr.ven_id = ven.id
    LEFT JOIN company cus ON pr.payby = cus.id
    WHERE po.id = ? 
    AND iv.deleted_at IS NULL
    AND iv.payment_status != 'paid'
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $invoiceId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Invoice Not Found</h2><p>The invoice does not exist, is already paid, or has been deleted.</p></div>');
}

// Calculate invoice total
$productsSql = "
    SELECT 
        product.price, product.quantity, product.valuelabour, 
        type.activelabour
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

// Apply discount
$discount = $subtotal * ($invoice['dis'] / 100);
$afterDiscount = $subtotal - $discount;

// Apply overhead
$overhead = 0;
if ($invoice['over'] > 0) {
    $overhead = $afterDiscount * ($invoice['over'] / 100);
    $afterDiscount += $overhead;
}

// Apply VAT
$vatAmount = $afterDiscount * ($invoice['vat'] / 100);
$grandTotal = round($afterDiscount + $vatAmount, 2);

// Amount remaining
$amountPaid = floatval($invoice['paid_amount']);
$amountDue = $grandTotal - $amountPaid;

// Get active payment gateways
$gateways = [];
$gwSql = "SELECT pm.id, pm.code, pm.name, pm.icon 
          FROM payment_method pm 
          WHERE pm.is_gateway = 1 AND pm.is_active = 1 
          ORDER BY pm.sort_order";
$gwResult = mysqli_query($conn, $gwSql);
while ($gw = mysqli_fetch_assoc($gwResult)) {
    // Check if gateway is configured
    $configSql = "SELECT COUNT(*) as cnt FROM payment_gateway_config 
                  WHERE payment_method_id = ? AND config_value != ''";
    $configStmt = mysqli_prepare($conn, $configSql);
    mysqli_stmt_bind_param($configStmt, "i", $gw['id']);
    mysqli_stmt_execute($configStmt);
    $configResult = mysqli_stmt_get_result($configStmt);
    $configCount = mysqli_fetch_assoc($configResult)['cnt'];
    
    if ($configCount > 0) {
        $gateways[] = $gw;
    }
}

// Handle payment initiation
$error = '';
$paymentUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gateway'])) {
    $selectedGateway = $_POST['gateway'];
    
    try {
        if ($selectedGateway === 'paypal') {
            require_once __DIR__ . '/inc/class.paypal.php';
            $paypal = new PayPalService($conn);
            
            $orderData = [
                'reference_id' => 'INV-' . $invoice['tex'],
                'invoice_id' => strval($invoice['tex']),
                'description' => 'Invoice #' . $invoice['tex'] . ' - ' . $invoice['po_name'],
                'currency' => 'THB',
                'total' => $amountDue,
                'items' => [
                    [
                        'name' => 'Invoice #' . $invoice['tex'],
                        'quantity' => 1,
                        'price' => $amountDue
                    ]
                ]
            ];
            
            $result = $paypal->createOrder($orderData);
            
            if ($result['success']) {
                // Update invoice with payment order ID
                $updateSql = "UPDATE iv SET payment_gateway = 'paypal', payment_order_id = ? WHERE tex = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($updateStmt, "si", $result['order_id'], $invoice['tex']);
                mysqli_stmt_execute($updateStmt);
                
                // Redirect to PayPal
                header('Location: ' . $result['approval_url']);
                exit;
            }
            
        } elseif ($selectedGateway === 'stripe') {
            require_once __DIR__ . '/inc/class.stripe.php';
            $stripe = new StripeService($conn);
            
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                       . '://' . $_SERVER['HTTP_HOST'];
            
            $sessionData = [
                'reference_id' => 'INV-' . $invoice['tex'],
                'invoice_id' => strval($invoice['tex']),
                'currency' => $stripe->getCurrency(),
                'success_url' => $baseUrl . '/index.php?page=inv_payment_success&session_id={CHECKOUT_SESSION_ID}&invoice_id=' . $invoice['tex'],
                'cancel_url' => $baseUrl . '/index.php?page=inv_payment_cancel&invoice_id=' . $invoice['tex'],
                'email' => $invoice['customer_email'],
                'items' => [
                    [
                        'name' => 'Invoice #' . $invoice['tex'] . ' - ' . $invoice['po_name'],
                        'quantity' => 1,
                        'price' => $amountDue
                    ]
                ]
            ];
            
            $result = $stripe->createCheckoutSession($sessionData);
            
            if ($result['success']) {
                // Update invoice with session ID
                $updateSql = "UPDATE iv SET payment_gateway = 'stripe', payment_order_id = ? WHERE tex = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($updateStmt, "si", $result['session_id'], $invoice['tex']);
                mysqli_stmt_execute($updateStmt);
                
                // Redirect to Stripe Checkout
                header('Location: ' . $result['checkout_url']);
                exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice #<?php echo $invoice['tex']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .checkout-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .checkout-header {
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .checkout-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            padding: 5px;
            margin-bottom: 15px;
        }
        
        .checkout-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .checkout-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .invoice-details {
            padding: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .amount-section {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }
        
        .amount-due {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .amount-due .label {
            font-size: 1.1rem;
            color: #333;
        }
        
        .amount-due .value {
            font-size: 2rem;
            font-weight: 700;
            color: #27ae60;
        }
        
        .currency {
            font-size: 1rem;
            font-weight: 400;
            color: #666;
        }
        
        .payment-section {
            padding: 25px;
        }
        
        .payment-section h3 {
            font-size: 1rem;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .gateway-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .gateway-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 25px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .gateway-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .gateway-btn.paypal {
            color: #003087;
            border-color: #003087;
        }
        
        .gateway-btn.paypal:hover {
            background: #003087;
            color: white;
        }
        
        .gateway-btn.stripe {
            color: #635bff;
            border-color: #635bff;
        }
        
        .gateway-btn.stripe:hover {
            background: #635bff;
            color: white;
        }
        
        .gateway-btn i {
            font-size: 1.5rem;
        }
        
        .secure-badge {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            color: #666;
            font-size: 0.85rem;
        }
        
        .secure-badge i {
            color: #27ae60;
            margin-right: 5px;
        }
        
        .error-message {
            background: #fce4e4;
            border: 1px solid #e74c3c;
            color: #c0392b;
            padding: 15px 20px;
            margin: 0 25px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .no-gateways {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        .no-gateways i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        @media (max-width: 480px) {
            .checkout-header {
                padding: 20px;
            }
            
            .amount-due .value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <?php if ($invoice['vendor_logo']): ?>
            <img src="pic/logo/<?php echo htmlspecialchars($invoice['vendor_logo']); ?>" alt="Logo">
            <?php else: ?>
            <div style="width:60px;height:60px;background:#fff;border-radius:50%;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;">
                <i class="fa fa-file-text-o" style="font-size:24px;color:#1a5276;"></i>
            </div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($invoice['vendor_name']); ?></h1>
            <p>Invoice Payment</p>
        </div>
        
        <div class="invoice-details">
            <div class="detail-row">
                <span class="detail-label">Invoice Number</span>
                <span class="detail-value">#<?php echo $invoice['tex']; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Description</span>
                <span class="detail-value"><?php echo htmlspecialchars($invoice['po_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Invoice Date</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime($invoice['createdate'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer</span>
                <span class="detail-value"><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
            </div>
            <?php if ($amountPaid > 0): ?>
            <div class="detail-row">
                <span class="detail-label">Amount Paid</span>
                <span class="detail-value" style="color:#27ae60;">฿<?php echo number_format($amountPaid, 2); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="amount-section">
            <div class="amount-due">
                <span class="label"><?php echo $amountPaid > 0 ? 'Amount Remaining' : 'Amount Due'; ?></span>
                <span class="value">฿<?php echo number_format($amountDue, 2); ?> <span class="currency">THB</span></span>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="payment-section">
            <?php if (count($gateways) > 0): ?>
            <h3>Select Payment Method</h3>
            <div class="gateway-options">
                <?php foreach ($gateways as $gateway): ?>
                <form method="POST" action="">
                    <input type="hidden" name="gateway" value="<?php echo htmlspecialchars($gateway['code']); ?>">
                    <button type="submit" class="gateway-btn <?php echo htmlspecialchars($gateway['code']); ?>">
                        <i class="fa fa-<?php echo $gateway['code'] === 'paypal' ? 'paypal' : 'cc-stripe'; ?>"></i>
                        Pay with <?php echo htmlspecialchars($gateway['name']); ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-gateways">
                <i class="fa fa-credit-card"></i>
                <p>Online payment is not available at this time.<br>Please contact us for alternative payment methods.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="secure-badge">
            <i class="fa fa-lock"></i> Secured with 256-bit SSL encryption
        </div>
    </div>
</body>
</html>
