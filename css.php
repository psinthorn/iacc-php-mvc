    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">

    <title>CMS</title>

    <!-- Core CSS - Include with every page -->
    <?php 
    // Use Bootstrap 5 for new pages, Bootstrap 3 for legacy compatibility
    $useBootstrap5 = isset($USE_BOOTSTRAP_5) && $USE_BOOTSTRAP_5;
    if ($useBootstrap5): ?>
    <link href="css/bootstrap-5.3.3.min.css" rel="stylesheet">
    <?php else: ?>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Font Awesome 4.7.0 - Use CDN for complete icon set -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <!-- Mobile-First Responsive Styles -->
    <link href="css/mobile-first.css" rel="stylesheet">

    <!-- Page-Level Plugin CSS - Dashboard -->
    <link href="css/plugins/morris/morris-0.4.3.min.css" rel="stylesheet">
    <link href="css/plugins/timeline/timeline.css" rel="stylesheet">

    <!-- SB Admin CSS - Include with every page -->
    <link href="css/sb-admin.css" rel="stylesheet">
    
    <!-- Skeleton Loader - Global loading animation -->
    <link href="css/skeleton-loader.css" rel="stylesheet">
    
    <!-- Smart Dropdown Component - Searchable & Sortable dropdowns -->
    <link href="css/smart-dropdown.css" rel="stylesheet">
    
    <!-- jQuery - Use 3.x with 1.x migrate for compatibility -->
    <?php if ($useBootstrap5): ?>
    <script src="js/jquery-3.7.1.min.js"></script>
    <?php else: ?>
    <script src="js/jquery-1.10.2.js"></script>
    <?php endif; ?>
    <script src="js/ajaxpagefetcher.js"></script>
    
    
    <style type="text/css">
    #box{ width:33.3%; float:left; padding:5px;  }
	 #box4{ width:100%; float:left; padding:5px;  }
	#box2{ width:20%; padding:5px;float:left; }
	#box3{width:13%;padding:5px;float:left; }
    @media only screen and (min-width: 480px) and (max-width: 959px) {
	#box{ width:50%; float:left; }
	#box2{ width:50%; float:left; }
	#box3{ width:50%; float:left; }
	}
    @media only screen and (max-width: 479px) {
	#box{ width:100%;}
	#box2{ width:100%;}
	#box3{ width:100%;}
	}
    
    /* Top Navbar Layout Adjustments */
    body.has-top-nav {
        padding-top: 60px;
    }
    
    body.has-top-nav .navbar-static-side {
        position: fixed;
        top: 60px;
        bottom: 0;
        left: 0;
        z-index: 1000;
        overflow-y: auto;
        background-color: #f8f9fa;
        width: 250px;
        border-right: 1px solid #e9ecef;
    }
    
    /* Sidebar menu styling for light background */
    body.has-top-nav .navbar-static-side .nav > li > a {
        color: #333;
    }
    
    body.has-top-nav .navbar-static-side .nav > li > a:hover,
    body.has-top-nav .navbar-static-side .nav > li > a:focus {
        background-color: #e9ecef;
        color: #8e44ad;
    }
    
    body.has-top-nav .navbar-static-side .nav > li.active > a {
        background-color: #8e44ad;
        color: #fff;
    }
    
    body.has-top-nav .navbar-static-side .nav-second-level > li > a {
        color: #555;
    }
    
    body.has-top-nav .navbar-static-side .nav-second-level > li > a:hover {
        color: #8e44ad;
    }
    
    body.has-top-nav .navbar-static-side .sidebar-search input {
        background-color: #fff;
        border: 1px solid #ddd;
        color: #333;
    }
    
    body.has-top-nav .navbar-static-side .sidebar-search input::placeholder {
        color: #999;
    }
    
    body.has-top-nav #page-wrapper {
        margin-left: 250px;
        min-height: calc(100vh - 60px);
        padding-top: 24px;
    }
    
    @media (max-width: 768px) {
        body.has-top-nav .navbar-static-side {
            left: -250px;
            transition: left 0.3s ease;
        }
        
        body.has-top-nav .navbar-static-side.mobile-visible {
            left: 0;
        }
        
        body.has-top-nav #page-wrapper {
            margin-left: 0;
            padding-top: 20px;
        }
    }
    </style>