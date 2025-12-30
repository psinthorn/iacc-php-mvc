<?php

// Initialize authorization system
require_once __DIR__ . '/../../classes/Authorization.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

// Create global authorization object
if (!isset($authorization)) {
    $authorization = new Authorization($db ?? null, $_SESSION['user_id'] ?? 0);
}

// Create global audit logger
if (!isset($audit_log)) {
    $audit_log = new AuditLog($db ?? null, $_SESSION['user_id'] ?? 0);
}

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? $page_title . ' - iACC v1.9' : 'iACC v1.9 - Accounting System'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo base_url('public/css/app.css'); ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .font-thai { font-family: 'Sarabun', sans-serif; }
    </style>
    
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex h-screen bg-gray-100" x-data="appData()">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-auto">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash_success'])): ?>
                    <?php include __DIR__ . '/../components/alert-success.php'; ?>
                    <?php unset($_SESSION['flash_success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['flash_error'])): ?>
                    <?php include __DIR__ . '/../components/alert-error.php'; ?>
                    <?php unset($_SESSION['flash_error']); ?>
                <?php endif; ?>
                
                <!-- Page Header -->
                <?php if (isset($page_title)): ?>
                    <div class="px-6 pt-6">
                        <h2 class="page-title"><?php echo $page_title; ?></h2>
                        <?php if (isset($page_subtitle)): ?>
                            <p class="page-subtitle"><?php echo $page_subtitle; ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Content -->
                <div class="p-6">
                    <?php echo $content ?? ''; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Container -->
    <div id="modal-container"></div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 space-y-4 z-50"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <script>
        function appData() {
            return {
                sidebarOpen: window.innerWidth >= 1024,
                langMenuOpen: false,
                userMenuOpen: false,
                
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },
                
                closeSidebar() {
                    if (window.innerWidth < 1024) {
                        this.sidebarOpen = false;
                    }
                }
            };
        }

        // Toast notification function
        function showToast(message, type = 'success', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
            
            toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg animate-slide-in flex items-center gap-2`;
            toast.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, duration);
        }

        // Handle clicks outside sidebars/menus
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const langMenu = document.getElementById('lang-menu-dropdown');
            const userMenu = document.getElementById('user-menu-dropdown');
        });
    </script>
    
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>
