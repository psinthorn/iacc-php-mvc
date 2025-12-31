<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
</head>

<body><h2><i class="fa fa-credit-card"></i> <?=$xml->credit?></h2><?php
$users->checkSecurity();?>
<?php
$query=mysql_query("select company_credit.id as id, name_sh, limit_credit, limit_day, valid_start, valid_end from company_credit join company on company_credit.vendor_id=company.id where valid_end='0000-00-00' and customer_id='".$_REQUEST[id]."'");?>
<table width="100%" class="table"><tr><th><?=$xml->vender?></th><th><?=$xml->limitcredit?></th><th><?=$xml->limitday?></th><th><?=$xml->start?></th><th></th></tr>
<?php while($data=mysql_fetch_array($query)){
echo "<tr><td>".$data[name_sh]."</td>
<td>".$data[limit_credit]."</td>	
<td>".$data[limit_day]."</td>	
<td>".$data[valid_start]."</td>	
<td></td></tr>";	
	
	}?>
    <tr><td colspan="5"><br></td></tr>
<?php
$query=mysql_query("select company_credit.id as id, name_sh, limit_credit, limit_day, valid_start, valid_end from company_credit join company on company_credit.customer_id=company.id where valid_end='0000-00-00' and vendor_id='".$_REQUEST[id]."'");?>
<tr><th><?=$xml->customer?></th><th><?=$xml->limitcredit?></th><th><?=$xml->limitday?></th><th><?=$xml->start?></th><th width="120"><a href="#" onclick="ajaxpagefetcher.load('fetch_state2', 'company-credit.php?vendor_id=<?php echo $_REQUEST[id];?>', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a></th></tr>
<?php while($data=mysql_fetch_array($query)){
echo "<tr><td>".$data[name_sh]."</td>
<td>".$data[limit_credit]."</td>	
<td>".$data[limit_day]."</td>	
<td>".$data[valid_start]."</td>		
<td><a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state2', 'company-credit.php?id=".$data[id]."&vendor_id=".$_REQUEST[id]."', true);\"><i class=\"fa fa-pencil-square-o\"></i></a></td></tr>";	
	
	}?>

</table>
<div id="fetch_state2"></div>
</body></html>