
<?php
	// require_once("inc/sys.configs.php");
	// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
	// $db = new DbConn($config);
	// $users->checkSecurity();
?>

<h2><i class="fa fa-home fa-fw"></i> <?=$xml->company?></h2>
<?php
if($_SESSION['com_id']!=""){
	$sql="select id, name_en, contact,vender,customer, email, phone from company where id='".$_SESSION['com_id']."'";
}else {
	$sql="select id, name_en,vender,customer, contact, email, phone from company order by id desc";
}

$query = mysqli_query($db->conn, $sql);
?>

<div id="fetch_state"></div>
<table width="100%" class="table table-hover"><tr><th><?=$xml->name?></th><th><?=$xml->contact?></th><th><?=$xml->email?></th><th><?=$xml->phone?></th><th width="150">
<?php if($_SESSION['com_id']==""){?>
<a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a><?php }?></th></tr>
<?php while($data=mysqli_fetch_array($query)){
	if(($data['vender']=="1")&&($data['customer']=="1")){
		$owner.="<tr><td>".$data['name_en']."</td>
<td>".$data['contact']."</td>	
<td>".$data['email']."</td>	
<td>".$data['phone']."</td>
<td>";if($_SESSION['com_id']==""){ $owner.="<a href='remoteuser.php?id=".$data['id']."'><i class=\"fa fa-share-square\"></i></a>&nbsp;&nbsp;&nbsp;";} $owner.="<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'company.php?id=".$data['id']."', true);\"><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'company-addr.php?id=".$data['id']."', true);\"><i class=\"fa fa-truck\"></i></a>&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'credit-list.php?id=".$data['id']."', true);\"><i class=\"fa fa-credit-card\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	
		}else{$other.="<tr><td>".$data['name_en']."</td>
<td>".$data['contact']."</td>	
<td>".$data['email']."</td>	
<td>".$data['phone']."</td>
<td>";if($_SESSION['com_id']==""){ $other.="<a href='remoteuser.php?id=".$data['id']."'><i class=\"fa fa-share-square\"></i></a>&nbsp;&nbsp;&nbsp;";} $other.="<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'company.php?id=".$data['id']."', true);\"><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'company-addr.php?id=".$data['id']."', true);\"><i class=\"fa fa-truck\"></i></a>&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'credit-list.php?id=".$data['id']."', true);\"><i class=\"fa fa-credit-card\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td></tr>";	}

	
	}?>
<?=$owner?><tr><td colspan="5" height="30"><br><b><?=$xml->other?></b></td></tr>
<?=$other?>
</table>