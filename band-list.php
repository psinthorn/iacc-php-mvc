<?php
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
// $db = new DB($config);
// $db->checkSecurity();
?>
<h2><i class="fa fa-cog"></i> <?=$xml->brand?></h2>
<?php 
		$sql = "select id, brand_name, des from brand order by id desc";
$query=mysqli_query($db->conn,$sql);
?>
<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->description?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'band.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php 
	while($data=mysqli_fetch_array($query)){
	echo "<tr><td>".$data['brand_name']."</td><td>".$data['des']."</td>
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'band.php?id=".$data['id']."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"core-function.php?method=D&id=".$data['id']."&page=band\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";		
	}
?>

</table>