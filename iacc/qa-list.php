<script type="text/javascript" language="javascript" src="TableFilter/tablefilter.js"></script>  
 <h2><i class="fa fa-shopping-cart"></i> <?=$xml->quotation?></h2>
 <?php
//$users->checkSecurity();
<table width="100%" id="table1" class="table table-hover">
<thead>

<tr><td colspan="6"><?=$xml->quotation?> - <?=$xml->out?></td></tr>
<tr><th width="30%"><?=$xml->customer?></th><th width="15%"><?=$xml->quono?></th><th width="15%"><?=$xml->price?></th><th width="13%"><?=$xml->duedate?></th><th width="27%" colspan="2"><?=$xml->status?></th></tr></thead>
<tbody>
<?php
$query=mysqli_query($db->conn, "select purchase_order.id as id, purchase_order.name as name, purchase_order.tax as tax,mailcount, cancel,DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en,vat,dis,over, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.customer_id=company.id where po_id_new='' and vendor_id='".$_SESSION['company_id']."' and status='1'  order by cancel,purchase_order.id  desc ");
 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 	$que_pro=mysqli_query($db->conn, "select product.des as des,product_type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$data['id']."'");
	 	$summary=$total=0;
	 while($data_pro=mysqli_fetch_array($que_pro)){
		if($cklabour['cklabour']==1){	
		$equip=$data_pro['price']*$data_pro['quantity'];
		$labour1=$data_pro['valuelabour']*$data_pro['activelabour'];
		$labour=$labour1*$data_pro['quantity'];
		$total=$equip+$labour;}else 
		{$total=$data_pro['price']*$data_pro['quantity'];}
	 	$summary+=$total;

}
	 
 	$disco=$summary*$data['dis']/100;
 	$stotal=$summary-$disco;
 	$overh=$stotal*$data['over']/100;
	$stotal=$stotal+$overh;
	$vat=$stotal*$data['vat']/100;
 	$total=$stotal+$vat;
	 
	 
echo "<tr><td>".$data['name_en']."</td><td>QUO-".$data['tax']."</td><td>".number_format($stotal,2)." / ".number_format($total,2)."</td><td>".$data['valid_pay']."</td>";


$var=decodenum($data['status']);

if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=".$pg."&id=".$data['id']."&action=m' title='edit'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a href='index.php?page=po_view&id=".$data['id']."'  data-toggle='tooltip' data-placement='top' title='Confirm'><i class=\"glyphicon glyphicon-ok\"></i></a>&nbsp;&nbsp;<a href='exp.php?id=".$data['id']."' target='blank'><i class='glyphicon glyphicon-search'></i></a>&nbsp;&nbsp;&nbsp;<a data-toggle='modal' href='model_mail.php?page=exp&id=".$data['id']."'   data-target='.bs-example-modal-lg'><i class='glyphicon glyphicon-envelope'></i><span class='badge'>".$data['mailcount']."</span></a></td>
</tr>";}else
{echo "<td>".$xml->$var."</td><td><a href='index.php?page=".$pg."&id=".$data['id']."&action=m' title='edit'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a href='index.php?page=po_view&id=".$data['id']."'  data-toggle='tooltip' data-placement='top' title='Confirm'><i class=\"glyphicon glyphicon-ok\"></i></a>&nbsp;&nbsp;<a href='exp.php?id=".$data['id']."' target='blank'><i class='glyphicon glyphicon-search'></i></a>&nbsp;&nbsp;&nbsp;<a data-toggle='modal' href='model_mail.php?page=exp&id=".$data['id']."'   data-target='.bs-example-modal-lg'><i class='glyphicon glyphicon-envelope'></i><span class='badge'>".$data['mailcount']."</span></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";}
	
	}?>
 
 <tr>
   <td colspan="6"><?=$xml->quotation?> - <?=$xml->in?></td></tr>
 
<tr><th><?=$xml->vender?></th><th><?=$xml->quono?></th><th><?=$xml->price?></th><th><?=$xml->duedate?></th><th width="30%" colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select purchase_order.id as id, purchase_order.name as name, purchase_order.tax as tax, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en,vat,dis,over, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.vendor_id=company.id where po_id_new='' and customer_id='".$_SESSION['company_id']."' and status='1'  order by cancel,purchase_order.id desc ");
 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	  	$que_pro=mysqli_query($db->conn, "select product.des as des,product_type.name as name,product.price as price,discount,model.model_name as model,quantity,pack_quantity,valuelabour,activelabour from product join type on product.type=product_type.id join model on product.model=model.id where purchase_order_id='".$data['id']."'");
		$summary=$total=0;
	 while($data_pro=mysqli_fetch_array($que_pro)){
		if($cklabour['cklabour']==1){	
		$equip=$data_pro['price']*$data_pro['quantity'];
		$labour1=$data_pro['valuelabour']*$data_pro['activelabour'];
		$labour=$labour1*$data_pro['quantity'];
		$total=$equip+$labour;}else 
		{$total=$data_pro['price']*$data_pro['quantity'];}
		$summary+=$total;
	}
	 
		$disco=$summary*$data['dis']/100;
		$stotal=$summary-$disco;
		$overh=$stotal*$data['over']/100;
		$stotal=$stotal+$overh;
		$vat=$stotal*$data['vat']/100;
		$total=$stotal+$vat;
		
		echo "<tr><td>".$data['name_en']."</td><td>QUO-".$data['tax']."</td><td>".number_format($stotal,2)." / ".number_format($total,2)."</td><td>".$data['valid_pay']."</td>";



		$var=decodenum($data['status']);
		if($data['cancel']=="1"){
		echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=po_view&id=".$data['id']."'  data-toggle='tooltip' data-placement='top' title='Confirm'><i class=\"glyphicon glyphicon-ok\"></i></a>&nbsp;&nbsp;<a href='exp.php?id=".$data['id']."' target='blank'><i class='glyphicon glyphicon-search'></i></a></td>
		</tr>";}else
		{echo "<td>".$xml->$var."</td><td><a href='index.php?page=po_view&id=".$data['id']."'  data-toggle='tooltip' data-placement='top' title='Confirm'><i class=\"glyphicon glyphicon-ok\"></i></a>&nbsp;&nbsp;<a href='exp.php?id=".$data['id']."' target='blank'><i class='glyphicon glyphicon-search'></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=po_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
		</tr>";}

	}
</tbody>
</table>
 <script type="text/javascript">
 
  var table2_Props = {
    col_0: "select",
    col_4: "none",
	col_5: "none", 
    display_all_text: " [ Show all ] ",
	  col_date_type: [null,null,null,'dmy',null,null], 
	   on_filters_loaded: function(o){   
        o.SetFilterValue(3,'>01-07-2014');    
        o.Filter();  
    } , 
  
    sort_select: true
};
var tf2 = setFilterGrid("table1", table2_Props,2);</script>
<div id="fetch_state"></div>