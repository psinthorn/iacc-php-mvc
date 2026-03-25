<?php
require_once("inc/security.php");
require_once("inc/pagination.php");

$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 15;

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (c.name_en LIKE '%$search_escaped%' OR c.name_th LIKE '%$search_escaped%' OR c.contact LIKE '%$search_escaped%' OR c.email LIKE '%$search_escaped%' OR c.phone LIKE '%$search_escaped%')";
}

// Build type filter
$type_cond = '';
if ($type_filter === 'vendor') {
    $type_cond = " AND c.vender = '1'";
} elseif ($type_filter === 'customer') {
    $type_cond = " AND c.customer = '1'";
}

// Get statistics
if ($com_id > 0) {
    $base_where = "WHERE c.deleted_at IS NULL AND (c.id = $com_id OR c.company_id = $com_id)";
} else {
    $base_where = "WHERE c.deleted_at IS NULL";
}
$stats_query = mysqli_query($db->conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN c.vender = '1' THEN 1 ELSE 0 END) as vendors,
    SUM(CASE WHEN c.customer = '1' THEN 1 ELSE 0 END) as customers
    FROM company c $base_where");
$stats = mysqli_fetch_assoc($stats_query);
$total_items = $stats['total'];
$total_vendors = $stats['vendors'];
$total_customers = $stats['customers'];

// Use pagination helper
$pagination = paginate($total_items, $per_page, $current_page);
$offset = $pagination['offset'];
$total_pages = $pagination['total_pages'];

// Preserve query params for pagination
$query_params = $_GET;
unset($query_params['p']);

// Get items
if($com_id > 0){
    $sql = "SELECT c.id, c.name_en, c.name_th, c.contact, c.vender, c.customer, c.email, c.phone, c.logo,
            CASE 
                WHEN c.id = $com_id THEN 'self'
                WHEN c.vender = '1' AND c.customer = '1' THEN 'both'
                WHEN c.vender = '1' THEN 'vendor'
                WHEN c.customer = '1' THEN 'customer'
                ELSE 'partner'
            END as relationship
            FROM company c
            WHERE c.deleted_at IS NULL $search_cond $type_cond 
            AND (c.id = $com_id OR c.company_id = $com_id)
            ORDER BY 
                CASE WHEN c.id = $com_id THEN 0 ELSE 1 END,
                c.name_en ASC
            LIMIT $offset, $per_page";
} else {
    $sql = "SELECT c.id, c.name_en, c.name_th, c.vender, c.customer, c.contact, c.email, c.phone, c.logo, 'all' as relationship 
            FROM company c
            WHERE c.deleted_at IS NULL $search_cond $type_cond
            ORDER BY c.id DESC
            LIMIT $offset, $per_page";
}
$query = mysqli_query($db->conn, $sql);
$item_count = mysqli_num_rows($query);

// Check for edit mode (for inline form - redirects to company.php modal for now)
$show_form = isset($_GET['new']);
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-building"></i> <?=$xml->company ?? 'Company Management'?></h2>
    <div>
        <span class="text-muted"><?=$com_id > 0 ? 'Customers & Vendors' : 'All Companies'?></span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-building stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->company ?? 'Companies'?></div>
    </div>
    <div class="stat-card info">
        <i class="fa fa-truck stat-icon"></i>
        <div class="stat-value"><?=$total_vendors?></div>
        <div class="stat-label"><?=$xml->vendor ?? 'Vendors'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-users stat-icon"></i>
        <div class="stat-value"><?=$total_customers?></div>
        <div class="stat-label"><?=$xml->customer ?? 'Customers'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-section">
        <!-- Search Input -->
        <div class="search-input-wrapper">
            <form method="get" action="" class="search-form">
                <i class="fa fa-search search-icon"></i>
                <input type="hidden" name="page" value="company">
                <input type="hidden" name="type" value="<?=$type_filter?>">
                <input type="text" class="search-input" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> company, contact, email, phone..." 
                       value="<?=htmlspecialchars($search)?>"
                       autocomplete="off">
                <button type="submit" class="search-btn">
                    <i class="fa fa-arrow-right"></i>
                </button>
            </form>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?page=company&search=<?=urlencode($search)?>" 
               class="filter-tab <?=$type_filter == '' ? 'active' : ''?>">
               <i class="fa fa-th-list"></i>
               <span><?=$xml->all ?? 'All'?></span>
               <span class="tab-count"><?=$total_items?></span>
            </a>
            <a href="?page=company&type=vendor&search=<?=urlencode($search)?>" 
               class="filter-tab <?=$type_filter == 'vendor' ? 'active vendor' : ''?>">
               <i class="fa fa-truck"></i>
               <span><?=$xml->vendor ?? 'Vendors'?></span>
               <span class="tab-count"><?=$total_vendors?></span>
            </a>
            <a href="?page=company&type=customer&search=<?=urlencode($search)?>" 
               class="filter-tab <?=$type_filter == 'customer' ? 'active customer' : ''?>">
               <i class="fa fa-users"></i>
               <span><?=$xml->customer ?? 'Customers'?></span>
               <span class="tab-count"><?=$total_customers?></span>
            </a>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons-group">
        <?php if (!empty($search) || $type_filter != ''): ?>
        <a href="?page=company" class="btn-clear" title="Clear filters">
            <i class="fa fa-times"></i>
            <span><?=$xml->clear ?? 'Clear'?></span>
        </a>
        <?php endif; ?>
        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php', true);" class="btn btn-add">
            <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?>
        </a>
    </div>
</div>

<style>
/* Enhanced Search Section Styles */
.search-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
    flex: 1;
}

.search-input-wrapper {
    position: relative;
    max-width: 500px;
}

.search-form {
    position: relative;
    display: flex;
    align-items: center;
    margin: 0;
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
    z-index: 2;
    pointer-events: none;
}

.search-input {
    width: 100%;
    height: 48px;
    padding: 12px 50px 12px 46px !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 12px !important;
    font-size: 15px !important;
    background: #f8fafc;
    color: #1e293b;
    transition: all 0.2s ease;
}

.search-input:hover {
    border-color: #cbd5e1 !important;
    background: #fff;
}

.search-input:focus {
    border-color: #667eea !important;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12) !important;
    outline: none;
}

.search-input::placeholder {
    color: #94a3b8;
}

.search-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.search-btn:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    border: 2px solid transparent;
    text-decoration: none;
    transition: all 0.2s ease;
}

.filter-tab:hover {
    background: #e2e8f0;
    color: #475569;
    text-decoration: none;
}

.filter-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-color: transparent;
}

.filter-tab.active.vendor {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
}

.filter-tab.active.customer {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.filter-tab i {
    font-size: 14px;
}

.tab-count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.filter-tab:not(.active) .tab-count {
    background: #e2e8f0;
    color: #64748b;
}

/* Action Buttons Group */
.action-buttons-group {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-shrink: 0;
}

.btn-clear {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #ef4444;
    background: #fef2f2;
    border: 2px solid #fecaca;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-clear:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    text-decoration: none;
    color: #dc2626;
}

/* Responsive */
@media (max-width: 768px) {
    .action-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-section {
        width: 100%;
    }
    
    .search-input-wrapper {
        max-width: 100%;
    }
    
    .filter-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 4px;
    }
    
    .filter-tab {
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .filter-tab span:not(.tab-count) {
        display: none;
    }
    
    .action-buttons-group {
        justify-content: flex-end;
    }
    
    .btn-clear span {
        display: none;
    }
}
</style>

<!-- Inline Form Container for AJAX -->
<div id="fetch_state"></div>

<!-- Data Table -->
<div class="master-data-table">
    <?php if ($item_count > 0): ?>
    <table class="table table-hover">
        <thead>
            <tr>
                <th width="50">#</th>
                <th><?=$xml->name ?? 'Name'?></th>
                <th><?=$xml->contact ?? 'Contact'?></th>
                <th><?=$xml->email ?? 'Email'?></th>
                <th><?=$xml->phone ?? 'Phone'?></th>
                <th width="100"><?=$xml->type ?? 'Type'?></th>
                <th width="150"><?=$xml->actions ?? 'Actions'?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $row_num = $offset;
            while($data = mysqli_fetch_array($query)): 
                $row_num++;
                $isSelf = ($com_id > 0 && $data['id'] == $com_id);
                $relationship = $data['relationship'] ?? '';
            ?>
            <tr class="<?=$isSelf ? 'info' : ''?>">
                <td class="text-muted"><?=$row_num?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if (!empty($data['logo'])): ?>
                        <img src="upload/<?=htmlspecialchars($data['logo'])?>" class="logo-preview" alt="">
                        <?php else: ?>
                        <div class="logo-preview" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;">
                            <i class="fa fa-building text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="item-name"><?=htmlspecialchars($data['name_en'])?></span>
                            <?php if ($isSelf): ?>
                            <span class="badge" style="background:#007bff;color:white;font-size:0.7rem;margin-left:5px;">YOU</span>
                            <?php endif; ?>
                            <?php if ($data['name_th']): ?>
                            <br><small class="item-desc"><?=htmlspecialchars($data['name_th'])?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><?=htmlspecialchars($data['contact']) ?: '-'?></td>
                <td>
                    <?php if ($data['email']): ?>
                    <a href="mailto:<?=htmlspecialchars($data['email'])?>"><?=htmlspecialchars($data['email'])?></a>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><?=htmlspecialchars($data['phone']) ?: '-'?></td>
                <td>
                    <?php if ($data['vender'] == '1' && $data['customer'] == '1'): ?>
                    <span class="badge-both"><?=$xml->both ?? 'Both'?></span>
                    <?php elseif ($data['vender'] == '1'): ?>
                    <span class="badge-vendor"><?=$xml->vendor ?? 'Vendor'?></span>
                    <?php elseif ($data['customer'] == '1'): ?>
                    <span class="badge-customer"><?=$xml->customer ?? 'Customer'?></span>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="index.php?page=remote&select_company=<?=$data['id']?>" 
                           class="btn btn-select <?=($com_id == $data['id']) ? 'active' : ''?>" 
                           title="<?=($com_id == $data['id']) ? 'Currently Selected' : ($xml->select ?? 'Select Company')?>">
                            <i class="fa fa-sign-in"></i>
                        </a>
                        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php?id=<?=$data['id']?>', true);" 
                           class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="#" onclick="confirmDelete(<?=$data['id']?>, '<?=htmlspecialchars(addslashes($data['name_en']))?>')" 
                           class="btn btn-delete" title="<?=$xml->delete ?? 'Delete'?>">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?= render_pagination($pagination, '?page=company', $query_params, 'p') ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-building-o"></i>
        <h4><?=$xml->no_data ?? 'No Companies Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by adding your first customer or vendor'?></p>
        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php', true);" class="btn btn-add">
            <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Company'?>
        </a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?\n\nThis action cannot be undone.')) {
        // Create form and submit
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'core-function.php';
        
        var fields = {
            'page': 'company',
            'method': 'D',
            'id': id,
            'csrf_token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
        };
        
        for (var key in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>