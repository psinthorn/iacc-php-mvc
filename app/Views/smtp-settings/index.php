<?php
$pageTitle = 'Email — SMTP Settings';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
?>
<style>
.smtp-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06); max-width:760px; }
.smtp-header { background:linear-gradient(135deg,#0d9488,#0f766e); padding:22px 28px; display:flex; align-items:center; gap:14px; }
.smtp-header-icon { width:48px; height:48px; background:rgba(255,255,255,.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; color:white; flex-shrink:0; }
.smtp-header h4 { color:white; margin:0; font-size:17px; font-weight:700; }
.smtp-header p  { color:rgba(255,255,255,.85); margin:2px 0 0; font-size:12px; }
.smtp-body { padding:28px; }
.smtp-row { display:grid; gap:18px; margin-bottom:18px; }
.smtp-row.cols-2 { grid-template-columns:1fr 1fr; }
.smtp-row.cols-3 { grid-template-columns:2fr 1fr 1fr; }
.smtp-field label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em; }
.smtp-field .form-control { border-radius:8px; border:1px solid #cbd5e1; font-size:14px; }
.smtp-field .form-control:focus { border-color:#0d9488; box-shadow:0 0 0 3px rgba(13,148,136,.12); }
.smtp-divider { border:none; border-top:1px solid #f1f5f9; margin:24px 0; }
.smtp-footer { background:#f8fafc; padding:18px 28px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.test-result { font-size:13px; padding:8px 14px; border-radius:8px; display:none; }
.test-result.ok  { background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; }
.test-result.err { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }
</style>

<div class="container-fluid" style="padding:24px 20px;">
    <div class="page-header" style="margin-bottom:20px;">
        <h2><i class="fa fa-envelope-o" style="color:#0d9488;"></i> Email SMTP Settings</h2>
        <p class="text-muted" style="margin:0;">Configure outbound email delivery for vouchers, invoices, and notifications.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?>" style="max-width:760px;">
        <i class="fa fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
        <?= $e($message) ?>
    </div>
    <?php endif; ?>

    <div class="smtp-card">
        <div class="smtp-header">
            <div class="smtp-header-icon"><i class="fa fa-paper-plane"></i></div>
            <div>
                <h4>SMTP Configuration</h4>
                <p>Per-company outbound mail settings — Gmail, Outlook, custom SMTP server</p>
            </div>
            <div style="margin-left:auto;">
                <?php if (!empty($cfg['host'])): ?>
                <span class="label label-success" style="font-size:11px;padding:5px 10px;border-radius:20px;">
                    <i class="fa fa-check"></i> Configured
                </span>
                <?php else: ?>
                <span class="label label-warning" style="font-size:11px;padding:5px 10px;border-radius:20px;">
                    <i class="fa fa-exclamation-triangle"></i> Not configured
                </span>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="index.php?page=smtp_settings" id="smtpForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">

            <div class="smtp-body">
                <!-- Enable / Disable toggle -->
                <div style="margin-bottom:20px;">
                    <div class="checkbox" style="margin:0;">
                        <label style="font-size:14px;font-weight:600;color:#334155;">
                            <input type="checkbox" name="is_enabled" value="1" <?= ($cfg['is_enabled'] ?? 1) ? 'checked' : '' ?>>
                            &nbsp;Enable outbound email via SMTP
                        </label>
                        <p class="text-muted" style="font-size:12px;margin:4px 0 0 22px;">
                            When disabled, the system falls back to PHP <code>mail()</code> or environment SMTP variables.
                        </p>
                    </div>
                </div>

                <hr class="smtp-divider">

                <!-- Server settings -->
                <div class="smtp-row cols-3">
                    <div class="smtp-field">
                        <label>SMTP Host</label>
                        <input type="text" name="host" class="form-control"
                               placeholder="smtp.gmail.com" value="<?= $e($cfg['host']) ?>">
                    </div>
                    <div class="smtp-field">
                        <label>Port</label>
                        <input type="number" name="port" class="form-control"
                               placeholder="587" min="1" max="65535" value="<?= intval($cfg['port'] ?? 587) ?>">
                    </div>
                    <div class="smtp-field">
                        <label>Encryption</label>
                        <select name="encryption" class="form-control">
                            <?php foreach (['tls' => 'TLS (STARTTLS — port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None (port 25)'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($cfg['encryption'] ?? 'tls') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Auth -->
                <div class="smtp-row cols-2">
                    <div class="smtp-field">
                        <label>Username / Email</label>
                        <input type="text" name="username" class="form-control"
                               autocomplete="username"
                               placeholder="your@gmail.com" value="<?= $e($cfg['username']) ?>">
                    </div>
                    <div class="smtp-field">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control"
                               autocomplete="new-password"
                               placeholder="<?= !empty($cfg['password']) ? '●●●●●●●● (leave blank to keep)' : 'App password or SMTP password' ?>">
                        <?php if (!empty($cfg['password'])): ?>
                        <small class="text-muted">Leave blank to keep existing password.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="smtp-divider">

                <!-- From address -->
                <div class="smtp-row cols-2">
                    <div class="smtp-field">
                        <label>From Email Address</label>
                        <input type="email" name="from_email" class="form-control"
                               placeholder="noreply@yourcompany.com" value="<?= $e($cfg['from_email']) ?>">
                    </div>
                    <div class="smtp-field">
                        <label>From Name</label>
                        <input type="text" name="from_name" class="form-control"
                               placeholder="Your Company Name" value="<?= $e($cfg['from_name']) ?>">
                    </div>
                </div>

                <hr class="smtp-divider">

                <!-- Test email row -->
                <div style="background:#f8fafc;border-radius:10px;padding:18px;border:1px solid #e2e8f0;">
                    <p style="font-size:13px;font-weight:600;color:#334155;margin:0 0 12px;">
                        <i class="fa fa-flask"></i> Send Test Email
                    </p>
                    <div class="input-group" style="max-width:420px;">
                        <input type="email" id="testTo" class="form-control" placeholder="Recipient email address">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default" id="btnTestSmtp">
                                <i class="fa fa-paper-plane"></i> Send Test
                            </button>
                        </span>
                    </div>
                    <div class="test-result" id="testResult"></div>
                </div>
            </div>

            <div class="smtp-footer">
                <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:9px 22px;">
                    <i class="fa fa-save"></i> Save Settings
                </button>
                <a href="index.php?page=dashboard" class="btn btn-default" style="border-radius:8px;">
                    Cancel
                </a>
                <span style="margin-left:auto;font-size:12px;color:#94a3b8;">
                    <i class="fa fa-info-circle"></i>
                    Gmail: use an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a>,
                    port 587 TLS. For Outlook use smtp.office365.com, port 587 TLS.
                </span>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('btnTestSmtp').addEventListener('click', function () {
    var btn = this;
    var testTo = document.getElementById('testTo').value.trim();
    var result = document.getElementById('testResult');

    if (!testTo) { alert('Enter a recipient email address.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
    result.style.display = 'none';

    var form = document.getElementById('smtpForm');
    var data = new FormData(form);
    data.set('test_to', testTo);
    data.set('action', 'test');  // override action for detection on server

    // Build a plain object from current form values
    var params = new URLSearchParams();
    params.append('csrf_token', document.querySelector('input[name=csrf_token]').value);
    params.append('host',       form.querySelector('[name=host]').value);
    params.append('port',       form.querySelector('[name=port]').value);
    params.append('encryption', form.querySelector('[name=encryption]').value);
    params.append('username',   form.querySelector('[name=username]').value);
    params.append('password',   form.querySelector('[name=password]').value);
    params.append('from_email', form.querySelector('[name=from_email]').value);
    params.append('from_name',  form.querySelector('[name=from_name]').value);
    params.append('test_to',    testTo);

    fetch('index.php?page=smtp_settings_test', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        result.className = 'test-result ' + (data.ok ? 'ok' : 'err');
        result.innerHTML = '<i class="fa fa-' + (data.ok ? 'check-circle' : 'times-circle') + '"></i> ' + (data.message || '');
        result.style.display = 'block';
    })
    .catch(e => {
        result.className = 'test-result err';
        result.innerHTML = '<i class="fa fa-times-circle"></i> Request failed: ' + e.message;
        result.style.display = 'block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-paper-plane"></i> Send Test';
    });
});
</script>
