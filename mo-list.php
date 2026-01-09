<?php 
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
require_once("inc/pagination.php");

// Get company filter instance
$companyFilter = CompanyFilter::getInstance();
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
$brand_filter = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 15;

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (model_name LIKE '%$search_escaped%' OR type.name LIKE '%$search_escaped%' OR brand.brand_name LIKE '%$search_escaped%')";
}
if ($type_filter > 0) {
    $search_cond .= " AND model.type_id = '$type_filter'";
}
if ($brand_filter > 0) {
    $search_cond .= " AND model.brand_id = '$brand_filter'";
}

// Get statistics
$stats_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM model " . $companyFilter->whereCompanyFilter());
$stats = mysqli_fetch_assoc($stats_query);
$total_items = $stats['total'];

// Use pagination helper
$pagination = paginate($total_items, $per_page, $current_page);
$offset = $pagination['offset'];
$total_pages = $pagination['total_pages'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['p']);

// Get types and brands for filter dropdowns
$types_query = mysqli_query($db->conn, "SELECT id, name FROM type " . $companyFilter->whereCompanyFilter() . " ORDER BY name");
$brands_query = mysqli_query($db->conn, "SELECT id, brand_name FROM brand " . $companyFilter->whereCompanyFilter() . " ORDER BY brand_name");

// Get items
$sql = "SELECT model.id as id, model_name, type.name as type_name, model.type_id, brand.brand_name, model.brand_id, model.price, model.des
        FROM model 
        JOIN type ON model.type_id = type.id 
        JOIN brand ON model.brand_id = brand.id 
        WHERE 1=1 " . $companyFilter->andCompanyFilter('model') . " $search_cond 
        ORDER BY model.id DESC LIMIT $offset, $per_page";
$query = mysqli_query($db->conn, $sql);
$item_count = mysqli_num_rows($query);

// Check for edit mode
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $edit_query = mysqli_query($db->conn, "SELECT * FROM model WHERE id='$edit_id' " . $companyFilter->andCompanyFilter());
    if (mysqli_num_rows($edit_query) == 1) {
        $edit_data = mysqli_fetch_assoc($edit_query);
    }
}
$show_form = isset($_GET['new']) || $edit_data;

// Build return URL with current filters for delete/edit actions
$return_params = [];
$return_params['page'] = 'mo_list';
if ($type_filter > 0) $return_params['type_id'] = $type_filter;
if ($brand_filter > 0) $return_params['brand_id'] = $brand_filter;
if (!empty($search)) $return_params['search'] = $search;
$return_url = http_build_query($return_params);
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<?php
// Display flash messages
if (isset($_SESSION['flash_message'])): 
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
?>
<div class="flash-message flash-<?=$flash['type']?>" id="flashMessage">
    <i class="fa fa-<?=$flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle')?>"></i>
    <?=htmlspecialchars($flash['text'])?>
    <button type="button" class="flash-close" onclick="this.parentElement.style.display='none';">&times;</button>
</div>
<style>
.flash-message {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    animation: slideDown 0.3s ease-out;
}
.flash-message i { font-size: 20px; }
.flash-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.flash-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.flash-warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.flash-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
.flash-close {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.6;
    color: inherit;
}
.flash-close:hover { opacity: 1; }
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<script>setTimeout(function(){ var el = document.getElementById('flashMessage'); if(el) el.style.display='none'; }, 5000);</script>
<?php endif; ?>

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-tags"></i> <?=$xml->model ?? 'Model Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-tags stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->model ?? 'Models'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$item_count?></div>
        <div class="stat-label"><?=$xml->showing ?? 'Showing'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-box" style="display:flex;gap:12px;max-width:800px;flex-wrap:wrap;align-items:center;">
        <div style="position:relative;flex:1;min-width:280px;">
            <form method="get" action="" style="margin:0;" id="searchForm">
                <i class="fa fa-search"></i>
                <input type="hidden" name="page" value="mo_list">
                <input type="hidden" name="type_id" value="<?=$type_filter?>">
                <input type="hidden" name="brand_id" value="<?=$brand_filter?>">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->model ?? 'model'?>..." 
                       value="<?=htmlspecialchars($search)?>" 
                       onchange="this.form.submit()">
            </form>
        </div>
        <select class="form-control filter-select" style="min-width:180px;" onchange="window.location='?page=mo_list&type_id='+this.value+'&brand_id=<?=$brand_filter?>&search=<?=urlencode($search)?>'">
            <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->type ?? 'Types'?></option>
            <?php 
            mysqli_data_seek($types_query, 0);
            while($type = mysqli_fetch_array($types_query)): ?>
            <option value="<?=$type['id']?>" <?=$type_filter == $type['id'] ? 'selected' : ''?>><?=htmlspecialchars($type['name'])?></option>
            <?php endwhile; ?>
        </select>
        <select class="form-control filter-select" style="min-width:180px;" onchange="window.location='?page=mo_list&brand_id='+this.value+'&type_id=<?=$type_filter?>&search=<?=urlencode($search)?>'">
            <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->brand ?? 'Brands'?></option>
            <?php 
            mysqli_data_seek($brands_query, 0);
            while($brand = mysqli_fetch_array($brands_query)): ?>
            <option value="<?=$brand['id']?>" <?=$brand_filter == $brand['id'] ? 'selected' : ''?>><?=htmlspecialchars($brand['brand_name'])?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <?php if (!empty($search) || $type_filter > 0 || $brand_filter > 0): ?>
        <a href="?page=mo_list" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=mo_list&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="master-data-guide.php" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->model ?? 'Model'?></h4>
        <a href="?page=mo_list" class="btn-close-form">&times;</a>
    </div>
    <form action="core-function.php" method="post" id="modelForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="model_name"><i class="fa fa-tag"></i> <?=$xml->model ?? 'Model'?> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="model_name" name="model_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->model ?? 'model'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['model_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="price"><i class="fa fa-money"></i> <?=$xml->price ?? 'Price'?></label>
                <input type="number" step="0.01" class="form-control" id="price" name="price"
                       placeholder="0.00"
                       value="<?=htmlspecialchars($edit_data['price'] ?? '')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="type"><i class="fa fa-cube"></i> <?=$xml->type ?? 'Product Type'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="type" name="type" required onchange="loadBrands(this.value)">
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->type ?? 'Type'?> --</option>
                    <?php 
                    mysqli_data_seek($types_query, 0);
                    while($type = mysqli_fetch_array($types_query)): ?>
                    <option value="<?=$type['id']?>" <?=($edit_data['type_id'] ?? 0) == $type['id'] ? 'selected' : ''?>><?=htmlspecialchars($type['name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="brand"><i class="fa fa-bookmark"></i> <?=$xml->brand ?? 'Brand'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="brand" name="brand" required>
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->type ?? 'Type'?> <?=$xml->first ?? 'first'?> --</option>
                    <?php if ($edit_data): 
                        mysqli_data_seek($brands_query, 0);
                        while($brand = mysqli_fetch_array($brands_query)): ?>
                    <option value="<?=$brand['id']?>" <?=($edit_data['brand_id'] ?? 0) == $brand['id'] ? 'selected' : ''?>><?=htmlspecialchars($brand['brand_name'])?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" id="des" name="des" rows="4"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..." style="min-height:120px;"><?=htmlspecialchars($edit_data['des'] ?? '')?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="mo_list">
            <input type="hidden" name="p_id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Model')?>
            </button>
            <a href="?page=mo_list" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
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
                <th><?=$xml->model ?? 'Model'?></th>
                <th><?=$xml->type ?? 'Type'?></th>
                <th><?=$xml->brand ?? 'Brand'?></th>
                <th width="120"><?=$xml->price ?? 'Price'?></th>
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
                    <span class="item-name"><?=htmlspecialchars($data['model_name'])?></span>
                    <?php if ($data['des']): ?>
                    <br><small class="item-desc"><?=htmlspecialchars(substr($data['des'], 0, 40))?><?=strlen($data['des']) > 40 ? '...' : ''?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-default" style="background:#e0f2fe;color:#0369a1;"><?=htmlspecialchars($data['type_name'])?></span>
                </td>
                <td>
                    <span class="badge badge-default" style="background:#fef3c7;color:#92400e;"><?=htmlspecialchars($data['brand_name'])?></span>
                </td>
                <td>
                    <?php if ($data['price'] > 0): ?>
                    <strong><?=number_format($data['price'], 2)?></strong>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=mo_list&edit=<?=$data['id']?><?=$type_filter > 0 ? '&type_id='.$type_filter : ''?><?=$brand_filter > 0 ? '&brand_id='.$brand_filter : ''?><?=!empty($search) ? '&search='.urlencode($search) : ''?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="core-function.php?method=D&p_id=<?=$data['id']?>&<?=$return_url?>" 
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
    <?= render_pagination($pagination, '?page=mo_list', $query_params, 'p') ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-tags"></i>
        <h4><?=$xml->no_data ?? 'No Models Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first model'?></p>
        <a href="?page=mo_list&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Model'?></a>
    </div>
    <?php endif; ?>
</div>

<script>
// Load brands based on selected type
function loadBrands(typeId) {
    if (!typeId) {
        document.getElementById('brand').innerHTML = '<option value="">-- Select Type first --</option>';
        return;
    }
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('brand').innerHTML = xhr.responseText;
        }
    };
    xhr.open("GET", "model.php?q=" + typeId, true);
    xhr.send();
}

</script>

</div><!-- /.master-data-container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('model_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>