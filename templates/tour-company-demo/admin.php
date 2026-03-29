<?php
/**
 * iACC Template — Admin Panel
 * Minimal admin interface for managing the template:
 *   - API key/secret management
 *   - Product sync from iACC API
 *   - Enable/disable individual products
 *   - View recent bookings
 */
session_start();
require_once __DIR__ . '/includes/api-client.php';
require_once __DIR__ . '/includes/database.php';

$configPath = __DIR__ . '/config.php';
$config = file_exists($configPath) ? require $configPath : [];

// Redirect to setup if not configured
if (!($config['configured'] ?? false)) {
    header('Location: setup.php');
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['template_admin_logged_in'], $_SESSION['template_admin_user'], $_SESSION['template_admin_login_time']);
    header('Location: admin-login.php');
    exit;
}

// Auth guard — redirect to login if not authenticated
if (empty($_SESSION['template_admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

$db = new LocalDatabase();
$db->migrateAddIsActive(); // ensure is_active column exists

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'toggle_product':
            $productId = intval($_POST['product_id'] ?? 0);
            $active = ($_POST['active'] ?? '1') === '1';
            if ($productId > 0) {
                $db->toggleProduct($productId, $active);
                echo json_encode(['success' => true, 'message' => $active ? 'Product enabled' : 'Product disabled']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            }
            exit;

        case 'sync_products':
            $client = new IaccApiClient($config['api_url'], $config['api_key'], $config['api_secret']);

            // Remember current active states before sync
            $oldProducts = $db->getProducts(null, false);
            $activeStates = [];
            foreach ($oldProducts as $p) {
                $activeStates[$p['id']] = $p['is_active'] ?? 1;
            }

            // Sync categories
            $catResult = $client->getCategories();
            $catCount = 0;
            if ($catResult['success'] ?? false) {
                $catCount = $db->syncCategories($catResult['data']['categories'] ?? []);
            }

            // Sync products
            $prodResult = $client->getProducts();
            $prodCount = 0;
            if ($prodResult['success'] ?? false) {
                $prodCount = $db->syncProducts($prodResult['data']['products'] ?? []);

                // Restore active states for existing products
                $newProducts = $db->getProducts(null, false);
                foreach ($newProducts as $p) {
                    if (isset($activeStates[$p['id']]) && $activeStates[$p['id']] == 0) {
                        $db->toggleProduct($p['id'], false);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Synced $catCount categories and $prodCount products",
                'categories' => $catCount,
                'products' => $prodCount,
            ]);
            exit;

        case 'update_credentials':
            $apiUrl = trim($_POST['api_url'] ?? '');
            $apiKey = trim($_POST['api_key'] ?? '');
            $apiSecret = trim($_POST['api_secret'] ?? '');

            if (empty($apiUrl) || empty($apiKey) || empty($apiSecret)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            // Test new credentials first
            $client = new IaccApiClient($apiUrl, $apiKey, $apiSecret);
            $result = $client->getSubscription();
            if (!($result['success'] ?? false)) {
                echo json_encode(['success' => false, 'message' => 'Connection failed: ' . ($client->getLastError() ?: 'Invalid credentials')]);
                exit;
            }

            // Update config
            $config['api_url'] = $apiUrl;
            $config['api_key'] = $apiKey;
            $config['api_secret'] = $apiSecret;
            $content = "<?php\n/**\n * iACC Template Configuration — Auto-generated\n * Updated: " . date('Y-m-d H:i:s') . "\n */\nreturn " . var_export($config, true) . ";\n";

            if (file_put_contents($configPath, $content)) {
                echo json_encode(['success' => true, 'message' => 'API credentials updated', 'plan' => $result['data']['plan'] ?? 'N/A']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to write config.php']);
            }
            exit;

        case 'test_connection':
            $client = new IaccApiClient($config['api_url'], $config['api_key'], $config['api_secret']);
            $result = $client->getSubscription();
            if ($result['success'] ?? false) {
                echo json_encode(['success' => true, 'data' => $result['data'] ?? []]);
            } else {
                echo json_encode(['success' => false, 'message' => $client->getLastError() ?: 'Connection failed']);
            }
            exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// GET — render admin page
$allProducts = $db->getProducts(null, false); // get ALL products including inactive
$categories = $db->getCategories();
$lastSync = $db->getLastSync();
$prodCount = $db->getProductCount();
$catCount = $db->getCategoryCount();
$activeCount = count(array_filter($allProducts, fn($p) => ($p['is_active'] ?? 1) == 1));
$inactiveCount = count($allProducts) - $activeCount;
$bookingCount = $db->getBookingCount();
$recentBookings = $db->getBookings(10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — <?= htmlspecialchars($config['site_title'] ?? 'iACC Template') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #1e293b; --gray: #64748b; --bg: #f1f5f9; --success: #10b981; --danger: #ef4444; --warn: #f59e0b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--dark); }

        /* Layout */
        .admin-header { background: var(--dark); color: white; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 18px; font-weight: 700; }
        .admin-header h1 span { color: var(--primary); }
        .admin-header-links a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; margin-left: 20px; }
        .admin-header-links a:hover { color: white; }
        .admin-body { max-width: 1100px; margin: 0 auto; padding: 24px; }

        /* Tabs */
        .tabs { display: flex; gap: 0; margin-bottom: 24px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .tab { flex: 1; padding: 14px; text-align: center; font-size: 14px; font-weight: 600; cursor: pointer; border: none; background: white; color: var(--gray); transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .tab:hover { background: #f8fafc; color: var(--dark); }
        .tab.active { background: var(--primary); color: white; }
        .tab .badge { background: rgba(0,0,0,0.15); padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        .tab.active .badge { background: rgba(255,255,255,0.25); }

        /* Panel */
        .panel { display: none; }
        .panel.active { display: block; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
        .stat-card .icon.purple { background: rgba(142,68,173,0.1); color: var(--primary); }
        .stat-card .icon.green { background: rgba(16,185,129,0.1); color: var(--success); }
        .stat-card .icon.orange { background: rgba(245,158,11,0.1); color: var(--warn); }
        .stat-card .icon.blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .stat-card .value { font-size: 28px; font-weight: 700; }
        .stat-card .label { font-size: 13px; color: var(--gray); }

        /* Card */
        .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
        .card h3 { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .card h3 i { color: var(--primary); }

        /* Form */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .form-group small { display: block; font-size: 12px; color: var(--gray); margin-top: 4px; }
        input[type="text"], input[type="url"], input[type="password"] {
            width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px;
            font-family: inherit; font-size: 14px; transition: all 0.2s; background: #f8fafc;
        }
        input:focus { outline: none; border-color: var(--primary); background: white; }

        /* Button */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; font-family: inherit; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-outline { background: white; border: 2px solid #e2e8f0; color: var(--gray); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 6px 14px; font-size: 12px; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-block { width: 100%; justify-content: center; }

        /* Product Table */
        .product-table { width: 100%; border-collapse: collapse; }
        .product-table th { text-align: left; padding: 10px 14px; background: #f8fafc; font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--gray); letter-spacing: 0.5px; }
        .product-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        .product-table tr:hover { background: #faf5ff; }
        .product-name { font-weight: 600; color: var(--dark); }
        .product-meta { font-size: 12px; color: var(--gray); }

        /* Toggle Switch */
        .toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle .slider { position: absolute; inset: 0; background: #e2e8f0; border-radius: 24px; cursor: pointer; transition: 0.3s; }
        .toggle .slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .toggle input:checked + .slider { background: var(--success); }
        .toggle input:checked + .slider::before { transform: translateX(20px); }

        /* Status */
        .status-box { padding: 14px; border-radius: 8px; margin-top: 14px; font-size: 14px; display: none; }
        .status-box.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; display: block; }
        .status-box.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; display: block; }

        /* Bookings Table */
        .booking-table { width: 100%; border-collapse: collapse; }
        .booking-table th { text-align: left; padding: 10px 12px; background: #f8fafc; font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--gray); }
        .booking-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .badge-status { padding: 3px 10px; border-radius: 50px; font-size: 11px; font-weight: 600; }
        .badge-confirmed { background: #ecfdf5; color: #065f46; }
        .badge-pending { background: #fefce8; color: #854d0e; }

        /* Filter */
        .filter-bar { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-btn { padding: 6px 16px; border: 2px solid #e2e8f0; border-radius: 50px; background: white; font-size: 12px; font-weight: 600; cursor: pointer; color: var(--gray); transition: all 0.2s; }
        .filter-btn.active, .filter-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .tabs { flex-wrap: wrap; }
            .tab { min-width: 50%; }
            .product-table, .booking-table { font-size: 12px; }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="admin-header">
    <h1><i class="fa-solid fa-gear"></i> <span><?= htmlspecialchars($config['site_title'] ?? 'Template') ?></span> Admin</h1>
    <div class="admin-header-links">
        <span style="color:rgba(255,255,255,0.5);font-size:12px;"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['template_admin_user'] ?? 'admin') ?></span>
        <a href="index.php"><i class="fa-solid fa-eye"></i> View Site</a>
        <a href="sync.php"><i class="fa-solid fa-rotate"></i> Quick Sync</a>
        <a href="setup.php"><i class="fa-solid fa-wrench"></i> Setup Wizard</a>
        <a href="admin.php?action=logout" style="color:#f87171;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="admin-body">
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon purple"><i class="fa-solid fa-box"></i></div>
            <div class="value"><?= count($allProducts) ?></div>
            <div class="label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="icon green"><i class="fa-solid fa-check-circle"></i></div>
            <div class="value"><?= $activeCount ?></div>
            <div class="label">Active Products</div>
        </div>
        <div class="stat-card">
            <div class="icon orange"><i class="fa-solid fa-layer-group"></i></div>
            <div class="value"><?= $catCount ?></div>
            <div class="label">Categories</div>
        </div>
        <div class="stat-card">
            <div class="icon blue"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="value"><?= $bookingCount ?></div>
            <div class="label">Bookings</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="switchTab('products')">
            <i class="fa-solid fa-box"></i> Products <span class="badge"><?= count($allProducts) ?></span>
        </button>
        <button class="tab" onclick="switchTab('api')">
            <i class="fa-solid fa-key"></i> API Settings
        </button>
        <button class="tab" onclick="switchTab('sync')">
            <i class="fa-solid fa-rotate"></i> Sync
        </button>
        <button class="tab" onclick="switchTab('bookings')">
            <i class="fa-solid fa-calendar"></i> Bookings <span class="badge"><?= $bookingCount ?></span>
        </button>
    </div>

    <!-- Panel: Products -->
    <div class="panel active" id="panel-products">
        <div class="card">
            <h3><i class="fa-solid fa-box"></i> Product Management</h3>
            <p style="font-size:14px; color:var(--gray); margin-bottom:16px;">Enable or disable products to control what appears on your website. Disabled products won't be shown to visitors.</p>

            <div class="filter-bar">
                <button class="filter-btn active" data-filter="all">All (<?= count($allProducts) ?>)</button>
                <button class="filter-btn" data-filter="active">Active (<?= $activeCount ?>)</button>
                <button class="filter-btn" data-filter="inactive">Disabled (<?= $inactiveCount ?>)</button>
                <?php
                $catNames = array_unique(array_column($allProducts, 'category_name'));
                foreach ($catNames as $cn):
                    if ($cn):
                ?>
                <button class="filter-btn" data-filter="cat-<?= htmlspecialchars($cn) ?>"><?= htmlspecialchars($cn) ?></button>
                <?php endif; endforeach; ?>
            </div>

            <table class="product-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th style="text-align:center">Active</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allProducts as $product): 
                    $isActive = ($product['is_active'] ?? 1) == 1;
                ?>
                    <tr class="product-row" 
                        data-active="<?= $isActive ? 'active' : 'inactive' ?>" 
                        data-category="<?= htmlspecialchars($product['category_name'] ?? '') ?>">
                        <td>
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-meta">ID: <?= $product['id'] ?></div>
                        </td>
                        <td><?= htmlspecialchars($product['category_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($product['type_name'] ?? '—') ?></td>
                        <td>฿<?= number_format(floatval($product['price'])) ?></td>
                        <td style="text-align:center">
                            <label class="toggle">
                                <input type="checkbox" <?= $isActive ? 'checked' : '' ?> 
                                    onchange="toggleProduct(<?= $product['id'] ?>, this.checked)">
                                <span class="slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($allProducts)): ?>
            <div style="text-align:center; padding:40px; color:var(--gray);">
                <i class="fa-solid fa-box-open" style="font-size:36px; margin-bottom:12px; display:block;"></i>
                <p>No products yet. <a href="#" onclick="switchTab('sync'); return false;" style="color:var(--primary);">Sync products</a> from iACC.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel: API Settings -->
    <div class="panel" id="panel-api">
        <div class="card">
            <h3><i class="fa-solid fa-key"></i> API Credentials</h3>
            <p style="font-size:14px; color:var(--gray); margin-bottom:16px;">Update your iACC API credentials. Connection will be tested before saving.</p>

            <div class="form-group">
                <label>iACC URL</label>
                <input type="url" id="apiUrl" value="<?= htmlspecialchars($config['api_url'] ?? '') ?>">
                <small>Your iACC installation URL</small>
            </div>
            <div class="form-group">
                <label>API Key</label>
                <input type="text" id="apiKey" value="<?= htmlspecialchars($config['api_key'] ?? '') ?>">
                <small>Starts with iACC_</small>
            </div>
            <div class="form-group">
                <label>API Secret</label>
                <input type="password" id="apiSecret" value="<?= htmlspecialchars($config['api_secret'] ?? '') ?>">
            </div>

            <div style="display:flex; gap:12px;">
                <button class="btn btn-outline" id="btnTestApi" onclick="testConnection()">
                    <i class="fa-solid fa-plug"></i> Test Connection
                </button>
                <button class="btn btn-primary" id="btnSaveApi" onclick="updateCredentials()">
                    <i class="fa-solid fa-save"></i> Save Credentials
                </button>
            </div>
            <div id="api-status" class="status-box"></div>
        </div>

        <div class="card">
            <h3><i class="fa-solid fa-circle-info"></i> Current Configuration</h3>
            <table style="width:100%;">
                <tr><td style="font-weight:600; padding:8px 0;">Site Title</td><td><?= htmlspecialchars($config['site_title'] ?? '') ?></td></tr>
                <tr><td style="font-weight:600; padding:8px 0;">Company</td><td><?= htmlspecialchars($config['company_name'] ?? '') ?></td></tr>
                <tr><td style="font-weight:600; padding:8px 0;">Currency</td><td><?= htmlspecialchars($config['currency'] ?? 'THB') ?></td></tr>
                <tr><td style="font-weight:600; padding:8px 0;">Theme Color</td><td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= htmlspecialchars($config['theme_color'] ?? '#0369a1') ?>;vertical-align:middle;"></span> <?= htmlspecialchars($config['theme_color'] ?? '#0369a1') ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Panel: Sync -->
    <div class="panel" id="panel-sync">
        <div class="card">
            <h3><i class="fa-solid fa-rotate"></i> Product Sync</h3>
            <p style="font-size:14px; color:var(--gray); margin-bottom:16px;">Pull the latest products and categories from your iACC account. Active/inactive states will be preserved.</p>

            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:20px;">
                <div class="stat-card">
                    <div class="value" id="syncCatCount"><?= $catCount ?></div>
                    <div class="label">Categories</div>
                </div>
                <div class="stat-card">
                    <div class="value" id="syncProdCount"><?= count($allProducts) ?></div>
                    <div class="label">Products</div>
                </div>
                <div class="stat-card">
                    <div class="value" id="syncLastDate"><?= $lastSync ? date('M j, g:i A', strtotime($lastSync['synced_at'])) : 'Never' ?></div>
                    <div class="label">Last Sync</div>
                </div>
            </div>

            <button class="btn btn-primary btn-block" id="btnSync" onclick="syncProducts()">
                <i class="fa-solid fa-rotate"></i> Sync Products Now
            </button>
            <div id="sync-status" class="status-box"></div>
        </div>
    </div>

    <!-- Panel: Bookings -->
    <div class="panel" id="panel-bookings">
        <div class="card">
            <h3><i class="fa-solid fa-calendar-check"></i> Recent Bookings</h3>

            <?php if (empty($recentBookings)): ?>
            <div style="text-align:center; padding:40px; color:var(--gray);">
                <i class="fa-solid fa-calendar" style="font-size:36px; margin-bottom:12px; display:block;"></i>
                <p>No bookings yet. Bookings will appear here when customers book via your website.</p>
            </div>
            <?php else: ?>
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Product</th>
                        <th>Guests</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentBookings as $b): ?>
                    <tr>
                        <td>#<?= $b['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($b['guest_name']) ?></strong><br>
                            <span style="font-size:11px;color:var(--gray);"><?= htmlspecialchars($b['guest_email'] ?? '') ?></span>
                        </td>
                        <td><?= htmlspecialchars($b['product_name'] ?? '—') ?></td>
                        <td><?= $b['guests'] ?? 1 ?></td>
                        <td>฿<?= number_format(floatval($b['total_amount'] ?? 0)) ?></td>
                        <td><span class="badge-status badge-<?= ($b['status'] ?? 'pending') ?>"><?= ucfirst($b['status'] ?? 'pending') ?></span></td>
                        <td style="font-size:12px;"><?= $b['created_at'] ? date('M j, g:i A', strtotime($b['created_at'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Tab switching
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    document.querySelector(`.tab[onclick="switchTab('${tab}')"]`).classList.add('active');
}

// Product filter
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.product-row').forEach(row => {
            if (filter === 'all') {
                row.style.display = '';
            } else if (filter === 'active') {
                row.style.display = row.dataset.active === 'active' ? '' : 'none';
            } else if (filter === 'inactive') {
                row.style.display = row.dataset.active === 'inactive' ? '' : 'none';
            } else if (filter.startsWith('cat-')) {
                row.style.display = row.dataset.category === filter.substring(4) ? '' : 'none';
            }
        });
    });
});

// Toggle product active/inactive
async function toggleProduct(id, active) {
    const fd = new FormData();
    fd.append('action', 'toggle_product');
    fd.append('product_id', id);
    fd.append('active', active ? '1' : '0');

    const res = await fetch('admin.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        // Update row data attribute
        const row = document.querySelector(`.product-row input[onchange*="toggleProduct(${id}"]`).closest('.product-row');
        if (row) row.dataset.active = active ? 'active' : 'inactive';
    }
}

// Test API connection
async function testConnection() {
    const btn = document.getElementById('btnTestApi');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Testing...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'test_connection');
    const res = await fetch('admin.php', { method: 'POST', body: fd });
    const data = await res.json();
    const statusEl = document.getElementById('api-status');

    if (data.success) {
        statusEl.className = 'status-box success';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Connected! Plan: <strong>' + (data.data?.plan || 'N/A') + '</strong> | Orders: <strong>' + (data.data?.orders_remaining ?? 'N/A') + '</strong> remaining';
    } else {
        statusEl.className = 'status-box error';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
    }
    btn.innerHTML = '<i class="fa-solid fa-plug"></i> Test Connection';
    btn.disabled = false;
}

// Update API credentials
async function updateCredentials() {
    const btn = document.getElementById('btnSaveApi');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'update_credentials');
    fd.append('api_url', document.getElementById('apiUrl').value);
    fd.append('api_key', document.getElementById('apiKey').value);
    fd.append('api_secret', document.getElementById('apiSecret').value);

    const res = await fetch('admin.php', { method: 'POST', body: fd });
    const data = await res.json();
    const statusEl = document.getElementById('api-status');

    if (data.success) {
        statusEl.className = 'status-box success';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message + (data.plan ? ' — Plan: <strong>' + data.plan + '</strong>' : '');
    } else {
        statusEl.className = 'status-box error';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
    }
    btn.innerHTML = '<i class="fa-solid fa-save"></i> Save Credentials';
    btn.disabled = false;
}

// Sync products
async function syncProducts() {
    const btn = document.getElementById('btnSync');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Syncing...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'sync_products');
    const res = await fetch('admin.php', { method: 'POST', body: fd });
    const data = await res.json();
    const statusEl = document.getElementById('sync-status');

    if (data.success) {
        statusEl.className = 'status-box success';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
        document.getElementById('syncCatCount').textContent = data.categories;
        document.getElementById('syncProdCount').textContent = data.products;
        document.getElementById('syncLastDate').textContent = 'Just now';
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Synced! Reload to see changes';
        setTimeout(() => location.reload(), 2000);
    } else {
        statusEl.className = 'status-box error';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Retry Sync';
        btn.disabled = false;
    }
}
</script>

</body>
</html>
