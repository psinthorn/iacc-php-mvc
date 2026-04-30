<?php
/**
 * Shared agent portal helpers (CSS + portal nav)
 * Include via: include __DIR__ . '/_shared.php';
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';
$currentPage = $_GET['page'] ?? 'agent_portal_dashboard';
?>
<link rel="stylesheet" href="css/master-data.css">

<style>
.portal-nav { display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap; }
.portal-nav a { padding: 12px 20px; font-size: 14px; font-weight: 500; color: #64748b; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .15s; }
.portal-nav a:hover { color: #0d9488; }
.portal-nav a.active { color: #0d9488; border-bottom-color: #0d9488; font-weight: 600; }
.portal-nav a i { margin-right: 6px; }

.empty-state { text-align: center; padding: 48px; color: #94a3b8; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
</style>

<div class="portal-nav">
    <a href="index.php?page=agent_portal_dashboard" class="<?= $currentPage === 'agent_portal_dashboard' ? 'active' : '' ?>">
        <i class="fa fa-tachometer"></i> <?= $isThai ? 'แดชบอร์ด' : 'Dashboard' ?>
    </a>
    <a href="index.php?page=agent_portal_products" class="<?= $currentPage === 'agent_portal_products' ? 'active' : '' ?>">
        <i class="fa fa-cubes"></i> <?= $isThai ? 'สินค้า' : 'Products' ?>
    </a>
    <a href="index.php?page=agent_portal_contracts" class="<?= in_array($currentPage, ['agent_portal_contracts', 'agent_portal_contract']) ? 'active' : '' ?>">
        <i class="fa fa-file-text-o"></i> <?= $isThai ? 'สัญญา' : 'Contracts' ?>
    </a>
    <a href="index.php?page=agent_portal_bookings" class="<?= $currentPage === 'agent_portal_bookings' ? 'active' : '' ?>">
        <i class="fa fa-calendar-check-o"></i> <?= $isThai ? 'การจอง' : 'Bookings' ?>
    </a>
    <a href="index.php?page=agent_portal_documents" class="<?= $currentPage === 'agent_portal_documents' ? 'active' : '' ?>">
        <i class="fa fa-folder-open-o"></i> <?= $isThai ? 'เอกสาร' : 'Documents' ?>
    </a>
</div>
