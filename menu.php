<?php
/**
 * Menu Component
 * Includes top navbar and sidebar navigation
 */

// Include the new top navbar component
include_once 'inc/top-navbar.php';
?>

        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0; display: none;">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php">iACC <?=$_SESSION['com_name']?></a></div>
            <!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
               
               <!-- <
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-tasks fa-fw"></i>  <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-tasks">
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 1</strong>
                                        <span class="pull-right text-muted">40% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 40%">
                                            <span class="sr-only">40% Complete (success)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 2</strong>
                                        <span class="pull-right text-muted">20% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
                                            <span class="sr-only">20% Complete</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 3</strong>
                                        <span class="pull-right text-muted">60% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                            <span class="sr-only">60% Complete (warning)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <strong>Task 4</strong>
                                        <span class="pull-right text-muted">80% Complete</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%">
                                            <span class="sr-only">80% Complete (danger)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a class="text-center" href="#">
                                <strong>See All Tasks</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-bell fa-fw"></i>  <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-alerts">
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-comment fa-fw"></i> New Comment
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-twitter fa-fw"></i> 3 New Followers
                                    <span class="pull-right text-muted small">12 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-envelope fa-fw"></i> Message Sent
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-tasks fa-fw"></i> New Task
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="#">
                                <div>
                                    <i class="fa fa-upload fa-fw"></i> Server Rebooted
                                    <span class="pull-right text-muted small">4 minutes ago</span>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a class="text-center" href="#">
                                <strong>See All Alerts</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </li>-->
                <!-- /.dropdown -->
                  <li><form action="lang.php" method="post"><button name="chlang" value="0" class="btn btn-default <?php $current_lang = isset($_SESSION['lang']) ? intval($_SESSION['lang']) : 0; if($current_lang == 0) echo "active";?>"><img src="images/us.jpg"> English</button>&nbsp;<button name="chlang" value="1" class="btn btn-default <?php if($current_lang == 1) echo "active";?>"><img src="images/th.jpg">  ภาษาไทย</button></form></li>
              
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i>  <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="#"><i class="fa fa-user fa-fw"></i> User Profile</a>
                        </li>
                        <li><a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a>
                        </li>
                        <li><a href="remoteuser.php"><i class="fa fa-home fa-fw"></i> Home</a>
                        </li>
                        <li class="divider"></li>
                        <li><a href="authorize.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            <!-- /.navbar-top-links -->

        </nav>
        <!-- /.navbar-static-top -->

        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="side-menu">
                    <li class="sidebar-search">
                        <div class="input-group custom-search-form"><form action="index.php?page=search"  method="post">
                            <input type="text" class="form-control" placeholder="Search...">
                            </form>
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span>
                      </div>
                        <!-- /input-group -->
                    </li>
                  
                  <!-- Dashboard -->
                  <li>
                        <a href="index.php?page=dashboard"><i class="fa fa-dashboard"></i> <?= isset($xml->dashboard) ? $xml->dashboard : 'Dashboard' ?></a>
                  </li>
                  
                  <?php if($_SESSION['com_id']!=""){?>
                  
                  <!-- Purchasing Request -->
                  <li>
                    <a href="#"><i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest ?? 'Purchase Request'?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=pr_list"><i class="fa fa-list"></i> <?=$xml->listpr ?? 'PR List'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=pr_create"><i class="fa fa-plus"></i> <?=$xml->prforvender ?? 'PR for Vendor'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=pr_make"><i class="fa fa-plus-circle"></i> <?=$xml->prforcustomer ?? 'PR for Customer'?></a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Sales & Orders -->
                    <li>
                        <a href="#"><i class="fa fa-shopping-cart"></i> <?=$xml->salesorders ?? 'Sales & Orders'?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=qa_list"><i class="fa fa-file-text-o"></i> <?=$xml->quotation ?? 'Quotation'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=po_list"><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder ?? 'Purchase Order'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=deliv_list"><i class="fa fa-truck"></i> <?=$xml->deliverynote ?? 'Delivery Note'?></a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Billing & Invoices -->
                    <li>
                        <a href="#"><i class="fa fa-file-text"></i> <?=$xml->billinginvoices ?? 'Billing & Invoices'?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=billing"><i class="glyphicon glyphicon-calendar"></i> <?=$xml->billingnote ?? 'Billing Note'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=compl_list"><i class="fa fa-file-text-o"></i> <?=$xml->invoice ?? 'Invoice'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=compl_list2"><i class="fa fa-file"></i> <?=$xml->taxinvoice ?? 'Tax Invoice'?></a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Payments -->
                    <li>
                        <a href="#"><i class="fa fa-credit-card"></i> <?=$xml->payments ?? 'Payments'?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=voucher_list"><i class="glyphicon glyphicon-tags" style="color:#e74c3c;"></i> <?=$xml->voucher ?? 'Voucher'?> <small class="text-muted">(Out)</small></a>
                            </li>
                            <li>
                                <a href="index.php?page=receipt_list"><i class="glyphicon glyphicon-usd" style="color:#27ae60;"></i> <?=$xml->receipt ?? 'Receipt'?> <small class="text-muted">(In)</small></a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="index.php?page=invoice_payments"><i class="fa fa-money"></i> <?=$xml->paymenttracking ?? 'Payment Tracking'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=payment"><i class="fa fa-bank"></i> <?=$xml->payment ?? 'Bank Accounts'?></a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Reports -->
                    <li>
                        <a href="index.php?page=report"><i class="glyphicon glyphicon-book"></i> <?=$xml->report ?? 'Reports'?></a>
                    </li>  
                    
                    <?php } ?>
                    
                    <!-- Master Data / Settings -->
                    <li>
                        <a href="#"><i class="fa fa-database"></i> <?=$xml->masterdata ?? 'Master Data'?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=company"><i class="fa fa-building"></i> <?=$xml->company ?? 'Company'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=category"><i class="fa fa-folder"></i> <?=$xml->category ?? 'Category'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=brand"><i class="fa fa-bookmark"></i> <?=$xml->brand ?? 'Brand'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=type"><i class="fa fa-cube"></i> <?=$xml->product ?? 'Product'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=mo_list"><i class="fa fa-cubes"></i> <?=$xml->model ?? 'Model'?></a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Admin Section (Level 2+) -->
                    <?php if (isset($_SESSION['user_level']) && $_SESSION['user_level'] >= 2): ?>
                    <li>
                        <a href="#"><i class="fa fa-shield"></i> <?=$xml->admin ?? 'Admin'?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=user"><i class="fa fa-users"></i> <?=$xml->user ?? 'Users'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=audit_log"><i class="fa fa-history"></i> <?=$xml->auditlog ?? 'Audit Log'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=monitoring"><i class="fa fa-dashboard"></i> <?=$xml->monitoring ?? 'System Monitor'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=containers"><i class="fa fa-server"></i> <?=$xml->containers ?? 'Containers'?></a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="index.php?page=payment_method_list"><i class="fa fa-credit-card-alt"></i> <?=$xml->paymentmethods ?? 'Payment Methods'?></a>
                            </li>
                            <li>
                                <a href="index.php?page=payment_gateway_config"><i class="fa fa-cogs"></i> <?=$xml->gatewayconfig ?? 'Gateway Config'?></a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                  
                </ul>
                <!-- /#side-menu -->
            </div>
            <!-- /.sidebar-collapse -->
        </nav>
        <!-- /.navbar-static-side -->