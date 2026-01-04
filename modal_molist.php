<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
$users=new DbConn($config);
// Security already checked in index.php

// Company filter for multi-tenant data isolation
$companyFilter = CompanyFilter::getInstance();

// SECURITY FIX: Use sql_int() to sanitize user input (prevents SQL injection)
$p_id = sql_int($_REQUEST['p_id'] ?? 0);
$query=mysqli_query($db->conn, "select model.id as id,price,model_name,type.name as type,model.des as des,brand.brand_name as brand from model join type on model.type_id=type.id join brand on model.brand_id=brand.id where model.id='".$p_id."'" . $companyFilter->andCompanyFilter('model'));
	 if(mysqli_num_rows($query)>0){
		 $data=mysqli_fetch_array($query);
		 
		 }else{exit("<script>alert('access denied');  window.history.back();</script>");}


?>
	<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <script type="text/javascript">
  
  $('body').on('hidden.bs.modal', '.modal', function () {
  $(this).removeData('bs.modal');
});

function Conf(object) {
if (confirm("ยืนยันต้องการเปลี่ยนแปลง? \nDo u want to update?") == true) {
return true;
}
return false;
}

</script>
</head>
<body>
 
        <form action="core-function.php" method="post" name="myform" id="myform">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title"><?=$xml->edit?><?=$xml->model?></h4>
            </div>			<!-- /modal-header -->
            <div class="modal-body">
    
            
           
	<div id="box">
		<lable for="model_name"><?=$xml->model?></lable>
		<input id="model_name" name="model_name" class="form-control" required type="text" value="<?php echo $data[model_name];?>">
	</div>
    	<div id="box">
		<lable for="des"><?=$xml->type?></lable>
	 <input type="text" class="form-control" disabled value="<?=$data[type];?>">
        
        
        </div>
        <div id="box">
    	<div id="box" style="width:50%;">
		<lable for="des"><?=$xml->brand?></lable>
        <div id="txtHint">
   <input type="text"  class="form-control" disabled value="<?=$data[brand];?>">
        
    </div>
	</div>
    <div id="box" style="width:50%;">
		<lable for="des"><?=$xml->price?></lable>
        <input type="text" name="price" class="form-control" placeholder="Price" value="<?=$data[price];?>">
      
	</div>
    
    </div>
    
    <div id="box" style="width:100%">
		<lable for="des"><?=$xml->description?></lable>
        <textarea class="form-control" name="des"><?=$data[des]?></textarea>
        </div>
	<input type="hidden" name="page" value="mo_list">
    	<input type="hidden" name="p_id" value="<?=$_REQUEST[p_id]?>">

    
 <div class="clearfix"></div>   
<div id="boxbtn">
</div>    

 
            </div>			<!-- /modal-body -->
            <div class="modal-footer">
              <button type="submit" name="method" value="E" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-saved"></span> <?=$xml->edit?></button><?php 
              // SECURITY FIX: Add company filter to product check to prevent cross-tenant data exposure
              $productCheck = mysqli_query($db->conn, "SELECT p.* FROM product p JOIN model m ON p.model = m.id WHERE p.model='".$p_id."'" . $companyFilter->andCompanyFilter('m'));
              if(mysqli_num_rows($productCheck)==0){?>   <button name="method" value="D" type="submit" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> <?=$xml->delete?></button>  <?php }?> <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <?=$xml->close?></button>
           

            </div>			<!-- /modal-footer -->
              </form>
  
</body>
</html>