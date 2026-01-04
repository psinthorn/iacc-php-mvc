<?php
// Security already checked in index.php

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%' OR company.name_th LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND deliver.deliver_date >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND deliver.deliver_date <= '$date_to'";
}
?>

<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .list-wrapper {
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
        display: flex;
        justify-content: space-between;
        align-items: center;
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
    
    .page-header .btn-create {
        background: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .page-header .btn-create:hover {
        background: rgba(255,255,255,0.3);
        text-decoration: none;
        color: white;
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
        padding: 12px 14px;
        font-size: 14px;
        min-height: 44px;
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
        padding: 12px 20px;
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
        padding: 12px 20px;
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
        text-decoration: none;
    }
    
    .action-btn.success {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    
    .action-btn.success:hover {
        background: #10b981;
        color: white;
    }
    
    .action-btn.warning {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }
    
    .action-btn.warning:hover {
        background: #f59e0b;
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
    .status-badge.info { background: #dbeafe; color: #3b82f6; }
</style>

<div class="list-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2><i class="fa fa-truck"></i> <?=$xml->deliverynote?></h2>
            <div class="subtitle">Manage delivery notes and shipments</div>
        </div>
        <a href="?page=deliv_make" class="btn-create">
            <i class="fa fa-plus"></i> <?=$xml->create." ".$xml->deliverynote;?>
        </a>
    </div>

    <!-- Search and Filter Panel -->
    <div class="filter-card">
        <div class="filter-header">
            <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        </div>
        <div class="filter-body">
            <form method="get" action="" class="form-inline">
                <input type="hidden" name="page" value="deliv_list">
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> DN#, Name, Customer..." 
                           value="<?=htmlspecialchars($search)?>" style="width: 250px;">
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <label style="margin-right: 6px; color: #6b7280; font-weight: 500;"><?=$xml->from ?? 'From'?>:</label>
                    <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>">
                </div>
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <label style="margin-right: 6px; color: #6b7280; font-weight: 500;"><?=$xml->to ?? 'To'?>:</label>
                    <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom: 10px;"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                <a href="?page=deliv_list" class="btn btn-default" style="margin-bottom: 10px; margin-left: 8px;"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            </form>
        </div>
    </div>

    <!-- Delivery Notes Out Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->deliverynote?> - <?=$xml->out ?? 'Out'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->dnno?></th>
                    <th width="200"><?=$xml->customer?></th>
                    <th width="200"><?=$xml->description ?? 'Description'?></th>
                    <th width="100"><?=$xml->duedate?></th>
                    <th width="100"><?=$xml->deliverydate?></th>
                    <th width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$query=mysqli_query($db->conn, "select deliver.id as id2,po.id as id, po.name as name,  DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id join deliver on po.id=deliver.po_id  where po_id_new='' and ven_id='".$_SESSION['com_id']."' and status='3' $search_cond $date_cond order by deliver.id desc ");

 while($data=mysqli_fetch_array($query)){

	 $var=decodenum($data['status']);
echo "<tr><td>DN-".str_pad($data['id2'], 8, "0", STR_PAD_LEFT)."</td><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['name'])."</td><td>".htmlspecialchars($data['valid_pay'])."</td><td>".htmlspecialchars($data['deliver_date'])."</td><td><span class='status-badge info'>".$xml->$var."</span></td><td>
<a class='action-btn' href='index.php?page=deliv_view&id=".$data['id2']."' title='View'><i class=\"fa fa-search-plus\"></i></a>
<a class='action-btn success' href='rec.php?id=".$data['id2']."' target='blank' title='Print DN'>DN</a>
<a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href=\"#\"><i class=\"fa fa-trash\"></i></a>
</td></tr>";
	
	}?>
    
<?php
$query=mysqli_query($db->conn, "select sendoutitem.id as id2,deliver.id as id,sendoutitem.tmp as des,name_en,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where ven_id='".$_SESSION['com_id']."' and deliver.id not in (select deliver_id from receive) order by deliver.id desc ");

 while($data=mysqli_fetch_array($query)){

	 
echo "<tr><td>DN-".str_pad($data['id'], 8, "0", STR_PAD_LEFT)."(make)</td><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['des'])."</td><td></td><td>".htmlspecialchars($data['deliver_date'])."</td><td><span class='status-badge active'>".$xml->processdeli."</span></td><td>
<a class='action-btn' href='index.php?page=deliv_view&id=".$data['id']."&modep=ad' title='View'><i class=\"fa fa-search-plus\"></i></a>
<a class='action-btn warning' href='index.php?page=deliv_edit&id=".$data['id']."&modep=ad' title='Edit'><i class='fa fa-edit'></i></a>
<a class='action-btn success' href='rec.php?id=".$data['id']."&modep=ad' target='blank' title='Print DN'>DN</a>
</td></tr>";
	
	}?>
            </tbody>
        </table>
    </div>

    <!-- Delivery Notes In Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->deliverynote?> - <?=$xml->in ?? 'In'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->dnno?></th>
                    <th width="200">Vendor</th>
                    <th width="200"><?=$xml->description ?? 'Description'?></th>
                    <th width="100"><?=$xml->duedate?></th>
                    <th width="100"><?=$xml->deliverydate?></th>
                    <th width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$query=mysqli_query($db->conn, "select deliver.id as id2,po.id as id, po.name as name, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id join deliver on po.id=deliver.po_id where po_id_new='' and pr.cus_id='".$_SESSION['com_id']."' and status='3' $search_cond $date_cond order by deliver.id desc");

 while($data=mysqli_fetch_array($query)){
$var=decodenum($data['status']);
echo "<tr><td>DN-".str_pad($data['id2'], 8, "0", STR_PAD_LEFT)."</td><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['name'])."</td><td>".htmlspecialchars($data['valid_pay'])."</td><td>".htmlspecialchars($data['deliver_date'])."</td><td><span class='status-badge info'>".$xml->$var."</span></td><td>
<a class='action-btn' href='index.php?page=deliv_view&id=".$data['id2']."' title='Receive'><i class=\"fa fa-dropbox\"></i></a>
<a class='action-btn success' href='rec.php?id=".$data['id2']."' target='blank' title='Receipt'>R</a>
<a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href=\"#\"><i class=\"fa fa-trash\"></i></a>
</td></tr>";	
	
	}?>
<?php
$query=mysqli_query($db->conn, "select sendoutitem.id as id2,deliver.id as id,sendoutitem.tmp as des,name_en,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.ven_id=company.id where cus_id='".$_SESSION['com_id']."' and deliver.id not in (select deliver_id from receive) order by deliver.id desc");

 while($data=mysqli_fetch_array($query)){

	echo "<tr><td>DN-".str_pad($data['id'], 7, "0", STR_PAD_LEFT)."(make)</td><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['des'])."</td><td></td><td>".htmlspecialchars($data['deliver_date'])."</td><td><span class='status-badge active'>".$xml->processdeli."</span></td><td>
<a class='action-btn' href='index.php?page=deliv_view&id=".$data['id']."&modep=ad' title='Receive'><i class=\"fa fa-dropbox\"></i></a>
<a class='action-btn success' href='rec.php?id=".$data['id']."&modep=ad' target='blank' title='Print DN'>DN</a>
</td></tr>";
	
	
	}?>
            </tbody>
        </table>
    </div>
</div>
<div id="fetch_state"></div>