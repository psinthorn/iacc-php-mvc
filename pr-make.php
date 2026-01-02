<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
// $users=new DbConn($config);
// $users->checkSecurity();
?>
<!DOCTYPE html>
<html>

<head>
<script type="text/javascript">
function calprice(id) { 
	
	if(document.getElementById('quantity'+id).value==0){
  		alert('Value Quantity is Not Zero'); 
  		document.getElementById('quantity'+id).value=1;
  	}
  document.getElementById('total'+id).value=document.getElementById('quantity'+id).value*document.getElementById('price'+id).value;
  sumall();

  }
  
function sumall() { 
	var totalsum=0;
	for(i=0;i<9;i++){
		totalsum+=parseFloat(document.getElementById('total'+i).value);
	}
		document.getElementById('totalnet').value=totalsum;
  }

    targetElement = null;    
	function makeMenu(frm, id, indexn) {      
	if(!frm || !id)        
	return;      
	targetElement = frm.elements[id];
	targetElement2 = frm.elements['id'+indexn];
	targetElement3 = frm.elements['price'+indexn];
	targetElement4 = frm.elements['quantity'+indexn];
	targetElement5 = frm.elements['total'+indexn];
	var handle = window.open('product-list.php');}  
</script>

</head>

<body><h2><i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest?></h2>
<form action="core-function.php" method="post" id="company-form">

	<div id="box">
		<lable for="name"><?=$xml->name?></lable>
		<input id="name" name="name" placeholder="<?=$xml->name?>" class="form-control" required  type="text">
	</div>
    <div id="box">
		<lable for="name"><?=$xml->customer?></lable>
		<select id="cus_id" name="cus_id" class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select name_en,id from company where customer='1' and id !='".$_SESSION['com_id'] ."' order by name_en ");
			
			
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
					echo "<option value='".$fetch_customer['id']."' >".$fetch_customer['name_en']."</option>";
				}?>
		</select>
	</div>
	<div id="box" style="width:100%;">
		<lable for="des"><?=$xml->Description?></lable><textarea id="des" name="des" class="form-control" required placeholder="<?=$xml->Description?>" rows="3"></textarea>
		
	</div><div class="clearfix"></div>
    
    <div style='width:42%; float:left'><?=$xml->Product?></div>	
	<div style='width:17%; float:left'><?=$xml->Unit?></div>				
	<div style='width:17%; float:left'><?=$xml->Price?></div>		
	<div style='width:15%; float:left'><?=$xml->Total?></div>	
	<?php for($i=0;$i<9;$i++){
	echo "										
<div id='box4'>";?>
    <input type='text' name='ordername[<?php echo $i;?>]' id='ordername[<?php echo $i;?>]' readonly='true' class='form-control' style=" margin-right:1%; float:left; width:41%"  onclick='makeMenu(this.form,this.id,"<?php echo $i;?>");'/>
    
    <?php echo"<input type='hidden' name='id".$i."' readonly='true'  id='id".$i."' value='0' />
	
	<input type='text' name='quantity".$i."'   onchange=\"calprice('".$i."'); \"  required style='float:left; width:15%' class='form-control' id='quantity".$i."' value='1' /><div style='float:left; padding:1%;'> * </div>
	
	
	<input type='text' name='price".$i."' readonly='true' style=' float:left; width:15%' id='price".$i."' class='form-control' value='0' />
	
	<div style='float:left; padding:1%;'> : </div>
	
	<input type='text' name='total".$i."' readonly='true' style='margin-right:1%; float:left; width:15%' class='form-control' id='total".$i."' value='0' />
	<input  style=' width:7%' class='btn btn-danger'  value=' X ' type='button'  
	onclick=\"document.getElementById('ordername[".$i."]').value='';
				document.getElementById('price".$i."').value='0';
				document.getElementById('id".$i."').value='';
				document.getElementById('quantity".$i."').value='1';
				document.getElementById('total".$i."').value='0';sumall();
			\"></div>
 ";}?>
    
    <div class="clearfix"></div>
    <div id="box" style=" float:right"> <?=$xml->summary?> <input type='text' name='totalnet' id='totalnet' readonly='true' class='form-control' style="margin-bottom:2px;" value='0'/>
    </div>
	<div id="box" style="padding-top:20px;">
	
	<input type="hidden" name="method" value="A">
	<input type="hidden" name="page" value="pr_list">
    <input type="hidden" name="ven_id" value="<?php echo $_SESSION['com_id'];?>">
	
	<input type="submit" value="<?=$xml->request?>" class="btn btn-primary"></div>
</form>

</body>
</html>