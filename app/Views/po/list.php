<?php
/**
 * PO List View
 * Variables: $items_out, $items_in, $total_out, $total_in, $total_records, $pagination, $filters, $per_page, $query_params
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text"></i> <?=$xml->po ?? 'Purchase Orders'?></h2>
</div>

<!-- Filters -->
<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%">
        <input type="hidden" name="page" value="po_list">
        <div class="search-input-wrapper" style="flex:1;min-width:200px">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($filters['search'])?>" placeholder="Search PO..." class="search-input">
        </div>
        <?php render_date_presets($filters['date_from'] ?? '', $filters['date_to'] ?? ''); ?>
        <select name="per_page" class="form-control input-sm" style="width:80px" onchange="this.form.submit()">
            <?php foreach([10,20,50,100] as $pp): ?>
                <option value="<?=$pp?>" <?=$per_page==$pp?'selected':''?>><?=$pp?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card primary"><div class="stat-value"><?=$total_records?></div><div class="stat-label">Total POs</div></div>
    <div class="stat-card info"><div class="stat-value"><?=$total_out?></div><div class="stat-label">Outgoing</div></div>
    <div class="stat-card success"><div class="stat-value"><?=$total_in?></div><div class="stat-label">Incoming</div></div>
</div>

<?php
$statusMap = ['0'=>['Pending','warning'],'1'=>['Quotation','info'],'2'=>['Confirmed','primary'],'3'=>['Delivered','success'],'4'=>['Invoiced','danger'],'5'=>['Completed','success']];
function renderPOTable($items, $title, $icon, $xml) {
    global $statusMap;
?>
<div class="panel panel-default">
    <div class="panel-heading"><strong><i class="fa fa-<?=$icon?>"></i> <?=$title?></strong></div>
    <table class="table table-striped table-hover">
        <thead><tr><th>PO#</th><th>Company</th><th>Description</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items)): ?><tr><td colspan="6" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items as $row): $st=$statusMap[$row['status']]??['Unknown','default']; ?>
            <tr<?=$row['cancel']=='1'?' style="opacity:0.5"':''?>>
                <td><strong><?=e($row['tax'])?></strong></td>
                <td><?=e($row['name_en'])?></td>
                <td><?=e($row['name'])?></td>
                <td><?=e($row['valid_pay'])?></td>
                <td><span class="label label-<?=$st[1]?>"><?=$st[0]?></span></td>
                <td>
                    <a href="index.php?page=po_view&id=<?=$row['id']?>" class="btn btn-xs btn-info" title="View"><i class="fa fa-eye"></i></a>
                    <?php if($row['cancel']!='1'): ?>
                        <?php if($row['status']=='1'): ?>
                            <a href="index.php?page=po_edit&id=<?=$row['id']?>" class="btn btn-xs btn-warning" title="Edit"><i class="fa fa-pencil"></i></a>
                        <?php endif; ?>
                        <?php if($row['status']=='2'): ?>
                            <a href="index.php?page=po_deliv&id=<?=$row['id']?>&action=c" class="btn btn-xs btn-success" title="Deliver"><i class="fa fa-truck"></i></a>
                        <?php endif; ?>
                        <a href="index.php?page=po_store&method=D&id=<?=$row['id']?>&csrf_token=<?=csrf_token()?>" class="btn btn-xs btn-danger" onclick="return confirm('Cancel this PO?')"><i class="fa fa-times"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php } ?>

<?php renderPOTable($items_out, 'Outgoing PO', 'arrow-up', $xml); ?>
<?php renderPOTable($items_in, 'Incoming PO', 'arrow-down', $xml); ?>
<?php render_pagination($pagination, array_merge($query_params, ['page'=>'po_list'])); ?>
</div>
