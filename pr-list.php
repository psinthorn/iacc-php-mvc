
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

<h2><i class="glyphicon glyphicon-pencil"></i> <?=$xml->purchasingrequest?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="pr_list">
            
            <div class="form-group" style="margin-right: 10px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> PR, Description, Customer..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 220px;">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
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
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;"><?=$xml->from ?? 'From'?>:</label>
                <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;"><?=$xml->to ?? 'To'?>:</label>
                <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>">
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->filter ?? 'Filter'?></button>
            <a href="?page=pr_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<table width="100%" class="table table-hover">
	<tr><td colspan="6"><strong><i class="fa fa-arrow-up text-success"></i> <?=$xml->purchasingrequest?> - <?=$xml->out ?? 'Out'?></strong></td></tr>
	<tr>
		<th width="20%"><?=$xml->customer?></th><th width="40%"><?=$xml->description?></th><th width="10%"><?=$xml->name?></th><th width="10%"><?=$xml->date?></th><th width="10%"><?=$xml->status?></th><th width="10%"></th></tr>
<?php
$query=mysqli_query($db->conn, "select pr.id as id, name,DATE_FORMAT(date,'%d-%m-%Y') as date,cancel, des, name_en, status from pr join company on pr.cus_id=company.id where ven_id='".$com_id."' ".$condition." $search_cond $date_cond order by cancel,id desc");

 while($data=mysqli_fetch_array($query)){
echo "<tr><td>".htmlspecialchars($data['name_en'])."</td><td>".htmlspecialchars($data['des'])."</td><td>".htmlspecialchars($data['name'])."</td><td>".htmlspecialchars($data['date'])."</td>";
$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=po_make&id=".$data['id']."'><i class=\"glyphicon glyphicon-pencil\"></i></a>";}
else {
	echo "<td>".$xml->$var."</td><td><a href='index.php?page=po_make&id=".$data['id']."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=pr_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a>";
	
	}

echo "</td>
</tr>";	

	}
	
	 if($_REQUEST['status']=="5"){
		$query= mysqli_query($db->conn, "select * from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where ven_id='".$_SESSION['com_id']."' and deliver.id in (select deliver_id from receive)");
		 
		 while($data=mysqli_fetch_array($query)){
echo "<tr><td>Send out</td><td>".$data['tmp']."</td><td>".$data['name_sh']."</td><td>".$data['deliver_date']."</td><td>Success</td><td><a onClick='return Conf(this)' title='Cancel' href='#'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";	
	
	}
		 
		 
		 }
	?>
 
 <tr><td colspan="6"><strong><i class="fa fa-arrow-down text-primary"></i> <?=$xml->purchasingrequest?> - <?=$xml->in ?? 'In'?></strong></td></tr>
 <tr><th><?=$xml->vender?></th><th><?=$xml->description?></th><th><?=$xml->name?></th><th><?=$xml->date?></th><th><?=$xml->status?></th><th></th></tr>
<?php
$query=mysqli_query($db->conn,"select pr.id as id, name,cancel,DATE_FORMAT(date,'%d-%m-%Y') as date,des, name_en, status from pr join company on pr.ven_id=company.id where cus_id='".$_SESSION['com_id']."' ".$condition." $search_cond $date_cond order by cancel,id desc");

 while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data['name_en']."</td><td>".$data['des']."</td><td>".$data['name']."</td><td>".$data['date']."</td>";

$val=decodenum($data['status']);
if($data['cancel']=="1"){
	
echo "<td><font color='red'>".$xml->$val."</font></td><td>";}
else {
	echo "<td>".$xml->$val."</td><td><a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=pr_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td><td>";
	
	}

echo "</td>
</tr>";	
	
	}
	 if($_REQUEST['status']=="5"){
		$query= mysqli_query($db->conn, "select * from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.ven_id=company.id where cus_id='".$_SESSION['com_id']."' and deliver.id in (select deliver_id from receive)");
		 
		 while($data=mysqli_fetch_array($query)){
echo "<tr><td>Send out</td><td>".$data[tmp]."</td><td>".$data['name_sh']."</td><td>".$data['deliver_date']."</td><td>Success</td><td><a onClick='return Conf(this)' title='Cancel' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";	
	
	}
		 
		 
		 }
	
	
	?>

</table>
<div id="fetch_state"></div>