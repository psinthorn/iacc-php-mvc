
<script type="text/javascript" language="javascript" src="TableFilter/tablefilter.js"></script> <h2><i class="fa fa-thumbs-up"></i> <?=$xml->taxinvoice?></h2><?php
// $users->checkSecurity();

?>

<table width="100%" id="table1" class="table table-hover">

<tr><th><?=$xml->customer?></th><th><?=$xml->taxno?></th><th><?=$xml->name?></th><th width="100"><?=$xml->createdate?></th><th colspan="2"><?=$xml->status?></th></tr>
<?php

$query=mysqli_query($db->conn, "select purchase_order.id as id,countmailtax, purchase_order.name as name,texiv_rw, DATE_FORMAT(texiv_create,'%d-%m-%Y') as texiv_create, name_en, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.customer_id=company.id join invoice on purchase_order.id=invoice.tex where po_id_new='' and vendor_id='".$_SESSION['company_id']."' and status='5' and status_iv='1' order by texiv_rw desc");
$cot=0;
 while($data=mysqli_fetch_array($query)){
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	$var=decodenum($data['status']) ;
	 $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
echo "<tr ".$color."><td>".$data['name_en']."</td><td>TAX-".str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)."</td><td>".$data['name']."</td><td>".$data['texiv_create']."</td><td>".$xml->$var."</td><td><a href='taxiv.php?id=".$data['id']."' target='blank'>TAX-IV</a>&nbsp;&nbsp;&nbsp;<a data-toggle='modal' href='model_mail.php?page=tax&id=".$data['id']."'   data-target='.bs-example-modal-lg'><i class='glyphicon glyphicon-envelope'></i><span class='badge'>".$data['	countmailtax']."</span></a></td>
</tr>";
	
	}?>
 
<tr><th><?=$xml->vender?></th><th><?=$xml->taxno?></th><th><?=$xml->name?></th><th><?=$xml->createdate?></th><th colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select purchase_order.id as id, purchase_order.name as name, invoice.id as tax, texiv_rw, DATE_FORMAT(texiv_create,'%d-%m-%Y') as texiv_create, name_en, status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id join company on purchase_request.vendor_id=company.id  join invoice on purchase_order.id=invoice.tex  where  po_id_new='' and purchase_request.customer_id='".$_SESSION['company_id']."' and status='5' and status_iv='1' order by texiv_rw desc ");
$cot=0;
 while($data=mysqli_fetch_array($query)){
	  $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
$var=decodenum($data['status']);
echo "<tr ".$color."><td>".$data['name_en']."</td><td>TAX-".str_pad($data['texiv_rw'], 8, "0", STR_PAD_LEFT)."</td><td>".$data['name']."</td><td>".$data['texiv_create']."</td><td>".$xml->$var."</td><td><a href='taxiv.php?id=".$data['id']."' target='blank'>TAX-IV</a></td>
</tr>";	
	
	}?>

</table>

 <script type="text/javascript">
 
  var table2_Props = {
    col_0: "select",
	col_5: "none",
    col_date_type: [null,null,null,'dmy',null,null],  
    display_all_text: " [ Show all ] ",
    on_filters_loaded: function(o){   
        o.SetFilterValue(3,'>01-09-2014');  
        o.Filter();  
    } ,
    sort_select: true
};
var tf2 = setFilterGrid("table1", table2_Props,2);</script>
<div id="fetch_state"></div>