<?php
	// require_once("inc/sys.configs.php");
	// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
	// $dbconn = new DbConn($config);

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR iv.taxrw LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND iv.createdate >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND iv.createdate <= '$date_to'";
}

// Build status filter
$status_cond = '';
if ($status_filter === 'pending') {
    $status_cond = " AND status='4'";
} elseif ($status_filter === 'completed') {
    $status_cond = " AND status='5'";
}
?>

<h2><i class="fa fa-thumbs-up"></i> <?=$xml->invoice?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="compl_list">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Name, Customer..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 250px;">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">Status:</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="pending" <?=$status_filter=='pending'?'selected':''?>>Pending</option>
                    <option value="completed" <?=$status_filter=='completed'?'selected':''?>>Completed</option>
                </select>
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
            <a href="?page=compl_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<table width="100%" id="table1" class="table table-hover">

<tr><td colspan="7"><strong><i class="fa fa-arrow-up text-success"></i> <?=$xml->invoice?> - <?=$xml->out ?? 'Out'?></strong></td></tr>
<tr><th width="24%"><?=$xml->customer?></th><th width="10%"><?=$xml->inno?></th><th width="20%"><?=$xml->name?></th><th width="13%"><?=$xml->duedate?></th><th width="13%"><?=$xml->deliverydate?></th><th width="20%" colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id,	countmailinv, po.name as name, taxrw as tax,status_iv,  DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date ,status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id join iv on po.id=iv.tex where  po_id_new='' and pr.ven_id='".$_SESSION['com_id']."' and status>='4' $search_cond $date_cond $status_cond order by iv.id desc ");
$cot=0;
 while($data=mysqli_fetch_array($query)){
	  $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 if(($data['status_iv']=="2")&&($data['status']=="4")){$statusiv="void";}
	 else if(($data['status']=="4")&&($data['valid_pay']<date("d-m-Y")))
	 {$statusiv="overdue";}else{$statusiv=decodenum($data['status']);}
echo "<tr ".$color."><td>".$data['name_en']."</td><td>INV-".$data['tax']."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td><td>".$data['deliver_date']."</td><td>".$xml->$statusiv."</td><td width='10%' align='right'>";
if($data['status']!="5") echo "
<a href='index.php?page=compl_view&id=".$data['id']."'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;";
echo "<a href='inv.php?id=".$data['id']."' target='blank'>IV</a>&nbsp;&nbsp;&nbsp;<a data-toggle='modal' href='model_mail.php?page=inv&id=".$data['id']."'   data-target='.bs-example-modal-lg'><i class='glyphicon glyphicon-envelope'></i><span class='badge'>".$data['	countmailinv']."</span></a></td>
</tr>";
	
	}?>
 
<tr><td colspan="7"><strong><i class="fa fa-arrow-down text-primary"></i> <?=$xml->invoice?> - <?=$xml->in ?? 'In'?></strong></td></tr>
<tr><th><?=$xml->vender?></th><th><?=$xml->inno?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->deliverydate?></th><th colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id, po.name as name,  taxrw as tax,  DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date ,status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id join iv on po.id=iv.tex where  po_id_new='' and pr.cus_id='".$_SESSION['com_id']."' and status>='4' $search_cond $date_cond $status_cond order by iv.id desc ");
$cot=0;
$var=decodenum($data['status']);
 while($data=mysqli_fetch_array($query)){
	  $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
echo "<tr ".$color."><td>".$data['name_en']."</td><td>INV-".$data['tax']."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td><td>".$data['deliver_date']."</td><td>".$xml->$var."</td><td align='right'>";


if($data['status']!="5") echo "
<a href='index.php?page=compl_view&id=".$data['id']."'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;";
echo "<a href='inv.php?id=".$data['id']."' target='blank'>IV</a></td>
</tr>";	
	
	}?>

</table>
<div id="fetch_state"></div>