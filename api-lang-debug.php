<?php
/**
 * Language Debug Tool
 * Debug language/localization settings and API
 * Redesigned with modern UI
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/dev-tools-style.php");

// Check access
check_dev_tools_access();

$db = new DbConn($config);

// Get current session language
$session_lang = $_SESSION['lang'] ?? 0;

// Get database language
$user_id = intval($_SESSION['user_id'] ?? 0);
$db_lang = 0;
if ($user_id) {
    $query = "SELECT lang FROM authorize WHERE id = ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $db_lang = $row['lang'] ?? 0;
}

$is_synced = ($session_lang == $db_lang);

// Load XML files
$lang_us_file = "inc/string-us.xml";
$lang_th_file = "inc/string-th.xml";

$xml_us = file_exists($lang_us_file) ? simplexml_load_file($lang_us_file) : null;
$xml_th = file_exists($lang_th_file) ? simplexml_load_file($lang_th_file) : null;

// Get current XML based on session
$current_xml = ($session_lang == 1) ? $xml_th : $xml_us;

// Sample menu items for comparison
$sample_keys = ['generalinformation', 'company', 'category', 'user', 'dashboard', 'report', 'brand', 'product'];

// Check if API mode requested
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    $sample_items = [];
    foreach ($sample_keys as $key) {
        $sample_items[$key] = (string)($current_xml->$key ?? '');
    }
    echo json_encode([
        'status' => 'success',
        'session_lang' => $session_lang,
        'db_lang' => $db_lang,
        'synced' => $is_synced,
        'current_lang_name' => ($session_lang == 0 ? 'English' : 'Thai'),
        'sample_menu_items' => $sample_items,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language Debug - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('Language Debug', 'Debug language/localization settings and synchronization', 'fa-language', '#2980b9'); ?>
        
        <!-- Status Overview -->
        <div class="stats-grid">
            <div class="stat-card <?php echo $session_lang == 0 ? 'info' : 'warning'; ?>">
                <div class="stat-icon"><i class="fa fa-globe"></i></div>
                <div class="stat-value"><?php echo $session_lang == 0 ? 'EN' : 'TH'; ?></div>
                <div class="stat-label">Session Language</div>
            </div>
            <div class="stat-card <?php echo $db_lang == 0 ? 'info' : 'warning'; ?>">
                <div class="stat-icon"><i class="fa fa-database"></i></div>
                <div class="stat-value"><?php echo $db_lang == 0 ? 'EN' : 'TH'; ?></div>
                <div class="stat-label">Database Language</div>
            </div>
            <div class="stat-card <?php echo $is_synced ? 'success' : 'danger'; ?>">
                <div class="stat-icon"><i class="fa fa-<?php echo $is_synced ? 'check-circle' : 'times-circle'; ?>"></i></div>
                <div class="stat-value"><?php echo $is_synced ? 'Yes' : 'No'; ?></div>
                <div class="stat-label">Synced</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-user"></i></div>
                <div class="stat-value"><?php echo $user_id ?: 'N/A'; ?></div>
                <div class="stat-label">User ID</div>
            </div>
        </div>
        
        <!-- Sync Status -->
        <div class="test-section">
            <h2><i class="fa fa-refresh"></i> Sync Status</h2>
            <?php if ($is_synced): ?>
                <div class="info-box success">
                    <i class="fa fa-check-circle"></i>
                    <div><strong>Languages are synchronized!</strong> Session and database language settings match.</div>
                </div>
            <?php else: ?>
                <div class="info-box warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div>
                        <strong>Languages are NOT synchronized!</strong><br>
                        Session: <?php echo $session_lang == 0 ? 'English' : 'Thai'; ?><br>
                        Database: <?php echo $db_lang == 0 ? 'English' : 'Thai'; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Language Files -->
        <div class="test-section">
            <h2><i class="fa fa-file-code-o"></i> Language Files</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Path</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>English</strong></td>
                        <td><code><?php echo $lang_us_file; ?></code></td>
                        <td>
                            <?php if ($xml_us): ?>
                                <span class="status-badge status-pass"><i class="fa fa-check"></i> Loaded</span>
                            <?php else: ?>
                                <span class="status-badge status-fail"><i class="fa fa-times"></i> Not Found</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Thai</strong></td>
                        <td><code><?php echo $lang_th_file; ?></code></td>
                        <td>
                            <?php if ($xml_th): ?>
                                <span class="status-badge status-pass"><i class="fa fa-check"></i> Loaded</span>
                            <?php else: ?>
                                <span class="status-badge status-fail"><i class="fa fa-times"></i> Not Found</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Sample Translations -->
        <div class="test-section">
            <h2><i class="fa fa-list"></i> Sample Translations</h2>
            <p style="color: #666; margin-bottom: 15px;">Comparing translations between English and Thai language files:</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>English</th>
                        <th>Thai</th>
                        <th>Current</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sample_keys as $key): ?>
                    <tr>
                        <td><code><?php echo $key; ?></code></td>
                        <td><?php echo htmlspecialchars((string)($xml_us->$key ?? '-')); ?></td>
                        <td><?php echo htmlspecialchars((string)($xml_th->$key ?? '-')); ?></td>
                        <td>
                            <span style="background: <?php echo $session_lang == 0 ? '#d1ecf1' : '#fff3cd'; ?>; padding: 3px 8px; border-radius: 4px;">
                                <?php echo htmlspecialchars((string)($current_xml->$key ?? '-')); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Configuration -->
        <div class="test-section">
            <h2><i class="fa fa-cog"></i> Language Configuration</h2>
            <ul class="kv-list">
                <li class="kv-item">
                    <span class="kv-key">Session Language Code</span>
                    <span class="kv-value"><?php echo $session_lang; ?> (<?php echo $session_lang == 0 ? 'English' : 'Thai'; ?>)</span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Database Language Code</span>
                    <span class="kv-value"><?php echo $db_lang; ?> (<?php echo $db_lang == 0 ? 'English' : 'Thai'; ?>)</span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Current User ID</span>
                    <span class="kv-value"><?php echo $user_id ?: 'Not logged in'; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">English File Path</span>
                    <span class="kv-value mono"><?php echo realpath($lang_us_file) ?: $lang_us_file; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Thai File Path</span>
                    <span class="kv-value mono"><?php echo realpath($lang_th_file) ?: $lang_th_file; ?></span>
                </li>
            </ul>
        </div>
        
        <!-- JSON Output -->
        <div class="test-section">
            <h2><i class="fa fa-code"></i> API Response Preview</h2>
            <pre class="code-block"><?php 
                $sample_items = [];
                foreach ($sample_keys as $key) {
                    $sample_items[$key] = (string)($current_xml->$key ?? '');
                }
                echo format_json_html([
                    'status' => 'success',
                    'session_lang' => $session_lang,
                    'db_lang' => $db_lang,
                    'synced' => $is_synced,
                    'current_lang_name' => ($session_lang == 0 ? 'English' : 'Thai'),
                    'sample_menu_items' => $sample_items,
                    'timestamp' => date('Y-m-d H:i:s')
                ]); 
            ?></pre>
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
