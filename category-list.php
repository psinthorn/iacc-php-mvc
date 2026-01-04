<?php
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
require_once("inc/pagination.php");

// Get company filter instance
$companyFilter = CompanyFilter::getInstance();
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 15;

// Build search condition with company filter
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (cat_name LIKE '%$search_escaped%' OR des LIKE '%$search_escaped%')";
}

// Get statistics
$stats_query = mysqli_query($db->conn, "SELECT COUNT(*) as total FROM category " . $companyFilter->whereCompanyFilter());
$stats = mysqli_fetch_assoc($stats_query);
$total_items = $stats['total'];

// Use pagination helper
$pagination = paginate($total_items, $per_page, $current_page);
$offset = $pagination['offset'];
$total_pages = $pagination['total_pages'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['p']);

// Get items with usage count
$sql = "SELECT c.id, c.cat_name, c.des, 
        (SELECT COUNT(*) FROM type t WHERE t.cat_id = c.id " . $companyFilter->andCompanyFilter('t') . ") as product_count
        FROM category c " . $companyFilter->whereCompanyFilter('c') . " $search_cond 
        ORDER BY c.id DESC LIMIT $offset, $per_page";
$query = mysqli_query($db->conn, $sql);
$item_count = mysqli_num_rows($query);

// Check for edit mode
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $edit_query = mysqli_query($db->conn, "SELECT * FROM category WHERE id='$edit_id' " . $companyFilter->andCompanyFilter());
    if (mysqli_num_rows($edit_query) == 1) {
        $edit_data = mysqli_fetch_assoc($edit_query);
    }
}
$show_form = isset($_GET['new']) || $edit_data;
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-folder-open"></i> <?=$xml->category ?? 'Category Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-folder stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->category ?? 'Categories'?></div>
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
            <input type="hidden" name="page" value="category">
            <input type="text" class="form-control" name="search" 
                   placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->category ?? 'category'?>..." 
                   value="<?=htmlspecialchars($search)?>" 
                   onchange="this.form.submit()">
        </form>
    </div>
    <div>
        <?php if (!empty($search)): ?>
        <a href="?page=category" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=category&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="master-data-guide.php" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->category ?? 'Category'?></h4>
        <a href="?page=category" class="btn-close-form">&times;</a>
    </div>
    <form action="core-function.php" method="post" id="categoryForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="cat_name"><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="cat_name" name="cat_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->category ?? 'category'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['cat_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <input type="text" class="form-control" id="des" name="des"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..."
                       value="<?=htmlspecialchars($edit_data['des'] ?? '')?>">
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="category">
            <input type="hidden" name="id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Category')?>
            </button>
            <a href="?page=category" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
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
                <th><?=$xml->description ?? 'Description'?></th>
                <th width="120"><?=$xml->products ?? 'Products'?></th>
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
                    <span class="item-name"><?=htmlspecialchars($data['cat_name'])?></span>
                </td>
                <td>
                    <span class="item-desc"><?=htmlspecialchars($data['des']) ?: '-'?></span>
                </td>
                <td>
                    <span class="badge badge-info"><?=$data['product_count']?> <?=$xml->items ?? 'items'?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=category&edit=<?=$data['id']?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <?php if ($data['product_count'] == 0): ?>
                        <a href="core-function.php?method=D&id=<?=$data['id']?>&page=category" 
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
    <?= render_pagination($pagination, '?page=category', $query_params, 'p') ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-folder-open-o"></i>
        <h4><?=$xml->no_data ?? 'No Categories Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first category'?></p>
        <a href="?page=category&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Category'?></a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
// Auto-focus on form when visible and scroll to it
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('cat_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>