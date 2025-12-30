<?php
/**
 * Sidebar Navigation Component
 * Permission-based menu visibility using RBAC
 */

// Initialize authorization if not already done
if (!isset($authorization)) {
    $authorization = null;
}

// Get menu items with permission requirements
$menu_items = [
    [
        'label' => 'Dashboard',
        'icon' => 'fa-home',
        'url' => '?page=dashboard',
        'permission' => null  // Everyone can see dashboard
    ],
    [
        'label' => 'Purchase Orders',
        'icon' => 'fa-file-invoice-dollar',
        'url' => '?page=po_list',
        'permission' => 'po.view'
    ],
    [
        'label' => 'Purchase Requests',
        'icon' => 'fa-clipboard-list',
        'url' => '?page=pr_list',
        'permission' => 'pr.view'
    ],
    [
        'label' => 'Companies',
        'icon' => 'fa-building',
        'url' => '?page=company_list',
        'permission' => 'companies.view'
    ],
    [
        'label' => 'Quotations',
        'icon' => 'fa-quote-left',
        'url' => '?page=qa_list',
        'permission' => 'qa.view'
    ],
    [
        'label' => 'Invoices',
        'icon' => 'fa-receipt',
        'url' => '?page=inv_list',
        'permission' => 'inv.view'
    ],
    [
        'label' => 'Payments',
        'icon' => 'fa-wallet',
        'url' => '?page=payment_list',
        'permission' => 'payment.view'
    ],
    [
        'label' => 'Deliveries',
        'icon' => 'fa-truck',
        'url' => '?page=deliv_list',
        'permission' => 'deliv.view'
    ],
    [
        'label' => 'Reports',
        'icon' => 'fa-chart-line',
        'url' => '?page=report',
        'permission' => 'reports.view'
    ],
];

// Admin-only menu items
$admin_items = [
    [
        'label' => 'Users',
        'icon' => 'fa-users',
        'url' => '?page=user_list',
        'permission' => 'users.view'
    ],
    [
        'label' => 'Roles',
        'icon' => 'fa-user-shield',
        'url' => '?page=role_list',
        'permission' => 'settings.manage_roles'
    ],
    [
        'label' => 'Audit Log',
        'icon' => 'fa-history',
        'url' => '?page=audit_log',
        'permission' => 'settings.manage_audit_log'
    ],
];
?>

<aside id="sidebar" 
       class="w-64 bg-white border-r border-gray-200 overflow-y-auto fixed lg:relative h-full z-40 lg:z-auto transition-transform"
       :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
    
    <!-- Logo Section -->
    <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
        <h1 class="text-xl font-bold text-blue-600">iACC</h1>
        <p class="text-xs text-gray-500 mt-1">v1.9 Accounting System</p>
        <?php if ($authorization && $authorization->hasRole('Admin')): ?>
        <p class="text-xs text-red-500 font-semibold mt-1">👑 Admin</p>
        <?php endif; ?>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4 space-y-2">
        <?php foreach ($menu_items as $item): ?>
            <?php 
            // Check if user has permission to view this menu item
            if ($item['permission'] && !auth_can($item['permission'])) {
                continue;  // Skip this menu item if no permission
            }
            
            // Check if current page matches
            $current_page = $_REQUEST['page'] ?? 'dashboard';
            $item_page = str_replace('?page=', '', $item['url']);
            $is_active = $current_page === $item_page;
            $active_class = $is_active ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100';
            ?>
            
            <a href="<?php echo htmlspecialchars($item['url']); ?>" 
               class="flex items-center px-4 py-2 rounded-lg transition <?php echo $active_class; ?>"
               @click="closeSidebar()">
                <i class="fas <?php echo $item['icon']; ?> w-5 mr-3"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Admin Section (only visible to users with admin permissions) -->
    <?php if (auth_can('users.view') || auth_can('settings.manage_roles') || auth_can('settings.manage_audit_log')): ?>
    <div class="px-4 py-4 border-t border-gray-200 bg-gray-50">
        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Administration</p>
        <nav class="space-y-2">
            <?php foreach ($admin_items as $item): ?>
                <?php 
                // Check admin permissions
                if ($item['permission'] && !auth_can($item['permission'])) {
                    continue;
                }
                
                $current_page = $_REQUEST['page'] ?? 'dashboard';
                $item_page = str_replace('?page=', '', $item['url']);
                $is_active = $current_page === $item_page;
                $active_class = $is_active ? 'bg-amber-100 text-amber-700 font-semibold' : 'text-gray-700 hover:bg-gray-100';
                ?>
                
                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                   class="flex items-center px-4 py-2 text-sm rounded-lg transition <?php echo $active_class; ?>"
                   @click="closeSidebar()">
                    <i class="fas <?php echo $item['icon']; ?> w-4 mr-3"></i>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php endif; ?>

    <!-- Settings Section -->
    <div class="absolute bottom-0 w-full p-4 border-t border-gray-200 bg-gray-50 space-y-2">
        <a href="?page=settings" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg transition">
            <i class="fas fa-cog w-5 mr-3"></i>
            <span class="text-sm">Settings</span>
        </a>
        
        <form method="POST" action="logout.php" class="block">
            <button type="submit" class="w-full flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span class="text-sm">Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="fixed inset-0 bg-black bg-opacity-50 lg:hidden z-30 transition"
     :class="{ 'opacity-0 pointer-events-none': !sidebarOpen, 'opacity-100': sidebarOpen }"
     @click="sidebarOpen = false"></div>
