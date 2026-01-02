
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
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
                  
                  
                 <li>
                        <a href="#"><i class="fa fa-cogs"></i> 
<?=$xml->generalinformation?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=company"><?=$xml->company?></a>
                            </li>
                            <li>
                                <a href="index.php?page=user"><?=$xml->user?></a>
                            </li>
                             <li>
                             
                                <a href="index.php?page=category"><?=$xml->category?></a>
                            </li>
                             <li>
                                <a href="index.php?page=band"><?=$xml->brand?></a>
                            </li>
                              <li>
                                <a href="index.php?page=type"><?=$xml->product?></a>
                            </li>
                               <li>
                                <a href="index.php?page=mo_list"><?=$xml->model?></a>
                            </li>
                            
                               <?php if($_SESSION['com_id']!=""){?>   <li>
                                <a href="index.php?page=payment"><?=$xml->payment?></a>
                            </li> <?php } ?>
                   </ul>
                    </li>
                  <?php if($_SESSION['com_id']!=""){?> 
                  <li>
                    <a href="#"><i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest?><span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="index.php?page=pr_list"> <?=$xml->listpr?></a>
                            </li>
                            <li>
                                <a href="index.php?page=pr_create"> <?=$xml->prforvender?></a>
                            </li>
                            <li>
                                <a href="index.php?page=pr_make"> <?=$xml->prforcustomer?></a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="index.php?page=qa_list"><i class="fa fa-shopping-cart"></i> <?=$xml->quotation?></span></a>
                    </li>
                    <li>
                        <a href="index.php?page=po_list"><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder?></span></a>
                       
                    </li>
                    <li>
                        <a href="index.php?page=deliv_list"><i class="fa fa-truck"></i> <?=$xml->deliverynote?></a>
                       
                    </li>
                    <li>
                        <a href="index.php?page=voucher_list"><i class="glyphicon glyphicon-tags"></i> <?=$xml->voucher?></a></li>
                          <li>
                        <a href="index.php?page=receipt_list"><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt?></a>
                       
                    </li>     
                 
                 
                    <li>
                        <a href="index.php?page=billing"><i class="glyphicon glyphicon-calendar"></i> <?=$xml->billingnote?></a>
                      
                    </li>
                    <li>
                        <a href="index.php?page=compl_list"><i class="fa fa-thumbs-up"></i> <?=$xml->invoice?></a>
                       
                    </li>
                    
                    

                    
                    
                   <li>
                        <a href="index.php?page=compl_list2"><i class="fa fa-thumbs-up"></i> <?=$xml->taxinvoice?></a>
                    
                    </li>  
                    <li>
                        <a href="index.php?page=report"><i class="glyphicon glyphicon-book"></i> <?=$xml->report?></a>
                    
                    </li>  
                    
                    
                    
                    
                     <?php }?>
                  
                  
                </ul>
                <!-- /#side-menu -->
            </div>
            <!-- /.sidebar-collapse -->
        </nav>
        <!-- /.navbar-static-side -->