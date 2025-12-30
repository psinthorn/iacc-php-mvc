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

    /* ============ Sidebar Styling ============ */
    .navbar-static-side {
        background: #2c3e50;
        border: none;
    }

    .sidebar-collapse {
        padding: 0;
    }

    #side-menu {
        margin-bottom: 0;
        padding: 0;
    }

    #side-menu > li > a {
        color: #ecf0f1;
        padding: 15px 20px;
        display: block;
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    #side-menu > li > a:hover {
        background: #34495e;
        border-left-color: #667eea;
        color: #667eea;
    }

    #side-menu > li > a.active {
        background: #34495e;
        border-left-color: #667eea;
        color: #667eea;
    }

    #side-menu > li > a i {
        margin-right: 12px;
        width: 18px;
        text-align: center;
    }

    /* Sidebar Search */
    .sidebar-search {
        padding: 15px 20px;
        border-bottom: 1px solid #34495e;
    }

    .custom-search-form input {
        border-radius: 4px;
        border: 1px solid #34495e;
        background: #34495e;
        color: #ecf0f1;
        padding: 10px 12px;
        font-size: 13px;
    }

    .custom-search-form input::placeholder {
        color: #95a5a6;
    }

    .custom-search-form button {
        background: #667eea;
        border: none;
        color: white;
        padding: 10px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .custom-search-form button:hover {
        background: #764ba2;
    }

    /* Menu Groups */
    .nav-group-title {
        padding: 12px 20px 8px;
        color: #95a5a6;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 10px;
    }

    /* Second Level Menu */
    #side-menu .nav-second-level {
        background: #34495e;
        display: none;
        margin: 0;
        padding: 0;
    }

    #side-menu .nav-second-level.in {
        display: block;
    }

    #side-menu .nav-second-level > li > a {
        padding: 10px 20px 10px 50px;
        border-left: none;
        color: #bdc3c7;
        font-size: 13px;
    }

    #side-menu .nav-second-level > li > a:hover {
        background: #2c3e50;
        border-left-color: transparent;
        color: #667eea;
    }

    #side-menu .nav-second-level > li.active > a {
        color: #667eea;
        background: #2c3e50;
    }

    /* Arrow Icon */
    .fa.arrow {
        float: right;
        margin-top: 2px;
    }

    .nav-second-level.in > li:first-child > a {
        border-top: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .navbar-top-links {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .lang-selector {
            flex-direction: column;
            gap: 4px;
        }

        .lang-btn {
            font-size: 10px;
            padding: 4px 8px;
        }

        #side-menu > li > a {
            font-size: 13px;
            padding: 12px 15px;
        }

        #side-menu .nav-second-level > li > a {
            padding: 8px 15px 8px 40px;
        }
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

<!-- ============ SIDEBAR ============ -->
<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav" id="side-menu">
            <!-- Dashboard -->
            <li>
                <a href="index.php?page=dashboard">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <!-- Search -->
            <li class="sidebar-search">
                <div class="input-group custom-search-form">
                    <form action="index.php?page=search" method="post" style="width: 100%; display: flex; gap: 5px;">
                        <input type="text" class="form-control" placeholder="Search..." style="flex: 1;">
                        <button class="btn btn-default" type="button" style="padding: 8px 12px;">
                            <i class="fa fa-search"></i>
                        </button>
                    </form>
                </div>
            </li>

            <!-- Configuration Section -->
            <li>
                <div class="nav-group-title"><i class="fa fa-cogs"></i> Configuration</div>
            </li>
            <li>
                <a href="index.php?page=company">
                    <i class="fa fa-building"></i> <?=$xml->company?>
                </a>
            </li>
            <li>
                <a href="index.php?page=user">
                    <i class="fa fa-users"></i> <?=$xml->user?>
                </a>
            </li>
            <li>
                <a href="index.php?page=category">
                    <i class="fa fa-list"></i> <?=$xml->category?>
                </a>
            </li>
            <li>
                <a href="index.php?page=band">
                    <i class="fa fa-tag"></i> <?=$xml->brand?>
                </a>
            </li>
            <li>
                <a href="index.php?page=type">
                    <i class="fa fa-box"></i> <?=$xml->product?>
                </a>
            </li>
            <li>
                <a href="index.php?page=mo_list">
                    <i class="fa fa-cube"></i> <?=$xml->model?>
                </a>
            </li>
            <?php if(isset($_SESSION['com_id']) && $_SESSION['com_id']!=""){?>
            <li>
                <a href="index.php?page=payment">
                    <i class="fa fa-credit-card"></i> <?=$xml->payment?>
                </a>
            </li>
            <?php } ?>

            <!-- Purchasing Section -->
            <?php if(isset($_SESSION['com_id']) && $_SESSION['com_id']!=""){?>
            <li>
                <div class="nav-group-title"><i class="fa fa-shopping-cart"></i> Purchasing</div>
            </li>
            <li>
                <a href="#" onclick="toggleSubmenu(event)">
                    <i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest?>
                    <span class="fa arrow"></span>
                </a>
                <ul class="nav nav-second-level" id="submenu-pr">
                    <li>
                        <a href="index.php?page=pr_list">
                            <i class="fa fa-list"></i> <?=$xml->listpr?>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=pr_create">
                            <i class="fa fa-file"></i> <?=$xml->prforvender?>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=pr_make">
                            <i class="fa fa-file"></i> <?=$xml->prforcustomer?>
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="index.php?page=qa_list">
                    <i class="fa fa-quote-left"></i> <?=$xml->quotation?>
                </a>
            </li>

            <li>
                <a href="index.php?page=po_list">
                    <i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder?>
                </a>
            </li>

            <!-- Logistics Section -->
            <li>
                <div class="nav-group-title"><i class="fa fa-truck"></i> Logistics</div>
            </li>

            <li>
                <a href="index.php?page=deliv_list">
                    <i class="fa fa-truck"></i> <?=$xml->deliverynote?>
                </a>
            </li>

            <!-- Finance Section -->
            <li>
                <div class="nav-group-title"><i class="fa fa-money"></i> Finance</div>
            </li>

            <li>
                <a href="index.php?page=voucher_list">
                    <i class="fa fa-ticket"></i> <?=$xml->voucher?>
                </a>
            </li>

            <li>
                <a href="index.php?page=receipt_list">
                    <i class="fa fa-receipt"></i> <?=$xml->receipt?>
                </a>
            </li>

            <li>
                <a href="index.php?page=billing">
                    <i class="fa fa-calendar"></i> <?=$xml->billingnote?>
                </a>
            </li>

            <!-- Documents Section -->
            <li>
                <div class="nav-group-title"><i class="fa fa-file-alt"></i> Documents</div>
            </li>

            <li>
                <a href="index.php?page=compl_list">
                    <i class="fa fa-receipt"></i> <?=$xml->invoice?>
                </a>
            </li>

            <li>
                <a href="index.php?page=compl_list2">
                    <i class="fa fa-file-invoice-dollar"></i> <?=$xml->taxinvoice?>
                </a>
            </li>

            <!-- Reports Section -->
            <li>
                <div class="nav-group-title"><i class="fa fa-chart-bar"></i> Reports</div>
            </li>

            <li>
                <a href="index.php?page=report">
                    <i class="fa fa-book"></i> <?=$xml->report?>
                </a>
            </li>

            <?php } ?>
        </ul>
    </div>
</nav>

<script>
    function toggleSubmenu(event) {
        event.preventDefault();
        const parentLi = event.target.closest('li');
        const submenu = parentLi.querySelector('.nav-second-level');
        const arrow = parentLi.querySelector('.fa.arrow');
        
        if (submenu) {
            submenu.classList.toggle('in');
            if (arrow) {
                arrow.style.transform = submenu.classList.contains('in') ? 'rotate(180deg)' : 'rotate(0deg)';
                arrow.style.transition = 'transform 0.3s ease';
            }
        }
    }

    // Highlight active menu item
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = new URLSearchParams(window.location.search).get('page');
        if (currentPage) {
            document.querySelectorAll('#side-menu a').forEach(link => {
                if (link.href.includes('page=' + currentPage)) {
                    link.closest('li').classList.add('active');
                    // Also show parent submenu if exists
                    const parent = link.closest('.nav-second-level');
                    if (parent) {
                        parent.classList.add('in');
                        const arrow = parent.closest('li').querySelector('.fa.arrow');
                        if (arrow) {
                            arrow.style.transform = 'rotate(180deg)';
                        }
                    }
                }
            });
        }
    });
</script>
