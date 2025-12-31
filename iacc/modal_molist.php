<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();

$query=mysql_query("select model.id as id,price,model_name,product_type.name as type,model.des as des,brand.brand_name as brand from model join type on model.product_type_id=product_type.id join brand on model.brand_id=brand.id where model.id='".$_REQUEST[p_id]."'");
	 if(mysql_num_rows($query)>0){
		 $data=mysql_fetch_array($query);
		 
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
              <button type="submit" name="method" value="E" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-saved"></span> <?=$xml->edit?></button><?php if(mysql_num_rows(mysql_query("SELECT * FROM  product WHERE 	model='".$_REQUEST[p_id]."'"))==0){?>   <button name="method" value="D" type="submit" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> <?=$xml->delete?></button>  <?php }?> <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> <?=$xml->close?></button>
           

            </div>			<!-- /modal-footer -->
              </form>
  
</body>
</html>