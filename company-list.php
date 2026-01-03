<?php
require_once("inc/security.php");

$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 15;
$offset = ($page_num - 1) * $per_page;

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
$total_pages = ceil($total_items / $per_page);

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
    <div class="search-box" style="display:flex;gap:10px;max-width:500px;flex-wrap:wrap;">
        <div style="position:relative;flex:1;min-width:200px;">
            <form method="get" action="" style="margin:0;">
                <i class="fa fa-search"></i>
                <input type="hidden" name="page" value="company">
                <input type="hidden" name="type" value="<?=$type_filter?>">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> name, contact, email..." 
                       value="<?=htmlspecialchars($search)?>" 
                       onchange="this.form.submit()">
            </form>
        </div>
        <div class="btn-group">
            <a href="?page=company&search=<?=urlencode($search)?>" 
               class="btn btn-sm <?=$type_filter == '' ? 'btn-primary' : 'btn-default'?>"><?=$xml->all ?? 'All'?></a>
            <a href="?page=company&type=vendor&search=<?=urlencode($search)?>" 
               class="btn btn-sm <?=$type_filter == 'vendor' ? 'btn-info' : 'btn-default'?>">
               <i class="fa fa-truck"></i> <?=$xml->vendor ?? 'Vendors'?></a>
            <a href="?page=company&type=customer&search=<?=urlencode($search)?>" 
               class="btn btn-sm <?=$type_filter == 'customer' ? 'btn-success' : 'btn-default'?>">
               <i class="fa fa-users"></i> <?=$xml->customer ?? 'Customers'?></a>
        </div>
    </div>
    <div>
        <?php if (!empty($search) || $type_filter != ''): ?>
        <a href="?page=company" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php', true);" class="btn btn-add">
            <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?>
        </a>
        <a href="master-data-guide.php" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

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
                        <?php if ($com_id == 0): ?>
                        <a href="remoteuser.php?id=<?=$data['id']?>" class="btn btn-view" title="<?=$xml->remote ?? 'Remote Login'?>">
                            <i class="fa fa-sign-in"></i>
                        </a>
                        <?php endif; ?>
                        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php?id=<?=$data['id']?>', true);" 
                           class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company-addr.php?id=<?=$data['id']?>', true);" 
                           class="btn btn-view" title="<?=$xml->address ?? 'Address'?>">
                            <i class="fa fa-map-marker"></i>
                        </a>
                        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'credit-list.php?id=<?=$data['id']?>', true);" 
                           class="btn" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;" title="<?=$xml->credit ?? 'Credit'?>">
                            <i class="fa fa-credit-card"></i>
                        </a>
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
            <li><a href="?page=company&p=1&search=<?=urlencode($search)?>&type=<?=$type_filter?>">&laquo;</a></li>
            <li><a href="?page=company&p=<?=$page_num-1?>&search=<?=urlencode($search)?>&type=<?=$type_filter?>">&lsaquo;</a></li>
            <?php endif; ?>
            
            <?php 
            $start_page = max(1, $page_num - 2);
            $end_page = min($total_pages, $page_num + 2);
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
            <li class="<?=$i == $page_num ? 'active' : ''?>">
                <a href="?page=company&p=<?=$i?>&search=<?=urlencode($search)?>&type=<?=$type_filter?>"><?=$i?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page_num < $total_pages): ?>
            <li><a href="?page=company&p=<?=$page_num+1?>&search=<?=urlencode($search)?>&type=<?=$type_filter?>">&rsaquo;</a></li>
            <li><a href="?page=company&p=<?=$total_pages?>&search=<?=urlencode($search)?>&type=<?=$type_filter?>">&raquo;</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
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