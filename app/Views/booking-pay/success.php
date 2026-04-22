<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $error ? 'Payment Failed' : 'Payment Confirmed' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
:root {
    --teal:    #0d9488;
    --teal-dk: #0f766e;
    --dark:    #0f172a;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --bg:      #f1f5f9;
    --white:   #ffffff;
    --green:   #10b981;
    --green-lt:#d1fae5;
    --amber:   #f59e0b;
    --amber-lt:#fef3c7;
    --red:     #ef4444;
    --red-lt:  #fee2e2;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: var(--bg); min-height: 100vh;
    display: flex; flex-direction: column;
}

/* ── Top bar ───────────────────────── */
.topbar {
    background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dk) 100%);
    padding: 14px 20px; display: flex; align-items: center; gap: 12px;
    box-shadow: 0 2px 12px rgba(13,148,136,.3);
}
.topbar-brand { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
.topbar-brand i { margin-right: 6px; opacity: .9; }
.topbar-sub { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 1px; font-weight: 500; }
.topbar-secure { margin-left: auto; display: flex; align-items: center; gap: 5px; font-size: 12px; color: rgba(255,255,255,.85); font-weight: 600; background: rgba(255,255,255,.15); padding: 5px 10px; border-radius: 20px; }

/* ── Main ──────────────────────────── */
.page { flex: 1; display: flex; align-items: center; justify-content: center; padding: 32px 16px; }

.result-card {
    background: var(--white); border-radius: 24px;
    padding: 44px 36px; text-align: center;
    max-width: 420px; width: 100%;
    box-shadow: 0 4px 28px rgba(0,0,0,.09);
    border: 1px solid var(--border);
    animation: fadeUp .4s ease both;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Status icon circle ────────────── */
.icon-circle {
    width: 88px; height: 88px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; margin: 0 auto 24px;
}
.icon-circle.ok      { background: var(--green-lt); color: var(--green); box-shadow: 0 0 0 10px rgba(16,185,129,.1); }
.icon-circle.pending { background: var(--amber-lt); color: var(--amber); box-shadow: 0 0 0 10px rgba(245,158,11,.1); }
.icon-circle.err     { background: var(--red-lt);   color: var(--red);   box-shadow: 0 0 0 10px rgba(239,68,68,.1); }

.result-title { font-size: 22px; font-weight: 800; color: var(--dark); margin-bottom: 8px; letter-spacing: -.3px; }
.result-desc  { font-size: 14px; color: var(--muted); line-height: 1.65; }

/* Booking ref pill */
.ref-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: #f0fdf4; border-radius: 10px;
    padding: 11px 20px; margin: 22px 0;
    font-size: 15px; font-weight: 700; color: #065f46;
    border: 1px solid #bbf7d0;
}
.ref-pill i { color: var(--green); }

/* Amount line */
.amount-line {
    font-size: 28px; font-weight: 900; color: var(--green);
    letter-spacing: -1px; margin: 6px 0 18px;
    font-variant-numeric: tabular-nums;
}
.amount-line.pending { color: var(--amber); }
.amount-line.err     { color: var(--red); }
.amount-line span { font-size: 16px; font-weight: 700; vertical-align: top; padding-top: 5px; display: inline-block; margin-right: 2px; }

/* Close note */
.close-note { font-size: 12px; color: #94a3b8; margin-top: 20px; line-height: 1.5; }

/* Steps for pending */
.pending-steps { background: var(--amber-lt); border-radius: 12px; padding: 14px 16px; margin: 16px 0; text-align: left; }
.pending-steps p { font-size: 13px; color: #78350f; font-weight: 600; margin-bottom: 8px; }
.pending-steps ul { font-size: 12.5px; color: #92400e; padding-left: 16px; line-height: 2; }

/* Trust footer */
.trust { display: flex; justify-content: center; gap: 18px; flex-wrap: wrap; margin-top: 24px; }
.trust-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #94a3b8; font-weight: 500; }

<?php
$isPending = !$error && isset($amount) && floatval($amount) === 0.0;
$isError   = !empty($error);
$isOk      = !$isError && !$isPending;
?>
</style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-brand"><i class="fa fa-plane"></i><?= htmlspecialchars($booking['company_name'] ?? 'Tour Operator') ?></div>
        <div class="topbar-sub">Secure Payment Portal</div>
    </div>
    <div class="topbar-secure"><i class="fa fa-lock"></i> SSL Secured</div>
</div>

<div class="page">
    <div class="result-card">

        <?php if ($isError): ?>
        <!-- Error / Failed -->
        <div class="icon-circle err"><i class="fa fa-times-circle"></i></div>
        <div class="result-title">Payment Failed</div>
        <div class="result-desc"><?= htmlspecialchars($error) ?></div>
        <div class="ref-pill">
            <i class="fa fa-calendar-check-o"></i>
            <?= htmlspecialchars($bookingNumber ?? '') ?>
        </div>
        <div class="close-note">Please try again or contact the tour operator for assistance.</div>

        <?php elseif ($isPending): ?>
        <!-- PromptPay slip submitted -->
        <div class="icon-circle pending"><i class="fa fa-clock-o"></i></div>
        <div class="result-title">Slip Submitted!</div>
        <div class="result-desc">Your payment slip has been received and is awaiting admin review.</div>
        <div class="ref-pill">
            <i class="fa fa-calendar-check-o"></i>
            <?= htmlspecialchars($bookingNumber ?? '') ?>
        </div>
        <div class="pending-steps">
            <p><i class="fa fa-info-circle"></i> What happens next?</p>
            <ul>
                <li>Admin reviews your slip (usually within a few hours)</li>
                <li>Your booking will be confirmed once approved</li>
                <li>You'll be contacted if there are any issues</li>
            </ul>
        </div>
        <div class="close-note">You can safely close this window. We'll be in touch soon.</div>

        <?php else: ?>
        <!-- Success -->
        <div class="icon-circle ok"><i class="fa fa-check-circle"></i></div>
        <div class="result-title">Payment Confirmed!</div>
        <?php if (isset($amount) && floatval($amount) > 0): ?>
        <div class="amount-line"><span>฿</span><?= number_format(floatval($amount), 2) ?></div>
        <?php endif; ?>
        <div class="result-desc">Your payment has been received and confirmed. Thank you for booking with us!</div>
        <div class="ref-pill">
            <i class="fa fa-calendar-check-o"></i>
            <?= htmlspecialchars($bookingNumber ?? '') ?>
        </div>
        <div class="close-note">You can close this window. The tour operator will be in touch with your booking details.</div>

        <?php endif; ?>

        <div class="trust">
            <div class="trust-item"><i class="fa fa-lock"></i> SSL Encrypted</div>
            <div class="trust-item"><i class="fa fa-shield"></i> Secure Payment</div>
        </div>
    </div>
</div>

</body>
</html>
