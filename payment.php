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
<link rel="stylesheet" href="css/master-data.css">
<style>
/* Form Page Specific Styles */
.form-page-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.form-page-header {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: white;
    padding: 28px 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);
    display: flex;
    align-items: center;
    gap: 16px;
}

.form-page-header .header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.form-page-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.form-page-header .subtitle {
    margin: 4px 0 0;
    opacity: 0.9;
    font-size: 14px;
    font-weight: 400;
}

.form-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.form-card .card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
}

.form-card .card-header i {
    color: #4f46e5;
    font-size: 18px;
}

.form-card .card-body {
    padding: 28px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group:last-of-type {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group .form-control {
    width: 100%;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    height: 50px;
    padding: 12px 16px;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s ease;
    box-sizing: border-box;
    background: #fff;
}

.form-group .form-control:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    outline: none;
}

.form-group .form-control::placeholder {
    color: #9ca3af;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.btn-submit {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    border: none;
    color: #fff;
    padding: 14px 28px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Inter', sans-serif;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
}

.btn-cancel {
    background: #fff;
    border: 2px solid #e5e7eb;
    color: #6b7280;
    padding: 14px 28px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
}

.btn-cancel:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #374151;
}

/* Info box */
.form-info-box {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #1e40af;
}

.form-info-box i {
    font-size: 18px;
}
</style>

<div class="form-page-container">
    <!-- Page Header -->
    <div class="form-page-header">
        <div class="header-icon">
            <i class="fa fa-credit-card"></i>
        </div>
        <div>
            <h1><?=$xml->payment ?? 'Payment Term'?></h1>
            <p class="subtitle"><?=$method == "E" ? ($xml->edit_payment_subtitle ?? 'Update payment term details') : ($xml->add_payment_subtitle ?? 'Create a new payment term')?></p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="form-card">
        <div class="card-header">
            <i class="fa fa-<?=$method == "E" ? 'edit' : 'plus-circle'?>"></i>
            <?=$method == "E" ? ($xml->edit ?? 'Edit') : ($xml->add ?? 'Add New')?> <?=$xml->payment ?? 'Payment Term'?>
        </div>
        <div class="card-body">
            <?php if($method == "E"): ?>
            <div class="form-info-box">
                <i class="fa fa-info-circle"></i>
                <span><?=$xml->editing_record ?? 'You are editing an existing record'?> (ID: <?=$id?>)</span>
            </div>
            <?php endif; ?>

            <form action="core-function.php" method="post" id="myform">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="payment_name"><?=$xml->name ?? 'Payment Name'?></label>
                    <input id="payment_name" name="payment_name" class="form-control" required type="text" 
                           placeholder="<?=$xml->enter_payment_name ?? 'Enter payment term name'?>"
                           value="<?php echo e($data['payment_name'] ?? '');?>">
                </div>
                <div class="form-group">
                    <label for="payment_des"><?=$xml->description ?? 'Description'?></label>
                    <input id="payment_des" name="payment_des" class="form-control" required type="text" 
                           placeholder="<?=$xml->enter_description ?? 'Enter description'?>"
                           value="<?php echo e($data['payment_des'] ?? '');?>">
                </div>
                
                <input type="hidden" name="method" value="<?php echo $method;?>">
                <input type="hidden" name="page" value="payment">
                <input type="hidden" name="id" value="<?php echo $id;?>">
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fa fa-<?=$method == "E" ? 'save' : 'plus'?>"></i>
                        <?php if($method=="E") echo $xml->save ?? 'Save Changes'; else echo $xml->add ?? 'Add Payment';?>
                    </button>
                    <a href="?page=payment_list" class="btn-cancel">
                        <i class="fa fa-times"></i>
                        <?=$xml->cancel ?? 'Cancel'?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>