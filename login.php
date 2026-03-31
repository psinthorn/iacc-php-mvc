<?php
// Error reporting settings
ini_set('display_errors', 0); // Never show errors to users
ini_set('log_errors', 1);     // Enable error logging
ini_set('display_startup_errors', 0);
ini_set('error_log', __DIR__ . '/logs/php-error.log'); // Log file path
error_reporting(E_ALL);       // Report all errors
/**
 * iAcc Login Page
 * Modern login with responsive design
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/security.php");

// Check if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

// Language handling
$lang = $_GET['lang'] ?? $_SESSION['landing_lang'] ?? 'en';
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;
$t = require __DIR__ . '/inc/lang/' . $lang . '.php';
function __($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

// Get error message if any
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?> - iACC</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --primary-light: #a569bd;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        /* Left Panel - Branding */
        .login-branding {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10%, 10%); }
        }
        
        .branding-content {
            position: relative;
            z-index: 1;
            color: white;
        }
        
        .branding-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }
        
        .branding-logo-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }
        
        .branding-logo-text {
            font-size: 28px;
            font-weight: 700;
        }
        
        .branding-content h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .branding-content p {
            font-size: 1.05rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .branding-features {
            list-style: none;
        }
        
        .branding-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .branding-features li i {
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        /* Right Panel - Form */
        .login-form-panel {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-header {
            margin-bottom: 35px;
        }
        
        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .form-header p {
            color: var(--gray-600);
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-600);
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--gray-100);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(142, 68, 173, 0.1);
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            font-size: 16px;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .remember-me input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .remember-me span {
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(142, 68, 173, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: var(--gray-600);
            font-size: 0.85rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .back-to-home {
            text-align: center;
        }
        
        .back-to-home a {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .back-to-home a:hover {
            color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
            }
            
            .login-branding {
                display: none;
            }
            
            .login-form-panel {
                padding: 40px 30px;
            }
            
            .form-header {
                text-align: center;
            }
            
            .mobile-logo {
                display: flex;
                justify-content: center;
                margin-bottom: 30px;
            }
            
            .mobile-logo .branding-logo-icon {
                background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                color: white;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-logo {
                display: none;
            }
        }
        
        /* Loading state */
        .btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn.loading .btn-text {
            display: none;
        }
        
        .btn .spinner {
            display: none;
        }
        
        .btn.loading .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Branding -->
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-logo">
                    <div class="branding-logo-text">iACC</div>
                </div>
                
                <h1><?= __('login_welcome') ?></h1>
                <p><?= __('login_description') ?></p>
                
                <ul class="branding-features">
                    <li>
                        <i class="fa fa-check"></i>
                        <span><?= __('login_feature_1') ?></span>
                    </li>
                    <li>
                        <i class="fa fa-check"></i>
                        <span><?= __('login_feature_2') ?></span>
                    </li>
                    <li>
                        <i class="fa fa-check"></i>
                        <span><?= __('login_feature_3') ?></span>
                    </li>
                    <li>
                        <i class="fa fa-check"></i>
                        <span><?= __('login_feature_4') ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Right Panel - Login Form -->
        <div class="login-form-panel">
            <div class="mobile-logo">
                <div class="branding-logo">
                    <div class="branding-logo-text" style="color: var(--primary);">iACC</div>
                </div>
            </div>
            
            <div class="form-header">
                <h2><?= __('login_title') ?></h2>
                <p><?= __('login_subtitle') ?></p>
            </div>
            
            <?php if ($error === 'invalid'): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i>
                <span><?= __('login_error_invalid') ?></span>
            </div>
            <?php elseif ($error === 'locked'): ?>
            <div class="alert alert-danger">
                <i class="fa fa-lock"></i>
                <span><?= __('login_error_locked') ?></span>
            </div>
            <?php elseif ($error === 'session'): ?>
            <div class="alert alert-danger">
                <i class="fa fa-clock-o"></i>
                <span><?= __('login_error_session') ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success === 'reset'): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                <span><?= __('login_success_reset') ?></span>
            </div>
            <?php endif; ?>
            
            <form action="index.php?page=authorize" method="post" id="loginForm">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="email"><?= __('login_email') ?></label>
                    <div class="input-group">
                        <i class="fa fa-envelope"></i>
                        <input type="email" 
                               id="email" 
                               name="m_user" 
                               class="form-control" 
                               placeholder="<?= __('login_ph_email') ?>" 
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password"><?= __('login_password') ?></label>
                    <div class="input-group">
                        <i class="fa fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="m_pass" 
                               class="form-control" 
                               placeholder="<?= __('login_ph_password') ?>" 
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        <span><?= __('login_remember') ?></span>
                    </label>
                    <a href="index.php?page=forgot_password" class="forgot-link"><?= __('login_forgot') ?></a>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span class="btn-text"><?= __('login_button') ?></span>
                    <span class="spinner"></span>
                </button>
            </form>
            
            <div class="divider">
                <span><?= __('login_or') ?></span>
            </div>
            
            <div class="back-to-home" style="text-align:center;">
                <p style="margin-bottom:10px;font-size:0.9rem;color:#6c757d;">
                    <?= __('login_no_account') ?> 
                    <a href="index.php?page=register" style="color:#8e44ad;font-weight:600;text-decoration:none;"><?= __('login_signup_free') ?></a>
                </p>
                <a href="landing.php">
                    <i class="fa fa-arrow-left"></i> <?= __('login_back_home') ?>
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Form submit loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
        });
        
        // Reset button state on page load (handles browser back/forward cache)
        window.addEventListener('pageshow', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.remove('loading');
        });
    </script>
</body>
</html>
