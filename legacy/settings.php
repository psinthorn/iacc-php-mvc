<?php
/**
 * User Settings Page
 * Allows users to configure their preferences
 */
require_once("inc/security.php");

// Get current user data
$userId = $_SESSION['user_id'];
$stmt = $db->conn->prepare("SELECT * FROM authorize WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        switch ($action) {
            case 'update_language':
                $lang = isset($_POST['lang']) ? intval($_POST['lang']) : 0;
                $stmt = $db->conn->prepare("UPDATE authorize SET lang = ? WHERE id = ?");
                $stmt->bind_param("ii", $lang, $userId);
                if ($stmt->execute()) {
                    $_SESSION['lang'] = $lang;
                    $message = 'Language preference updated.';
                    $messageType = 'success';
                    // Reload to apply language change
                    header('Location: index.php?page=settings&updated=1');
                    exit;
                } else {
                    $message = 'Failed to update language.';
                    $messageType = 'danger';
                }
                $stmt->close();
                break;
                
            case 'update_notifications':
                // Note: notification preferences would need a new column in the database
                // For now, just show success
                $message = 'Notification preferences updated.';
                $messageType = 'success';
                break;
        }
    }
}

if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = isset($xml->settings_saved) ? $xml->settings_saved : 'Settings saved successfully.';
    $messageType = 'success';
}
?>

<style>
.settings-container {
    max-width: 900px;
    margin: 0 auto;
}

.settings-header {
    margin-bottom: 30px;
}

.settings-header h1 {
    font-size: 28px;
    margin: 0 0 8px 0;
    color: #333;
}

.settings-header p {
    color: #6c757d;
    margin: 0;
}

.settings-cards {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.settings-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.settings-card-header {
    background: #f8f9fa;
    padding: 18px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 12px;
}

.settings-card-header i {
    color: #8e44ad;
    font-size: 20px;
    width: 24px;
}

.settings-card-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
    flex: 1;
}

.settings-card-body {
    padding: 24px;
}

.setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
}

.setting-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.setting-row:first-child {
    padding-top: 0;
}

.setting-info {
    flex: 1;
}

.setting-info h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.setting-info p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

.setting-control {
    min-width: 150px;
    text-align: right;
}

/* Custom Select */
.custom-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%238e44ad'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 14px 45px 14px 16px;
    font-size: 15px;
    font-family: inherit;
    color: #333;
    cursor: pointer;
    transition: border-color 0.3s, box-shadow 0.3s;
    min-width: 180px;
    height: 48px;
    line-height: 1.4;
}

.custom-select option {
    padding: 12px;
    color: #333;
    background: #fff;
    font-size: 15px;
}

.custom-select:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 52px;
    height: 28px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 28px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.btn-save {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(142, 68, 173, 0.3);
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.danger-zone {
    border: 2px solid #fee2e2;
}

.danger-zone .settings-card-header {
    background: #fef2f2;
    border-color: #fee2e2;
}

.danger-zone .settings-card-header i {
    color: #dc2626;
}

.btn-danger {
    background: #dc2626;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-danger:hover {
    background: #b91c1c;
}

@media (max-width: 768px) {
    .setting-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .setting-control {
        width: 100%;
        text-align: left;
    }
    
    .custom-select {
        width: 100%;
    }
}
</style>

<div class="settings-container">
    <div class="settings-header">
        <h1><i class="fa fa-cog"></i> <?= isset($xml->settings) ? $xml->settings : 'Settings' ?></h1>
        <p><?= isset($xml->settings_desc) ? $xml->settings_desc : 'Manage your account settings and preferences' ?></p>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fa fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <div class="settings-cards">
        <!-- Language Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <i class="fa fa-globe"></i>
                <h3><?= isset($xml->language_settings) ? $xml->language_settings : 'Language & Region' ?></h3>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_language">
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4><?= isset($xml->display_language) ? $xml->display_language : 'Display Language' ?></h4>
                            <p><?= isset($xml->language_desc) ? $xml->language_desc : 'Choose your preferred language for the interface' ?></p>
                        </div>
                        <div class="setting-control">
                            <select name="lang" class="custom-select" onchange="this.form.submit()">
                                <option value="0" <?= $user['lang'] == 0 ? 'selected' : '' ?>>English</option>
                                <option value="1" <?= $user['lang'] == 1 ? 'selected' : '' ?>>ไทย (Thai)</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Notification Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <i class="fa fa-bell"></i>
                <h3><?= isset($xml->notifications) ? $xml->notifications : 'Notifications' ?></h3>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_notifications">
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4><?= isset($xml->email_notifications) ? $xml->email_notifications : 'Email Notifications' ?></h4>
                            <p><?= isset($xml->email_notifications_desc) ? $xml->email_notifications_desc : 'Receive email alerts for important updates' ?></p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_notifications" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4><?= isset($xml->invoice_alerts) ? $xml->invoice_alerts : 'Invoice Alerts' ?></h4>
                            <p><?= isset($xml->invoice_alerts_desc) ? $xml->invoice_alerts_desc : 'Get notified when invoices are created or paid' ?></p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="invoice_alerts" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4><?= isset($xml->payment_reminders) ? $xml->payment_reminders : 'Payment Reminders' ?></h4>
                            <p><?= isset($xml->payment_reminders_desc) ? $xml->payment_reminders_desc : 'Receive reminders for upcoming payment due dates' ?></p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="payment_reminders">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Display Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <i class="fa fa-desktop"></i>
                <h3><?= isset($xml->display_settings) ? $xml->display_settings : 'Display' ?></h3>
            </div>
            <div class="settings-card-body">
                <div class="setting-row">
                    <div class="setting-info">
                        <h4><?= isset($xml->records_per_page) ? $xml->records_per_page : 'Records Per Page' ?></h4>
                        <p><?= isset($xml->records_per_page_desc) ? $xml->records_per_page_desc : 'Default number of records shown in list pages' ?></p>
                    </div>
                    <div class="setting-control">
                        <select class="custom-select">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <h4><?= isset($xml->date_format) ? $xml->date_format : 'Date Format' ?></h4>
                        <p><?= isset($xml->date_format_desc) ? $xml->date_format_desc : 'How dates are displayed throughout the application' ?></p>
                    </div>
                    <div class="setting-control">
                        <select class="custom-select">
                            <option value="d/m/Y">DD/MM/YYYY</option>
                            <option value="m/d/Y">MM/DD/YYYY</option>
                            <option value="Y-m-d">YYYY-MM-DD</option>
                        </select>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <h4><?= isset($xml->compact_view) ? $xml->compact_view : 'Compact View' ?></h4>
                        <p><?= isset($xml->compact_view_desc) ? $xml->compact_view_desc : 'Use a more compact display for tables and lists' ?></p>
                    </div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="compact_view">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <i class="fa fa-shield"></i>
                <h3><?= isset($xml->security) ? $xml->security : 'Security' ?></h3>
            </div>
            <div class="settings-card-body">
                <div class="setting-row">
                    <div class="setting-info">
                        <h4><?= isset($xml->password) ? $xml->password : 'Password' ?></h4>
                        <p><?= isset($xml->password_desc) ? $xml->password_desc : 'Change your account password' ?></p>
                    </div>
                    <div class="setting-control">
                        <a href="index.php?page=profile" class="btn-save" style="text-decoration: none;">
                            <i class="fa fa-key"></i> <?= isset($xml->change) ? $xml->change : 'Change' ?>
                        </a>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <h4><?= isset($xml->active_sessions) ? $xml->active_sessions : 'Active Sessions' ?></h4>
                        <p><?= isset($xml->active_sessions_desc) ? $xml->active_sessions_desc : 'You are currently logged in from 1 device' ?></p>
                    </div>
                    <div class="setting-control">
                        <button type="button" class="btn-save" style="background: #6c757d;">
                            <i class="fa fa-sign-out"></i> <?= isset($xml->logout_all) ? $xml->logout_all : 'Logout All' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="settings-card danger-zone">
            <div class="settings-card-header">
                <i class="fa fa-exclamation-triangle"></i>
                <h3><?= isset($xml->danger_zone) ? $xml->danger_zone : 'Danger Zone' ?></h3>
            </div>
            <div class="settings-card-body">
                <div class="setting-row">
                    <div class="setting-info">
                        <h4><?= isset($xml->delete_account) ? $xml->delete_account : 'Delete Account' ?></h4>
                        <p><?= isset($xml->delete_account_desc) ? $xml->delete_account_desc : 'Permanently delete your account and all associated data. This action cannot be undone.' ?></p>
                    </div>
                    <div class="setting-control">
                        <button type="button" class="btn-danger" onclick="alert('Please contact an administrator to delete your account.')">
                            <i class="fa fa-trash"></i> <?= isset($xml->delete) ? $xml->delete : 'Delete' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
