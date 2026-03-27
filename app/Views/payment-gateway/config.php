<?php
/**
 * Payment Gateway Configuration View
 * Extracted from payment-gateway-config.php
 * 
 * Variables: $gateways, $gatewayConfigs, $gatewayFields, $message, $messageType, $xml
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/master-data.css">
<style>
.gateway-config-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
.page-header-gateway {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: white;
    padding: 28px 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.page-header-gateway .header-content { display: flex; align-items: center; gap: 16px; }
.page-header-gateway .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.page-header-gateway h2 { margin: 0; font-size: 26px; font-weight: 700; }
.page-header-gateway .subtitle { margin: 4px 0 0; opacity: 0.9; font-size: 14px; font-weight: 400; }
.btn-back-link { background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.3); color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.btn-back-link:hover { background: rgba(255,255,255,0.25); color: white; text-decoration: none; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.gateway-card { background: white; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
.gateway-header { padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
.gateway-header.paypal { background: linear-gradient(135deg, #003087 0%, #009cde 100%); color: white; }
.gateway-header.stripe { background: linear-gradient(135deg, #635bff 0%, #00d4ff 100%); color: white; }
.gateway-title { display: flex; align-items: center; gap: 14px; }
.gateway-title i { font-size: 28px; }
.gateway-title h3 { margin: 0; font-size: 20px; font-weight: 700; }
.gateway-status { display: flex; align-items: center; gap: 12px; }
.status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.status-badge.configured { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.status-badge.not-configured { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.gateway-body { padding: 25px; }
.config-form .form-group { margin-bottom: 20px; }
.config-form label { display: block; font-weight: 600; color: #2c3e50; margin-bottom: 8px; }
.config-form label .required { color: #e74c3c; }
.config-form .form-control { width: 100%; height: 48px; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; font-family: 'Inter', sans-serif; transition: all 0.2s ease; background-color: #fff; color: #333; box-sizing: border-box; }
.config-form .form-control:hover { border-color: #cbd5e1; }
.config-form .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); outline: none; }
.config-form select.form-control { appearance: none; -webkit-appearance: none; -moz-appearance: none; background-color: #fff; background-image: linear-gradient(45deg, transparent 50%, #4f46e5 50%), linear-gradient(135deg, #4f46e5 50%, transparent 50%); background-position: calc(100% - 20px) calc(50% + 2px), calc(100% - 14px) calc(50% + 2px); background-size: 6px 6px, 6px 6px; background-repeat: no-repeat; padding-right: 45px; cursor: pointer; font-weight: 500; line-height: 1.5; height: 48px; }
.config-form .help-text { margin-top: 5px; font-size: 12px; color: #7f8c8d; }
.form-row { display: flex; gap: 20px; flex-wrap: wrap; }
.form-row .form-col { flex: 1; min-width: 250px; }
.btn-save { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-save:hover { background: linear-gradient(135deg, #219a52, #27ae60); transform: translateY(-2px); }
.btn-test { background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-left: 10px; }
.btn-test:hover { background: linear-gradient(135deg, #2980b9, #21618c); transform: translateY(-2px); }
.action-buttons { display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
.no-gateways { text-align: center; padding: 60px 20px; background: white; border-radius: 10px; }
.no-gateways i { font-size: 60px; color: #bdc3c7; margin-bottom: 20px; }
.no-gateways h4 { color: #7f8c8d; margin-bottom: 10px; }
.no-gateways p { color: #95a5a6; }
.password-toggle { position: relative; }
.password-toggle .toggle-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #7f8c8d; cursor: pointer; }
.password-toggle .toggle-btn:hover { color: #8e44ad; }
.mode-indicator { display: inline-block; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-left: 8px; vertical-align: middle; }
.mode-indicator.sandbox, .mode-indicator.test { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border: 1px solid #f59e0b; }
.mode-indicator.live, .mode-indicator.production { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; border: 1px solid #22c55e; }
@media (max-width: 768px) { .form-row { flex-direction: column; } .form-row .form-col { min-width: 100%; } .action-buttons { flex-direction: column; } .action-buttons .btn-save, .action-buttons .btn-test { width: 100%; margin: 5px 0; } }
</style>

<div class="gateway-config-container">
    <div class="page-header-gateway">
        <div class="header-content">
            <div class="header-icon"><i class="fa fa-cogs"></i></div>
            <div>
                <h2><?=$xml->paymentgatewayconfig ?? 'Payment Gateway Configuration'?></h2>
                <p class="subtitle"><?=$xml->managepaymentgateways ?? 'Configure PayPal and Stripe API credentials for online payments'?></p>
            </div>
        </div>
        <a href="index.php?page=payment_method_list" class="btn-back-link">
            <i class="fa fa-arrow-left"></i> <?=$xml->backtolist ?? 'Back to Payment Methods'?>
        </a>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible" style="border-radius: 12px; border: none;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'times-circle' : 'exclamation-circle') ?>"></i>
        <?= e($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if (empty($gateways)): ?>
    <div class="no-gateways">
        <i class="fa fa-credit-card"></i>
        <h4><?=$xml->nogatewaysfound ?? 'No Payment Gateways Found'?></h4>
        <p><?=$xml->addgatewaysfirst ?? 'Please add PayPal or Stripe as a payment method first.'?></p>
        <a href="index.php?page=payment_method&mode=A" class="btn btn-primary" style="margin-top: 15px;">
            <i class="fa fa-plus"></i> <?=$xml->addpaymentmethod ?? 'Add Payment Method'?>
        </a>
    </div>
    <?php else: ?>
    
    <?php foreach ($gateways as $gateway): 
        $code = strtolower($gateway['code']);
        $fields = $gatewayFields[$code] ?? [];
        $configs = $gatewayConfigs[$gateway['id']] ?? [];
        $isConfigured = !empty($configs['client_id']) || !empty($configs['secret_key']) || !empty($configs['publishable_key']);
    ?>
    <div class="gateway-card">
        <div class="gateway-header <?= $code ?>">
            <div class="gateway-title">
                <i class="fa <?= $code === 'paypal' ? 'fa-paypal' : 'fa-cc-stripe' ?>"></i>
                <h3><?= e($gateway['name']) ?></h3>
            </div>
            <div class="gateway-status">
                <span class="status-badge <?= $isConfigured ? 'configured' : 'not-configured' ?>">
                    <?= $isConfigured ? ($xml->configured ?? 'Configured') : ($xml->notconfigured ?? 'Not Configured') ?>
                </span>
            </div>
        </div>
        <div class="gateway-body">
            <?php if (empty($fields)): ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> <?=$xml->unsupportedgateway ?? 'This gateway type is not yet supported for configuration.'?>
            </div>
            <?php else: ?>
            <form method="post" class="config-form">
                <input type="hidden" name="action" value="save_config">
                <input type="hidden" name="gateway_id" value="<?= $gateway['id'] ?>">
                <input type="hidden" name="gateway_code" value="<?= $code ?>">
                <div class="form-row">
                    <?php foreach ($fields as $key => $field): ?>
                    <div class="form-col">
                        <div class="form-group">
                            <label>
                                <?= e($field['label']) ?>
                                <?php if ($field['required']): ?><span class="required">*</span><?php endif; ?>
                                <?php if ($key === 'mode'): 
                                    $currentMode = $configs[$key] ?? 'sandbox';
                                    $isLive = in_array($currentMode, ['live', 'production']);
                                ?>
                                <span id="mode-badge-<?= $code ?>" class="mode-indicator <?= $isLive ? 'live' : 'sandbox' ?>">
                                    <?= $isLive ? 'LIVE MODE' : 'TEST MODE' ?>
                                </span>
                                <?php endif; ?>
                            </label>
                            <?php if ($field['type'] === 'select'): ?>
                            <select name="config[<?= $key ?>]" class="form-control" <?= $field['required'] ? 'required' : '' ?>
                                    <?php if ($key === 'mode'): ?>onchange="updateModeIndicator(this, '<?= $code ?>')"<?php endif; ?>>
                                <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                                <option value="<?= $optVal ?>" <?= ($configs[$key] ?? '') === $optVal ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php elseif ($field['type'] === 'password'): ?>
                            <div class="password-toggle">
                                <input type="password" name="config[<?= $key ?>]" class="form-control" value="<?= e($configs[$key] ?? '') ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= $field['required'] ? 'required' : '' ?>>
                                <button type="button" class="toggle-btn" onclick="togglePassword(this)"><i class="fa fa-eye"></i></button>
                            </div>
                            <?php else: ?>
                            <input type="text" name="config[<?= $key ?>]" class="form-control" value="<?= e($configs[$key] ?? '') ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= $field['required'] ? 'required' : '' ?>>
                            <?php endif; ?>
                            <?php if (!empty($field['help'])): ?>
                            <div class="help-text"><i class="fa fa-info-circle"></i> <?= e($field['help']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> <?=$xml->saveconfig ?? 'Save Configuration'?></button>
                    <button type="submit" name="action" value="test_connection" class="btn-test"><i class="fa fa-plug"></i> <?=$xml->testconnection ?? 'Test Connection'?></button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="gateway-card">
        <div class="gateway-header" style="background: linear-gradient(135deg, #34495e, #2c3e50); color: white;">
            <div class="gateway-title"><i class="fa fa-question-circle"></i><h3><?=$xml->setupguide ?? 'Setup Guide'?></h3></div>
        </div>
        <div class="gateway-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4><i class="fa fa-paypal" style="color: #003087;"></i> PayPal Setup</h4>
                    <ol style="padding-left: 20px; color: #555;">
                        <li>Go to <a href="https://developer.paypal.com" target="_blank">PayPal Developer</a></li>
                        <li>Create or log into your account</li>
                        <li>Go to Dashboard → My Apps & Credentials</li>
                        <li>Create a new app or use existing one</li>
                        <li>Copy Client ID and Secret</li>
                        <li>Set up webhooks for payment notifications</li>
                    </ol>
                </div>
                <div>
                    <h4><i class="fa fa-cc-stripe" style="color: #635bff;"></i> Stripe Setup</h4>
                    <ol style="padding-left: 20px; color: #555;">
                        <li>Go to <a href="https://dashboard.stripe.com" target="_blank">Stripe Dashboard</a></li>
                        <li>Create or log into your account</li>
                        <li>Go to Developers → API Keys</li>
                        <li>Copy Publishable and Secret keys</li>
                        <li>Set up webhooks under Developers → Webhooks</li>
                        <li>Add webhook endpoint and copy signing secret</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(btn) {
    var input = btn.parentNode.querySelector('input');
    var icon = btn.querySelector('i');
    if (input.type === 'password') { input.type = 'text'; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
    else { input.type = 'password'; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
}
function updateModeIndicator(select, gatewayCode) {
    var badge = document.getElementById('mode-badge-' + gatewayCode);
    if (!badge) return;
    var value = select.value;
    var isLive = (value === 'live' || value === 'production');
    badge.className = 'mode-indicator ' + (isLive ? 'live' : 'sandbox');
    badge.textContent = isLive ? 'LIVE' : 'TEST MODE';
    if (isLive) {
        if (!confirm('Warning: You are switching to LIVE/PRODUCTION mode. Real transactions will be processed. Continue?')) {
            select.value = select.options[0].value;
            badge.className = 'mode-indicator sandbox';
            badge.textContent = 'TEST MODE';
        }
    }
}
document.querySelectorAll('.config-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var btn = form.querySelector('button[type="submit"]:focus, button[type="submit"]:active');
        if (btn) { btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...'; btn.disabled = true; }
    });
});
</script>
