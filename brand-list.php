<?php
require_once("inc/security.php");

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " WHERE (brand_name LIKE '%$search_escaped%' OR des LIKE '%$search_escaped%')";
}
?>
<h2><i class="fa fa-cog"></i> <?=$xml->brand?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
	<div class="panel-heading">
		<i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
	</div>
	<div class="panel-body">
		<form method="get" action="" class="form-inline">
			<input type="hidden" name="page" value="brand">
			
			<div class="form-group" style="margin-right: 15px;">
				<input type="text" class="form-control" name="search" 
					   placeholder="<?=$xml->search ?? 'Search'?> Brand Name, Description..." 
					   value="<?=htmlspecialchars($search)?>" style="width: 300px;">
			</div>
			
			<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
			<a href="?page=brand" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
		</form>
	</div>
</div>

<?php 
		$sql = "select id, brand_name, des from brand $search_cond order by id desc";
$query=mysqli_query($db->conn,$sql);
?>
<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->description?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'brand.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php 
	while($data=mysqli_fetch_array($query)){
	echo "<tr><td>".e($data['brand_name'])."</td><td>".e($data['des'])."</td>
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'brand.php?id=".intval($data['id'])."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"core-function.php?method=D&id=".intval($data['id'])."&page=brand\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";		
	}
?>

</table>