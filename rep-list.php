<h2><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt?><div style="float:right; font-size:20px; padding-top:7px;"><a href="?page=rep_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->receipt;?></a></div></h2><?php
// Security already checked in index.php
$com_id = sql_int($_SESSION['com_id']);
?>

<table width="100%" class="table">
<tr><td colspan="7"><?=$xml->receipt?> - <?=$xml->out?></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->email?></th><th><?=$xml->phone?></th><th><?=$xml->receiptno?></th><th><?=$xml->createdate?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select id,name,email,phone, DATE_FORMAT(createdate,'%d-%m-%Y') as createdate, description,rep_rw,brand,vender from receipt  where  vender='".$com_id."' order by id desc");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".e($data['name'])."</td><td>".e($data['email'])."</td><td>".e($data['phone'])."</td><td>REP-".e($data['rep_rw'])."</td><td>".e($data['createdate'])."</td><td><a href='?page=rep_make&id=".e($data['id'])."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='rep-print.php?id=".e($data['id'])."'><i class='glyphicon glyphicon-usd'></i>REC</a></td>
</tr>";

	}?>
 

</table>
<div id="fetch_state"></div>