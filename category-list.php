<?php
require_once("inc/security.php");
require_once("inc/class.company_filter.php");

// Get company filter instance
$companyFilter = CompanyFilter::getInstance();

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition with company filter
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (cat_name LIKE '%$search_escaped%' OR des LIKE '%$search_escaped%')";
}
?>
<h2><i class="fa fa-cog"></i> <?=$xml->category?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
	<div class="panel-heading">
		<i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
	</div>
	<div class="panel-body">
		<form method="get" action="" class="form-inline">
			<input type="hidden" name="page" value="category">
			
			<div class="form-group" style="margin-right: 15px;">
				<input type="text" class="form-control" name="search" 
					   placeholder="<?=$xml->search ?? 'Search'?> Category Name, Description..." 
					   value="<?=htmlspecialchars($search)?>" style="width: 300px;">
			</div>
			
			<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
			<a href="?page=category" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
		</form>
	</div>
</div>

<?php
$sql = "SELECT id, cat_name, des FROM category " . $companyFilter->whereCompanyFilter() . " $search_cond ORDER BY id DESC";
$query=mysqli_query($db->conn, $sql);?>

<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->description?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'category.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data['cat_name']."</td><td>".$data['des']."</td>
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'category.php?id=".$data['id']."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"core-function.php?method=D&id=".$data['id']."&page=category\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
	
	}?>

</table>