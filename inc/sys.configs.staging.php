<?PHP
/**
 * iACC - Staging Configuration for dev.iacc.f2.co.th
 * 
 * ENVIRONMENT: Staging/Development
 * DOMAIN: dev.iacc.f2.co.th
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to inc/sys.configs.php on staging server
 * 2. Update database credentials below (use staging database!)
 * 
 * @version 4.8
 * @environment staging
 */

// Load error handler first
require_once(dirname(__FILE__) . "/error-handler.php");

// ============================================================================
// ENVIRONMENT IDENTIFIER
// ============================================================================
define('APP_ENV', 'staging');
define('APP_DEBUG', true);  // Enable debug in staging
define('APP_URL', 'https://dev.iacc.f2.co.th');

// ============================================================================
// SESSION SECURITY SETTINGS
// ============================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 1);        // HTTPS only

// ============================================================================
// STAGING ERROR HANDLING (Show errors for debugging)
// ============================================================================
ini_set('display_errors', 1);               // Show errors in staging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../logs/php_errors.log');

// ============================================================================
// DATABASE CONFIGURATION - STAGING
// ============================================================================
// Use a SEPARATE database from production!
// ============================================================================
$config["hostname"] = "localhost";
$config["username"] = "CPANEL_USERNAME_devuser";     // e.g., f2coth_iaccdev
$config["password"] = "YOUR_STAGING_DB_PASSWORD";    // Different from production
$config["dbname"]   = "CPANEL_USERNAME_iacc_dev";    // e.g., f2coth_iacc_dev

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set("Asia/Bangkok");

// ============================================================================
// DOCKER TOOLS (Disabled - not Docker environment)
// ============================================================================
$config["docker_tools"] = "off";
$config["container_manager"] = "off";

define('DOCKER_SETTINGS_FILE', dirname(__FILE__) . '/docker-settings.json');

function get_docker_tools_setting($key = 'docker_tools') {
    return 'off';
}

function save_docker_tools_setting($setting, $key = 'docker_tools') {
    return false;
}

function is_docker_tools_enabled() {
    return false;
}

function is_container_manager_enabled() {
    return false;
}

function is_running_in_docker() {
    return false;
}

function get_docker_tools_status() {
    return [
        'setting' => 'off',
        'is_docker_environment' => false,
        'is_enabled' => false,
        'status_text' => 'Disabled',
        'mode_text' => 'Staging (dev.iacc.f2.co.th)'
    ];
}
