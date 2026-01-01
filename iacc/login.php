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

    <div class="login-container">
        <!-- Branding Section -->
        <div class="login-header">
            <div class="brand-logo">
                <i class="fa fa-dashboard"></i>
            </div>
            <h1 class="brand-title">iAcc CMS</h1>
            <p class="brand-subtitle">Intelligent Account Management System</p>
        </div>

        <!-- Login Panel -->
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-lock"></i> Secure Login</h3>
            </div>
            <div class="panel-body">
                <form role="form" action="authorize.php" method="post" class="login-form">
                    <fieldset>
                        <!-- Email Input -->
                        <div class="form-group form-group-with-icon">
                            <label for="email">Email Address</label>
                            <input
                                class="form-control"
                                id="email"
                                placeholder="Enter your email"
                                name="m_user"
                                type="email"
                                autofocus
                                required
                                aria-label="Email address"
                            >
                            <i class="fa fa-envelope form-icon"></i>
                        </div>

                        <!-- Password Input -->
                        <div class="form-group form-group-with-icon">
                            <label for="password">Password</label>
                            <input
                                class="form-control"
                                id="password"
                                placeholder="Enter your password"
                                name="m_pass"
                                type="password"
                                required
                                aria-label="Password"
                            >
                            <i class="fa fa-key form-icon"></i>
                        </div>

                        <!-- Remember Me -->
                        <div class="checkbox">
                            <label>
                                <input name="remember" type="checkbox" value="Remember Me" aria-label="Remember me">
                                <span>Remember me on this device</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-login" aria-label="Sign in to your account">
                            <i class="fa fa-sign-in"></i>
                            <span>Sign In</span>
                        </button>
                    </fieldset>
                </form>

                <!-- Additional Links -->
                <div class="login-links">
                    <a href="#forgot" title="Forgot password">Forgot Password?</a>
                    <span style="color: #ccc;">•</span>
                    <a href="#support" title="Get support">Need Help?</a>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div style="text-align: center; margin-top: 2rem; color: rgba(255, 255, 255, 0.8); font-size: 0.875rem;">
            <p style="margin: 0;">© 2026 iAcc CMS. All rights reserved.</p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.8rem;">Secure & Encrypted Connection</p>
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
