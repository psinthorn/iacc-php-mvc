<?php
require_once("inc/security.php");
require_once("inc/class.company_filter.php");

// Get company filter instance
$companyFilter = CompanyFilter::getInstance();
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 15;
$offset = ($page_num - 1) * $per_page;

// Build search condition with company filter
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (brand_name LIKE '%$search_escaped%' OR des LIKE '%$search_escaped%')";
}

// Get statistics
$stats_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM brand " . $companyFilter->whereCompanyFilter());
$stats = mysqli_fetch_assoc($stats_query);
$total_items = $stats['total'];
$total_pages = ceil($total_items / $per_page);

// Get items with usage count and vendor info
$sql = "SELECT b.id, b.brand_name, b.des, b.logo, b.ven_id,
        (SELECT name_en FROM company WHERE id = b.ven_id) as vendor_name,
        (SELECT COUNT(*) FROM map_type_to_brand m WHERE m.brand_id = b.id) as product_count
        FROM brand b " . $companyFilter->whereCompanyFilter('b') . " $search_cond 
        ORDER BY b.id DESC LIMIT $offset, $per_page";
$query = mysqli_query($db->conn, $sql);
$item_count = mysqli_num_rows($query);

// Check for edit mode
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $edit_query = mysqli_query($db->conn, "SELECT * FROM brand WHERE id='$edit_id' " . $companyFilter->andCompanyFilter());
    if (mysqli_num_rows($edit_query) == 1) {
        $edit_data = mysqli_fetch_assoc($edit_query);
    }
}
$show_form = isset($_GET['new']) || $edit_data;

// Get logged-in company info for default vendor
$own_company_query = mysqli_query($db->conn, "SELECT id, name_en FROM company WHERE id = '$com_id'");
$own_company = mysqli_fetch_assoc($own_company_query);

// Get vendors for dropdown (excluding own company to avoid duplicate)
$vendors_query = mysqli_query($db->conn, "SELECT id, name_en FROM company WHERE vender='1' AND company_id = '$com_id' AND id != '$com_id' ORDER BY name_en");
?>
<link rel="stylesheet" href="css/master-data.css">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-bookmark"></i> <?=$xml->brand ?? 'Brand Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-bookmark stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->brand ?? 'Brands'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$item_count?></div>
        <div class="stat-label"><?=$xml->showing ?? 'Showing'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-box">
        <form method="get" action="" style="margin:0;">
            <i class="fa fa-search"></i>
            <input type="hidden" name="page" value="brand">
            <input type="text" class="form-control" name="search" 
                   placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->brand ?? 'brand'?>..." 
                   value="<?=htmlspecialchars($search)?>" 
                   onchange="this.form.submit()">
        </form>
    </div>
    <div>
        <?php if (!empty($search)): ?>
        <a href="?page=brand" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=brand&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="master-data-guide.php" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->brand ?? 'Brand'?></h4>
        <a href="?page=brand" class="btn-close-form">&times;</a>
    </div>
    <form action="core-function.php" method="post" enctype="multipart/form-data" id="brandForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="brand_name"><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="brand_name" name="brand_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->brand ?? 'brand'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['brand_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <input type="text" class="form-control" id="des" name="des"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..."
                       value="<?=htmlspecialchars($edit_data['des'] ?? '')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="ven_id"><i class="fa fa-truck"></i> <?=$xml->owner ?? 'Owner/Vendor'?></label>
                <select class="form-control" id="ven_id" name="ven_id">
                    <option value="0">-- <?=$xml->no_owner ?? 'No Owner'?> --</option>
                    <?php if ($own_company): ?>
                    <option value="<?=$own_company['id']?>" <?=($edit_data['ven_id'] ?? $own_company['id']) == $own_company['id'] ? 'selected' : ''?>>
                        <?=htmlspecialchars($own_company['name_en'])?> (<?=$xml->own_company ?? 'Own Company'?>)
                    </option>
                    <?php endif; ?>
                    <?php while($vendor = mysqli_fetch_array($vendors_query)): ?>
                    <option value="<?=$vendor['id']?>" <?=($edit_data['ven_id'] ?? 0) == $vendor['id'] ? 'selected' : ''?>>
                        <?=htmlspecialchars($vendor['name_en'])?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="logo"><i class="fa fa-image"></i> <?=$xml->logo ?? 'Logo'?></label>
                <?php if (!empty($edit_data['logo'])): ?>
                <div style="margin-bottom: 8px;">
                    <img src="upload/<?=htmlspecialchars($edit_data['logo'])?>" class="logo-preview-large">
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/jpg">
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="brand">
            <input type="hidden" name="id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Brand')?>
            </button>
            <a href="?page=brand" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="master-data-table">
    <?php if ($item_count > 0): ?>
    <table class="table table-hover">
        <thead>
            <tr>
                <th width="50">#</th>
                <th width="60"><?=$xml->logo ?? 'Logo'?></th>
                <th><?=$xml->name ?? 'Name'?></th>
                <th><?=$xml->description ?? 'Description'?></th>
                <th><?=$xml->owner ?? 'Owner'?></th>
                <th width="100"><?=$xml->products ?? 'Products'?></th>
                <th width="120"><?=$xml->actions ?? 'Actions'?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $row_num = $offset;
            while($data = mysqli_fetch_array($query)): 
                $row_num++;
            ?>
            <tr>
                <td class="text-muted"><?=$row_num?></td>
                <td>
                    <?php if (!empty($data['logo'])): ?>
                    <img src="upload/<?=htmlspecialchars($data['logo'])?>" class="logo-preview" alt="<?=htmlspecialchars($data['brand_name'])?>">
                    <?php else: ?>
                    <div class="logo-preview" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-bookmark text-muted"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="item-name"><?=htmlspecialchars($data['brand_name'])?></span>
                </td>
                <td>
                    <span class="item-desc"><?=htmlspecialchars($data['des']) ?: '-'?></span>
                </td>
                <td>
                    <?php if ($data['vendor_name']): ?>
                    <span class="badge-vendor"><?=htmlspecialchars($data['vendor_name'])?></span>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-info"><?=$data['product_count']?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=brand&edit=<?=$data['id']?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <?php if ($data['product_count'] == 0): ?>
                        <a href="core-function.php?method=D&id=<?=$data['id']?>&page=brand" 
                           class="btn btn-delete" title="<?=$xml->delete ?? 'Delete'?>"
                           onclick="return confirm('<?=$xml->confirm_delete ?? 'Are you sure you want to delete this item?'?>');">
                            <i class="fa fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <button class="btn btn-delete" disabled title="<?=$xml->cannot_delete ?? 'Cannot delete: has associated products'?>">
                            <i class="fa fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="master-data-pagination">
        <div class="page-info">
            <?=$xml->showing ?? 'Showing'?> <?=$offset + 1?>-<?=min($offset + $per_page, $total_items)?> 
            <?=$xml->of ?? 'of'?> <?=$total_items?> <?=$xml->items ?? 'items'?>
        </div>
        <ul class="pagination pagination-sm">
            <?php if ($page_num > 1): ?>
            <li><a href="?page=brand&p=1&search=<?=urlencode($search)?>">&laquo;</a></li>
            <li><a href="?page=brand&p=<?=$page_num-1?>&search=<?=urlencode($search)?>">&lsaquo;</a></li>
            <?php endif; ?>
            
            <?php 
            $start_page = max(1, $page_num - 2);
            $end_page = min($total_pages, $page_num + 2);
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
            <li class="<?=$i == $page_num ? 'active' : ''?>">
                <a href="?page=brand&p=<?=$i?>&search=<?=urlencode($search)?>"><?=$i?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page_num < $total_pages): ?>
            <li><a href="?page=brand&p=<?=$page_num+1?>&search=<?=urlencode($search)?>">&rsaquo;</a></li>
            <li><a href="?page=brand&p=<?=$total_pages?>&search=<?=urlencode($search)?>">&raquo;</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-bookmark-o"></i>
        <h4><?=$xml->no_data ?? 'No Brands Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first brand'?></p>
        <a href="?page=brand&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Brand'?></a>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('brand_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>