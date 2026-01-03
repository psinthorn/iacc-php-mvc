<?php
/**
 * Invoice Payment Cancel/Failed Callback
 * Handles cancelled or failed payments from PayPal/Stripe
 */

session_start();
require_once __DIR__ . '/inc/sys.configs.php';
require_once __DIR__ . '/inc/class.dbconn.php';

$db = new DbConn($config);
$conn = $db->conn;

$invoiceId = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$reason = isset($_GET['reason']) ? $_GET['reason'] : 'cancelled';

// Fetch invoice details for display
$invoiceData = null;
if ($invoiceId) {
    $sql = "SELECT iv.tex, po.name as po_name, c.name_en as vendor_name,
                   iv.payment_gateway
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
    
    // Clear the pending payment order ID since it was cancelled
    $clearSql = "UPDATE iv SET payment_order_id = NULL WHERE tex = ? AND payment_status = 'pending'";
    $clearStmt = mysqli_prepare($conn, $clearSql);
    mysqli_stmt_bind_param($clearStmt, "i", $invoiceId);
    mysqli_stmt_execute($clearStmt);
}

// Determine message based on reason
$title = 'Payment Cancelled';
$message = 'Your payment was cancelled. No charges have been made to your account.';
$icon = 'fa-times-circle';

if ($reason === 'failed') {
    $title = 'Payment Failed';
    $message = 'Your payment could not be processed. Please try again or use a different payment method.';
    $icon = 'fa-exclamation-triangle';
} elseif ($reason === 'expired') {
    $title = 'Payment Session Expired';
    $message = 'Your payment session has expired. Please start a new payment.';
    $icon = 'fa-clock-o';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
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
            color: #f39c12;
        }
        
        .icon-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(243, 156, 18, 0.1);
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
        
        .invoice-info {
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
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .help-text {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #856404;
        }
        
        .help-text i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-icon">
            <div class="icon-wrapper">
                <i class="fa <?php echo $icon; ?>"></i>
            </div>
        </div>
        
        <div class="result-content">
            <h1><?php echo $title; ?></h1>
            <p><?php echo $message; ?></p>
            
            <?php if ($invoiceData): ?>
            <div class="invoice-info">
                <div class="detail-row">
                    <span class="detail-label">Invoice</span>
                    <span class="detail-value">#<?php echo $invoiceData['tex']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span class="detail-value"><?php echo htmlspecialchars($invoiceData['po_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color:#f39c12;">Unpaid</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <?php if ($invoiceId): ?>
                <a href="index.php?page=inv_checkout&id=<?php echo $invoiceId; ?>" class="btn btn-primary">
                    <i class="fa fa-credit-card"></i> Try Again
                </a>
                <a href="inv.php?id=<?php echo $invoiceId; ?>" target="_blank" class="btn btn-secondary">
                    <i class="fa fa-file-text-o"></i> View Invoice
                </a>
                <?php else: ?>
                <a href="index.php?page=dashboard" class="btn btn-primary">
                    <i class="fa fa-home"></i> Go to Dashboard
                </a>
                <?php endif; ?>
            </div>
            
            <div class="help-text">
                <i class="fa fa-question-circle"></i>
                Need help? Contact us if you continue experiencing issues with your payment.
            </div>
        </div>
    </div>
</body>
</html>
