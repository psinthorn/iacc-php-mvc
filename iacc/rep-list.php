<h2><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt?><div style="float:right; font-size:20px; padding-top:7px;"><a href="?page=rep_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->receipt;?></a></div></h2><?php
$db->checkSecurity();

?>

<table width="100%" class="table">
<tr><td colspan="7"><?=$xml->receipt?> - <?=$xml->out?></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->email?></th><th><?=$xml->phone?></th><th><?=$xml->receiptno?></th><th><?=$xml->createdate?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select id,name,email,phone, DATE_FORMAT(createdate,'%d-%m-%Y') as createdate, description,rep_rw,brand,vender from receipt  where  vender='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' order by id desc");

 while($data=mysqli_fetch_array($query)){
	 if((isset($data['status']) ? $data['status'] : 0)==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".(isset($data['name']) ? $data['name'] : '')."</td><td>".(isset($data['email']) ? $data['email'] : '')."</td><td>".(isset($data['phone']) ? $data['phone'] : '')."</td><td>REP-".(isset($data['rep_rw']) ? $data['rep_rw'] : '')."</td><td>".(isset($data['createdate']) ? $data['createdate'] : '')."</td><td><a href='?page=rep_make&id=".(isset($data['id']) ? $data['id'] : '')."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='rep-print.php?id=".(isset($data['id']) ? $data['id'] : '')."'><i class='glyphicon glyphicon-usd'></i>REC</a></td>
</tr>";

	}?>
 

</table>
<div id="fetch_state"></div>