    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">

    <!-- Page-Level Plugin CSS - Dashboard -->
    <link href="css/plugins/morris/morris-0.4.3.min.css" rel="stylesheet">
    <link href="css/plugins/timeline/timeline.css" rel="stylesheet">

    <!-- SB Admin CSS - Include with every page -->
    <link href="css/sb-admin.css" rel="stylesheet">
    
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
    </style>