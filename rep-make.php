<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/payment-method-helper.php");
$users=new DbConn($config);
// Security already checked in index.php?>
<!DOCTYPE html>
<html>

<head>
<style>
/* Modern Receipt Form Styling */
.receipt-container { max-width: 1200px; margin: 0 auto; }
.page-header { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: #fff; padding: 20px 25px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
.page-header h2 { margin: 0; font-size: 24px; font-weight: 600; }
.page-header .btn-back { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 8px 20px; border-radius: 5px; text-decoration: none; transition: all 0.3s; }
.page-header .btn-back:hover { background: rgba(255,255,255,0.3); }

.form-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
.form-card .card-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; }
.form-card .card-header h4 { margin: 0; font-size: 16px; font-weight: 600; color: #333; }
.form-card .card-header h4 i { margin-right: 8px; color: #27ae60; }
.form-card .card-body { padding: 20px; }

.form-row { display: flex; flex-wrap: wrap; margin: 0 -10px; }
.form-col { padding: 0 10px; margin-bottom: 15px; }
.form-col-2 { width: 16.66%; }
.form-col-3 { width: 25%; }
.form-col-4 { width: 33.33%; }
.form-col-6 { width: 50%; }
.form-col-12 { width: 100%; }

.form-group { margin-bottom: 0; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group .form-control { border-radius: 5px; border: 1px solid #ddd; padding: 10px 12px; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; }
.form-group .form-control:focus { border-color: #27ae60; box-shadow: 0 0 0 3px rgba(39,174,96,0.1); outline: none; }
.form-group select.form-control { height: auto; padding: 10px 12px; }

.invoice-link-card { border: 2px dashed #27ae60; background: #f0fff4; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.invoice-link-card label { color: #27ae60; font-weight: 600; }
.invoice-link-card select { border-color: #27ae60; }

#invoice_info .panel { border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
#invoice_info .panel-heading { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: #fff; border-radius: 8px 8px 0 0; padding: 15px 20px; }
#invoice_info .panel-body { padding: 20px; }

.product-section { border-top: 3px solid #27ae60; }
.product-section .card-header { background: #27ae60; color: #fff; }
.product-section .card-header h4 { color: #fff; }
.product-section .card-header h4 i { color: #fff; }

.product-header { display: flex; background: #f8f9fa; padding: 12px 15px; border-radius: 5px; margin-bottom: 10px; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #555; }
.product-row { background: #fff; border: 1px solid #eee; border-radius: 5px; padding: 15px; margin-bottom: 10px; transition: box-shadow 0.2s; }
.product-row:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

.btn-add-row, .btn-remove-row { width: 45px; height: 45px; border-radius: 50%; font-size: 20px; font-weight: bold; border: none; cursor: pointer; transition: all 0.2s; }
.btn-add-row { background: #27ae60; color: #fff; }
.btn-add-row:hover { background: #219a52; transform: scale(1.1); }
.btn-remove-row { background: #e74c3c; color: #fff; }
.btn-remove-row:hover { background: #c0392b; transform: scale(1.1); }

.btn-submit { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); border: none; color: #fff; padding: 12px 40px; font-size: 16px; font-weight: 600; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(39,174,96,0.3); }

.btn-preview { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border: none; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: 600; border-radius: 5px; cursor: pointer; transition: all 0.3s; margin-right: 10px; }
.btn-preview:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(52,152,219,0.3); }
.btn-preview i { margin-right: 5px; }

.action-buttons { display: flex; align-items: center; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }

/* Hide product section when invoice is linked */
.invoice-linked .product-section { display: none; }
.invoice-linked .action-buttons .btn-add-row,
.invoice-linked .action-buttons .btn-remove-row { display: none; }

@media (max-width: 768px) {
    .form-col-2, .form-col-3, .form-col-4 { width: 50%; }
    .form-col-6 { width: 100%; }
}
</style>
<script type="text/javascript">
// Function to load invoice data when an invoice is selected
function loadInvoiceData(invoiceId) {
    var formContainer = document.getElementById('receipt-form-container');
    var productSection = document.getElementById('product-section');
    
    if (!invoiceId) {
        // Clear fields if no invoice selected
        document.getElementById('name').value = '';
        document.getElementById('invoice_info').style.display = 'none';
        // Show product section when no invoice linked
        if (formContainer) formContainer.classList.remove('invoice-linked');
        if (productSection) productSection.style.display = 'block';
        return;
    }
    
    // Hide product section when invoice is linked
    if (formContainer) formContainer.classList.add('invoice-linked');
    if (productSection) productSection.style.display = 'none';
    
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    // Populate customer info
                    document.getElementById('name').value = data.customer.name || '';
                    if (document.getElementById('email')) {
                        document.getElementById('email').value = data.customer.email || '';
                    }
                    if (document.getElementById('phone')) {
                        document.getElementById('phone').value = data.customer.phone || '';
                    }
                    
                    // Update VAT and discount from invoice
                    if (document.querySelector('input[name="vat"]')) {
                        document.querySelector('input[name="vat"]').value = data.invoice.vat || '7';
                    }
                    if (document.querySelector('input[name="dis"]')) {
                        document.querySelector('input[name="dis"]').value = data.invoice.discount || '0';
                    }
                    
                    // Build product table
                    var productsHtml = '';
                    for (var i = 0; i < data.products.length; i++) {
                        var p = data.products[i];
                        productsHtml += '<tr><td>' + (i+1) + '</td><td>' + p.product_name;
                        if (p.description) {
                            productsHtml += '<br><small class="text-muted">' + p.description.substring(0, 80) + '</small>';
                        }
                        productsHtml += '</td><td class="text-center">' + p.quantity + '</td>';
                        productsHtml += '<td class="text-right">' + parseFloat(p.price).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>';
                        productsHtml += '<td class="text-right">' + p.amount + '</td></tr>';
                    }
                    
                    // Show invoice info summary with full details
                    var infoHtml = '<div class="panel panel-info">' +
                        '<div class="panel-heading"><strong><i class="fa fa-file-text"></i> Linked Invoice: INV-' + data.invoice.invoice_no + '</strong></div>' +
                        '<div class="panel-body">' +
                        '<div class="row">' +
                        '<div class="col-md-6">' +
                        '<p><strong>Customer:</strong> ' + data.customer.name + '</p>' +
                        '<p><strong>Invoice Date:</strong> ' + (data.invoice.invoice_date || '-') + '</p>' +
                        '<p><strong>Due Date:</strong> ' + (data.invoice.due_date || '-') + '</p>' +
                        '</div>' +
                        '<div class="col-md-6 text-right">' +
                        '<p><strong>Subtotal:</strong> ฿' + data.totals.subtotal + '</p>';
                    
                    if (parseFloat(data.totals.discount_percent) > 0) {
                        infoHtml += '<p><strong>Discount ' + data.totals.discount_percent + '%:</strong> -฿' + data.totals.discount_amount + '</p>';
                    }
                    if (parseFloat(data.totals.overhead_percent) > 0) {
                        infoHtml += '<p><strong>Overhead ' + data.totals.overhead_percent + '%:</strong> +฿' + data.totals.overhead_amount + '</p>';
                    }
                    
                    infoHtml += '<p><strong>VAT ' + data.totals.vat_percent + '%:</strong> +฿' + data.totals.vat_amount + '</p>' +
                        '<p class="text-primary" style="font-size:18px;"><strong>Grand Total: ฿' + data.totals.grand_total + '</strong></p>' +
                        '</div></div><hr>' +
                        '<table class="table table-condensed table-striped">' +
                        '<thead><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-right">Price</th><th class="text-right">Amount</th></tr></thead>' +
                        '<tbody>' + productsHtml + '</tbody></table>' +
                        '</div></div>';
                    
                    document.getElementById('invoice_info').innerHTML = infoHtml;
                    document.getElementById('invoice_info').style.display = 'block';
                } else {
                    alert('Error loading invoice: ' + data.error);
                }
            } catch(e) {
                console.error('Error parsing response:', e);
                console.error('Response:', xhr.responseText);
            }
        }
    };
    xhr.open("GET", "fetch-invoice-data.php?invoice_id=" + invoiceId, true);
    xhr.withCredentials = true;
    xhr.send();
}

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
				
			echo "<option value='".$fetch_customer[id]."' >".$fetch_customer[name]."</option>";}?></select></div><div id='box' style='width:18%'><div id='slotbrand["+indexthis+"]'><select id='ban_id["+indexthis+"]' name='ban_id["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:18%'><div id='slotmodel["+indexthis+"]'><select id='model["+indexthis+"]' name='model["+indexthis+"]' required class='form-control'><option value='' >Please Select Product First</option></select></div></div><div id='box'  style='width:14%'><div class='input-group'><input type='number' class='form-control' name='quantity["+indexthis+"]' id='quantity["+indexthis+"]' required placeholder='Quantity' value='1' /><span class='input-group-addon'><?=$xml->unit?></span></div></div><input type='hidden' value='1' class='form-control' name='pack_quantity["+indexthis+"]' id='pack_quantity["+indexthis+"]' required placeholder='<?=$xml->unit?>' /><div id='box2'  style='width:15%'><div class='input-group'><input type='text' class='form-control' placeholder='<?=$xml->price?>' required name='price["+indexthis+"]' id='price["+indexthis+"]' /><span class='input-group-addon'><?=$xml->baht?></span></div></div><div id='box' style='width:12%'><input type='text' name='warranty["+indexthis+"]' id='warranty["+indexthis+"]' value='<?=date("d-m-Y")?>' class='form-control'></div></div><div id='box' style='width:5%'><a href='' style='width:100%;' class='btn btn-danger' onclick='del_tr(this);return false;'>x</a></div><div id='box' style='width:100%'><textarea name='des["+indexthis+"]' id='des["+indexthis+"]' placeholder='<?=$xml->notes?>' class='form-control'></textarea></div></td></tr>";
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

<body><?php 
$id = sql_int($_REQUEST['id']);
$com_id = sql_int($_SESSION['com_id']);

$queryvou=mysqli_query($db->conn, "select * from receipt where id='".$id."' and vender='".$com_id."'");
if(mysqli_num_rows($queryvou)==1){$mode="E";
$fetvou=mysqli_fetch_array($queryvou);
}else{$mode="A";}

$hasLinkedInvoice = !empty($fetvou['invoice_id']);
?>

<div class="receipt-container" id="receipt-form-container" <?=$hasLinkedInvoice?'class="invoice-linked"':''?>>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="glyphicon glyphicon-usd"></i> <?=$mode=='E' ? ($xml->editreceipt ?? 'Edit Receipt') : ($xml->newreceipt ?? 'New Receipt')?></h2>
    <a href="index.php?page=receipt_list" class="btn-back"><i class="glyphicon glyphicon-arrow-left"></i> <?=$xml->back ?? 'Back to List'?></a>
</div>

<form action="core-function.php" method="post" id="company-form">

<!-- Invoice Link Section -->
<div class="invoice-link-card">
    <div class="form-row">
        <div class="form-col form-col-6">
            <div class="form-group">
                <label for="invoice_id"><i class="glyphicon glyphicon-link"></i> <?=$xml->linkedinvoice ?? 'Link to Invoice'?></label>
                <select id="invoice_id" name="invoice_id" class="form-control" onchange="loadInvoiceData(this.value)">
                    <option value=""><?=$xml->none ?? '-- Select Invoice (Optional) --'?></option>
                    <?php 
                    $inv_query = mysqli_query($db->conn, "SELECT po.id, iv.taxrw as inv_no, company.name_en,
                        DATE_FORMAT(iv.createdate, '%d-%m-%Y') as inv_date
                        FROM po 
                        JOIN pr ON po.ref=pr.id 
                        JOIN company ON pr.cus_id=company.id 
                        JOIN iv ON po.id=iv.tex
                        WHERE pr.ven_id='".$com_id."'
                        ORDER BY po.id DESC LIMIT 100");
                    while($inv = mysqli_fetch_array($inv_query)) {
                        $selected = ($fetvou['invoice_id'] == $inv['id']) ? 'selected' : '';
                        echo "<option value='".e($inv['id'])."' $selected>INV-".e($inv['inv_no'])." - ".e($inv['name_en'])." (".e($inv['inv_date']).")</option>";
                    }
                    ?>
                </select>
                <small style="color:#666; margin-top:5px; display:block;"><i class="glyphicon glyphicon-info-sign"></i> Linking to an invoice will auto-fill customer and product data</small>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Info Display -->
<div id="invoice_info" class="clearfix" style="margin-bottom:20px; display:<?=$fetvou['invoice_id']?'block':'none'?>;">
    <?php if($fetvou['invoice_id']): 
        $linked_inv = mysqli_fetch_array(mysqli_query($db->conn, "SELECT iv.taxrw, company.name_en, po.vat, po.dis, po.over,
            DATE_FORMAT(iv.createdate, '%d-%m-%Y') as inv_date, DATE_FORMAT(po.valid_pay, '%d-%m-%Y') as due_date
            FROM po 
            JOIN pr ON po.ref=pr.id 
            JOIN company ON pr.cus_id=company.id 
            JOIN iv ON po.id=iv.tex
            WHERE po.id='".$fetvou['invoice_id']."'"));
        
        $inv_products = mysqli_query($db->conn, "SELECT type.name, product.quantity, product.price, product.des
            FROM product 
            JOIN type ON product.type=type.id 
            WHERE po_id='".$fetvou['invoice_id']."'");
        $product_list = [];
        $subtotal = 0;
        while($p = mysqli_fetch_array($inv_products)) {
            $product_list[] = $p;
            $subtotal += $p['quantity'] * $p['price'];
        }
        $discount = $subtotal * $linked_inv['dis'] / 100;
        $after_disc = $subtotal - $discount;
        $overhead = $after_disc * $linked_inv['over'] / 100;
        $after_over = $after_disc + $overhead;
        $vat_amt = $after_over * $linked_inv['vat'] / 100;
        $grand_total = $after_over + $vat_amt;
        
        if($linked_inv):
    ?>
    <div class="panel panel-info">
        <div class="panel-heading"><strong><i class="fa fa-file-text"></i> Linked Invoice: INV-<?=e($linked_inv['taxrw'])?></strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Customer:</strong> <?=e($linked_inv['name_en'])?></p>
                    <p><strong>Invoice Date:</strong> <?=e($linked_inv['inv_date'])?></p>
                    <p><strong>Due Date:</strong> <?=e($linked_inv['due_date'])?></p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Subtotal:</strong> ฿<?=number_format($subtotal, 2)?></p>
                    <?php if($linked_inv['dis'] > 0): ?><p><strong>Discount <?=$linked_inv['dis']?>%:</strong> -฿<?=number_format($discount, 2)?></p><?php endif; ?>
                    <?php if($linked_inv['over'] > 0): ?><p><strong>Overhead <?=$linked_inv['over']?>%:</strong> +฿<?=number_format($overhead, 2)?></p><?php endif; ?>
                    <p><strong>VAT <?=$linked_inv['vat']?>%:</strong> +฿<?=number_format($vat_amt, 2)?></p>
                    <p class="text-primary" style="font-size:18px;"><strong>Grand Total: ฿<?=number_format($grand_total, 2)?></strong></p>
                </div>
            </div>
            <hr>
            <table class="table table-condensed table-striped">
                <thead><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-right">Price</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                <?php $n=1; foreach($product_list as $p): ?>
                <tr>
                    <td><?=$n++?></td>
                    <td><?=e($p['name'])?><?php if($p['des']): ?><br><small class="text-muted"><?=e(substr($p['des'],0,80))?></small><?php endif; ?></td>
                    <td class="text-center"><?=$p['quantity']?></td>
                    <td class="text-right"><?=number_format($p['price'],2)?></td>
                    <td class="text-right"><?=number_format($p['quantity']*$p['price'],2)?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; endif; ?>
</div>

<!-- Customer Information Card -->
<div class="form-card">
    <div class="card-header">
        <h4><i class="glyphicon glyphicon-user"></i> <?=$xml->customerinfo ?? 'Customer Information'?></h4>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-col form-col-4">
                <div class="form-group">
                    <label for="name"><?=$xml->name ?? 'Customer Name'?> *</label>
                    <input id="name" name="name" class="form-control" required placeholder="<?=$xml->name?>" value="<?=$fetvou['name']?>" type="text">
                </div>
            </div>
            <div class="form-col form-col-4">
                <div class="form-group">
                    <label for="email"><?=$xml->email ?? 'Email'?></label>
                    <input id="email" class="form-control" type="email" value="<?=$fetvou['email']?>" name="email" placeholder="email@example.com">
                </div>
            </div>
            <div class="form-col form-col-4">
                <div class="form-group">
                    <label for="phone"><?=$xml->phone ?? 'Phone'?></label>
                    <input id="phone" class="form-control" value="<?=$fetvou['phone']?>" placeholder="+66 xxx xxx xxxx" type="text" name="phone">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Settings Card -->
<div class="form-card">
    <div class="card-header">
        <h4><i class="glyphicon glyphicon-cog"></i> <?=$xml->receiptsettings ?? 'Receipt Settings'?></h4>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-col form-col-3">
                <div class="form-group">
                    <label for="brandven"><?=$xml->brand ?? 'Brand/Logo'?></label>
                    <select id="brandven" name="brandven" class="form-control">
                        <?php 
                        if($fetvou['brand']==0)
                            echo "<option value='0' selected>Use Default</option>";
                        else
                            echo "<option value='0'>Use Default</option>";
                        
                        $querycustomer=mysqli_query($db->conn, "select brand_name,id from brand where ven_id='".$com_id."' ");
                        while($fetch_customer=mysqli_fetch_array($querycustomer)){
                            if($fetch_customer['id']==$fetvou['brand'])
                                echo "<option selected value='".$fetch_customer['id']."'>".$fetch_customer['brand_name']."</option>"; 
                            else 
                                echo "<option value='".$fetch_customer['id']."'>".$fetch_customer['brand_name']."</option>";
                        }?>
                    </select>
                </div>
            </div>
 
            <div class="form-col form-col-2">
                <div class="form-group">
                    <label for="vat"><?=$xml->vat ?? 'VAT'?> *</label>
                    <div class="input-group">
                        <input class="form-control" required name="vat" type="number" step="0.01" value="<?=$fetvou['vat'] ?? 7?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
            </div>
            <div class="form-col form-col-2">
                <div class="form-group">
                    <label for="dis"><?=$xml->discount ?? 'Discount'?></label>
                    <div class="input-group">
                        <input class="form-control" required name="dis" type="number" step="0.01" value="<?=$fetvou['dis'] ?? 0?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
            </div>
            <div class="form-col form-col-3">
                <div class="form-group">
                    <label for="payment_method"><?=$xml->paymentmethod ?? 'Payment Method'?></label>
                    <select id="payment_method" name="payment_method" class="form-control">
                        <?=renderPaymentMethodOptions($db->conn, $fetvou['payment_method'] ?? '', $xml)?>
                    </select>
                </div>
            </div>
            <div class="form-col form-col-2">
                <div class="form-group">
                    <label for="status"><?=$xml->status ?? 'Status'?></label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" <?=$fetvou['status']=='draft'?'selected':''?>><?=$xml->draft ?? 'Draft'?></option>
                        <option value="confirmed" <?=($fetvou['status']=='confirmed' || $mode=='A')?'selected':''?>><?=$xml->confirmed ?? 'Confirmed'?></option>
                        <option value="cancelled" <?=$fetvou['status']=='cancelled'?'selected':''?>><?=$xml->cancelled ?? 'Cancelled'?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Selection Card (hidden when invoice is linked) -->
<div class="form-card product-section" id="product-section" style="<?=$hasLinkedInvoice?'display:none;':''?>">
    <div class="card-header">
        <h4><i class="glyphicon glyphicon-shopping-cart"></i> <?=$xml->pleaseselectproduct ?? 'Products / Services'?></h4>
    </div>
    <div class="card-body">
        <div class="product-header">
            <div style="width:18%; margin-left:0.5%"><?=$xml->product ?? 'Product'?></div>
            <div style="width:18%;"><?=$xml->brand ?? 'Brand'?></div>
            <div style="width:18%;"><?=$xml->model ?? 'Model'?></div>
            <div style="width:14%;"><?=$xml->unit ?? 'Qty'?></div>
            <div style="width:15%;"><?=$xml->price ?? 'Price'?></div> 
            <div style="width:10%;"><?=$xml->warranty ?? 'Date'?></div>
            <div style="width:5%;"></div>
        </div> 
<table id="myTbl" class ="table" width="100%" border="0" cellpadding="0" cellspacing="0">
<?php $i=0;

if($mode=="A"){?>
									
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
			echo "<option value='' >Please Select Product</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="ban_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysqli_query($db->conn, "select brand_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$data_pro[type]."'");
echo "<option value='' >Please Select Brand</option>";
while($fetch_customer=mysqli_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer[id];?>' <?php if($fetch_customer[id]==$data_pro[ban_id]) echo "selected";?> ><?php echo $fetch_customer[brand_name];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select model_name,id from model where brand_id='".$data_pro[ban_id]."' and type_id='".$data_pro[type]."'");
			if(mysqli_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[model]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[model_name]."</option>";
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
	$query_pro=mysqli_query($db->conn, "select pro_id,price,type,ban_id,model,quantity,pack_quantity,des,vo_id,DATE_FORMAT(vo_warranty,'%d-%m-%Y') as vo_warranty from product where re_id='".$id."'");$i=0;

while($data_pro=mysqli_fetch_array($query_pro)){?>
<tr id="fr[<?=$i?>] <?php if($i==0) echo 'firstTr'?>">
    <td  style=" margin-left:0; padding-left:0px; margin-right:0; padding-right:0px;margin-bottom:5px; padding-bottom:10px;">
   
    
       <div id="box" style="width:18%"><select  onchange="checkorder(this.value,this.id)" id="type[<?=$i?>]" name="type[<?=$i?>]" required class="form-control">
				<?php $querycustomer=mysqli_query($db->conn, "select name,id from type");
			echo "<option value='' >Please Select Product</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[type]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[name]."</option>";
				}?>
   </select></div><div id="box"  style="width:18%"><div id="slotbrand[<?=$i?>]"><select required id="ban_id[<?=$i?>]" onchange="checkorder2(this.value,this.id)" name="ban_id[<?=$i?>]" class="form-control">
<?php $querycustomer=mysqli_query($db->conn, "select brand_name,brand.id as id from brand join map_type_to_brand on brand.id=map_type_to_brand.brand_id where type_id='".$data_pro[type]."'");
echo "<option value='' >Please Select Brand</option>";
while($fetch_customer=mysqli_fetch_array($querycustomer)){	?>
					<option value='<?php echo $fetch_customer[id];?>' <?php if($fetch_customer[id]==$data_pro[ban_id]) echo "selected";?> ><?php echo $fetch_customer[brand_name];?></option>     
					
					<?php
				}?>                
		</select></div></div>
   
  
        
          <div id="box" style="width:18%"><div id="slotmodel[<?=$i?>]"><select id="model[<?=$i?>]" name="model[<?=$i?>]" required class="form-control">
			<?php $querycustomer=mysqli_query($db->conn, "select model_name,id from model where brand_id='".$data_pro[ban_id]."' and type_id='".$data_pro[type]."'");
			if(mysqli_num_rows($querycustomer)==0)echo "<option value=''>Type or Brand no model</option>";
			else
			echo "<option value=''>Please Select Model</option>";
		while($fetch_customer=mysqli_fetch_array($querycustomer)){
					if($data_pro[model]==$fetch_customer[id])$condition=" selected='selected' ";else $condition="";
					
					echo "<option value='".$fetch_customer[id]."' ".$condition." >".$fetch_customer[model_name]."</option>";
				}?>
		</select></div></div>
      
        <div id="box" style="width:14%"><div class="input-group">
<input type="number" class="form-control" name="quantity[<?=$i?>]" value="<?php echo $data_pro[quantity];?>" id="quantity[<?=$i?>]" required placeholder="Quantity" />  <span class="input-group-addon"><?=$xml->unit?></span></div></div>
      <input type="hidden" class="form-control" name="pack_quantity[<?=$i?>]" id="pack_quantity[<?=$i?>]" required value="1" />
 <div id="box2" style="width:15%"><div class="input-group"><input type="text" class="form-control" placeholder="Price" required name="price[<?=$i?>]" id="price[<?=$i?>]"  value="<?php echo $data_pro[price];?>"/><span class="input-group-addon"><?=$xml->baht?></span></div></div>
  
 <div id="box" style="width:12%">
  <input type="text" name="warranty[<?=$i?>]" id="warranty[<?=$i?>]" value="<?php echo $data_pro[vo_warranty];?>" placeholder="warranty" class="form-control">
 </div>  

<div id="box" style="width:5%"><a href='' style="width:100%;" class="btn btn-danger" onclick='del_tr(this);return false;'>x</a></div>

 <div id="box" style="width:100%; ">
<textarea name="des[<?=$i?>]" id="des[<?=$i?>]" placeholder="<?=$xml->notes?>"  class="form-control"><?=$data_pro[des];?></textarea></div>
</td>
  </tr>

  <?php $i++;}}?>

                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button id="addRow" class="btn-add-row" type="button" title="Add Row">+</button>
            <button id="removeRow" class="btn-remove-row" type="button" title="Remove Row">−</button>
            <div style="flex-grow:1;"></div>
            <input type="hidden" id="countloop" name="countloop" value="<?=$i?>">
            <input type="hidden" name="method" value="<?=$mode?>">
            <input type="hidden" name="page" value="receipt_list">
            <input type="hidden" name="id" value="<?php echo $id;?>">
            <?php if($mode == 'E' && $id): ?>
            <button type="button" class="btn-preview" onclick="previewReceipt(<?=$id?>)"><i class="glyphicon glyphicon-eye-open"></i> <?=$xml->preview ?? 'Preview PDF'?></button>
            <?php endif; ?>
            <button type="submit" class="btn-submit"><i class="glyphicon glyphicon-ok"></i> <?=$xml->save ?? 'Save Receipt'?></button>
        </div>
    </div>
</form>

</div><!-- /receipt-container -->

<script>
// Initialize visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    var invoiceSelect = document.getElementById('invoice_id');
    if (invoiceSelect && invoiceSelect.value) {
        document.getElementById('receipt-form-container').classList.add('invoice-linked');
        document.getElementById('product-section').style.display = 'none';
    }
});

// Preview Receipt PDF
function previewReceipt(receiptId) {
    if (!receiptId) {
        alert('Please save the receipt first before previewing.');
        return;
    }
    // Open PDF in new tab
    window.open('index.php?page=rep_print&id=' + receiptId, '_blank');
}
</script>

</body>
</html>