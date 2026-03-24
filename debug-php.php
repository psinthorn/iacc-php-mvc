<?php
/**
 * PHP Debug & Error Log Viewer
 * View PHP environment info, error logs, and run diagnostics
 * Integrated into admin Developer Tools panel
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/dev-tools-style.php");

// Check access - Developer role required
check_dev_tools_access();

// Initialize database connection
$db = new DbConn($config);

// ============================================================================
// Handle AJAX actions
// ============================================================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['action']) {
        case 'clear_log':
            $logFile = $_GET['file'] ?? '';
            $allowed = get_log_files();
            $cleared = false;
            foreach ($allowed as $lf) {
                if ($lf['name'] === $logFile && file_exists($lf['path'])) {
                    file_put_contents($lf['path'], '');
                    $cleared = true;
                    break;
                }
            }
            echo json_encode(['success' => $cleared, 'message' => $cleared ? 'Log cleared' : 'File not found']);
            exit;
            
        case 'refresh_logs':
            $logs = [];
            foreach (get_log_files() as $lf) {
                $logs[] = get_log_info($lf);
            }
            echo json_encode(['logs' => $logs]);
            exit;

        case 'test_write':
            $logDir = __DIR__ . '/logs';
            $testFile = $logDir . '/test-write-' . time() . '.tmp';
            $results = [];
            
            // Test directory
            if (!is_dir($logDir)) {
                $results['mkdir'] = @mkdir($logDir, 0755, true) ? 'Created' : 'Failed';
            } else {
                $results['mkdir'] = 'Exists';
            }
            $results['dir_writable'] = is_writable($logDir) ? 'Yes' : 'No';
            
            // Test file write
            $written = @file_put_contents($testFile, 'test');
            $results['file_write'] = $written !== false ? 'OK' : 'Failed';
            if (file_exists($testFile)) @unlink($testFile);
            
            // Test error_log
            $results['error_log'] = @error_log("PHP Debug test message at " . date('Y-m-d H:i:s')) ? 'OK' : 'Failed';
            
            echo json_encode(['results' => $results]);
            exit;
    }
}

// ============================================================================
// Helper functions
// ============================================================================
function get_log_files() {
    $baseDir = dirname(__FILE__);
    return [
        ['name' => 'app.log', 'path' => $baseDir . '/logs/app.log', 'label' => 'Application Log', 'icon' => 'fa-cogs'],
        ['name' => 'php_errors.log', 'path' => $baseDir . '/logs/php_errors.log', 'label' => 'PHP Errors', 'icon' => 'fa-exclamation-triangle'],
        ['name' => 'error.log', 'path' => $baseDir . '/logs/error.log', 'label' => 'Error Handler Log', 'icon' => 'fa-bug'],
        ['name' => 'php-error.log', 'path' => $baseDir . '/php-error.log', 'label' => 'PHP Error (root)', 'icon' => 'fa-file-text'],
    ];
}

function get_log_info($lf) {
    $info = [
        'name' => $lf['name'],
        'label' => $lf['label'],
        'icon' => $lf['icon'],
        'exists' => file_exists($lf['path']),
        'size' => 0,
        'size_human' => '0 B',
        'modified' => '-',
        'lines' => [],
        'total_lines' => 0,
    ];
    
    if ($info['exists']) {
        $info['size'] = filesize($lf['path']);
        $info['size_human'] = format_bytes($info['size']);
        $info['modified'] = date('Y-m-d H:i:s', filemtime($lf['path']));
        
        // Read last 100 lines
        $content = file_get_contents($lf['path']);
        if ($content !== false && strlen($content) > 0) {
            $allLines = explode("\n", trim($content));
            $info['total_lines'] = count($allLines);
            $info['lines'] = array_slice($allLines, -100);
        }
    }
    
    return $info;
}

function format_bytes($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

// ============================================================================
// Gather PHP environment data
// ============================================================================
$php_info = [
    'version' => phpversion(),
    'sapi' => php_sapi_name(),
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors'),
    'error_log' => ini_get('error_log') ?: 'Default',
    'error_reporting' => error_reporting(),
    'timezone' => date_default_timezone_get(),
    'session_save_path' => session_save_path() ?: 'Default',
    'session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'opcache_enabled' => function_exists('opcache_get_status') ? (ini_get('opcache.enable') ? 'Yes' : 'No') : 'Not available',
];

// Check extensions
$required_ext = ['mysqli', 'mbstring', 'session', 'json', 'simplexml', 'libxml', 'curl', 'gd', 'openssl', 'zip'];
$extensions = [];
foreach ($required_ext as $ext) {
    $extensions[$ext] = extension_loaded($ext);
}

// Deprecated function check
$deprecated_check = [];
$deprecated_check['each'] = function_exists('each') ? 'Available (PHP < 8.0)' : 'Removed (PHP 8.0+)';
$deprecated_check['mysql_connect'] = function_exists('mysql_connect') ? 'Available (deprecated)' : 'Removed (use mysqli)';
$deprecated_check['create_function'] = function_exists('create_function') ? 'Available (deprecated)' : 'Removed (PHP 8.0+)';

// Database info
$db_info = [];
try {
    $db_info['server_version'] = mysqli_get_server_info($db->conn);
    $db_info['client_version'] = mysqli_get_client_info();
    $db_info['charset'] = mysqli_character_set_name($db->conn);
    $db_info['host_info'] = mysqli_get_host_info($db->conn);
    
    // Table count
    $result = mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE()");
    $row = mysqli_fetch_assoc($result);
    $db_info['table_count'] = $row['cnt'];
    
    // DB size
    $result = mysqli_query($db->conn, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()");
    $row = mysqli_fetch_assoc($result);
    $db_info['size'] = ($row['size'] ?? '0') . ' MB';
} catch (Exception $e) {
    $db_info['error'] = $e->getMessage();
}

// Directory checks
$dir_checks = [
    'logs' => __DIR__ . '/logs',
    'upload' => __DIR__ . '/upload',
    'file' => __DIR__ . '/file',
    'cache' => __DIR__ . '/cache',
    'inc' => __DIR__ . '/inc',
    'backups' => __DIR__ . '/backups',
];

// Gather log data
$log_data = [];
foreach (get_log_files() as $lf) {
    $log_data[] = get_log_info($lf);
}

// Is Docker environment?
$is_docker = function_exists('is_running_in_docker') ? is_running_in_docker() : file_exists('/.dockerenv');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Debug - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <style>
        .debug-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .debug-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .debug-card .card-head { padding: 14px 18px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); background: var(--light); }
        .debug-card .card-head i { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13px; }
        .debug-card .card-body { padding: 16px 18px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: var(--gray); font-weight: 500; }
        .info-row .value { font-weight: 600; color: var(--dark); font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px; }
        .badge-ok { background: var(--success-light); color: #155724; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-err { background: var(--danger-light); color: #721c24; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-warn { background: var(--warning-light); color: #856404; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-info { background: var(--info-light); color: #0c5460; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        
        .ext-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; }
        .ext-item { padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-align: center; }
        .ext-ok { background: var(--success-light); color: #155724; }
        .ext-missing { background: var(--danger-light); color: #721c24; }
        
        .dir-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .dir-row:last-child { border-bottom: none; }
        .dir-icon { font-size: 16px; }
        
        /* Log viewer */
        .log-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 16px; }
        .log-tab { padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px 8px 0 0; background: var(--light); cursor: pointer; font-size: 12px; font-weight: 600; color: var(--gray); transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
        .log-tab:hover { background: #fff; color: var(--dark); }
        .log-tab.active { background: #fff; color: var(--primary); border-bottom-color: #fff; }
        .log-tab .tab-size { font-size: 10px; opacity: 0.7; }
        .log-content { display: none; background: #1a1a2e; color: #e0e0e0; border-radius: 0 8px 8px 8px; padding: 16px; font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px; line-height: 1.6; max-height: 500px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; }
        .log-content.active { display: block; }
        .log-empty { color: #666; font-style: italic; padding: 30px; text-align: center; }
        .log-line { padding: 1px 0; }
        .log-line:hover { background: rgba(255,255,255,0.05); }
        .log-line .log-error { color: #ff6b6b; }
        .log-line .log-warn { color: #ffd93d; }
        .log-line .log-info { color: #6bcb77; }
        .log-line .log-date { color: #4ecdc4; }
        .log-actions { display: flex; gap: 8px; margin-bottom: 12px; }
        .log-actions .btn-sm { padding: 6px 14px; border: 1px solid var(--border); border-radius: 6px; background: #fff; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; }
        .log-actions .btn-sm:hover { background: var(--light); }
        .log-actions .btn-danger { border-color: var(--danger); color: var(--danger); }
        .log-actions .btn-danger:hover { background: var(--danger); color: #fff; }
        
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 25px; }
        .stat-card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 16px; text-align: center; }
        .stat-card .stat-value { font-size: 24px; font-weight: 700; color: var(--dark); }
        .stat-card .stat-label { font-size: 11px; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .stat-card.stat-green .stat-value { color: var(--success); }
        .stat-card.stat-red .stat-value { color: var(--danger); }
        .stat-card.stat-blue .stat-value { color: var(--info); }
        .stat-card.stat-orange .stat-value { color: var(--warning); }
    </style>
</head>
<body>
<div class="dev-tools-container">
    <?php echo get_dev_tools_header('PHP Debug & Logs', 'PHP environment, error logs, and system diagnostics', 'fa-bug', '#e74c3c'); ?>
    
    <!-- Quick Stats -->
    <div class="stat-cards">
        <div class="stat-card stat-blue">
            <div class="stat-value"><?= htmlspecialchars($php_info['version']) ?></div>
            <div class="stat-label">PHP Version</div>
        </div>
        <div class="stat-card <?= version_compare($php_info['version'], '8.0', '>=') ? 'stat-green' : 'stat-orange' ?>">
            <div class="stat-value"><?= version_compare($php_info['version'], '8.0', '>=') ? 'PHP 8+' : 'PHP 7.x' ?></div>
            <div class="stat-label">Compatibility</div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-value"><?= count(array_filter($extensions)) ?>/<?= count($extensions) ?></div>
            <div class="stat-label">Extensions OK</div>
        </div>
        <div class="stat-card <?= $is_docker ? 'stat-blue' : 'stat-orange' ?>">
            <div class="stat-value"><?= $is_docker ? 'Docker' : 'Native' ?></div>
            <div class="stat-label">Environment</div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-value"><?= $db_info['table_count'] ?? '?' ?></div>
            <div class="stat-label">DB Tables</div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-value"><?= $db_info['size'] ?? '?' ?></div>
            <div class="stat-label">DB Size</div>
        </div>
    </div>

    <!-- PHP Info + Extensions -->
    <div class="debug-grid">
        <div class="debug-card">
            <div class="card-head"><i style="background: #e3f2fd; color: #1565c0;"><span class="fa fa-info-circle"></span></i> PHP Configuration</div>
            <div class="card-body">
                <?php foreach ($php_info as $key => $val): ?>
                <div class="info-row">
                    <span class="label"><?= ucwords(str_replace('_', ' ', $key)) ?></span>
                    <span class="value"><?= htmlspecialchars($val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="debug-card">
            <div class="card-head"><i style="background: #e8f5e9; color: #2e7d32;"><span class="fa fa-puzzle-piece"></span></i> PHP Extensions</div>
            <div class="card-body">
                <div class="ext-grid">
                    <?php foreach ($extensions as $ext => $loaded): ?>
                    <div class="ext-item <?= $loaded ? 'ext-ok' : 'ext-missing' ?>">
                        <?= $loaded ? '✅' : '❌' ?> <?= $ext ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f0f0f0;">
                    <div style="font-weight: 600; font-size: 13px; margin-bottom: 8px; color: var(--dark);">Deprecated Functions</div>
                    <?php foreach ($deprecated_check as $fn => $status): ?>
                    <div class="info-row">
                        <span class="label"><code><?= $fn ?>()</code></span>
                        <span class="<?= strpos($status, 'Removed') !== false ? 'badge-ok' : 'badge-warn' ?>"><?= $status ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="debug-card">
            <div class="card-head"><i style="background: #fff3e0; color: #e65100;"><span class="fa fa-database"></span></i> Database Info</div>
            <div class="card-body">
                <?php if (isset($db_info['error'])): ?>
                    <div class="badge-err"><?= htmlspecialchars($db_info['error']) ?></div>
                <?php else: ?>
                    <?php foreach ($db_info as $key => $val): ?>
                    <div class="info-row">
                        <span class="label"><?= ucwords(str_replace('_', ' ', $key)) ?></span>
                        <span class="value"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="debug-card">
            <div class="card-head"><i style="background: #fce4ec; color: #c62828;"><span class="fa fa-folder-open"></span></i> Directory Status</div>
            <div class="card-body">
                <?php foreach ($dir_checks as $name => $path): ?>
                <div class="dir-row">
                    <span class="dir-icon"><?= is_dir($path) ? '📁' : '⚠️' ?></span>
                    <span style="flex: 1; font-weight: 500;">/<?= $name ?>/</span>
                    <?php if (is_dir($path)): ?>
                        <span class="<?= is_writable($path) ? 'badge-ok' : 'badge-err' ?>">
                            <?= is_writable($path) ? 'Writable' : 'Read-only' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-warn">Missing</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 12px;">
                    <button onclick="testWrite()" class="btn-sm" style="padding: 6px 14px; border: 1px solid var(--info); color: var(--info); border-radius: 6px; background: #fff; cursor: pointer; font-size: 12px;">
                        <i class="fa fa-pencil"></i> Test Write Permissions
                    </button>
                    <span id="write-test-result" style="font-size: 12px; margin-left: 8px;"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Log Viewer -->
    <div class="debug-card" style="margin-bottom: 25px;">
        <div class="card-head"><i style="background: #fce4ec; color: #c62828;"><span class="fa fa-file-text"></span></i> Error Log Viewer</div>
        <div class="card-body">
            <div class="log-actions">
                <button onclick="refreshLogs()" class="btn-sm"><i class="fa fa-refresh"></i> Refresh</button>
                <button onclick="clearCurrentLog()" class="btn-sm btn-danger"><i class="fa fa-trash"></i> Clear Current Log</button>
                <span id="log-status" style="font-size: 12px; color: var(--gray); line-height: 32px; margin-left: 8px;"></span>
            </div>
            
            <div class="log-tabs" id="log-tabs">
                <?php foreach ($log_data as $i => $log): ?>
                <div class="log-tab <?= $i === 0 ? 'active' : '' ?>" onclick="switchTab(<?= $i ?>)" data-name="<?= htmlspecialchars($log['name']) ?>">
                    <i class="fa <?= $log['icon'] ?>"></i>
                    <?= htmlspecialchars($log['label']) ?>
                    <span class="tab-size">(<?= $log['exists'] ? $log['size_human'] : 'N/A' ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php foreach ($log_data as $i => $log): ?>
            <div class="log-content <?= $i === 0 ? 'active' : '' ?>" id="log-<?= $i ?>">
                <?php if (!$log['exists']): ?>
                    <div class="log-empty">📄 File does not exist: <?= htmlspecialchars($log['name']) ?></div>
                <?php elseif (empty($log['lines'])): ?>
                    <div class="log-empty">✅ Log is empty — no errors recorded</div>
                <?php else: ?>
                    <div style="color: #888; margin-bottom: 8px; font-size: 11px;">
                        Showing last <?= count($log['lines']) ?> of <?= $log['total_lines'] ?> lines | Modified: <?= $log['modified'] ?>
                    </div>
                    <?php foreach ($log['lines'] as $line): ?>
                    <div class="log-line"><?= colorize_log(htmlspecialchars($line)) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="debug-card">
        <div class="card-head"><i style="background: #e8eaf6; color: #283593;"><span class="fa fa-bolt"></span></i> Quick Actions</div>
        <div class="card-body">
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="index.php?page=debug_session" style="padding: 10px 18px; background: var(--light); border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--dark); font-size: 13px; font-weight: 500;">
                    <i class="fa fa-key"></i> Session Debug
                </a>
                <a href="index.php?page=debug_invoice" style="padding: 10px 18px; background: var(--light); border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--dark); font-size: 13px; font-weight: 500;">
                    <i class="fa fa-file-text-o"></i> Invoice Debug
                </a>
                <a href="index.php?page=api_lang_debug" style="padding: 10px 18px; background: var(--light); border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--dark); font-size: 13px; font-weight: 500;">
                    <i class="fa fa-language"></i> Language Debug
                </a>
                <a href="index.php?page=monitoring" style="padding: 10px 18px; background: var(--light); border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--dark); font-size: 13px; font-weight: 500;">
                    <i class="fa fa-dashboard"></i> System Monitor
                </a>
                <button onclick="phpInfo()" style="padding: 10px 18px; background: var(--light); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; color: var(--dark); font-size: 13px; font-weight: 500;">
                    <i class="fa fa-info-circle"></i> Full phpinfo()
                </button>
            </div>
        </div>
    </div>
</div>

<!-- phpinfo modal -->
<div id="phpinfo-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; padding: 30px;">
    <div style="background:#fff; border-radius:12px; max-width:1000px; margin:0 auto; height:100%; display:flex; flex-direction:column; overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <strong>phpinfo() Output</strong>
            <button onclick="document.getElementById('phpinfo-modal').style.display='none'" style="background:none; border:none; font-size:20px; cursor:pointer; color: var(--gray);">✕</button>
        </div>
        <iframe id="phpinfo-frame" style="flex:1; border:none;"></iframe>
    </div>
</div>

<script>
let currentTab = 0;
const logNames = <?= json_encode(array_column($log_data, 'name')) ?>;

function switchTab(index) {
    document.querySelectorAll('.log-tab').forEach((t, i) => t.classList.toggle('active', i === index));
    document.querySelectorAll('.log-content').forEach((c, i) => c.classList.toggle('active', i === index));
    currentTab = index;
}

function refreshLogs() {
    document.getElementById('log-status').textContent = 'Refreshing...';
    fetch('?action=refresh_logs')
        .then(r => r.json())
        .then(data => {
            // Reload page to refresh log display
            location.reload();
        })
        .catch(err => {
            document.getElementById('log-status').textContent = 'Error refreshing: ' + err.message;
        });
}

function clearCurrentLog() {
    const name = logNames[currentTab];
    if (!confirm('Clear log file: ' + name + '?')) return;
    
    fetch('?action=clear_log&file=' + encodeURIComponent(name))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('log-status').textContent = '✅ ' + name + ' cleared';
                setTimeout(() => location.reload(), 500);
            } else {
                document.getElementById('log-status').textContent = '❌ ' + data.message;
            }
        });
}

function testWrite() {
    const el = document.getElementById('write-test-result');
    el.textContent = 'Testing...';
    fetch('?action=test_write')
        .then(r => r.json())
        .then(data => {
            const r = data.results;
            el.innerHTML = 'Dir: <b>' + r.mkdir + '</b> | Writable: <b>' + r.dir_writable + '</b> | Write: <b>' + r.file_write + '</b> | error_log: <b>' + r.error_log + '</b>';
        })
        .catch(err => { el.textContent = 'Error: ' + err.message; });
}

function phpInfo() {
    document.getElementById('phpinfo-modal').style.display = 'block';
    document.getElementById('phpinfo-frame').src = '?action=phpinfo&t=' + Date.now();
}
</script>

<?php
// Handle phpinfo action (in iframe)
if (isset($_GET['action']) && $_GET['action'] === 'phpinfo') {
    ob_end_clean();
    phpinfo();
    exit;
}
?>
</body>
</html>
<?php

function colorize_log($line) {
    // Highlight dates
    $line = preg_replace('/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', '<span class="log-date">$1</span>', $line);
    // Highlight errors
    if (preg_match('/error|fatal|critical|exception/i', $line)) {
        $line = '<span class="log-error">' . $line . '</span>';
    } elseif (preg_match('/warning|warn|deprecated/i', $line)) {
        $line = '<span class="log-warn">' . $line . '</span>';
    } elseif (preg_match('/success|ok|passed/i', $line)) {
        $line = '<span class="log-info">' . $line . '</span>';
    }
    return $line;
}
?>
