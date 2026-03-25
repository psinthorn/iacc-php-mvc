<?php
/**
 * Payment Method Form Page
 * Add/Edit payment method
 */

require_once("inc/class.company_filter.php");
$companyFilter = CompanyFilter::getInstance();

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'A';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = mysqli_real_escape_string($db->conn, $_POST['code']);
    $name = mysqli_real_escape_string($db->conn, $_POST['name']);
    $name_th = mysqli_real_escape_string($db->conn, $_POST['name_th']);
    $icon = mysqli_real_escape_string($db->conn, $_POST['icon']);
    $description = mysqli_real_escape_string($db->conn, $_POST['description']);
    $is_gateway = isset($_POST['is_gateway']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order']);
    $company_id = $companyFilter->getSafeCompanyId();
    
    if($mode === 'E' && $id > 0) {
        // Update existing (with company check)
        $sql = "UPDATE payment_method SET 
                code = '$code',
                name = '$name',
                name_th = '$name_th',
                icon = '$icon',
                description = '$description',
                is_gateway = $is_gateway,
                is_active = $is_active,
                sort_order = $sort_order
                WHERE id = $id " . $companyFilter->andCompanyFilter();
        mysqli_query($db->conn, $sql);
    } else {
        // Insert new (with company_id)
        $sql = "INSERT INTO payment_method (code, name, name_th, icon, description, is_gateway, is_active, sort_order, company_id) 
                VALUES ('$code', '$name', '$name_th', '$icon', '$description', $is_gateway, $is_active, $sort_order, $company_id)";
        mysqli_query($db->conn, $sql);
    }
    
    echo "<script>window.location.href='index.php?page=payment_method_list';</script>";
    exit;
}

// Get existing data for edit mode
$data = [
    'code' => '',
    'name' => '',
    'name_th' => '',
    'icon' => 'fa-money',
    'description' => '',
    'is_gateway' => 0,
    'is_active' => 1,
    'sort_order' => 0
];

if($mode === 'E' && $id > 0) {
    $result = mysqli_query($db->conn, "SELECT * FROM payment_method WHERE id = $id " . $companyFilter->andCompanyFilter());
    if($row = mysqli_fetch_assoc($result)) {
        $data = $row;
    }
}

// Get next sort order for new entries (with company filter)
if($mode === 'A') {
    $companyCondition = $companyFilter->hasCompany() ? "WHERE company_id = " . $companyFilter->getSafeCompanyId() : "";
    $max_order = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT MAX(sort_order) as max_order FROM payment_method $companyCondition"));
    $data['sort_order'] = ($max_order['max_order'] ?? 0) + 1;
}

// Common Font Awesome icons for payment
$common_icons = [
    'fa-money' => 'Money',
    'fa-university' => 'Bank',
    'fa-credit-card' => 'Credit Card',
    'fa-credit-card-alt' => 'Credit Card Alt',
    'fa-file-text-o' => 'Document',
    'fa-paypal' => 'PayPal',
    'fa-cc-stripe' => 'Stripe',
    'fa-cc-visa' => 'Visa',
    'fa-cc-mastercard' => 'MasterCard',
    'fa-cc-amex' => 'Amex',
    'fa-cc-discover' => 'Discover',
    'fa-cc-paypal' => 'PayPal CC',
    'fa-bitcoin' => 'Bitcoin',
    'fa-google-wallet' => 'Google Wallet',
    'fa-apple' => 'Apple Pay',
    'fa-android' => 'Android Pay',
    'fa-globe' => 'Globe',
    'fa-exchange' => 'Exchange',
    'fa-shopping-cart' => 'Shopping Cart',
    'fa-qrcode' => 'QR Code'
];
?>

<style>
.payment-form-container {
    padding: 20px;
    background: #f5f6fa;
    min-height: 100vh;
}

.page-header-pm {
    background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3);
}

.page-header-pm h2 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 600;
}

.page-header-pm p {
    margin: 0;
    opacity: 0.9;
}

.btn-back {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-back:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    text-decoration: none;
}

/* Form Card */
.form-card {
    background: white;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 25px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.form-col {
    flex: 1;
    min-width: 200px;
}

.form-col-2 {
    flex: 2;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-group label .required {
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #8e44ad;
    outline: none;
    box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

/* Icon Selector */
.icon-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.icon-option:hover {
    background: #e9ecef;
}

.icon-option.selected {
    background: #8e44ad;
    color: white;
    border-color: #6c3483;
}

.icon-option i {
    font-size: 24px;
    margin-bottom: 5px;
}

.icon-option span {
    font-size: 10px;
    text-align: center;
}

/* Checkbox Switches */
.switch-group {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.switch-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    flex: 1;
    min-width: 200px;
}

.switch-item .switch-info h4 {
    margin: 0 0 3px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.switch-item .switch-info p {
    margin: 0;
    font-size: 12px;
    color: #777;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #27ae60;
}

input:checked + .toggle-slider.gateway {
    background-color: #3498db;
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Preview Box */
.preview-box {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    color: white;
}

.preview-icon {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 36px;
}

.preview-name {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 5px;
}

.preview-code {
    font-size: 13px;
    opacity: 0.8;
    background: rgba(255,255,255,0.2);
    padding: 3px 12px;
    border-radius: 20px;
    display: inline-block;
}

/* Action Buttons */
.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    margin-top: 25px;
}

.btn-submit {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(142, 68, 173, 0.4);
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
}

.btn-cancel:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    
    .form-col, .form-col-2 {
        flex: none;
        width: 100%;
    }
    
    .switch-group {
        flex-direction: column;
    }
    
    .switch-item {
        width: 100%;
    }
}
</style>

<div class="payment-form-container">
    <!-- Page Header -->
    <div class="page-header-pm">
        <div class="row">
            <div class="col-md-8">
                <h2>
                    <i class="fa <?=$mode=='A'?'fa-plus-circle':'fa-edit'?>"></i> 
                    <?=$mode=='A' ? ($xml->addpaymentmethod ?? 'Add Payment Method') : ($xml->editpaymentmethod ?? 'Edit Payment Method')?>
                </h2>
                <p><?=$xml->filldetailsbelow ?? 'Fill in the details below to configure payment method'?></p>
            </div>
            <div class="col-md-4 text-right">
                <a href="index.php?page=payment_method_list" class="btn-back">
                    <i class="fa fa-arrow-left"></i> <?=$xml->backlist ?? 'Back to List'?>
                </a>
            </div>
        </div>
    </div>

    <form method="post" id="paymentMethodForm">
        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="form-card">
                    <div class="card-header">
                        <i class="fa fa-info-circle"></i>
                        <?=$xml->basicinformation ?? 'Basic Information'?>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label><?=$xml->code ?? 'Code'?> <span class="required">*</span></label>
                                    <input type="text" name="code" id="code" class="form-control" 
                                           value="<?=e($data['code'])?>" 
                                           placeholder="e.g., paypal, stripe, bank_abc"
                                           required pattern="[a-z0-9_]+" 
                                           title="Only lowercase letters, numbers, and underscores">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label><?=$xml->sortorder ?? 'Sort Order'?></label>
                                    <input type="number" name="sort_order" class="form-control" 
                                           value="<?=$data['sort_order']?>" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label><?=$xml->englishname ?? 'English Name'?> <span class="required">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" 
                                           value="<?=e($data['name'])?>" 
                                           placeholder="e.g., PayPal, Stripe" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label><?=$xml->thainame ?? 'Thai Name'?></label>
                                    <input type="text" name="name_th" id="name_th" class="form-control" 
                                           value="<?=e($data['name_th'] ?? '')?>" 
                                           placeholder="e.g., เพย์พาล, สไตรพ์">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><?=$xml->description ?? 'Description'?></label>
                            <textarea name="description" class="form-control" 
                                      placeholder="<?=$xml->optionaldescription ?? 'Optional description for this payment method'?>"><?=e($data['description'] ?? '')?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Icon Selection -->
                <div class="form-card">
                    <div class="card-header">
                        <i class="fa fa-image"></i>
                        <?=$xml->selecticon ?? 'Select Icon'?>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="icon" id="icon" value="<?=e($data['icon'])?>">
                        <div class="icon-selector">
                            <?php foreach($common_icons as $icon_class => $icon_name): ?>
                            <div class="icon-option <?=$data['icon']==$icon_class?'selected':''?>" 
                                 data-icon="<?=$icon_class?>" onclick="selectIcon('<?=$icon_class?>')">
                                <i class="fa <?=$icon_class?>"></i>
                                <span><?=$icon_name?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 15px;">
                            <label><?=$xml->customicon ?? 'Or enter custom Font Awesome icon class'?>:</label>
                            <input type="text" id="customIcon" class="form-control" 
                                   placeholder="e.g., fa-btc, fa-amazon" 
                                   value="<?=!in_array($data['icon'], array_keys($common_icons)) ? $data['icon'] : ''?>"
                                   onchange="selectIcon(this.value)">
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="form-card">
                    <div class="card-header">
                        <i class="fa fa-cog"></i>
                        <?=$xml->settings ?? 'Settings'?>
                    </div>
                    <div class="card-body">
                        <div class="switch-group">
                            <div class="switch-item">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_gateway" <?=$data['is_gateway']?'checked':''?>>
                                    <span class="toggle-slider gateway"></span>
                                </label>
                                <div class="switch-info">
                                    <h4><?=$xml->paymentgateway ?? 'Payment Gateway'?></h4>
                                    <p><?=$xml->isonlinepaymentgateway ?? 'This is an online payment gateway (PayPal, Stripe, etc.)'?></p>
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
                <!-- Preview -->
                <div class="form-card">
                    <div class="card-header">
                        <i class="fa fa-eye"></i>
                        <?=$xml->preview ?? 'Preview'?>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="preview-box">
                            <div class="preview-icon">
                                <i class="fa" id="previewIcon"></i>
                            </div>
                            <div class="preview-name" id="previewName"><?=$data['name'] ?: 'Payment Method'?></div>
                            <div class="preview-code" id="previewCode"><?=$data['code'] ?: 'code'?></div>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="form-card">
                    <div class="card-header">
                        <i class="fa fa-question-circle"></i>
                        <?=$xml->help ?? 'Help'?>
                    </div>
                    <div class="card-body">
                        <p><strong><?=$xml->code ?? 'Code'?>:</strong> <?=$xml->codehelp ?? 'Unique identifier used in system. Use lowercase letters, numbers, and underscores only.'?></p>
                        <p><strong><?=$xml->paymentgateway ?? 'Payment Gateway'?>:</strong> <?=$xml->gatewayhelp ?? 'Enable this if the payment method is an online payment gateway like PayPal or Stripe.'?></p>
                        <p><strong><?=$xml->sortorder ?? 'Sort Order'?>:</strong> <?=$xml->sortorderhelp ?? 'Lower numbers appear first in dropdown lists.'?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-card">
            <div class="card-body">
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fa fa-save"></i> <?=$mode=='A' ? ($xml->create ?? 'Create') : ($xml->update ?? 'Update')?>
                    </button>
                    <a href="index.php?page=payment_method_list" class="btn-cancel">
                        <i class="fa fa-times"></i> <?=$xml->cancel ?? 'Cancel'?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Initialize preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});

// Select icon
function selectIcon(iconClass) {
    // Remove selected from all
    document.querySelectorAll('.icon-option').forEach(function(el) {
        el.classList.remove('selected');
    });
    
    // Add selected to clicked
    var option = document.querySelector('.icon-option[data-icon="' + iconClass + '"]');
    if(option) {
        option.classList.add('selected');
    }
    
    // Update hidden input
    document.getElementById('icon').value = iconClass;
    
    // Update preview
    updatePreview();
}

// Update preview
function updatePreview() {
    var icon = document.getElementById('icon').value || 'fa-money';
    var name = document.getElementById('name').value || 'Payment Method';
    var code = document.getElementById('code').value || 'code';
    
    document.getElementById('previewIcon').className = 'fa ' + icon;
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewCode').textContent = code;
}

// Listen for input changes
document.getElementById('name').addEventListener('input', updatePreview);
document.getElementById('code').addEventListener('input', updatePreview);
</script>
