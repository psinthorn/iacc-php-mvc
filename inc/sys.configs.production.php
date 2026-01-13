<?PHP
/**
 * iACC - Production Configuration for iacc.f2.co.th
 * 
 * ENVIRONMENT: Production
 * DOMAIN: iacc.f2.co.th
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to inc/sys.configs.php
 * 2. Update database credentials below
 * 3. Ensure SSL is enabled on your domain
 * 
 * @version 4.8
 * @environment production
 */

// Load error handler first
require_once(dirname(__FILE__) . "/error-handler.php");

// ============================================================================
// ENVIRONMENT IDENTIFIER
// ============================================================================
define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('APP_URL', 'https://iacc.f2.co.th');

// ============================================================================
// SESSION SECURITY SETTINGS (Production - HTTPS Required)
// ============================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 1);        // HTTPS only

// ============================================================================
// PRODUCTION ERROR HANDLING
// ============================================================================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../logs/php_errors.log');

// ============================================================================
// DATABASE CONFIGURATION - PRODUCTION
// ============================================================================
// cPanel format: cpanelusername_databasename
// ============================================================================
$config["hostname"] = "localhost";
$config["username"] = "CPANEL_USERNAME_dbuser";     // e.g., f2coth_iaccuser
$config["password"] = "YOUR_DATABASE_PASSWORD";      // Strong password
$config["dbname"]   = "CPANEL_USERNAME_iacc";       // e.g., f2coth_iacc

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set("Asia/Bangkok");

// ============================================================================
// DOCKER TOOLS (Disabled in Production)
// ============================================================================
$config["docker_tools"] = "off";
$config["container_manager"] = "off";

define('DOCKER_SETTINGS_FILE', dirname(__FILE__) . '/docker-settings.json');

function get_docker_tools_setting($key = 'docker_tools') {
    return 'off';
}

function save_docker_tools_setting($setting, $key = 'docker_tools') {
    return false; // Disabled in production
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
        'mode_text' => 'Production (iacc.f2.co.th)'
    ];
}
