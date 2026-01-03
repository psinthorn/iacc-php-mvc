<?php
require_once("inc/security.php");

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (po.name LIKE '%$search_escaped%' OR name_en LIKE '%$search_escaped%' OR texiv_rw LIKE '%$search_escaped%')";
}

// Build date filter
$date_cond = '';
if (!empty($date_from)) {
    $date_cond .= " AND texiv_create >= '$date_from'";
}
if (!empty($date_to)) {
    $date_cond .= " AND texiv_create <= '$date_to'";
}
?>

<h2><i class="fa fa-thumbs-up"></i> <?=$xml->taxinvoice?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="compl_list2">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> TAX#, Name, Customer..." 
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
            <a href="?page=compl_list2" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<table width="100%" id="table1" class="table table-hover">

<tr><td colspan="6"><strong><i class="fa fa-arrow-up text-success"></i> <?=$xml->taxinvoice?> - <?=$xml->out ?? 'Out'?></strong></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->taxno?></th><th><?=$xml->name?></th><th width="100"><?=$xml->createdate?></th><th colspan="2"><?=$xml->status?></th></tr>
<?php

$query=mysqli_query($db->conn, "select po.id as id,countmailtax, po.name as name,texiv_rw, DATE_FORMAT(texiv_create,'%d-%m-%Y') as texiv_create, name_en, status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id join iv on po.id=iv.tex where po_id_new='' and ven_id='".$_SESSION['com_id']."' and status='5' and status_iv='1' $search_cond $date_cond order by texiv_rw desc");
$cot=0;
 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	$var=decodenum($data['status']) ;
	 $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
echo "<tr ".$color."><td>".$data['name_en']."</td><td>TAX-".str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)."</td><td>".$data['name']."</td><td>".$data['texiv_create']."</td><td>".$xml->$var."</td><td><a href='taxiv.php?id=".$data['id']."' target='blank'>TAX-IV</a>&nbsp;&nbsp;&nbsp;<a data-toggle='modal' href='model_mail.php?page=tax&id=".$data['id']."'   data-target='.bs-example-modal-lg'><i class='glyphicon glyphicon-envelope'></i><span class='badge'>".$data['	countmailtax']."</span></a></td>
</tr>";
	
	}?>
 
<tr><td colspan="6"><strong><i class="fa fa-arrow-down text-primary"></i> <?=$xml->taxinvoice?> - <?=$xml->in ?? 'In'?></strong></td></tr>
<tr><th><?=$xml->vender?></th><th><?=$xml->taxno?></th><th><?=$xml->name?></th><th><?=$xml->createdate?></th><th colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id, po.name as name, iv.id as tax, texiv_rw, DATE_FORMAT(texiv_create,'%d-%m-%Y') as texiv_create, name_en, status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id  join iv on po.id=iv.tex  where  po_id_new='' and pr.cus_id='".$_SESSION['com_id']."' and status='5' and status_iv='1' $search_cond $date_cond order by texiv_rw desc ");
$cot=0;
 while($data=mysqli_fetch_array($query)){
	  $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
$var=decodenum($data['status']);
echo "<tr ".$color."><td>".$data['name_en']."</td><td>TAX-".str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)."</td><td>".$data['name']."</td><td>".$data['texiv_create']."</td><td>".$xml->$var."</td><td><a href='taxiv.php?id=".$data['id']."' target='blank'>TAX-IV</a></td>
</tr>";	
	
	}?>

</table>
<div id="fetch_state"></div>