<?php
$pageTitle = 'Payment Methods — New';

/**
 * Payment Method Form View
 * 
 * Variables provided by PaymentMethodController::form():
 *   $data - payment method data (defaults for new, or existing record)
 *   $mode - 'A' (add) or 'E' (edit)
 *   $id   - payment method ID (0 for new)
 *   $xml  - i18n strings
 */

// Common Font Awesome icons for payment
$common_icons = [
    'fa-money' => 'Money', 'fa-university' => 'Bank', 'fa-credit-card' => 'Credit Card',
    'fa-credit-card-alt' => 'Credit Card Alt', 'fa-file-text-o' => 'Document',
    'fa-paypal' => 'PayPal', 'fa-cc-stripe' => 'Stripe', 'fa-cc-visa' => 'Visa',
    'fa-cc-mastercard' => 'MasterCard', 'fa-cc-amex' => 'Amex', 'fa-cc-discover' => 'Discover',
    'fa-cc-paypal' => 'PayPal CC', 'fa-bitcoin' => 'Bitcoin', 'fa-google-wallet' => 'Google Wallet',
    'fa-apple' => 'Apple Pay', 'fa-android' => 'Android Pay', 'fa-globe' => 'Globe',
    'fa-exchange' => 'Exchange', 'fa-shopping-cart' => 'Shopping Cart', 'fa-qrcode' => 'QR Code'
];
?>

<style>
.payment-form-container { padding: 20px; background: #f5f6fa; min-height: 100vh; }
.page-header-pm { background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%); color: white; padding: 25px 30px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3); }
.page-header-pm h2 { margin: 0 0 5px 0; font-size: 28px; font-weight: 600; }
.page-header-pm p { margin: 0; opacity: 0.9; }
.btn-back { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 6px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
.btn-back:hover { background: rgba(255,255,255,0.3); color: white; text-decoration: none; }
.form-card { background: white; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
.card-header-f { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; padding: 15px 20px; font-weight: 600; font-size: 16px; display: flex; align-items: center; gap: 10px; }
.card-body-f { padding: 25px; }
.form-row-f { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
.form-col { flex: 1; min-width: 200px; }
.form-group-f { margin-bottom: 0; }
.form-group-f label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; font-size: 14px; }
.form-group-f .required { color: #e74c3c; }
.form-group-f .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
.form-group-f .form-control:focus { border-color: #8e44ad; outline: none; box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1); }
.form-group-f textarea.form-control { resize: vertical; min-height: 100px; }
.icon-selector { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; max-height: 200px; overflow-y: auto; padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd; }
.icon-option { display: flex; flex-direction: column; align-items: center; padding: 10px; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s; }
.icon-option:hover { background: #e9ecef; }
.icon-option.selected { background: #8e44ad; color: white; border-color: #6c3483; }
.icon-option i { font-size: 24px; margin-bottom: 5px; }
.icon-option span { font-size: 10px; text-align: center; }
.switch-group { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.switch-item { display: flex; align-items: center; gap: 10px; padding: 15px 20px; background: #f8f9fa; border-radius: 8px; flex: 1; min-width: 200px; }
.switch-item .switch-info h4 { margin: 0 0 3px 0; font-size: 14px; font-weight: 600; }
.switch-item .switch-info p { margin: 0; font-size: 12px; color: #777; }
.toggle-switch { position: relative; width: 50px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 26px; }
.toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
input:checked + .toggle-slider { background-color: #27ae60; }
input:checked + .toggle-slider.gateway { background-color: #3498db; }
input:checked + .toggle-slider:before { transform: translateX(24px); }
.preview-box { background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 10px; padding: 30px; text-align: center; color: white; }
.preview-icon { width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 36px; }
.preview-name { font-size: 22px; font-weight: 600; margin-bottom: 5px; }
.preview-code { font-size: 13px; opacity: 0.8; background: rgba(255,255,255,0.2); padding: 3px 12px; border-radius: 20px; display: inline-block; }
.pm-form-actions { display: flex; gap: 15px; padding-top: 20px; border-top: 1px solid #eee; margin-top: 25px; }
.btn-pm-submit { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 15px; }
.btn-pm-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(142, 68, 173, 0.4); }
.btn-pm-cancel { background: #6c757d; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 15px; }
.btn-pm-cancel:hover { background: #5a6268; color: white; text-decoration: none; }
</style>

<div class="payment-form-container">
    <div class="page-header-pm">
        <div class="row">
            <div class="col-md-8">
                <h2><i class="fa <?=$mode=='A'?'fa-plus-circle':'fa-edit'?>"></i> <?=$mode=='A' ? ($xml->addpaymentmethod ?? 'Add Payment Method') : ($xml->editpaymentmethod ?? 'Edit Payment Method')?></h2>
                <p><?=$xml->filldetailsbelow ?? 'Fill in the details below to configure payment method'?></p>
            </div>
            <div class="col-md-4 text-right">
                <a href="index.php?page=payment_method_list" class="btn-back"><i class="fa fa-arrow-left"></i> <?=$xml->backlist ?? 'Back to List'?></a>
            </div>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:12px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; color:#991b1b;">
        <i class="fa fa-exclamation-triangle" style="margin-right:6px;"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <form method="post" action="index.php?page=payment_method_store" id="paymentMethodForm">
        <?=csrf_field()?>
        <input type="hidden" name="mode" value="<?=$mode?>">
        <input type="hidden" name="id" value="<?=$id?>">
        <div class="row">
            <div class="col-md-8">
                <div class="form-card">
                    <div class="card-header-f"><i class="fa fa-info-circle"></i> <?=$xml->basicinformation ?? 'Basic Information'?></div>
                    <div class="card-body-f">
                        <div class="form-row-f">
                            <div class="form-col">
                                <div class="form-group-f">
                                    <label><?=$xml->code ?? 'Code'?> <span class="required">*</span></label>
                                    <input type="text" name="code" id="code" class="form-control" value="<?=htmlspecialchars($data['code'])?>" placeholder="e.g., paypal, stripe, bank_abc" required pattern="[a-z0-9_]+" title="Only lowercase letters, numbers, and underscores">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group-f">
                                    <label><?=$xml->sortorder ?? 'Sort Order'?></label>
                                    <input type="number" name="sort_order" class="form-control" value="<?=$data['sort_order']?>" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="form-row-f">
                            <div class="form-col">
                                <div class="form-group-f">
                                    <label><?=$xml->englishname ?? 'English Name'?> <span class="required">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" value="<?=htmlspecialchars($data['name'])?>" placeholder="e.g., PayPal, Stripe" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group-f">
                                    <label><?=$xml->thainame ?? 'Thai Name'?></label>
                                    <input type="text" name="name_th" id="name_th" class="form-control" value="<?=htmlspecialchars($data['name_th'] ?? '')?>" placeholder="e.g., เพย์พาล, สไตรพ์">
                                </div>
                            </div>
                        </div>
                        <div class="form-group-f">
                            <label><?=$xml->description ?? 'Description'?></label>
                            <textarea name="description" class="form-control" placeholder="<?=$xml->optionaldescription ?? 'Optional description for this payment method'?>"><?=htmlspecialchars($data['description'] ?? '')?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-header-f"><i class="fa fa-image"></i> <?=$xml->selecticon ?? 'Select Icon'?></div>
                    <div class="card-body-f">
                        <input type="hidden" name="icon" id="icon" value="<?=htmlspecialchars($data['icon'])?>">
                        <div class="icon-selector">
                            <?php foreach ($common_icons as $icon_class => $icon_name): ?>
                            <div class="icon-option <?=$data['icon']==$icon_class?'selected':''?>" data-icon="<?=$icon_class?>" onclick="selectIcon('<?=$icon_class?>')">
                                <i class="fa <?=$icon_class?>"></i>
                                <span><?=$icon_name?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 15px;">
                            <label><?=$xml->customicon ?? 'Or enter custom Font Awesome icon class'?>:</label>
                            <input type="text" id="customIcon" class="form-control" placeholder="e.g., fa-btc, fa-amazon" value="<?=!in_array($data['icon'], array_keys($common_icons)) ? htmlspecialchars($data['icon']) : ''?>" onchange="selectIcon(this.value)">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-header-f"><i class="fa fa-cog"></i> <?=$xml->settings ?? 'Settings'?></div>
                    <div class="card-body-f">
                        <div class="switch-group">
                            <div class="switch-item">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_gateway" <?=$data['is_gateway']?'checked':''?>>
                                    <span class="toggle-slider gateway"></span>
                                </label>
                                <div class="switch-info">
                                    <h4><?=$xml->paymentgateway ?? 'Payment Gateway'?></h4>
                                    <p><?=$xml->isonlinepaymentgateway ?? 'This is an online payment gateway'?></p>
                                </div>
                            </div>
                            <div class="switch-item">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_active" <?=$data['is_active']?'checked':''?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <div class="switch-info">
                                    <h4><?=$xml->active ?? 'Active'?></h4>
                                    <p><?=$xml->enablethismethod ?? 'Enable this payment method for use'?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-card">
                    <div class="card-header-f"><i class="fa fa-eye"></i> <?=$xml->preview ?? 'Preview'?></div>
                    <div class="card-body-f" style="padding: 0;">
                        <div class="preview-box">
                            <div class="preview-icon"><i class="fa" id="previewIcon"></i></div>
                            <div class="preview-name" id="previewName"><?=htmlspecialchars($data['name'] ?: 'Payment Method')?></div>
                            <div class="preview-code" id="previewCode"><?=htmlspecialchars($data['code'] ?: 'code')?></div>
                        </div>
                    </div>
                </div>
                <div class="form-card">
                    <div class="card-header-f"><i class="fa fa-question-circle"></i> <?=$xml->help ?? 'Help'?></div>
                    <div class="card-body-f">
                        <p><strong><?=$xml->code ?? 'Code'?>:</strong> <?=$xml->codehelp ?? 'Unique identifier used in system.'?></p>
                        <p><strong><?=$xml->paymentgateway ?? 'Payment Gateway'?>:</strong> <?=$xml->gatewayhelp ?? 'Enable for online payment gateways.'?></p>
                        <p><strong><?=$xml->sortorder ?? 'Sort Order'?>:</strong> <?=$xml->sortorderhelp ?? 'Lower numbers appear first.'?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-body-f">
                <div class="pm-form-actions">
                    <button type="submit" class="btn-pm-submit"><i class="fa fa-save"></i> <?=$mode=='A' ? ($xml->create ?? 'Create') : ($xml->update ?? 'Update')?></button>
                    <a href="index.php?page=payment_method_list" class="btn-pm-cancel"><i class="fa fa-times"></i> <?=$xml->cancel ?? 'Cancel'?></a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() { updatePreview(); });
function selectIcon(iconClass) {
    document.querySelectorAll('.icon-option').forEach(function(el) { el.classList.remove('selected'); });
    var option = document.querySelector('.icon-option[data-icon="' + iconClass + '"]');
    if (option) option.classList.add('selected');
    document.getElementById('icon').value = iconClass;
    updatePreview();
}
function updatePreview() {
    var icon = document.getElementById('icon').value || 'fa-money';
    var name = document.getElementById('name').value || 'Payment Method';
    var code = document.getElementById('code').value || 'code';
    document.getElementById('previewIcon').className = 'fa ' + icon;
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewCode').textContent = code;
}
document.getElementById('name').addEventListener('input', updatePreview);
document.getElementById('code').addEventListener('input', updatePreview);
</script>
