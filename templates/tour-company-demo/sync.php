<?php
/**
 * iACC Template — Product Sync
 * Re-sync products and categories from iACC API to local SQLite cache
 * 
 * Usage:
 *   GET  sync.php        → Show sync status page
 *   POST sync.php        → Trigger sync (returns JSON)
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api-client.php';
require_once __DIR__ . '/includes/database.php';

$config = require __DIR__ . '/config.php';

// Require configured state
if (!($config['configured'] ?? false)) {
    header('Location: setup.php');
    exit;
}

// Handle POST — perform sync
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $client = new IaccApiClient($config['api_url'], $config['api_key'], $config['api_secret']);
    $db = new LocalDatabase();

    $errors = [];

    // Sync categories
    $catResult = $client->getCategories();
    if ($catResult['success'] ?? false) {
        $catCount = $db->syncCategories($catResult['data']['categories'] ?? []);
    } else {
        $errors[] = 'Categories: ' . $client->getLastError();
        $catCount = 0;
    }

    // Sync products
    $prodResult = $client->getProducts();
    if ($prodResult['success'] ?? false) {
        $prodCount = $db->syncProducts($prodResult['data']['products'] ?? []);
    } else {
        $errors[] = 'Products: ' . $client->getLastError();
        $prodCount = 0;
    }

    if (empty($errors)) {
        echo json_encode([
            'success'    => true,
            'message'    => "Synced $catCount categories and $prodCount products",
            'categories' => $catCount,
            'products'   => $prodCount,
            'synced_at'  => date('Y-m-d H:i:s'),
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => implode('; ', $errors),
            'categories' => $catCount,
            'products'   => $prodCount,
        ]);
    }
    exit;
}

// GET — show sync status page
$db = new LocalDatabase();
$lastSync = $db->getLastSync();
$prodCount = $db->getProductCount();
$catCount = $db->getCategoryCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Products — <?= htmlspecialchars($config['site_title'] ?? 'iACC Template') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1a1a2e; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .container { max-width: 500px; width: 100%; }
        .card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); text-align: center; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .stat { padding: 16px; background: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0; }
        .stat .num { font-size: 28px; font-weight: 700; color: #10b981; }
        .stat .lbl { font-size: 12px; color: #64748b; }
        .last-sync { font-size: 13px; color: #64748b; margin-bottom: 20px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border: none; border-radius: 50px; font-family: inherit; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: #8e44ad; color: white; }
        .btn-primary:hover { background: #6c3483; }
        .btn-outline { background: white; border: 2px solid #e2e8f0; color: #475569; }
        .btn-outline:hover { border-color: #8e44ad; color: #8e44ad; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .status-box { padding: 14px; border-radius: 10px; margin-top: 16px; font-size: 14px; display: none; }
        .status-box.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; display: block; }
        .status-box.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; display: block; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1><i class="fa-solid fa-rotate" style="color:#8e44ad"></i> Product Sync</h1>
        <p class="subtitle">Sync your product catalog from iACC</p>

        <div class="stats">
            <div class="stat"><div class="num" id="catCount"><?= $catCount ?></div><div class="lbl">Categories</div></div>
            <div class="stat"><div class="num" id="prodCount"><?= $prodCount ?></div><div class="lbl">Products</div></div>
        </div>

        <p class="last-sync">
            Last sync: <strong id="lastSync"><?= $lastSync ? date('M j, Y g:i A', strtotime($lastSync)) : 'Never' ?></strong>
        </p>

        <div class="actions">
            <button class="btn btn-primary" id="btnSync" onclick="doSync()">
                <i class="fa-solid fa-rotate"></i> Sync Now
            </button>
            <a href="index.php" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Site
            </a>
        </div>

        <div id="status" class="status-box"></div>
    </div>
</div>

<script>
async function doSync() {
    const btn = document.getElementById('btnSync');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Syncing...';
    btn.disabled = true;

    const res = await fetch('sync.php', { method: 'POST' });
    const data = await res.json();
    const statusEl = document.getElementById('status');

    if (data.success) {
        statusEl.className = 'status-box success';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
        document.getElementById('catCount').textContent = data.categories;
        document.getElementById('prodCount').textContent = data.products;
        document.getElementById('lastSync').textContent = data.synced_at;
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Synced';
    } else {
        statusEl.className = 'status-box error';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Retry';
        btn.disabled = false;
    }
}
</script>
</body>
</html>
