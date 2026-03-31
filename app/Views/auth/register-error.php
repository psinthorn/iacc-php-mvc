<?php
/**
 * Registration Error Page
 * Shown when email verification fails (invalid/expired token)
 */
$errorMessage = $errorMessage ?? 'An error occurred during registration.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Error - iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #2c3e50; --danger: #e74c3c; --gray-600: #6c757d; }
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
            background: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .icon-circle i { color: white; font-size: 36px; }
        h1 { font-size: 1.6rem; color: var(--dark); margin-bottom: 15px; }
        p { color: var(--gray-600); line-height: 1.6; margin-bottom: 15px; }
        .btn-retry {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-retry:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(142,68,173,0.3);
        }
        .links { margin-top: 20px; }
        .links a {
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
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <h1>Verification Failed</h1>
        <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <a href="index.php?page=register" class="btn-retry">
            <i class="fa fa-refresh"></i> Register Again
        </a>
        <div class="links">
            <a href="login.php"><i class="fa fa-arrow-left"></i> Back to Sign In</a>
        </div>
    </div>
</body>
</html>
