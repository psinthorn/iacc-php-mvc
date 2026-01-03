<?php
/**
 * Payment Gateway Configuration Page
 * Admin interface for configuring PayPal and Stripe API credentials
 */

// Include payment method helper
require_once __DIR__ . '/inc/payment-method-helper.php';

// Check admin access (level >= 2)
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit;
}

// Get database connection
$conn = $GLOBALS['conn'] ?? null;

// Get payment gateways
$gateways = [];
$gatewayConfigs = [];

$sql = "SELECT pm.*, 
        (SELECT GROUP_CONCAT(CONCAT(pgc.config_key, ':', COALESCE(pgc.config_value, '')) SEPARATOR '||') 
         FROM payment_gateway_config pgc WHERE pgc.payment_method_id = pm.id) as configs
        FROM payment_method pm 
        WHERE pm.is_gateway = 1 AND pm.is_active = 1 
        ORDER BY pm.sort_order, pm.name";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $gateways[] = $row;
    
    // Parse configs
    $configs = [];
    if (!empty($row['configs'])) {
        $pairs = explode('||', $row['configs']);
        foreach ($pairs as $pair) {
            list($key, $value) = explode(':', $pair, 2);
            $configs[$key] = $value;
        }
    }
    $gatewayConfigs[$row['id']] = $configs;
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config') {
        $gatewayId = intval($_POST['gateway_id'] ?? 0);
        $configs = $_POST['config'] ?? [];
        
        if ($gatewayId > 0 && !empty($configs)) {
            $success = true;
            
            foreach ($configs as $key => $value) {
                // Check if config exists
                $checkSql = "SELECT id FROM payment_gateway_config WHERE payment_method_id = ? AND config_key = ?";
                $checkStmt = mysqli_prepare($conn, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "is", $gatewayId, $key);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                
                if (mysqli_num_rows($checkResult) > 0) {
                    // Update existing
                    $updateSql = "UPDATE payment_gateway_config SET config_value = ?, updated_at = NOW() 
                                  WHERE payment_method_id = ? AND config_key = ?";
                    $updateStmt = mysqli_prepare($conn, $updateSql);
                    mysqli_stmt_bind_param($updateStmt, "sis", $value, $gatewayId, $key);
                    if (!mysqli_stmt_execute($updateStmt)) {
                        $success = false;
                    }
                } else {
                    // Insert new
                    $isEncrypted = in_array($key, ['client_secret', 'secret_key', 'webhook_secret']) ? 1 : 0;
                    $insertSql = "INSERT INTO payment_gateway_config (payment_method_id, config_key, config_value, is_encrypted) 
                                  VALUES (?, ?, ?, ?)";
                    $insertStmt = mysqli_prepare($conn, $insertSql);
                    mysqli_stmt_bind_param($insertStmt, "issi", $gatewayId, $key, $value, $isEncrypted);
                    if (!mysqli_stmt_execute($insertStmt)) {
                        $success = false;
                    }
                }
            }
            
            if ($success) {
                $message = 'Configuration saved successfully!';
                $messageType = 'success';
                
                // Reload configs
                header("Location: index.php?page=payment_gateway_config&saved=1");
                exit;
            } else {
                $message = 'Error saving configuration.';
                $messageType = 'danger';
            }
        }
    }
}

// Check for saved message
if (isset($_GET['saved'])) {
    $message = 'Configuration saved successfully!';
    $messageType = 'success';
}

// Gateway field definitions
$gatewayFields = [
    'paypal' => [
        'mode' => ['label' => 'Mode', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox (Testing)', 'live' => 'Live (Production)'], 'required' => true],
        'client_id' => ['label' => 'Client ID', 'type' => 'text', 'placeholder' => 'Your PayPal Client ID', 'required' => true],
        'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'placeholder' => 'Your PayPal Client Secret', 'required' => true, 'encrypted' => true],
        'webhook_id' => ['label' => 'Webhook ID', 'type' => 'text', 'placeholder' => 'PayPal Webhook ID (optional)', 'required' => false],
        'return_url' => ['label' => 'Return URL', 'type' => 'text', 'placeholder' => '/payment/paypal/success', 'required' => true],
        'cancel_url' => ['label' => 'Cancel URL', 'type' => 'text', 'placeholder' => '/payment/paypal/cancel', 'required' => true],
    ],
    'stripe' => [
        'mode' => ['label' => 'Mode', 'type' => 'select', 'options' => ['test' => 'Test Mode', 'live' => 'Live (Production)'], 'required' => true],
        'publishable_key' => ['label' => 'Publishable Key', 'type' => 'text', 'placeholder' => 'pk_test_... or pk_live_...', 'required' => true],
        'secret_key' => ['label' => 'Secret Key', 'type' => 'password', 'placeholder' => 'sk_test_... or sk_live_...', 'required' => true, 'encrypted' => true],
        'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'whsec_... (optional)', 'required' => false, 'encrypted' => true],
        'currency' => ['label' => 'Default Currency', 'type' => 'select', 'options' => ['thb' => 'THB - Thai Baht', 'usd' => 'USD - US Dollar', 'eur' => 'EUR - Euro', 'gbp' => 'GBP - British Pound'], 'required' => true],
    ],
];
?>

<style>
:root {
    --primary-color: #8e44ad;
    --primary-dark: #6c3483;
    --primary-light: #9b59b6;
    --success-color: #27ae60;
    --danger-color: #e74c3c;
    --warning-color: #f39c12;
    --info-color: #3498db;
}

.gateway-config-page {
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(142, 68, 173, 0.3);
}

.page-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}

.page-header p {
    margin: 10px 0 0;
    opacity: 0.9;
}

.gateway-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    overflow: hidden;
}

.gateway-header {
    padding: 20px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #eee;
}

.gateway-header.paypal {
    background: linear-gradient(135deg, #003087, #009cde);
    color: white;
}

.gateway-header.stripe {
    background: linear-gradient(135deg, #635bff, #7c3aed);
    color: white;
}

.gateway-title {
    display: flex;
    align-items: center;
    gap: 15px;
}

.gateway-title i {
    font-size: 2rem;
}

.gateway-title h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.gateway-status {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #f39c12;
}

.status-indicator.configured {
    background: #27ae60;
    box-shadow: 0 0 10px rgba(39, 174, 96, 0.5);
}

.status-indicator.not-configured {
    background: #e74c3c;
}

.status-text {
    font-size: 0.9rem;
    opacity: 0.9;
}

.gateway-body {
    padding: 25px;
}

.config-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-group label .required {
    color: var(--danger-color);
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
}

.form-control.paypal-field:focus {
    border-color: #003087;
    box-shadow: 0 0 0 3px rgba(0, 48, 135, 0.1);
}

.form-control.stripe-field:focus {
    border-color: #635bff;
    box-shadow: 0 0 0 3px rgba(99, 91, 255, 0.1);
}

.input-group {
    display: flex;
    gap: 10px;
}

.input-group .form-control {
    flex: 1;
}

.btn-toggle-password {
    background: #f0f0f0;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 0 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-toggle-password:hover {
    background: #e0e0e0;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-save {
    background: linear-gradient(135deg, var(--success-color), #219a52);
    color: white;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-test {
    background: linear-gradient(135deg, var(--info-color), #2980b9);
    color: white;
}

.btn-test:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-paypal {
    background: linear-gradient(135deg, #003087, #009cde);
}

.btn-stripe {
    background: linear-gradient(135deg, #635bff, #7c3aed);
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: rgba(39, 174, 96, 0.1);
    border: 1px solid rgba(39, 174, 96, 0.3);
    color: var(--success-color);
}

.alert-danger {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.3);
    color: var(--danger-color);
}

.help-text {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.webhook-info {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid rgba(52, 152, 219, 0.3);
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
}

.webhook-info h5 {
    margin: 0 0 10px;
    color: var(--info-color);
}

.webhook-url {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    font-family: monospace;
    font-size: 0.9rem;
    word-break: break-all;
}

.copy-btn {
    background: var(--info-color);
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    margin-left: 10px;
    font-size: 0.8rem;
}

.mode-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.mode-badge.sandbox, .mode-badge.test {
    background: rgba(243, 156, 18, 0.2);
    color: #d68910;
}

.mode-badge.live {
    background: rgba(39, 174, 96, 0.2);
    color: var(--success-color);
}

/* Test Modal */
.test-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.test-modal.show {
    display: flex;
}

.test-modal-content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    text-align: center;
}

.test-result {
    margin-top: 20px;
}

.test-result.success {
    color: var(--success-color);
}

.test-result.error {
    color: var(--danger-color);
}

.spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
    opacity: 0.9;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.back-link:hover {
    opacity: 1;
}
</style>

<div class="gateway-config-page">
    <div class="page-header">
        <a href="index.php?page=payment_method_list" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Payment Methods
        </a>
        <h1><i class="fa fa-cogs"></i> Payment Gateway Configuration</h1>
        <p>Configure your PayPal and Stripe API credentials for online payments</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fa fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <?php foreach ($gateways as $gateway): ?>
    <?php 
        $code = strtolower($gateway['code']);
        $fields = $gatewayFields[$code] ?? [];
        $configs = $gatewayConfigs[$gateway['id']] ?? [];
        $isConfigured = !empty($configs['client_id'] ?? $configs['publishable_key'] ?? '');
        $mode = $configs['mode'] ?? '';
    ?>
    <div class="gateway-card">
        <div class="gateway-header <?php echo $code; ?>">
            <div class="gateway-title">
                <i class="fa fa-<?php echo $code === 'paypal' ? 'paypal' : 'cc-stripe'; ?>"></i>
                <h3><?php echo htmlspecialchars($gateway['name']); ?></h3>
                <?php if ($mode): ?>
                <span class="mode-badge <?php echo $mode; ?>"><?php echo ucfirst($mode); ?></span>
                <?php endif; ?>
            </div>
            <div class="gateway-status">
                <span class="status-indicator <?php echo $isConfigured ? 'configured' : 'not-configured'; ?>"></span>
                <span class="status-text"><?php echo $isConfigured ? 'Configured' : 'Not Configured'; ?></span>
            </div>
        </div>
        
        <div class="gateway-body">
            <form method="POST" action="" class="config-form" id="form-<?php echo $code; ?>">
                <input type="hidden" name="action" value="save_config">
                <input type="hidden" name="gateway_id" value="<?php echo $gateway['id']; ?>">
                
                <?php foreach ($fields as $key => $field): ?>
                <div class="form-group">
                    <label>
                        <?php echo $field['label']; ?>
                        <?php if ($field['required']): ?>
                        <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php if ($field['type'] === 'select'): ?>
                    <select name="config[<?php echo $key; ?>]" class="form-control <?php echo $code; ?>-field" <?php echo $field['required'] ? 'required' : ''; ?>>
                        <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                        <option value="<?php echo $optVal; ?>" <?php echo ($configs[$key] ?? '') === $optVal ? 'selected' : ''; ?>>
                            <?php echo $optLabel; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php elseif ($field['type'] === 'password'): ?>
                    <div class="input-group">
                        <input type="password" 
                               name="config[<?php echo $key; ?>]" 
                               class="form-control <?php echo $code; ?>-field" 
                               placeholder="<?php echo $field['placeholder'] ?? ''; ?>"
                               value="<?php echo htmlspecialchars($configs[$key] ?? ''); ?>"
                               <?php echo $field['required'] ? 'required' : ''; ?>
                               id="<?php echo $code; ?>-<?php echo $key; ?>">
                        <button type="button" class="btn-toggle-password" onclick="togglePassword('<?php echo $code; ?>-<?php echo $key; ?>')">
                            <i class="fa fa-eye" id="eye-<?php echo $code; ?>-<?php echo $key; ?>"></i>
                        </button>
                    </div>
                    <?php if (isset($field['encrypted'])): ?>
                    <p class="help-text"><i class="fa fa-lock"></i> This field is stored encrypted</p>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <input type="text" 
                           name="config[<?php echo $key; ?>]" 
                           class="form-control <?php echo $code; ?>-field" 
                           placeholder="<?php echo $field['placeholder'] ?? ''; ?>"
                           value="<?php echo htmlspecialchars($configs[$key] ?? ''); ?>"
                           <?php echo $field['required'] ? 'required' : ''; ?>>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Webhook Info -->
                <div class="webhook-info">
                    <h5><i class="fa fa-link"></i> Webhook URL</h5>
                    <p>Use this URL in your <?php echo $gateway['name']; ?> dashboard for webhook notifications:</p>
                    <div class="webhook-url" id="webhook-<?php echo $code; ?>">
                        <?php 
                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                        echo $baseUrl . "/index.php?page=payment_webhook&gateway=" . $code;
                        ?>
                        <button type="button" class="copy-btn" onclick="copyWebhookUrl('<?php echo $code; ?>')">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-save">
                        <i class="fa fa-save"></i> Save Configuration
                    </button>
                    <button type="button" class="btn btn-test btn-<?php echo $code; ?>" onclick="testConnection('<?php echo $code; ?>', <?php echo $gateway['id']; ?>)">
                        <i class="fa fa-plug"></i> Test Connection
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($gateways)): ?>
    <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i>
        No payment gateways found. Please add PayPal or Stripe in the 
        <a href="index.php?page=payment_method_list">Payment Methods</a> section first.
    </div>
    <?php endif; ?>
</div>

<!-- Test Connection Modal -->
<div class="test-modal" id="testModal">
    <div class="test-modal-content">
        <div id="testLoading">
            <div class="spinner"></div>
            <p>Testing connection...</p>
        </div>
        <div id="testResult" class="test-result" style="display: none;">
            <i class="fa fa-3x" id="testIcon"></i>
            <h3 id="testTitle"></h3>
            <p id="testMessage"></p>
        </div>
        <button type="button" class="btn" style="margin-top: 20px; background: #ccc;" onclick="closeTestModal()">
            Close
        </button>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById('eye-' + fieldId);
    
    if (field.type === 'password') {
        field.type = 'text';
        eye.className = 'fa fa-eye-slash';
    } else {
        field.type = 'password';
        eye.className = 'fa fa-eye';
    }
}

function copyWebhookUrl(gateway) {
    const webhookDiv = document.getElementById('webhook-' + gateway);
    const url = webhookDiv.textContent.trim().replace('Copy', '').trim();
    
    navigator.clipboard.writeText(url).then(() => {
        alert('Webhook URL copied to clipboard!');
    }).catch(err => {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Webhook URL copied to clipboard!');
    });
}

function testConnection(gateway, gatewayId) {
    const modal = document.getElementById('testModal');
    const loading = document.getElementById('testLoading');
    const result = document.getElementById('testResult');
    
    modal.classList.add('show');
    loading.style.display = 'block';
    result.style.display = 'none';
    
    // Get form data
    const form = document.getElementById('form-' + gateway);
    const formData = new FormData(form);
    formData.append('test_connection', '1');
    formData.append('gateway', gateway);
    
    fetch('index.php?page=payment_gateway_test', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        result.style.display = 'block';
        
        const icon = document.getElementById('testIcon');
        const title = document.getElementById('testTitle');
        const message = document.getElementById('testMessage');
        
        if (data.success) {
            result.className = 'test-result success';
            icon.className = 'fa fa-3x fa-check-circle';
            title.textContent = 'Connection Successful!';
            message.textContent = data.message || 'API credentials are valid.';
        } else {
            result.className = 'test-result error';
            icon.className = 'fa fa-3x fa-times-circle';
            title.textContent = 'Connection Failed';
            message.textContent = data.message || 'Please check your API credentials.';
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        result.style.display = 'block';
        result.className = 'test-result error';
        document.getElementById('testIcon').className = 'fa fa-3x fa-times-circle';
        document.getElementById('testTitle').textContent = 'Connection Error';
        document.getElementById('testMessage').textContent = 'Could not connect to test endpoint.';
    });
}

function closeTestModal() {
    document.getElementById('testModal').classList.remove('show');
}

// Close modal on outside click
document.getElementById('testModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTestModal();
    }
});
</script>
