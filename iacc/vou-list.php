<h2><i class="glyphicon glyphicon-tags"></i> <?=$xml->voucher?><div style="float:right; font-size:20px; padding-top:7px;"><a href="?page=voc_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->voucher;?></a></div></h2><?php
$db->checkSecurity();

?>

<table width="100%" class="table">
<tr><td colspan="7"><?=$xml->voucher?> - <?=$xml->out?></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->email?></th><th><?=$xml->phone?></th><th><?=$xml->voucherno?></th><th><?=$xml->createdate?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select voucher.id as id,voucher.name as name, voucher.email as email,phone, DATE_FORMAT(createdate,'%d-%m-%Y') as createdate, voucher.description as description,vou_rw,voucher.brand as brand,voucher.vender as vender from voucher  where  voucher.vender='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' order by voucher.id desc");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".$data['name']."</td><td>".$data['email']."</td><td>".$data['phone']."</td><td>VOC-".$data['vou_rw']."</td><td>".$data['createdate']."</td><td><a href='?page=voc_make&id=".$data['id']."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='vou-print.php?id=".$data['id']."'>VOC</a></td>
</tr>";

	}?>
 

</table>
<div id="fetch_state"></div>