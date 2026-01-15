<?php
/**
 * Reset Password Page
 * Allows user to set new password using reset token
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

$db = new DbConn($config);
$message = '';
$messageType = '';
$validToken = false;
$email = '';

// Get token from URL
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($token) {
    $email = verify_password_reset_token($db->conn, $token);
    $validToken = ($email !== false);
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $messageType = 'danger';
        } elseif ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'danger';
        } else {
            // Reset password
            if (reset_password_with_token($db->conn, $token, $password)) {
                $message = 'Password has been reset successfully! You can now login.';
                $messageType = 'success';
                $validToken = false; // Hide form after success
            } else {
                $message = 'Failed to reset password. Please try again.';
                $messageType = 'danger';
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
    <title>Reset Password - iACC</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/sb-admin.css" rel="stylesheet">
    <style>
        .password-strength { margin-top: 5px; font-size: 12px; }
        .strength-weak { color: #d9534f; }
        .strength-medium { color: #f0ad4e; }
        .strength-strong { color: #5cb85c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default" style="margin-top: 100px;">
                    <div class="panel-heading">
                        <h3 class="panel-title">Reset Password</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($validToken): ?>
                            <p class="text-muted">Resetting password for: <strong><?= e($email) ?></strong></p>
                            
                            <form role="form" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="token" value="<?= e($token) ?>">
                                
                                <fieldset>
                                    <div class="form-group">
                                        <input class="form-control" placeholder="New Password" name="password" type="password" id="password" required minlength="8" autofocus>
                                        <div class="password-strength" id="strength"></div>
                                    </div>
                                    <div class="form-group">
                                        <input class="form-control" placeholder="Confirm Password" name="confirm_password" type="password" required minlength="8">
                                    </div>
                                    <button type="submit" class="btn btn-lg btn-primary btn-block">Reset Password</button>
                                </fieldset>
                            </form>
                        <?php elseif (!$message): ?>
                            <div class="alert alert-danger">
                                Invalid or expired reset link. Please request a new one.
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <p class="text-center">
                            <a href="login.php">Back to Login</a>
                            <?php if (!$validToken && $messageType !== 'success'): ?>
                                | <a href="forgot-password.php">Request New Link</a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
    // Simple password strength indicator
    document.getElementById('password')?.addEventListener('input', function() {
        var password = this.value;
        var strength = document.getElementById('strength');
        var score = 0;
        
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        if (password.length === 0) {
            strength.innerHTML = '';
        } else if (score < 2) {
            strength.innerHTML = '<span class="strength-weak">Weak password</span>';
        } else if (score < 4) {
            strength.innerHTML = '<span class="strength-medium">Medium password</span>';
        } else {
            strength.innerHTML = '<span class="strength-strong">Strong password</span>';
        }
    });
    </script>
</body>
</html>
