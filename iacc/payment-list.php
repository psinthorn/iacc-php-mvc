<h2><i class="fa fa-money"></i> <?=$xml->payment?></h2><?php
$db->checkSecurity();?>
<?php
$query=mysqli_query($db->conn, "select id, payment_name,payment_des from payment where  company_id='".mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '')."' order by id desc");?>

<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->description?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'payment.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data['payment_name']."</td><td>".$data['payment_des']."</td>
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'payment.php?id=".$data['id']."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
	
	}?>

</table>