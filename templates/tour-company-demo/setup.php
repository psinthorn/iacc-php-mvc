<?php
/**
 * iACC Template — Setup Wizard
 * First-run configuration: API key setup, connection test, product sync
 * 
 * Workflow:
 *   Step 1: Enter API URL, Key, and Secret
 *   Step 2: Test connection → fetch subscription info
 *   Step 3: Sync products and categories from iACC
 *   Step 4: Done! → Redirect to template homepage
 */
session_start();
require_once __DIR__ . '/includes/api-client.php';
require_once __DIR__ . '/includes/database.php';

$configPath = __DIR__ . '/config.php';
$config = file_exists($configPath) ? require $configPath : [];

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'test_connection':
            $apiUrl = trim($_POST['api_url'] ?? '');
            $apiKey = trim($_POST['api_key'] ?? '');
            $apiSecret = trim($_POST['api_secret'] ?? '');

            if (empty($apiUrl) || empty($apiKey) || empty($apiSecret)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            $client = new IaccApiClient($apiUrl, $apiKey, $apiSecret);

            // Test connection with subscription endpoint (requires valid auth)
            $result = $client->getSubscription();
            if ($result['success'] ?? false) {
                $_SESSION['setup_credentials'] = compact('apiUrl', 'apiKey', 'apiSecret');
                echo json_encode([
                    'success' => true,
                    'message' => 'Connection successful!',
                    'data' => $result['data'] ?? []
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $client->getLastError() ?: 'Connection failed'
                ]);
            }
            exit;

        case 'sync_products':
            $creds = $_SESSION['setup_credentials'] ?? null;
            if (!$creds) {
                echo json_encode(['success' => false, 'message' => 'Please test connection first']);
                exit;
            }

            $client = new IaccApiClient($creds['apiUrl'], $creds['apiKey'], $creds['apiSecret']);
            $localDb = new LocalDatabase();

            // Fetch and sync categories
            $catResult = $client->getCategories();
            if (!($catResult['success'] ?? false)) {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch categories: ' . $client->getLastError()]);
                exit;
            }
            $catCount = $localDb->syncCategories($catResult['data']['categories'] ?? []);

            // Fetch and sync products
            $prodResult = $client->getProducts();
            if (!($prodResult['success'] ?? false)) {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch products: ' . $client->getLastError()]);
                exit;
            }
            $prodCount = $localDb->syncProducts($prodResult['data']['products'] ?? []);

            echo json_encode([
                'success' => true,
                'message' => "Synced $catCount categories and $prodCount products",
                'categories' => $catCount,
                'products' => $prodCount
            ]);
            exit;

        case 'save_config':
            $creds = $_SESSION['setup_credentials'] ?? null;
            if (!$creds) {
                echo json_encode(['success' => false, 'message' => 'Please test connection first']);
                exit;
            }

            $siteTitle = trim($_POST['site_title'] ?? 'My Website');
            $themeColor = trim($_POST['theme_color'] ?? '#0369a1');
            $adminUser = trim($_POST['admin_username'] ?? 'admin');
            $adminPass = $_POST['admin_password'] ?? '';

            // Get company name from subscription
            $client = new IaccApiClient($creds['apiUrl'], $creds['apiKey'], $creds['apiSecret']);
            $subResult = $client->getSubscription();
            $companyName = $subResult['data']['company_name'] ?? $siteTitle;

            // Hash admin password (use default if empty)
            if (empty($adminPass)) $adminPass = 'admin123';
            $adminHash = password_hash($adminPass, PASSWORD_BCRYPT);

            $newConfig = [
                'configured'        => true,
                'api_url'           => $creds['apiUrl'],
                'api_key'           => $creds['apiKey'],
                'api_secret'        => $creds['apiSecret'],
                'company_name'      => $companyName,
                'site_title'        => $siteTitle,
                'theme_color'       => $themeColor,
                'currency'          => trim($_POST['currency'] ?? 'THB'),
                'timezone'          => trim($_POST['timezone'] ?? 'Asia/Bangkok'),
                'admin_username'    => $adminUser ?: 'admin',
                'admin_password_hash' => $adminHash,
            ];

            $content = "<?php\n/**\n * iACC Template Configuration — Auto-generated\n * Generated: " . date('Y-m-d H:i:s') . "\n */\nreturn " . var_export($newConfig, true) . ";\n";

            if (file_put_contents($configPath, $content)) {
                unset($_SESSION['setup_credentials']);
                echo json_encode(['success' => true, 'message' => 'Configuration saved!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to write config.php. Check file permissions.']);
            }
            exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// If already configured, show option to reconfigure
$isConfigured = $config['configured'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iACC Template Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --success: #10b981; --danger: #ef4444; --gray: #64748b; --bg: #f8fafc; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #1a1a2e; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .setup-container { max-width: 640px; width: 100%; }
        .setup-header { text-align: center; margin-bottom: 32px; }
        .setup-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .setup-header h1 span { color: var(--primary); }
        .setup-header p { color: var(--gray); font-size: 15px; }
        .setup-card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .step-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .step-num { width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
        .step-num.done { background: var(--success); }
        .step-num.pending { background: #e2e8f0; color: var(--gray); }
        .step-title { font-size: 18px; font-weight: 600; }
        .step-desc { font-size: 13px; color: var(--gray); }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .form-group small { display: block; font-size: 12px; color: var(--gray); margin-top: 4px; }
        input, select { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 14px; transition: all 0.2s; background: #f8fafc; }
        input:focus, select:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(142,68,173,0.1); }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border: none; border-radius: 50px; font-family: inherit; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-outline { background: white; border: 2px solid #e2e8f0; color: #475569; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-block { width: 100%; justify-content: center; }
        .status-box { padding: 16px; border-radius: 10px; margin-top: 16px; font-size: 14px; display: none; }
        .status-box.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; display: block; }
        .status-box.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; display: block; }
        .status-box.info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; display: block; }
        .info-card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .info-card h4 { font-size: 14px; font-weight: 600; color: #0369a1; margin-bottom: 8px; }
        .info-card p, .info-card li { font-size: 13px; color: #475569; line-height: 1.6; }
        .info-card ol { padding-left: 18px; }
        .info-card code { background: #e0f2fe; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .sync-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
        .sync-stat { text-align: center; padding: 16px; background: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0; }
        .sync-stat .num { font-size: 32px; font-weight: 700; color: var(--success); }
        .sync-stat .lbl { font-size: 12px; color: var(--gray); }
        .hidden { display: none; }
        .step-content { transition: all 0.3s; }
    </style>
</head>
<body>
<div class="setup-container">
    <div class="setup-header">
        <h1><span>iACC</span> Template Setup</h1>
        <p>Connect your website template to your iACC account in 3 simple steps</p>
    </div>

    <?php if ($isConfigured): ?>
    <div class="info-card">
        <h4><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> Already Configured</h4>
        <p>This template is already connected to iACC. <a href="index.php">Go to website →</a></p>
        <p style="margin-top:8px"><small>To reconfigure, continue with the steps below.</small></p>
    </div>
    <?php endif; ?>

    <!-- Step 1: API Credentials -->
    <div class="setup-card" id="step1">
        <div class="step-header">
            <div class="step-num" id="step1-num">1</div>
            <div>
                <div class="step-title">API Credentials</div>
                <div class="step-desc">Enter your iACC API key and secret</div>
            </div>
        </div>

        <div class="info-card">
            <h4><i class="fa-solid fa-key"></i> How to get your API Key</h4>
            <ol>
                <li>Login to your iACC account at your iACC URL</li>
                <li>Go to <strong>Settings → Sales Channel API</strong></li>
                <li>Click <strong>"Activate Free Trial"</strong> (or upgrade plan)</li>
                <li>Click <strong>"Generate New API Key"</strong></li>
                <li>Copy the <code>API Key</code> and <code>API Secret</code></li>
            </ol>
        </div>

        <div class="form-group">
            <label>iACC URL</label>
            <input type="url" id="apiUrl" placeholder="https://iacc.f2.co.th" value="<?= htmlspecialchars($config['api_url'] ?? '') ?>">
            <small>Your iACC installation URL (e.g., https://iacc.f2.co.th or http://localhost)</small>
        </div>
        <div class="form-group">
            <label>API Key</label>
            <input type="text" id="apiKey" placeholder="iACC_xxxxxxxxxxxx..." value="<?= htmlspecialchars($config['api_key'] ?? '') ?>">
            <small>Starts with iACC_ — found in Settings → Sales Channel API → Keys</small>
        </div>
        <div class="form-group">
            <label>API Secret</label>
            <input type="password" id="apiSecret" placeholder="Your API secret key" value="<?= htmlspecialchars($config['api_secret'] ?? '') ?>">
            <small>The secret key generated alongside your API key</small>
        </div>

        <button class="btn btn-primary btn-block" id="btnTestConnection" onclick="testConnection()">
            <i class="fa-solid fa-plug"></i> Test Connection
        </button>
        <div id="step1-status" class="status-box"></div>
    </div>

    <!-- Step 2: Sync Products -->
    <div class="setup-card hidden" id="step2">
        <div class="step-header">
            <div class="step-num" id="step2-num">2</div>
            <div>
                <div class="step-title">Sync Products</div>
                <div class="step-desc">Fetch your product catalog from iACC</div>
            </div>
        </div>

        <p style="font-size:14px; color:var(--gray); margin-bottom:20px;">
            This will download all your products and categories from iACC and store them locally for fast page loading.
        </p>

        <button class="btn btn-primary btn-block" id="btnSyncProducts" onclick="syncProducts()">
            <i class="fa-solid fa-rotate"></i> Sync Products Now
        </button>

        <div id="step2-status" class="status-box"></div>
        <div id="sync-stats" class="sync-stats hidden">
            <div class="sync-stat"><div class="num" id="statCategories">0</div><div class="lbl">Categories</div></div>
            <div class="sync-stat"><div class="num" id="statProducts">0</div><div class="lbl">Products</div></div>
        </div>
    </div>

    <!-- Step 3: Site Settings -->
    <div class="setup-card hidden" id="step3">
        <div class="step-header">
            <div class="step-num" id="step3-num">3</div>
            <div>
                <div class="step-title">Site Settings</div>
                <div class="step-desc">Customize your website appearance</div>
            </div>
        </div>

        <div class="form-group">
            <label>Site Title</label>
            <input type="text" id="siteTitle" placeholder="My Tour Company" value="<?= htmlspecialchars($config['site_title'] ?? 'My Samui Island Tour') ?>">
        </div>
        <div class="form-group">
            <label>Theme Color</label>
            <input type="color" id="themeColor" value="<?= htmlspecialchars($config['theme_color'] ?? '#0369a1') ?>" style="height:44px; padding:6px;">
        </div>
        <div class="form-group">
            <label>Currency</label>
            <select id="currency">
                <option value="THB" <?= ($config['currency'] ?? 'THB') === 'THB' ? 'selected' : '' ?>>THB (฿)</option>
                <option value="USD" <?= ($config['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                <option value="EUR" <?= ($config['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
            </select>
        </div>

        <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
        <div class="step-header" style="margin-bottom:16px;">
            <div class="step-num" style="width:28px;height:28px;font-size:12px;"><i class="fa-solid fa-lock" style="font-size:11px;"></i></div>
            <div>
                <div class="step-title" style="font-size:15px;">Admin Login</div>
                <div class="step-desc">Set credentials for the admin panel</div>
            </div>
        </div>
        <div class="form-group">
            <label>Admin Username</label>
            <input type="text" id="adminUsername" placeholder="admin" value="<?= htmlspecialchars($config['admin_username'] ?? 'admin') ?>">
            <small>Username to access the admin panel</small>
        </div>
        <div class="form-group">
            <label>Admin Password</label>
            <input type="password" id="adminPassword" placeholder="Set a password (default: admin123)">
            <small>Leave blank to keep default password (admin123). <strong>Change this for production!</strong></small>
        </div>

        <button class="btn btn-success btn-block" id="btnSaveConfig" onclick="saveConfig()">
            <i class="fa-solid fa-check"></i> Save & Launch Website
        </button>
        <div id="step3-status" class="status-box"></div>
    </div>
</div>

<script>
async function postAction(action, extraData = {}) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(extraData).forEach(([k, v]) => fd.append(k, v));
    const res = await fetch('setup.php', { method: 'POST', body: fd });
    return res.json();
}

function showStatus(id, type, msg) {
    const el = document.getElementById(id);
    el.className = 'status-box ' + type;
    el.innerHTML = '<i class="fa-solid fa-' + (type === 'success' ? 'circle-check' : type === 'error' ? 'circle-xmark' : 'circle-info') + '"></i> ' + msg;
}

async function testConnection() {
    const btn = document.getElementById('btnTestConnection');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Testing...';
    btn.disabled = true;

    const result = await postAction('test_connection', {
        api_url: document.getElementById('apiUrl').value,
        api_key: document.getElementById('apiKey').value,
        api_secret: document.getElementById('apiSecret').value,
    });

    if (result.success) {
        showStatus('step1-status', 'success', 'Connected! Plan: <strong>' + (result.data?.plan || 'N/A') + '</strong> | Orders remaining: <strong>' + (result.data?.orders_remaining ?? 'N/A') + '</strong>');
        document.getElementById('step1-num').className = 'step-num done';
        document.getElementById('step1-num').innerHTML = '<i class="fa-solid fa-check"></i>';
        document.getElementById('step2').classList.remove('hidden');
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Connected';
        btn.className = 'btn btn-success btn-block';
    } else {
        showStatus('step1-status', 'error', result.message);
        btn.innerHTML = '<i class="fa-solid fa-plug"></i> Test Connection';
        btn.disabled = false;
    }
}

async function syncProducts() {
    const btn = document.getElementById('btnSyncProducts');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Syncing...';
    btn.disabled = true;

    const result = await postAction('sync_products');

    if (result.success) {
        showStatus('step2-status', 'success', result.message);
        document.getElementById('step2-num').className = 'step-num done';
        document.getElementById('step2-num').innerHTML = '<i class="fa-solid fa-check"></i>';
        document.getElementById('sync-stats').classList.remove('hidden');
        document.getElementById('statCategories').textContent = result.categories;
        document.getElementById('statProducts').textContent = result.products;
        document.getElementById('step3').classList.remove('hidden');
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Synced';
        btn.className = 'btn btn-success btn-block';
    } else {
        showStatus('step2-status', 'error', result.message);
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Retry Sync';
        btn.disabled = false;
    }
}

async function saveConfig() {
    const btn = document.getElementById('btnSaveConfig');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    const result = await postAction('save_config', {
        site_title: document.getElementById('siteTitle').value,
        theme_color: document.getElementById('themeColor').value,
        currency: document.getElementById('currency').value,
        admin_username: document.getElementById('adminUsername').value,
        admin_password: document.getElementById('adminPassword').value,
    });

    if (result.success) {
        showStatus('step3-status', 'success', 'Configuration saved! Redirecting to your website...');
        document.getElementById('step3-num').className = 'step-num done';
        document.getElementById('step3-num').innerHTML = '<i class="fa-solid fa-check"></i>';
        setTimeout(() => window.location.href = 'index.php', 1500);
    } else {
        showStatus('step3-status', 'error', result.message);
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Save & Launch Website';
        btn.disabled = false;
    }
}
</script>
</body>
</html>
