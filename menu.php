
<?php
// Menu Component: Bootstrap 5 Sidebar (legacy look, modern code)
include_once 'inc/top-navbar.php';
$current_user_level = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
$docker_debug_enabled = function_exists('is_docker_tools_enabled') ? is_docker_tools_enabled() : true;
$container_mgr_enabled = function_exists('is_container_manager_enabled') ? is_container_manager_enabled() : false;
$has_developer_role = function_exists('has_role') ? has_role('Developer') : false;
?>
<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav" id="side-menu">
            <li class="sidebar-search" style="padding: 15px 15px 0 15px;">
                <div class="input-group custom-search-form">
                    <input type="text" class="form-control" placeholder="Search...">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </li>
            <li>
                <a href="index.php?page=dashboard"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
            </li>
            <li>
                <a href="#sidebarMasterDataMenu" data-bs-toggle="collapse" aria-expanded="false"><i class="fa fa-database fa-fw"></i> Master Data <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level collapse" id="sidebarMasterDataMenu">
                    <li><a href="index.php?page=company"><i class="fa fa-building fa-fw"></i> <?= $xml->company ?? 'Company' ?></a></li>
                    <li><a href="index.php?page=category"><i class="fa fa-folder fa-fw"></i> <?= $xml->category ?? 'Category' ?></a></li>
                    <li><a href="index.php?page=brand"><i class="fa fa-bookmark fa-fw"></i> <?= $xml->brand ?? 'Brand' ?></a></li>
                    <li><a href="index.php?page=type"><i class="fa fa-cube fa-fw"></i> <?= $xml->product ?? 'Product' ?></a></li>
                    <li><a href="index.php?page=mo_list"><i class="fa fa-cubes fa-fw"></i> <?= $xml->model ?? 'Model' ?></a></li>
                </ul>
            </li>
            <?php if ($current_user_level >= 2): ?>
            <li>
                <a href="#sidebarAdminMenu" data-bs-toggle="collapse" aria-expanded="false"><i class="fa fa-shield fa-fw"></i> Admin <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level collapse" id="sidebarAdminMenu">
                    <li><a href="index.php?page=user"><i class="fa fa-users fa-fw"></i> <?= $xml->user ?? 'Users' ?></a></li>
                    <li><a href="index.php?page=audit_log"><i class="fa fa-history fa-fw"></i> <?= $xml->auditlog ?? 'Audit Log' ?></a></li>
                    <li><a href="index.php?page=payment_method_list"><i class="fa fa-credit-card-alt fa-fw"></i> <?= $xml->paymentmethods ?? 'Payment Methods' ?></a></li>
                    <li><a href="index.php?page=payment_gateway_config"><i class="fa fa-cogs fa-fw"></i> <?= $xml->gatewayconfig ?? 'Gateway Config' ?></a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php if ($has_developer_role): ?>
            <li>
                <a href="#sidebarDevToolsMenu" data-bs-toggle="collapse" aria-expanded="false"><i class="fa fa-wrench fa-fw"></i> Developer Tools <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level collapse" id="sidebarDevToolsMenu">
                    <li><a href="index.php?page=test_crud"><i class="fa fa-database fa-fw"></i> <?= $xml->testcrud ?? 'CRUD Test' ?></a></li>
                    <li><a href="index.php?page=debug_session"><i class="fa fa-key fa-fw"></i> <?= $xml->debugsession ?? 'Session Debug' ?></a></li>
                    <li><a href="index.php?page=debug_invoice"><i class="fa fa-file-text-o fa-fw"></i> <?= $xml->debuginvoice ?? 'Invoice Debug' ?></a></li>
                    <li><a href="index.php?page=api_lang_debug"><i class="fa fa-language fa-fw"></i> <?= $xml->apilangdebug ?? 'Language Debug' ?></a></li>
                    <li><a href="index.php?page=test_rbac"><i class="fa fa-shield fa-fw"></i> <?= $xml->testrbac ?? 'RBAC Test' ?></a></li>
                    <?php if ($docker_debug_enabled): ?>
                    <li><a href="index.php?page=docker_test"><i class="fa fa-cloud fa-fw"></i> <?= $xml->dockertest ?? 'Docker Test' ?></a></li>
                    <li><a href="index.php?page=test_containers"><i class="fa fa-cube fa-fw"></i> <?= $xml->testcontainers ?? 'Container Debug' ?></a></li>
                    <?php endif; ?>
                    <li><a href="index.php?page=containers" style="<?= !$container_mgr_enabled ? 'color: #999;' : '' ?>"><i class="fa fa-server fa-fw" style="<?= !$container_mgr_enabled ? 'color: #ccc;' : '' ?>"></i> <?= $xml->containers ?? 'Container Manager' ?><?php if (!$container_mgr_enabled): ?><span style="font-size: 10px; color: #999; margin-left: 5px;">(Off)</span><?php endif; ?></a></li>
                    <li><a href="index.php?page=monitoring"><i class="fa fa-dashboard fa-fw"></i> <?= $xml->monitoring ?? 'System Monitor' ?></a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
