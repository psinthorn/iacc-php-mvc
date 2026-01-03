<h2><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder?></h2><?php
// Security already checked in index.php
// Use session variable (already validated) for queries
$com_id = sql_int($_SESSION['com_id']);

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR po.tax LIKE '%$search_escaped%' OR company.name_en LIKE '%$search_escaped%' OR company.name_th LIKE '%$search_escaped%')";
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

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="po_list">
            <div class="form-group" style="margin-right: 15px;">
                <label for="search" style="margin-right: 5px;"><i class="fa fa-search"></i></label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> PO#, Name, Customer..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 250px;">
            </div>
            <div class="form-group" style="margin-right: 15px;">
                <label for="date_from" style="margin-right: 5px;"><?=$xml->from ?? 'From'?>:</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?=htmlspecialchars($date_from)?>">
            </div>
            <div class="form-group" style="margin-right: 15px;">
                <label for="date_to" style="margin-right: 5px;"><?=$xml->to ?? 'To'?>:</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?=htmlspecialchars($date_to)?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=po_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<table width="100%" class="table table-hover">
<tr><td colspan="6"><strong><i class="fa fa-arrow-up text-success"></i> <?=$xml->purchasingorder?> - <?=$xml->out?></strong></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->pono?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->status?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id,cancel, po.name as name, po.tax as tax, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id where po_id_new='' and ven_id='".$com_id."' and status='2' $search_cond $date_cond order by cancel,po.id desc");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".e($data['name'])."</td><td>PO-".e($data['tax'])."</td><td>".e($data['name_en'])."</td><td>".e($data['valid_pay'])."</td>";

$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><!--<a href='index.php?page=".$pg."&id=".e($data['id'])."&action=m'><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".e($data['id'])."&action=c'><i class=\"fa fa-magic\"></i></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><!--<a href='index.php?page=".$pg."&id=".e($data['id'])."&action=m'><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".e($data['id'])."&action=c'><i class=\"fa fa-magic\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".e($data['id'])."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	}


	}?>
 
 <tr>
   <td colspan="6"><strong><i class="fa fa-arrow-down text-primary"></i> <?=$xml->purchasingorder?> - <?=$xml->in?></strong></td></tr>
 
<tr><th><?=$xml->vender?></th><th><?=$xml->pono?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->status?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id, po.name as name, po.tax as tax,cancel, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id where po_id_new='' and cus_id='".$com_id."' and status='2' $search_cond $date_cond order by cancel,po.id desc ");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".e($data['name_en'])."</td><td>PO-".e($data['tax'])."</td><td>".e($data['name'])."</td><td>".e($data['valid_pay'])."</td>";



$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=po_view&id=".e($data['id'])."'><i class=\"fa fa-dropbox\"></i></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><a href='index.php?page=po_view&id=".e($data['id'])."'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".e($data['id'])."&action=c'><i class=\"fa fa-magic\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".e($data['id'])."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	}
	
	}?>
 

</table>
<div id="fetch_state"></div>