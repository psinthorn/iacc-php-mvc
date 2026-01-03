<?php
// Security already checked in index.php
$com_id = sql_int($_SESSION['com_id']);

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%' OR phone LIKE '%$search_escaped%' OR rep_rw LIKE '%$search_escaped%' OR description LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND createdate >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND createdate <= '$date_to'";
}
?>

<h2><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt?>
    <div style="float:right; font-size:20px; padding-top:7px;">
        <a href="?page=rep_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->receipt;?></a>
    </div>
</h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="receipt_list">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Name, Email, Receipt#..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 250px;">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;"><?=$xml->from ?? 'From'?>:</label>
                <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;"><?=$xml->to ?? 'To'?>:</label>
                <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>">
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=receipt_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<table width="100%" class="table table-hover">
<tr><td colspan="7"><strong><i class="fa fa-arrow-up text-success"></i> <?=$xml->receipt?> - <?=$xml->out ?? 'Out'?></strong></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->email?></th><th><?=$xml->phone?></th><th><?=$xml->receiptno?></th><th><?=$xml->createdate?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select id,name,email,phone, DATE_FORMAT(createdate,'%d-%m-%Y') as createdate, description,rep_rw,brand,vender from receipt  where  vender='".$com_id."' $search_cond $date_cond order by id desc");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".e($data['name'])."</td><td>".e($data['email'])."</td><td>".e($data['phone'])."</td><td>REP-".e($data['rep_rw'])."</td><td>".e($data['createdate'])."</td><td><a href='?page=rep_make&id=".e($data['id'])."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='rep-print.php?id=".e($data['id'])."'><i class='glyphicon glyphicon-usd'></i>REC</a></td>
</tr>";

	}?>
 

</table>
<div id="fetch_state"></div>