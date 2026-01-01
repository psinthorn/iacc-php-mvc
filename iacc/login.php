<?php
error_reporting(E_ALL & ~E_NOTICE);
session_start();

// Redirect if already logged in
if(isset($_SESSION['usr_id']) && !empty($_SESSION['usr_id'])){
    header('Location: index.php?page=dashboard');
    exit;
}
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMS Login - iACC</title>

    <!-- Core CSS - Include with every page -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">

    <!-- SB Admin CSS - Include with every page -->
    <link href="css/sb-admin.css" rel="stylesheet">

    <!-- Login Page - Branding & UX/UI Enhancement -->
    <link href="css/login-page.css" rel="stylesheet">

</head>

<body>

    <div class="login-wrapper">
        <!-- Branding Section -->
        <div class="login-header">
            <i class="fa fa-dashboard brand-icon"></i>
            <h1 class="brand-name">iACC</h1>
            <p class="brand-tagline">Intelligence Accounting Content Management System</p>
        </div>

        <!-- Login Form -->
        <div class="login-form-wrapper">
            <!-- Error Message Display -->
            <div id="error-message" class="alert alert-danger" style="display:none; margin-bottom:20px;">
                <i class="fa fa-exclamation-circle"></i>
                <span id="error-text"></span>
            </div>

            <form action="authorize.php" method="post" name="login-form" id="login-form">
                <!-- Email Input -->
                <div class="form-group">
                    <label for="email">Email or Username</label>
                    <input
                        class="form-control"
                        id="email"
                        placeholder="your@email.com"
                        name="m_user"
                        type="text"
                        autofocus
                        required
                        autocomplete="email"
                    >
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        class="form-control"
                        id="password"
                        placeholder="••••••••"
                        name="m_pass"
                        type="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <!-- Remember Me -->
                <div class="form-check">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="Remember Me"
                    >
                    <label for="remember">Remember me</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">
                    <i class="fa fa-sign-in"></i>
                    <span>Sign In</span>
                </button>

                <!-- Footer Links -->
                <div class="login-footer">
                    <div class="footer-links">
                        <a href="#forgot">Forgot password?</a>
                        <span class="footer-divider">•</span>
                        <a href="#support">Help</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Info -->
        <div class="login-info">
            <p>© 2026 iACC</p>
            <p>Secure connection</p>
        </div>
    </div>

    <!-- Core Scripts - Include with every page -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>

    <!-- Login Form Validation -->
    <script>
    $(document).ready(function() {
        // Form validation on submit
        $('#login-form').on('submit', function(e) {
            var m_user = $('input[name="m_user"]').val().trim();
            var m_pass = $('input[name="m_pass"]').val().trim();
            
            // Clear previous error
            $('#error-message').hide();
            $('#error-text').text('');
            
            // Validate fields
            if(!m_user || !m_pass){
                e.preventDefault();
                showError('Please enter both username/email and password');
                return false;
            }
            
            // Show loading state
            $('.btn-submit').prop('disabled', true);
            $('.btn-submit span').text('Signing in...');
            $('.btn-submit i').addClass('fa-spinner fa-spin').removeClass('fa-sign-in');
        });
        
        function showError(message) {
            $('#error-text').text(message);
            $('#error-message').slideDown();
            $('input[name="m_pass"]').focus();
        }
        
        // Handle URL parameters for error messages
        var urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('error')){
            var error = urlParams.get('error');
            var messages = {
                'invalid_credentials': 'Invalid username/email or password',
                'db_error': 'Database error. Please try again.',
                'invalid_request': 'Invalid request. Please try again.'
            };
            if(messages[error]){
                showError(messages[error]);
            }
        }
    });
    </script>

</body>

</html>

