
<?php 
	// Security: Use whitelist approach for status filter
	$status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
	if($status == 1) $condition="and status='1'";
	else if($status == 2) $condition="and status='2'";
	else if($status == 3) $condition="and status='3'";
	else if($status == 4) $condition="and status='4'";
	else if($status == 5) $condition="and status='5'";
	else if($status == 6) $condition="";
	else $condition="and status='0'";
	
	// Get search parameters
	$search = isset($_GET['search']) ? trim($_GET['search']) : '';
	$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
	$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
	
	// Build search condition
	$search_cond = '';
	if (!empty($search)) {
	    $search_escaped = sql_escape($search);
	    $search_cond = " AND (pr.name LIKE '%$search_escaped%' OR pr.des LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%' OR company.name_th LIKE '%$search_escaped%')";
	}
	
	// Build date filter
	$date_cond = '';
	if (!empty($date_from)) {
	    $date_cond .= " AND pr.date >= '$date_from'";
	}
	if (!empty($date_to)) {
	    $date_cond .= " AND pr.date <= '$date_to'";
	}
	
	$com_id = intval($_SESSION['com_id']);
?>

<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .pr-list-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header .subtitle {
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .filter-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .filter-card .filter-header {
        background: #f9fafb;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .filter-card .filter-header i {
        color: #667eea;
        margin-right: 8px;
    }
    
    .filter-card .filter-body {
        padding: 20px;
    }
    
    .filter-card .form-control {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 10px 14px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .filter-card .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .filter-card .btn-primary {
        background: #667eea;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .filter-card .btn-primary:hover {
        background: #5a6fd6;
        transform: translateY(-1px);
    }
    
    .filter-card .btn-default {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 20px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .filter-card .btn-default:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .data-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .data-card .section-header {
        background: #f9fafb;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .data-card .table {
        margin: 0;
        font-size: 13px;
    }
    
    .data-card .table thead th {
        background: #f9fafb;
        color: #1f2937;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 2px solid #e5e7eb;
        border-top: none;
    }
    
    .data-card .table tbody td {
        padding: 14px 16px;
        border-color: #e5e7eb;
        vertical-align: middle;
        color: #1f2937;
    }
    
    .data-card .table tbody tr:hover {
        background: rgba(102, 126, 234, 0.03);
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        text-decoration: none;
        transition: all 0.2s;
        margin-right: 4px;
    }
    
    .action-btn:hover {
        background: #667eea;
        color: white;
    }
    
    .action-btn.danger {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    
    .action-btn.danger:hover {
        background: #ef4444;
        color: white;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-badge.active { background: #fef3c7; color: #d97706; }
    .status-badge.success { background: #d1fae5; color: #10b981; }
    .status-badge.cancelled { background: #fee2e2; color: #ef4444; }
</style>

<div class="pr-list-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa fa-clipboard"></i> <?=$xml->purchasingrequest?></h2>
        <div class="subtitle">Manage and track all purchase requests</div>
    </div>

    <!-- Search and Filter Panel -->
    <div class="filter-card">
        <div class="filter-header">
            <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        </div>
        <div class="filter-body">
            <form method="get" action="" class="form-inline">
                <input type="hidden" name="page" value="pr_list">
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> PR, Description, Customer..." 
                           value="<?=htmlspecialchars($search)?>" style="width: 240px;">
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <select name="status" class="form-control">
                        <option value='0' <?php if($status == 0)echo "selected";?> ><?=$xml->processpr?></option>
                        <option value='1' <?php if($status == 1)echo "selected";?> ><?=$xml->processquo?></option>
                        <option value='2' <?php if($status == 2)echo "selected";?> ><?=$xml->processpo?></option>
                        <option value='3' <?php if($status == 3)echo "selected";?> ><?=$xml->processdeli?></option>
                        <option value='4' <?php if($status == 4)echo "selected";?> ><?=$xml->processpaid?></option>
                        <option value='5' <?php if($status == 5)echo "selected";?> ><?=$xml->success?></option>
                        <option value='6' <?php if($status == 6)echo "selected";?> ><?=$xml->processall?></option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <label style="margin-right: 6px; color: #6b7280; font-weight: 500;"><?=$xml->from ?? 'From'?>:</label>
                    <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>">
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <label style="margin-right: 6px; color: #6b7280; font-weight: 500;"><?=$xml->to ?? 'To'?>:</label>
                    <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom: 10px;"><i class="fa fa-search"></i> <?=$xml->filter ?? 'Filter'?></button>
                <a href="?page=pr_list" class="btn btn-default" style="margin-bottom: 10px; margin-left: 8px;"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            </form>
        </div>
    </div>

    <!-- PR Out Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->purchasingrequest?> - <?=$xml->out ?? 'Out'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->prno ?? 'PR#'?></th>
                    <th width="230"><?=$xml->customer?></th>
                    <th width="230"><?=$xml->description?></th>
                    <th width="100"><?=$xml->date?></th>
                    <th width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$query=mysqli_query($db->conn, "select pr.id as id, name,DATE_FORMAT(date,'%d-%m-%Y') as date,cancel, des, name_en, status from pr join company on pr.cus_id=company.id where ven_id='".$com_id."' ".$condition." $search_cond $date_cond order by cancel,id desc");

 while($data=mysqli_fetch_array($query)){
echo "<tr><td>PR-".str_pad($data['id'], 6, "0", STR_PAD_LEFT)."</td><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['des'])."</td><td>".htmlspecialchars($data['date'])."</td>";
$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><span class='status-badge cancelled'>".$xml->$var."</span></td><td><a class='action-btn' href='index.php?page=po_make&id=".$data['id']."' title='Edit'><i class=\"fa fa-pencil\"></i></a>";}
else {
	echo "<td><span class='status-badge active'>".$xml->$var."</span></td><td><a class='action-btn' href='index.php?page=po_make&id=".$data['id']."' title='Edit'><i class=\"fa fa-pencil\"></i></a><a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href='core-function.php?page=pr_list&id=".$data['id']."&method=D'><i class=\"fa fa-trash\"></i></a>";
	
	}

echo "</td>
</tr>";	

	}
	
	 if($_REQUEST['status']=="5"){
		$query= mysqli_query($db->conn, "select * from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where ven_id='".$_SESSION['com_id']."' and deliver.id in (select deliver_id from receive)");
		 
		 while($data=mysqli_fetch_array($query)){
echo "<tr><td>Send out</td><td>".htmlspecialchars($data['tmp'])."</td><td>".htmlspecialchars($data['name_sh'])."</td><td>".htmlspecialchars($data['deliver_date'])."</td><td><span class='status-badge success'>Success</span></td><td><a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href='#'><i class=\"fa fa-trash\"></i></a></td>
</tr>";	
	
	}
		 
		 
		 }
	?>
 
            </tbody>
        </table>
    </div>

    <!-- PR In Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->purchasingrequest?> - <?=$xml->in ?? 'In'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->prno ?? 'PR#'?></th>
                    <th width="230"><?=$xml->vender?></th>
                    <th width="230"><?=$xml->description?></th>
                    <th width="100"><?=$xml->date?></th>
                    <th width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$query=mysqli_query($db->conn,"select pr.id as id, name,cancel,DATE_FORMAT(date,'%d-%m-%Y') as date,des, name_en, status from pr join company on pr.ven_id=company.id where cus_id='".$_SESSION['com_id']."' ".$condition." $search_cond $date_cond order by cancel,id desc");

 while($data=mysqli_fetch_array($query)){
echo "<tr><td>PR-".str_pad($data['id'], 6, "0", STR_PAD_LEFT)."</td><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['des'])."</td><td>".htmlspecialchars($data['date'])."</td>";

$val=decodenum($data['status']);
if($data['cancel']=="1"){
	
echo "<td><span class='status-badge cancelled'>".$xml->$val."</span></td><td>";}
else {
	echo "<td><span class='status-badge active'>".$xml->$val."</span></td><td><a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href='core-function.php?page=pr_list&id=".$data['id']."&method=D'><i class=\"fa fa-trash\"></i></a></td><td>";
	
	}

echo "</td>
</tr>";	
	
	}
	 if($_REQUEST['status']=="5"){
		$query= mysqli_query($db->conn, "select * from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.ven_id=company.id where cus_id='".$_SESSION['com_id']."' and deliver.id in (select deliver_id from receive)");
		 
		 while($data=mysqli_fetch_array($query)){
echo "<tr><td>Send out</td><td>".htmlspecialchars($data['tmp'])."</td><td>".htmlspecialchars($data['name_sh'])."</td><td>".htmlspecialchars($data['deliver_date'])."</td><td><span class='status-badge success'>Success</span></td><td><a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href=\"#\"><i class=\"fa fa-trash\"></i></a></td>
</tr>";	
	
	}
		 
		 
		 }
	
	
	?>
            </tbody>
        </table>
    </div>
</div>
<div id="fetch_state"></div>