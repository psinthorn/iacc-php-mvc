<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Invalid Link') ?></title>
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
    background: var(--red-lt); color: var(--red);
    box-shadow: 0 0 0 10px rgba(239,68,68,.08);
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; margin: 0 auto 24px;
}

.result-title { font-size: 22px; font-weight: 800; color: var(--dark); margin-bottom: 10px; letter-spacing: -.3px; }
.result-desc  { font-size: 14px; color: var(--muted); line-height: 1.65; }

.help-box {
    background: #f8fafc; border-radius: 12px; padding: 16px 18px;
    margin-top: 24px; text-align: left;
    border: 1px solid var(--border);
}
.help-box p { font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 10px; }
.help-box ul { font-size: 12.5px; color: var(--muted); line-height: 2.1; padding-left: 16px; }
.help-box ul li::marker { color: var(--teal); }

.close-note { font-size: 12px; color: #94a3b8; margin-top: 22px; line-height: 1.5; }
</style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-brand"><i class="fa fa-plane"></i>Tour Operator</div>
        <div class="topbar-sub">Secure Payment Portal</div>
    </div>
</div>

<div class="page">
    <div class="result-card">
        <div class="icon-circle"><i class="fa fa-exclamation-triangle"></i></div>
        <div class="result-title"><?= htmlspecialchars($title ?? 'Invalid Payment Link') ?></div>
        <div class="result-desc"><?= htmlspecialchars($message ?? 'This payment link is not valid or has expired.') ?></div>

        <div class="help-box">
            <p>What could have gone wrong?</p>
            <ul>
                <li>The link may have been copied incorrectly</li>
                <li>The payment has already been completed</li>
                <li>The link may have expired</li>
            </ul>
        </div>

        <div class="close-note">Please contact the tour operator for a new payment link.</div>
    </div>
</div>

</body>
</html>
