<?php
//session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
$db=new DbConn($config);
$db->checkSecurity();
$brand_id = sql_int($_REQUEST['id'] ?? 0);
$companyFilter = CompanyFilter::getInstance();

$query=mysqli_query($db->conn, "SELECT * FROM brand WHERE id='".$brand_id."' " . $companyFilter->andCompanyFilter());
if(mysqli_num_rows($query)==1){
    $method="E";
    $data=mysqli_fetch_array($query);
}else $method="A";
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.form-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 600px; margin: 0 auto; }
.page-header-form { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 20px rgba(245,158,11,0.3); }
.page-header-form h2 { margin: 0; font-size: 24px; font-weight: 700; }

.form-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 24px; }
.form-card .card-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; font-size: 15px; }
.form-card .card-header i { color: #f59e0b; }
.form-card .card-body { padding: 24px; }

.form-group { margin-bottom: 20px; }
.form-group:last-child { margin-bottom: 0; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 48px; padding: 12px 16px; font-size: 14px; transition: all 0.2s; width: 100%; box-sizing: border-box; }
.form-group .form-control:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); outline: none; }
.form-group select.form-control { cursor: pointer; }

.logo-preview { margin-bottom: 12px; }
.logo-preview img { border-radius: 12px; border: 1px solid #e5e7eb; padding: 8px; background: #f8fafc; }

.btn-submit { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; color: #fff; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
</style>

<div class="form-container">

<div class="page-header-form">
    <i class="fa fa-bookmark" style="font-size:28px;"></i>
    <h2><?=$xml->brand ?? 'Brand'?></h2>
</div>

<form action="core-function.php" method="post" enctype="multipart/form-data" id="myform">
<?= csrf_field() ?>
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-edit"></i> <?=$method == "E" ? ($xml->save ?? 'Edit') : ($xml->add ?? 'Add')?> <?=$xml->brand ?? 'Brand'?>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="brand_name"><?=$xml->name?></label>
            <input id="brand_name" name="brand_name" class="form-control" required type="text" value="<?php echo e($data['brand_name'] ?? '');?>">
        </div>
        <div class="form-group">
            <label for="des"><?=$xml->description?></label>
            <input id="des" name="des" class="form-control" required type="text" value="<?php echo e($data['des'] ?? '');?>">
        </div>
        <div class="form-group">
            <label for="ven_id"><?=$xml->owner?></label>
            <select id="ven_id" name="ven_id" class="form-control">
                <option value="0">Non Owner</option>
                <?php 
                $querycustomer=mysqli_query($db->conn, "select name_en,id from company where vender='1' AND company_id = '" . intval($_SESSION['com_id']) . "'");
                while($fetch_customer=mysqli_fetch_array($querycustomer)){
                    $selected = ($fetch_customer['id']==$data['ven_id']) ? 'selected' : '';
                    echo "<option $selected value='".intval($fetch_customer['id'])."' >".e($fetch_customer['name_en'])."</option>";
                }?>
            </select>
        </div>
        <div class="form-group">
            <label for="logo"><?=$xml->logo?></label>
            <?php if(isset($data['logo']) && $data['logo']!=""){?>
            <div class="logo-preview"><img width="200" src="upload/<?php echo e($data['logo']);?>"></div>
            <?php } ?>
            <input id="logo" name="logo" class="form-control" type="file" style="height:auto;padding:12px;">
        </div>
        
        <input type="hidden" name="method" value="<?php echo $method;?>">
        <input type="hidden" name="page" value="brand">
        <input type="hidden" name="id" value="<?php echo $brand_id;?>">
        
        <button type="submit" class="btn-submit">
            <i class="fa fa-save"></i> <?php if($method=="E") echo $xml->save; else echo $xml->add;?>
        </button>
    </div>
</div>

</form>
</div>