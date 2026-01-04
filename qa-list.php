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
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR po.tax LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%')";
}

// Build date filter  
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND po.date >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND po.date <= '$date_to'";
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
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(245, 158, 11, 0.25);
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
        color: #f59e0b;
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
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        outline: none;
    }
    
    .filter-card .btn-primary {
        background: #f59e0b;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .filter-card .btn-primary:hover {
        background: #d97706;
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
        background: rgba(245, 158, 11, 0.03);
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        text-decoration: none;
        transition: all 0.2s;
        margin-right: 4px;
    }
    
    .action-btn:hover {
        background: #f59e0b;
        color: white;
        text-decoration: none;
    }
    
    .action-btn.primary {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }
    
    .action-btn.primary:hover {
        background: #667eea;
        color: white;
    }
    
    .action-btn.success {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    
    .action-btn.success:hover {
        background: #10b981;
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
    
    .mail-badge {
        background: #667eea;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        margin-left: 2px;
    }
</style>

<div class="list-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa fa-file-text-o"></i> <?=$xml->quotation?></h2>
        <div class="subtitle">Manage and track all quotations</div>
    </div>

    <!-- Search and Filter Panel -->
    <div class="filter-card">
        <div class="filter-header">
            <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
        </div>
        <div class="filter-body">
            <form method="get" action="" class="form-inline">
                <input type="hidden" name="page" value="qa_list">
                
                <div class="form-group" style="margin-right: 12px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?=$xml->search ?? 'Search'?> QUO#, Name, Customer..." 
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
                <a href="?page=qa_list" class="btn btn-default" style="margin-bottom: 10px; margin-left: 8px;"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
            </form>
        </div>
    </div>

    <!-- Quotation Out Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-up text-success"></i> <?=$xml->quotation?> - <?=$xml->out ?? 'Out'?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->quono?></th>
                    <th width="230"><?=$xml->customer?></th>
                    <th width="150"><?=$xml->price?></th>
                    <th width="100"><?=$xml->duedate?></th>
                    <th width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$query=mysqli_query($db->conn, "select po.id as id, po.name as name, po.tax as tax,mailcount, cancel,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en,vat,dis,over, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id where po_id_new='' and ven_id='".$_SESSION['com_id']."' and status='1' $search_cond $date_cond order by cancel,po.id  desc ");
 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 	$que_pro=mysqli_query($db->conn, "select product.des as des,type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=type.id join model on product.model=model.id where po_id='".$data[id]."'");
	 	$summary=$total=0;
	 while($data_pro=mysqli_fetch_array($que_pro)){
		if($cklabour[cklabour]==1){	
		$equip=$data_pro[price]*$data_pro[quantity];
		$labour1=$data_pro[valuelabour]*$data_pro[activelabour];
		$labour=$labour1*$data_pro[quantity];
		$total=$equip+$labour;}else 
		{$total=$data_pro[price]*$data_pro[quantity];}
	 	$summary+=$total;

}
	 
 	$disco=$summary*$data['dis']/100;
 	$stotal=$summary-$disco;
 	$overh=$stotal*$data['over']/100;
	$stotal=$stotal+$overh;
	$vat=$stotal*$data['vat']/100;
 	$total=$stotal+$vat;
	 
	 
echo "<tr><td>QUO-".htmlspecialchars($data['tax'])."</td><td>".htmlspecialchars($data['name_en'])."</td><td>".number_format($stotal,2)." / ".number_format($total,2)."</td><td>".htmlspecialchars($data['valid_pay'])."</td>";


$var=decodenum($data['status']);

if($data['cancel']=="1"){
echo "<td><span class='status-badge cancelled'>".$xml->$var."</span></td><td>
<a class='action-btn primary' href='index.php?page=".$pg."&id=".$data['id']."&action=m' title='Edit'><i class=\"fa fa-pencil\"></i></a>
<a class='action-btn success' href='index.php?page=po_view&id=".$data['id']."' title='Confirm'><i class=\"fa fa-check\"></i></a>
<a class='action-btn' href='exp.php?id=".$data['id']."' target='blank' title='View'><i class='fa fa-search'></i></a>
<a class='action-btn' data-toggle='modal' href='model_mail.php?page=exp&id=".$data['id']."' data-target='.bs-example-modal-lg' title='Email'><i class='fa fa-envelope'></i><span class='mail-badge'>".$data['mailcount']."</span></a>
</td></tr>";}else
{echo "<td><span class='status-badge active'>".$xml->$var."</span></td><td>
<a class='action-btn primary' href='index.php?page=".$pg."&id=".$data['id']."&action=m' title='Edit'><i class=\"fa fa-pencil\"></i></a>
<a class='action-btn success' href='index.php?page=po_view&id=".$data['id']."' title='Confirm'><i class=\"fa fa-check\"></i></a>
<a class='action-btn' href='exp.php?id=".$data['id']."' target='blank' title='View'><i class='fa fa-search'></i></a>
<a class='action-btn' data-toggle='modal' href='model_mail.php?page=exp&id=".$data['id']."' data-target='.bs-example-modal-lg' title='Email'><i class='fa fa-envelope'></i><span class='mail-badge'>".$data['mailcount']."</span></a>
<a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data['id']."&method=D'><i class=\"fa fa-trash\"></i></a>
</td></tr>";}
	
	}?>
            </tbody>
        </table>
    </div>

    <!-- Quotation In Table -->
    <div class="data-card">
        <div class="section-header">
            <i class="fa fa-arrow-down text-primary"></i> <?=$xml->quotation?> - <?=$xml->in?>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="120"><?=$xml->quono?></th>
                    <th width="230">Vendor</th>
                    <th width="150"><?=$xml->price?></th>
                    <th width="100"><?=$xml->duedate?></th>
                    <th width="90"><?=$xml->status?></th>
                    <th width="130"></th>
                </tr>
            </thead>
            <tbody>
<?php
$query=mysqli_query($db->conn, "select po.id as id, po.name as name, po.tax as tax, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en,vat,dis,over, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id where po_id_new='' and cus_id='".$_SESSION['com_id']."' and status='1'  order by cancel,po.id desc ");
 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	  	$que_pro=mysqli_query($db->conn, "select product.des as des,type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=type.id join model on product.model=model.id where po_id='".$data['id']."'");
		$summary=$total=0;
	 while($data_pro=mysqli_fetch_array($que_pro)){
		if($cklabour['cklabour']==1){	
		$equip=$data_pro['price']*$data_pro['quantity'];
		$labour1=$data_pro['valuelabour']*$data_pro['activelabour'];
		$labour=$labour1*$data_pro['quantity'];
		$total=$equip+$labour;}else 
		{$total=$data_pro['price']*$data_pro['quantity'];}
		$summary+=$total;
	}
	 
		$disco=$summary*$data['dis']/100;
		$stotal=$summary-$disco;
		$overh=$stotal*$data['over']/100;
		$stotal=$stotal+$overh;
		$vat=$stotal*$data['vat']/100;
		$total=$stotal+$vat;
		
		echo "<tr><td>QUO-".htmlspecialchars($data['tax'])."</td><td>".htmlspecialchars($data['name_en'])."</td><td>".number_format($stotal,2)." / ".number_format($total,2)."</td><td>".htmlspecialchars($data['valid_pay'])."</td>";



		$var=decodenum($data['status']);
		if($data['cancel']=="1"){
		echo "<td><span class='status-badge cancelled'>".$xml->$var."</span></td><td>
		<a class='action-btn success' href='index.php?page=po_view&id=".$data['id']."' title='Confirm'><i class=\"fa fa-check\"></i></a>
		<a class='action-btn' href='exp.php?id=".$data['id']."' target='blank' title='View'><i class='fa fa-search'></i></a>
		</td></tr>";}else
		{echo "<td><span class='status-badge active'>".$xml->$var."</span></td><td>
		<a class='action-btn success' href='index.php?page=po_view&id=".$data['id']."' title='Confirm'><i class=\"fa fa-check\"></i></a>
		<a class='action-btn' href='exp.php?id=".$data['id']."' target='blank' title='View'><i class='fa fa-search'></i></a>
		<a class='action-btn danger' onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data['id']."&method=D'><i class=\"fa fa-trash\"></i></a>
		</td></tr>";}

	}
?>
            </tbody>
        </table>
    </div>
</div>
<div id="fetch_state"></div>