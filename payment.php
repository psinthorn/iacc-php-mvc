<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$users=new DbConn($config);
// Security already checked in index.php

// Get current company for multi-tenant isolation
$com_id = sql_int($_SESSION['com_id']);
$id = sql_int($_REQUEST['id']);
// SECURITY FIX: Add company_id filter to prevent cross-tenant data access
$query=mysqli_query($db->conn, "select * from payment where id='".$id."' AND com_id='".$com_id."'");
if(mysqli_num_rows($query)==1){
    $method="E";
    $data=mysqli_fetch_array($query);
}else $method="A";
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.form-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; }
.page-header-form { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 20px rgba(16,185,129,0.3); }
.page-header-form h2 { margin: 0; font-size: 24px; font-weight: 700; }

.form-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
.form-card .card-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; font-size: 15px; }
.form-card .card-header i { color: #10b981; }
.form-card .card-body { padding: 24px; }

.form-group { margin-bottom: 20px; }
.form-group:last-child { margin-bottom: 0; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 48px; padding: 12px 16px; font-size: 14px; transition: all 0.2s; width: 100%; box-sizing: border-box; }
.form-group .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); outline: none; }

.btn-submit { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: #fff; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 8px; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
</style>

<div class="form-container">

<div class="page-header-form">
    <i class="fa fa-credit-card" style="font-size:28px;"></i>
    <h2><?=$xml->payment ?? 'Payment Term'?></h2>
</div>

<div class="form-card">
    <div class="card-header">
        <i class="fa fa-edit"></i> <?=$method == "E" ? ($xml->edit ?? 'Edit') : ($xml->add ?? 'Add')?> <?=$xml->payment ?? 'Payment'?>
    </div>
    <div class="card-body">
        <form action="core-function.php" method="post" id="myform">
            <div class="form-group">
                <label for="payment_name"><?=$xml->name?></label>
                <input id="payment_name" name="payment_name" class="form-control" required type="text" value="<?php echo e($data['payment_name'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="payment_des"><?=$xml->description?></label>
                <input id="payment_des" name="payment_des" class="form-control" required type="text" value="<?php echo e($data['payment_des'] ?? '');?>">
            </div>
            
            <input type="hidden" name="method" value="<?php echo $method;?>">
            <input type="hidden" name="page" value="payment">
            <input type="hidden" name="id" value="<?php echo $id;?>">
            
            <button type="submit" class="btn-submit">
                <i class="fa fa-save"></i> <?php if($method=="E") echo $xml->edit; else echo $xml->add;?>
            </button>
        </form>
    </div>
</div>

</div>