<?php
/**
 * Registration Form Page
 * Matches login.php styling with split panel layout
 */
$error = $_SESSION['reg_error'] ?? '';
$old = $_SESSION['reg_old'] ?? [];
unset($_SESSION['reg_error'], $_SESSION['reg_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --primary-light: #a569bd;
            --success: #27ae60;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            overflow: hidden;
        }

        /* Left Panel - Branding */
        .register-branding {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .register-branding::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
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
            width: 50px; height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }

        .branding-logo-text { font-size: 28px; font-weight: 700; }

        .branding-content h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .branding-content p {
            font-size: 1.05rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        .trial-badge i { font-size: 18px; }

        .branding-features { list-style: none; }
        .branding-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        .branding-features li i {
            width: 22px; height: 22px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        /* Right Panel - Form */
        .register-form-panel {
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header { margin-bottom: 25px; }
        .form-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .form-header p { color: var(--gray-600); font-size: 0.9rem; }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-danger { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6cb; }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .form-group .required { color: var(--danger); }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(142,68,173,0.15);
        }

        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 18px 0;
        }
        .checkbox-group input[type="checkbox"] {
            margin-top: 3px;
            accent-color: var(--primary);
        }
        .checkbox-group label {
            font-size: 0.82rem;
            color: var(--gray-600);
            line-height: 1.4;
        }
        .checkbox-group a { color: var(--primary); text-decoration: none; }
        .checkbox-group a:hover { text-decoration: underline; }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(142,68,173,0.3);
        }
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        .form-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: var(--gray-200);
            margin-top: 6px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        .password-hint {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .register-container { grid-template-columns: 1fr; }
            .register-branding { display: none; }
            .register-form-panel { padding: 40px 30px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Branding Panel -->
        <div class="register-branding">
            <div class="branding-content">
                <div class="branding-logo">
                    <div class="branding-logo-icon">i</div>
                    <div class="branding-logo-text">iACC</div>
                </div>
                <h1>Start Your Free Trial</h1>
                <div class="trial-badge">
                    <i class="fa fa-gift"></i>
                    14 days free — No credit card required
                </div>
                <p>Everything you need to manage your business accounting, invoicing, and sales channels.</p>
                <ul class="branding-features">
                    <li><i class="fa fa-check"></i> Create quotations, invoices & receipts</li>
                    <li><i class="fa fa-check"></i> Track expenses & payments</li>
                    <li><i class="fa fa-check"></i> Multi-currency with BOT exchange rates</li>
                    <li><i class="fa fa-check"></i> Thai VAT & Withholding Tax reports</li>
                    <li><i class="fa fa-check"></i> AI-powered chatbot assistant</li>
                    <li><i class="fa fa-check"></i> Sales Channel API integration</li>
                    <li><i class="fa fa-check"></i> PDF generation & email delivery</li>
                </ul>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="register-form-panel">
            <div class="form-header">
                <h2>Create your account</h2>
                <p>Fill in the details below to get started</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>

            <form action="index.php?page=register_submit" method="post" id="registerForm">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required
                           value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="name">
                </div>

                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="you@company.com" required
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email">
                </div>

                <div class="form-group">
                    <label>Company Name <span style="color: var(--gray-600); font-weight: 400;">(optional)</span></label>
                    <input type="text" name="company_name" class="form-control" placeholder="Your Company Ltd."
                           value="<?= htmlspecialchars($old['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="organization">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control"
                                   placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
                        <div class="password-hint" id="strengthHint">Use 8+ characters with letters and numbers</div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password_confirm" id="passwordConfirm" class="form-control"
                                   placeholder="Re-enter password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('passwordConfirm', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="agree_terms" id="agreeTerms" required>
                    <label for="agreeTerms">
                        I agree to the <a href="terms.php" target="_blank">Terms of Service</a>
                        and <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn-register" id="submitBtn">
                    <i class="fa fa-rocket"></i> Create Free Account
                </button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(fieldId, btn) {
        var field = document.getElementById(fieldId);
        var icon = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fa fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fa fa-eye';
        }
    }

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
        var val = this.value;
        var strength = 0;
        var bar = document.getElementById('strengthBar');
        var hint = document.getElementById('strengthHint');

        if (val.length >= 8) strength++;
        if (val.length >= 12) strength++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;
        if (/\d/.test(val)) strength++;
        if (/[^a-zA-Z0-9]/.test(val)) strength++;

        var colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
        var labels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
        var widths = ['20%', '40%', '60%', '80%', '100%'];

        var idx = Math.min(strength, 4);
        if (val.length === 0) {
            bar.style.width = '0';
            hint.textContent = 'Use 8+ characters with letters and numbers';
        } else {
            bar.style.width = widths[idx];
            bar.style.background = colors[idx];
            hint.textContent = labels[idx];
        }
    });

    // Form submit validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        var pw = document.getElementById('password').value;
        var pwc = document.getElementById('passwordConfirm').value;
        if (pw !== pwc) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Creating account...';
    });
    </script>
</body>
</html>
