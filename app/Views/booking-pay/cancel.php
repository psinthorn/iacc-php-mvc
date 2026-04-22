<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Cancelled</title>
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
    --amber:   #f59e0b;
    --amber-lt:#fef3c7;
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
    max-width: 400px; width: 100%;
    box-shadow: 0 4px 28px rgba(0,0,0,.09);
    border: 1px solid var(--border);
    animation: fadeUp .4s ease both;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.icon-circle {
    width: 88px; height: 88px; border-radius: 50%;
    background: var(--amber-lt); color: var(--amber);
    box-shadow: 0 0 0 10px rgba(245,158,11,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; margin: 0 auto 24px;
}

.result-title { font-size: 22px; font-weight: 800; color: var(--dark); margin-bottom: 8px; letter-spacing: -.3px; }
.result-desc  { font-size: 14px; color: var(--muted); line-height: 1.65; margin-bottom: 28px; }

.no-charge-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 8px; padding: 7px 14px;
    font-size: 12.5px; font-weight: 600; color: #065f46;
    margin-bottom: 28px;
}
.no-charge-badge i { color: #10b981; }

.retry-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, var(--teal), var(--teal-dk));
    color: #fff; border-radius: 12px; padding: 13px 26px;
    font-size: 14px; font-weight: 700; text-decoration: none;
    font-family: inherit; border: none; cursor: pointer;
    transition: all .15s; box-shadow: 0 4px 14px rgba(13,148,136,.35);
}
.retry-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(13,148,136,.4); }
.retry-btn:active { transform: translateY(0); }

.close-note { font-size: 12px; color: #94a3b8; margin-top: 18px; }

.trust { display: flex; justify-content: center; gap: 18px; flex-wrap: wrap; margin-top: 28px; }
.trust-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #94a3b8; font-weight: 500; }
</style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-brand"><i class="fa fa-plane"></i>Tour Operator</div>
        <div class="topbar-sub">Secure Payment Portal</div>
    </div>
    <div class="topbar-secure"><i class="fa fa-lock"></i> SSL Secured</div>
</div>

<div class="page">
    <div class="result-card">
        <div class="icon-circle"><i class="fa fa-times-circle"></i></div>
        <div class="result-title">Payment Cancelled</div>
        <div class="result-desc">You cancelled the payment. No charge has been made to your account.</div>

        <div class="no-charge-badge">
            <i class="fa fa-check-circle"></i> No charge was made
        </div>

        <?php if ($bookingId && $token): ?>
        <a class="retry-btn" href="index.php?page=booking_pay&id=<?= intval($bookingId) ?>&token=<?= urlencode($token) ?>">
            <i class="fa fa-arrow-left"></i> Back to Payment Page
        </a>
        <?php endif; ?>

        <div class="close-note">You can try again whenever you're ready, or close this window.</div>

        <div class="trust">
            <div class="trust-item"><i class="fa fa-lock"></i> SSL Encrypted</div>
            <div class="trust-item"><i class="fa fa-shield"></i> Secure Payment</div>
        </div>
    </div>
</div>

</body>
</html>
