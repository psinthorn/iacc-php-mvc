<?php
/**
 * Docker Container Monitor
 * Monitor and manage Docker containers
 * Admin/Super Admin only
 * 
 * Environment Modes:
 * - Development: Direct Docker socket access (full control: start/stop/restart)
 * - Production: Docker socket proxy (read-only monitoring only)
 * 
 * Development (docker-compose.yml):
 *   volumes:
 *     - /var/run/docker.sock:/var/run/docker.sock
 * 
 * Production (docker-compose.prod.yml):
 *   Uses docker-socket-proxy service for security (read-only access)
 * 
 * Docker Tools Toggle:
 * - This page respects the docker_tools setting (auto/on/off)
 * - Default is OFF unless explicitly enabled or Docker is detected
 */

$user_level = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
if ($user_level < 1) {
    echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied. Admin privileges required. (Your level: ' . $user_level . ')</div>';
    return;
}

// Check if Container Manager is enabled (separate from Docker debug tools)
$container_manager_enabled = function_exists('is_container_manager_enabled') ? is_container_manager_enabled() : false;
$container_manager_setting = function_exists('get_docker_tools_setting') ? get_docker_tools_setting('container_manager') : 'off';

if (!$container_manager_enabled) {
    ?>
    <div style="padding: 40px; text-align: center;">
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 40px; max-width: 600px; margin: 0 auto;">
            <i class="fa fa-server" style="font-size: 48px; color: #6c757d; margin-bottom: 20px;"></i>
            <h3 style="color: #333; margin-bottom: 15px;">Container Manager Disabled</h3>
            <p style="color: #6c757d; margin-bottom: 20px;">
                Container management is currently disabled.<br>
                <strong>Current setting:</strong> <?= ucfirst(htmlspecialchars($container_manager_setting)) ?>
            </p>
            <p style="color: #6c757d; margin-bottom: 25px;">
                Container Manager has start/stop/restart actions and is <strong>disabled by default</strong> for safety.<br>
                Enable it manually if you need to manage Docker containers.
            </p>
            <?php if ($user_level >= 2): ?>
            <p style="margin-bottom: 15px;">
                <a href="index.php?page=dashboard" class="btn btn-primary" style="padding: 10px 25px;">
                    <i class="fa fa-cog"></i> Go to Dashboard to Enable
                </a>
            </p>
            <p style="color: #adb5bd; font-size: 12px;">
                Super Admin can enable Container Manager in Dashboard → Developer Tools panel
            </p>
            <?php else: ?>
            <p style="color: #adb5bd; font-size: 12px;">
                Contact Super Admin to enable Container Manager if needed.
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return;
}

// Environment detection
// Production mode if APP_ENV=production or docker-socket-proxy is available
$is_production = (getenv('APP_ENV') === 'production');
$proxy_host = 'docker-socket-proxy';
$proxy_port = 2375;
$docker_socket = '/var/run/docker.sock';

// Check if we're using socket proxy (production) or direct socket (development)
function getDockerEndpointType() {
    global $is_production, $proxy_host, $proxy_port, $docker_socket;
    
    // In production, try socket proxy first
    if ($is_production) {
        // Check if proxy is reachable
        $fp = @fsockopen($proxy_host, $proxy_port, $errno, $errstr, 1);
        if ($fp) {
            fclose($fp);
            return 'proxy';
        }
    }
    
    // Fall back to direct socket (development)
    if (file_exists($docker_socket)) {
        return 'socket';
    }
    
    return 'none';
}

$docker_endpoint_type = getDockerEndpointType();

// Debug mode - uncomment to see debug info
// echo '<pre>Mode: ' . ($is_production ? 'Production' : 'Development') . ', Endpoint: ' . $docker_endpoint_type . '</pre>';

// Check if Docker is accessible
function isDockerAvailable() {
    $output = [];
    $return_var = 0;
    exec("docker info 2>&1", $output, $return_var);
    return $return_var === 0;
}

// Docker API version - auto-detect or use default
function getDockerApiVersion() {
    global $docker_endpoint_type, $proxy_host, $proxy_port, $docker_socket;
    
    if ($docker_endpoint_type === 'proxy') {
        // Use HTTP proxy
        $response = @file_get_contents("http://$proxy_host:$proxy_port/version");
        if ($response) {
            $version = json_decode($response, true);
            return $version['ApiVersion'] ?? '1.43';
        }
    } elseif ($docker_endpoint_type === 'socket' && file_exists($docker_socket)) {
        // Use Unix socket
        $output = [];
        exec("curl -s --unix-socket $docker_socket http://localhost/version 2>&1", $output, $return_var);
        $response = implode('', $output);
        $version = json_decode($response, true);
        return $version['ApiVersion'] ?? '1.43';
    }
    
    return '1.43';
}

// Helper function to call Docker API via socket or proxy
function dockerApiCall($endpoint, $method = 'GET', $timeout = 10) {
    global $docker_endpoint_type, $proxy_host, $proxy_port, $docker_socket, $is_production;
    
    // Block write operations in production
    if ($is_production && $method === 'POST') {
        return json_encode(['message' => 'Write operations disabled in production mode']);
    }
    
    if ($docker_endpoint_type === 'proxy') {
        // Use HTTP proxy (curl via HTTP)
        $url = "http://$proxy_host:$proxy_port$endpoint";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?: null;
    } elseif ($docker_endpoint_type === 'socket' && file_exists($docker_socket)) {
        // Use Unix socket (shell curl)
        $cmd = "curl -s --unix-socket $docker_socket";
        if ($method === 'POST') {
            $cmd .= " -X POST";
        }
        $cmd .= " -m $timeout";
        $cmd .= " 'http://localhost$endpoint' 2>&1";
        
        $output = [];
        exec($cmd, $output, $return_var);
        return implode("\n", $output);
    }
    
    return null;
}

// Try to read from Docker socket directly if docker CLI not available
function getContainersFromSocket() {
    global $docker_endpoint_type;
    
    if ($docker_endpoint_type === 'none') {
        return null;
    }
    
    $apiVersion = getDockerApiVersion();
    $response = dockerApiCall("/v$apiVersion/containers/json?all=true");
    
    if (!$response) {
        return null;
    }
    
    $containers = json_decode($response, true);
    if (!is_array($containers)) {
        return null;
    }
    
    // Transform to our format
    $result = [];
    foreach ($containers as $c) {
        $ports = [];
        if (isset($c['Ports'])) {
            foreach ($c['Ports'] as $p) {
                if (isset($p['PublicPort'])) {
                    $ports[] = ($p['PublicPort'] ?? '') . ':' . ($p['PrivatePort'] ?? '') . '/' . ($p['Type'] ?? 'tcp');
                }
            }
        }
        
        $result[] = [
            'id' => substr($c['Id'], 0, 12),
            'name' => ltrim($c['Names'][0] ?? '', '/'),
            'image' => $c['Image'],
            'status' => $c['Status'],
            'state' => strtolower($c['State']),
            'ports' => implode(', ', $ports),
            'created' => date('Y-m-d H:i:s', $c['Created'])
        ];
    }
    
    return $result;
}

// Get container stats from Docker API
function getContainerStatsFromSocket() {
    global $docker_endpoint_type;
    
    if ($docker_endpoint_type === 'none') {
        return [];
    }
    
    $apiVersion = getDockerApiVersion();
    
    // First get list of running containers
    $response = dockerApiCall("/v$apiVersion/containers/json");
    
    $containers = json_decode($response, true);
    if (!is_array($containers)) {
        return [];
    }
    
    $stats = [];
    foreach ($containers as $c) {
        $name = ltrim($c['Names'][0] ?? '', '/');
        $id = $c['Id'];
        
        // Get stats for this container (one-shot) - use shorter timeout
        $statResponse = dockerApiCall("/v$apiVersion/containers/$id/stats?stream=false", 'GET', 5);
        
        $stat = json_decode($statResponse, true);
        if ($stat && isset($stat['cpu_stats'])) {
            // Calculate CPU percentage
            $cpuDelta = ($stat['cpu_stats']['cpu_usage']['total_usage'] ?? 0) - 
                        ($stat['precpu_stats']['cpu_usage']['total_usage'] ?? 0);
            $systemDelta = ($stat['cpu_stats']['system_cpu_usage'] ?? 0) - 
                           ($stat['precpu_stats']['system_cpu_usage'] ?? 0);
            $cpuCount = $stat['cpu_stats']['online_cpus'] ?? 1;
            $cpuPercent = ($systemDelta > 0) ? ($cpuDelta / $systemDelta) * $cpuCount * 100 : 0;
            
            // Memory
            $memUsage = $stat['memory_stats']['usage'] ?? 0;
            $memLimit = $stat['memory_stats']['limit'] ?? 1;
            $memPercent = ($memUsage / $memLimit) * 100;
            
            $stats[$name] = [
                'cpu' => number_format($cpuPercent, 2) . '%',
                'mem_usage' => formatBytes($memUsage) . ' / ' . formatBytes($memLimit),
                'mem_perc' => number_format($memPercent, 2) . '%',
                'net_io' => '--',
                'block_io' => '--'
            ];
        }
    }
    
    return $stats;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

// Handle container actions via AJAX
if (isset($_GET['action']) && isset($_GET['container'])) {
    header('Content-Type: application/json');
    
    // Block write operations in production
    if ($is_production) {
        echo json_encode(['success' => false, 'message' => 'Container management disabled in production mode (read-only)']);
        exit;
    }
    
    $container = $_GET['container'];
    $action = $_GET['action'];
    
    $allowed_actions = ['start', 'stop', 'restart'];
    if (!in_array($action, $allowed_actions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Try Docker CLI first
    if (isDockerAvailable()) {
        $output = [];
        $return_var = 0;
        exec("docker $action " . escapeshellarg($container) . " 2>&1", $output, $return_var);
        echo json_encode([
            'success' => $return_var === 0,
            'message' => implode("\n", $output) ?: ($return_var === 0 ? 'Success' : 'Failed')
        ]);
    } else {
        // Try Docker API via socket
        $apiVersion = getDockerApiVersion();
        $response = dockerApiCall("/v$apiVersion/containers/" . urlencode($container) . "/$action", 'POST');
        
        // Check if successful (empty response means success for start/stop/restart)
        $success = empty($response) || strpos($response, 'error') === false;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Success' : ($response ?: 'Failed')
        ]);
    }
    exit;
}

// Get container logs via AJAX
if (isset($_GET['logs']) && isset($_GET['container'])) {
    header('Content-Type: application/json');
    $container = $_GET['container'];
    $lines = isset($_GET['lines']) ? intval($_GET['lines']) : 50;
    
    // Try Docker CLI first
    if (isDockerAvailable()) {
        $output = [];
        exec("docker logs --tail $lines " . escapeshellarg($container) . " 2>&1", $output, $return_var);
        echo json_encode([
            'success' => true,
            'logs' => implode("\n", $output)
        ]);
    } else {
        // Try Docker API via socket using shell curl
        $socket = '/var/run/docker.sock';
        if (file_exists($socket)) {
            $apiVersion = getDockerApiVersion();
            $containerEsc = escapeshellarg($container);
            $output = [];
            exec("curl -s --unix-socket $socket 'http://localhost/v$apiVersion/containers/$containerEsc/logs?stdout=true&stderr=true&tail=$lines' 2>&1", $output, $return_var);
            
            // Docker log output has stream headers, clean them
            $logs = implode("\n", $output);
            $logs = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $logs);
            
            echo json_encode([
                'success' => true,
                'logs' => $logs ?: 'No logs available'
            ]);
        } else {
            echo json_encode(['success' => false, 'logs' => 'Docker not accessible']);
        }
    }
    exit;
}

// Function to get container list
function getContainers() {
    // Try Docker CLI first
    if (isDockerAvailable()) {
        $format = '{{.ID}}|{{.Names}}|{{.Image}}|{{.Status}}|{{.State}}|{{.Ports}}|{{.CreatedAt}}';
        $output = [];
        exec("docker ps -a --format '$format' 2>&1", $output, $return_var);
        
        $containers = [];
        foreach ($output as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 7) {
                $containers[] = [
                    'id' => $parts[0],
                    'name' => $parts[1],
                    'image' => $parts[2],
                    'status' => $parts[3],
                    'state' => strtolower($parts[4]),
                    'ports' => $parts[5],
                    'created' => $parts[6]
                ];
            }
        }
        return $containers;
    }
    
    // Try Docker socket API
    $socketContainers = getContainersFromSocket();
    if ($socketContainers !== null) {
        return $socketContainers;
    }
    
    return ['error' => 'Docker is not accessible. To enable container monitoring, mount the Docker socket into the PHP container.'];
}

// Function to get container stats
function getContainerStats() {
    if (isDockerAvailable()) {
        $stats = [];
        $format = '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}|{{.NetIO}}|{{.BlockIO}}';
        $output = [];
        exec("docker stats --no-stream --format '$format' 2>&1", $output, $return_var);
        
        foreach ($output as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 6) {
                $name = ltrim($parts[0], '/');
                $stats[$name] = [
                    'cpu' => $parts[1],
                    'mem_usage' => $parts[2],
                    'mem_perc' => $parts[3],
                    'net_io' => $parts[4],
                    'block_io' => $parts[5]
                ];
            }
        }
        return $stats;
    }
    
    // Try socket API
    return getContainerStatsFromSocket();
}

// Function to get Docker system info
function getDockerInfo() {
    $info = [
        'version' => 'N/A',
        'containers_total' => 0,
        'containers_running' => 0,
        'containers_stopped' => 0,
        'images' => 0
    ];
    
    if (isDockerAvailable()) {
        $output = [];
        exec("docker version --format '{{.Server.Version}}' 2>&1", $output);
        $info['version'] = $output[0] ?? 'Unknown';
        
        $output = [];
        exec("docker info --format '{{.Containers}} {{.ContainersRunning}} {{.ContainersPaused}} {{.ContainersStopped}} {{.Images}}' 2>&1", $output);
        if (isset($output[0])) {
            $parts = explode(' ', $output[0]);
            $info['containers_total'] = $parts[0] ?? 0;
            $info['containers_running'] = $parts[1] ?? 0;
            $info['containers_paused'] = $parts[2] ?? 0;
            $info['containers_stopped'] = $parts[3] ?? 0;
            $info['images'] = $parts[4] ?? 0;
        }
    } else {
        // Try socket API using shell curl
        $socket = '/var/run/docker.sock';
        if (file_exists($socket)) {
            $response = dockerApiCall('/version');
            $version = json_decode($response, true);
            if ($version) {
                $info['version'] = $version['Version'] ?? 'Unknown';
            }
            
            // Get info
            $response = dockerApiCall('/info');
            $dockerInfo = json_decode($response, true);
            if ($dockerInfo) {
                $info['containers_total'] = $dockerInfo['Containers'] ?? 0;
                $info['containers_running'] = $dockerInfo['ContainersRunning'] ?? 0;
                $info['containers_paused'] = $dockerInfo['ContainersPaused'] ?? 0;
                $info['containers_stopped'] = $dockerInfo['ContainersStopped'] ?? 0;
                $info['images'] = $dockerInfo['Images'] ?? 0;
            }
        }
    }
    
    return $info;
}

$dockerAvailable = isDockerAvailable() || file_exists('/var/run/docker.sock');
$containers = getContainers();
$stats = [];
$dockerInfo = getDockerInfo();

if (is_array($containers) && !isset($containers['error'])) {
    $stats = getContainerStats();
    // Merge stats into containers
    foreach ($containers as &$container) {
        if (isset($stats[$container['name']])) {
            $container = array_merge($container, $stats[$container['name']]);
        }
    }
}

// Count states
$running_count = $dockerInfo['containers_running'] ?? 0;
$stopped_count = $dockerInfo['containers_stopped'] ?? 0;

if (is_array($containers) && !isset($containers['error']) && $running_count == 0) {
    foreach ($containers as $c) {
        if ($c['state'] === 'running') $running_count++;
        else $stopped_count++;
    }
}
?>

<style>
.container-monitor { max-width: 1400px; margin: 0 auto; }
.monitor-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.monitor-title { font-size: 28px; font-weight: 600; color: #1f2937; margin: 0; display: flex; align-items: center; gap: 12px; }
.monitor-title i { color: #0ea5e9; }
.monitor-actions { display: flex; gap: 10px; }
.btn-refresh { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; background: #f3f4f6; color: #374151; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.btn-refresh:hover { background: #e5e7eb; }

/* Stats Grid */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
.stat-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.stat-card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.stat-card-icon.docker { background: #0ea5e920; color: #0ea5e9; }
.stat-card-icon.running { background: #10b98120; color: #10b981; }
.stat-card-icon.stopped { background: #ef444420; color: #ef4444; }
.stat-card-icon.images { background: #8b5cf620; color: #8b5cf6; }
.stat-card-value { font-size: 28px; font-weight: 700; color: #1f2937; }
.stat-card-label { font-size: 13px; color: #6b7280; margin-top: 4px; }

/* Container Cards */
.containers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
.container-card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
.container-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

.container-card-header { padding: 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
.container-info { display: flex; align-items: center; gap: 14px; }
.container-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.container-icon.running { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.container-icon.stopped { background: linear-gradient(135deg, #6b7280, #4b5563); color: #fff; }
.container-icon.paused { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.container-name { font-size: 16px; font-weight: 600; color: #1f2937; }
.container-image { font-size: 12px; color: #6b7280; margin-top: 2px; display: flex; align-items: center; gap: 6px; }
.container-image i { font-size: 10px; }

.container-status { display: flex; align-items: center; gap: 8px; }
.status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.status-badge.running { background: #d1fae5; color: #065f46; }
.status-badge.exited, .status-badge.stopped { background: #fee2e2; color: #991b1b; }
.status-badge.paused { background: #fef3c7; color: #92400e; }

.container-card-body { padding: 20px; }
.container-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
.container-stat { text-align: center; }
.container-stat-value { font-size: 18px; font-weight: 600; color: #1f2937; }
.container-stat-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

.container-ports { margin-bottom: 16px; }
.container-ports-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.port-badges { display: flex; flex-wrap: wrap; gap: 6px; }
.port-badge { background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-family: 'SF Mono', monospace; }

.container-card-footer { padding: 16px 20px; background: #f9fafb; border-top: 1px solid #f3f4f6; display: flex; gap: 10px; }
.container-btn { flex: 1; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
.container-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.container-btn.start { background: #d1fae5; color: #065f46; }
.container-btn.start:hover:not(:disabled) { background: #a7f3d0; }
.container-btn.stop { background: #fee2e2; color: #991b1b; }
.container-btn.stop:hover:not(:disabled) { background: #fecaca; }
.container-btn.restart { background: #e0e7ff; color: #3730a3; }
.container-btn.restart:hover:not(:disabled) { background: #c7d2fe; }
.container-btn.logs { background: #f3f4f6; color: #374151; }
.container-btn.logs:hover { background: #e5e7eb; }

/* Logs Modal */
.logs-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
.logs-modal.show { display: flex; }
.logs-modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 900px; max-height: 80vh; display: flex; flex-direction: column; }
.logs-modal-header { padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
.logs-modal-title { font-size: 18px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 10px; }
.logs-modal-close { width: 36px; height: 36px; border-radius: 8px; border: none; background: #f3f4f6; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
.logs-modal-close:hover { background: #e5e7eb; }
.logs-modal-body { flex: 1; overflow: auto; padding: 0; }
.logs-content { background: #1e1e1e; color: #d4d4d4; font-family: 'SF Mono', 'Menlo', monospace; font-size: 12px; line-height: 1.6; padding: 20px; white-space: pre-wrap; word-break: break-all; min-height: 300px; margin: 0; }

/* Empty State */
.empty-state { background: #fff; border-radius: 16px; padding: 60px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
.empty-icon { width: 80px; height: 80px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
.empty-icon i { font-size: 32px; color: #9ca3af; }
.empty-title { font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 8px; }
.empty-text { color: #6b7280; font-size: 14px; }

/* Loading */
.loading-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Toast */
.toast { position: fixed; bottom: 20px; right: 20px; padding: 16px 24px; border-radius: 12px; font-size: 14px; font-weight: 500; z-index: 3000; transform: translateY(100px); opacity: 0; transition: all 0.3s ease; }
.toast.show { transform: translateY(0); opacity: 1; }
.toast.success { background: #10b981; color: #fff; }
.toast.error { background: #ef4444; color: #fff; }

/* Skeleton Loading */
.skeleton-container { display: none; }
.skeleton-loading .skeleton-container { display: block; }
.skeleton-loading .content-container { display: none !important; }
.content-container { display: block; }

@keyframes skeleton-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-wave 1.5s ease-in-out infinite;
    border-radius: 4px;
}

@keyframes skeleton-wave {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-text { height: 14px; margin-bottom: 8px; }
.skeleton-text.sm { width: 60%; }
.skeleton-text.md { width: 80%; }
.skeleton-text.lg { width: 100%; }
.skeleton-text.title { height: 28px; width: 250px; margin-bottom: 12px; }

.skeleton-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.skeleton-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    margin-bottom: 12px;
}

.skeleton-value {
    height: 32px;
    width: 60px;
    margin-bottom: 8px;
}

.skeleton-label {
    height: 12px;
    width: 100px;
}

.skeleton-container-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.skeleton-card-header {
    padding: 20px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.skeleton-container-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 14px;
}

.skeleton-card-body {
    padding: 20px;
}

.skeleton-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}

.skeleton-stat-item {
    text-align: center;
}

.skeleton-stat-value {
    height: 22px;
    width: 50px;
    margin: 0 auto 6px;
}

.skeleton-stat-label {
    height: 10px;
    width: 40px;
    margin: 0 auto;
}

.skeleton-card-footer {
    padding: 16px 20px;
    background: #f9fafb;
    border-top: 1px solid #f3f4f6;
    display: flex;
    gap: 10px;
}

.skeleton-btn {
    flex: 1;
    height: 38px;
    border-radius: 8px;
}

.skeleton-badge {
    height: 28px;
    width: 80px;
    border-radius: 20px;
}
</style>

<!-- Skeleton loading script - runs immediately -->
<script>
// This runs immediately when parsed to ensure skeleton is visible
// before the rest of the page content
</script>

<div class="container-monitor skeleton-loading" id="containerMonitor">
    <!-- Skeleton Loading State -->
    <div class="skeleton-container">
        <!-- Skeleton Header -->
        <div class="monitor-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="skeleton skeleton-text title"></div>
            </div>
            <div class="skeleton" style="width: 100px; height: 40px; border-radius: 10px;"></div>
        </div>
        
        <!-- Skeleton Stats Grid -->
        <div class="stats-grid">
            <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="skeleton-stat-card">
                <div class="skeleton skeleton-icon"></div>
                <div class="skeleton skeleton-value"></div>
                <div class="skeleton skeleton-label"></div>
            </div>
            <?php endfor; ?>
        </div>
        
        <!-- Skeleton Container Cards -->
        <div class="containers-grid">
            <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="skeleton-container-card">
                <div class="skeleton-card-header">
                    <div style="display: flex; align-items: center;">
                        <div class="skeleton skeleton-container-icon"></div>
                        <div>
                            <div class="skeleton" style="width: 120px; height: 16px; margin-bottom: 6px;"></div>
                            <div class="skeleton" style="width: 180px; height: 12px;"></div>
                        </div>
                    </div>
                    <div class="skeleton skeleton-badge"></div>
                </div>
                <div class="skeleton-card-body">
                    <div class="skeleton-stats-row">
                        <div class="skeleton-stat-item">
                            <div class="skeleton skeleton-stat-value"></div>
                            <div class="skeleton skeleton-stat-label"></div>
                        </div>
                        <div class="skeleton-stat-item">
                            <div class="skeleton skeleton-stat-value"></div>
                            <div class="skeleton skeleton-stat-label"></div>
                        </div>
                        <div class="skeleton-stat-item">
                            <div class="skeleton skeleton-stat-value"></div>
                            <div class="skeleton skeleton-stat-label"></div>
                        </div>
                    </div>
                    <div class="skeleton" style="width: 100%; height: 32px; margin-bottom: 12px;"></div>
                    <div class="skeleton" style="width: 60%; height: 12px;"></div>
                </div>
                <div class="skeleton-card-footer">
                    <div class="skeleton skeleton-btn"></div>
                    <div class="skeleton skeleton-btn"></div>
                    <div class="skeleton skeleton-btn"></div>
                    <div class="skeleton skeleton-btn"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Actual Content -->
    <div class="content-container">
    <!-- Header -->
    <div class="monitor-header">
        <h1 class="monitor-title">
            <i class="fa fa-docker"></i> Container Monitor
            <?php if ($is_production): ?>
            <span style="font-size: 12px; background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 6px; font-weight: 500;">
                <i class="fa fa-lock"></i> Production (Read-only)
            </span>
            <?php else: ?>
            <span style="font-size: 12px; background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-weight: 500;">
                <i class="fa fa-wrench"></i> Development
            </span>
            <?php endif; ?>
        </h1>
        <div class="monitor-actions">
            <button class="btn-refresh" onclick="location.reload()">
                <i class="fa fa-refresh"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon docker">
                    <i class="fa fa-cube"></i>
                </div>
            </div>
            <div class="stat-card-value"><?=$dockerInfo['version'] ?? 'N/A'?></div>
            <div class="stat-card-label">Docker Version</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon running">
                    <i class="fa fa-play-circle"></i>
                </div>
            </div>
            <div class="stat-card-value"><?=$running_count?></div>
            <div class="stat-card-label">Running Containers</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon stopped">
                    <i class="fa fa-stop-circle"></i>
                </div>
            </div>
            <div class="stat-card-value"><?=$stopped_count?></div>
            <div class="stat-card-label">Stopped Containers</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon images">
                    <i class="fa fa-clone"></i>
                </div>
            </div>
            <div class="stat-card-value"><?=$dockerInfo['images'] ?? 'N/A'?></div>
            <div class="stat-card-label">Images</div>
        </div>
    </div>

    <!-- Containers -->
    <?php if (isset($containers['error'])): ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <div class="empty-title">Docker Not Accessible</div>
        <div class="empty-text" style="max-width: 600px; margin: 0 auto;">
            <p><?=htmlspecialchars($containers['error'])?></p>
            <div style="text-align: left; background: #f9fafb; border-radius: 12px; padding: 20px; margin-top: 20px;">
                <strong style="color: #374151;">To enable container monitoring, add this to your <code>docker-compose.yml</code>:</strong>
                <pre style="background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 8px; margin-top: 12px; overflow-x: auto; font-size: 13px;">services:
  php:
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock</pre>
                <p style="margin-top: 12px; font-size: 13px; color: #6b7280;">
                    <i class="fa fa-info-circle"></i> After updating, run: <code>docker-compose down && docker-compose up -d</code>
                </p>
            </div>
        </div>
    </div>
    <?php elseif (empty($containers)): ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fa fa-inbox"></i>
        </div>
        <div class="empty-title">No Containers Found</div>
        <div class="empty-text">There are no Docker containers on this system.</div>
    </div>
    <?php else: ?>
    <div class="containers-grid">
        <?php foreach ($containers as $container): 
            $state = $container['state'];
            $isRunning = ($state === 'running');
        ?>
        <div class="container-card" data-container="<?=htmlspecialchars($container['name'])?>">
            <div class="container-card-header">
                <div class="container-info">
                    <div class="container-icon <?=$state?>">
                        <i class="fa fa-<?=$isRunning ? 'check' : 'power-off'?>"></i>
                    </div>
                    <div>
                        <div class="container-name"><?=htmlspecialchars($container['name'])?></div>
                        <div class="container-image">
                            <i class="fa fa-circle"></i>
                            <?=htmlspecialchars($container['image'])?>
                        </div>
                    </div>
                </div>
                <div class="container-status">
                    <span class="status-badge <?=$state?>"><?=$state?></span>
                </div>
            </div>
            
            <div class="container-card-body">
                <?php if ($isRunning && isset($container['cpu'])): ?>
                <div class="container-stats">
                    <div class="container-stat">
                        <div class="container-stat-value"><?=htmlspecialchars($container['cpu'])?></div>
                        <div class="container-stat-label">CPU</div>
                    </div>
                    <div class="container-stat">
                        <div class="container-stat-value"><?=htmlspecialchars($container['mem_perc'])?></div>
                        <div class="container-stat-label">Memory</div>
                    </div>
                    <div class="container-stat">
                        <div class="container-stat-value"><?=htmlspecialchars(explode(' / ', $container['mem_usage'])[0] ?? '--')?></div>
                        <div class="container-stat-label">Mem Used</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="container-stats">
                    <div class="container-stat">
                        <div class="container-stat-value">--</div>
                        <div class="container-stat-label">CPU</div>
                    </div>
                    <div class="container-stat">
                        <div class="container-stat-value">--</div>
                        <div class="container-stat-label">Memory</div>
                    </div>
                    <div class="container-stat">
                        <div class="container-stat-value">--</div>
                        <div class="container-stat-label">Mem Used</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($container['ports'])): ?>
                <div class="container-ports">
                    <div class="container-ports-label">Ports</div>
                    <div class="port-badges">
                        <?php 
                        $ports = explode(', ', $container['ports']);
                        foreach (array_slice($ports, 0, 4) as $port): 
                            // Simplify port display
                            $port = preg_replace('/0\.0\.0\.0:/', '', $port);
                        ?>
                        <span class="port-badge"><?=htmlspecialchars($port)?></span>
                        <?php endforeach; ?>
                        <?php if (count($ports) > 4): ?>
                        <span class="port-badge">+<?=count($ports) - 4?> more</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="font-size: 12px; color: #9ca3af;">
                    <i class="fa fa-clock-o"></i> <?=htmlspecialchars($container['status'])?>
                </div>
            </div>
            
            <div class="container-card-footer">
                <?php if (!$is_production): ?>
                <button class="container-btn start" onclick="containerAction('<?=$container['name']?>', 'start')" <?=$isRunning ? 'disabled' : ''?>>
                    <i class="fa fa-play"></i> Start
                </button>
                <button class="container-btn stop" onclick="containerAction('<?=$container['name']?>', 'stop')" <?=!$isRunning ? 'disabled' : ''?>>
                    <i class="fa fa-stop"></i> Stop
                </button>
                <button class="container-btn restart" onclick="containerAction('<?=$container['name']?>', 'restart')" <?=!$isRunning ? 'disabled' : ''?>>
                    <i class="fa fa-refresh"></i> Restart
                </button>
                <?php else: ?>
                <span class="container-btn" style="background: #fef3c7; color: #92400e; flex: 3; cursor: default;">
                    <i class="fa fa-lock"></i> Read-only mode (Production)
                </span>
                <?php endif; ?>
                <button class="container-btn logs" onclick="showLogs('<?=$container['name']?>')">
                    <i class="fa fa-file-text-o"></i> Logs
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div><!-- End content-container -->
</div>

<!-- Logs Modal -->
<div class="logs-modal" id="logsModal">
    <div class="logs-modal-content">
        <div class="logs-modal-header">
            <div class="logs-modal-title">
                <i class="fa fa-file-text-o"></i>
                <span id="logsContainerName">Container Logs</span>
            </div>
            <button class="logs-modal-close" onclick="closeLogs()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="logs-modal-body">
            <pre class="logs-content" id="logsContent">Loading...</pre>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
function containerAction(container, action) {
    const btn = event.target.closest('.container-btn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span>';
    btn.disabled = true;
    
    fetch(`?page=containers&action=${action}&container=${encodeURIComponent(container)}`)
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                setTimeout(() => location.reload(), 1000);
            } else {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(err => {
            showToast('Request failed', 'error');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
}

function showLogs(container) {
    document.getElementById('logsModal').classList.add('show');
    document.getElementById('logsContainerName').textContent = container + ' Logs';
    document.getElementById('logsContent').textContent = 'Loading...';
    
    fetch(`?page=containers&logs=1&container=${encodeURIComponent(container)}&lines=100`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('logsContent').textContent = data.logs || 'No logs available';
        })
        .catch(err => {
            document.getElementById('logsContent').textContent = 'Failed to load logs';
        });
}

function closeLogs() {
    document.getElementById('logsModal').classList.remove('show');
}

function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type + ' show';
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Close modal on backdrop click
document.getElementById('logsModal').addEventListener('click', function(e) {
    if (e.target === this) closeLogs();
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLogs();
});

// Remove skeleton loading after content is ready
// Use requestAnimationFrame to ensure the browser has rendered the skeleton first
(function() {
    // Wait for next paint cycle, then add delay for smooth UX
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            setTimeout(function() {
                const monitor = document.getElementById('containerMonitor');
                if (monitor) {
                    monitor.classList.remove('skeleton-loading');
                }
            }, 600); // 600ms to make skeleton visible
        });
    });
})();
</script>
