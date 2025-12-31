<?php
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
    <script src="js/tooltip.js"></script>

		<?php include_once "css.php";?>
</head>

<body >

    <div id="wrapper">
		<?php include_once "menu.php";?>

        <div id="page-wrapper">
            <div class="row">
                <?php 
				if($_REQUEST[page]=="company")				
				include_once "company-list.php";
				if($_REQUEST[page]=="category")				
				include_once "category-list.php";
				
				if($_REQUEST[page]=="type")				
				include_once "type-list.php";
				
				if($_REQUEST[page]=="receipt_list")				
				include_once "rep-list.php";
				if($_REQUEST[page]=="rep_make")				
				include_once "rep-make.php";
				if($_REQUEST[page]=="brand")				
			include_once "brand-list.php";
				if($_REQUEST[page]=="pr_list")				
				include_once "pr-list.php";
				if($_REQUEST[page]=="pr_create")				
				include_once "pr-create.php";
				if($_REQUEST[page]=="pr_make")				
				include_once "pr-make.php";
				if($_REQUEST[page]=="po_make")				
				include_once "po-make.php";
				if($_REQUEST[page]=="po_list")				
				include_once "po-list.php";
				if($_REQUEST[page]=="voucher_list")				
				include_once "vou-list.php";
				if($_REQUEST[page]=="voc_make")				
				include_once "voc-make.php";
				if($_REQUEST[page]=="po_edit")				
				include_once "po-edit.php";
					if($_REQUEST[page]=="po_view")				
				include_once "po-view.php";
					if($_REQUEST[page]=="po_deliv")				
				include_once "po-deliv.php";
				if($_REQUEST[page]=="deliv_list")				
				include_once "deliv-list.php";
				if($_REQUEST[page]=="deliv_view")				
				include_once "deliv-view.php";
				if($_REQUEST[page]=="deliv_make")				
				include_once "deliv-make.php";
				if($_REQUEST[page]=="deliv_edit")				
				include_once "deliv-edit.php";
				if($_REQUEST[page]=="compl_list")				
				include_once "compl-list.php";
				if($_REQUEST[page]=="payment")				
				include_once "payment-list.php";
				if($_REQUEST[page]=="compl_view")				
				include_once "compl-view.php";
				if($_REQUEST[page]=="compl_list2")				
				include_once "compl-list2.php";
				if($_REQUEST[page]=="qa_list")				
				include_once "qa-list.php";
				if($_REQUEST[page]=="mo_list")				
				include_once "mo-list.php";
				if($_REQUEST[page]=="report")				
				include_once "report.php";
				
				?>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
          
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->

    </div>
     <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      Error
    </div>
  </div>
</div>

    <!-- /#wrapper -->
		<?php include_once "script.php";?>
  
</body>

</html>
