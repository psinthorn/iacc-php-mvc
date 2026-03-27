<?php
/**
 * PR List View
 * Variables: $items_out, $items_in, $total_out, $total_in, $total_records, $pagination, $filters, $per_page, $query_params
 */
require_once __DIR__ . '/../../../inc/pagination.php';
$statusLabels = ['0'=>['Pending','warning'],'1'=>['Quotation','info'],'2'=>['Confirmed','primary'],'3'=>['Delivered','success'],'4'=>['Invoiced','danger'],'5'=>['Completed','success']];
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text-o"></i> <?=$xml->pr ?? 'Purchase Requests'?></h2>
    <div>
        <a href="index.php?page=pr_create" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create PR (Vendor)</a>
        <a href="index.php?page=pr_make" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Create PR (Customer)</a>
    </div>
</div>

<!-- Filters -->
<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%">
        <input type="hidden" name="page" value="pr_list">
        <div class="search-input-wrapper" style="flex:1;min-width:200px">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($filters['search'])?>" placeholder="Search PR..." class="search-input">
        </div>
        <?php render_date_presets($filters['date_from'] ?? '', $filters['date_to'] ?? ''); ?>
        <select name="status" class="form-control input-sm" style="width:auto">
            <option value="">All Status</option>
            <?php foreach($statusLabels as $k=>$v): ?>
                <option value="<?=$k?>" <?=($filters['status']??'')===$k?'selected':''?>><?=$v[0]?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card primary"><div class="stat-value"><?=$total_records?></div><div class="stat-label">Total</div></div>
    <div class="stat-card info"><div class="stat-value"><?=$total_out?></div><div class="stat-label">Outgoing</div></div>
    <div class="stat-card success"><div class="stat-value"><?=$total_in?></div><div class="stat-label">Incoming</div></div>
</div>

<!-- OUT Table -->
<div class="panel panel-default">
    <div class="panel-heading"><strong><i class="fa fa-arrow-up"></i> Outgoing PR</strong></div>
    <table class="table table-striped table-hover">
        <thead><tr><th>#</th><th>Customer</th><th>Description</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items_out)): ?><tr><td colspan="6" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items_out as $row): $st=$statusLabels[$row['status']]??['Unknown','default']; ?>
            <tr<?=$row['cancel']=='1'?' style="opacity:0.5"':''?>>
                <td><?=e($row['id'])?></td>
                <td><?=e($row['name_en'])?></td>
                <td><?=e($row['name'])?></td>
                <td><?=e($row['createdate'])?></td>
                <td><span class="label label-<?=$st[1]?>"><?=$st[0]?></span></td>
                <td>
                    <?php if($row['cancel']!='1' && $row['status']=='0'): ?>
                        <a href="index.php?page=po_make&id=<?=$row['id']?>" class="btn btn-xs btn-info" title="Make PO"><i class="fa fa-file"></i></a>
                        <a href="index.php?page=pr_store&method=D&id=<?=$row['id']?>&<?=csrf_field()?>" class="btn btn-xs btn-danger" onclick="return confirm('Cancel?')"><i class="fa fa-times"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- IN Table -->
<div class="panel panel-default">
    <div class="panel-heading"><strong><i class="fa fa-arrow-down"></i> Incoming PR</strong></div>
    <table class="table table-striped table-hover">
        <thead><tr><th>#</th><th>Vendor</th><th>Description</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items_in)): ?><tr><td colspan="6" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items_in as $row): $st=$statusLabels[$row['status']]??['Unknown','default']; ?>
            <tr<?=$row['cancel']=='1'?' style="opacity:0.5"':''?>>
                <td><?=e($row['id'])?></td>
                <td><?=e($row['name_en'])?></td>
                <td><?=e($row['name'])?></td>
                <td><?=e($row['createdate'])?></td>
                <td><span class="label label-<?=$st[1]?>"><?=$st[0]?></span></td>
                <td>
                    <?php if($row['cancel']!='1' && $row['status']=='0'): ?>
                        <a href="index.php?page=po_make&id=<?=$row['id']?>" class="btn btn-xs btn-info"><i class="fa fa-file"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php render_pagination($pagination, array_merge($query_params, ['page'=>'pr_list'])); ?>
</div>
