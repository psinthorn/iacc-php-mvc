<?php 
require_once("inc/security.php");

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (model_name LIKE '%$search_escaped%' OR type.name LIKE '%$search_escaped%' OR brand.brand_name LIKE '%$search_escaped%')";
}
?>
<h2><i class="fa fa-ticket"></i> <?=$xml->model?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="mo_list">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Model, Type, Brand..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 300px;">
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=mo_list" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<script type="text/javascript">

function fetbrand(str) {
  if (str=="") {
    document.getElementById("txtHint").innerHTML="";
    return;
  } 
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      document.getElementById("txtHint").innerHTML=xmlhttp.responseText;
    }
  }
  xmlhttp.open("GET","model.php?q="+str,true);
  xmlhttp.send();
}

</script>
<?php
  $id = sql_int($_REQUEST['id']);
  $query=mysqli_query($db->conn, "select * from model where id='".$id."'");
  if(mysqli_num_rows($query)==1){
    $method="E";
    $data=mysqli_fetch_array($query);
  }else{
    $method="A";
  }
?>

<form action="core-function.php" method="post" name="myform" id="myform">
	<div id="box">
		<lable for="model_name"><?=$xml->model?></lable>
		<input id="model_name" name="model_name" class="form-control" required type="text" value="<?php echo $data['model_name'];?>">
	</div>
    	<div id="box">
		<lable for="des"><?=$xml->type?></lable>
		<select id="type"  onchange="fetbrand(this.value)" class="form-control" name="type" >
          <option value="">-----Please select Type-----</option>    
	<?php $querytype=mysqli_query($db->conn, "select * from type order by name");			
		while($datatype=mysqli_fetch_array($querytype)){
			echo "<option value='".$datatype['id']."'>".$datatype['name']."</option>";
			}
		?>																																																	</select>
        
        
        </div>
        <div id="box">
    	<div id="box" style="width:50%;">
		<lable for="des"><?=$xml->brand?></lable>
        <div id="txtHint">
   <select name="brand"   class="form-control"> 
                                
                                
          <option value="">-----Please select Type-----</option>           
                                
                                </select></div>
	</div>
    <div id="box" style="width:50%;">
		<lable for="des"><?=$xml->price?></lable>
        <input type="text" name="price" class="form-control" placeholder="Price">
      
	</div>
    
    </div>
    
    <div id="box" style="width:100%">
		<lable for="des"><?=$xml->description?></lable>
        <textarea class="form-control" name="des"><?=$data['des']?></textarea>
        </div>
	<input type="hidden" name="method" value="A">
	<input type="hidden" name="page" value="mo_list">
	<div id="box" style="padding-top:25px; float:left; width:15%"><input type="submit" style="float:left;" value="<?=$xml->add?>" class="btn btn-primary"></div>
</form>

<?php
$query=mysqli_query($db->conn, "select model.id as id,model_name,type.name as type,brand.brand_name as brand,price from model join type on model.type_id=type.id join brand on model.brand_id=brand.id where 1=1 $search_cond order by model.id desc");?>

<div id="fetch_state"></div>
<table width="100%" class="table"><tr><th><?=$xml->name?></th><th><?=$xml->type?></th><th><?=$xml->brand?></th><th><?=$xml->price?></th><th width="120"></th></tr>
<?php while($data=mysqli_fetch_array($query)){
echo " <tr  data-toggle='modal' style='cursor:pointer;' href='modal_molist.php?p_id=".$data['id']."'  data-target='.bs-example-modal-lg'><td>".e($data['model_name'])."</td><td>".e($data['type'])."</td><td>".e($data['brand'])."</td><td>".e($data['price'])."</td>
<td></td></tr>";	
	
	}?>

</table>

 <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      Error
    </div>
  </div>
</div>

