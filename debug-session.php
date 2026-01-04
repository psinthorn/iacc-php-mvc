<?php
/**
 * Session Debug Tool
 * View and analyze current session data
 * Redesigned with modern UI
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/dev-tools-style.php");

// Check access
check_dev_tools_access();

// Gather session data
$session_data = $_SESSION;
$session_id = session_id();
$session_name = session_name();
$session_status = session_status();
$session_save_path = session_save_path();

// Check if API mode requested
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'session_id' => $session_id,
        'session_name' => $session_name,
        'data' => $session_data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Session status labels
$status_labels = [
    PHP_SESSION_DISABLED => 'Disabled',
    PHP_SESSION_NONE => 'None',
    PHP_SESSION_ACTIVE => 'Active'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('Session Debug', 'View and analyze current PHP session data', 'fa-key', '#e67e22'); ?>
        
        <!-- Session Info Stats -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-hashtag"></i></div>
                <div class="stat-value" style="font-size: 14px; word-break: break-all;"><?php echo substr($session_id, 0, 16); ?>...</div>
                <div class="stat-label">Session ID</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $status_labels[$session_status]; ?></div>
                <div class="stat-label">Status</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-database"></i></div>
                <div class="stat-value"><?php echo count($session_data); ?></div>
                <div class="stat-label">Variables</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fa fa-tag"></i></div>
                <div class="stat-value"><?php echo $session_name; ?></div>
                <div class="stat-label">Session Name</div>
            </div>
        </div>
        
        <!-- Session Configuration -->
        <div class="test-section">
            <h2><i class="fa fa-cog"></i> Session Configuration</h2>
            <ul class="kv-list">
                <li class="kv-item">
                    <span class="kv-key">Session ID</span>
                    <span class="kv-value mono"><?php echo $session_id; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Session Name</span>
                    <span class="kv-value"><?php echo $session_name; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Save Path</span>
                    <span class="kv-value mono"><?php echo $session_save_path ?: '(default)'; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Cookie Lifetime</span>
                    <span class="kv-value"><?php echo ini_get('session.cookie_lifetime'); ?> seconds</span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">GC Max Lifetime</span>
                    <span class="kv-value"><?php echo ini_get('session.gc_maxlifetime'); ?> seconds</span>
                </li>
            </ul>
        </div>
        
        <!-- Key Session Variables -->
        <div class="test-section">
            <h2><i class="fa fa-key"></i> Key Session Variables</h2>
            <div class="stats-grid" style="margin-bottom: 20px;">
                <div class="stat-card <?php echo isset($_SESSION['user_id']) ? 'success' : 'danger'; ?>">
                    <div class="stat-icon"><i class="fa fa-user"></i></div>
                    <div class="stat-value"><?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></div>
                    <div class="stat-label">User ID</div>
                </div>
                <div class="stat-card <?php echo isset($_SESSION['com_id']) ? 'success' : 'warning'; ?>">
                    <div class="stat-icon"><i class="fa fa-building"></i></div>
                    <div class="stat-value"><?php echo $_SESSION['com_id'] ?? 'NOT SET'; ?></div>
                    <div class="stat-label">Company ID</div>
                </div>
                <div class="stat-card <?php echo isset($_SESSION['user_level']) ? 'info' : 'warning'; ?>">
                    <div class="stat-icon"><i class="fa fa-shield"></i></div>
                    <div class="stat-value"><?php 
                        $level = $_SESSION['user_level'] ?? -1;
                        echo $level == 2 ? 'Super Admin' : ($level == 1 ? 'Admin' : ($level == 0 ? 'User' : 'NOT SET'));
                    ?></div>
                    <div class="stat-label">User Level</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-building-o"></i></div>
                    <div class="stat-value" style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['com_name'] ?? 'NOT SET'); ?></div>
                    <div class="stat-label">Company Name</div>
                </div>
            </div>
        </div>
        
        <!-- All Session Variables -->
        <div class="test-section">
            <h2><i class="fa fa-list"></i> All Session Variables</h2>
            <?php if (empty($session_data)): ?>
                <div class="info-box warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div>No session variables are currently set.</div>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Type</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($session_data as $key => $value): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($key); ?></code></td>
                            <td><span class="status-badge status-info"><?php echo gettype($value); ?></span></td>
                            <td>
                                <?php 
                                if (is_array($value) || is_object($value)) {
                                    echo '<pre class="code-block" style="margin: 0; max-height: 100px; overflow: auto;">';
                                    echo htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT));
                                    echo '</pre>';
                                } else {
                                    echo '<code>' . htmlspecialchars((string)$value) . '</code>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Raw JSON -->
        <div class="test-section">
            <h2><i class="fa fa-code"></i> Raw JSON Output</h2>
            <pre class="code-block"><?php echo format_json_html($session_data); ?></pre>
        </div>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="?" class="btn-dev btn-primary"><i class="fa fa-refresh"></i> Refresh</a>
            <a href="?format=json" class="btn-dev btn-outline" target="_blank"><i class="fa fa-download"></i> JSON API</a>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
