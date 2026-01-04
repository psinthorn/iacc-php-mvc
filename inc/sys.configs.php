<?PHP
// Load error handler first to suppress deprecated PHP warnings
require_once(dirname(__FILE__) . "/error-handler.php");

// ============================================================================
// SESSION SECURITY SETTINGS
// Configure these BEFORE session_start() is called
// ============================================================================
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
ini_set('session.use_strict_mode', 1);      // Reject uninitialized session IDs
ini_set('session.cookie_samesite', 'Lax');  // Prevent CSRF via cross-site requests
// ini_set('session.cookie_secure', 1);     // Uncomment when using HTTPS

// ============================================================================
// SERVER : MYSQL Configuration
// ============================================================================
$config["hostname"] = "mysql";
$config["username"] = "root";
//$config["username"] = "theiconn_cms";
$config["password"] = "root";
// $config["dbname"]   = "root";
$config["dbname"]   = "iacc";

// Sets the default timezone
date_default_timezone_set("Asia/Bangkok"); 

// SERVER : MYSQL Cnfiguration
//$config["hostname"] = "localhost";
//$config["username"] = "root";
//$config["username"] = "theiconn_cms";
//$config["password"] = ")q#gLfESG;M(";
//$config["dbname"]   = "ngt-admin";
//$config["dbname"]   = "theiconn_cms";

// ============================================================================
// DOCKER TOOLS CONFIGURATION
// ============================================================================
// Two separate settings:
// 1. docker_tools: For Docker Test & Container Debug (read-only debug tools)
//    Default: 'auto' - auto-detect Docker environment
// 2. container_manager: For Container Manager (has start/stop/restart actions)
//    Default: 'off' - requires manual enable for safety

// Options: 'auto' | 'on' | 'off'
// - 'auto': Auto-detect if running in Docker container
// - 'on':   Always enable (force)
// - 'off':  Always disable
$config["docker_tools"] = "auto";        // Docker Test, Container Debug
$config["container_manager"] = "off";    // Container Manager (default OFF for safety)

// Runtime settings file path
define('DOCKER_SETTINGS_FILE', dirname(__FILE__) . '/docker-settings.json');

/**
 * Get Docker tools setting (from file or default config)
 * @param string $key 'docker_tools' or 'container_manager'
 * @return string 'auto' | 'on' | 'off'
 */
function get_docker_tools_setting($key = 'docker_tools') {
    global $config;
    
    // Check for runtime settings file first
    if (file_exists(DOCKER_SETTINGS_FILE)) {
        $settings = json_decode(file_get_contents(DOCKER_SETTINGS_FILE), true);
        if (isset($settings[$key]) && in_array($settings[$key], ['auto', 'on', 'off'])) {
            return $settings[$key];
        }
    }
    
    // Fall back to config default
    $default = ($key === 'container_manager') ? 'off' : 'auto';
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Save Docker tools setting to file
 * @param string $setting 'auto' | 'on' | 'off'
 * @param string $key 'docker_tools' or 'container_manager'
 * @return bool
 */
function save_docker_tools_setting($setting, $key = 'docker_tools') {
    if (!in_array($setting, ['auto', 'on', 'off'])) {
        return false;
    }
    if (!in_array($key, ['docker_tools', 'container_manager'])) {
        return false;
    }
    
    $settings = [];
    if (file_exists(DOCKER_SETTINGS_FILE)) {
        $settings = json_decode(file_get_contents(DOCKER_SETTINGS_FILE), true) ?: [];
    }
    
    $settings[$key] = $setting;
    $settings['updated_at'] = date('Y-m-d H:i:s');
    
    return file_put_contents(DOCKER_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Check if Docker tools should be enabled (for Docker Test & Container Debug)
 * @return bool
 */
function is_docker_tools_enabled() {
    $setting = get_docker_tools_setting('docker_tools');
    
    // Manual override
    if ($setting === "on") {
        return true;
    }
    if ($setting === "off") {
        return false;
    }
    
    // Auto-detect: Check if running in Docker container
    return is_running_in_docker();
}

/**
 * Check if Container Manager should be enabled
 * @return bool
 */
function is_container_manager_enabled() {
    $setting = get_docker_tools_setting('container_manager');
    
    // Manual override
    if ($setting === "on") {
        return true;
    }
    if ($setting === "off") {
        return false;
    }
    
    // Auto-detect: Check if running in Docker container
    return is_running_in_docker();
}

/**
 * Detect if the application is running inside a Docker container
 * @return bool
 */
function is_running_in_docker() {
    // Method 1: Check for /.dockerenv file (Docker creates this)
    if (file_exists('/.dockerenv')) {
        return true;
    }
    
    // Method 2: Check cgroup for docker/container references
    if (file_exists('/proc/1/cgroup')) {
        $cgroup = @file_get_contents('/proc/1/cgroup');
        if ($cgroup && (
            strpos($cgroup, 'docker') !== false ||
            strpos($cgroup, 'kubepods') !== false ||
            strpos($cgroup, 'containerd') !== false
        )) {
            return true;
        }
    }
    
    // Method 3: Check for container environment variables
    if (getenv('DOCKER_CONTAINER') || getenv('KUBERNETES_SERVICE_HOST')) {
        return true;
    }
    
    // Method 4: Check Docker socket exists (might be mounted)
    if (file_exists('/var/run/docker.sock')) {
        return true;
    }
    
    return false;
}

/**
 * Get Docker tools status info for display
 * @return array
 */
function get_docker_tools_status() {
    $setting = get_docker_tools_setting();
    $is_docker = is_running_in_docker();
    $is_enabled = is_docker_tools_enabled();
    
    return [
        'setting' => $setting,
        'is_docker_environment' => $is_docker,
        'is_enabled' => $is_enabled,
        'status_text' => $is_enabled ? 'Enabled' : 'Disabled',
        'mode_text' => $setting === 'auto' 
            ? ($is_docker ? 'Auto (Docker detected)' : 'Auto (No Docker)') 
            : ucfirst($setting)
    ];
}

