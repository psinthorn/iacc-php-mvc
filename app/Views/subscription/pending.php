<?php
$pageTitle = 'Payment Pending';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
$planName = $e(ucfirst($info['plan'] ?? ''));
$amount   = '฿' . number_format(floatval($info['amount'] ?? 0), 2);
$cycle    = $info['cycle'] ?? 'monthly';
?>
<div class="container-fluid" style="padding:24px 20px;">
    <div style="max-width:580px;margin:0 auto;text-align:center;padding:40px 20px;">
        <div style="width:80px;height:80px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:34px;color:white;">
            <i class="fa fa-clock-o"></i>
        </div>
        <h3 style="margin:0 0 8px;color:#334155;">Payment Pending</h3>
        <p style="color:#64748b;font-size:15px;margin-bottom:28px;">
            Please complete your payment to activate <strong><?= $planName ?></strong>.
        </p>

        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:24px;text-align:left;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,0,0,.05);">
            <h5 style="margin:0 0 16px;font-size:14px;font-weight:700;color:#334155;"><i class="fa fa-university"></i> Bank Transfer Details</h5>
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr><td style="padding:6px 0;color:#64748b;width:40%;">Bank</td><td style="font-weight:600;">Kasikorn Bank (KBank)</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Account Name</td><td style="font-weight:600;">iACC Co., Ltd.</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Account No.</td><td style="font-weight:600;">XXX-X-XXXXX-X</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Amount</td><td style="font-weight:800;font-size:18px;color:#6366f1;"><?= $amount ?></td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Ref</td><td style="font-weight:600;">#<?= intval($info['payment_id'] ?? 0) ?></td></tr>
            </table>
        </div>

        <p style="color:#94a3b8;font-size:13px;line-height:1.6;margin-bottom:24px;">
            After transferring, please send your slip to <a href="mailto:billing@iacc.app" style="color:#6366f1;">billing@iacc.app</a>
            with reference <strong>#<?= intval($info['payment_id'] ?? 0) ?></strong>. Your plan will be activated within 1 business day.
        </p>

        <a href="index.php?page=billing" class="btn btn-primary" style="border-radius:8px;padding:10px 24px;background:#6366f1;border-color:#6366f1;">
            <i class="fa fa-arrow-left"></i> Back to Billing
        </a>
    </div>
</div>
<?php unset($_SESSION['billing_pending']); ?>
