<h2><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder?></h2><?php
$db->checkSecurity();

?>

<table width="100%" class="table table-hover">
<tr><td colspan="6"><?=$xml->purchasingorder?> - <?=$xml->out?></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->pono?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->status?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select purchase_order.id as id,cancel, purchase_order.name as name, purchase_order.tax as tax, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.customer_id=company.id where po_id_new='' and vendor_id='".$_SESSION['company_id']."' and status='2' order by cancel,purchase_order.id desc");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".$data['name']."</td><td>PO-".$data['tax']."</td><td>".$data['name_en']."</td><td>".$data['valid_pay']."</td>";

$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><!--<a href='index.php?page=".$pg."&id=".$data['id']."&action=m'><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".$data['id']."&action=c'><i class=\"fa fa-magic\"></i></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><!--<a href='index.php?page=".$pg."&id=".$data['id']."&action=m'><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".$data['id']."&action=c'><i class=\"fa fa-magic\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	}


	}?>
 
 <tr>
   <td colspan="6"><?=$xml->purchasingorder?> - <?=$xml->in?></td></tr>
 
<tr><th><?=$xml->vender?></th><th><?=$xml->pono?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->status?></th><th width="120"></th></tr>
<?php
$query=mysqli_query($db->conn, "select purchase_order.id as id, purchase_order.name as name, purchase_order.tax as tax,cancel, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.vendor_id=company.id where po_id_new='' and customer_id='".$_SESSION['company_id']."' and status='2' order by cancel,purchase_order.id desc ");

 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".$data['name_en']."</td><td>PO-".$data['tax']."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td>";



$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=po_view&id=".$data['id']."'><i class=\"fa fa-dropbox\"></i></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><a href='index.php?page=po_view&id=".$data['id']."'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".$data['id']."&action=c'><i class=\"fa fa-magic\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	}
	
	}?>
 

</table>
<div id="fetch_state"></div>