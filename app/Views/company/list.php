<?php
/**
 * Company List View
 * 
 * Variables from CompanyController::index():
 *   $items, $total, $count, $pagination, $stats,
 *   $search, $type_filter, $com_id, $query_params
 */
require_once __DIR__ . '/../../../inc/pagination.php';
$total_items     = $stats['total'];
$total_vendors   = $stats['vendors'];
$total_customers = $stats['customers'];
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
        <div class="search-input-wrapper">
            <form method="get" action="" class="search-form">
                <i class="fa fa-search search-icon"></i>
                <input type="hidden" name="page" value="company">
                <input type="hidden" name="type" value="<?=htmlspecialchars($type_filter)?>">
                <input type="text" class="search-input" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> company, contact, email, phone..." 
                       value="<?=htmlspecialchars($search)?>"
                       autocomplete="off">
                <button type="submit" class="search-btn"><i class="fa fa-arrow-right"></i></button>
            </form>
        </div>
        
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
    
    <div class="action-buttons-group">
        <?php if (!empty($search) || $type_filter != ''): ?>
        <a href="?page=company" class="btn-clear" title="Clear filters">
            <i class="fa fa-times"></i>
            <span><?=$xml->clear ?? 'Clear'?></span>
        </a>
        <?php endif; ?>
        <a href="index.php?page=company_form" class="btn btn-add">
            <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?>
        </a>
    </div>
</div>

<style>
.search-section { display: flex; flex-direction: column; gap: 16px; flex: 1; }
.search-input-wrapper { position: relative; max-width: 500px; }
.search-form { position: relative; display: flex; align-items: center; margin: 0; }
.search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; z-index: 2; pointer-events: none; }
.search-input { width: 100%; height: 48px; padding: 12px 50px 12px 46px !important; border: 2px solid #e2e8f0 !important; border-radius: 12px !important; font-size: 15px !important; background: #f8fafc; color: #1e293b; transition: all 0.2s ease; }
.search-input:hover { border-color: #cbd5e1 !important; background: #fff; }
.search-input:focus { border-color: #667eea !important; background: #fff; box-shadow: 0 0 0 4px rgba(102,126,234,0.12) !important; outline: none; }
.search-btn { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border: none; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.filter-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; color: #64748b; background: #f1f5f9; border: 2px solid transparent; text-decoration: none; transition: all 0.2s ease; }
.filter-tab:hover { background: #e2e8f0; color: #475569; text-decoration: none; }
.filter-tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
.filter-tab.active.vendor { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.filter-tab.active.customer { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.tab-count { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.filter-tab:not(.active) .tab-count { background: #e2e8f0; color: #64748b; }
.action-buttons-group { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.btn-clear { display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; color: #ef4444; background: #fef2f2; border: 2px solid #fecaca; text-decoration: none; }
.btn-clear:hover { background: #fee2e2; border-color: #fca5a5; text-decoration: none; color: #dc2626; }
.badge-both { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-vendor { background: #dbeafe; color: #1d4ed8; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-customer { background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.logo-preview { width: 36px; height: 36px; border-radius: 6px; object-fit: contain; border: 1px solid #e5e7eb; }
@media (max-width: 768px) {
    .action-toolbar { flex-direction: column; align-items: stretch; }
    .search-input-wrapper { max-width: 100%; }
    .filter-tabs { overflow-x: auto; flex-wrap: nowrap; }
}
</style>

<!-- Data Table -->
<div class="master-data-table">
    <?php if ($count > 0): ?>
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
            $row_num = $pagination['offset'];
            foreach ($items as $data): 
                $row_num++;
                $isSelf = ($com_id > 0 && $data['id'] == $com_id);
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
                            <?php if (!empty($data['name_th'])): ?>
                            <br><small class="item-desc"><?=htmlspecialchars($data['name_th'])?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><?=htmlspecialchars($data['contact'] ?? '') ?: '-'?></td>
                <td>
                    <?php if (!empty($data['email'])): ?>
                    <a href="mailto:<?=htmlspecialchars($data['email'])?>"><?=htmlspecialchars($data['email'])?></a>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><?=htmlspecialchars($data['phone'] ?? '') ?: '-'?></td>
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
                        <a href="index.php?page=company_form&id=<?=$data['id']?>"
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
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php 
    $qp = $query_params;
    unset($qp['p']);
    echo render_pagination($pagination, '?page=company', $qp, 'p'); 
    ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fa fa-building-o"></i>
        <h4><?=$xml->no_data ?? 'No Companies Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by adding your first customer or vendor'?></p>
        <a href="index.php?page=company_form" class="btn btn-add">
            <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Company'?>
        </a>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?\n\nThis will soft-delete the company and its addresses.')) {
        window.location.href = 'index.php?page=company_delete&id=' + id;
    }
}
</script>
