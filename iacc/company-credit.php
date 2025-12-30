<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db=new DbConn($config);
$db->checkSecurity();?>
<!DOCTYPE html>
<html>

<head>
</head>

<body>
<?php
$query=mysqli_query($db->conn, "select * from company_credit where id='" . mysqli_real_escape_string($db->conn, $_REQUEST['id'] ?? '') . "'");
if(mysqli_num_rows($query)==1){
$method="A4";
$data=mysqli_fetch_array($query);}
else $method="A3";
?>
<form action="core-function" method="post" id="myform">
	<div id="box">
		<lable for="cus_id"><?=$xml->customer?></lable>
		<?php if($method=="A4"){
		$customername=mysqli_fetch_array(mysqli_query($db->conn, "select name_en from company where id='" . mysqli_real_escape_string($db->conn, $data['cus_id'] ?? '') . "'"));?>
		<input type="text" value="<?php echo (isset($customername['name_en']) ? $customername['name_en'] : '');?>" readonly class="form-control">
		<input type="text" value="<?php echo (isset($data['cus_id']) ? $data['cus_id'] : '');?>" name ="cus_id">
		<?php }else{
		
			?>
           
		<select id="cus_id" name="cus_id" class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select name_en,id from company where id not in (select cus_id from company_credit where ven_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['ven_id'] ?? '') . "' group by cus_id) and id!='" . mysqli_real_escape_string($db->conn, $_REQUEST['ven_id'] ?? '') . "' and customer='1' ");
			
			
				while($fetch_customer=mysqli_fetch_array($querycustomer)){
					echo "<option value='".$fetch_customer['id']."' >".$fetch_customer['name_en']."</option>";
				}?>
		</select>
		<?php }?>
	</div>
	<div id="box">
		<lable for="limit_credit"><?=$xml->limitcredit?></lable>
		<input id="limit_credit" name="limit_credit" class="form-control" required type="number" value="<?php echo $data[limit_credit];?>">
	</div>
	<div id="box">
		<lable for="limit_day"><?=$xml->limitday?></lable>
		<input id="limit_day" name="limit_day" class="form-control" required type="number" value="<?php echo $data[limit_day];?>">
	</div>
	
	<input type="hidden" name="method" value="<?php echo $method;?>">
	<input type="hidden" name="page" value="company">
    
    <input type="hidden" name="ven_id" value="<?php echo $_REQUEST[ven_id];?>">
	<input type="hidden" name="id" value="<?php echo $_REQUEST[id];?>">
	<input type="hidden" name="valid_start" value="<?php echo $_REQUEST[valid_start];?>">
	<div id="box" style="padding-top:20px;"><input type="submit" value="<?php if($method=="A4")echo $xml->save;else echo $xml->add;?>" class="btn btn-primary"></div>
</form>
</body>
</html>