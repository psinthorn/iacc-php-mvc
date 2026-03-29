<?php
/**
 * iACC Template — Admin Login
 * Simple session-based authentication for the admin panel.
 * Default credentials are set during the setup wizard (Step 3).
 * Default: admin / admin123 (change after first login!)
 */
session_start();

$configPath = __DIR__ . '/config.php';
$config = file_exists($configPath) ? require $configPath : [];

// Redirect to setup if not configured
if (!($config['configured'] ?? false)) {
    header('Location: setup.php');
    exit;
}

// Already logged in → go to admin
if (!empty($_SESSION['template_admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $storedUser = $config['admin_username'] ?? 'admin';
    $storedHash = $config['admin_password_hash'] ?? password_hash('admin123', PASSWORD_BCRYPT);

    if ($username === $storedUser && password_verify($password, $storedHash)) {
        $_SESSION['template_admin_logged_in'] = true;
        $_SESSION['template_admin_user'] = $username;
        $_SESSION['template_admin_login_time'] = time();
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$siteTitle = htmlspecialchars($config['site_title'] ?? 'iACC Template');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= $siteTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #1e293b; --gray: #64748b; --bg: #f1f5f9; --success: #10b981; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--dark); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .login-container { max-width: 420px; width: 100%; }
        .login-header { text-align: center; margin-bottom: 32px; }
        .login-header .icon { width: 64px; height: 64px; border-radius: 16px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px; }
        .login-header h1 { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
        .login-header h1 span { color: var(--primary); }
        .login-header p { color: var(--gray); font-size: 14px; }
        .login-card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .form-group .input-wrap { position: relative; }
        .form-group .input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray); font-size: 15px; }
        .form-group input { width: 100%; padding: 12px 16px 12px 42px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 14px; transition: all 0.2s; background: #f8fafc; }
        .form-group input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(142,68,173,0.1); }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px 28px; border: none; border-radius: 50px; font-family: inherit; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.2s; background: var(--primary); color: white; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .error-box { padding: 12px 16px; border-radius: 10px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: var(--gray); text-decoration: none; font-size: 13px; }
        .back-link a:hover { color: var(--primary); }
        .default-creds { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px; margin-bottom: 20px; font-size: 12px; color: #1e40af; }
        .default-creds strong { font-weight: 600; }
        .default-creds code { background: #dbeafe; padding: 1px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="icon"><i class="fa-solid fa-lock"></i></div>
        <h1><span>Admin</span> Login</h1>
        <p><?= $siteTitle ?> — Admin Panel</p>
    </div>

    <div class="login-card">
        <?php if ($error): ?>
        <div class="error-box">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (!isset($config['admin_username'])): ?>
        <div class="default-creds">
            <strong><i class="fa-solid fa-info-circle"></i> Default Credentials</strong><br>
            Username: <code>admin</code> &nbsp; Password: <code>admin123</code><br>
            <em>Please change these after your first login via Setup Wizard.</em>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" placeholder="Enter username" autocomplete="username" required 
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </div>
            </div>
            <button type="submit" class="btn">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>
    </div>

    <div class="back-link">
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
    </div>
</div>
</body>
</html>
