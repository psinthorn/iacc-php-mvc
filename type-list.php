<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
$db=new DbConn($config);
$db->checkSecurity();

// Get company filter instance
$companyFilter = CompanyFilter::getInstance();

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (type.name LIKE '%$search_escaped%' OR cat_name LIKE '%$search_escaped%')";
}
?>
	<h2><i class="fa fa-ticket"></i> <?=$xml->product?></h2>

	<!-- Search and Filter Panel -->
	<div class="panel panel-default">
		<div class="panel-heading">
			<i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
		</div>
		<div class="panel-body">
			<form method="get" action="" class="form-inline">
				<input type="hidden" name="page" value="type">
				
				<div class="form-group" style="margin-right: 15px;">
					<input type="text" class="form-control" name="search" 
						   placeholder="<?=$xml->search ?? 'Search'?> Product Name, Category..." 
						   value="<?=htmlspecialchars($search)?>" style="width: 300px;">
				</div>
				
				<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
				<a href="?page=type" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
			</form>
		</div>
	</div>

	<?php
		$query=mysqli_query($db->conn, "SELECT type.id as id, name, cat_name FROM type JOIN category ON type.cat_id=category.id WHERE 1=1 " . $companyFilter->andCompanyFilter('type') . " $search_cond ORDER BY type.id DESC");
	?>

		<div id="fetch_state"></div>
		<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->category?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'type.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
	<?php 
	while($data=mysqli_fetch_array($query)){
		echo "<tr><td>".$data['name']."</td><td>".$data['cat_name']."</td>
		<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'type.php?id=".$data['id']."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"core-function.php?method=D&id=".$data['id']."&page=type\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
		}
	?>
</table>