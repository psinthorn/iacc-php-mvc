<?php
/**
 * Invoice Payment Checkout View (standalone HTML)
 * Variables: $invoice, $totals, $gateways, $error
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice #<?= $invoice['tex'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .checkout-container { background: white; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); max-width: 500px; width: 100%; overflow: hidden; }
        .checkout-header { background: linear-gradient(135deg, #1a5276, #2980b9); color: white; padding: 30px; text-align: center; }
        .checkout-header img { width: 60px; height: 60px; border-radius: 50%; background: white; padding: 5px; margin-bottom: 15px; }
        .checkout-header h1 { font-size: 1.5rem; margin-bottom: 5px; }
        .checkout-header p { opacity: 0.9; font-size: 0.9rem; }
        .invoice-details { padding: 25px; border-bottom: 1px solid #eee; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { color: #666; }
        .detail-value { font-weight: 600; color: #333; }
        .amount-section { background: #f8f9fa; padding: 20px 25px; border-bottom: 1px solid #eee; }
        .amount-due { display: flex; justify-content: space-between; align-items: center; }
        .amount-due .label { font-size: 1.1rem; color: #333; }
        .amount-due .value { font-size: 2rem; font-weight: 700; color: #27ae60; }
        .currency { font-size: 1rem; font-weight: 400; color: #666; }
        .payment-section { padding: 25px; }
        .payment-section h3 { font-size: 1rem; color: #333; margin-bottom: 20px; text-align: center; }
        .gateway-options { display: flex; flex-direction: column; gap: 15px; }
        .gateway-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 18px 25px; border: 2px solid #e0e0e0; border-radius: 12px; background: white; cursor: pointer; font-size: 1.1rem; font-weight: 600; transition: all 0.3s ease; }
        .gateway-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .gateway-btn.paypal { color: #003087; border-color: #003087; }
        .gateway-btn.paypal:hover { background: #003087; color: white; }
        .gateway-btn.stripe { color: #635bff; border-color: #635bff; }
        .gateway-btn.stripe:hover { background: #635bff; color: white; }
        .gateway-btn i { font-size: 1.5rem; }
        .secure-badge { text-align: center; padding: 15px; background: #f8f9fa; color: #666; font-size: 0.85rem; }
        .secure-badge i { color: #27ae60; margin-right: 5px; }
        .error-message { background: #fce4e4; border: 1px solid #e74c3c; color: #c0392b; padding: 15px 20px; margin: 0 25px 20px; border-radius: 8px; font-size: 0.9rem; }
        .no-gateways { text-align: center; padding: 30px; color: #666; }
        .no-gateways i { font-size: 3rem; color: #ccc; margin-bottom: 15px; }
        @media (max-width: 480px) { .checkout-header { padding: 20px; } .amount-due .value { font-size: 1.5rem; } }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <?php if ($invoice['vendor_logo']): ?>
            <img src="pic/logo/<?= htmlspecialchars($invoice['vendor_logo']) ?>" alt="Logo">
            <?php else: ?>
            <div style="width:60px;height:60px;background:#fff;border-radius:50%;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;">
                <i class="fa fa-file-text-o" style="font-size:24px;color:#1a5276;"></i>
            </div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($invoice['vendor_name']) ?></h1>
            <p>Invoice Payment</p>
        </div>
        <div class="invoice-details">
            <div class="detail-row"><span class="detail-label">Invoice Number</span><span class="detail-value">#<?= $invoice['tex'] ?></span></div>
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value"><?= htmlspecialchars($invoice['po_name']) ?></span></div>
            <div class="detail-row"><span class="detail-label">Invoice Date</span><span class="detail-value"><?= date('d/m/Y', strtotime($invoice['createdate'])) ?></span></div>
            <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-value"><?= htmlspecialchars($invoice['customer_name']) ?></span></div>
            <?php if ($totals['amountPaid'] > 0): ?>
            <div class="detail-row"><span class="detail-label">Amount Paid</span><span class="detail-value" style="color:#27ae60;">฿<?= number_format($totals['amountPaid'], 2) ?></span></div>
            <?php endif; ?>
        </div>
        <div class="amount-section">
            <div class="amount-due">
                <span class="label"><?= $totals['amountPaid'] > 0 ? 'Amount Remaining' : 'Amount Due' ?></span>
                <span class="value">฿<?= number_format($totals['amountDue'], 2) ?> <span class="currency">THB</span></span>
            </div>
        </div>
        <?php if ($error): ?>
        <div class="error-message"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="payment-section">
            <?php if (count($gateways) > 0): ?>
            <h3>Select Payment Method</h3>
            <div class="gateway-options">
                <?php foreach ($gateways as $gw): ?>
                <form method="POST"><?= csrf_field() ?><input type="hidden" name="gateway" value="<?= htmlspecialchars($gw['code']) ?>">
                    <button type="submit" class="gateway-btn <?= htmlspecialchars($gw['code']) ?>">
                        <i class="fa fa-<?= $gw['code'] === 'paypal' ? 'paypal' : 'cc-stripe' ?>"></i>
                        Pay with <?= htmlspecialchars($gw['name']) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-gateways"><i class="fa fa-credit-card"></i><p>Online payment is not available at this time.<br>Please contact us for alternative payment methods.</p></div>
            <?php endif; ?>
        </div>
        <div class="secure-badge"><i class="fa fa-lock"></i> Secured with 256-bit SSL encryption</div>
    </div>
</body>
</html>
