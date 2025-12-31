<?php 
/**
 * User Authentication Handler - Phase 1 Security Implementation
 * 
 * Features:
 * - Bcrypt password verification with MD5 migration support
 * - CSRF token validation
 * - Session regeneration after login
 * - Failed login attempt tracking
 * - Account lockout after N failed attempts
 * - Session timeout enforcement
 * 
 * Part of iACC Phase 1: Security Hardening
 * Last Updated: December 31, 2025
 */

error_reporting(E_ALL & ~E_NOTICE);
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.security.php");

$db = new DbConn($config);

// Check if user is already logged in
if (!empty($_SESSION['usr_id'])) {
    // User attempting logout
    logActivity($_SESSION['usr_id'], 'LOGOUT', 'User logged out');
    session_destroy();
    echo "<script>alert('Logout Success');window.location='login.php';</script>";
    exit;
}

// Verify CSRF token on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!SecurityHelper::validateCsrfToken()) {
        logActivity(null, 'CSRF_FAILURE', 'CSRF token validation failed for: ' . $_POST['m_user']);
        exit("<script>alert('SECURITY ERROR: Invalid form submission. Please try again.');history.back();</script>");
    }
}

// Get login credentials
$username = isset($_POST['m_user']) ? trim($_POST['m_user']) : '';
$password = isset($_POST['m_pass']) ? $_POST['m_pass'] : '';

// Validate input
if (empty($username) || empty($password)) {
    exit("<script>alert('Username and password are required');history.back();</script>");
}

// Sanitize username for logging (don't sanitize for query - use prepared statements)
$username_safe = SecurityHelper::sanitizeInput($username);

// Query user account
$query = $db->conn->prepare("
    SELECT usr_id, usr_name, usr_pass, password_algorithm, password_requires_reset, 
           account_locked_until, failed_login_attempts, level, lang 
    FROM authorize 
    WHERE usr_name = ?
");

if (!$query) {
    logActivity(null, 'LOGIN_ERROR', 'Database prepare error: ' . $db->conn->error);
    exit("<script>alert('Login error. Please try again later.');history.back();</script>");
}

$query->bind_param("s", $username);

if (!$query->execute()) {
    logActivity(null, 'LOGIN_ERROR', 'Database execute error: ' . $db->conn->error);
    exit("<script>alert('Login error. Please try again later.');history.back();</script>");
}

$result = $query->get_result();

if ($result->num_rows !== 1) {
    // User not found - still increase counter for the username (prevent user enumeration)
    logActivity(null, 'LOGIN_FAILURE', 'Invalid credentials for user: ' . $username_safe);
    recordFailedLoginAttempt($username);
    exit("<script>alert('Invalid username or password');history.back();</script>");
}

$user = $result->fetch_assoc();
$query->close();

// Check if account is locked
if (!empty($user['account_locked_until'])) {
    $lockUntil = strtotime($user['account_locked_until']);
    $now = time();
    
    if ($now < $lockUntil) {
        $minutesRemaining = ceil(($lockUntil - $now) / 60);
        logActivity($user['usr_id'], 'LOGIN_BLOCKED', "Account locked. {$minutesRemaining} minutes remaining");
        exit("<script>alert('Account temporarily locked. Try again in {$minutesRemaining} minutes.');history.back();</script>");
    } else {
        // Unlock the account
        $unlockQuery = $db->conn->prepare("
            UPDATE authorize 
            SET account_locked_until = NULL, failed_login_attempts = 0 
            WHERE usr_id = ?
        ");
        $unlockQuery->bind_param("i", $user['usr_id']);
        $unlockQuery->execute();
        $unlockQuery->close();
        
        // Reset counters for this attempt
        $user['failed_login_attempts'] = 0;
        $user['account_locked_until'] = null;
    }
}

// Verify password (supports both bcrypt and MD5 with automatic migration)
$passwordValid = false;
$passwordAlgorithm = $user['password_algorithm'] ?? 'md5';

if ($passwordAlgorithm === 'bcrypt') {
    // Use bcrypt verification
    $passwordValid = SecurityHelper::verifyPassword($password, $user['usr_pass']);
} else if ($passwordAlgorithm === 'md5' || empty($passwordAlgorithm)) {
    // Legacy MD5 verification
    $passwordValid = (md5($password) === $user['usr_pass']);
    
    if ($passwordValid) {
        // Password is valid, but migrate to bcrypt on successful login
        $newHash = SecurityHelper::hashPassword($password);
        $migrateQuery = $db->conn->prepare("
            UPDATE authorize 
            SET usr_pass = ?, 
                password_algorithm = 'bcrypt', 
                password_hash_cost = 12,
                password_last_changed = NOW(),
                password_requires_reset = 0
            WHERE usr_id = ?
        ");
        $migrateQuery->bind_param("si", $newHash, $user['usr_id']);
        
        if ($migrateQuery->execute()) {
            logActivity($user['usr_id'], 'PASSWORD_MIGRATED', 'Password migrated from MD5 to bcrypt');
        }
        $migrateQuery->close();
    }
}

// Invalid password
if (!$passwordValid) {
    $newFailureCount = $user['failed_login_attempts'] + 1;
    $maxAttempts = 5;
    $lockoutMinutes = 15;
    
    if ($newFailureCount >= $maxAttempts) {
        // Lock account
        $lockUntil = date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60));
        $updateQuery = $db->conn->prepare("
            UPDATE authorize 
            SET failed_login_attempts = ?, account_locked_until = ? 
            WHERE usr_id = ?
        ");
        $updateQuery->bind_param("isi", $newFailureCount, $lockUntil, $user['usr_id']);
        $updateQuery->execute();
        $updateQuery->close();
        
        logActivity($user['usr_id'], 'ACCOUNT_LOCKED', "Account locked due to {$maxAttempts} failed attempts");
        exit("<script>alert('Too many failed attempts. Account locked for {$lockoutMinutes} minutes.');history.back();</script>");
    } else {
        // Record failed attempt
        $updateQuery = $db->conn->prepare("
            UPDATE authorize 
            SET failed_login_attempts = ? 
            WHERE usr_id = ?
        ");
        $updateQuery->bind_param("ii", $newFailureCount, $user['usr_id']);
        $updateQuery->execute();
        $updateQuery->close();
        
        logActivity($user['usr_id'], 'LOGIN_FAILURE', "Failed login attempt ({$newFailureCount}/{$maxAttempts})");
        exit("<script>alert('Invalid username or password');history.back();</script>");
    }
}

// Password is valid - successful login
// Reset failed login attempts
$resetQuery = $db->conn->prepare("
    UPDATE authorize 
    SET failed_login_attempts = 0, 
        account_locked_until = NULL,
        last_login = NOW()
    WHERE usr_id = ?
");
$resetQuery->bind_param("i", $user['usr_id']);
$resetQuery->execute();
$resetQuery->close();

// Check if password reset is required
if (!empty($user['password_requires_reset'])) {
    $_SESSION['usr_id'] = $user['usr_id'];
    $_SESSION['usr_name'] = $user['usr_name'];
    $_SESSION['require_password_reset'] = true;
    
    logActivity($user['usr_id'], 'LOGIN_SUCCESS', 'Login successful - password reset required');
    echo "<script>window.location='change-password.php';</script>";
    exit;
}

// Regenerate session ID after successful login (prevent session fixation attacks)
session_regenerate_id(true);

// Set session variables
$_SESSION['usr_name'] = $user['usr_name'];
$_SESSION['usr_id'] = $user['usr_id'];
$_SESSION['lang'] = $user['lang'] ?? 'en';
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = SecurityHelper::generateCsrfToken();

logActivity($user['usr_id'], 'LOGIN_SUCCESS', 'User logged in successfully');

echo "<script>window.location='index.php?page=dashboard';</script>";
exit;

/**
 * Helper Functions
 */

/**
 * Record failed login attempt
 * 
 * @param string $username Username that failed
 */
function recordFailedLoginAttempt($username) {
    global $db;
    
    // Try to find user by username first
    $query = $db->conn->prepare("SELECT usr_id FROM authorize WHERE usr_name = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $newFailureCount = 1; // Will be incremented by proper login handler
        
        $updateQuery = $db->conn->prepare("
            UPDATE authorize 
            SET failed_login_attempts = failed_login_attempts + 1 
            WHERE usr_id = ?
        ");
        $updateQuery->bind_param("i", $user['usr_id']);
        $updateQuery->execute();
        $updateQuery->close();
    }
    $query->close();
}

/**
 * Log authentication activity
 * 
 * @param int|null $userId User ID (null for non-authenticated events)
 * @param string $action Action type (LOGIN_SUCCESS, LOGIN_FAILURE, etc.)
 * @param string $details Additional details
 */
function logActivity($userId, $action, $details) {
    global $db;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    
    // Create audit log table if it doesn't exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS `auth_activity_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `usr_id` INT,
            `action` VARCHAR(50),
            `ip_address` VARCHAR(45),
            `user_agent` VARCHAR(500),
            `details` TEXT,
            `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_usr_id` (`usr_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_timestamp` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    @$db->conn->query($createTableSql);
    
    // Insert log entry
    $sql = "
        INSERT INTO auth_activity_log (usr_id, action, ip_address, user_agent, details)
        VALUES (?, ?, ?, ?, ?)
    ";
    
    $stmt = $db->conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issss", $userId, $action, $ipAddress, $userAgent, $details);
        @$stmt->execute();
        $stmt->close();
    }
}
?>
