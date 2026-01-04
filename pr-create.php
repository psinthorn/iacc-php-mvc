<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
// $users=new DbConn($config);
// // Security already checked in index.php
?>
<!DOCTYPE html>
<html>

<head>
<!-- Modern Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .pr-form-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(16, 185, 129, 0.25);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header .subtitle {
        margin-top: 6px;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .form-card .section-title {
        font-weight: 600;
        color: #1f2937;
        font-size: 16px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-card .section-title i {
        color: #10b981;
    }
    
    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        font-weight: 500;
        color: #374151;
        font-size: 13px;
        margin-bottom: 6px;
    }
    
    .form-card .form-control {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 14px 16px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
        width: 100%;
        min-height: 48px;
    }
    
    .form-card .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        outline: none;
    }
    
    .product-grid {
        margin-top: 8px;
    }
    
    .product-header {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        padding: 12px 16px;
        background: #f9fafb;
        border-radius: 8px 8px 0 0;
        border: 1px solid #e5e7eb;
        border-bottom: none;
        font-weight: 600;
        font-size: 12px;
        color: #1f2937;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-top: none;
        align-items: center;
    }
    
    .product-row:nth-child(even) {
        background: #fafafa;
    }
    
    .product-row:last-child {
        border-radius: 0 0 8px 8px;
    }
    
    .product-row input {
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        padding: 12px 14px;
        font-size: 14px;
        width: 100%;
        min-height: 44px;
    }
    
    .product-row input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        outline: none;
    }
    
    .product-row input[readonly] {
        background: #f9fafb;
        color: #6b7280;
        cursor: pointer;
    }
    
    .btn-clear-row {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-clear-row:hover {
        background: #ef4444;
        color: white;
    }
    
    .summary-section {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
    }
    
    .summary-section label {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .summary-section input {
        width: 180px;
        text-align: right;
        font-weight: 700;
        font-size: 16px;
        color: #10b981;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 10px 14px;
    }
    
    .form-actions {
        margin-top: 20px;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 28px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
    }
</style>
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

<body>
<div class="pr-form-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa fa-pencil-square-o"></i> <?=$xml->purchasingrequest?></h2>
        <div class="subtitle">Create a new purchase request from a vendor</div>
    </div>

    <form action="core-function.php" method="post" id="company-form">
        <!-- Basic Info Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-info-circle"></i> Request Information</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><?=$xml->name?></label>
                    <input id="name" placeholder="<?=$xml->name?>" name="name" class="form-control" required type="text">
                </div>
                <div class="form-group">
                    <label for="ven_id">Vendor</label>
                    <select id="ven_id" name="ven_id" class="form-control">
                        <?php 
                        $com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;
                        if ($com_id > 0) {
                            $querycustomer = mysqli_query($db->conn, "SELECT name_en, id FROM company 
                                WHERE vender='1' AND company_id = '$com_id' AND deleted_at IS NULL
                                ORDER BY name_en");
                        } else {
                            $querycustomer = mysqli_query($db->conn, "SELECT name_en, id FROM company WHERE vender='1' ORDER BY name_en");
                        }
                        
                        while($fetch_customer=mysqli_fetch_array($querycustomer)){
                            echo "<option value='".$fetch_customer['id']."' >".$fetch_customer['name_en']."</option>";
                        }?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="des"><?=$xml->description?></label>
                <textarea id="des" name="des" class="form-control" required placeholder="<?=$xml->description?>" rows="3"></textarea>
            </div>
        </div>

        <!-- Products Card -->
        <div class="form-card">
            <div class="section-title"><i class="fa fa-shopping-cart"></i> <?=$xml->product?> Selection</div>
            
            <div class="product-grid">
                <div class="product-header">
                    <div><?=$xml->product?></div>
                    <div><?=$xml->unit?></div>
                    <div><?=$xml->price?></div>
                    <div><?=$xml->total?></div>
                    <div></div>
                </div>
                
                <?php for($i=0;$i<9;$i++){ ?>
                <div class="product-row">
                    <input type='text' name='ordername[<?php echo $i;?>]' id='ordername[<?php echo $i;?>]' readonly='true' placeholder="Click to select product..." onclick='makeMenu(this.form,this.id,"<?php echo $i;?>");'/>
                    <input type='hidden' name='id<?php echo $i;?>' readonly='true' id='id<?php echo $i;?>' value='0' />
                    <input type='text' name='quantity<?php echo $i;?>' onchange="calprice('<?php echo $i;?>');" required value='1' />
                    <input type='text' name='price<?php echo $i;?>' readonly='true' id='price<?php echo $i;?>' value='0' />
                    <input type='text' name='total<?php echo $i;?>' readonly='true' id='total<?php echo $i;?>' value='0' />
                    <button type="button" class="btn-clear-row" onclick="document.getElementById('ordername[<?php echo $i;?>]').value='';
                        document.getElementById('price<?php echo $i;?>').value='0';
                        document.getElementById('id<?php echo $i;?>').value='';
                        document.getElementById('quantity<?php echo $i;?>').value='1';
                        document.getElementById('total<?php echo $i;?>').value='0';sumall();">✕</button>
                </div>
                <?php } ?>
            </div>
            
            <div class="summary-section">
                <label><?=$xml->summary?></label>
                <input type='text' name='totalnet' id='totalnet' value='0' readonly='true'/>
            </div>
            
            <div class="form-actions">
                <input type="hidden" name="method" value="A">
                <input type="hidden" name="page" value="pr_list">
                <input type="hidden" name="cus_id" value="<?php echo $_SESSION['com_id'];?>">
                <button type="submit" class="btn-submit"><i class="fa fa-paper-plane"></i> <?=$xml->request?></button>
            </div>
        </div>
    </form>
</div>

</body>
</html>