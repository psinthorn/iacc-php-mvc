<?php
	// require_once("inc/sys.configs.php");
	// require_once("inc/class.dbconn.php");
	// $db = new DbConn($config);
	// ตัวแปรสำหรับติดต่อดาต้าเบส
	// $db->conn
?>
<h2><i class="fa fa-cog"></i> <?=$xml->category?></h2><?php
$db->checkSecurity();?>
<?php
$sql="select id, cat_name, des from category order by id desc";
$query=mysqli_query($db->conn, $sql);?>

<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->description?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'category.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data['cat_name']."</td><td>".$data['des']."</td>
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'category.php?id=".$data['id']."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"core-function.php?method=D&id=".$data['id']."&page=category\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
	
	}?>

</table>