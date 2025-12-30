<?php
$user_name = $_SESSION['name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$current_lang = $_SESSION['lang'] ?? 'en';
?>

<header class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Mobile Menu Toggle -->
        <button @click="toggleSidebar()" class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-bars text-xl text-gray-600"></i>
        </button>

        <!-- Right Actions -->
        <div class="flex items-center gap-4 ml-auto">
            <!-- Language Selector -->
            <div class="relative" @click.outside="langMenuOpen = false">
                <button @click="langMenuOpen = !langMenuOpen" 
                        class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 rounded-lg transition text-sm">
                    <i class="fas fa-globe text-gray-600"></i>
                    <span class="font-medium text-gray-700">
                        <?php echo $current_lang === 'th' ? '🇹🇭 ไทย' : '🇬🇧 English'; ?>
                    </span>
                </button>

                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 origin-top-right"
                     x-show="langMenuOpen" x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95">
                    
                    <form method="POST" action="lang.php" class="divide-y">
                        <button type="submit" name="chlang" value="th" 
                                class="w-full text-left px-4 py-3 hover:bg-gray-50 transition flex items-center gap-2 <?php echo $current_lang === 'th' ? 'bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                            <i class="fas fa-check text-sm <?php echo $current_lang === 'th' ? '' : 'invisible'; ?>"></i>
                            🇹🇭 ไทย (Thai)
                        </button>
                    </form>
                    
                    <form method="POST" action="lang.php" class="block">
                        <button type="submit" name="chlang" value="us" 
                                class="w-full text-left px-4 py-3 hover:bg-gray-50 transition flex items-center gap-2 <?php echo $current_lang === 'us' ? 'bg-blue-50 text-blue-700' : 'text-gray-700'; ?>">
                            <i class="fas fa-check text-sm <?php echo $current_lang === 'us' ? '' : 'invisible'; ?>"></i>
                            🇬🇧 English
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notifications -->
            <button class="relative p-2 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-bell text-xl text-gray-600"></i>
                <span class="absolute top-1 right-1 w-3 h-3 bg-red-500 rounded-full"></span>
            </button>

            <!-- User Menu -->
            <div class="relative flex items-center border-l border-gray-200 pl-4" @click.outside="userMenuOpen = false">
                <button @click="userMenuOpen = !userMenuOpen" 
                        class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded-lg transition">
                    <img src="<?php echo getAvatar($user_name, 32); ?>" 
                         alt="<?php echo htmlspecialchars($user_name); ?>" 
                         class="w-8 h-8 rounded-full">
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user_role); ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-gray-600"></i>
                </button>

                <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 origin-top-right"
                     x-show="userMenuOpen" x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95">
                    
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($_SESSION['email'] ?? 'No email'); ?></p>
                    </div>
                    
                    <!-- Menu Items -->
                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-user mr-2 text-gray-400"></i> My Profile
                    </a>
                    
                    <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition border-b border-gray-200">
                        <i class="fas fa-cog mr-2 text-gray-400"></i> Settings
                    </a>
                    
                    <!-- Logout -->
                    <form method="POST" action="logout.php" class="block">
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
