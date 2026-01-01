<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();?>
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



	

	
function checkorder3(value,id) {
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
		
		
	var newval = xmlhttp.responseText.split("x||x");
      document.getElementById("price["+index[0]+"]").value=newval[0];
   document.getElementById("des["+index[0]+"]").value=newval[1];
   
    }
  }
  xmlhttp.open("GET","makeoptionindex.php?value="+value+"&mode=3&id="+index[0],true);
   xmlhttp.send();
   
}

function fetadr(value,id) {

  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		 var id2 = xmlhttp.responseText.split(";");
		 
      document.getElementById("adr_tax").value=id2[0];
	  document.getElementById("city_tax").value=id2[1];
	  document.getElementById("district_tax").value=id2[2];
	  document.getElementById("province_tax").value=id2[3];
	  document.getElementById("zip_tax").value=id2[4];
	
    }
  }
  xmlhttp.open("GET","fetadr.php?id="+value,true);
   xmlhttp.send();
   
}

	
	
	


</script>

   <script type="text/javascript">
$(function(){
	$("#addRow").click(function(){
		var indexthis = document.getElementById("countloop").value;
		document.getElementById("countloop").value=parseInt(indexthis)+1;
		
		var NR ="<tr id=fr["+indexthis+"]> <td style=' margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;'><div id='box' style='width:18%'><select required id='type["+indexthis+"]' name='type["+indexthis+"]' onchange='checkorder(this.value,this.id)' class='form-control'><?php $querycustomer=mysql_query("select name,id from type");
			echo "<option value='' >Please Select Product</option>";
			while($fetch_customer=mysql_fetch_array($querycustomer)){
				
			echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[name]."</option>";}?></select></div><div id='box' style='width:18%'><div id='slotbrand["+indexthis+"]'><select id='ban_id["+indexthis+"]' name='ban_id["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:18%'><div id='slotmodel["+indexthis+"]'><select id='model["+indexthis+"]' name='model["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:14%'><div class='input-group'><input type='number' class='form-control' name='quantity["+indexthis+"]' id='quantity["+indexthis+"]' required placeholder='Quantity' value='1' /><span class='input-group-addon'><?=$xml->unit?></span></div></div><input type='hidden' value='1' class='form-control' name='pack_quantity["+indexthis+"]' id='pack_quantity["+indexthis+"]' required placeholder='<?=$xml->unit?>' /><div id='box2'  style='width:15%'><div class='input-group'><input type='text' class='form-control' placeholder='<?=$xml->price?>' required name='price["+indexthis+"]' id='price["+indexthis+"]' /><span class='input-group-addon'><?=$xml->baht?></span></div></div><div id='box' style='width:12%'><div class='input-group'><span class='input-group-addon'><input value='1' name='a_labour["+indexthis+"]' id='a_labour["+indexthis+"]' type='checkbox'></span><input type='text' name='v_labour["+indexthis+"]' id='v_labour["+indexthis+"]' placeholder='<?=$xml->labour?>' class='form-control'></div></div><div id='box' style='width:5%'><a href='' style='width:100%;' class='btn btn-danger' onclick='del_tr(this);return false;'>x</a></div><div id='box' style='width:100%'><textarea name='des["+indexthis+"]' id='des["+indexthis+"]' placeholder='<?=$xml->notes?>' class='form-control'></textarea></div></td></tr>";
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
$_date = explode("-", date("d-m-Y"));
					$day = $_date[0];
					$month = $_date[1];
					$year = $_date[2];
				
		

?>
<div style="float:left; width:auto"><h2><i class="fa fa-shopping-cart"></i> <?=$xml->quotation?></h2></div><form action="index.php?page=qa_list" style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>


<?php

 $query=mysql_query("select po.id as id,ref,over,brandven,des, po.name as name,vat,adr_tax,city_tax,district_tax,province_tax,zip_tax des,DATE_FORMAT(po.date,'%d-%m-%Y')  as datepo, cus_id,dis,	ven_id from po join pr on po.ref=pr.id join company_addr on pr.cus_id=company_addr.com_id where po.id='".$_REQUEST[id]."' and status='1' and ven_id='".$_SESSION[com_id]."' and valid_end='0000-00-00'");
if(mysql_num_rows($query)=="1"){
	$data=mysql_fetch_array($query);
	$vender=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[ven_id]."'"));
	$customer=mysql_fetch_array(mysql_query("select name_sh,id from company where id='".$data[cus_id]."'"));
	$limit_day=mysql_fetch_array(mysql_query("select limit_day from company_credit where ven_id='".$data[ven_id]."' and cus_id='".$data[cus_id]."'"));
	
	?>
    <div class="clearfix"></div>
<form action="core-function.php" method="post" id="company-form">
  <div id="box">
		<lable for="name"><?=$xml->customer?></lable>
		<!--<input class="form-control" type="text" readonly value="<?php echo $customer[name_sh];?>">-->
        <select class="form-control" onchange="fetadr(this.value,this.id)" name="cus_id"  id="cus_id">
        <?php $query_cus=mysql_query("select name_en,id from company where customer='1'");
		
		
		while($fetch_cus=mysql_fetch_array($query_cus)){
					if($fetch_cus[id]==$customer[id])
					echo "<option selected value='".$fetch_cus[id]."' >".$fetch_cus[name_en]."</option>"; else 	echo "<option value='".$fetch_cus[id]."' >".$fetch_cus[name_en]."</option>";
				}?>
		
        
        
</select>
	</div>
	<div id="box">
		<lable for="name"><?=$xml->name?></lable>
		<input id="name" name="name" class="form-control" required value="<?php echo $data[name];?>"  type="text">
	</div>
    <div id="box">
		<lable for="name"><?=$xml->brand?></lable>
		<select id="brandven" name="brandven" class="form-control">
    
			<?php 
			echo "<option value='0' >".$vender[name_sh]."</option>";
			
			$querycustomer=mysql_query("select band_name,id from brand where ven_id='".$data[ven_id]."' ");
			
			
				while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($fetch_customer[id]==$data[brandven])
					echo "<option selected value='".$fetch_customer[id]."' >".$fetch_customer[band_name]."</option>"; else 	echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[band_name]."</option>";
				}?>
		</select>
	</div>
<div id="box">
		<lable for="adr_tax"><?=$xml->raddress?></lable>
		<input id="adr_tax" disabled class="form-control" type="text" value="<?php echo $data[adr_tax];?>">
	</div>
	<div id="box">
		<lable for="city_tax"><?=$xml->rcity?></lable>
		<input id="city_tax" disabled class="form-control" type="text" value="<?php echo $data[city_tax];?>">
	</div>
	<div id="box">
		<lable for="district_tax"><?=$xml->rdistrict?></lable>
		<input id="district_tax" disabled class="form-control" type="text" value="<?php echo $data[district_tax];?>">
	</div>
	<div id="box">
		<lable for="province_tax"><?=$xml->rprovince?></lable>
		<input id="province_tax" disabled class="form-control" type="text" value="<?php echo $data[province_tax];?>">
	</div>
	<div id="box">
		<lable for="zip_tax"><?=$xml->rzip?></lable>
		<input id="zip_tax" disabled class="form-control" type="text" value="<?php echo $data[zip_tax];?>">
	</div><div class="clearfix"></div>
          <div id="box">
		<lable for="name"><?=$xml->vat?></lable>
        <div class="input-group">
		<input class="form-control" required name="vat" type="text" value="<?php echo $data[vat];?>"><span class="input-group-addon">%</span></div>
	</div>
         <div id="box">
		<lable for="name"><?=$xml->discount?></lable>
        <div class="input-group">
		<input class="form-control" required name="dis" type="text" value="<?php echo number_format($data[dis]);?>"><span class="input-group-addon">%</span></div></div>
    
       <div id="box">
		<lable for="name"><?=$xml->overhead?></lable>
        <div class="input-group">
		<input class="form-control" required name="over" type="text" value="<?php echo number_format($data[over]);?>"><span class="input-group-addon">%</span></div></div>
    
     <div id="box">
		<lable for="name"><?=$xml->validpay?></lable>
		<input class="form-control" name="valid_pay" type="text" value="<?php echo date('d-m-Y',mktime(0,0,0, intval($month), (intval($day)+number_format($limit_day[limit_day])), intval($year)));?>">
	</div>
     <div id="box">
		<lable for="name"><?=$xml->deliverydate?></lable>
		<input class="form-control" name="deliver_date" type="text" value="<?php echo date('d-m-Y',mktime(0,0,0, intval($month), intval($day)+1, intval($year)));?>">
	</div>
         <div id="box">
		<lable for="create_date"><?=$xml->createdate?></lable>
		<input class="form-control" name="create_date" type="text" value="<?php echo $data[datepo];?>">
	</div>
    <div class="clearfix" style="margin-bottom:20px;"></div>
    <h3><?=$xml->pleaseselectproduct?></h3>
    
    <div style="width:18%; float:left; margin-left:0.5%"><?=$xml->product?></div>
    <div style="width:18%; float:left;"><?=$xml->brand?></div>
    <div style="width:18%; float:left;"><?=$xml->model?></div>
    <div style="width:14%; float:left;"><?=$xml->unit?></div>
     <div style="width:15%; float:left;"><?=$xml->price?></div> 
     <div style="width:10%; float:left;"><?=$xml->labour?></div> 
    
<table id="myTbl" class ="table" width="100%" border="0" cellpadding="0" cellspacing="0">


<?php 
$query_pro=mysql_query("select * from product where po_id='".$_REQUEST[id]."'");$i=0;

while($data_pro=mysql_fetch_array($query_pro)){?>										
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysql_query("select name,id from type");
			echo "<option value='' >-------Please Select Product--------</option>";
		while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($data_pro[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="ban_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysql_query("select band_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$data_pro[type]."'");
echo "<option value='' >Please Select Band</option>";
while($fetch_customer=mysql_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer[id];?>' <?php if($fetch_customer[id]==$data_pro[ban_id]) echo "selected";?> ><?php echo $fetch_customer[band_name];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysql_query("select model_name,id from model where brand_id='".$data_pro[ban_id]."' and type_id='".$data_pro[type]."'");
			if(mysql_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($data_pro[model]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[model_name]."</option>";
				}?>
		</select></div></div>
      
        <div id="box" style="width:14%"><div class="input-group">
<input type="number" class="form-control" name="quantity[<?=$i?>]" value="<?php echo $data_pro[quantity];?>" id="quantity[<?=$i?>]" required placeholder="Quantity" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
      <input type="hidden" class="form-control" name="pack_quantity[<?=$i?>]" id="pack_quantity[<?=$i?>]" required value='1' />
 <div id="box2" style="width:15%"><div class="input-group"><input type="text" class="form-control" placeholder="Price" required name="price[<?=$i?>]" id="price[<?=$i?>]"  value="<?php echo $data_pro[price];?>"/><span class="input-group-addon"><?=$xml->baht?></span></div></div>
  
 <div id="box" style="width:12%">
 <div class="input-group">
      <span class="input-group-addon">
        <input   name="a_labour[<?=$i?>]" <?php if($data_pro[activelabour]=="1")echo "checked"?> value="1" id="a_labour[<?=$i?>]" type="checkbox">
      </span>
      <input type="text" name="v_labour[<?=$i?>]" id="v_labour[<?=$i?>]" value="<?=$data_pro[valuelabour];?>" placeholder="labour" class="form-control">
    </div><!-- /input-group -->
 </div>  

<div id="box" style="width:5%"><a href='' style="width:100%;" class="btn btn-danger" onclick='del_tr(this);return false;'>x</a></div>

 <div id="box" style="width:100%; ">
<textarea  name="des[<?=$i?>]" id="des[<?=$i?>]" placeholder="<?=$xml->notes?>"  class="form-control"><?=$data_pro[des];?></textarea></div>
</td>
  </tr>
  <?php $i++;}?>
  
 
				  </table>
			<button style="width:40px;" id="addRow" class="btn btn-primary"  type="button">+</button>  
    &nbsp;
    <button style="width:40px;" id="removeRow" class="btn btn-primary" type="button">-</button>
	&nbsp;
    	<input type="hidden" id="countloop" name="countloop" value="<?=$i?>">
    	
	<input type="hidden" name="method" value="E">
    <input type="hidden" name="ref" value="<?php echo $data[ref];?>">
	<input type="hidden" name="page" value="po_list">
    <input type="hidden" name="id" value="<?php echo $_REQUEST[id];?>">
    
    
	
	<input type="submit" value="<?=$xml->save?>" class="btn btn-primary">
</form>



<?php 
}else echo "<center>ERROR</center>";?>

</body>
</html>