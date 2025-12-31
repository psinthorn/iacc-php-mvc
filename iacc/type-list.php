<?php 
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db=new DbConn($config);
$db->checkSecurity();
?>
	<h2><i class="fa fa-ticket"></i> <?=$xml->product?></h2>

	<?php
		$query=mysqli_query($db->conn, "select product_type.id as id,name, cat_name from type join category on product_type.category_id=category.id order by product_type.id desc");
	?>

		<div id="fetch_state"></div>
		<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->category?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'product_type.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
	<?php 
	while($data=mysqli_fetch_array($query)){
		echo "<tr><td>".$data['name']."</td><td>".$data['cat_name']."</td>
		<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'product_type.php?id=".$data['id']."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"core-function.php?method=D&id=".$data['id']."&page=type\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
		}
	?>
</table>