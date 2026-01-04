<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
require_once("inc/pagination.php");
$db=new DbConn($config);
$db->checkSecurity();

// Get company filter instance
$companyFilter = CompanyFilter::getInstance();
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_filter = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 15;

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (type.name LIKE '%$search_escaped%' OR cat_name LIKE '%$search_escaped%')";
}
if ($cat_filter > 0) {
    $search_cond .= " AND type.cat_id = '$cat_filter'";
}

// Get statistics
$stats_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM type " . $companyFilter->whereCompanyFilter());
$stats = mysqli_fetch_assoc($stats_query);
$total_items = $stats['total'];

// Use pagination helper
$pagination = paginate($total_items, $per_page, $current_page);
$offset = $pagination['offset'];
$total_pages = $pagination['total_pages'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['p']);

// Get categories for filter dropdown
$categories_query = mysqli_query($db->conn, "SELECT id, cat_name FROM category " . $companyFilter->whereCompanyFilter() . " ORDER BY cat_name");

// Get items with brand count
$sql = "SELECT type.id as id, type.name, type.des, category.cat_name, type.cat_id,
        (SELECT COUNT(*) FROM map_type_to_brand m WHERE m.type_id = type.id) as brand_count
        FROM type 
        JOIN category ON type.cat_id = category.id 
        WHERE 1=1 " . $companyFilter->andCompanyFilter('type') . " $search_cond 
        ORDER BY type.id DESC LIMIT $offset, $per_page";
$query = mysqli_query($db->conn, $sql);
$item_count = mysqli_num_rows($query);

// Check for edit mode
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
$edit_brands = [];
if ($edit_id > 0) {
    $edit_query = mysqli_query($db->conn, "SELECT * FROM type WHERE id='$edit_id' " . $companyFilter->andCompanyFilter());
    if (mysqli_num_rows($edit_query) == 1) {
        $edit_data = mysqli_fetch_assoc($edit_query);
        // Get associated brands
        $brands_q = mysqli_query($db->conn, "SELECT brand_id FROM map_type_to_brand WHERE type_id='$edit_id'");
        while ($b = mysqli_fetch_assoc($brands_q)) {
            $edit_brands[] = $b['brand_id'];
        }
    }
}
$show_form = isset($_GET['new']) || $edit_data;

// Get all brands for checkboxes
$all_brands_query = mysqli_query($db->conn, "SELECT id, brand_name FROM brand " . $companyFilter->whereCompanyFilter() . " ORDER BY brand_name");
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-cube"></i> <?=$xml->product ?? 'Product Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-cube stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->products ?? 'Products'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$item_count?></div>
        <div class="stat-label"><?=$xml->showing ?? 'Showing'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-box" style="display:flex;gap:10px;max-width:500px;">
        <div style="position:relative;flex:1;">
            <form method="get" action="" style="margin:0;" id="searchForm">
                <i class="fa fa-search"></i>
                <input type="hidden" name="page" value="type">
                <input type="hidden" name="cat" value="<?=$cat_filter?>">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->product ?? 'product'?>..." 
                       value="<?=htmlspecialchars($search)?>" 
                       onchange="this.form.submit()">
            </form>
        </div>
        <select class="form-control" style="width:150px;" onchange="window.location='?page=type&cat='+this.value+'&search=<?=urlencode($search)?>'">
            <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->category ?? 'Categories'?></option>
            <?php 
            mysqli_data_seek($categories_query, 0);
            while($cat = mysqli_fetch_array($categories_query)): ?>
            <option value="<?=$cat['id']?>" <?=$cat_filter == $cat['id'] ? 'selected' : ''?>><?=htmlspecialchars($cat['cat_name'])?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <?php if (!empty($search) || $cat_filter > 0): ?>
        <a href="?page=type" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=type&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="master-data-guide.php" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->product ?? 'Product'?></h4>
        <a href="?page=type" class="btn-close-form">&times;</a>
    </div>
    <form action="core-function.php" method="post" id="productForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="type_name"><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="type_name" name="type_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->product ?? 'product'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="cat_id"><i class="fa fa-folder"></i> <?=$xml->category ?? 'Category'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="cat_id" name="cat_id" required>
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->category ?? 'Category'?> --</option>
                    <?php 
                    mysqli_data_seek($categories_query, 0);
                    while($cat = mysqli_fetch_array($categories_query)): ?>
                    <option value="<?=$cat['id']?>" <?=($edit_data['cat_id'] ?? 0) == $cat['id'] ? 'selected' : ''?>><?=htmlspecialchars($cat['cat_name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" id="des" name="des" rows="2"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..."><?=htmlspecialchars($edit_data['des'] ?? '')?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label><i class="fa fa-bookmark"></i> <?=$xml->brandonthistype ?? 'Associated Brands'?></label>
                <div style="display:flex;flex-wrap:wrap;gap:10px;padding:10px;background:#f8f9fa;border-radius:8px;max-height:150px;overflow-y:auto;">
                    <?php 
                    mysqli_data_seek($all_brands_query, 0);
                    while($brand = mysqli_fetch_array($all_brands_query)): 
                        $checked = in_array($brand['id'], $edit_brands) ? 'checked' : '';
                    ?>
                    <label style="display:flex;align-items:center;gap:5px;cursor:pointer;padding:5px 10px;background:white;border-radius:5px;border:1px solid #ddd;">
                        <input type="checkbox" name="<?=$brand['id']?>" <?=$checked?>>
                        <?=htmlspecialchars($brand['brand_name'])?>
                    </label>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="type">
            <input type="hidden" name="id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Product')?>
            </button>
            <a href="?page=type" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
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
                <th><?=$xml->name ?? 'Name'?></th>
                <th><?=$xml->category ?? 'Category'?></th>
                <th width="100"><?=$xml->brand ?? 'Brands'?></th>
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
                    <span class="item-name"><?=htmlspecialchars($data['name'])?></span>
                    <?php if ($data['des']): ?>
                    <br><small class="item-desc"><?=htmlspecialchars(substr($data['des'], 0, 50))?><?=strlen($data['des']) > 50 ? '...' : ''?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-default" style="background:#e9ecef;color:#495057;"><?=htmlspecialchars($data['cat_name'])?></span>
                </td>
                <td>
                    <span class="badge badge-info"><?=$data['brand_count']?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=type&edit=<?=$data['id']?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="core-function.php?method=D&id=<?=$data['id']?>&page=type" 
                           class="btn btn-delete" title="<?=$xml->delete ?? 'Delete'?>"
                           onclick="return confirm('<?=$xml->confirm_delete ?? 'Are you sure you want to delete this item?'?>');">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?= render_pagination($pagination, '?page=type', $query_params, 'p') ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-cube"></i>
        <h4><?=$xml->no_data ?? 'No Products Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first product'?></p>
        <a href="?page=type&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Product'?></a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('type_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>