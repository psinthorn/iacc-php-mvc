<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>PromptPay — <?= htmlspecialchars($booking['booking_number']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --pp:      #003F88;
    --pp-lt:   #e8f0fb;
    --pp-mid:  #1a5bb5;
    --accent:  #F7941D;
    --green:   #10b981;
    --green-lt:#d1fae5;
    --red:     #ef4444;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --bg:      #f1f5f9;
    --card:    #ffffff;
    --dark:    #0f172a;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: var(--bg);
    color: var(--dark);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

/* ── Topbar ─────────────────────────────────── */
.topbar {
    background: var(--pp);
    height: 54px; padding: 0 18px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 12px rgba(0,63,136,.3);
}
.topbar-left { display: flex; align-items: center; gap: 10px; }
.topbar-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #fff;
}
.topbar-name { font-size: 14px; font-weight: 700; color: #fff; }
.topbar-sub  { font-size: 10px; color: rgba(255,255,255,.65); margin-top: 1px; }
.topbar-secure {
    display: flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600; color: rgba(255,255,255,.8);
    background: rgba(255,255,255,.12); border-radius: 20px; padding: 4px 10px;
}
.topbar-secure svg { width: 11px; height: 11px; fill: currentColor; }

/* ── Page ────────────────────────────────────── */
.page { max-width: 460px; margin: 0 auto; padding: 20px 14px 56px; }

/* ── Progress bar ────────────────────────────── */
.progress-bar {
    display: flex; align-items: center; justify-content: center;
    gap: 0; margin-bottom: 20px;
}
.pb-step {
    display: flex; flex-direction: column; align-items: center; gap: 5px;
    flex: 1;
}
.pb-dot {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800; position: relative; z-index: 1;
}
.pb-dot.done  { background: var(--green); color: #fff; }
.pb-dot.active { background: var(--pp); color: #fff; box-shadow: 0 0 0 4px rgba(0,63,136,.15); }
.pb-dot.idle  { background: #e2e8f0; color: #94a3b8; }
.pb-label { font-size: 10px; font-weight: 600; color: var(--muted); text-align: center; }
.pb-label.active { color: var(--pp); }
.pb-connector { flex: 1; height: 2px; background: #e2e8f0; margin-bottom: 15px; max-width: 40px; }
.pb-connector.done { background: var(--green); }

/* ── Amount hero ─────────────────────────────── */
.amount-hero {
    background: linear-gradient(150deg, var(--pp) 0%, var(--pp-mid) 100%);
    border-radius: 20px; padding: 24px 20px 20px;
    text-align: center; color: #fff;
    margin-bottom: 14px;
    box-shadow: 0 8px 28px rgba(0,63,136,.3);
    position: relative; overflow: hidden;
}
.amount-hero::after {
    content: ''; position: absolute;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.04);
    bottom: -60px; right: -40px; pointer-events: none;
}
.ah-label { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; opacity: .7; margin-bottom: 6px; }
.ah-amount { font-size: 52px; font-weight: 900; letter-spacing: -2px; line-height: 1; font-variant-numeric: tabular-nums; }
.ah-amount sup { font-size: 22px; font-weight: 700; vertical-align: top; margin-top: 10px; display: inline-block; margin-right: 2px; letter-spacing: 0; }
.ah-ref { font-size: 12px; opacity: .65; margin-top: 8px; }
.ah-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
    border-radius: 20px; padding: 5px 12px;
    font-size: 11px; font-weight: 700; margin-top: 12px;
    letter-spacing: .02em;
}
.ah-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); }

/* ── Card ────────────────────────────────────── */
.card {
    background: var(--card); border-radius: 18px;
    border: 1px solid var(--border);
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
    margin-bottom: 12px; overflow: hidden;
}
.card-hd {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 18px 0;
    font-size: 12px; font-weight: 700; color: var(--muted);
    text-transform: uppercase; letter-spacing: .06em;
}
.card-hd-icon {
    width: 26px; height: 26px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
}

/* ── QR Section ──────────────────────────────── */
.qr-body { padding: 18px 20px 20px; text-align: center; }
.qr-frame {
    display: inline-flex; flex-direction: column; align-items: center;
    background: #fff;
    border: 3px solid var(--pp-lt);
    border-radius: 20px;
    padding: 14px 14px 10px;
    box-shadow: 0 0 0 7px rgba(0,63,136,.06), 0 4px 20px rgba(0,63,136,.1);
    margin-bottom: 16px;
    position: relative;
}
.qr-frame img { display: block; width: 220px; height: 220px; object-fit: contain; border-radius: 4px; }
.qr-loading { width: 220px; height: 220px; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 48px; }
.qr-pp-label {
    font-size: 10px; font-weight: 800; color: var(--pp);
    letter-spacing: .08em; text-transform: uppercase;
    margin-top: 8px; opacity: .7;
}
.qr-account { margin-bottom: 4px; }
.qr-account-name { font-size: 15px; font-weight: 700; color: var(--dark); }
.qr-account-id {
    font-size: 12px; color: var(--muted); margin-top: 3px;
    font-family: 'Courier New', monospace; letter-spacing: .04em;
}
.qr-actions { display: flex; gap: 8px; justify-content: center; margin-top: 4px; }
.qr-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none;
    border: 1.5px solid var(--border); background: #f8fafc; color: var(--muted);
    transition: all .15s; font-family: inherit;
}
.qr-btn:hover { border-color: var(--pp); color: var(--pp); background: var(--pp-lt); }

/* ── Steps ───────────────────────────────────── */
.steps { padding: 14px 18px 18px; }
.step {
    display: flex; align-items: flex-start; gap: 13px;
    padding: 10px 0; border-bottom: 1px solid #f8fafc;
}
.step:last-child { border-bottom: none; }
.step-num {
    width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
    background: var(--pp-lt); color: var(--pp);
    font-size: 11px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    margin-top: 1px;
}
.step-title { font-size: 13px; font-weight: 600; color: var(--dark); }
.step-desc  { font-size: 12px; color: var(--muted); margin-top: 3px; line-height: 1.55; }
.bank-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.bank-chip {
    display: flex; align-items: center; gap: 5px;
    padding: 4px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 700; border: 1.5px solid;
}

/* ── Upload ──────────────────────────────────── */
.upload-body { padding: 16px 18px 18px; }
.upload-zone {
    border: 2px dashed #cbd5e1; border-radius: 14px;
    padding: 28px 16px; text-align: center;
    cursor: pointer; transition: all .2s;
    background: #fafbfc; position: relative;
}
.upload-zone:hover, .upload-zone.drag-over { border-color: var(--pp); background: var(--pp-lt); }
.upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.upload-icon { font-size: 32px; color: #cbd5e1; margin-bottom: 8px; transition: color .2s; }
.upload-zone:hover .upload-icon, .upload-zone.drag-over .upload-icon { color: var(--pp); }
.upload-title { font-size: 14px; font-weight: 600; color: #475569; }
.upload-sub   { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.upload-zone.has-file { border-color: var(--green); background: var(--green-lt); }
.upload-zone.has-file .upload-icon { color: var(--green); }
.upload-zone.has-file .upload-title { color: #065f46; }
.slip-preview { display: none; margin-top: 12px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); position: relative; }
.slip-preview img { width: 100%; max-height: 220px; object-fit: cover; display: block; }
.slip-remove {
    position: absolute; top: 8px; right: 8px;
    width: 28px; height: 28px; border-radius: 50%;
    background: rgba(0,0,0,.55); color: #fff; border: none;
    cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center;
}

/* ── Form ────────────────────────────────────── */
.f-group { margin-top: 14px; }
.f-label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
.f-opt   { font-size: 11px; font-weight: 400; color: #94a3b8; text-transform: none; letter-spacing: 0; }
.f-input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid var(--border); border-radius: 10px;
    font-size: 14px; font-family: inherit; color: var(--dark);
    background: #fff; outline: none; transition: border-color .15s;
}
.f-input:focus { border-color: var(--pp); box-shadow: 0 0 0 3px rgba(0,63,136,.1); }
.f-hint { font-size: 11px; color: #94a3b8; margin-top: 5px; }

/* ── Submit ──────────────────────────────────── */
.btn-submit {
    width: 100%; margin-top: 18px; padding: 15px;
    background: linear-gradient(135deg, var(--pp), var(--pp-mid));
    color: #fff; border: none; border-radius: 13px;
    font-size: 15px; font-weight: 700; font-family: inherit;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 16px rgba(0,63,136,.35);
    transition: all .2s; letter-spacing: .01em;
}
.btn-submit:hover   { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(0,63,136,.45); }
.btn-submit:active  { transform: none; }
.btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Back link ───────────────────────────────── */
.btn-back {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 16px; padding: 10px 0;
    font-size: 13px; font-weight: 500; color: var(--muted);
    text-decoration: none; transition: color .15s;
}
.btn-back:hover { color: var(--dark); }

/* ── Trust footer ────────────────────────────── */
.trust {
    display: flex; justify-content: center; gap: 18px; flex-wrap: wrap;
    margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border);
}
.trust-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #94a3b8; font-weight: 500; }

@media (max-width: 400px) {
    .ah-amount { font-size: 42px; }
    .qr-frame img { width: 200px; height: 200px; }
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-icon">&#xe442;</div>
        <div>
            <div class="topbar-name"><?= htmlspecialchars($booking['company_name'] ?? 'Tour Payment') ?></div>
            <div class="topbar-sub">PromptPay Transfer</div>
        </div>
    </div>
    <div class="topbar-secure">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
        Secure
    </div>
</div>

<div class="page">

    <!-- Progress -->
    <div class="progress-bar">
        <div class="pb-step">
            <div class="pb-dot done">&#10003;</div>
            <div class="pb-label">Select</div>
        </div>
        <div class="pb-connector done"></div>
        <div class="pb-step">
            <div class="pb-dot active">2</div>
            <div class="pb-label active">Scan & Pay</div>
        </div>
        <div class="pb-connector"></div>
        <div class="pb-step">
            <div class="pb-dot idle">3</div>
            <div class="pb-label">Confirm</div>
        </div>
    </div>

    <!-- Amount hero -->
    <div class="amount-hero">
        <div class="ah-label">Amount to Transfer</div>
        <div class="ah-amount"><sup>฿</sup><?= number_format($amount, 2) ?></div>
        <div class="ah-ref">Booking #<?= htmlspecialchars($booking['booking_number']) ?></div>
        <div>
            <span class="ah-badge">
                <span class="ah-badge-dot"></span>
                PromptPay QR &nbsp;·&nbsp; Thailand
            </span>
        </div>
    </div>

    <!-- QR Code -->
    <div class="card">
        <div class="card-hd">
            <div class="card-hd-icon" style="background:#e8f0fb; color:var(--pp);">&#9641;</div>
            Scan QR Code
        </div>
        <div class="qr-body">
            <div class="qr-frame">
                <?php if (!empty($qrData['qr_url'])): ?>
                <img src="<?= htmlspecialchars($qrData['qr_url']) ?>" alt="PromptPay QR Code" id="qrImg"
                     onerror="this.parentNode.innerHTML='<div class=\'qr-loading\'>&#10683;</div>'">
                <?php else: ?>
                <div class="qr-loading" style="flex-direction:column;gap:10px;font-size:32px;">
                    <span>&#9641;</span>
                    <span style="font-size:12px;color:#94a3b8;">QR unavailable</span>
                </div>
                <?php endif; ?>
                <div class="qr-pp-label">PromptPay</div>
            </div>

            <?php if ($promptpayName || !empty($qrData['target'])): ?>
            <div class="qr-account">
                <?php if ($promptpayName): ?>
                <div class="qr-account-name"><?= htmlspecialchars($promptpayName) ?></div>
                <?php endif; ?>
                <?php if (!empty($qrData['target'])): ?>
                <div class="qr-account-id"><?= htmlspecialchars($qrData['target']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="qr-actions">
                <?php if (!empty($qrData['qr_url'])): ?>
                <a href="<?= htmlspecialchars($qrData['qr_url']) ?>" target="_blank" class="qr-btn" title="Open QR in new tab to save">
                    &#8681; Save QR
                </a>
                <?php endif; ?>
                <?php if (!empty($qrData['payload'])): ?>
                <button type="button" class="qr-btn" onclick="copyPayload(this)" data-payload="<?= htmlspecialchars($qrData['payload']) ?>">
                    &#10697; Copy Code
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- How to pay -->
    <div class="card">
        <div class="card-hd">
            <div class="card-hd-icon" style="background:#fef3c7; color:#d97706;">&#9776;</div>
            How to Pay
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div>
                    <div class="step-title">Open your banking app</div>
                    <div class="step-desc">Works with any Thai banking app:</div>
                    <div class="bank-chips">
                        <span class="bank-chip" style="color:#1e3a8a; border-color:#bfdbfe; background:#eff6ff;">KBank</span>
                        <span class="bank-chip" style="color:#7c3aed; border-color:#ddd6fe; background:#f5f3ff;">SCB</span>
                        <span class="bank-chip" style="color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff;">BBL</span>
                        <span class="bank-chip" style="color:#b91c1c; border-color:#fecaca; background:#fef2f2;">KTB</span>
                        <span class="bank-chip" style="color:#b45309; border-color:#fde68a; background:#fffbeb;">GSB</span>
                        <span class="bank-chip" style="color:#0369a1; border-color:#bae6fd; background:#f0f9ff;">TTB</span>
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div>
                    <div class="step-title">Tap "Scan QR" / สแกน QR</div>
                    <div class="step-desc">Find the scan icon on your app's home screen or in the transfer menu.</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div>
                    <div class="step-title">Confirm amount: <strong style="color:var(--pp);">฿<?= number_format($amount, 2) ?></strong></div>
                    <div class="step-desc">Verify the amount and payee name before confirming.</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div>
                    <div class="step-title">Screenshot your slip &amp; upload below</div>
                    <div class="step-desc">Capture the transfer confirmation screen, then upload it to complete your booking.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload slip -->
    <div class="card">
        <div class="card-hd">
            <div class="card-hd-icon" style="background:#d1fae5; color:#059669;">&#8679;</div>
            Upload Transfer Slip
        </div>
        <div class="upload-body">
            <form method="post" action="index.php?page=booking_pay_promptpay_confirm" enctype="multipart/form-data" id="slipForm">
                <input type="hidden" name="id"           value="<?= intval($booking['id']) ?>">
                <input type="hidden" name="token"        value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
                <input type="hidden" name="amount"       value="<?= htmlspecialchars($amount) ?>">
                <input type="hidden" name="payment_type" value="<?= htmlspecialchars($paymentType ?? 'full') ?>">

                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="slip" accept="image/*,.pdf" required id="slipFile">
                    <div class="upload-icon" id="uploadIcon">&#9729;</div>
                    <div class="upload-title" id="uploadTitle">Tap to upload your slip</div>
                    <div class="upload-sub" id="uploadSub">or drag &amp; drop &nbsp;·&nbsp; JPG PNG PDF &nbsp;·&nbsp; max 5 MB</div>
                </div>

                <div class="slip-preview" id="slipPreview">
                    <img src="" alt="Slip preview" id="previewImg">
                    <button type="button" class="slip-remove" id="removeBtn">&#10005;</button>
                </div>

                <div class="f-group">
                    <label class="f-label">Transaction Ref <span class="f-opt">(optional)</span></label>
                    <input type="text" name="reference_id" class="f-input" placeholder="e.g. 67001234567890">
                    <div class="f-hint">Last 10–14 digits shown on your transfer confirmation</div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    &#10003;&nbsp; Submit Payment Slip
                </button>
            </form>

            <div style="text-align:center;">
                <a href="index.php?page=booking_pay&id=<?= intval($booking['id']) ?>&token=<?= urlencode($_GET['token'] ?? '') ?>" class="btn-back">
                    &#8592; Back to payment options
                </a>
            </div>
        </div>
    </div>

    <!-- Trust footer -->
    <div class="trust">
        <div class="trust-item">&#128274; SSL Encrypted</div>
        <div class="trust-item">&#128737; Data Protected</div>
        <div class="trust-item">&#128336; Reviewed within 24h</div>
    </div>

</div><!-- /page -->

<script>
(function () {
    var zone      = document.getElementById('uploadZone');
    var fileInput = document.getElementById('slipFile');
    var preview   = document.getElementById('slipPreview');
    var previewImg= document.getElementById('previewImg');
    var removeBtn = document.getElementById('removeBtn');
    var uploadIcon  = document.getElementById('uploadIcon');
    var uploadTitle = document.getElementById('uploadTitle');
    var uploadSub   = document.getElementById('uploadSub');
    var submitBtn = document.getElementById('submitBtn');

    function showFile(file) {
        if (!file) return;
        zone.classList.add('has-file');
        uploadIcon.innerHTML  = '&#10003;';
        uploadIcon.style.color = 'var(--green)';
        uploadTitle.textContent = file.name;
        uploadSub.textContent   = (file.size / 1024).toFixed(0) + ' KB  ·  click to change';

        if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    }

    function clearFile() {
        fileInput.value = '';
        zone.classList.remove('has-file');
        uploadIcon.innerHTML   = '&#9729;';
        uploadIcon.style.color = '';
        uploadTitle.textContent = 'Tap to upload your slip';
        uploadSub.textContent   = 'or drag & drop \u00b7 JPG PNG PDF \u00b7 max 5 MB';
        preview.style.display = 'none';
        previewImg.src = '';
    }

    fileInput.addEventListener('change', function () { if (this.files[0]) showFile(this.files[0]); });
    removeBtn.addEventListener('click', function (e) { e.stopPropagation(); clearFile(); });

    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.add('drag-over'); });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.remove('drag-over'); });
    });
    zone.addEventListener('drop', function (e) {
        var file = e.dataTransfer.files[0];
        if (!file) return;
        var dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showFile(file);
    });

    document.getElementById('slipForm').addEventListener('submit', function (e) {
        if (!fileInput.files[0]) { e.preventDefault(); zone.classList.add('drag-over'); zone.style.borderColor='var(--red)'; return; }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '&#8987;&nbsp; Submitting…';
    });
})();

function copyPayload(btn) {
    var payload = btn.dataset.payload;
    if (!payload) return;
    navigator.clipboard.writeText(payload).then(function () {
        var orig = btn.innerHTML;
        btn.innerHTML = '&#10003; Copied!';
        btn.style.color = 'var(--green)';
        btn.style.borderColor = 'var(--green)';
        setTimeout(function () { btn.innerHTML = orig; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
    });
}
</script>
</body>
</html>
