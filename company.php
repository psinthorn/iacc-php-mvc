<?php
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
// $users=new DbConn($config);
// // Security already checked in index.php

$id = sql_int($_REQUEST['id']);
$query=mysqli_query($db->conn, "select * from company where id='".$id."'");
if(mysqli_num_rows($query)==1){
$method="E";
$data=mysqli_fetch_array($query);
}else $method="A";
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Modern Form Styling */
.form-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 900px; margin: 0 auto; }
.page-header-form { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 20px rgba(37,99,235,0.3); }
.page-header-form h2 { margin: 0; font-size: 24px; font-weight: 700; }

.form-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.form-card .card-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; font-size: 15px; }
.form-card .card-header i { color: #2563eb; }
.form-card .card-body { padding: 24px; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
.form-grid.full { grid-template-columns: 1fr; }

.form-group { margin-bottom: 0; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 48px; padding: 12px 16px; font-size: 14px; transition: all 0.2s; width: 100%; box-sizing: border-box; }
.form-group textarea.form-control { height: 120px; resize: vertical; }
.form-group .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); outline: none; }

.logo-preview { margin-bottom: 12px; }
.logo-preview img { border-radius: 12px; border: 1px solid #e5e7eb; padding: 8px; background: #f8fafc; }

.checkbox-group { display: flex; gap: 24px; align-items: center; padding: 16px 0; }
.checkbox-group label { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; color: #374151; cursor: pointer; }
.checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #2563eb; }

.btn-submit { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; color: #fff; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.4); }
</style>

<div class="form-container">

<!-- Page Header -->
<div class="page-header-form">
    <i class="fa fa-briefcase" style="font-size:28px;"></i>
    <h2><?=$xml->information?></h2>
</div>

<form action="core-function.php" method="post" enctype="multipart/form-data" id="company-form">

<!-- Basic Information -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-building"></i> Basic Information
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="name_en"><?=$xml->nameen?></label>
                <input id="name_en" name="name_en" class="form-control" required type="text" value="<?php echo $data['name_en'];?>">
            </div>
            <div class="form-group">
                <label for="name_th"><?=$xml->nameth?></label>
                <input id="name_th" name="name_th" class="form-control" required type="text" value="<?php echo $data['name_th'];?>">
            </div>
            <div class="form-group">
                <label for="name_sh"><?=$xml->namesh?></label>
                <input id="name_sh" name="name_sh" class="form-control" required type="text" value="<?php echo $data['name_sh'];?>">
            </div>
            <div class="form-group">
                <label for="contact"><?=$xml->contact?></label>
                <input id="contact" name="contact" class="form-control" required type="text" value="<?php echo $data['contact'];?>">
            </div>
            <div class="form-group">
                <label for="email"><?=$xml->email?></label>
                <input id="email" name="email" class="form-control" required type="email" value="<?php echo $data['email'];?>">
            </div>
            <div class="form-group">
                <label for="phone"><?=$xml->phone?></label>
                <input id="phone" name="phone" class="form-control" required type="text" value="<?php echo $data['phone'];?>">
            </div>
            <div class="form-group">
                <label for="fax"><?=$xml->fax?></label>
                <input id="fax" name="fax" class="form-control" required type="text" value="<?php echo $data['fax'];?>">
            </div>
            <div class="form-group">
                <label for="tax"><?=$xml->tax?></label>
                <input id="tax" name="tax" class="form-control" required type="number" value="<?php echo $data['tax'];?>">
            </div>
        </div>
        
        <div class="form-grid full" style="margin-top:20px;">
            <div class="form-group">
                <label for="logo"><?=$xml->logo?></label>
                <?php if($data['logo']!=""){?>
                <div class="logo-preview"><img width="200" src="upload/<?php echo $data['logo'];?>"></div>
                <?php } ?>
                <input id="logo" name="logo" class="form-control" type="file" style="height:auto;padding:12px;">
            </div>
            <div class="form-group">
                <label for="term"><?=$xml->term?></label>
                <textarea id="term" name="term" class="form-control"><?php echo $data['term'];?></textarea>
            </div>
        </div>
    </div>
</div>

<?php if($method!="E"): ?>
<!-- Register Address -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-map-marker"></i> <?=$xml->registeraddress?>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="adr_tax"><?=$xml->raddress?></label>
                <input id="adr_tax" name="adr_tax" class="form-control" type="text" value="<?php echo $data['adr_tax'];?>">
            </div>
            <div class="form-group">
                <label for="city_tax"><?=$xml->rcity?></label>
                <input id="city_tax" name="city_tax" class="form-control" type="text" value="<?php echo $data['city_tax'];?>">
            </div>
            <div class="form-group">
                <label for="district_tax"><?=$xml->rdistrict?></label>
                <input id="district_tax" name="district_tax" class="form-control" type="text" value="<?php echo $data['district_tax'];?>">
            </div>
            <div class="form-group">
                <label for="province_tax"><?=$xml->rprovince?></label>
                <input id="province_tax" name="province_tax" class="form-control" type="text" value="<?php echo $data['province_tax'];?>">
            </div>
            <div class="form-group">
                <label for="zip_tax"><?=$xml->rzip?></label>
                <input id="zip_tax" name="zip_tax" class="form-control" type="text" value="<?php echo $data['zip_tax'];?>">
            </div>
        </div>
    </div>
</div>

<!-- Billing Address -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-file-text-o"></i> <?=$xml->exitingaddress?>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="adr_bil"><?=$xml->baddress?></label>
                <input id="adr_bil" name="adr_bil" placeholder="<?=$xml->nullforsave?>" class="form-control" type="text" value="<?php echo $data['adr_bil'];?>">
            </div>
            <div class="form-group">
                <label for="city_bil"><?=$xml->bcity?></label>
                <input id="city_bil" name="city_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo $data['city_bil'];?>">
            </div>
            <div class="form-group">
                <label for="district_bil"><?=$xml->bdistrict?></label>
                <input id="district_bil" name="district_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo $data['district_bil'];?>">
            </div>
            <div class="form-group">
                <label for="province_bil"><?=$xml->bprovince?></label>
                <input id="province_bil" name="province_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo $data['province_bil'];?>">
            </div>
            <div class="form-group">
                <label for="zip_bil"><?=$xml->bzip?></label>
                <input id="zip_bil" name="zip_bil" class="form-control" type="text" placeholder="<?=$xml->nullforsave?>" value="<?php echo $data['zip_bil'];?>">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Options & Submit -->
<div class="form-card">
    <div class="card-body">
        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="customer" <?php if($data['customer']=="1")echo "checked"; ?> value="1"> <?=$xml->customer?>
            </label>
            <label>
                <input type="checkbox" name="vender" <?php if($data['vender']=="1")echo "checked"; ?> value="1"> <?=$xml->vender?>
            </label>
        </div>
        
        <input type="hidden" name="method" value="<?php echo e($method);?>">
        <input type="hidden" name="page" value="company">
        <input type="hidden" name="id" value="<?php echo e($_REQUEST['id'] ?? '');?>">
        <?= csrf_field() ?>
        
        <button type="submit" class="btn-submit">
            <i class="fa fa-save"></i> <?php if($method=="E")echo $xml->save; else echo $xml->add?>
        </button>
    </div>
</div>

</form>
</div><!-- /form-container -->