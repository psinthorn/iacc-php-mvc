<?php
// Error reporting settings
ini_set('display_errors', 1); // Show errors in browser for debug
ini_set('log_errors', 1);     // Enable error logging
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log'); // Log file path
error_reporting(E_ALL);       // Report all errors
// session_start();
// require_once("inc/sys.configs.php");
// require_once("inc/class.dbconn.php");
// $db=new DbConn($config);
// $db->checkSecurity();
require_once("inc/class.company_filter.php");

// Security: Sanitize ID parameter
$type_id = intval($_REQUEST['id'] ?? 0);
$companyFilter = CompanyFilter::getInstance();

$query=mysqli_query($db->conn,"SELECT * FROM type WHERE id='".$type_id."' " . $companyFilter->andCompanyFilter());
if(mysqli_num_rows($query)==1){
    $method="E";
    $data=mysqli_fetch_array($query);
}else $method="A";
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.form-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 700px; margin: 0 auto; }
.page-header-form { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 20px rgba(14,165,233,0.3); }
.page-header-form h2 { margin: 0; font-size: 24px; font-weight: 700; }

.form-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 24px; }
.form-card .card-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; font-size: 15px; }
.form-card .card-header i { color: #0ea5e9; }
.form-card .card-body { padding: 24px; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }

.form-group { margin-bottom: 0; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 48px; padding: 12px 16px; font-size: 14px; transition: all 0.2s; width: 100%; box-sizing: border-box; }
.form-group textarea.form-control { height: 100px; resize: vertical; }
.form-group .form-control:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); outline: none; }
.form-group select.form-control { cursor: pointer; }

.brand-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.brand-item { display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; }
.brand-item:hover { border-color: #0ea5e9; background: #f0f9ff; }
.brand-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: #0ea5e9; cursor: pointer; }
.brand-item label { margin: 0; font-size: 14px; font-weight: 500; color: #374151; cursor: pointer; }
.brand-item.checked { background: #e0f2fe; border-color: #0ea5e9; }

.btn-submit { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border: none; color: #fff; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(14,165,233,0.4); }
</style>

<div class="form-container">

<div class="page-header-form">
    <i class="fa fa-cubes" style="font-size:28px;"></i>
    <h2><?=$xml->type ?? 'Product Type'?></h2>
</div>

<form action="core-function.php" method="post" id="myform">
<?= csrf_field() ?>
<!-- Basic Information -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-info-circle"></i> <?=$method == "E" ? ($xml->save ?? 'Edit') : ($xml->add ?? 'Add')?> <?=$xml->type ?? 'Type'?>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="type_name"><?=$xml->name?></label>
                <input id="type_name" name="type_name" class="form-control" required type="text" value="<?php echo htmlspecialchars($data['name'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="cat_id"><?=$xml->category?></label>
                <select id="cat_id" name="cat_id" class="form-control">
                    <?php $querycustomer=mysqli_query($db->conn, "SELECT cat_name, id FROM category " . $companyFilter->whereCompanyFilter());
                    while($fetch_customer=mysqli_fetch_array($querycustomer)){
                        $seld = ($data['cat_id']==$fetch_customer['id']) ? ' selected ' : '';
                        echo "<option ".$seld." value='".intval($fetch_customer['id'])."' >".htmlspecialchars($fetch_customer['cat_name'])."</option>";
                    }?>
                </select>
            </div>
            <div class="form-group full">
                <label for="des"><?=$xml->description?></label>
                <textarea name="des" class="form-control"><?php echo htmlspecialchars($data['des'] ?? '');?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Brand Selection -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-bookmark"></i> <?=$xml->brandonthistype?>
    </div>
    <div class="card-body">
        <div class="brand-grid">
            <?php 
            // Selected brands
            $query_additional=mysqli_query($db->conn, "SELECT brand.id as id, brand.brand_name as name FROM brand JOIN map_type_to_brand ON brand.id=map_type_to_brand.brand_id WHERE type_id='".$type_id."' " . $companyFilter->andCompanyFilter('brand') . " ORDER BY brand.brand_name");
            while($fet_additional=mysqli_fetch_array($query_additional)){?>
            <div class="brand-item checked">
                <input type="checkbox" checked id="brand_<?=intval($fet_additional['id'])?>" name="<?=intval($fet_additional['id'])?>" class="checkbox">
                <label for="brand_<?=intval($fet_additional['id'])?>"><?=htmlspecialchars($fet_additional['name'])?></label>
            </div>
            <?php }?>
            <?php 
            // Unselected brands
            $query_additional=mysqli_query($db->conn, "SELECT brand.id as id, brand.brand_name as name FROM brand WHERE id NOT IN (SELECT brand_id FROM map_type_to_brand WHERE type_id='".$type_id."') " . $companyFilter->andCompanyFilter() . " ORDER BY brand.brand_name");
            while($fet_additional=mysqli_fetch_array($query_additional)){?>
            <div class="brand-item">
                <input type="checkbox" name="<?=intval($fet_additional['id'])?>" id="brand_<?=intval($fet_additional['id'])?>" class="checkbox">
                <label for="brand_<?=intval($fet_additional['id'])?>"><?=htmlspecialchars($fet_additional['name'])?></label>
            </div>
            <?php }?>
        </div>
    </div>
</div>

<!-- Submit -->
<div class="form-card">
    <div class="card-body" style="padding:16px 24px;">
        <input type="hidden" name="method" value="<?php echo $method;?>">
        <input type="hidden" name="page" value="type">
        <input type="hidden" name="id" value="<?php echo $type_id;?>">
        
        <button type="submit" class="btn-submit">
            <i class="fa fa-save"></i> <?php if($method=="E") echo $xml->save; else echo $xml->add;?>
        </button>
    </div>
</div>

</form>
</div>

<script>
document.querySelectorAll('.brand-item input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        this.closest('.brand-item').classList.toggle('checked', this.checked);
    });
});
</script>