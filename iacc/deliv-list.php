<h2><i class="fa fa-truck"></i> <?=$xml->deliverynote?><div style="float:right; font-size:20px; padding-top:7px;"><a href="?page=deliv_make" style="text-decoration:none;"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create." ".$xml->deliverynote;?></a></div></h2><?php
$db->checkSecurity();

?>

<table width="100%" class="table table-hover">

<tr><th><?=$xml->customer?></th><th><?=$xml->dnno?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->deliverydate?></th><th><?=$xml->status?></th><th></th></tr>
<?php
$query=mysqli_query($db->conn, "select deliver.id as id2,purchase_order.id as id, purchase_order.name as name,  DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.customer_id=company.id join deliver on purchase_order.id=deliver.purchase_order_id  where po_id_new='' and vendor_id='" . mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '') . "' and status='3' order by deliver.id desc ");

 while($data=mysqli_fetch_array($query)){

	 $var=decodenum($data['status']);
echo "<tr><td>".$data['name_en']."</td><td>DN-".str_pad($data['id2'], 8, "0", STR_PAD_LEFT)."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td><td>".$data['deliver_date']."</td><td>".$xml->$var."</td><td><a href='index.php?page=deliv_view&id=".$data['id2']."'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data['id2']."' target='blank'>DN</a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";
	
	}?>
    
    
    
    
    
    
    <?php
$query=mysqli_query($db->conn, "select send_out_item.id as id2,deliver.id as id,send_out_item.tmp as des,name_en,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from send_out_item join deliver on send_out_item.id=deliver.output_id join company on send_out_item.customer_id=company.id where vendor_id='" . mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '') . "' and deliver.id not in (select deliver_id from receive) order by deliver.id desc ");

 while($data=mysqli_fetch_array($query)){

	 
echo "<tr><td>".$data['name_en']."</td><td>DN-".str_pad($data['id'], 8, "0", STR_PAD_LEFT)."(make)</td><td>".$data['des']."</td><td></td><td>".$data['deliver_date']."</td><td>".$xml->processdeli."</td><td><a href='index.php?page=deliv_view&id=".$data['id']."&modep=ad'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;<a href='index.php?page=deliv_edit&id=".$data['id']."&modep=ad'><span class='glyphicon glyphicon-edit'></span></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data['id']."&modep=ad' target='blank'>DN</a></td>
</tr>";
	
	}?>
 
<tr><th><?=$xml->vender?></th><th><?=$xml->dnno?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->deliverydate?></th><th><?=$xml->status?></th><th></th></tr>
<?php
$query=mysqli_query($db->conn, "select deliver.id as id2,purchase_order.id as id, purchase_order.name as name, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.vendor_id=company.id join deliver on purchase_order.id=deliver.purchase_order_id where po_id_new='' and purchase_request.customer_id='" . mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '') . "' and status='3' order by deliver.id desc");

 while($data=mysqli_fetch_array($query)){
	$var=decodenum($data['status']);
echo "<tr><td>".$data['name_en']."</td><td>DN-".str_pad($data['id2'], 8, "0", STR_PAD_LEFT)."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td><td>".$data['deliver_date']."</td><td>".$xml->$var."</td><td><a href='index.php?page=deliv_view&id=".$data['id2']."'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data['id2']."' target='blank'>R</a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";	
	
	}?>
      <?php
$query=mysqli_query($db->conn, "select send_out_item.id as id2,deliver.id as id,send_out_item.tmp as des,name_en,DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date from send_out_item join deliver on send_out_item.id=deliver.output_id join company on send_out_item.vendor_id=company.id where customer_id='" . mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '') . "' and deliver.id not in (select deliver_id from receive) order by deliver.id desc");

 while($data=mysqli_fetch_array($query)){

	echo "<tr><td>".$data['name_en']."</td><td>DN-".str_pad($data['id'], 7, "0", STR_PAD_LEFT)."(make)</td><td>".$data['des']."</td><td></td><td>".$data['deliver_date']."</td><td>".$xml->processdeli."</td><td><a href='index.php?page=deliv_view&id=".$data['id']."&modep=ad'><i class=\"fa fa-dropbox\"></i></a>&nbsp;&nbsp;&nbsp;<a href='rec.php?id=".$data['id']."&modep=ad' target='blank'>DN</a></td>
</tr>";
	
	
	}?>
 

</table>
<div id="fetch_state"></div>