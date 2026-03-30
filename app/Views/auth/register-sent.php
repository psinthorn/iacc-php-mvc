<?php
/**
 * Registration Email Sent Page
 * Shows after successful registration form submission
 */
$email = $_SESSION['reg_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Email - iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #2c3e50; --gray-600: #6c757d; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            padding: 50px 40px;
            text-align: center;
        }
        .icon-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .icon-circle i { color: white; font-size: 36px; }
        h1 { font-size: 1.6rem; color: var(--dark); margin-bottom: 12px; }
        p { color: var(--gray-600); line-height: 1.6; margin-bottom: 15px; }
        .email-badge {
            display: inline-block;
            background: #f8f4fb;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 0 20px;
            word-break: break-all;
        }
        .divider { border: none; border-top: 1px solid #eee; margin: 25px 0; }
        .help-text { font-size: 0.82rem; color: #999; }
        .help-text a { color: var(--primary); text-decoration: none; }
        .links { margin-top: 25px; }
        .links a {
            display: inline-block;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">
            <i class="fa fa-envelope-o"></i>
        </div>
        <h1>Check your email</h1>
        <p>We've sent a verification link to:</p>
        <div class="email-badge"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
        <p>Click the link in the email to verify your address and activate your <strong>14-day free trial</strong>.</p>
        <hr class="divider">
        <p class="help-text">
            Didn't receive the email? Check your spam folder, or
            <a href="index.php?page=register">try registering again</a>.
        </p>
        <p class="help-text">The verification link expires in 24 hours.</p>
        <div class="links">
            <a href="login.php"><i class="fa fa-arrow-left"></i> Back to Sign In</a>
        </div>
    </div>
</body>
</html>
