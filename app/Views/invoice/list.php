<?php
/**
 * Invoice List View (MVC)
 * Replaces legacy compl-list.php
 */
require_once("inc/pagination.php");
$search = $filters['search'] ?? '';
$status_filter = $filters['status'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
?>
<style>
.invoice-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }
.page-header-inv { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(139,92,246,0.3); }
.page-header-inv h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-inv .record-count { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; }
.filter-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.filter-card .filter-header { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 10px; }
.filter-card .filter-body { padding: 20px; }
.filter-card .form-control { border-radius: 10px; border: 1px solid #e5e7eb; height: 44px; }
.filter-card .btn-primary { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.summary-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 16px; }
.summary-card .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.summary-card .icon.out { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #16a34a; }
.summary-card .icon.in { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }
.summary-card .info h3 { margin: 0; font-size: 28px; font-weight: 700; color: #1f2937; }
.summary-card .info p { margin: 0; font-size: 13px; color: #6b7280; }
.data-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden; }
.data-card .card-header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 15px; }
.data-card .card-header.out { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #166534; }
.data-card .card-header.in { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e40af; }
.data-card .card-header .badge { background: rgba(0,0,0,0.1); padding: 4px 12px; border-radius: 20px; font-size: 13px; }
.table-modern { margin-bottom: 0; }
.table-modern thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
.table-modern tbody tr:hover { background-color: #faf5ff; }
.table-modern tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.table-modern .inv-number { font-weight: 600; color: #8b5cf6; }
.table-modern .customer-name { font-weight: 500; color: #1f2937; }
.status-badge { display: inline-flex; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge.completed { background: #dcfce7; color: #166534; }
.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.overdue { background: #fee2e2; color: #991b1b; }
.status-badge.cancelled { background: #f3f4f6; color: #6b7280; }
.btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; margin-right: 4px; transition: all 0.2s; text-decoration: none; font-size: 12px; }
.btn-action-view { background: #eff6ff; color: #2563eb; }
.btn-action-view:hover { background: #2563eb; color: #fff; }
.btn-action-inv { background: #faf5ff; color: #7c3aed; font-weight: 600; width: auto; padding: 0 10px; }
.btn-action-inv:hover { background: #7c3aed; color: #fff; }
.btn-action-pay { background: #dcfce7; color: #16a34a; }
.btn-action-pay:hover { background: #16a34a; color: #fff; }
.btn-action-email { background: #fef3c7; color: #d97706; }
.btn-action-email:hover { background: #d97706; color: #fff; }
.empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
.empty-state i { font-size: 48px; margin-bottom: 16px; color: #d1d5db; }
.date-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.date-presets .btn { border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 500; }
/* Split Invoice Group Styles */
.split-group-row { cursor: pointer; background: #faf5ff; }
.split-group-row:hover { background: #f3e8ff !important; }
.split-group-row .toggle-icon { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; background: #ede9fe; color: #7c3aed; font-size: 10px; margin-right: 8px; transition: transform 0.2s; }
.split-group-row.expanded .toggle-icon { transform: rotate(90deg); }
.split-group-badge { display: inline-flex; align-items: center; gap: 4px; background: #ede9fe; color: #7c3aed; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-left: 8px; }
.split-sub-row { background: #fefce8; }
.split-sub-row td { border-left: 3px solid #8b5cf6 !important; padding-left: 20px !important; }
.split-type-badge { display: inline-flex; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.split-type-badge.material { background: #dbeafe; color: #1e40af; }
.split-type-badge.labour { background: #fef3c7; color: #92400e; }
</style>

<div class="invoice-container">
<div class="page-header-inv">
    <h2><i class="fa fa-file-text-o"></i> <?=$xml->invoice?></h2>
    <span class="record-count"><i class="fa fa-list"></i> <?=$total_records?> <?=$xml->records ?? 'records'?></span>
</div>

<div class="filter-card">
    <div class="filter-header"><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></div>
    <div class="filter-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="compl_list">
            <div class="date-presets"><?= render_date_presets($date_preset, 'compl_list') ?></div>
            <div class="row">
                <div class="col-xs-12 col-sm-3" style="margin-bottom:12px;">
                    <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> Invoice#, Name..." value="<?=htmlspecialchars($search)?>">
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;">
                    <select name="status" class="form-control">
                        <option value=""><?=$xml->all ?? 'All Status'?></option>
                        <option value="pending" <?=$status_filter=='pending'?'selected':''?>><?=$xml->pending ?? 'Pending'?></option>
                        <option value="completed" <?=$status_filter=='completed'?'selected':''?>><?=$xml->completed ?? 'Completed'?></option>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>"></div>
                <div class="col-xs-6 col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>"></div>
                <div class="col-xs-6 col-sm-3" style="margin-bottom:12px;">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                    <a href="?page=compl_list" class="btn btn-default"><i class="fa fa-refresh"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="summary-cards">
    <div class="summary-card"><div class="icon out"><i class="fa fa-arrow-up"></i></div><div class="info"><h3><?=$total_out?></h3><p><?=$xml->invoice?> <?=$xml->out ?? 'Out'?></p></div></div>
    <div class="summary-card"><div class="icon in"><i class="fa fa-arrow-down"></i></div><div class="info"><h3><?=$total_in?></h3><p><?=$xml->invoice?> <?=$xml->in ?? 'In'?></p></div></div>
</div>

<!-- OUT Table -->
<div class="data-card">
    <div class="card-header out"><i class="fa fa-arrow-up"></i> <?=$xml->invoice?> - <?=$xml->out ?? 'Out'?> <span class="badge"><?=$total_out?></span></div>
    <div class="table-responsive"><table class="table table-modern"><thead><tr>
        <th width="120"><?=$xml->inno?></th><th width="230"><?=$xml->customer?></th>
        <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th><th width="100"><?=$xml->duedate?></th>
        <th class="hidden-xs" width="90"><?=$xml->status?></th><th width="140"><?=$xml->action ?? 'Actions'?></th>
    </tr></thead><tbody>
    <?php if(empty($items_out)): ?>
    <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No data found'?></h4></div></td></tr>
    <?php else:
        $renderedGroups = [];
        foreach($items_out as $row):
        // Split group handling: render group row for first occurrence, skip siblings
        $splitGroupId = $row['split_group_id'] ?? null;
        if ($splitGroupId) {
            if (isset($renderedGroups[$splitGroupId])) continue; // skip, already rendered as sub-row
            $renderedGroups[$splitGroupId] = true;
            // Find all siblings in this group
            $siblings = array_filter($items_out, function($r) use ($splitGroupId) { return ($r['split_group_id'] ?? null) == $splitGroupId; });
            $sibCount = count($siblings);
    ?>
    <tr class="split-group-row" onclick="toggleSplitGroup(this, <?=intval($splitGroupId)?>)">
        <td class="inv-number"><span class="toggle-icon"><i class="fa fa-chevron-right"></i></span>INV-<?=e(explode('/', $row['po_tax'] ?? $row['tax'])[0])?><span class="split-group-badge"><i class="fa fa-clone"></i> <?=$sibCount?></span></td>
        <td class="customer-name"><?=e($row['name_en'])?></td>
        <td class="hidden-xs"><?=e($row['name'])?></td>
        <td><?=e($row['valid_pay'])?></td>
        <td class="hidden-xs"><span class="status-badge pending"><?=$xml->split ?? 'Split'?></span></td>
        <td>
            <a href="index.php?page=pdf_invoice&id=<?=e($row['id'])?>" target="_blank" class="btn-action btn-action-inv">IV</a>
        </td>
    </tr>
    <?php foreach ($siblings as $sib):
        if(($sib['status_iv'] ?? '')=="2" && $sib['status']=="4"){ $statusiv="void"; $sc="cancelled"; }
        elseif($sib['status']=="4" && ($sib['valid_pay'] ?? '') < date("d-m-Y")){ $statusiv="overdue"; $sc="overdue"; }
        else { $statusiv=decodenum($sib['status']); $sc=$sib['status']=='5'?'completed':'pending'; }
        $splitLabel = ($sib['split_type'] ?? 'full') === 'material' ? 'Material' : 'Labour';
        $splitClass = ($sib['split_type'] ?? 'full') === 'material' ? 'material' : 'labour';
    ?>
    <tr class="split-sub-row" data-split-parent="<?=intval($splitGroupId)?>" style="display:none;">
        <td class="inv-number" style="padding-left:40px !important;">INV-<?=e($sib['po_tax'] ?? $sib['tax'])?> <span class="split-type-badge <?=$splitClass?>"><?=$splitLabel?></span></td>
        <td class="customer-name"><?=e($sib['name_en'])?></td>
        <td class="hidden-xs"><?=e($sib['name'])?></td>
        <td><?=e($sib['valid_pay'])?></td>
        <td class="hidden-xs"><span class="status-badge <?=$sc?>"><?=$xml->$statusiv?></span></td>
        <td>
            <?php if($sib['status']!="5"): ?><a href="index.php?page=compl_view&id=<?=e($sib['id'])?>" class="btn-action btn-action-view" title="View"><i class="fa fa-eye"></i></a><?php endif; ?>
            <a href="index.php?page=pdf_invoice&id=<?=e($sib['id'])?>" target="_blank" class="btn-action btn-action-inv">IV</a>
            <?php if(($sib['payment_status'] ?? 'pending')!=='paid'): ?>
            <a href="index.php?page=inv_checkout&id=<?=e($sib['id'])?>" target="_blank" class="btn-action btn-action-pay"><i class="fa fa-credit-card"></i></a>
            <?php else: ?><span class="btn-action btn-action-pay" style="cursor:default;"><i class="fa fa-check"></i></span><?php endif; ?>
            <a data-toggle="modal" href="index.php?page=ajax_mail&type=inv&id=<?=e($sib['id'])?>" data-target=".bs-example-modal-lg" class="btn-action btn-action-email"><i class="fa fa-envelope"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php } else {
        // Normal (non-split) invoice row
        if(($row['status_iv'] ?? '')=="2" && $row['status']=="4"){ $statusiv="void"; $sc="cancelled"; }
        elseif($row['status']=="4" && ($row['valid_pay'] ?? '') < date("d-m-Y")){ $statusiv="overdue"; $sc="overdue"; }
        else { $statusiv=decodenum($row['status']); $sc=$row['status']=='5'?'completed':'pending'; }
    ?>
    <tr>
        <td class="inv-number">INV-<?=e($row['tax'])?></td>
        <td class="customer-name"><?=e($row['name_en'])?></td>
        <td class="hidden-xs"><?=e($row['name'])?></td>
        <td><?=e($row['valid_pay'])?></td>
        <td class="hidden-xs"><span class="status-badge <?=$sc?>"><?=$xml->$statusiv?></span></td>
        <td>
            <?php if($row['status']!="5"): ?><a href="index.php?page=compl_view&id=<?=e($row['id'])?>" class="btn-action btn-action-view" title="View"><i class="fa fa-eye"></i></a><?php endif; ?>
            <a href="index.php?page=pdf_invoice&id=<?=e($row['id'])?>" target="_blank" class="btn-action btn-action-inv">IV</a>
            <?php if(($row['payment_status'] ?? 'pending')!=='paid'): ?>
            <a href="index.php?page=inv_checkout&id=<?=e($row['id'])?>" target="_blank" class="btn-action btn-action-pay"><i class="fa fa-credit-card"></i></a>
            <?php else: ?><span class="btn-action btn-action-pay" style="cursor:default;"><i class="fa fa-check"></i></span><?php endif; ?>
            <a data-toggle="modal" href="index.php?page=ajax_mail&type=inv&id=<?=e($row['id'])?>" data-target=".bs-example-modal-lg" class="btn-action btn-action-email"><i class="fa fa-envelope"></i></a>
        </td>
    </tr>
    <?php } endforeach; endif; ?>
    </tbody></table></div>
</div>

<!-- IN Table -->
<div class="data-card">
    <div class="card-header in"><i class="fa fa-arrow-down"></i> <?=$xml->invoice?> - <?=$xml->in ?? 'In'?> <span class="badge"><?=$total_in?></span></div>
    <div class="table-responsive"><table class="table table-modern"><thead><tr>
        <th width="120"><?=$xml->inno?></th><th width="230"><?=$xml->vender ?? 'Vendor'?></th>
        <th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th><th width="100"><?=$xml->duedate?></th>
        <th class="hidden-xs" width="90"><?=$xml->status?></th><th width="140"><?=$xml->action ?? 'Actions'?></th>
    </tr></thead><tbody>
    <?php if(empty($items_in)): ?>
    <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No data found'?></h4></div></td></tr>
    <?php else:
        $renderedGroupsIn = [];
        foreach($items_in as $row):
        $splitGroupId = $row['split_group_id'] ?? null;
        if ($splitGroupId) {
            if (isset($renderedGroupsIn[$splitGroupId])) continue;
            $renderedGroupsIn[$splitGroupId] = true;
            $siblings = array_filter($items_in, function($r) use ($splitGroupId) { return ($r['split_group_id'] ?? null) == $splitGroupId; });
            $sibCount = count($siblings);
    ?>
    <tr class="split-group-row" onclick="toggleSplitGroup(this, <?=intval($splitGroupId)?>)">
        <td class="inv-number"><span class="toggle-icon"><i class="fa fa-chevron-right"></i></span>INV-<?=e(explode('/', $row['po_tax'] ?? $row['tax'])[0])?><span class="split-group-badge"><i class="fa fa-clone"></i> <?=$sibCount?></span></td>
        <td class="customer-name"><?=e($row['name_en'])?></td>
        <td class="hidden-xs"><?=e($row['name'])?></td>
        <td><?=e($row['valid_pay'])?></td>
        <td class="hidden-xs"><span class="status-badge pending"><?=$xml->split ?? 'Split'?></span></td>
        <td></td>
    </tr>
    <?php foreach ($siblings as $sib):
        $var=decodenum($sib['status']); $sc=$sib['status']=='5'?'completed':'pending';
        $splitLabel = ($sib['split_type'] ?? 'full') === 'material' ? 'Material' : 'Labour';
        $splitClass = ($sib['split_type'] ?? 'full') === 'material' ? 'material' : 'labour';
    ?>
    <tr class="split-sub-row" data-split-parent="<?=intval($splitGroupId)?>" style="display:none;">
        <td class="inv-number" style="padding-left:40px !important;">INV-<?=e($sib['po_tax'] ?? $sib['tax'])?> <span class="split-type-badge <?=$splitClass?>"><?=$splitLabel?></span></td>
        <td class="customer-name"><?=e($sib['name_en'])?></td>
        <td class="hidden-xs"><?=e($sib['name'])?></td>
        <td><?=e($sib['valid_pay'])?></td>
        <td class="hidden-xs"><span class="status-badge <?=$sc?>"><?=$xml->$var?></span></td>
        <td>
            <?php if($sib['status']!="5"): ?><a href="index.php?page=compl_view&id=<?=e($sib['id'])?>" class="btn-action btn-action-view"><i class="fa fa-eye"></i></a><?php endif; ?>
            <a href="index.php?page=pdf_invoice&id=<?=e($sib['id'])?>" target="_blank" class="btn-action btn-action-inv">IV</a>
            <?php if(($sib['payment_status'] ?? 'pending')!=='paid'): ?>
            <a href="index.php?page=inv_checkout&id=<?=e($sib['id'])?>" target="_blank" class="btn-action btn-action-pay"><i class="fa fa-credit-card"></i></a>
            <?php else: ?><span class="btn-action btn-action-pay" style="cursor:default;"><i class="fa fa-check"></i></span><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php } else {
        $var=decodenum($row['status']); $sc=$row['status']=='5'?'completed':'pending';
    ?>
    <tr>
        <td class="inv-number">INV-<?=e($row['tax'])?></td>
        <td class="customer-name"><?=e($row['name_en'])?></td>
        <td class="hidden-xs"><?=e($row['name'])?></td>
        <td><?=e($row['valid_pay'])?></td>
        <td class="hidden-xs"><span class="status-badge <?=$sc?>"><?=$xml->$var?></span></td>
        <td>
            <?php if($row['status']!="5"): ?><a href="index.php?page=compl_view&id=<?=e($row['id'])?>" class="btn-action btn-action-view"><i class="fa fa-eye"></i></a><?php endif; ?>
            <a href="index.php?page=pdf_invoice&id=<?=e($row['id'])?>" target="_blank" class="btn-action btn-action-inv">IV</a>
            <?php if(($row['payment_status'] ?? 'pending')!=='paid'): ?>
            <a href="index.php?page=inv_checkout&id=<?=e($row['id'])?>" target="_blank" class="btn-action btn-action-pay"><i class="fa fa-credit-card"></i></a>
            <?php else: ?><span class="btn-action btn-action-pay" style="cursor:default;"><i class="fa fa-check"></i></span><?php endif; ?>
        </td>
    </tr>
    <?php } endforeach; endif; ?>
    </tbody></table></div>
</div>

<?= render_pagination($pagination, '?page=compl_list', $query_params) ?>
</div>

<script>
function toggleSplitGroup(row, groupId) {
    var isExpanded = row.classList.contains('expanded');
    var subRows = document.querySelectorAll('.split-sub-row[data-split-parent="' + groupId + '"]');
    if (isExpanded) {
        row.classList.remove('expanded');
        for (var i = 0; i < subRows.length; i++) subRows[i].style.display = 'none';
    } else {
        row.classList.add('expanded');
        for (var i = 0; i < subRows.length; i++) subRows[i].style.display = 'table-row';
    }
}
</script>
