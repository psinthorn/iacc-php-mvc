<?php
/**
 * LINE OA Navigation Header (shared partial)
 * Include in all LINE OA sub-pages.
 *
 * Required: $t['page_title'] from the including page.
 * Set $currentNavPage before including (e.g. 'line_orders').
 * Optionally set $navIcon to override the page icon.
 *
 * Opens <div class="master-data-container"> — must be closed by the including page.
 */
$_navLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$_navItems = [
    'line_dashboard'    => ['icon' => 'fa-dashboard',      'en' => 'Dashboard',    'th' => 'แดชบอร์ด'],
    'line_settings'     => ['icon' => 'fa-cog',            'en' => 'Settings',     'th' => 'ตั้งค่า'],
    'line_orders'       => ['icon' => 'fa-shopping-cart',   'en' => 'Orders',       'th' => 'คำสั่งซื้อ'],
    'line_messages'     => ['icon' => 'fa-comments',        'en' => 'Messages',     'th' => 'ข้อความ'],
    'line_users'        => ['icon' => 'fa-users',           'en' => 'Users',        'th' => 'ผู้ใช้'],
    'line_auto_replies' => ['icon' => 'fa-reply-all',       'en' => 'Auto Replies', 'th' => 'ตอบกลับอัตโนมัติ'],
    'line_templates'    => ['icon' => 'fa-clone',           'en' => 'Templates',    'th' => 'เทมเพลต'],
    'line_broadcasts'   => ['icon' => 'fa-bullhorn',        'en' => 'Broadcasts',   'th' => 'ส่งกลุ่ม'],
    'line_send_message' => ['icon' => 'fa-paper-plane',     'en' => 'Send Message', 'th' => 'ส่งข้อความ'],
];
$_navCurrentIcon = $navIcon ?? ($_navItems[$currentNavPage ?? '']['icon'] ?? 'fa-comment');
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa <?= $_navCurrentIcon ?>"></i> <?= $t['page_title'] ?></h2>
    <div>
        <?php foreach ($_navItems as $_page => $_item): ?>
            <?php if ($_page !== ($currentNavPage ?? '')): ?>
            <a href="index.php?page=<?= $_page ?>" class="btn btn-sm btn-outline-primary"><i class="fa <?= $_item['icon'] ?>"></i> <?= $_item[$_navLang] ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
