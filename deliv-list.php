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

<h2><i class="fa fa-truck"></i> <?=$xml->deliverynote?>
    <div style="float:right; font-size:20px; padding-top:7px;">
        <a href="?page=deliv_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->deliverynote;?></a>
    </div>
</h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="deliv_list">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> DN#, Name, Customer..." 
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
            <a href="?page=deliv_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<table width="100%" class="table table-hover">

<tr><td colspan="7"><strong><i class="fa fa-arrow-up text-success"></i> <?=$xml->deliverynote?> - <?=$xml->out ?? 'Out'?></strong></td></tr>
<tr><th width="120"><?=$xml->dnno?></th><th width="200"><?=$xml->customer?></th><th width="200"><?=$xml->description ?? 'Description'?></th><th width="100"><?=$xml->duedate?></th><th width="100"><?=$xml->deliverydate?></th><th width="90"><?=$xml->status?></th><th width="130"></th></tr>
<?php
$query=mysqli_query($db->conn, "select deliver.id as id2,po.id as id, po.name as name,  DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id join deliver on po.id=deliver.po_id  where po_id_new='' and ven_id='".$_SESSION['com_id']."' and status='3' $search_cond $date_cond order by deliver.id desc ");

 while($data=mysqli_fetch_array($query)){

	 $var=decodenum($data[status]);
echo "<tr><td>DN-".str_pad($data[id2], 8, "0", STR_PAD_LEFT)."</td><td>".$data[name_en]."</td><td>".$data[name]."</td><td>".$data[valid_pay]."</td><td>".$data[deliver_date]."</td><td>".$xml->$var."</td><td><a href='index.php?page=deliv_view&id=".$data[id2]."'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data[id2]."' target='blank'>DN</a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	
	}?>
    
    
    
    
    
    
    <?php
$query=mysqli_query($db->conn, "select sendoutitem.id as id2,deliver.id as id,sendoutitem.tmp as des,name_en,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where ven_id='".$_SESSION['com_id']."' and deliver.id not in (select deliver_id from receive) order by deliver.id desc ");

 while($data=mysqli_fetch_array($query)){

	 
echo "<tr><td>DN-".str_pad($data[id], 8, "0", STR_PAD_LEFT)."(make)</td><td>".$data[name_en]."</td><td>".$data[des]."</td><td></td><td>".$data[deliver_date]."</td><td>".$xml->processdeli."</td><td><a href='index.php?page=deliv_view&id=".$data[id]."&modep=ad'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;<a href='index.php?page=deliv_edit&id=".$data[id]."&modep=ad'><span class='glyphicon glyphicon-edit'></span></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data[id]."&modep=ad' target='blank'>DN</a></td>
</tr>";
	
	}?>
 
<tr><td colspan="7"><strong><i class="fa fa-arrow-down text-primary"></i> <?=$xml->deliverynote?> - <?=$xml->in ?? 'In'?></strong></td></tr>
<tr><th width="120"><?=$xml->dnno?></th><th width="200"><?=$xml->vender?></th><th width="200"><?=$xml->description ?? 'Description'?></th><th width="100"><?=$xml->duedate?></th><th width="100"><?=$xml->deliverydate?></th><th width="90"><?=$xml->status?></th><th width="130"></th></tr>
<?php
$query=mysqli_query($db->conn, "select deliver.id as id2,po.id as id, po.name as name, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id join deliver on po.id=deliver.po_id where po_id_new='' and pr.cus_id='".$_SESSION['com_id']."' and status='3' $search_cond $date_cond order by deliver.id desc");

$var=decodenum($data[status]);
 while($data=mysqli_fetch_array($query)){
echo "<tr><td>DN-".str_pad($data[id2], 8, "0", STR_PAD_LEFT)."</td><td>".$data[name_en]."</td><td>".$data[name]."</td><td>".$data[valid_pay]."</td><td>".$data[deliver_date]."</td><td>".$xml->$var."</td><td><a href='index.php?page=deliv_view&id=".$data[id2]."'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data[id2]."' target='blank'>R</a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";	
	
	}?>
      <?php
$query=mysqli_query($db->conn, "select sendoutitem.id as id2,deliver.id as id,sendoutitem.tmp as des,name_en,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.ven_id=company.id where cus_id='".$_SESSION['com_id']."' and deliver.id not in (select deliver_id from receive) order by deliver.id desc");

 while($data=mysqli_fetch_array($query)){

	echo "<tr><td>DN-".str_pad($data[id], 7, "0", STR_PAD_LEFT)."(make)</td><td>".$data[name_en]."</td><td>".$data[des]."</td><td></td><td>".$data[deliver_date]."</td><td>".$xml->processdeli."</td><td><a href='index.php?page=deliv_view&id=".$data[id]."&modep=ad'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data[id]."&modep=ad' target='blank'>DN</a></td>
</tr>";
	
	
	}?>
 

</table>
<div id="fetch_state"></div>