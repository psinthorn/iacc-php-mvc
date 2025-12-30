<style>
    /* ============ Modern Navbar Styling ============ */
    .navbar-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: none;
        padding: 0;
        margin-bottom: 0;
    }

    .navbar-modern .navbar-brand {
        color: white !important;
        font-size: 20px;
        font-weight: 700;
        padding: 15px 20px;
        letter-spacing: 0.5px;
    }

    .navbar-modern .navbar-brand:hover {
        color: #f0f0f0 !important;
    }

    .navbar-modern .navbar-toggle {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        padding: 8px 12px;
    }

    .navbar-modern .navbar-toggle:hover,
    .navbar-modern .navbar-toggle:focus {
        background: rgba(255, 255, 255, 0.3);
    }

    .navbar-modern .navbar-toggle .icon-bar {
        background-color: white;
    }

    /* Top Right Links */
    .navbar-top-links {
        padding-right: 15px;
    }

    .navbar-top-links > li {
        display: inline-block;
        margin-left: 5px;
    }

    .navbar-top-links > li > a {
        color: white;
        padding: 15px 12px;
        font-size: 14px;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .navbar-top-links > li > a:hover {
        color: #f0f0f0;
        background: rgba(255, 255, 255, 0.1);
    }

    .navbar-top-links .dropdown-menu {
        right: 0;
        left: auto;
        top: 100%;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        border: none;
        margin-top: 5px;
    }

    .navbar-top-links .dropdown-menu > li > a {
        padding: 12px 20px;
        color: #333;
        transition: all 0.2s ease;
    }

    .navbar-top-links .dropdown-menu > li > a:hover {
        background-color: #f8f9fa;
        padding-left: 25px;
    }

    .navbar-top-links .dropdown-menu > li.divider {
        background-color: #e9ecef;
        margin: 5px 0;
    }

    /* Language Buttons */
    .lang-selector {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .lang-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .lang-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
    }

    .lang-btn.active {
        background: rgba(255, 255, 255, 0.4);
        border-color: white;
    }

    .lang-btn img {
        height: 14px;
        margin-right: 4px;
        border-radius: 2px;
    }

    /* User Profile Dropdown */
    .user-profile-dropdown {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.5);
    }

    /* ============ MINIMAL SIDEBAR STYLING ============ */
    .navbar-static-side {
        background: #34495e;
        border: none;
        transition: all 0.3s ease;
        width: 70px;
        position: fixed;
        top: 50px;
        left: 0;
        bottom: 0;
        z-index: 999;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .navbar-static-side.expanded {
        width: 220px;
    }

    .sidebar-collapse {
        padding: 0;
    }

    /* Toggle Button */
    .sidebar-toggle {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 70px;
        height: 50px;
        background: #2c3e50;
        border-bottom: 1px solid #1a252f;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .sidebar-toggle:hover {
        background: #1a252f;
    }

    .sidebar-toggle i {
        color: #667eea;
        font-size: 18px;
    }

    /* Main Menu */
    #side-menu {
        margin-bottom: 0;
        padding: 0;
        list-style: none;
    }

    #side-menu > li {
        border-bottom: 1px solid #2c3e50;
    }

    #side-menu > li > a {
        color: #bdc3c7;
        padding: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        text-decoration: none;
        font-size: 16px;
        position: relative;
        cursor: pointer;
    }

    #side-menu > li > a:hover {
        background: #2c3e50;
        color: #667eea;
        border-left-color: #667eea;
    }

    #side-menu > li.active > a {
        background: #2c3e50;
        color: #667eea;
        border-left-color: #667eea;
    }

    /* Icon Styling */
    #side-menu > li > a i {
        margin: 0;
        min-width: 24px;
        text-align: center;
    }

    /* Text Labels (hidden by default, shown when expanded) */
    .menu-label {
        margin-left: 15px;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        transition: opacity 0.3s ease;
        max-width: 0;
        overflow: hidden;
    }

    .navbar-static-side.expanded .menu-label {
        opacity: 1;
        max-width: 150px;
    }

    /* Submenu */
    #side-menu .nav-second-level {
        background: #2c3e50;
        display: none;
        margin: 0;
        padding: 0;
        list-style: none;
        border-left: 3px solid #667eea;
    }

    #side-menu .nav-second-level.in {
        display: block;
    }

    #side-menu .nav-second-level > li > a {
        padding: 12px 15px 12px 20px;
        color: #bdc3c7;
        font-size: 12px;
        border-left: none;
        display: flex;
        align-items: center;
        white-space: nowrap;
        transition: all 0.3s ease;
    }

    #side-menu .nav-second-level > li > a:hover {
        background: #1a252f;
        color: #667eea;
        padding-left: 25px;
    }

    #side-menu .nav-second-level > li.active > a {
        color: #667eea;
        background: #1a252f;
    }

    #side-menu .nav-second-level > li > a i {
        margin-right: 8px;
        min-width: 14px;
    }

    /* Hide submenu text when sidebar is collapsed */
    .navbar-static-side:not(.expanded) #side-menu .nav-second-level {
        display: none !important;
    }

    .navbar-static-side:not(.expanded) .menu-label {
        display: none;
    }

    /* Tooltip for collapsed state */
    .sidebar-tooltip {
        position: absolute;
        left: 80px;
        top: 50%;
        transform: translateY(-50%);
        background: #2c3e50;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-left: 3px solid #667eea;
        z-index: 1000;
    }

    .navbar-static-side:not(.expanded) #side-menu > li > a:hover .sidebar-tooltip {
        opacity: 1;
    }

    /* Adjust main content when sidebar expands */
    .navbar-static-side.expanded ~ .page-wrapper {
        margin-left: 150px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .navbar-static-side {
            width: 70px;
            top: 50px;
        }

        .navbar-static-side.expanded {
            width: 100%;
            position: fixed;
        }

        #side-menu > li > a {
            padding: 12px;
        }
    }

    /* Search box styling */
    .sidebar-search {
        padding: 10px 5px;
        border-bottom: 1px solid #2c3e50;
        display: none;
    }

    .navbar-static-side.expanded .sidebar-search {
        display: block;
        padding: 10px 10px;
    }

    .custom-search-form {
        display: flex;
        gap: 3px;
    }

    .custom-search-form input {
        border-radius: 4px;
        border: 1px solid #2c3e50;
        background: #2c3e50;
        color: #ecf0f1;
        padding: 8px 10px;
        font-size: 12px;
        flex: 1;
    }

    .custom-search-form input::placeholder {
        color: #7f8c8d;
    }

    .custom-search-form button {
        background: #667eea;
        border: none;
        color: white;
        padding: 8px 10px;
        border-radius: 4px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .custom-search-form button:hover {
        background: #764ba2;
    }

    /* Section titles in expanded mode */
    .nav-group-title {
        padding: 10px 15px;
        color: #7f8c8d;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 8px;
        display: none;
    }

    .navbar-static-side.expanded .nav-group-title {
        display: block;
    }

    /* Arrow styling */
    .fa.arrow {
        float: right;
        transition: transform 0.3s ease;
        margin-left: 10px;
    }

    .nav-second-level.in ~ .fa.arrow {
        transform: rotate(180deg);
    }
</style>

<!-- ============ TOP NAVBAR ============ -->
<nav class="navbar navbar-modern navbar-default navbar-static-top" role="navigation">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="index.php">
            <i class="fa fa-cube"></i> iACC <?=($_SESSION['com_name'] ?? '')?$_SESSION['com_name']:'System'?>
        </a>
    </div>

    <ul class="nav navbar-top-links navbar-right">
        <!-- Language Selector -->
        <li>
            <form action="lang.php" method="post" style="display: flex; gap: 5px; margin: 0;">
                <button type="submit" name="chlang" value="0" class="lang-btn <?php if($lg=="us") echo "active";?>">
                    <img src="images/us.jpg" alt="English"> EN
                </button>
                <button type="submit" name="chlang" value="1" class="lang-btn <?php if($lg=="th") echo "active";?>">
                    <img src="images/th.jpg" alt="ไทย"> TH
                </button>
            </form>
        </li>

        <!-- User Profile Dropdown -->
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <div class="user-profile-dropdown">
                    <div class="user-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <span class="hidden-sm hidden-xs">
                        <?php echo $_SESSION['usr_name'] ?? 'User'; ?>
                    </span>
                    <i class="fa fa-caret-down"></i>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-user">
                <li>
                    <a href="#"><i class="fa fa-user fa-fw"></i> User Profile</a>
                </li>
                <li>
                    <a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a>
                </li>
                <li>
                    <a href="remoteuser.php"><i class="fa fa-home fa-fw"></i> Home</a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="authorize.php" style="color: #ff6b6b;">
                        <i class="fa fa-sign-out fa-fw"></i> Logout
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<!-- ============ MINIMAL SIDEBAR ============ -->
<nav class="navbar-default navbar-static-side" id="sidebar" role="navigation">
    <div class="sidebar-collapse">
        <!-- Toggle Button -->
        <div class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fa fa-bars"></i>
        </div>

        <!-- Search -->
        <li class="sidebar-search">
            <div class="input-group custom-search-form">
                <form action="index.php?page=search" method="post" style="width: 100%; display: flex; gap: 3px;">
                    <input type="text" class="form-control" placeholder="Search...">
                    <button class="btn btn-default" type="button">
                        <i class="fa fa-search"></i>
                    </button>
                </form>
            </div>
        </li>

        <ul class="nav" id="side-menu">
            <!-- Dashboard -->
            <li>
                <a href="index.php?page=dashboard">
                    <i class="fa fa-tachometer-alt"></i>
                    <span class="menu-label">Dashboard</span>
                    <span class="sidebar-tooltip">Dashboard</span>
                </a>
            </li>

            <!-- Configuration -->
            <div class="nav-group-title">Configuration</div>
            <li>
                <a href="index.php?page=company">
                    <i class="fa fa-building"></i>
                    <span class="menu-label"><?=$xml->company?></span>
                    <span class="sidebar-tooltip"><?=$xml->company?></span>
                </a>
            </li>
            <li>
                <a href="index.php?page=user">
                    <i class="fa fa-users"></i>
                    <span class="menu-label"><?=$xml->user?></span>
                    <span class="sidebar-tooltip"><?=$xml->user?></span>
                </a>
            </li>
            <li>
                <a href="index.php?page=category">
                    <i class="fa fa-list"></i>
                    <span class="menu-label"><?=$xml->category?></span>
                    <span class="sidebar-tooltip"><?=$xml->category?></span>
                </a>
            </li>
            <li>
                <a href="index.php?page=band">
                    <i class="fa fa-tag"></i>
                    <span class="menu-label"><?=$xml->brand?></span>
                    <span class="sidebar-tooltip"><?=$xml->brand?></span>
                </a>
            </li>
            <li>
                <a href="index.php?page=type">
                    <i class="fa fa-box"></i>
                    <span class="menu-label"><?=$xml->product?></span>
                    <span class="sidebar-tooltip"><?=$xml->product?></span>
                </a>
            </li>
            <li>
                <a href="index.php?page=mo_list">
                    <i class="fa fa-cube"></i>
                    <span class="menu-label"><?=$xml->model?></span>
                    <span class="sidebar-tooltip"><?=$xml->model?></span>
                </a>
            </li>
            <?php if(isset($_SESSION['com_id']) && $_SESSION['com_id']!=""){?>
            <li>
                <a href="index.php?page=payment">
                    <i class="fa fa-credit-card"></i>
                    <span class="menu-label"><?=$xml->payment?></span>
                    <span class="sidebar-tooltip"><?=$xml->payment?></span>
                </a>
            </li>
            <?php } ?>

            <!-- Purchasing -->
            <?php if(isset($_SESSION['com_id']) && $_SESSION['com_id']!=""){?>
            <div class="nav-group-title">Purchasing</div>
            <li>
                <a href="#" onclick="toggleSubmenu(event, 'submenu-pr')">
                    <i class="fa fa-pencil-square-o"></i>
                    <span class="menu-label"><?=$xml->purchasingrequest?></span>
                    <span class="sidebar-tooltip"><?=$xml->purchasingrequest?></span>
                    <i class="fa fa-chevron-right arrow" style="position: absolute; right: 10px;"></i>
                </a>
                <ul class="nav nav-second-level" id="submenu-pr">
                    <li>
                        <a href="index.php?page=pr_list"><i class="fa fa-list"></i> <?=$xml->listpr?></a>
                    </li>
                    <li>
                        <a href="index.php?page=pr_create"><i class="fa fa-file"></i> <?=$xml->prforvender?></a>
                    </li>
                    <li>
                        <a href="index.php?page=pr_make"><i class="fa fa-file"></i> <?=$xml->prforcustomer?></a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="index.php?page=qa_list">
                    <i class="fa fa-quote-left"></i>
                    <span class="menu-label"><?=$xml->quotation?></span>
                    <span class="sidebar-tooltip"><?=$xml->quotation?></span>
                </a>
            </li>

            <li>
                <a href="index.php?page=po_list">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="menu-label"><?=$xml->purchasingorder?></span>
                    <span class="sidebar-tooltip"><?=$xml->purchasingorder?></span>
                </a>
            </li>

            <!-- Logistics -->
            <div class="nav-group-title">Logistics</div>
            <li>
                <a href="index.php?page=deliv_list">
                    <i class="fa fa-truck"></i>
                    <span class="menu-label"><?=$xml->deliverynote?></span>
                    <span class="sidebar-tooltip"><?=$xml->deliverynote?></span>
                </a>
            </li>

            <!-- Finance -->
            <div class="nav-group-title">Finance</div>
            <li>
                <a href="index.php?page=voucher_list">
                    <i class="fa fa-ticket"></i>
                    <span class="menu-label"><?=$xml->voucher?></span>
                    <span class="sidebar-tooltip"><?=$xml->voucher?></span>
                </a>
            </li>

            <li>
                <a href="index.php?page=receipt_list">
                    <i class="fa fa-receipt"></i>
                    <span class="menu-label"><?=$xml->receipt?></span>
                    <span class="sidebar-tooltip"><?=$xml->receipt?></span>
                </a>
            </li>

            <li>
                <a href="index.php?page=billing">
                    <i class="fa fa-calendar"></i>
                    <span class="menu-label"><?=$xml->billingnote?></span>
                    <span class="sidebar-tooltip"><?=$xml->billingnote?></span>
                </a>
            </li>

            <!-- Documents -->
            <div class="nav-group-title">Documents</div>
            <li>
                <a href="index.php?page=compl_list">
                    <i class="fa fa-receipt"></i>
                    <span class="menu-label"><?=$xml->invoice?></span>
                    <span class="sidebar-tooltip"><?=$xml->invoice?></span>
                </a>
            </li>

            <li>
                <a href="index.php?page=compl_list2">
                    <i class="fa fa-file-invoice-dollar"></i>
                    <span class="menu-label"><?=$xml->taxinvoice?></span>
                    <span class="sidebar-tooltip"><?=$xml->taxinvoice?></span>
                </a>
            </li>

            <!-- Reports -->
            <div class="nav-group-title">Reports</div>
            <li>
                <a href="index.php?page=report">
                    <i class="fa fa-book"></i>
                    <span class="menu-label"><?=$xml->report?></span>
                    <span class="sidebar-tooltip"><?=$xml->report?></span>
                </a>
            </li>

            <?php } ?>
        </ul>
    </div>
</nav>

<script>
    // Toggle Sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('expanded');
        localStorage.setItem('sidebarExpanded', sidebar.classList.contains('expanded'));
    }

    // Toggle Submenu
    function toggleSubmenu(event, submenuId) {
        event.preventDefault();
        const submenu = document.getElementById(submenuId);
        submenu.classList.toggle('in');
    }

    // Restore sidebar state
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const isExpanded = localStorage.getItem('sidebarExpanded') === 'true';
        if (isExpanded) {
            sidebar.classList.add('expanded');
        }

        // Highlight active menu item
        const currentPage = new URLSearchParams(window.location.search).get('page');
        if (currentPage) {
            document.querySelectorAll('#side-menu a').forEach(link => {
                if (link.href.includes('page=' + currentPage)) {
                    link.closest('li').classList.add('active');
                    // Show parent submenu if exists
                    const parent = link.closest('.nav-second-level');
                    if (parent) {
                        parent.classList.add('in');
                    }
                }
            });
        }
    });
</script>
