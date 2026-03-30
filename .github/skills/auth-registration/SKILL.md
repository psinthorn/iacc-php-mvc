---
name: auth-registration
description: 'Authentication and self-registration flows for iACC. USE FOR: email verification, user registration, onboarding wizards, SMTP email sending, password security, auto-login after verification, trial activation, session management, login/signup pages. Use when: building registration flow, sending verification emails, creating onboarding steps, implementing email-based auth, adding signup links.'
argument-hint: 'Describe the auth or registration feature to build'
---

# Authentication & Registration

## When to Use

- Building user registration / signup flows
- Implementing email verification (token-based)
- Creating onboarding wizards for new users
- Sending transactional emails (SMTP)
- Managing auth sessions and auto-login

## Architecture

```
Registration Flow:
  Register Form → POST validate → Create token → Send email
  → User clicks link → Verify token → Create account (transaction)
  → Auto-login → Onboarding wizard → Dashboard

Key Files:
  app/Models/Registration.php          — Token + account creation logic
  app/Controllers/RegistrationController.php — Flow orchestration
  app/Views/auth/register.php          — Signup form (split-panel)
  app/Views/auth/register-sent.php     — Email confirmation page
  app/Views/auth/register-error.php    — Token error page
  app/Views/auth/onboarding.php        — Company setup wizard
  app/Views/auth/plans.php             — Subscription tier comparison
```

## Email Verification Pattern

### Token Generation & Storage

```php
// Generate cryptographically secure token
$token = bin2hex(random_bytes(32)); // 64 hex chars

// Store with expiry
$stmt = $conn->prepare(
    "INSERT INTO email_verifications (email, token, payload, expires_at)
     VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
);
$payload = json_encode(['name' => $name, 'password_hash' => $hash, 'company_name' => $company]);
$stmt->bind_param('sss', $email, $token, $payload);
$stmt->execute();
```

### Token Verification

```php
$stmt = $conn->prepare(
    "SELECT * FROM email_verifications
     WHERE token = ? AND expires_at > NOW() AND verified_at IS NULL"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
// $row is null if token invalid/expired/used
```

### Schema

```sql
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    payload TEXT NOT NULL COMMENT 'JSON data',
    expires_at DATETIME NOT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## SMTP Email Sending

Uses raw `fsockopen()` — no PHP extensions required. Works with MailHog in dev.

```php
function sendSmtpEmail(string $to, string $subject, string $htmlBody): bool
{
    $host = getenv('SMTP_HOST') ?: 'iacc_mailhog_server';
    $port = (int)(getenv('SMTP_PORT') ?: 1025);
    $from = getenv('SMTP_FROM') ?: 'noreply@iacc.app';

    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$fp) return false;

    $commands = [
        null,                                    // Read greeting
        "EHLO iacc.app\r\n",
        "MAIL FROM:<{$from}>\r\n",
        "RCPT TO:<{$to}>\r\n",
        "DATA\r\n",
    ];

    foreach ($commands as $cmd) {
        if ($cmd === null) {
            fgets($fp, 512); // read greeting
            continue;
        }
        fwrite($fp, $cmd);
        fgets($fp, 512);
    }

    // Headers + body
    $boundary = md5(uniqid());
    $headers  = "From: iACC <{$from}>\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "\r\n";

    fwrite($fp, $headers . $htmlBody . "\r\n.\r\n");
    fgets($fp, 512);
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return true;
}
```

**Dev**: MailHog at `iacc_mailhog_server:1025`, view at http://localhost:8025
**Prod**: Configure `SMTP_HOST`, `SMTP_PORT`, `SMTP_FROM` env vars

## Account Creation (Transactional)

Always use a transaction when creating company + user + subscription:

```php
$this->conn->begin_transaction();
try {
    // 1. Create company
    $companyId = $har->Maxid('company');
    $har->insertSafe('company', [
        'com_id' => $companyId,
        'com_name' => $companyName,
        'registered_via' => 'self',
    ]);

    // 2. Create user (level 0 = regular user)
    $userId = $har->Maxid('authorize');
    $har->insertSafe('authorize', [
        'id' => $userId,
        'email' => $email,
        'password' => $passwordHash,
        'name' => $name,
        'level' => 0,
        'company_id' => $companyId,
        'registered_via' => 'self',
        'email_verified_at' => date('Y-m-d H:i:s'),
    ]);

    // 3. Create trial subscription
    $sub = new Subscription();
    $subId = $sub->createTrial($companyId);

    $this->conn->commit();
    return [$companyId, $userId, $subId];
} catch (\Exception $e) {
    $this->conn->rollback();
    throw $e;
}
```

## Auto-Login After Verification

```php
function autoLogin(int $userId, string $email, string $name, int $companyId, string $companyName): void
{
    session_regenerate_id(true); // Prevent session fixation

    $_SESSION['user_id']    = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_level'] = 0;
    $_SESSION['com_id']     = $companyId;
    $_SESSION['com_name']   = $companyName;
    $_SESSION['lang']       = 'en';

    // Load RBAC permissions
    if (function_exists('rbac_load_permissions')) {
        rbac_load_permissions($userId);
    }
    if (function_exists('rbac_load_roles')) {
        rbac_load_roles($userId);
    }
}
```

## Rate Limiting (Session-Based)

```php
function checkRateLimit(string $action, int $maxAttempts = 10, int $windowSeconds = 3600): bool
{
    $key = 'rate_' . $action . '_' . md5($_SERVER['REMOTE_ADDR'] ?? 'cli');
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset' => time() + $windowSeconds];
    }
    if (time() > $_SESSION[$key]['reset']) {
        $_SESSION[$key] = ['count' => 0, 'reset' => time() + $windowSeconds];
    }
    $_SESSION[$key]['count']++;
    return $_SESSION[$key]['count'] <= $maxAttempts;
}
```

## Password Security

```php
// Hash (registration)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify (login) — supports bcrypt + MD5 fallback
if (password_verify($password, $storedHash)) {
    // OK
} elseif (md5($password) === $storedHash) {
    // Legacy MD5 — migrate to bcrypt
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    // UPDATE authorize SET password = $newHash WHERE id = $userId
}
```

## View Design Pattern (Auth Pages)

Auth pages use a **split-panel layout** (left: branding gradient, right: form):

```html
<div class="login-container">
    <!-- Left: Branding panel -->
    <div class="branding-panel" style="background: linear-gradient(135deg, #8e44ad, #6c3483);">
        <h1>iACC</h1>
        <p>Feature highlights listed here</p>
    </div>
    <!-- Right: Form panel -->
    <div class="form-panel">
        <h2>Page Title</h2>
        <form method="POST" action="index.php?page=action_name">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <!-- form fields -->
            <button type="submit">Submit</button>
        </form>
    </div>
</div>
```

## Routes Configuration

```php
// Public (no auth) — registration, verification
'register'          => ['RegistrationController', 'showForm', 'public'],
'register_submit'   => ['RegistrationController', 'register', 'public'],
'register_verify'   => ['RegistrationController', 'verify', 'public'],

// Auth required — onboarding, plans (user must be logged in)
'onboarding'        => ['RegistrationController', 'onboarding'],
'plans'             => ['RegistrationController', 'plans'],
```

## Subscription Plans

| Plan | Price | Orders/mo | API Keys | Duration | Channels |
|------|-------|-----------|----------|----------|----------|
| Trial | Free | 50 | 1 | 14 days | Website |
| Starter | ฿990 | 500 | 3 | 30 days | Website + Email |
| Professional | ฿2,490 | 5,000 | 10 | 30 days | All |
| Enterprise | Custom | Unlimited | Unlimited | 365 days | All + Custom |
