<?php
/**
 * Onboarding Wizard
 * Collects company details after registration
 */
$companyName = isset($_SESSION['com_name']) ? htmlspecialchars($_SESSION['com_name'], ENT_QUOTES, 'UTF-8') : '';
$csrfToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
$errorMessage = isset($_SESSION['onboarding_error']) ? $_SESSION['onboarding_error'] : '';
unset($_SESSION['onboarding_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Account - iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #2c3e50; --success: #27ae60; --gray-300: #dee2e6; --gray-600: #6c757d; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px 20px; }

        .onboarding-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            max-width: 600px;
            width: 100%;
            padding: 50px 40px;
        }

        .welcome {
            text-align: center;
            margin-bottom: 35px;
        }
        .welcome-icon {
            width: 70px; height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 28px;
        }
        .welcome h1 { font-size: 1.6rem; color: var(--dark); margin-bottom: 8px; }
        .welcome p { color: var(--gray-600); font-size: 0.9rem; }

        .progress-bar { display: flex; gap: 8px; margin-bottom: 30px; }
        .progress-step { flex: 1; height: 4px; border-radius: 2px; background: #e9ecef; }
        .progress-step.done { background: var(--success); }
        .progress-step.active { background: var(--primary); }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px; background: #fce4ec; color: #c0392b; }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .form-group label .optional { font-weight: 400; color: var(--gray-600); font-size: 0.8rem; }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-300);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { min-height: 80px; resize: vertical; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

        .actions { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; gap: 15px; }
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.2s;
            text-decoration: none;
            text-align: center;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        .btn-skip { background: transparent; color: var(--gray-600); border: 2px solid var(--gray-300); }

        .help-text { color: var(--gray-600); font-size: 0.78rem; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="onboarding-card">
        <div class="welcome">
            <div class="welcome-icon"><i class="fa fa-briefcase"></i></div>
            <h1>Welcome, <?php echo $companyName; ?>!</h1>
            <p>Tell us about your business so we can customize your experience</p>
        </div>

        <!-- Progress: 1-Register ✓, 2-Verify ✓, 3-Company Details (current), 4-Done -->
        <div class="progress-bar">
            <div class="progress-step done"></div>
            <div class="progress-step done"></div>
            <div class="progress-step active"></div>
            <div class="progress-step"></div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=onboarding_complete" id="onboardingForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label for="company_name">Company / Business Name</label>
                <input type="text" class="form-control" id="company_name" name="company_name"
                       value="<?php echo $companyName; ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number <span class="optional">(optional)</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g. 02-xxx-xxxx">
                </div>
                <div class="form-group">
                    <label for="industry">Industry <span class="optional">(optional)</span></label>
                    <select class="form-control" id="industry" name="industry">
                        <option value="">Select industry</option>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="manufacturing">Manufacturing</option>
                        <option value="services">Services</option>
                        <option value="food">Food & Beverage</option>
                        <option value="technology">Technology</option>
                        <option value="construction">Construction</option>
                        <option value="transport">Transport & Logistics</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="education">Education</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address <span class="optional">(optional)</span></label>
                <textarea class="form-control" id="address" name="address" placeholder="Street address, city, postal code"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tax_id">Tax ID <span class="optional">(optional)</span></label>
                    <input type="text" class="form-control" id="tax_id" name="tax_id" placeholder="13-digit Tax ID" maxlength="13">
                    <div class="help-text">For Thai tax invoices (VAT/WHT)</div>
                </div>
                <div class="form-group">
                    <label for="branch">Branch <span class="optional">(optional)</span></label>
                    <input type="text" class="form-control" id="branch" name="branch" placeholder="e.g. Head Office / 00000" value="00000">
                </div>
            </div>

            <div class="actions">
                <a href="index.php?page=dashboard" class="btn btn-skip">Skip for now</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Complete Setup
                </button>
            </div>
        </form>
    </div>
</body>
</html>
