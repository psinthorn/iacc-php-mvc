<?php
/**
 * AI Schema Refresh
 * 
 * Refresh database schema cache with auto-detection option
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-05
 */

/**
 * Calculate a hash of the database schema structure
 */
function calculateSchemaHash($conn): string {
    $sql = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_KEY 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            ORDER BY TABLE_NAME, ORDINAL_POSITION";
    $stmt = $conn->query($sql);
    $schema = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return md5(json_encode($schema));
}

/**
 * Get auto-refresh setting from ai_settings
 */
function getAutoRefreshSetting($conn): bool {
    try {
        $sql = "SELECT setting_value FROM ai_settings WHERE setting_key = 'schema_auto_refresh' LIMIT 1";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['setting_value'] === '1';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Set auto-refresh setting
 */
function setAutoRefreshSetting($conn, bool $enabled): void {
    $sql = "INSERT INTO ai_settings (setting_key, setting_value, updated_at) 
            VALUES ('schema_auto_refresh', ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
    $stmt = $conn->prepare($sql);
    $value = $enabled ? '1' : '0';
    $stmt->execute([$value, $value]);
}

// Handle AJAX requests (called directly)
if (isset($_GET['ajax'])) {
    session_start();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    require_once __DIR__ . '/ai/schema-discovery.php';
    
    $conn = new PDO(
        'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
        'root', 'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'refresh':
            try {
                $discovery = new SchemaDiscovery($conn);
                $schema = $discovery->discoverSchema();
                $discovery->saveToCache();
                
                // Log the refresh
                $logSql = "INSERT INTO ai_action_log (company_id, user_id, session_id, action_type, action_params, status, created_at)
                           VALUES (?, ?, 'system', 'schema_refresh', ?, 'executed', NOW())";
                $logStmt = $conn->prepare($logSql);
                $logStmt->execute([
                    $_SESSION['com_id'] ?? 0,
                    $_SESSION['user_id'] ?? 0,
                    json_encode(['tables' => count($schema['tables']), 'trigger' => $_GET['trigger'] ?? 'manual'])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'tables' => count($schema['tables']),
                    'cached_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'status':
            $cached = SchemaDiscovery::loadFullSchema();
            $autoRefresh = getAutoRefreshSetting($conn);
            
            $hashFile = __DIR__ . '/cache/db-schema-hash.txt';
            $lastHash = file_exists($hashFile) ? file_get_contents($hashFile) : null;
            $currentHash = calculateSchemaHash($conn);
            $changed = $lastHash && $currentHash !== $lastHash;
            
            echo json_encode([
                'success' => true,
                'cached' => $cached ? true : false,
                'cached_at' => $cached['discovered_at'] ?? null,
                'tables' => $cached ? count($cached['tables']) : 0,
                'auto_refresh' => $autoRefresh,
                'schema_changed' => $changed,
                'current_hash' => substr($currentHash, 0, 12),
                'last_hash' => $lastHash ? substr($lastHash, 0, 12) : null
            ]);
            exit;
            
        case 'check_changes':
            $hashFile = __DIR__ . '/cache/db-schema-hash.txt';
            $lastHash = file_exists($hashFile) ? file_get_contents($hashFile) : null;
            $currentHash = calculateSchemaHash($conn);
            
            $changed = !$lastHash || $currentHash !== $lastHash;
            $autoRefresh = getAutoRefreshSetting($conn);
            $refreshed = false;
            
            if ($changed && $autoRefresh) {
                $discovery = new SchemaDiscovery($conn);
                $schema = $discovery->discoverSchema();
                $discovery->saveToCache();
                file_put_contents($hashFile, $currentHash);
                $refreshed = true;
            }
            
            echo json_encode([
                'success' => true,
                'changed' => $changed,
                'refreshed' => $refreshed,
                'auto_refresh' => $autoRefresh
            ]);
            exit;
            
        case 'set_auto_refresh':
            $enabled = ($_POST['enabled'] ?? '0') === '1';
            setAutoRefreshSetting($conn, $enabled);
            echo json_encode(['success' => true, 'enabled' => $enabled]);
            exit;
            
        case 'save_hash':
            $hashFile = __DIR__ . '/cache/db-schema-hash.txt';
            $currentHash = calculateSchemaHash($conn);
            file_put_contents($hashFile, $currentHash);
            echo json_encode(['success' => true, 'hash' => substr($currentHash, 0, 12)]);
            exit;
            
        case 'get_migrations':
            try {
                $sql = "SELECT * FROM _migration_log ORDER BY executed_at DESC LIMIT 20";
                $stmt = $conn->query($sql);
                echo json_encode(['success' => true, 'migrations' => $stmt->fetchAll()]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'migrations' => [], 'note' => 'No migration table']);
            }
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Page context (included from index.php)
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
    echo '<div class="alert alert-danger">Access denied. Super Admin required.</div>';
    return;
}

require_once __DIR__ . '/ai/schema-discovery.php';

// Create PDO for page display (need for getAutoRefreshSetting)
$conn = new PDO(
    'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
    'root', 'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Get current status
$cached = SchemaDiscovery::loadFullSchema();
$autoRefresh = getAutoRefreshSetting($conn);
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="fa fa-refresh"></i> Schema Refresh
            <small>Keep AI in sync with database changes</small>
        </h1>
    </div>
</div>

<!-- Status Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="panel panel-primary">
            <div class="panel-heading text-center">Cache Status</div>
            <div class="panel-body text-center">
                <h2 id="cache-status"><?=$cached ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'?></h2>
                <p><?=$cached ? 'Cached' : 'Not Cached'?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-info">
            <div class="panel-heading text-center">Tables</div>
            <div class="panel-body text-center">
                <h2 id="table-count"><?=$cached ? count($cached['tables']) : 0?></h2>
                <p>Discovered</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-heading text-center">Last Refresh</div>
            <div class="panel-body text-center">
                <h4 id="last-refresh"><?=htmlspecialchars($cached['discovered_at'] ?? 'Never')?></h4>
                <p>Cached At</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-<?=$autoRefresh ? 'success' : 'default'?>">
            <div class="panel-heading text-center">Auto-Refresh</div>
            <div class="panel-body text-center">
                <h2 id="auto-status"><?=$autoRefresh ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>'?></h2>
                <p><?=$autoRefresh ? 'Enabled' : 'Disabled'?></p>
            </div>
        </div>
    </div>
</div>

<!-- Controls -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-cogs"></i> Schema Refresh Controls
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5>Manual Refresh</h5>
                        <p class="text-muted">Refresh schema cache immediately</p>
                        <button class="btn btn-primary btn-lg" onclick="refreshSchema()">
                            <i class="fa fa-refresh"></i> Refresh Now
                        </button>
                    </div>
                    <div class="col-md-4">
                        <h5>Auto-Refresh</h5>
                        <p class="text-muted">Automatically refresh when schema changes detected</p>
                        <div class="btn-group">
                            <button class="btn btn-success <?=$autoRefresh ? 'active' : ''?>" onclick="setAutoRefresh(true)">
                                <i class="fa fa-check"></i> Enable
                            </button>
                            <button class="btn btn-default <?=!$autoRefresh ? 'active' : ''?>" onclick="setAutoRefresh(false)">
                                <i class="fa fa-times"></i> Disable
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h5>Check for Changes</h5>
                        <p class="text-muted">Compare current schema with cached version</p>
                        <button class="btn btn-info" onclick="checkChanges()">
                            <i class="fa fa-search"></i> Check Now
                        </button>
                        <span id="change-status"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schema Hash Info -->
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-key"></i> Schema Hash
            </div>
            <div class="panel-body">
                <table class="table">
                    <tr>
                        <th>Current Hash:</th>
                        <td><code id="current-hash">Loading...</code></td>
                    </tr>
                    <tr>
                        <th>Cached Hash:</th>
                        <td><code id="cached-hash">Loading...</code></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td id="hash-status">Loading...</td>
                    </tr>
                </table>
                <button class="btn btn-sm btn-default" onclick="saveCurrentHash()">
                    <i class="fa fa-save"></i> Save Current Hash
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-database"></i> Recent Migrations
            </div>
            <div class="panel-body" style="max-height: 200px; overflow-y: auto;">
                <div id="migrations-list">Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- How Auto-Refresh Works -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <i class="fa fa-info-circle"></i> How Auto-Refresh Works
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5><i class="fa fa-1"></i> Schema Hashing</h5>
                        <p>A hash is calculated from all table/column definitions. When this hash changes, we know the schema has been modified.</p>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="fa fa-2"></i> Change Detection</h5>
                        <p>The system compares the current hash with the last saved hash. Changes are detected after migrations or manual ALTER statements.</p>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="fa fa-3"></i> Auto Cache Update</h5>
                        <p>When auto-refresh is enabled, schema changes automatically trigger a cache refresh so the AI always has current information.</p>
                    </div>
                </div>
                <hr>
                <p><strong>Integration:</strong> Add this to your migration scripts or cron job:</p>
                <pre>curl -s "<?=rtrim($_SERVER['HTTP_HOST'] ?? 'localhost', '/')?>/index.php?page=ai_schema_refresh&ajax=1&action=check_changes"</pre>
                <p>Or call from PHP:</p>
                <pre>// After running migrations:
require_once 'ai/schema-discovery.php';
$discovery = new SchemaDiscovery($conn);
$discovery->discoverSchema();
$discovery->saveToCache();</pre>
            </div>
        </div>
    </div>
</div>

<style>
.panel-body h2 {
    margin: 0;
    font-size: 32px;
}
.panel-body h4 {
    margin: 0;
    font-size: 14px;
}
.btn-group .btn.active {
    box-shadow: inset 0 3px 5px rgba(0,0,0,.125);
}
</style>

<script>
function refreshSchema() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refreshing...';
    
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=refresh&trigger=manual', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('table-count').textContent = data.tables;
            document.getElementById('last-refresh').textContent = data.cached_at;
            document.getElementById('cache-status').innerHTML = '<i class="fa fa-check text-success"></i>';
            alert('Schema refreshed! Found ' + data.tables + ' tables.');
            loadStatus();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-refresh"></i> Refresh Now';
    });
}

function setAutoRefresh(enabled) {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=set_auto_refresh', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'enabled=' + (enabled ? '1' : '0')
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('auto-status').innerHTML = enabled ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
        }
    });
}

function checkChanges() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';
    
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=check_changes', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        let status = '';
        if (data.changed) {
            if (data.refreshed) {
                status = '<span class="label label-success">Changed & Refreshed</span>';
            } else {
                status = '<span class="label label-warning">Changes Detected</span>';
            }
        } else {
            status = '<span class="label label-default">No Changes</span>';
        }
        document.getElementById('change-status').innerHTML = status;
        loadStatus();
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-search"></i> Check Now';
    });
}

function loadStatus() {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=status', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('current-hash').textContent = data.current_hash || 'N/A';
            document.getElementById('cached-hash').textContent = data.last_hash || 'Not saved';
            
            if (data.schema_changed) {
                document.getElementById('hash-status').innerHTML = '<span class="label label-warning">Schema Changed</span>';
            } else if (data.last_hash) {
                document.getElementById('hash-status').innerHTML = '<span class="label label-success">In Sync</span>';
            } else {
                document.getElementById('hash-status').innerHTML = '<span class="label label-default">No baseline</span>';
            }
        }
    });
}

function saveCurrentHash() {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=save_hash', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cached-hash').textContent = data.hash;
            document.getElementById('hash-status').innerHTML = '<span class="label label-success">In Sync</span>';
        }
    });
}

function loadMigrations() {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=get_migrations', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('migrations-list');
        if (data.migrations && data.migrations.length > 0) {
            container.innerHTML = '<table class="table table-condensed table-striped" style="font-size: 11px;">' +
                '<thead><tr><th>Migration</th><th>Status</th><th>Date</th></tr></thead><tbody>' +
                data.migrations.map(m => `<tr>
                    <td>${m.migration_name}</td>
                    <td><span class="label label-${m.status === 'success' ? 'success' : 'danger'}">${m.status}</span></td>
                    <td>${m.executed_at}</td>
                </tr>`).join('') + '</tbody></table>';
        } else {
            container.innerHTML = '<p class="text-muted">No migrations found</p>';
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadStatus();
    loadMigrations();
});
</script>
