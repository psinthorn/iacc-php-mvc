<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
// Security already checked in index.php?>
<!DOCTYPE html>
<html>

<head>
</head>

<body><h2><i class="fa fa-credit-card"></i> <?=$xml->credit?></h2><?php
// Security already checked in index.php
$id = sql_int($_REQUEST['id']);
?>
<?php
$query=mysqli_query($db->conn, "select company_credit.id as id, name_sh, limit_credit, limit_day, valid_start, valid_end from company_credit join company on company_credit.ven_id=company.id where valid_end='0000-00-00' and cus_id='".$id."'");?>
<table width="100%" class="table"><tr><th><?=$xml->vender?></th><th><?=$xml->limitcredit?></th><th><?=$xml->limitday?></th><th><?=$xml->start?></th><th></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data[name_sh]."</td>
<td>".$data[limit_credit]."</td>	
<td>".$data[limit_day]."</td>	
<td>".$data[valid_start]."</td>	
<td></td></tr>";	
	
	}?>
    <tr><td colspan="5"><br></td></tr>
<?php
$query=mysqli_query($db->conn, "select company_credit.id as id, name_sh, limit_credit, limit_day, valid_start, valid_end from company_credit join company on company_credit.cus_id=company.id where valid_end='0000-00-00' and ven_id='".$id."'");?>
<tr><th><?=$xml->customer?></th><th><?=$xml->limitcredit?></th><th><?=$xml->limitday?></th><th><?=$xml->start?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state2', 'company-credit.php?ven_id=<?php echo $id;?>', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data[name_sh]."</td>
<td>".$data[limit_credit]."</td>	
<td>".$data[limit_day]."</td>	
<td>".$data[valid_start]."</td>		
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state2', 'company-credit.php?id=".$data[id]."&ven_id=".$id."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a></td></tr>";	
	
	}?>

</table>
<div id="fetch_state2"></div>
</body></html>