<?php
/**
 * Docker Socket Test Tool
 * Test Docker API connectivity and socket access
 * Redesigned with modern UI
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/dev-tools-style.php");

// Check access (Docker tools must be enabled)
check_docker_tools_access();

// Docker socket path
$socket = '/var/run/docker.sock';
$socket_exists = file_exists($socket);
$socket_readable = $socket_exists ? is_readable($socket) : false;

// Test Docker API
$docker_version = null;
$api_version = '1.43';
$containers = [];
$docker_working = false;

if ($socket_exists) {
    // Get Docker version
    $output = [];
    exec("curl -s --unix-socket $socket http://localhost/version 2>&1", $output, $return_var);
    $response = implode('', $output);
    
    if ($response) {
        $docker_version = json_decode($response, true);
        if ($docker_version) {
            $api_version = $docker_version['ApiVersion'] ?? '1.43';
            $docker_working = true;
        }
    }
    
    // Get containers
    if ($docker_working) {
        $output = [];
        exec("curl -s --unix-socket $socket 'http://localhost/v$api_version/containers/json?all=true' 2>&1", $output, $return_var);
        $response = implode('', $output);
        
        if ($response) {
            $containers = json_decode($response, true) ?: [];
        }
    }
}

// Count container states
$running_count = 0;
$stopped_count = 0;
foreach ($containers as $c) {
    if ($c['State'] === 'running') $running_count++;
    else $stopped_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Test - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('Docker Socket Test', 'Test Docker API connectivity and socket accessibility', 'fa-cloud', '#1abc9c'); ?>
        
        <!-- Status Overview -->
        <div class="stats-grid">
            <div class="stat-card <?php echo $socket_exists ? 'success' : 'danger'; ?>">
                <div class="stat-icon"><i class="fa fa-plug"></i></div>
                <div class="stat-value"><?php echo $socket_exists ? 'Found' : 'Missing'; ?></div>
                <div class="stat-label">Socket Status</div>
            </div>
            <div class="stat-card <?php echo $socket_readable ? 'success' : 'danger'; ?>">
                <div class="stat-icon"><i class="fa fa-eye"></i></div>
                <div class="stat-value"><?php echo $socket_readable ? 'Yes' : 'No'; ?></div>
                <div class="stat-label">Readable</div>
            </div>
            <div class="stat-card <?php echo $docker_working ? 'success' : 'danger'; ?>">
                <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $docker_working ? 'Working' : 'Failed'; ?></div>
                <div class="stat-label">API Status</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-cubes"></i></div>
                <div class="stat-value"><?php echo count($containers); ?></div>
                <div class="stat-label">Containers</div>
            </div>
        </div>
        
        <!-- Socket Details -->
        <div class="test-section">
            <h2><i class="fa fa-plug"></i> Socket Configuration</h2>
            <ul class="kv-list">
                <li class="kv-item">
                    <span class="kv-key">Socket Path</span>
                    <span class="kv-value mono"><?php echo $socket; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Socket Exists</span>
                    <span class="kv-value">
                        <?php if ($socket_exists): ?>
                            <span class="status-badge status-pass"><i class="fa fa-check"></i> YES</span>
                        <?php else: ?>
                            <span class="status-badge status-fail"><i class="fa fa-times"></i> NO</span>
                        <?php endif; ?>
                    </span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">Socket Readable</span>
                    <span class="kv-value">
                        <?php if ($socket_readable): ?>
                            <span class="status-badge status-pass"><i class="fa fa-check"></i> YES</span>
                        <?php else: ?>
                            <span class="status-badge status-fail"><i class="fa fa-times"></i> NO</span>
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>
        
        <?php if ($docker_working && $docker_version): ?>
        <!-- Docker Version -->
        <div class="test-section">
            <h2><i class="fa fa-info-circle"></i> Docker Version</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-tag"></i></div>
                    <div class="stat-value"><?php echo $docker_version['Version'] ?? 'N/A'; ?></div>
                    <div class="stat-label">Docker Version</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fa fa-code"></i></div>
                    <div class="stat-value"><?php echo $api_version; ?></div>
                    <div class="stat-label">API Version</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-linux"></i></div>
                    <div class="stat-value"><?php echo $docker_version['Os'] ?? 'N/A'; ?></div>
                    <div class="stat-label">OS</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-microchip"></i></div>
                    <div class="stat-value"><?php echo $docker_version['Arch'] ?? 'N/A'; ?></div>
                    <div class="stat-label">Architecture</div>
                </div>
            </div>
        </div>
        
        <!-- Container Summary -->
        <div class="test-section">
            <h2><i class="fa fa-cubes"></i> Container Summary</h2>
            <div class="stats-grid" style="margin-bottom: 20px;">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fa fa-play-circle"></i></div>
                    <div class="stat-value"><?php echo $running_count; ?></div>
                    <div class="stat-label">Running</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fa fa-stop-circle"></i></div>
                    <div class="stat-value"><?php echo $stopped_count; ?></div>
                    <div class="stat-label">Stopped</div>
                </div>
            </div>
            
            <?php if (!empty($containers)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Image</th>
                        <th>State</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($containers as $c): 
                        $name = ltrim($c['Names'][0] ?? '', '/');
                        $image = $c['Image'] ?? '';
                        $state = $c['State'] ?? '';
                        $status = $c['Status'] ?? '';
                    ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($name); ?></code></td>
                        <td><?php echo htmlspecialchars($image); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $state === 'running' ? 'pass' : 'fail'; ?>">
                                <i class="fa fa-<?php echo $state === 'running' ? 'play' : 'stop'; ?>"></i>
                                <?php echo strtoupper($state); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($status); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Success Message -->
        <div class="info-box success">
            <i class="fa fa-check-circle"></i>
            <div>
                <strong>Docker API is working!</strong><br>
                You can now use the <a href="index.php?page=containers" style="color: inherit; text-decoration: underline;">Container Manager</a> for full container management.
            </div>
        </div>
        
        <?php elseif (!$socket_exists): ?>
        <!-- Socket Not Found -->
        <div class="info-box danger">
            <i class="fa fa-times-circle"></i>
            <div>
                <strong>Docker socket not found.</strong><br>
                Make sure the Docker socket is mounted in your docker-compose.yml:
            </div>
        </div>
        <pre class="code-block">volumes:
  - /var/run/docker.sock:/var/run/docker.sock</pre>
        
        <?php else: ?>
        <!-- API Failed -->
        <div class="info-box warning">
            <i class="fa fa-exclamation-triangle"></i>
            <div>
                <strong>Docker socket found but API call failed.</strong><br>
                Check if Docker is running and the socket has correct permissions.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="?" class="btn-dev btn-primary"><i class="fa fa-refresh"></i> Test Again</a>
            <?php if ($docker_working): ?>
            <a href="index.php?page=containers" class="btn-dev btn-success" style="background: #27ae60;"><i class="fa fa-server"></i> Container Manager</a>
            <?php endif; ?>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
