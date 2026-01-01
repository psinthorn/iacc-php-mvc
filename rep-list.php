<h2><i class="glyphicon glyphicon-usd"></i> <?=$xml->receipt?><div style="float:right; font-size:20px; padding-top:7px;"><a href="?page=rep_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->receipt;?></a></div></h2><?php
$users->checkSecurity();

?>

<table width="100%" class="table">
<tr><td colspan="7"><?=$xml->receipt?> - <?=$xml->out?></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->email?></th><th><?=$xml->phone?></th><th><?=$xml->receiptno?></th><th><?=$xml->createdate?></th><th width="120"></th></tr>
<?php
$query=mysql_query("select id,name,email,phone, DATE_FORMAT(createdate,'%d-%m-%Y') as createdate, description,rep_rw,brand,vender from receipt  where  vender='".$_SESSION[com_id]."' order by id desc");

 while($data=mysql_fetch_array($query)){
	 if($data[status]==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".$data[name]."</td><td>".$data[email]."</td><td>".$data[phone]."</td><td>REP-".$data[rep_rw]."</td><td>".$data[createdate]."</td><td><a href='?page=rep_make&id=".$data[id]."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='rep-print.php?id=".$data[id]."'><i class='glyphicon glyphicon-usd'></i>REC</a></td>
</tr>";

	}?>
 

</table>
<div id="fetch_state"></div>