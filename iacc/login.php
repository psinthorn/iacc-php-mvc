<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CMS Login</title>

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
            <h1 class="brand-name">iACC CMS</h1>
            <p class="brand-tagline">Account Management System</p>
        </div>

        <!-- Login Form -->
        <div class="login-form-wrapper">
            <form action="authorize.php" method="post">
                <!-- Email Input -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        class="form-control"
                        id="email"
                        placeholder="your@email.com"
                        name="m_user"
                        type="email"
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
            <p>© 2026 iACC CMS</p>
            <p>Secure connection</p>
        </div>
    </div>

    <!-- Core Scripts - Include with every page -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>

</body>

</html>
