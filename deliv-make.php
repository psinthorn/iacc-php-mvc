<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
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

$(function(){
	$("#addRow").click(function(){
			var indexthis = document.getElementById("countloop").value;
		document.getElementById("countloop").value=parseInt(indexthis)+1;
		
		var NR ="<tr id=fr["+indexthis+"]> <td style=' margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;'><div id='box'><select required id='type["+indexthis+"]' name='type["+indexthis+"]' onchange='checkorder(this.value,this.id)' class='form-control'><?php $querycustomer=mysql_query("select name,id from type  ");
			echo "<option value='' >Please Select Product</option>";
			while($fetch_customer=mysql_fetch_array($querycustomer)){
				
			echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[name]."</option>";}?></select></div><div id='box'><div id='slotbrand["+indexthis+"]'><select id='ban_id["+indexthis+"]' name='ban_id["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'><div id='slotmodel["+indexthis+"]'><select id='model["+indexthis+"]' name='model["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'><div class='input-group'><input type='number' class='form-control' name='quantity[]' id='quantity[]' required placeholder='<?=$xml->unit?>' value='1' /><span class='input-group-addon'><?=$xml->unit?></span></div></div><input type='hidden' value='1' class='form-control' name='pack_quantity[]' id='pack_quantity[]' required placeholder='Quantity Per Pack' /><div id='box'><div class='input-group'><input type='text' class='form-control' name='s_n[]' id='s_n[]' required placeholder='S/N' /><span class='input-group-addon'><?=$xml->sn?></span></div></div><div id='box' style='width:25%'><div class='input-group'><input type='text' class='form-control' name='warranty[]' id='warranty[]' required placeholder='dd-mm-yyyy' /><span class='input-group-addon'><?=$xml->warranty?></span></div></div><div id='box' style='width:8%;'><a href='' style='width:100%;' class='btn btn-danger' onclick='del_tr(this);return false;'>x</a></div></td></tr></td></tr>";
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
$com_id = sql_int($_SESSION['com_id']);
$id = sql_int($_REQUEST['id']);

$_date = explode("-", date("d-m-Y"));
					$day = $_date[0];
					$month = $_date[1];
					$year = $_date[2];
				
		

?>
<div style="float:left; width:auto"><h2><i class="fa fa-shopping-cart"></i> <?=$xml->deliverynote?></h2></div><form action="index.php?page=deliv_list" style="float:right; margin-top:15px;" method="post"><input value="<?=$xml->back?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form>


<?php

	$vender=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[ven_id]."'"));
	$customer=mysql_fetch_array(mysql_query("select name_sh from company where id='".$data[cus_id]."'"));
	$limit_day=mysql_fetch_array(mysql_query("select limit_day from company_credit where ven_id='".$data[ven_id]."' and cus_id='".$data[cus_id]."'"));
	
	?>
    <div class="clearfix"></div>
<form action="core-function.php" method="post" id="company-form">


    <div id="box">
		<lable for="name"><?=$xml->customer?></lable>
		<select id="cus_id" name="cus_id" class="form-control">
  		<option value='0' >------ Please Select Customer ---------</option>
		<?php $querycustomer=mysql_query("select name_en,id from company where customer='1' and id !='".$com_id."' ");
			
			
				while($fetch_customer=mysql_fetch_array($querycustomer)){
					echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[name_en]."</option>";
				}?>
		</select>
	</div>
       <div id="box">
		<lable for="name"><?=$xml->deliverydate?></lable>
		<input class="form-control" name="deliver_date" type="date" value="<?php echo date('d-m-Y',mktime(0,0,0, intval($month), intval($day)+1, intval($year)));?>">
	</div>

	<div id="box"  style="width:100%;" >
		<lable for="des"><?=$xml->description?></lable><textarea name="des" id="des" class="form-control"  rows="5"></textarea>
		
	</div>
  
<table id="myTbl" class ="table" width="100%" border="0" cellpadding="0" cellspacing="0">
<?php 
$qeurytmpitem=mysql_query("select * from tmp_product where pr_id='".$id."'");
$i=0;
if(mysql_num_rows($qeurytmpitem)>0){
while($data_fetitem=mysql_fetch_array($qeurytmpitem)){?>
<tr id="firstTr">
    <td  style="margin-bottom:5px; padding-bottom:10px;"> 
   
    
       <div id="box"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysql_query("select name,id from type order by id desc");
			echo "<option value='' >-------Please Select Product--------</option>";
		while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($data_fetitem[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
		</select></div>
   
      
     <div id="box"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="ban_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysql_query("select band_name,id from brand order by id desc");
echo "<option value='' >-------Please Select Band--------</option>";
while($fetch_customer=mysql_fetch_array($querycustomer)){
					echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[band_name]."</option>";
				}?>
		</select></div></div>
        
      <div id="box"><div class="input-group"><input type="text" class="form-control" name="model[]" required id="model[]" placeholder="Model" /><span class="input-group-addon"><?=$xml->model?></span></div></div>
  
        
        <div id="box"><div class="input-group">
<input type="number" class="form-control" name="quantity[]" value="<?php echo $data_fetitem[quantity];?>" id="quantity[]" required placeholder="<?=$xml->unit?>" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
       <input type="hidden" class="form-control" name="pack_quantity[]" id="pack_quantity[]"  value='1' />
        
   <div id="box2"><div class="input-group"><input type="text" class="form-control" placeholder="<?=$xml->price?>" required name="price[]" id="price[]"  value="<?php echo $data_fetitem[price];?>"/><span class="input-group-addon"><?=$xml->price?></span></div></div>
       <div id="box3" ><div class="input-group"><input placeholder="<?=$xml->vat?>" type="text" class="form-control" required  name="discount[]" id="discount[]" value="7" /><span class="input-group-addon"><?=$xml->vat?></span></div></div></td>
  </tr>
  <?php }}else{?><tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>"><td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
   
    
       <div id="box"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysql_query("select name,id from type");
			echo "<option value='0' >-------Please Select Product--------</option>";
		while($fetch_customer=mysql_fetch_array($querycustomer)){
					if($data_fetitem[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
		</select></div>
        
         <div id="box"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]"  name="ban_id[<?=$i?>]" class="form-control">
         <option value='' >Please Select Product First</option></select></div></div>
  
  
  
      <div id="box"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">     <option value='' >Please Select Product First</option></select></div></div>
       
    
        
        <div id="box"><div class="input-group">
<input type="number" class="form-control" name="quantity[<?=$i?>]" value="1" id="quantity[<?=$i?>]" required placeholder="<?=$xml->unit?>" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
     <input type="hidden" class="form-control" name="pack_quantity[<?=$i?>]" id="pack_quantity[<?=$i?>]" value='1' />
          <div id="box"><div class="input-group"><input type="text" class="form-control" name="s_n[<?=$i?>]" id="s_n[<?=$i?>]" required placeholder="S/N" /><span class="input-group-addon"><?=$xml->sn?></span></div></div>
            <div id="box" style="width:25%"><div class="input-group"><input type="text" class="form-control" name="warranty[<?=$i?>]" id="warranty[<?=$i?>]" required placeholder="dd-mm-yyyy"/><span class="input-group-addon"><?=$xml->warranty?></span></div></div>
              <div id="box" style="width:8%;"><a href='' style="width:100%;" class="btn btn-danger" onclick='del_tr(this);return false;'>x</a></div>
       </td>
  </tr><?php $i++; }?>
 
				  </table>
			<button style="width:40px;" id="addRow" class="btn btn-primary"  type="button">+</button>  
    &nbsp;
    <button style="width:40px;" id="removeRow" class="btn btn-primary" type="button">-</button>
	&nbsp;
        	<input type="hidden" id="countloop" name="countloop" value="<?=$i?>">
    	
	<input type="hidden" name="method" value="AD">
  
	<input type="hidden" name="page" value="deliv_list">
	
	<input type="submit" value="<?=$xml->make?>" class="btn btn-primary">
</form>



</body>
</html>