<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db=new DbConn($config);
$db->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
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
				
			echo "<option value='".$fetch_customer['id']."' >".$fetch_customer['name']."</option>";}?></select></div><div id='box' style='width:18%'><div id='slotbrand["+indexthis+"]'><select id='brand_id["+indexthis+"]' name='brand_id["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:18%'><div id='slotmodel["+indexthis+"]'><select id='model["+indexthis+"]' name='model["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:14%'><div class='input-group'><input type='number' class='form-control' name='quantity["+indexthis+"]' id='quantity["+indexthis+"]' required placeholder='Quantity' value='1' /><span class='input-group-addon'><?=$xml->unit?></span></div></div><input type='hidden' value='1' class='form-control' name='pack_quantity["+indexthis+"]' id='pack_quantity["+indexthis+"]' required placeholder='<?=$xml->unit?>' /><div id='box2'  style='width:15%'><div class='input-group'><input type='text' class='form-control' placeholder='<?=$xml->price?>' required name='price["+indexthis+"]' id='price["+indexthis+"]' /><span class='input-group-addon'><?=$xml->baht?></span></div></div><div id='box' style='width:12%'><input type='text' name='warranty["+indexthis+"]' id='warranty["+indexthis+"]' value='<?=date("d-m-Y")?>' class='form-control'></div></div><div id='box' style='width:5%'><a href='' style='width:100%;' class='btn btn-danger' onclick='del_tr(this);return false;'>x</a></div><div id='box' style='width:100%'><textarea name='des["+indexthis+"]' id='des["+indexthis+"]' placeholder='<?=$xml->notes?>' class='form-control'></textarea></div></td></tr>";
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

<body><?Php 

$queryvou=mysqli_query($db->conn, "select * from voucher where id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "' and vender='" . mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '') . "'");
if(mysqli_num_rows($queryvou)==1){$mode="E";
$fetvou=mysqli_fetch_array($queryvou);
}else{$mode="A";}
		

?>
<div style="float:left; width:auto"><h2><i class="glyphicon glyphicon-tags"></i> <?=$xml->voucher?></h2></div><form action="index.php?page=voucher_list" style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>
<div class="clearfix"></div>


<form action="core-function.php" method="post" id="company-form">

	<div id="box">
		<lable for="name"><?=$xml->name?></lable>
		<input id="name" name="name" class="form-control" required placeholder="<?=$xml->name?>" value="<?=$fetvou[name]?>"  type="text">
	</div>
        <div id="box">
		<lable for="email"><?=$xml->email?></lable>
		<input class="form-control" type="text" value="<?=$fetvou[email]?>"  name="email" placeholder="<?=$xml->email?>">
	</div>
        <div id="box">
		<lable for="phone"><?=$xml->phone?></lable>
		<input class="form-control" value="<?=$fetvou[phone]?>"  placeholder="<?=$xml->phone?>" type="text" name="phone">
	</div>
    <div id="box">
		<lable for="name"><?=$xml->brand?></lable>
		<select id="brandven" name="brandven" class="form-control">
			<?php 
			if($fetvou[brand]==0)
			echo "<option value='0' selected >Use Default</option>";
			else
			echo "<option value='0' >Use Default</option>";
			
			$querycustomer=mysqli_query($db->conn, "select brand_name,id from brand where vendor_id='" . mysqli_real_escape_string($db->conn, $_SESSION['company_id'] ?? '') . "' ");
			
			
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($fetch_customer['id']==$fetvou['brand'])
					echo "<option selected value='".$fetch_customer['id']."' >".$fetch_customer['brand_name']."</option>"; else 	echo "<option value='".$fetch_customer['id']."' >".$fetch_customer['brand_name']."</option>";
				}?>
		</select>
	</div>
 
	
          <div id="box">
		<lable for="name"><?=$xml->vat?></lable>
        <div class="input-group">
		<input class="form-control" required name="vat" type="text" value="<?=$fetvou[vat]?>"><span class="input-group-addon">%</span></div>
	</div>
         <div id="box">
		<lable for="name"><?=$xml->discount?></lable>
        <div class="input-group">
		<input class="form-control" required name="dis" type="text" value="<?=$fetvou[discount]?>"><span class="input-group-addon">%</span></div></div>
    
     
    <div class="clearfix" style="margin-bottom:20px;"></div>
    <h3><?=$xml->pleaseselectproduct?></h3>
    
    <div style="width:18%; float:left; margin-left:0.5%"><?=$xml->product?></div>
    <div style="width:18%; float:left;"><?=$xml->brand?></div>
    <div style="width:18%; float:left;"><?=$xml->model?></div>
    <div style="width:14%; float:left;"><?=$xml->unit?></div>
     <div style="width:15%; float:left;"><?=$xml->price?></div> 
     <div style="width:10%; float:left;"><?=$xml->warranty?></div> 
<table id="myTbl" class ="table" width="100%" border="0" cellpadding="0" cellspacing="0">
<?php $i=0;

if($mode=="A"){?>
									
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
				echo "<option value='' >Please Select Product</option>";
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
							if($data_pro['type']==$fetch_customer['id'])$condition=" selected='selected' ";else $condition="";
						
							echo "<option value='".$fetch_customer['id']."' ".$condition." >".$fetch_customer['name']."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="brand_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="brand_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysqli_query($db->conn, "select brand_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where product_type_id='" . mysqli_real_escape_string($db->conn, $data_pro['type']) . "'");
echo "<option value='' >Please Select Band</option>";
while($fetch_customer=mysqli_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer['id'];?>' <?php if($fetch_customer['id']==$data_pro['brand_id']) echo "selected";?> ><?php echo $fetch_customer['brand_name'];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select model_name,id from model where brand_id='" . mysqli_real_escape_string($db->conn, $data_pro['brand_id']) . "' and product_type_id='" . mysqli_real_escape_string($db->conn, $data_pro['type']) . "'");
			if(mysqli_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro['model']==$fetch_customer['id'])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer['id']."' ".$condition." >".$fetch_customer['model_name']."</option>";
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
	$query_pro=mysqli_query($db->conn, "select product_id,price,type,brand_id,model,quantity,pack_quantity,des,voucher_id,DATE_FORMAT(vo_warranty,'%d-%m-%Y') as vo_warranty from product where voucher_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "'");$i=0;

while($data_pro=mysqli_fetch_array($query_pro)){?>
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
			echo "<option value='' >Please Select Product</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro['type']==$fetch_customer['id'])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer['id']."' ".$condition." >".$fetch_customer['name']."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="brand_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="brand_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysqli_query($db->conn, "select brand_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where product_type_id='" . mysqli_real_escape_string($db->conn, $data_pro['type']) . "'");
echo "<option value='' >Please Select Band</option>";
while($fetch_customer=mysqli_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer['id'];?>' <?php if($fetch_customer['id']==$data_pro['brand_id']) echo "selected";?> ><?php echo $fetch_customer['brand_name'];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select model_name,id from model where brand_id='" . mysqli_real_escape_string($db->conn, $data_pro['brand_id']) . "' and product_type_id='" . mysqli_real_escape_string($db->conn, $data_pro['type']) . "'");
			if(mysqli_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro['model']==$fetch_customer['id'])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer['id']."' ".$condition." >".$fetch_customer['model_name']."</option>";
				}?>
		</select></div></div>
      
        <div id="box" style="width:14%"><div class="input-group">
<input type="number" class="form-control" name="quantity[<?=$i?>]" value="<?php echo $data_pro['quantity'];?>" id="quantity[<?=$i?>]" required placeholder="Quantity" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
      <input type="hidden" class="form-control" name="pack_quantity[<?=$i?>]" id="pack_quantity[<?=$i?>]" required value="1" />
 <div id="box2" style="width:15%"><div class="input-group"><input type="text" class="form-control" placeholder="Price" required name="price[<?=$i?>]" id="price[<?=$i?>]"  value="<?php echo $data_pro['price'];?>"/><span class="input-group-addon"><?=$xml->baht?></span></div></div>
  
 <div id="box" style="width:12%">
  <input type="text" name="warranty[<?=$i?>]" id="warranty[<?=$i?>]" value="<?php echo $data_pro['vo_warranty'];?>" placeholder="warranty" class="form-control">
 </div>  

<div id="box" style="width:5%"><a href='' style="width:100%;" class="btn btn-danger" onclick='del_tr(this);return false;'>x</a></div>

 <div id="box" style="width:100%; ">
<textarea name="des[<?=$i?>]" id="des[<?=$i?>]" placeholder="<?=$xml->notes?>"  class="form-control"><?=$data_pro['des'];?></textarea></div>
</td>
  </tr>

  <?php $i++;}}?>

				  </table>
                 
			<button style="width:40px;" id="addRow" class="btn btn-primary"  type="button">+</button>  
    &nbsp;
    <button style="width:40px;" id="removeRow" class="btn btn-primary" type="button">-</button>
	&nbsp;
    	<input type="hidden" id="countloop" name="countloop" value="<?=$i?>">
    	
	<input type="hidden" name="method" value="<?=$mode?>">
  
	<input type="hidden" name="page" value="voucher_list">
    <input type="hidden" name="id" value="<?php echo $_REQUEST[id];?>">
    
    
	
	<input type="submit" value="<?=$xml->save?>" class="btn btn-primary">
</form>


</body>
</html>