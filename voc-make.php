<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/payment-method-helper.php");
$users=new DbConn($config);
// Security already checked in index.php

$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);
?>
<!DOCTYPE html>
<html>

<head>
<style>
/* Modern Voucher Form Styling */
.voucher-container { max-width: 1200px; margin: 0 auto; }
.page-header-vou { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: #fff; padding: 20px 25px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
.page-header-vou h2 { margin: 0; font-size: 24px; font-weight: 600; }
.page-header-vou .btn-back { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 8px 20px; border-radius: 5px; text-decoration: none; transition: all 0.3s; }
.page-header-vou .btn-back:hover { background: rgba(255,255,255,0.3); color: #fff; }

.form-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
.form-card .card-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; }
.form-card .card-header h4 { margin: 0; font-size: 16px; font-weight: 600; color: #333; }
.form-card .card-header h4 i { margin-right: 8px; color: #e74c3c; }
.form-card .card-body { padding: 20px; }

.form-row { display: flex; flex-wrap: wrap; margin: 0 -10px; }
.form-col { padding: 0 10px; margin-bottom: 15px; }
.form-col-2 { width: 16.66%; }
.form-col-3 { width: 25%; }
.form-col-4 { width: 33.33%; }
.form-col-6 { width: 50%; }
.form-col-12 { width: 100%; }

.form-group { margin-bottom: 0; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group .form-control { border-radius: 5px; border: 1px solid #ddd; padding: 10px 12px; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; }
.form-group .form-control:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); outline: none; }
.form-group select.form-control { height: auto; padding: 10px 12px; }

.product-section { border-top: 3px solid #e74c3c; }
.product-section .card-header { background: #e74c3c; color: #fff; }
.product-section .card-header h4 { color: #fff; }
.product-section .card-header h4 i { color: #fff; }

.product-header { display: flex; background: #f8f9fa; padding: 12px 15px; border-radius: 5px; margin-bottom: 10px; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #555; }

.btn-add-row, .btn-remove-row { width: 45px; height: 45px; border-radius: 50%; font-size: 20px; font-weight: bold; border: none; cursor: pointer; transition: all 0.2s; }
.btn-add-row { background: #e74c3c; color: #fff; }
.btn-add-row:hover { background: #c0392b; transform: scale(1.1); }
.btn-remove-row { background: #95a5a6; color: #fff; }
.btn-remove-row:hover { background: #7f8c8d; transform: scale(1.1); }

.btn-submit { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border: none; color: #fff; padding: 12px 40px; font-size: 16px; font-weight: 600; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(231,76,60,0.3); }

.btn-preview { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border: none; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: 600; border-radius: 5px; cursor: pointer; transition: all 0.3s; margin-right: 10px; }
.btn-preview:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(52,152,219,0.3); }
.btn-preview i { margin-right: 5px; }

.action-buttons { display: flex; align-items: center; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }

@media (max-width: 768px) {
    .form-col-2, .form-col-3, .form-col-4 { width: 50%; }
    .form-col-6 { width: 100%; }
}
</style>
<script type="text/javascript">
function checkorder(value,id) {
	var id1 = id.split("[");
	var index = id1[1].split("]");
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
	xmlhttp2=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	xmlhttp2=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      document.getElementById("slotmodel["+index[0]+"]").innerHTML=xmlhttp.responseText;
    }
  }
  xmlhttp.open("GET","makeoptionindex.php?value="+value+"&mode=2&id="+index[0],true);
   xmlhttp.send();
   
    xmlhttp2.onreadystatechange=function() {
    if (xmlhttp2.readyState==4 && xmlhttp2.status==200) {
      document.getElementById("slotbrand["+index[0]+"]").innerHTML=xmlhttp2.responseText;
    }
  }
  xmlhttp2.open("GET","makeoptionindex.php?value="+value+"&mode=1&id="+index[0],true);
   xmlhttp2.send();
}

function checkorder2(value,id) {
	var id1 = id.split("[");
	var index = id1[1].split("]");
	var type = document.getElementById("type["+index[0]+"]").value;
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      document.getElementById("slotmodel["+index[0]+"]").innerHTML=xmlhttp.responseText;
    }
  }
  xmlhttp.open("GET","makeoptionindex.php?value="+value+"&mode=2&value2="+type+"&id="+index[0],true);
   xmlhttp.send();
   
}



	
	
	
	


</script>

   <script type="text/javascript">
$(function(){
	$("#addRow").click(function(){
		var indexthis = document.getElementById("countloop").value;
		document.getElementById("countloop").value=parseInt(indexthis)+1;
		
		var NR ="<tr id=fr["+indexthis+"]> <td style=' margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;'><div id='box' style='width:18%'><select required id='type["+indexthis+"]' name='type["+indexthis+"]' onchange='checkorder(this.value,this.id)' class='form-control'><?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
			echo "<option value='' >Please Select Product</option>";
			while($fetch_customer=mysqli_fetch_array($querycustomer)){
				
			echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[name]."</option>";}?></select></div><div id='box' style='width:18%'><div id='slotbrand["+indexthis+"]'><select id='ban_id["+indexthis+"]' name='ban_id["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:18%'><div id='slotmodel["+indexthis+"]'><select id='model["+indexthis+"]' name='model["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:14%'><div class='input-group'><input type='number' class='form-control' name='quantity["+indexthis+"]' id='quantity["+indexthis+"]' required placeholder='Quantity' value='1' /><span class='input-group-addon'><?=$xml->unit?></span></div></div><input type='hidden' value='1' class='form-control' name='pack_quantity["+indexthis+"]' id='pack_quantity["+indexthis+"]' required placeholder='<?=$xml->unit?>' /><div id='box2'  style='width:15%'><div class='input-group'><input type='text' class='form-control' placeholder='<?=$xml->price?>' required name='price["+indexthis+"]' id='price["+indexthis+"]' /><span class='input-group-addon'><?=$xml->baht?></span></div></div><div id='box' style='width:12%'><input type='text' name='warranty["+indexthis+"]' id='warranty["+indexthis+"]' value='<?=date("d-m-Y")?>' class='form-control'></div></div><div id='box' style='width:5%'><a href='' style='width:100%;' class='btn btn-danger' onclick='del_tr(this);return false;'>x</a></div><div id='box' style='width:100%'><textarea name='des["+indexthis+"]' id='des["+indexthis+"]' placeholder='<?=$xml->notes?>' class='form-control'></textarea></div></td></tr>";
		//$("#myTbl").append($("#firstTr").clone());
		$("#myTbl").append($(NR));
	});
	$("#removeRow").click(function(){
		if($("#myTbl tr").size()>1){
			$("#myTbl tr:last").remove();
		}else{
			alert("Don't Remove");
		}
	});			
});


</script>  

<script type='text/javascript'>
function del_tr(remtr)  
{   
    while((remtr.nodeName.toLowerCase())!='tr')
        remtr = remtr.parentNode;

    remtr.parentNode.removeChild(remtr);
}
function del_id(id)  
{   
        del_tr(document.getElementById(id));
}
</script>
</head>

<body><?php 

$queryvou=mysqli_query($db->conn, "select * from voucher where id='".$id."' and vender='".$com_id."'");
if(mysqli_num_rows($queryvou)==1){$mode="E";
$fetvou=mysqli_fetch_array($queryvou);
}else{$mode="A";}

?>

<div class="voucher-container">

<!-- Page Header -->
<div class="page-header-vou">
    <h2><i class="glyphicon glyphicon-tags"></i> <?=$mode=='E' ? ($xml->editvoucher ?? 'Edit Voucher') : ($xml->newvoucher ?? 'New Voucher')?></h2>
    <a href="index.php?page=voucher_list" class="btn-back"><i class="glyphicon glyphicon-arrow-left"></i> <?=$xml->back ?? 'Back to List'?></a>
</div>

<form action="core-function.php" method="post" id="company-form">

<!-- Vendor/Payee Information Card -->
<div class="form-card">
    <div class="card-header">
        <h4><i class="glyphicon glyphicon-user"></i> <?=$xml->vendorinfo ?? 'Vendor / Payee Information'?></h4>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-col form-col-4">
                <div class="form-group">
                    <label for="name"><?=$xml->name ?? 'Name'?> *</label>
                    <input id="name" name="name" class="form-control" required placeholder="<?=$xml->name ?? 'Vendor Name'?>" value="<?=$fetvou['name']?>" type="text">
                </div>
            </div>
            <div class="form-col form-col-4">
                <div class="form-group">
                    <label for="email"><?=$xml->email ?? 'Email'?></label>
                    <input id="email" class="form-control" type="email" value="<?=$fetvou['email']?>" name="email" placeholder="email@example.com">
                </div>
            </div>
            <div class="form-col form-col-4">
                <div class="form-group">
                    <label for="phone"><?=$xml->phone ?? 'Phone'?></label>
                    <input id="phone" class="form-control" value="<?=$fetvou['phone']?>" placeholder="+66 xxx xxx xxxx" type="text" name="phone">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Voucher Settings Card -->
<div class="form-card">
    <div class="card-header">
        <h4><i class="glyphicon glyphicon-cog"></i> <?=$xml->vouchersettings ?? 'Voucher Settings'?></h4>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-col form-col-3">
                <div class="form-group">
                    <label for="brandven"><?=$xml->brand ?? 'Brand/Logo'?></label>
                    <select id="brandven" name="brandven" class="form-control">
                        <?php 
                        if($fetvou['brand']==0)
                            echo "<option value='0' selected>Use Default</option>";
                        else
                            echo "<option value='0'>Use Default</option>";
                        
                        $querycustomer=mysqli_query($db->conn, "select brand_name,id from brand where ven_id='".$com_id."' ");
                        while($fetch_customer=mysqli_fetch_array($querycustomer)){
                            if($fetch_customer['id']==$fetvou['brand'])
                                echo "<option selected value='".$fetch_customer['id']."'>".$fetch_customer['brand_name']."</option>"; 
                            else 
                                echo "<option value='".$fetch_customer['id']."'>".$fetch_customer['brand_name']."</option>";
                        }?>
                    </select>
                </div>
            </div>
            <div class="form-col form-col-2">
                <div class="form-group">
                    <label for="vat"><?=$xml->vat ?? 'VAT'?> *</label>
                    <div class="input-group">
                        <input class="form-control" required name="vat" type="number" step="0.01" value="<?=$fetvou['vat'] ?? 7?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
            </div>
            <div class="form-col form-col-2">
                <div class="form-group">
                    <label for="dis"><?=$xml->discount ?? 'Discount'?></label>
                    <div class="input-group">
                        <input class="form-control" required name="dis" type="number" step="0.01" value="<?=$fetvou['discount'] ?? 0?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
            </div>
            <div class="form-col form-col-3">
                <div class="form-group">
                    <label for="payment_method"><?=$xml->paymentmethod ?? 'Payment Method'?></label>
                    <select id="payment_method" name="payment_method" class="form-control">
                        <?=renderPaymentMethodOptions($db->conn, $fetvou['payment_method'] ?? '', $xml)?>
                    </select>
                </div>
            </div>
            <div class="form-col form-col-2">
                <div class="form-group">
                    <label for="status"><?=$xml->status ?? 'Status'?></label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" <?=$fetvou['status']=='draft'?'selected':''?>><?=$xml->draft ?? 'Draft'?></option>
                        <option value="confirmed" <?=($fetvou['status']=='confirmed' || $mode=='A')?'selected':''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                        <option value="cancelled" <?=$fetvou['status']=='cancelled'?'selected':''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Selection Card -->
<div class="form-card product-section">
    <div class="card-header">
        <h4><i class="glyphicon glyphicon-shopping-cart"></i> <?=$xml->pleaseselectproduct ?? 'Products / Services'?></h4>
    </div>
    <div class="card-body">
        <div class="product-header">
            <div style="width:18%; margin-left:0.5%"><?=$xml->product ?? 'Product'?></div>
            <div style="width:18%;"><?=$xml->brand ?? 'Brand'?></div>
            <div style="width:18%;"><?=$xml->model ?? 'Model'?></div>
            <div style="width:14%;"><?=$xml->unit ?? 'Qty'?></div>
            <div style="width:15%;"><?=$xml->price ?? 'Price'?></div> 
            <div style="width:10%;"><?=$xml->warranty ?? 'Date'?></div>
            <div style="width:5%;"></div>
        </div> 
<table id="myTbl" class ="table" width="100%" border="0" cellpadding="0" cellspacing="0">
<?php $i=0;

if($mode=="A"){?>
									
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
			echo "<option value='' >Please Select Product</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="ban_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysqli_query($db->conn, "select brand_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$data_pro[type]."'");
echo "<option value='' >Please Select Brand</option>";
while($fetch_customer=mysqli_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer[id];?>' <?php if($fetch_customer[id]==$data_pro[ban_id]) echo "selected";?> ><?php echo $fetch_customer[brand_name];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select model_name,id from model where brand_id='".$data_pro[ban_id]."' and type_id='".$data_pro[type]."'");
			if(mysqli_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[model]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[model_name]."</option>";
				}?>
		</select></div></div>
      
        <div id="box" style="width:14%"><div class="input-group">
<input type="number" class="form-control" name="quantity[<?=$i?>]" value="1" id="quantity[<?=$i?>]" required placeholder="Quantity" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
      <input type="hidden" class="form-control" name="pack_quantity[<?=$i?>]" id="pack_quantity[<?=$i?>]" required value='1' />
 <div id="box2" style="width:15%"><div class="input-group"><input type="text" class="form-control" placeholder="Price" required name="price[<?=$i?>]" id="price[<?=$i?>]"  value="<?php echo $data_pro[price];?>"/><span class="input-group-addon"><?=$xml->baht?></span></div></div>
  
 <div id="box" style="width:12%">
  <input type="text" name="warranty[<?=$i?>]" id="warranty[<?=$i?>]" value="<?=date("d-m-Y")?>" placeholder="warranty" class="form-control">
 </div>  

<div id="box" style="width:5%"><a href='' style="width:100%;" class="btn btn-danger" onclick='del_tr(this);return false;'>x</a></div>
<div id="box" style="width:100%; ">
<textarea name="des[<?=$i?>]" id="des[<?=$i?>]" placeholder="<?=$xml->notes?>"  class="form-control"><?=$data_pro[des];?></textarea></div>
</td>
  </tr>

  <?php $i++;}else
if($mode=="E"){
	$query_pro=mysqli_query($db->conn, "select pro_id,price,type,ban_id,model,quantity,pack_quantity,des,vo_id,DATE_FORMAT(vo_warranty,'%d-%m-%Y') as vo_warranty from product where vo_id='".$id."'");$i=0;

while($data_pro=mysqli_fetch_array($query_pro)){?>
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
			echo "<option value='' >Please Select Product</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="ban_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysqli_query($db->conn, "select brand_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$data_pro[type]."'");
echo "<option value='' >Please Select Brand</option>";
while($fetch_customer=mysqli_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer[id];?>' <?php if($fetch_customer[id]==$data_pro[ban_id]) echo "selected";?> ><?php echo $fetch_customer[brand_name];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select model_name,id from model where brand_id='".$data_pro[ban_id]."' and type_id='".$data_pro[type]."'");
			if(mysqli_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[model]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[model_name]."</option>";
				}?>
		</select></div></div>
      
        <div id="box" style="width:14%"><div class="input-group">
<input type="number" class="form-control" name="quantity[<?=$i?>]" value="<?php echo $data_pro[quantity];?>" id="quantity[<?=$i?>]" required placeholder="Quantity" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
      <input type="hidden" class="form-control" name="pack_quantity[<?=$i?>]" id="pack_quantity[<?=$i?>]" required value="1" />
 <div id="box2" style="width:15%"><div class="input-group"><input type="text" class="form-control" placeholder="Price" required name="price[<?=$i?>]" id="price[<?=$i?>]"  value="<?php echo $data_pro[price];?>"/><span class="input-group-addon"><?=$xml->baht?></span></div></div>
  
 <div id="box" style="width:12%">
  <input type="text" name="warranty[<?=$i?>]" id="warranty[<?=$i?>]" value="<?php echo $data_pro[vo_warranty];?>" placeholder="warranty" class="form-control">
 </div>  

<div id="box" style="width:5%"><a href='' style="width:100%;" class="btn btn-danger" onclick='del_tr(this);return false;'>x</a></div>

 <div id="box" style="width:100%; ">
<textarea name="des[<?=$i?>]" id="des[<?=$i?>]" placeholder="<?=$xml->notes?>"  class="form-control"><?=$data_pro[des];?></textarea></div>
</td>
  </tr>

  <?php $i++;}}?>

                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button id="addRow" class="btn-add-row" type="button" title="Add Row">+</button>
            <button id="removeRow" class="btn-remove-row" type="button" title="Remove Row">−</button>
            <div style="flex-grow:1;"></div>
            <input type="hidden" id="countloop" name="countloop" value="<?=$i?>">
            <input type="hidden" name="method" value="<?=$mode?>">
            <input type="hidden" name="page" value="voucher_list">
            <input type="hidden" name="id" value="<?php echo $id;?>">
            <?php if($mode == 'E' && $id): ?>
            <button type="button" class="btn-preview" onclick="previewVoucher(<?=$id?>)"><i class="glyphicon glyphicon-eye-open"></i> <?=$xml->preview ?? 'Preview PDF'?></button>
            <?php endif; ?>
            <button type="submit" class="btn-submit"><i class="glyphicon glyphicon-ok"></i> <?=$xml->save ?? 'Save Voucher'?></button>
        </div>
    </div>
</form>

</div><!-- /voucher-container -->

<script>
// Preview Voucher PDF
function previewVoucher(voucherId) {
    if (!voucherId) {
        alert('Please save the voucher first before previewing.');
        return;
    }
    window.open('index.php?page=vou_print&id=' + voucherId, '_blank');
}
</script>

</body>
</html>