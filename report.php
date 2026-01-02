<h2><i class="glyphicon glyphicon-book"></i> <?=$xml->report?></h2>

<table class="table" width="100%">
<tr><th></th><th><?=$xml->purchasingrequest?></th><th><?=$xml->quotation?></th><th><?=$xml->purchasingorder?></th><th><?=$xml->invoice?></th><th><?=$xml->taxinvoice?></th></tr><?php

$querycom=mysqli_query($db->conn, "select name_en,id from company where company.id!='".$_SESSION['com_id']."' and customer='1'");
while($fetcom=mysqli_fetch_array($querycom)){
	$pr=mysqli_fetch_array(mysqli_query($db->conn, "select count(id) as ct from pr where ven_id='".$_SESSION['com_id']."' and cus_id='".$fetcom[id]."'"));
	$qa=mysqli_fetch_array(mysqli_query($db->conn, "select count(id) as ct from pr where ven_id='".$_SESSION['com_id']."' and cus_id='".$fetcom[id]."' and status>='1'"));
	$po=mysqli_fetch_array(mysqli_query($db->conn, "select count(id) as ct from pr where ven_id='".$_SESSION['com_id']."' and cus_id='".$fetcom[id]."' and status>='2'"));
	$iv=mysqli_fetch_array(mysqli_query($db->conn, "select count(id) as ct from pr where ven_id='".$_SESSION['com_id']."' and cus_id='".$fetcom[id]."' and status>='4'"));
	$tx=mysqli_fetch_array(mysqli_query($db->conn, "select count(id) as ct from pr where ven_id='".$_SESSION['com_id']."' and cus_id='".$fetcom[id]."' and status>='5'"));
	?>
<tr><th><?=$fetcom[name_en]?></th>
<td><?=$pr[ct];$prs=$prs+$pr[ct]?></td>
<td><?=$qa[ct];$qas=$qas+$qa[ct]?></td>
<td><?=$po[ct];$pos=$pos+$po[ct]?></td>
<td><?=$iv[ct];$ivs=$ivs+$iv[ct]?></td>
<td><?=$tx[ct];$txs=$txs+$tx[ct]?></td>

</tr><?php }?>

<tr><th style="text-align:right;"><?=$xml->summary?></th>
<td><?=$prs?></td>
<td><?=$qas?></td>
<td><?=$pos?></td>
<td><?=$ivs?></td>
<td><?=$txs?></td>

</tr>
</table>