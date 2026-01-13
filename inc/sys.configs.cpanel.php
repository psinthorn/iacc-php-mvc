<?PHP
/**
 * iACC - cPanel Production Configuration
 * 
 * INSTRUCTIONS:
 * 1. Upload all files to your cPanel public_html directory
 * 2. Rename this file to sys.configs.php (replace the existing one)
 * 3. Update database credentials below
 * 4. Import iacc_07012026.sql to your MySQL database
 * 
 * @version 4.8
 * @updated January 2026
 */

// Load error handler first to suppress deprecated PHP warnings
require_once(dirname(__FILE__) . "/error-handler.php");

// ============================================================================
// SESSION SECURITY SETTINGS (Production)
// Configure these BEFORE session_start() is called
// ============================================================================
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
ini_set('session.use_strict_mode', 1);      // Reject uninitialized session IDs
ini_set('session.cookie_samesite', 'Lax');  // Prevent CSRF via cross-site requests
ini_set('session.cookie_secure', 1);        // HTTPS only (enable for production with SSL)

// ============================================================================
// PRODUCTION ERROR HANDLING
// ============================================================================
ini_set('display_errors', 0);               // Never show errors to users
ini_set('log_errors', 1);                   // Log all errors
ini_set('error_log', dirname(__FILE__) . '/../logs/php_errors.log');

// ============================================================================
// SERVER : MYSQL Configuration (cPanel)
// ============================================================================
// IMPORTANT: Update these values with your cPanel MySQL credentials
// Format: cpanelusername_databasename
// ============================================================================
$config["hostname"] = "localhost";
$config["username"] = "YOUR_CPANEL_USERNAME_dbuser";    // e.g., theiconn_iaccuser
$config["password"] = "YOUR_DATABASE_PASSWORD";          // Your MySQL password
$config["dbname"]   = "YOUR_CPANEL_USERNAME_dbname";    // e.g., theiconn_iacc

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set("Asia/Bangkok");

// ============================================================================
// DOCKER TOOLS CONFIGURATION (Disabled for cPanel)
// ============================================================================
$config["docker_tools"] = "off";         // Disable Docker tools on cPanel
$config["container_manager"] = "off";    // Disable Container Manager on cPanel

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
 * Check if Docker tools should be enabled
 * @return bool
 */
function is_docker_tools_enabled() {
    $setting = get_docker_tools_setting('docker_tools');
    
    if ($setting === "on") return true;
    if ($setting === "off") return false;
    
    return is_running_in_docker();
}

/**
 * Check if Container Manager should be enabled
 * @return bool
 */
function is_container_manager_enabled() {
    $setting = get_docker_tools_setting('container_manager');
    
    if ($setting === "on") return true;
    if ($setting === "off") return false;
    
    return is_running_in_docker();
}

/**
 * Detect if running inside Docker (always false on cPanel)
 * @return bool
 */
function is_running_in_docker() {
    // cPanel is never Docker
    return false;
}

/**
 * Get Docker tools status info for display
 * @return array
 */
function get_docker_tools_status() {
    return [
        'setting' => 'off',
        'is_docker_environment' => false,
        'is_enabled' => false,
        'status_text' => 'Disabled',
        'mode_text' => 'cPanel Production'
    ];
}
