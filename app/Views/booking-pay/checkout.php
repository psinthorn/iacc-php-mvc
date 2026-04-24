<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pay for Booking <?= htmlspecialchars($booking['booking_number']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
:root {
    --teal:    #0d9488;
    --teal-dk: #0f766e;
    --teal-lt: #ccfbf1;
    --dark:    #0f172a;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --bg:      #f1f5f9;
    --white:   #ffffff;
    --green:   #10b981;
    --green-lt:#d1fae5;
    --red:     #ef4444;
    --red-lt:  #fee2e2;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg); min-height: 100vh; color: var(--dark); }

/* ── Top bar ───────────────────────── */
.topbar {
    position: sticky; top: 0; z-index: 50;
    background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dk) 100%);
    padding: 14px 20px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 2px 12px rgba(13,148,136,.35);
}
.topbar-brand { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
.topbar-brand i { margin-right: 6px; opacity: .9; }
.topbar-sub { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 1px; font-weight: 500; }
.topbar-secure { margin-left: auto; display: flex; align-items: center; gap: 5px; font-size: 12px; color: rgba(255,255,255,.85); font-weight: 600; background: rgba(255,255,255,.15); padding: 5px 10px; border-radius: 20px; }

/* ── Page layout ───────────────────── */
.page { max-width: 560px; margin: 28px auto 56px; padding: 0 16px; }

/* ── Booking summary card ──────────── */
.bk-card { background: var(--white); border-radius: 20px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.07); margin-bottom: 16px; border: 1px solid var(--border); }
.bk-header { background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dk) 100%); padding: 20px 22px; }
.bk-header-ref { font-size: 12px; color: rgba(255,255,255,.7); font-weight: 600; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 4px; }
.bk-header-num { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
.bk-header-name { font-size: 13px; color: rgba(255,255,255,.8); margin-top: 4px; font-weight: 500; }
.bk-body { padding: 18px 22px; }
.bk-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid #f8fafc; font-size: 13.5px; }
.bk-row:last-child { border-bottom: none; }
.bk-row-label { color: var(--muted); display: flex; align-items: center; gap: 7px; }
.bk-row-label i { width: 14px; text-align: center; opacity: .7; }
.bk-row-value { font-weight: 600; color: var(--dark); }
.bk-paid-note { font-size: 12px; color: var(--green); background: var(--green-lt); border-radius: 6px; padding: 4px 10px; font-weight: 600; margin-top: 2px; align-self: flex-end; }

/* Amount due hero */
.amount-hero { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius: 14px; padding: 16px 20px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center; border: 1.5px solid #bbf7d0; }
.amount-hero-label { font-size: 12px; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: .6px; }
.amount-hero-amount { font-size: 30px; font-weight: 900; color: #059669; letter-spacing: -1px; font-variant-numeric: tabular-nums; }
.amount-hero-amount span { font-size: 17px; font-weight: 700; margin-right: 2px; vertical-align: top; padding-top: 5px; display: inline-block; }

/* ── Paid banner ───────────────────── */
.paid-banner { background: var(--white); border-radius: 20px; padding: 32px 24px; text-align: center; box-shadow: 0 1px 6px rgba(0,0,0,.07); border: 2px solid var(--green); }
.paid-banner-icon { font-size: 52px; color: var(--green); margin-bottom: 14px; }
.paid-banner h2 { font-size: 20px; font-weight: 800; color: #065f46; margin-bottom: 6px; }
.paid-banner p { font-size: 14px; color: #059669; }

/* ── Flash message ─────────────────── */
.flash { display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: 12px; font-size: 13px; font-weight: 500; margin-bottom: 16px; }
.flash i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.flash.error   { background: var(--red-lt); color: #991b1b; border-left: 4px solid var(--red); }
.flash.success { background: var(--green-lt); color: #065f46; border-left: 4px solid var(--green); }

/* ── Payment section ───────────────── */
.pay-card { background: var(--white); border-radius: 20px; box-shadow: 0 1px 6px rgba(0,0,0,.07); border: 1px solid var(--border); padding: 22px; margin-bottom: 16px; }
.pay-card-title { font-size: 14px; font-weight: 700; color: var(--dark); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
.pay-card-title i { color: var(--teal); font-size: 16px; }

/* Gateway buttons */
.gw-btn {
    display: flex; align-items: center; gap: 14px; width: 100%;
    padding: 15px 18px; margin-bottom: 10px;
    border-radius: 14px; border: 1.5px solid; cursor: pointer; background: var(--white);
    font-family: inherit; transition: all .15s ease; text-align: left;
    appearance: none; -webkit-appearance: none;
}
.gw-btn:last-of-type { margin-bottom: 0; }
.gw-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
.gw-btn:active { transform: translateY(0); }
.gw-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.gw-text { flex: 1; }
.gw-title { font-size: 14px; font-weight: 700; }
.gw-desc  { font-size: 12px; opacity: .65; margin-top: 2px; font-weight: 500; }
.gw-arrow { font-size: 12px; opacity: .35; }

.gw-stripe    { color: #635bff; border-color: #ede9ff; }
.gw-stripe    .gw-icon { background: #ede9ff; color: #635bff; }
.gw-paypal    { color: #0070ba; border-color: #dbeafe; }
.gw-paypal    .gw-icon { background: #dbeafe; color: #0070ba; }
.gw-promptpay { color: #0891b2; border-color: #e0f2fe; }
.gw-promptpay .gw-icon { background: #e0f2fe; color: #0891b2; }

/* Partial section */
.partial-toggle { margin-top: 18px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.partial-toggle summary { font-size: 12.5px; color: var(--muted); cursor: pointer; user-select: none; font-weight: 500; list-style: none; display: flex; align-items: center; gap: 6px; }
.partial-toggle summary:hover { color: #475569; }
.partial-toggle summary::before { content: '\f055'; font-family: FontAwesome; font-size: 13px; color: var(--teal); }
.partial-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
.f-group label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
.f-group input, .f-group select {
    width: 100%; padding: 9px 12px; border: 1.5px solid var(--border); border-radius: 9px;
    font-size: 13px; font-family: inherit; background: #f8fafc; color: var(--dark);
    transition: border-color .15s;
}
.f-group input:focus, .f-group select:focus { outline: none; border-color: var(--teal); background: var(--white); }

/* No gateways */
.no-gw { text-align: center; padding: 20px 0 8px; }
.no-gw i { font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block; }
.no-gw p { font-size: 13px; color: var(--muted); line-height: 1.6; }

/* ── Trust footer ──────────────────── */
.trust { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 24px; }
.trust-item { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: #94a3b8; font-weight: 500; }
.trust-item i { font-size: 12px; }

@media (max-width: 480px) {
    .partial-inputs { grid-template-columns: 1fr; }
    .page { margin-top: 16px; }
    .amount-hero-amount { font-size: 26px; }
}
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

    <?php if ($flash): ?>
    <div class="flash <?= htmlspecialchars($flash['type']) ?>">
        <i class="fa fa-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Booking Summary -->
    <div class="bk-card">
        <div class="bk-header">
            <div class="bk-header-ref"><i class="fa fa-calendar-check-o"></i> &nbsp;Booking Reference</div>
            <div class="bk-header-num"><?= htmlspecialchars($booking['booking_number']) ?></div>
            <?php if (!empty($contact['contact_name'])): ?>
            <div class="bk-header-name"><i class="fa fa-user"></i> <?= htmlspecialchars($contact['contact_name']) ?></div>
            <?php endif; ?>
        </div>
        <div class="bk-body">
            <?php if (!empty($booking['travel_date'])): ?>
            <div class="bk-row">
                <span class="bk-row-label"><i class="fa fa-plane"></i> Trip Date</span>
                <span class="bk-row-value"><?= date('d M Y', strtotime($booking['travel_date'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="bk-row">
                <span class="bk-row-label"><i class="fa fa-users"></i> Passengers</span>
                <span class="bk-row-value"><?= intval($booking['total_pax']) ?> pax</span>
            </div>
            <div class="bk-row">
                <span class="bk-row-label"><i class="fa fa-tag"></i> Booking Total</span>
                <span class="bk-row-value">฿<?= number_format($booking['total_amount'], 2) ?></span>
            </div>
            <?php $amountPaid = floatval($summary['net_paid'] ?? $booking['amount_paid'] ?? 0); ?>
            <?php if ($amountPaid > 0): ?>
            <div class="bk-row">
                <span class="bk-row-label"><i class="fa fa-check"></i> Amount Paid</span>
                <span class="bk-paid-note">฿<?= number_format($amountPaid, 2) ?></span>
            </div>
            <?php endif; ?>

            <div class="amount-hero">
                <div>
                    <div class="amount-hero-label">Amount Due</div>
                </div>
                <div class="amount-hero-amount"><span>฿</span><?= number_format($amountDue, 2) ?></div>
            </div>
        </div>
    </div>

    <?php if ($amountDue <= 0): ?>
    <!-- Already fully paid -->
    <div class="paid-banner">
        <div class="paid-banner-icon"><i class="fa fa-check-circle"></i></div>
        <h2>Payment Complete</h2>
        <p>This booking has been fully paid. Thank you!</p>
    </div>

    <?php elseif (!empty($activeGateways)): ?>
    <!-- Payment options -->
    <div class="pay-card">
        <div class="pay-card-title"><i class="fa fa-credit-card"></i> Choose Payment Method</div>

        <?php
        $gwMeta = [
            'stripe'    => ['class' => 'gw-stripe',    'icon' => 'fa-credit-card', 'title' => 'Card Payment',  'desc' => 'Visa, Mastercard, Amex via Stripe'],
            'paypal'    => ['class' => 'gw-paypal',    'icon' => 'fa-paypal',      'title' => 'PayPal',        'desc' => 'Pay with your PayPal account'],
            'promptpay' => ['class' => 'gw-promptpay', 'icon' => 'fa-qrcode',      'title' => 'PromptPay QR', 'desc' => 'Scan with any Thai banking app'],
        ];
        $allowedGateways = ['stripe', 'paypal', 'promptpay'];
        foreach ($activeGateways as $gw):
            $code = strtolower($gw['code'] ?? '');
            if (!in_array($code, $allowedGateways, true)) continue;
            $meta = $gwMeta[$code] ?? ['class' => '', 'icon' => 'fa-plug', 'title' => $gw['name'], 'desc' => ''];
        ?>
        <form method="post" action="index.php?page=booking_pay_checkout" class="gw-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id"           value="<?= $bookingId ?>">
            <input type="hidden" name="token"        value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="gateway"      value="<?= htmlspecialchars($code) ?>">
            <input type="hidden" name="amount"       class="amount-field" value="<?= $amountDue ?>">
            <input type="hidden" name="payment_type" class="type-field"   value="full">
            <button type="submit" class="gw-btn <?= $meta['class'] ?>">
                <div class="gw-icon"><i class="fa <?= $meta['icon'] ?>"></i></div>
                <div class="gw-text">
                    <div class="gw-title"><?= htmlspecialchars($meta['title']) ?></div>
                    <div class="gw-desc"><?= htmlspecialchars($meta['desc']) ?></div>
                </div>
                <div class="gw-arrow"><i class="fa fa-chevron-right"></i></div>
            </button>
        </form>
        <?php endforeach; ?>

        <!-- Partial / deposit -->
        <details class="partial-toggle">
            <summary>Pay a deposit or partial amount instead</summary>
            <div class="partial-inputs">
                <div class="f-group">
                    <label>Amount (฿)</label>
                    <input type="number" id="partialAmt" step="0.01" min="1" max="<?= $amountDue ?>" value="<?= $amountDue ?>">
                </div>
                <div class="f-group">
                    <label>Payment Type</label>
                    <select id="partialType">
                        <option value="full">Full Payment</option>
                        <option value="deposit">Deposit</option>
                        <option value="partial">Partial</option>
                    </select>
                </div>
            </div>
        </details>
    </div>

    <?php else: ?>
    <!-- No gateways configured -->
    <div class="pay-card">
        <div class="pay-card-title"><i class="fa fa-bank"></i> Bank Transfer</div>
        <div class="no-gw">
            <i class="fa fa-info-circle"></i>
            <p>Please transfer ฿<?= number_format($amountDue, 2) ?> and contact the tour operator to share your payment slip.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="trust">
        <div class="trust-item"><i class="fa fa-lock"></i> SSL Encrypted</div>
        <div class="trust-item"><i class="fa fa-shield"></i> Secure Payments</div>
        <div class="trust-item"><i class="fa fa-eye-slash"></i> Data Protected</div>
    </div>
</div>

<script>
(function() {
    var amtInput  = document.getElementById('partialAmt');
    var typeInput = document.getElementById('partialType');
    if (!amtInput) return;
    function sync() {
        document.querySelectorAll('.amount-field').forEach(function(el) { el.value = amtInput.value; });
        document.querySelectorAll('.type-field').forEach(function(el)   { el.value = typeInput.value; });
    }
    amtInput.addEventListener('input', sync);
    typeInput.addEventListener('change', sync);
})();
</script>
</body>
</html>
