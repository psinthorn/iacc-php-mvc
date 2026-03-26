<?php
/**
 * Quotation List View (MVC)
 * Replaces legacy qa-list.php
 */
require_once("inc/pagination.php");
$search = $filters['search'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.qa-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }
.qa-header { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(245,158,11,0.25); }
.qa-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.qa-filter { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }
.qa-filter .hdr { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; font-size: 14px; }
.qa-filter .bdy { padding: 20px; }
.qa-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }
.qa-card .sec-hdr { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.qa-card .table { margin: 0; font-size: 13px; }
.qa-card .table thead th { background: #f9fafb; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e5e7eb; border-top: none; }
.qa-card .table tbody td { padding: 14px 16px; border-color: #e5e7eb; vertical-align: middle; }
.qa-card .table tbody tr:hover { background: rgba(245,158,11,0.03); }
.action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; text-decoration: none; transition: all 0.2s; margin-right: 4px; }
.action-btn.primary { background: rgba(102,126,234,0.1); color: #667eea; }
.action-btn.primary:hover { background: #667eea; color: white; }
.action-btn.success { background: rgba(16,185,129,0.1); color: #10b981; }
.action-btn.success:hover { background: #10b981; color: white; }
.action-btn.danger { background: rgba(239,68,68,0.1); color: #ef4444; }
.action-btn.danger:hover { background: #ef4444; color: white; }
.action-btn.default { background: rgba(245,158,11,0.1); color: #f59e0b; }
.action-btn.default:hover { background: #f59e0b; color: white; }
.status-badge { display: inline-flex; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-badge.active { background: #fef3c7; color: #d97706; }
.status-badge.cancelled { background: #fee2e2; color: #ef4444; }
.mail-btn-wrapper { position: relative; display: inline-block; }
.mail-badge { position: absolute; top: -6px; right: -6px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; min-width: 18px; height: 18px; border-radius: 9px; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
.mail-badge.zero { background: linear-gradient(135deg, #9ca3af, #6b7280); }
</style>

<div class="qa-wrapper">
<div class="qa-header">
    <h2><i class="fa fa-file-text-o"></i> <?=$xml->quotation?></h2>
    <div style="margin-top:6px; opacity:0.9; font-size:14px;">Manage and track all quotations</div>
</div>

<div class="qa-filter">
    <div class="hdr"><i class="fa fa-filter" style="color:#f59e0b;"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></div>
    <div class="bdy">
        <form method="get" class="form-inline"><input type="hidden" name="page" value="qa_list">
            <div class="form-group" style="margin-right:12px;margin-bottom:10px;">
                <input type="text" class="form-control" name="search" placeholder="QUO#, Name, Customer..." value="<?=htmlspecialchars($search)?>" style="width:250px;border-radius:8px;">
            </div>
            <div class="form-group" style="margin-right:12px;margin-bottom:10px;">
                <label style="margin-right:6px;color:#6b7280;"><?=$xml->from ?? 'From'?>:</label>
                <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>" style="border-radius:8px;">
            </div>
            <div class="form-group" style="margin-right:12px;margin-bottom:10px;">
                <label style="margin-right:6px;color:#6b7280;"><?=$xml->to ?? 'To'?>:</label>
                <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>" style="border-radius:8px;">
            </div>
            <button type="submit" class="btn btn-warning" style="margin-bottom:10px;border-radius:8px;"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=qa_list" class="btn btn-default" style="margin-bottom:10px;margin-left:8px;border-radius:8px;"><i class="fa fa-refresh"></i></a>
        </form>
    </div>
</div>

<!-- Quotation Out -->
<div class="qa-card">
    <div class="sec-hdr"><i class="fa fa-arrow-up text-success"></i> <?=$xml->quotation?> - <?=$xml->out ?? 'Out'?></div>
    <table class="table table-hover"><thead><tr>
        <th width="120"><?=$xml->quono?></th><th width="230"><?=$xml->customer?></th><th width="150"><?=$xml->price?></th>
        <th width="100"><?=$xml->duedate?></th><th width="90"><?=$xml->status?></th><th width="130"></th>
    </tr></thead><tbody>
    <?php if(empty($items_out)): ?>
    <tr><td colspan="6" style="text-align:center;padding:40px;color:#6b7280;"><i class="fa fa-inbox" style="font-size:24px;"></i><br><?=$xml->nodata ?? 'No data found'?></td></tr>
    <?php else: foreach($items_out as $d):
        $pg = ($d['status']=='2') ? 'po_deliv' : 'po_edit';
        $var = decodenum($d['status']);
        $isCancelled = ($d['cancel'] ?? '') == '1';
        $mailcount = intval($d['mailcount'] ?? 0);
        $badgeClass = $mailcount == 0 ? 'mail-badge zero' : 'mail-badge';
    ?>
    <tr>
        <td>QUO-<?=htmlspecialchars($d['tax'])?></td>
        <td><?=htmlspecialchars($d['name_en'])?></td>
        <td><?=number_format($d['subtotal'],2)?> / <?=number_format($d['grandtotal'],2)?></td>
        <td><?=htmlspecialchars($d['valid_pay'])?></td>
        <td><span class="status-badge <?=$isCancelled?'cancelled':'active'?>"><?=$xml->$var?></span></td>
        <td>
            <a class="action-btn primary" href="index.php?page=<?=$pg?>&id=<?=$d['id']?>&action=m" title="Edit"><i class="fa fa-pencil"></i></a>
            <a class="action-btn success" href="index.php?page=po_view&id=<?=$d['id']?>" title="Confirm"><i class="fa fa-check"></i></a>
            <a class="action-btn default" href="exp.php?id=<?=$d['id']?>" target="blank" title="View"><i class="fa fa-search"></i></a>
            <span class="mail-btn-wrapper"><a class="action-btn default" data-toggle="modal" href="model_mail.php?page=exp&id=<?=$d['id']?>" data-target=".bs-example-modal-lg"><i class="fa fa-envelope"></i></a><span class="<?=$badgeClass?>"><?=$mailcount?></span></span>
            <?php if(!$isCancelled): ?><a class="action-btn danger" onClick="return Conf(this)" href="index.php?page=po_store&method=D&id=<?=$d['id']?>&<?=csrf_field()?>"><i class="fa fa-trash"></i></a><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody></table>
    <?= render_pagination($pagination_out, '?page=qa_list', $query_params, 'pg_out') ?>
</div>

<!-- Quotation In -->
<div class="qa-card">
    <div class="sec-hdr"><i class="fa fa-arrow-down text-primary"></i> <?=$xml->quotation?> - <?=$xml->in ?? 'In'?></div>
    <table class="table table-hover"><thead><tr>
        <th width="120"><?=$xml->quono?></th><th width="230">Vendor</th><th width="150"><?=$xml->price?></th>
        <th width="100"><?=$xml->duedate?></th><th width="90"><?=$xml->status?></th><th width="130"></th>
    </tr></thead><tbody>
    <?php if(empty($items_in)): ?>
    <tr><td colspan="6" style="text-align:center;padding:40px;color:#6b7280;"><i class="fa fa-inbox" style="font-size:24px;"></i><br><?=$xml->nodata ?? 'No data found'?></td></tr>
    <?php else: foreach($items_in as $d):
        $pg = ($d['status']=='2') ? 'po_deliv' : 'po_edit';
        $var = decodenum($d['status']);
        $isCancelled = ($d['cancel'] ?? '') == '1';
    ?>
    <tr>
        <td>QUO-<?=htmlspecialchars($d['tax'])?></td>
        <td><?=htmlspecialchars($d['name_en'])?></td>
        <td><?=number_format($d['subtotal'],2)?> / <?=number_format($d['grandtotal'],2)?></td>
        <td><?=htmlspecialchars($d['valid_pay'])?></td>
        <td><span class="status-badge <?=$isCancelled?'cancelled':'active'?>"><?=$xml->$var?></span></td>
        <td>
            <a class="action-btn success" href="index.php?page=po_view&id=<?=$d['id']?>" title="Confirm"><i class="fa fa-check"></i></a>
            <a class="action-btn default" href="exp.php?id=<?=$d['id']?>" target="blank" title="View"><i class="fa fa-search"></i></a>
            <?php if(!$isCancelled): ?><a class="action-btn danger" onClick="return Conf(this)" href="index.php?page=po_store&method=D&id=<?=$d['id']?>&<?=csrf_field()?>"><i class="fa fa-trash"></i></a><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody></table>
    <?= render_pagination($pagination_in, '?page=qa_list', $query_params, 'pg_in') ?>
</div>
</div>
<div id="fetch_state"></div>
