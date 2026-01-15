<?php
/**
 * Top Navbar Component
 * Modern responsive top navigation bar with user dropdown
 */

// Get current user info
$current_user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$current_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$current_user_level = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
$current_company = isset($_SESSION['com_name']) ? $_SESSION['com_name'] : '';

// Get user initials for avatar
$initials = 'U';
if ($current_user_name) {
    $parts = explode(' ', $current_user_name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }
} elseif ($current_user_email) {
    $initials = strtoupper(substr($current_user_email, 0, 2));
}

// Get role label
$role_labels = [
    0 => 'User',
    1 => 'Admin',
    2 => 'Super Admin'
];
$role_label = $role_labels[$current_user_level] ?? 'User';

// Get current language
$current_lang = isset($_SESSION['lang']) ? intval($_SESSION['lang']) : 0;
?>

<style>
/* ============ TOP NAVBAR STYLES ============ */
.top-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(135deg, #8e44ad 0%, #6c3483 100%);
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
    z-index: 1050;
    display: flex;
    align-items: center;
    padding: 0 20px;
}

.top-nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: white;
    margin-right: 30px;
}

.top-nav-brand-icon {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
}

.top-nav-brand-text {
    font-size: 20px;
    font-weight: 700;
}

.top-nav-company {
    background: rgba(255, 255, 255, 0.15);
    padding: 6px 14px;
    border-radius: 20px;
    color: white;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.top-nav-company i {
    font-size: 14px;
}

.top-nav-toggle {
    display: none;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
}

.top-nav-spacer {
    flex: 1;
}

.top-nav-items {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    margin: 0;
    padding: 0;
}

/* Language Switcher */
.lang-switcher {
    display: flex;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    overflow: hidden;
}

.lang-btn {
    background: none;
    border: none;
    padding: 8px 12px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}

.lang-btn:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.lang-btn.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.lang-btn img {
    width: 18px;
    height: 12px;
    border-radius: 2px;
}

/* Notifications */
.nav-icon-btn {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.nav-icon-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.nav-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 18px;
    height: 18px;
    background: #e74c3c;
    color: white;
    font-size: 10px;
    font-weight: 600;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* User Dropdown */
.user-dropdown {
    position: relative;
}

.user-dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    padding: 6px 14px 6px 8px;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.2s;
}

.user-dropdown-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 600;
}

.user-info {
    text-align: left;
    display: none;
}

.user-name {
    color: white;
    font-size: 14px;
    font-weight: 600;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-role {
    color: rgba(255, 255, 255, 0.7);
    font-size: 11px;
}

.user-dropdown-caret {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
}

.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 260px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1100;
}

.user-dropdown.open .user-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-dropdown-header {
    padding: 20px;
    background: linear-gradient(135deg, #8e44ad, #6c3483);
    border-radius: 12px 12px 0 0;
    color: white;
    text-align: center;
}

.user-dropdown-header .user-avatar {
    width: 60px;
    height: 60px;
    font-size: 20px;
    margin: 0 auto 10px;
}

.user-dropdown-header h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.user-dropdown-header p {
    margin: 4px 0 0;
    font-size: 12px;
    opacity: 0.9;
}

.user-dropdown-header .role-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 11px;
    margin-top: 8px;
}

.user-dropdown-body {
    padding: 10px 0;
}

.user-dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.user-dropdown-item:hover {
    background: #f5f6fa;
    color: #8e44ad;
}

.user-dropdown-item i {
    width: 20px;
    text-align: center;
    color: #666;
}

.user-dropdown-item:hover i {
    color: #8e44ad;
}

.user-dropdown-divider {
    height: 1px;
    background: #eee;
    margin: 8px 0;
}

.user-dropdown-item.logout {
    color: #e74c3c;
}

.user-dropdown-item.logout i {
    color: #e74c3c;
}

/* Responsive */
@media (min-width: 768px) {
    .user-info {
        display: block;
    }
}

@media (max-width: 768px) {
    .top-nav-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .top-nav-company {
        display: none;
    }
    
    .lang-switcher span {
        display: none;
    }
}

/* Offset for fixed navbar */
body.has-top-nav {
    padding-top: 60px;
}

body.has-top-nav #wrapper {
    margin-top: 0;
}

body.has-top-nav .navbar-static-top {
    display: none;
}

body.has-top-nav .navbar-static-side {
    top: 60px;
}
</style>

<nav class="top-nav">
    <!-- Brand -->
    <a href="index.php?page=dashboard" class="top-nav-brand">
        <span class="top-nav-brand-text">iACC</span>
    </a>
    
    <!-- Company Badge -->
    <?php if ($current_company): ?>
    <div class="top-nav-company">
        <i class="fa fa-building"></i>
        <span><?= htmlspecialchars($current_company) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Mobile Toggle -->
    <button class="top-nav-toggle" id="sidebarToggle">
        <i class="fa fa-bars"></i>
    </button>
    
    <!-- Spacer -->
    <div class="top-nav-spacer"></div>
    
    <!-- Nav Items -->
    <ul class="top-nav-items">
        <!-- Language Switcher -->
        <li>
            <form action="lang.php" method="post" id="langForm" style="margin: 0;">
                <div class="lang-switcher">
                    <button type="submit" name="chlang" value="0" class="lang-btn <?= $current_lang == 0 ? 'active' : '' ?>">
                        <img src="images/us.jpg" alt="EN"> <span>EN</span>
                    </button>
                    <button type="submit" name="chlang" value="1" class="lang-btn <?= $current_lang == 1 ? 'active' : '' ?>">
                        <img src="images/th.jpg" alt="TH"> <span>TH</span>
                    </button>
                </div>
            </form>
        </li>
        
        <!-- Notifications (placeholder) -->
        <!--
        <li>
            <button class="nav-icon-btn" title="Notifications">
                <i class="fa fa-bell"></i>
                <span class="nav-badge">3</span>
            </button>
        </li>
        -->
        
        <!-- User Dropdown -->
        <li class="user-dropdown" id="userDropdown">
            <button class="user-dropdown-toggle" onclick="toggleUserDropdown()">
                <div class="user-avatar"><?= $initials ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($current_user_name ?: $current_user_email) ?></div>
                    <div class="user-role"><?= $role_label ?></div>
                </div>
                <i class="fa fa-caret-down user-dropdown-caret"></i>
            </button>
            
            <div class="user-dropdown-menu">
                <div class="user-dropdown-header">
                    <div class="user-avatar"><?= $initials ?></div>
                    <h4><?= htmlspecialchars($current_user_name ?: 'User') ?></h4>
                    <p><?= htmlspecialchars($current_user_email) ?></p>
                    <span class="role-badge"><?= $role_label ?></span>
                </div>
                <div class="user-dropdown-body">
                    <a href="index.php?page=profile" class="user-dropdown-item">
                        <i class="fa fa-user"></i>
                        <span><?= isset($xml->profile) ? $xml->profile : 'My Profile' ?></span>
                    </a>
                    <a href="index.php?page=settings" class="user-dropdown-item">
                        <i class="fa fa-cog"></i>
                        <span><?= isset($xml->settings) ? $xml->settings : 'Settings' ?></span>
                    </a>
                    <?php if ($current_user_level >= 1): ?>
                    <a href="index.php?page=dashboard&clear_company=1" class="user-dropdown-item">
                        <i class="fa fa-exchange"></i>
                        <span><?= isset($xml->switchcompany) ? $xml->switchcompany : 'Switch Company' ?></span>
                    </a>
                    <?php endif; ?>
                    <div class="user-dropdown-divider"></div>
                    <a href="index.php?page=help" class="user-dropdown-item">
                        <i class="fa fa-question-circle"></i>
                        <span><?= isset($xml->help) ? $xml->help : 'Help & Support' ?></span>
                    </a>
                    <div class="user-dropdown-divider"></div>
                    <a href="authorize.php?logout=1" class="user-dropdown-item logout">
                        <i class="fa fa-sign-out"></i>
                        <span><?= isset($xml->logout) ? $xml->logout : 'Sign Out' ?></span>
                    </a>
                </div>
            </div>
        </li>
    </ul>
</nav>

<script>
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
    }
});

// Sidebar toggle for mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.querySelector('.navbar-static-side');
    if (sidebar) {
        sidebar.classList.toggle('mobile-visible');
    }
});
</script>
