<?php
// Ensure session and database connection are available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

// Create database connection if not already available
if (!isset($db) || !$db) {
    $db = new DbConn($config);
}

$id = sql_int($_REQUEST['id'] ?? 0);
$query=mysqli_query($db->conn, "select * from company where id='".$id."'");
if(mysqli_num_rows($query)==1){
    $method="E";
    $data=mysqli_fetch_array($query);
    
    // Fetch current address data for editing - get the most recent active address
    $addr_query = mysqli_query($db->conn, 
        "SELECT * FROM company_addr WHERE com_id='".$id."' AND deleted_at IS NULL ORDER BY (valid_end = '0000-00-00' OR valid_end = '9999-12-31') DESC, valid_start DESC LIMIT 1"
    );
    if($addr_query && mysqli_num_rows($addr_query) >= 1) {
        $addr_data = mysqli_fetch_array($addr_query);
        // Merge address data into data array for form display
        $data['adr_tax'] = $addr_data['adr_tax'];
        $data['city_tax'] = $addr_data['city_tax'];
        $data['district_tax'] = $addr_data['district_tax'];
        $data['province_tax'] = $addr_data['province_tax'];
        $data['zip_tax'] = $addr_data['zip_tax'];
        $data['adr_bil'] = $addr_data['adr_bil'];
        $data['city_bil'] = $addr_data['city_bil'];
        $data['district_bil'] = $addr_data['district_bil'];
        $data['province_bil'] = $addr_data['province_bil'];
        $data['zip_bil'] = $addr_data['zip_bil'];
        $data['addr_id'] = $addr_data['id']; // Store address ID for update
    }
} else { 
    $method="A";
    $data = []; // Initialize empty array for new company
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Modern Form Styling - Enhanced UX/UI */
.form-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; width: 100%; margin: 0 auto; padding: 0; }

.page-header-form { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: #fff; 
    padding: 14px 20px; 
    border-radius: 12px; 
    margin-bottom: 16px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    gap: 12px; 
    box-shadow: 0 4px 16px rgba(102,126,234,0.25);
}
.page-header-form h2 { margin: 0; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.page-header-form .header-badge { 
    background: rgba(255,255,255,0.2); 
    padding: 5px 12px; 
    border-radius: 20px; 
    font-size: 11px; 
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.form-card { 
    background: #fff; 
    border-radius: 10px; 
    box-shadow: 0 1px 8px rgba(0,0,0,0.05); 
    margin-bottom: 12px; 
    border: 1px solid #e5e7eb; 
    overflow: hidden;
    transition: all 0.3s ease;
}
.form-card:hover { 
    box-shadow: 0 2px 12px rgba(0,0,0,0.07); 
}
.form-card .card-header { 
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
    padding: 10px 14px; 
    border-bottom: 1px solid #e5e7eb; 
    font-weight: 600; 
    color: #1e293b; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    font-size: 13px;
}
.form-card .card-header i { 
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 6px;
    font-size: 11px;
}
.form-card .card-body { padding: 14px; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
.form-grid.full { grid-template-columns: 1fr; }
.form-grid.three { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 900px) { .form-grid.three { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .form-grid.three { grid-template-columns: 1fr; } }

.form-group { margin-bottom: 0; position: relative; }
.form-group label { 
    display: block; 
    font-size: 10px; 
    font-weight: 600; 
    color: #64748b; 
    margin-bottom: 4px; 
    text-transform: uppercase; 
    letter-spacing: 0.4px;
}
.form-group .form-control { 
    border-radius: 8px; 
    border: 1px solid #e5e7eb; 
    height: 36px; 
    padding: 6px 10px; 
    font-size: 13px; 
    transition: all 0.2s ease; 
    width: 100%; 
    box-sizing: border-box;
    background: #fafafa;
}
.form-group .form-control:hover { border-color: #cbd5e1; background: #fff; }
.form-group textarea.form-control { height: 70px; resize: vertical; }
.form-group .form-control:focus { 
    border-color: #667eea; 
    box-shadow: 0 0 0 3px rgba(102,126,234,0.12); 
    outline: none;
    background: #fff;
}
.form-group .form-control::placeholder { color: #94a3b8; }

/* Logo Upload Styling - Fixed Layout */
.logo-upload-area {
    display: grid;
    grid-template-columns: 90px 1fr;
    gap: 14px;
    padding: 12px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 10px;
    border: 1px dashed #e5e7eb;
    align-items: center;
    overflow: hidden;
}
.logo-preview { 
    width: 90px;
    height: 90px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.logo-preview img { 
    max-width: 90px;
    max-height: 90px;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 8px; 
    border: 1px solid #e5e7eb; 
    padding: 4px; 
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    display: block;
    box-sizing: border-box;
}
.logo-preview-placeholder {
    width: 90px;
    height: 90px;
    border-radius: 8px;
    border: 1px dashed #cbd5e1;
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
}
.logo-preview-placeholder i { font-size: 20px; margin-bottom: 4px; }
.logo-preview-placeholder span { font-size: 10px; font-weight: 500; }
.logo-upload-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
}
.logo-upload-info h4 { margin: 0 0 2px 0; color: #1e293b; font-size: 12px; }
.logo-upload-info p { margin: 0 0 6px 0; color: #64748b; font-size: 11px; line-height: 1.3; }
.logo-upload-info .form-control[type="file"] {
    height: auto;
    padding: 6px 10px;
    background: #fff;
    cursor: pointer;
    font-size: 12px;
    width: 100%;
    box-sizing: border-box;
}
.logo-upload-info .form-control[type="file"]:hover { border-color: #667eea; }

@media (max-width: 500px) {
    .logo-upload-area { grid-template-columns: 1fr; justify-items: center; text-align: center; }
    .logo-upload-info { min-height: auto; }
    .logo-preview { width: 80px; height: 80px; }
}

/* Checkbox Styling */
.checkbox-group { 
    display: flex; 
    gap: 10px; 
    align-items: center; 
    flex-wrap: wrap;
}
.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.2s ease;
    flex: 1;
    min-width: 140px;
}
.checkbox-item:hover { border-color: #667eea; background: #fff; }
.checkbox-item.checked { 
    border-color: #667eea; 
    background: linear-gradient(135deg, rgba(102,126,234,0.08) 0%, rgba(118,75,162,0.08) 100%);
}
.checkbox-item input[type="checkbox"] { 
    width: 16px; 
    height: 16px; 
    accent-color: #667eea;
    cursor: pointer;
}
.checkbox-item .checkbox-label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
}
.checkbox-item .checkbox-desc {
    font-size: 10px;
    color: #64748b;
    margin-top: 1px;
}

/* Copy Address Button */
.copy-address-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 12px;
}
.copy-address-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(102,126,234,0.35);
}

/* Submit Button */
.form-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    padding-top: 6px;
}
.btn-submit { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    border: none; 
    color: #fff; 
    padding: 10px 24px; 
    border-radius: 8px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 16px rgba(102,126,234,0.3);
}
.btn-submit:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 6px 20px rgba(102,126,234,0.4);
}
.btn-submit:active { transform: translateY(0); }

.btn-cancel {
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #64748b;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-cancel:hover {
    border-color: #cbd5e1;
    color: #374151;
    background: #f8fafc;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .page-header-form { padding: 12px; flex-direction: column; text-align: center; }
    .page-header-form h2 { font-size: 15px; }
    .form-actions { flex-direction: column; }
    .btn-submit, .btn-cancel { width: 100%; justify-content: center; }
}
</style>

<div class="form-container">

<!-- Page Header -->
<div class="page-header-form">
    <h2><i class="fa fa-building"></i> <?php echo $method=="E" ? 'Edit Company' : 'Add New Company'; ?></h2>
    <span class="header-badge"><?php echo $method=="E" ? '<i class="fa fa-edit"></i> Editing: '.htmlspecialchars($data['name_en'] ?? '') : '<i class="fa fa-plus"></i> New Entry'; ?></span>
</div>

<form action="core-function.php" method="post" enctype="multipart/form-data" id="company-form">

<!-- Basic Information -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-info-circle"></i> <?=$xml->information?>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="name_en"><?=$xml->nameen?></label>
                <input id="name_en" name="name_en" class="form-control" required type="text" placeholder="Enter English name" value="<?php echo htmlspecialchars($data['name_en'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="name_th"><?=$xml->nameth?></label>
                <input id="name_th" name="name_th" class="form-control" required type="text" placeholder="ใส่ชื่อภาษาไทย" value="<?php echo htmlspecialchars($data['name_th'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="name_sh"><?=$xml->namesh?></label>
                <input id="name_sh" name="name_sh" class="form-control" required type="text" placeholder="Short name / Abbreviation" value="<?php echo htmlspecialchars($data['name_sh'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="tax"><?=$xml->tax?></label>
                <input id="tax" name="tax" class="form-control" required type="text" placeholder="Tax ID number" value="<?php echo htmlspecialchars($data['tax'] ?? '');?>">
            </div>
        </div>
    </div>
</div>

<!-- Contact Information -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-address-card"></i> Contact Information
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="contact"><?=$xml->contact?></label>
                <input id="contact" name="contact" class="form-control" required type="text" placeholder="Contact person name" value="<?php echo htmlspecialchars($data['contact'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="email"><?=$xml->email?></label>
                <input id="email" name="email" class="form-control" required type="email" placeholder="email@example.com" value="<?php echo htmlspecialchars($data['email'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="phone"><?=$xml->phone?></label>
                <input id="phone" name="phone" class="form-control" required type="text" placeholder="+66 XX XXX XXXX" value="<?php echo htmlspecialchars($data['phone'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="fax"><?=$xml->fax?></label>
                <input id="fax" name="fax" class="form-control" type="text" placeholder="Fax number (optional)" value="<?php echo htmlspecialchars($data['fax'] ?? '');?>">
            </div>
        </div>
    </div>
</div>

<!-- Logo & Terms -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-image"></i> Logo & Terms
    </div>
    <div class="card-body">
        <div class="logo-upload-area">
            <div class="logo-preview">
                <?php if(!empty($data['logo'])): ?>
                <img src="upload/<?php echo htmlspecialchars($data['logo']);?>" alt="Company Logo">
                <?php else: ?>
                <div class="logo-preview-placeholder">
                    <i class="fa fa-camera"></i>
                    <span>No Logo</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="logo-upload-info">
                <h4><?=$xml->logo?></h4>
                <p>Supported: JPG, PNG (max 2MB)</p>
                <input id="logo" name="logo" class="form-control" type="file" accept="image/jpeg,image/jpg,image/png">
            </div>
        </div>
        
        <div class="form-group" style="margin-top: 12px;">
            <label for="term"><?=$xml->term?></label>
            <textarea id="term" name="term" class="form-control" placeholder="Enter payment terms, conditions, or notes..."><?php echo htmlspecialchars($data['term'] ?? '');?></textarea>
        </div>
    </div>
</div>

<!-- Register Address -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-map-marker"></i> <?=$xml->registeraddress?>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="adr_tax"><?=$xml->raddress?></label>
                <input id="adr_tax" name="adr_tax" class="form-control" type="text" placeholder="Street address, building, floor" value="<?php echo htmlspecialchars($data['adr_tax'] ?? '');?>">
            </div>
        </div>
        <div class="form-grid three" style="margin-top: 12px;">
            <div class="form-group">
                <label for="district_tax"><?=$xml->rdistrict?></label>
                <input id="district_tax" name="district_tax" class="form-control" type="text" placeholder="District/Subdistrict" value="<?php echo htmlspecialchars($data['district_tax'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="city_tax"><?=$xml->rcity?></label>
                <input id="city_tax" name="city_tax" class="form-control" type="text" placeholder="City/Amphoe" value="<?php echo htmlspecialchars($data['city_tax'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="province_tax"><?=$xml->rprovince?></label>
                <input id="province_tax" name="province_tax" class="form-control" type="text" placeholder="Province" value="<?php echo htmlspecialchars($data['province_tax'] ?? '');?>">
            </div>
        </div>
        <div class="form-grid" style="margin-top: 12px;">
            <div class="form-group">
                <label for="zip_tax"><?=$xml->rzip?></label>
                <input id="zip_tax" name="zip_tax" class="form-control" type="text" placeholder="Postal code" value="<?php echo htmlspecialchars($data['zip_tax'] ?? '');?>">
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
        <button type="button" class="copy-address-btn" onclick="copyRegisterAddress()">
            <i class="fa fa-copy"></i> Copy from Register Address
        </button>
        
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="adr_bil"><?=$xml->baddress?></label>
                <input id="adr_bil" name="adr_bil" placeholder="<?=$xml->nullforsave?>" class="form-control" type="text" value="<?php echo htmlspecialchars($data['adr_bil'] ?? '');?>">
            </div>
        </div>
        <div class="form-grid three" style="margin-top: 12px;">
            <div class="form-group">
                <label for="district_bil"><?=$xml->bdistrict?></label>
                <input id="district_bil" name="district_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo htmlspecialchars($data['district_bil'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="city_bil"><?=$xml->bcity?></label>
                <input id="city_bil" name="city_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo htmlspecialchars($data['city_bil'] ?? '');?>">
            </div>
            <div class="form-group">
                <label for="province_bil"><?=$xml->bprovince?></label>
                <input id="province_bil" name="province_bil" class="form-control" placeholder="<?=$xml->nullforsave?>" type="text" value="<?php echo htmlspecialchars($data['province_bil'] ?? '');?>">
            </div>
        </div>
        <div class="form-grid" style="margin-top: 12px;">
            <div class="form-group">
                <label for="zip_bil"><?=$xml->bzip?></label>
                <input id="zip_bil" name="zip_bil" class="form-control" type="text" placeholder="<?=$xml->nullforsave?>" value="<?php echo htmlspecialchars($data['zip_bil'] ?? '');?>">
            </div>
        </div>
    </div>
</div>

<!-- Company Type & Submit -->
<div class="form-card">
    <div class="card-header">
        <i class="fa fa-tags"></i> Company Type
    </div>
    <div class="card-body">
        <div class="checkbox-group">
            <label class="checkbox-item <?php if(($data['customer'] ?? '')=="1") echo 'checked'; ?>" onclick="toggleCheckbox(this)">
                <input type="checkbox" name="customer" <?php if(($data['customer'] ?? '')=="1") echo "checked"; ?> value="1">
                <div>
                    <div class="checkbox-label"><i class="fa fa-user"></i> <?=$xml->customer?></div>
                    <div class="checkbox-desc">This company is a customer</div>
                </div>
            </label>
            <label class="checkbox-item <?php if(($data['vender'] ?? '')=="1") echo 'checked'; ?>" onclick="toggleCheckbox(this)">
                <input type="checkbox" name="vender" <?php if(($data['vender'] ?? '')=="1") echo "checked"; ?> value="1">
                <div>
                    <div class="checkbox-label"><i class="fa fa-truck"></i> <?=$xml->vender?></div>
                    <div class="checkbox-desc">This company is a vendor/supplier</div>
                </div>
            </label>
        </div>
        
        <input type="hidden" name="method" value="<?php echo e($method);?>">
        <input type="hidden" name="page" value="company">
        <input type="hidden" name="id" value="<?php echo e($_REQUEST['id'] ?? '');?>">
        <input type="hidden" name="addr_id" value="<?php echo e($data['addr_id'] ?? '');?>">
        <?= csrf_field() ?>
        
        <div class="form-actions" style="margin-top: 16px;">
            <a href="javascript:history.back()" class="btn-cancel">
                <i class="fa fa-arrow-left"></i> Cancel
            </a>
            <button type="submit" class="btn-submit">
                <i class="fa fa-<?php echo $method=="E" ? 'check' : 'plus'; ?>"></i> 
                <?php echo $method=="E" ? 'Update Company' : 'Create Company'; ?>
            </button>
        </div>
    </div>
</div>

</form>
</div><!-- /form-container -->

<script>
function copyRegisterAddress() {
    document.getElementById('adr_bil').value = document.getElementById('adr_tax').value;
    document.getElementById('city_bil').value = document.getElementById('city_tax').value;
    document.getElementById('district_bil').value = document.getElementById('district_tax').value;
    document.getElementById('province_bil').value = document.getElementById('province_tax').value;
    document.getElementById('zip_bil').value = document.getElementById('zip_tax').value;
}

function toggleCheckbox(label) {
    const checkbox = label.querySelector('input[type="checkbox"]');
    setTimeout(() => {
        if(checkbox.checked) {
            label.classList.add('checked');
        } else {
            label.classList.remove('checked');
        }
    }, 10);
}
</script>