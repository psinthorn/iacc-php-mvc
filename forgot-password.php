<?php
// Error reporting settings
ini_set('display_errors', 1); // Show errors in browser for debug
ini_set('log_errors', 1);     // Enable error logging
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log'); // Log file path
error_reporting(E_ALL);       // Report all errors
/**
 * Forgot Password Page
 * Sends password reset email to user
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

$db = new DbConn($config);
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } else {
            // Generate reset token
            $token = generate_password_reset_token($db->conn, $email);
            
            // Always show success message (don't reveal if email exists)
            $message = 'If an account with that email exists, a password reset link has been sent.';
            $messageType = 'success';
            
            if ($token) {
                // Build reset URL
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $resetUrl = "{$protocol}://{$host}/reset-password.php?token={$token}";
                
                // In production, send email here
                // For now, we'll display the link (remove in production!)
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $message .= "<br><br><strong>Debug:</strong> <a href='{$resetUrl}'>Reset Link</a>";
                }
                
                // TODO: Implement actual email sending
                // mail($email, 'Password Reset - iAcc', "Click here to reset your password: {$resetUrl}");
                
                // Log the reset request
                error_log("Password reset requested for {$email}. Token generated.");
            }
        }
    }
    csrf_regenerate();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - iACC</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/sb-admin.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default" style="margin-top: 100px;">
                    <div class="panel-heading">
                        <h3 class="panel-title">Forgot Password</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                        
                        <form role="form" method="post">
                            <?= csrf_field() ?>
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Email Address" name="email" type="email" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-lg btn-primary btn-block">Send Reset Link</button>
                            </fieldset>
                        </form>
                        
                        <hr>
                        <p class="text-center">
                            <a href="login.php">Back to Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
