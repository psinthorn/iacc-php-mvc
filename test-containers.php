<?php
/**
 * Container Test Tool
 * View raw Docker container data for debugging
 * Redesigned with modern UI
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/dev-tools-style.php");

// Check access (Docker tools must be enabled)
check_docker_tools_access();

$docker_socket = '/var/run/docker.sock';
$containers = [];
$api_version = '1.43';
$error = null;

// Get API version
$output = [];
exec("curl -s --unix-socket $docker_socket http://localhost/version 2>&1", $output, $return_var);
$response = implode('', $output);
$version = json_decode($response, true);

if ($version) {
    $api_version = $version['ApiVersion'] ?? '1.43';
    
    // Get containers
    $cmd = "curl -s --unix-socket $docker_socket 'http://localhost/v$api_version/containers/json?all=true' 2>&1";
    $output = [];
    exec($cmd, $output, $return_var);
    $response = implode("\n", $output);
    $containers = json_decode($response, true) ?: [];
} else {
    $error = 'Could not connect to Docker API';
}

// Check for duplicates
$ids = [];
$names = [];
foreach ($containers as $c) {
    $ids[] = $c['Id'];
    $names[] = ltrim($c['Names'][0] ?? '', '/');
}

$unique_ids = count(array_unique($ids));
$unique_names = count(array_unique($names));
$has_duplicate_ids = count($ids) !== $unique_ids;
$has_duplicate_names = count($names) !== $unique_names;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container Test - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('Container Test', 'View raw Docker container data for debugging duplicate issues', 'fa-cube', '#9b59b6'); ?>
        
        <?php if ($error): ?>
        <div class="info-box danger">
            <i class="fa fa-times-circle"></i>
            <div><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php else: ?>
        
        <!-- Overview Stats -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-code"></i></div>
                <div class="stat-value"><?php echo $api_version; ?></div>
                <div class="stat-label">API Version</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-cubes"></i></div>
                <div class="stat-value"><?php echo count($containers); ?></div>
                <div class="stat-label">Total Containers</div>
            </div>
            <div class="stat-card <?php echo $has_duplicate_ids ? 'danger' : 'success'; ?>">
                <div class="stat-icon"><i class="fa fa-fingerprint"></i></div>
                <div class="stat-value"><?php echo $unique_ids; ?>/<?php echo count($ids); ?></div>
                <div class="stat-label">Unique IDs</div>
            </div>
            <div class="stat-card <?php echo $has_duplicate_names ? 'danger' : 'success'; ?>">
                <div class="stat-icon"><i class="fa fa-tags"></i></div>
                <div class="stat-value"><?php echo $unique_names; ?>/<?php echo count($names); ?></div>
                <div class="stat-label">Unique Names</div>
            </div>
        </div>
        
        <!-- Duplicate Check -->
        <div class="test-section">
            <h2><i class="fa fa-check-circle"></i> Duplicate Check</h2>
            <?php if (!$has_duplicate_ids && !$has_duplicate_names): ?>
                <div class="info-box success">
                    <i class="fa fa-check-circle"></i>
                    <div><strong>No duplicates found!</strong> All container IDs and names are unique.</div>
                </div>
            <?php else: ?>
                <?php if ($has_duplicate_ids): ?>
                <div class="info-box danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <div><strong>DUPLICATE IDs FOUND!</strong> This should not happen.</div>
                </div>
                <?php endif; ?>
                <?php if ($has_duplicate_names): ?>
                <div class="info-box warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div><strong>DUPLICATE NAMES FOUND!</strong> Some containers have the same name.</div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Container List -->
        <div class="test-section">
            <h2><i class="fa fa-list"></i> Raw Container Data</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID (Short)</th>
                        <th>Name</th>
                        <th>Image</th>
                        <th>State</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($containers as $i => $c): 
                        $id = substr($c['Id'], 0, 12);
                        $name = ltrim($c['Names'][0] ?? '', '/');
                        $image = $c['Image'];
                        $state = $c['State'];
                        $created = date('Y-m-d H:i', $c['Created']);
                    ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><code><?php echo $id; ?></code></td>
                        <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                        <td style="font-size: 12px;"><?php echo htmlspecialchars($image); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $state === 'running' ? 'pass' : 'fail'; ?>">
                                <?php echo strtoupper($state); ?>
                            </span>
                        </td>
                        <td style="font-size: 12px;"><?php echo $created; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- All Names List -->
        <div class="test-section">
            <h2><i class="fa fa-tags"></i> All Container Names</h2>
            <pre class="code-block"><?php echo format_json_html($names); ?></pre>
        </div>
        
        <!-- All IDs List -->
        <div class="test-section">
            <h2><i class="fa fa-fingerprint"></i> All Container IDs</h2>
            <pre class="code-block"><?php 
                $short_ids = array_map(function($id) { return substr($id, 0, 12); }, $ids);
                echo format_json_html($short_ids); 
            ?></pre>
        </div>
        
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="?" class="btn-dev btn-primary"><i class="fa fa-refresh"></i> Refresh</a>
            <a href="index.php?page=containers" class="btn-dev btn-outline"><i class="fa fa-server"></i> Full Container Manager</a>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
