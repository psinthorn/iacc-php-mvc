<h2><i class="fa fa-shopping-cart"></i> <?=$xml->purchasingorder?></h2><?php
$users->checkSecurity();

?>

<table width="100%" class="table table-hover">
<tr><td colspan="6"><?=$xml->purchasingorder?> - <?=$xml->out?></td></tr>
<tr><th><?=$xml->customer?></th><th><?=$xml->pono?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->status?></th><th width="120"></th></tr>
<?php
$query=mysql_query("select po.id as id,cancel, po.name as name, po.tax as tax, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id where po_id_new='' and ven_id='".$_SESSION[com_id]."' and status='2' order by cancel,po.id desc");

 while($data=mysql_fetch_array($query)){
	 if($data[status]==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".$data[name]."</td><td>PO-".$data[tax]."</td><td>".$data[name_en]."</td><td>".$data[valid_pay]."</td>";

$var=decodenum($data[status]);
if($data[cancel]=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><!--<a href='index.php?page=".$pg."&id=".$data[id]."&action=m'><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".$data[id]."&action=c'><i class=\"fa fa-magic\"></i></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><!--<a href='index.php?page=".$pg."&id=".$data[id]."&action=m'><i class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".$data[id]."&action=c'><i class=\"fa fa-magic\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data[id]."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	}


	}?>
 
 <tr>
   <td colspan="6"><?=$xml->purchasingorder?> - <?=$xml->in?></td></tr>
 
<tr><th><?=$xml->vender?></th><th><?=$xml->pono?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->status?></th><th width="120"></th></tr>
<?php
$query=mysql_query("select po.id as id, po.name as name, po.tax as tax,cancel, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id where po_id_new='' and cus_id='".$_SESSION[com_id]."' and status='2' order by cancel,po.id desc ");

 while($data=mysql_fetch_array($query)){
	 if($data[status]==2)$pg="po_deliv";else $pg="po_edit";
	 
echo "<tr><td>".$data[name_en]."</td><td>PO-".$data[tax]."</td><td>".$data[name]."</td><td>".$data[valid_pay]."</td>";



$var=decodenum($data[status]);
if($data[cancel]=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=po_view&id=".$data[id]."'><i class=\"fa fa-dropbox\"></i></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><a href='index.php?page=po_view&id=".$data[id]."'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;--><a href='index.php?page=".$pg."&id=".$data[id]."&action=c'><i class=\"fa fa-magic\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data[id]."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	}
	
	}?>
 

</table>
<div id="fetch_state"></div>