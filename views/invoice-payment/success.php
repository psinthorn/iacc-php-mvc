<?php
/**
 * Invoice Payment Success View (standalone HTML)
 * Variables: $success, $error, $receiptId, $invoiceId, $invoiceData, $gateway
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'Payment Successful' : 'Payment Error' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        body.success { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); }
        body.error { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .result-container { background: white; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); max-width: 500px; width: 100%; text-align: center; overflow: hidden; }
        .result-icon { padding: 40px 30px 20px; }
        .result-icon i { font-size: 80px; }
        .result-icon.success i { color: #27ae60; }
        .result-icon.error i { color: #e74c3c; }
        .checkmark-animation { width: 100px; height: 100px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(39, 174, 96, 0.1); animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .result-content { padding: 0 30px 30px; }
        .result-content h1 { font-size: 1.8rem; margin-bottom: 10px; color: #333; }
        .result-content p { color: #666; line-height: 1.6; margin-bottom: 20px; }
        .payment-details { background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: left; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { color: #666; }
        .detail-value { font-weight: 600; color: #333; }
        .action-buttons { display: flex; flex-direction: column; gap: 12px; margin-top: 25px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 15px 25px; border-radius: 10px; font-size: 1rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer; }
        .btn-primary { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3); }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .receipt-badge { display: inline-block; background: rgba(39, 174, 96, 0.1); color: #27ae60; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem; margin-bottom: 20px; }
        .receipt-badge i { margin-right: 5px; }
    </style>
</head>
<body class="<?= $success ? 'success' : 'error' ?>">
    <div class="result-container">
        <div class="result-icon <?= $success ? 'success' : 'error' ?>">
            <?php if ($success): ?>
            <div class="checkmark-animation"><i class="fa fa-check-circle"></i></div>
            <?php else: ?>
            <i class="fa fa-times-circle"></i>
            <?php endif; ?>
        </div>
        <div class="result-content">
            <?php if ($success): ?>
                <h1>Payment Successful!</h1>
                <p>Thank you for your payment. A receipt has been automatically generated.</p>
                <?php if ($receiptId): ?>
                <div class="receipt-badge"><i class="fa fa-file-text-o"></i> Receipt #<?= $receiptId ?> Created</div>
                <?php endif; ?>
                <?php if ($invoiceData): ?>
                <div class="payment-details">
                    <div class="detail-row"><span class="detail-label">Invoice</span><span class="detail-value">#<?= $invoiceData['tex'] ?></span></div>
                    <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value"><?= htmlspecialchars($invoiceData['po_name']) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Amount Paid</span><span class="detail-value" style="color:#27ae60;">฿<?= number_format($invoiceData['paid_amount'], 2) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Payment Method</span><span class="detail-value"><?= ucfirst($gateway) ?></span></div>
                </div>
                <?php endif; ?>
                <div class="action-buttons">
                    <?php if ($receiptId): ?>
                    <a href="rep-print.php?id=<?= $receiptId ?>" target="_blank" class="btn btn-primary"><i class="fa fa-print"></i> Print Receipt</a>
                    <?php endif; ?>
                    <a href="inv.php?id=<?= $invoiceId ?>" target="_blank" class="btn btn-secondary"><i class="fa fa-file-text-o"></i> View Invoice</a>
                </div>
            <?php else: ?>
                <h1>Payment Error</h1>
                <p><?= htmlspecialchars($error) ?></p>
                <div class="action-buttons">
                    <?php if ($invoiceId): ?>
                    <a href="index.php?page=inv_checkout&id=<?= $invoiceId ?>" class="btn btn-primary"><i class="fa fa-refresh"></i> Try Again</a>
                    <?php endif; ?>
                    <a href="index.php?page=dashboard" class="btn btn-secondary"><i class="fa fa-home"></i> Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
