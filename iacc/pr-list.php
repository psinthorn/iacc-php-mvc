
<?php 
	$status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
	if($status=="1") $condition="and status='1'";
	else if($status=="2") $condition="and status='2'";
	else if($status=="3") $condition="and status='3'";
	else if($status=="4") $condition="and status='4'";
	else if($status=="5") $condition="and status='5'";
	else if($status=="6") $condition="";
	else $condition="and status='0'";
?>
<div style="float:left; width:auto"><h2><i class="glyphicon glyphicon-pencil"></i>  <?=$xml->purchasingrequest?></h2></div><form action="index.php?page=pr_list" style="float:right; margin-top:15px;" method="post"><select id="status" name="status" style="width:160px; float:left;" class="form-control">
<option value='0' <?php if(($status=="")||($status=="0"))echo "selected";?> ><?=$xml->processpr?></option>
<option value='1' <?php if($status=="1")echo "selected";?> ><?=$xml->processquo?></option>
<option value='2' <?php if($status=="2")echo "selected";?> ><?=$xml->processpo?></option>
<option value='3' <?php if($status=="3")echo "selected";?> ><?=$xml->processdeli?></option>
<option value='4' <?php if($status=="4")echo "selected";?> ><?=$xml->processpaid?></option>
<option value='5' <?php if($status=="5")echo "selected";?> ><?=$xml->success?></option>
<option value='6' <?php if($status=="6")echo "selected";?> ><?=$xml->processall?></option>

</select><input value="<?=$xml->filter?>" style=" margin-left:5px;float:left;" type="submit" class="btn btn-primary"></form><?php
//$users->checkSecurity();?>

<table width="100%" class="table table-hover">
	<tr>
		<th  width="20%"><?=$xml->customer?></th><th width="40%"><?=$xml->description?></th><th  width="10%"><?=$xml->name?></th><th  width="10%"><?=$xml->date?></th><th  width="10%"><?=$xml->status?></th><th  width="10%"></th></tr>
<?php
$query=mysqli_query($db->conn, "select pr.id as id, name,DATE_FORMAT(date,'%d-%m-%Y') as date,cancel, des, name_en, status from pr join company on pr.cus_id=company.id where ven_id='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' ".$condition." order by cancel,id desc");

 while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data['name_en']."</td><td>".$data['des']."</td><td>".$data['name']."</td><td>".$data['date']."</td>";
$var=decodenum($data['status']);
if($data['cancel']=="1"){
echo "<td><font color='red'>".$xml->$var."</font></td><td><a href='index.php?page=po_make&id=".$data['id']."'><i class=\"glyphicon glyphicon-pencil\"></i></a>";}
else {
	echo "<td>".$xml->$var."</td><td><a href='index.php?page=po_make&id=".$data['id']."'><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=pr_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a>";
	
	}

echo "</td>
</tr>";	

	}
	
	 if($status=="5"){
		$query= mysqli_query($db->conn, "select * from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.cus_id=company.id where ven_id='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' and deliver.id in (select deliver_id from receive)");
		 
		 while($data=mysqli_fetch_array($query)){
echo "<tr><td>Send out</td><td>".$data['tmp']."</td><td>".$data['name_sh']."</td><td>".$data['deliver_date']."</td><td>Success</td><td><a onClick='return Conf(this)' title='Cancel' href='#'><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";	
	
	}
		 
		 
		 }
	?>
 
 <tr><th><?=$xml->vender?></th><th><?=$xml->description?></th><th><?=$xml->name?></th><th><?=$xml->date?></th><th><?=$xml->status?></th><th></th></tr>
<?php
$query=mysqli_query($db->conn,"select pr.id as id, name,cancel,DATE_FORMAT(date,'%d-%m-%Y') as date,des, name_en, status from pr join company on pr.ven_id=company.id where cus_id='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' ".$condition." order by cancel,id desc");

 while($data=mysqli_fetch_array($query)){
echo "<tr><td>".$data['name_en']."</td><td>".$data['des']."</td><td>".$data['name']."</td><td>".$data['date']."</td>";

$val=decodenum($data['status']);
if($data['cancel']=="1"){
	
echo "<td><font color='red'>".$xml->$val."</font></td><td>";}
else {
	echo "<td>".$xml->$val."</td><td><a onClick='return Conf(this)' title='Cancel' href='core-function.php?page=pr_list&id=".$data['id']."&method=D'><span class=\"glyphicon glyphicon-trash\"></span></a></td><td>";
	
	}

echo "</td>
</tr>";	
	
	}
	 if($status=="5"){
		$query= mysqli_query($db->conn, "select * from sendoutitem join deliver on sendoutitem.id=deliver.out_id join company on sendoutitem.ven_id=company.id where cus_id='" . mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '') . "' and deliver.id in (select deliver_id from receive)");
		 
		 while($data=mysqli_fetch_array($query)){
echo "<tr><td>Send out</td><td>".$data['tmp']."</td><td>".$data['name_sh']."</td><td>".$data['deliver_date']."</td><td>Success</td><td><a onClick='return Conf(this)' title='Cancel' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a></td>
</tr>";	
	
	}
		 
		 
		 }
	
	
	?>

</table>
<div id="fetch_state"></div>