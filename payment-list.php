<?php
require_once("inc/security.php");

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (payment_name LIKE '%$search_escaped%' OR payment_des LIKE '%$search_escaped%')";
}
?>
<h2><i class="fa fa-money"></i> <?=$xml->payment?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="payment">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Payment Name, Description..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 300px;">
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=payment" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<?php
$query=mysqli_query($db->conn, "select id, payment_name,payment_des from payment where  com_id='".$_SESSION['com_id']."' $search_cond order by id desc");?>

<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->description?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'payment.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data[payment_name]."</td><td>".$data[payment_des]."</td>
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'payment.php?id=".$data[id]."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
	
	}?>

</table>