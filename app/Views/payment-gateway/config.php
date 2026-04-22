<?php
/**
 * Payment Gateway Configuration View
 * Variables: $gateways, $gatewayConfigs, $gatewayFields, $message, $messageType, $xml
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';
?>
<link rel="stylesheet" href="css/master-data.css">
<style>
/* ─── Gateway Cards ───────────────────────────────────── */
.gw-grid { display: flex; flex-direction: column; gap: 20px; }

.gw-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }

.gw-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; color: white; }
.gw-header.stripe    { background: linear-gradient(135deg, #635bff 0%, #4f46e5 100%); }
.gw-header.paypal    { background: linear-gradient(135deg, #0070ba 0%, #003087 100%); }
.gw-header.promptpay { background: linear-gradient(135deg, #06b6d4 0%, #0369a1 100%); }
.gw-header.default   { background: linear-gradient(135deg, #64748b 0%, #475569 100%); }

.gw-header-left { display: flex; align-items: center; gap: 14px; }
.gw-icon { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.gw-title { font-size: 17px; font-weight: 700; margin: 0; }
.gw-subtitle { font-size: 12px; opacity: 0.85; margin-top: 2px; }

.gw-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.03em; }
.gw-badge.ok  { background: rgba(16,185,129,0.25); color: #d1fae5; border: 1px solid rgba(16,185,129,0.4); }
.gw-badge.no  { background: rgba(239,68,68,0.2);   color: #fee2e2;  border: 1px solid rgba(239,68,68,0.3); }
.gw-badge.na  { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.25); }

/* ─── Form Body ───────────────────────────────────────── */
.gw-body { padding: 24px; }

.gw-fields { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
.gw-fields.cols-1 { grid-template-columns: 1fr; }
.gw-fields.cols-3 { grid-template-columns: repeat(3, 1fr); }

.gw-field { display: flex; flex-direction: column; gap: 6px; }
.gw-field label { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 6px; }
.gw-field label .req { color: #ef4444; }
.gw-field label .mode-pill { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; }
.mode-pill.sandbox { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
.mode-pill.live    { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }

.gw-field input, .gw-field select, .gw-field textarea {
    height: 42px; padding: 0 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 13px; font-family: inherit; color: #1e293b;
    background: #fff; outline: none; box-sizing: border-box;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.gw-field input:focus, .gw-field select:focus {
    border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.gw-field select {
    -webkit-appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px;
}
.gw-field .help { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* Key field — monospace, copy button */
.key-wrap { position: relative; }
.key-wrap input { font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 12px; padding-right: 80px; background: #f8fafc; }
.key-wrap input:focus { background: #fff; }
.key-actions { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: flex; gap: 4px; }
.key-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.key-btn:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }

/* ─── Divider ─────────────────────────────────────────── */
.gw-divider { border: none; border-top: 1px solid #f1f5f9; margin: 20px 0; }

/* ─── Action Bar ──────────────────────────────────────── */
.gw-actions { display: flex; align-items: center; gap: 10px; padding-top: 18px; border-top: 1.5px solid #f1f5f9; }
.btn-gw-save { height: 40px; padding: 0 22px; background: #4f46e5; color: white; border: none; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: background 0.15s; }
.btn-gw-save:hover { background: #4338ca; }
.btn-gw-test { height: 40px; padding: 0 18px; background: white; color: #64748b; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: all 0.15s; }
.btn-gw-test:hover { background: #f8fafc; border-color: #3b82f6; color: #3b82f6; }
.gw-save-note { margin-left: auto; font-size: 11px; color: #94a3b8; }

/* ─── Unsupported / Empty ─────────────────────────────── */
.gw-unsupported { display: flex; align-items: center; gap: 14px; padding: 18px 20px; background: #f8fafc; border-radius: 10px; border: 1px dashed #e2e8f0; color: #64748b; font-size: 13px; }
.gw-unsupported i { font-size: 20px; color: #cbd5e1; }

.gw-empty { text-align: center; padding: 64px 24px; background: white; border-radius: 14px; border: 1px solid #e2e8f0; }
.gw-empty i { font-size: 52px; color: #e2e8f0; display: block; margin-bottom: 16px; }
.gw-empty h4 { font-size: 16px; font-weight: 600; color: #64748b; margin: 0 0 8px; }
.gw-empty p { font-size: 13px; color: #94a3b8; margin: 0 0 20px; }

/* ─── Flash ───────────────────────────────────────────── */
.flash-ok  { background: #f0fdf4; border-left: 4px solid #10b981; padding: 13px 18px; border-radius: 0 10px 10px 0; font-size: 14px; color: #065f46; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
.flash-err { background: #fef2f2; border-left: 4px solid #ef4444; padding: 13px 18px; border-radius: 0 10px 10px 0; font-size: 14px; color: #991b1b; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }

/* ─── Enable/Disable Toggle ──────────────────────────── */
.gw-toggle-wrap { display: flex; align-items: center; gap: 12px; }

.gw-toggle { display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
.gw-toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
.gw-toggle-track {
    width: 46px; height: 26px; border-radius: 13px; flex-shrink: 0;
    background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.3);
    position: relative; transition: background 0.2s, border-color 0.2s;
}
.gw-toggle-thumb {
    position: absolute; top: 3px; left: 3px;
    width: 18px; height: 18px; border-radius: 50%; background: rgba(255,255,255,0.7);
    box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    transition: transform 0.2s, background 0.2s;
}
.gw-toggle-input:checked + .gw-toggle-track { background: rgba(16,185,129,0.65); border-color: rgba(16,185,129,0.5); }
.gw-toggle-input:checked + .gw-toggle-track .gw-toggle-thumb { transform: translateX(20px); background: #fff; }
.gw-toggle-text { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.85); min-width: 58px; }

/* Visually dim disabled cards */
.gw-card.gw-disabled .gw-body { opacity: 0.45; pointer-events: none; }
.gw-card.gw-disabled .gw-header { opacity: 0.75; }

/* ─── Setup Guide ─────────────────────────────────────── */
.guide-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; margin-top: 20px; overflow: hidden; }
.guide-header { background: #f8fafc; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 8px; }
.guide-body { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0; }
.guide-section { padding: 20px 24px; border-right: 1px solid #f1f5f9; }
.guide-section:last-child { border-right: none; }
.guide-section h5 { font-size: 13px; font-weight: 700; color: #1e293b; margin: 0 0 12px; display: flex; align-items: center; gap: 7px; }
.guide-section ol { padding-left: 18px; margin: 0; }
.guide-section ol li { font-size: 12px; color: #64748b; padding: 3px 0; line-height: 1.5; }
.guide-section ol li a { color: #4f46e5; }

@media (max-width: 768px) {
    .gw-fields, .gw-fields.cols-3 { grid-template-columns: 1fr; }
    .gw-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .guide-body { grid-template-columns: 1fr; }
    .guide-section { border-right: none; border-bottom: 1px solid #f1f5f9; }
}
</style>

<div class="master-data-container">

    <!-- Header -->
    <div class="master-data-header" data-theme="indigo" style="--md-primary:#4f46e5;">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-cogs"></i> <?= $isThai ? 'ตั้งค่า Payment Gateway' : 'Payment Gateway Config' ?></h2>
                <p><?= $isThai ? 'ตั้งค่า API credentials สำหรับ PayPal, Stripe และ PromptPay' : 'Configure API credentials for online payment processing' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=payment_method_list" class="btn-header btn-header-outline">
                    <i class="fa fa-credit-card"></i> <?= $isThai ? 'วิธีชำระเงิน' : 'Payment Methods' ?>
                </a>
            </div>
        </div>
    </div>


    <?php if (empty($gateways)): ?>
    <!-- Empty state -->
    <div class="gw-empty">
        <i class="fa fa-plug"></i>
        <h4><?= $isThai ? 'ยังไม่มี Payment Gateway' : 'No Payment Gateways Found' ?></h4>
        <p><?= $isThai ? 'เพิ่ม PayPal, Stripe หรือ PromptPay ในรายการวิธีชำระเงินก่อน' : 'Add PayPal, Stripe or PromptPay as a payment method first.' ?></p>
        <a href="index.php?page=payment_method&mode=A" class="btn-gw-save" style="display:inline-flex; text-decoration:none;">
            <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มวิธีชำระเงิน' : 'Add Payment Method' ?>
        </a>
    </div>

    <?php else: ?>
    <div class="gw-grid">
    <?php foreach ($gateways as $gateway):
        $code       = strtolower($gateway['code']);
        $fields     = $gatewayFields[$code] ?? [];
        $configs    = $gatewayConfigs[$gateway['id']] ?? [];
        $isConfigured = !empty($configs['client_id']) || !empty($configs['secret_key']) || !empty($configs['publishable_key']) || !empty($configs['merchant_id']) || !empty($configs['promptpay_id']);
        $headerClass = in_array($code, ['stripe','paypal','promptpay']) ? $code : 'default';
        $iconClass   = match($code) {
            'stripe'    => 'fa-cc-stripe',
            'paypal'    => 'fa-paypal',
            'promptpay' => 'fa-qrcode',
            default     => 'fa-plug',
        };
        $fieldCount = count($fields);
        $gridClass = $fieldCount <= 1 ? 'cols-1' : ($fieldCount >= 4 ? 'cols-3' : '');
    ?>
    <?php $isActive = !empty($gateway['is_active']); ?>
    <div class="gw-card <?= $isActive ? '' : 'gw-disabled' ?>">
        <!-- Card Header -->
        <div class="gw-header <?= $headerClass ?>">
            <div class="gw-header-left">
                <div class="gw-icon"><i class="fa <?= $iconClass ?>"></i></div>
                <div>
                    <div class="gw-title"><?= e($gateway['name']) ?></div>
                    <div class="gw-subtitle">
                        <?= match($code) {
                            'stripe'    => 'Stripe Payments — stripe.com',
                            'paypal'    => 'PayPal Commerce Platform',
                            'promptpay' => 'PromptPay QR (Thailand)',
                            default     => e($gateway['description'] ?? 'Payment Gateway'),
                        } ?>
                    </div>
                </div>
            </div>
            <div class="gw-toggle-wrap">
                <!-- Enable / Disable Toggle -->
                <label class="gw-toggle" title="<?= $isThai ? 'เปิด/ปิด Gateway นี้' : 'Enable or disable this gateway' ?>">
                    <input type="checkbox" class="gw-toggle-input"
                           data-gw-id="<?= $gateway['id'] ?>"
                           data-code="<?= $code ?>"
                           <?= $isActive ? 'checked' : '' ?>
                           onchange="toggleGateway(this)">
                    <span class="gw-toggle-track"><span class="gw-toggle-thumb"></span></span>
                    <span class="gw-toggle-text" id="toggle-lbl-<?= $code ?>"><?= $isActive ? ($isThai ? 'เปิดใช้' : 'Enabled') : ($isThai ? 'ปิดอยู่' : 'Disabled') ?></span>
                </label>
                <?php if (!empty($fields)): ?>
                <span class="gw-badge <?= $isConfigured ? 'ok' : 'no' ?>">
                    <i class="fa fa-<?= $isConfigured ? 'check' : 'times' ?>"></i>
                    <?= $isConfigured ? ($isThai ? 'ตั้งค่าแล้ว' : 'Configured') : ($isThai ? 'ยังไม่ตั้งค่า' : 'Not Configured') ?>
                </span>
                <?php else: ?>
                <span class="gw-badge na"><i class="fa fa-minus"></i> <?= $isThai ? 'ไม่รองรับ' : 'N/A' ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Body -->
        <div class="gw-body">
            <?php if (empty($fields)): ?>
            <div class="gw-unsupported">
                <i class="fa fa-info-circle"></i>
                <span><?= $isThai ? 'Gateway นี้ยังไม่รองรับการตั้งค่าผ่านระบบ กรุณาติดต่อผู้ดูแลระบบ' : 'This gateway does not support configuration through this interface yet.' ?></span>
            </div>
            <?php else: ?>
            <form method="post" class="config-form" id="form-<?= $code ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_config">
                <input type="hidden" name="gateway_id" value="<?= $gateway['id'] ?>">
                <input type="hidden" name="gateway_code" value="<?= $code ?>">

                <div class="gw-fields <?= $gridClass ?>">
                <?php foreach ($fields as $key => $field):
                    $val = $configs[$key] ?? '';
                    $isKey = in_array($field['type'], ['password', 'text']) && (str_contains($key, 'key') || str_contains($key, 'secret') || str_contains($key, 'id') || str_contains($key, 'token'));
                ?>
                <div class="gw-field">
                    <label>
                        <?= e($field['label']) ?>
                        <?php if ($field['required']): ?><span class="req">*</span><?php endif; ?>
                        <?php if ($key === 'mode'):
                            $curMode = $val ?: 'sandbox';
                            $isLive  = in_array($curMode, ['live', 'production']);
                        ?>
                        <span id="mode-pill-<?= $code ?>" class="mode-pill <?= $isLive ? 'live' : 'sandbox' ?>">
                            <?= $isLive ? '⚡ LIVE' : '🧪 TEST' ?>
                        </span>
                        <?php endif; ?>
                    </label>

                    <?php if ($field['type'] === 'select'): ?>
                    <select name="config[<?= $key ?>]"
                        <?= $key === 'mode' ? "onchange=\"updateMode(this,'$code')\"" : '' ?>>
                        <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                        <option value="<?= $optVal ?>" <?= $val === $optVal ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php elseif ($field['type'] === 'password'): ?>
                    <div class="key-wrap">
                        <input type="password" name="config[<?= $key ?>]" id="<?= $code ?>_<?= $key ?>"
                               value="<?= e($val) ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>"
                               <?= $field['required'] ? 'required' : '' ?>>
                        <div class="key-actions">
                            <button type="button" class="key-btn" title="Show/Hide" onclick="togglePw('<?= $code ?>_<?= $key ?>', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                            <?php if ($val): ?>
                            <button type="button" class="key-btn" title="Copy" onclick="copyVal('<?= $code ?>_<?= $key ?>')">
                                <i class="fa fa-copy"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php else: ?>
                    <?php if ($isKey): ?>
                    <div class="key-wrap">
                        <input type="text" name="config[<?= $key ?>]" id="<?= $code ?>_<?= $key ?>"
                               value="<?= e($val) ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>"
                               <?= $field['required'] ? 'required' : '' ?>>
                        <?php if ($val): ?>
                        <div class="key-actions">
                            <button type="button" class="key-btn" title="Copy" onclick="copyVal('<?= $code ?>_<?= $key ?>')">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <input type="text" name="config[<?= $key ?>]" value="<?= e($val) ?>"
                           placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= $field['required'] ? 'required' : '' ?>>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($field['help'])): ?>
                    <span class="help"><i class="fa fa-info-circle"></i> <?= e($field['help']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Actions -->
                <div class="gw-actions">
                    <button type="button" class="btn-gw-save" onclick="saveGateway('<?= $code ?>', this)">
                        <i class="fa fa-save"></i> <?= $isThai ? 'บันทึก' : 'Save Config' ?>
                    </button>
                    <button type="button" class="btn-gw-test" onclick="testGateway('<?= $code ?>', this)">
                        <i class="fa fa-plug"></i> <?= $isThai ? 'ทดสอบการเชื่อมต่อ' : 'Test Connection' ?>
                    </button>
                    <span class="gw-save-note">
                        <?php if ($isConfigured): ?>
                        <i class="fa fa-circle" style="color:#10b981; font-size:8px;"></i>
                        <?= $isThai ? 'ตั้งค่าล่าสุด: บันทึกแล้ว' : 'Last saved' ?>
                        <?php else: ?>
                        <i class="fa fa-circle" style="color:#f59e0b; font-size:8px;"></i>
                        <?= $isThai ? 'ยังไม่ได้ตั้งค่า' : 'Awaiting configuration' ?>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Inline test result (shown here, not at top) -->
                <div id="test-result-<?= $code ?>" style="display:none; margin-top:14px; padding:12px 16px; border-radius:10px; font-size:13px; display:none; align-items:center; gap:9px;"></div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Setup Guide -->
    <div class="guide-card">
        <div class="guide-header">
            <i class="fa fa-book" style="color:#4f46e5;"></i>
            <?= $isThai ? 'คู่มือการตั้งค่า' : 'Setup Guide' ?>
        </div>
        <div class="guide-body">
            <div class="guide-section">
                <h5><i class="fa fa-cc-stripe" style="color:#635bff;"></i> Stripe</h5>
                <ol>
                    <li>ไปที่ <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard → API Keys</a></li>
                    <li>คัดลอก <strong>Publishable key</strong> และ <strong>Secret key</strong></li>
                    <li>ตั้ง Mode เป็น <strong>test</strong> ก่อนทดสอบ</li>
                    <li>เปลี่ยนเป็น <strong>live</strong> เมื่อพร้อม production</li>
                    <li>ตั้ง Webhook ที่ Developers → Webhooks<br><small style="color:#94a3b8;">URL: <code><?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/index.php?page=payment_webhook&gateway=stripe</code></small></li>
                </ol>
            </div>
            <div class="guide-section">
                <h5><i class="fa fa-paypal" style="color:#0070ba;"></i> PayPal</h5>
                <ol>
                    <li>ไปที่ <a href="https://developer.paypal.com/dashboard/applications" target="_blank">PayPal Developer → My Apps</a></li>
                    <li>สร้าง App ใหม่หรือใช้ที่มีอยู่</li>
                    <li>คัดลอก <strong>Client ID</strong> และ <strong>Secret</strong></li>
                    <li>ตั้ง Mode เป็น <strong>sandbox</strong> ก่อนทดสอบ</li>
                    <li>ตั้ง Webhook URL:<br><small style="color:#94a3b8;"><code><?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/index.php?page=payment_webhook&gateway=paypal</code></small></li>
                </ol>
            </div>
            <div class="guide-section">
                <h5><i class="fa fa-qrcode" style="color:#06b6d4;"></i> PromptPay</h5>
                <ol>
                    <li>ติดต่อธนาคารเพื่อขอ Merchant ID</li>
                    <li>รับ QR Code สำหรับรับชำระ</li>
                    <li>ลูกค้าสแกน QR แล้วส่งหลักฐานการโอน</li>
                    <li>ใช้ระบบ Slip Review เพื่ออนุมัติการชำระ</li>
                </ol>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Enable / disable gateway toggle (AJAX)
function toggleGateway(checkbox) {
    var gwId   = checkbox.dataset.gwId;
    var code   = checkbox.dataset.code;
    var active = checkbox.checked ? '1' : '0';
    var label  = document.getElementById('toggle-lbl-' + code);
    var card   = checkbox.closest('.gw-card');

    var csrfInput = document.querySelector('#form-' + code + ' input[name="csrf_token"]')
                 || document.querySelector('input[name="csrf_token"]');
    var csrfToken = csrfInput ? csrfInput.value : '';

    var data = new FormData();
    data.append('gateway_id', gwId);
    data.append('active', active);
    data.append('csrf_token', csrfToken);

    checkbox.disabled = true;

    fetch('index.php?page=payment_gateway_toggle', { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(r) {
            if (r.success) {
                var isOn = r.active;
                if (label) label.textContent = isOn ? '<?= $isThai ? 'เปิดใช้' : 'Enabled' ?>' : '<?= $isThai ? 'ปิดอยู่' : 'Disabled' ?>';
                if (card) {
                    card.classList.toggle('gw-disabled', !isOn);
                }
            } else {
                // Revert
                checkbox.checked = !checkbox.checked;
                alert(r.message || 'Failed to update');
            }
        })
        .catch(function() {
            checkbox.checked = !checkbox.checked;
        })
        .finally(function() {
            checkbox.disabled = false;
        });
}

function togglePw(fieldId, btn) {
    var input = document.getElementById(fieldId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}

function copyVal(fieldId) {
    var input = document.getElementById(fieldId);
    var val = input.value;
    if (!val) return;
    navigator.clipboard.writeText(val).then(function() {
        // brief visual feedback
        var btn = document.querySelector('[onclick="copyVal(\'' + fieldId + '\')"]');
        if (btn) {
            btn.innerHTML = '<i class="fa fa-check"></i>';
            setTimeout(function() { btn.innerHTML = '<i class="fa fa-copy"></i>'; }, 1200);
        }
    });
}

function updateMode(select, code) {
    var pill = document.getElementById('mode-pill-' + code);
    if (!pill) return;
    var isLive = (select.value === 'live' || select.value === 'production');
    if (isLive) {
        if (!confirm('⚠️ Switching to LIVE mode — real transactions will be processed. Continue?')) {
            select.value = 'sandbox';
            return;
        }
    }
    pill.className = 'mode-pill ' + (isLive ? 'live' : 'sandbox');
    pill.textContent = isLive ? '⚡ LIVE' : '🧪 TEST';
}

// AJAX save config — shows result inline within the card
function saveGateway(code, btn) {
    var form   = document.getElementById('form-' + code);
    var result = document.getElementById('test-result-' + code);
    if (!form || !result) return;

    var data = new FormData(form);
    data.set('action', 'save_config');

    var origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?= $isThai ? 'กำลังบันทึก...' : 'Saving...' ?>';
    result.style.display = 'none';

    fetch('index.php?page=payment_gateway_save', { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(r) {
            var ok = r.success === true || r.success === 1;
            result.style.display = 'flex';
            result.style.background = ok ? '#f0fdf4' : '#fef2f2';
            result.style.border     = ok ? '1px solid #bbf7d0' : '1px solid #fecaca';
            result.style.color      = ok ? '#065f46' : '#991b1b';
            result.innerHTML = '<i class="fa fa-' + (ok ? 'check-circle' : 'times-circle') + '" style="font-size:16px; flex-shrink:0;"></i><span>' + (r.message || (ok ? 'Saved' : 'Save failed')) + '</span>';
        })
        .catch(function() {
            result.style.display = 'flex';
            result.style.background = '#fef2f2';
            result.style.border = '1px solid #fecaca';
            result.style.color = '#991b1b';
            result.innerHTML = '<i class="fa fa-exclamation-triangle" style="font-size:16px; flex-shrink:0;"></i><span><?= $isThai ? 'เกิดข้อผิดพลาด กรุณาลองใหม่' : 'Error — please try again' ?></span>';
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        });
}

// AJAX test connection — shows result inline within the card
function testGateway(code, btn) {
    var form   = document.getElementById('form-' + code);
    var result = document.getElementById('test-result-' + code);
    if (!form || !result) return;

    // Collect config values from the form
    var data = new FormData();
    data.append('gateway', code);
    form.querySelectorAll('[name^="config["]').forEach(function(el) {
        var key = el.name.replace(/^config\[/, '').replace(/\]$/, '');
        data.append('config[' + key + ']', el.value);
    });

    // Loading state
    var origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?= $isThai ? 'กำลังทดสอบ...' : 'Testing...' ?>';
    result.style.display = 'none';

    fetch('index.php?page=payment_gateway_test', { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(r) {
            var ok = r.success === true || r.success === 1;
            result.style.display = 'flex';
            result.style.background = ok ? '#f0fdf4' : '#fef2f2';
            result.style.border     = ok ? '1px solid #bbf7d0' : '1px solid #fecaca';
            result.style.color      = ok ? '#065f46' : '#991b1b';
            result.innerHTML = '<i class="fa fa-' + (ok ? 'check-circle' : 'times-circle') + '" style="font-size:16px; flex-shrink:0;"></i><span>' + (r.message || (ok ? 'Connection OK' : 'Connection failed')) + '</span>';
        })
        .catch(function(err) {
            result.style.display = 'flex';
            result.style.background = '#fef2f2';
            result.style.border = '1px solid #fecaca';
            result.style.color = '#991b1b';
            result.innerHTML = '<i class="fa fa-exclamation-triangle" style="font-size:16px; flex-shrink:0;"></i><span><?= $isThai ? 'เกิดข้อผิดพลาด กรุณาลองใหม่' : 'Error — please try again' ?></span>';
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        });
}
</script>
