<?php
/**
 * Tax Invoice List View (MVC)
 * Replaces legacy compl-list2.php
 */
require_once("inc/pagination.php");
$search = $filters['search'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
?>
<style>
.taxinv-container { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 0 auto; }
.page-header-tax { background: linear-gradient(135deg, #059669, #047857); color: #fff; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(5,150,105,0.3); }
.page-header-tax h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.page-header-tax .record-count { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; }
</style>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="taxinv-container">
<div class="page-header-tax">
    <h2><i class="fa fa-file-text"></i> <?=$xml->taxinvoice?></h2>
    <span class="record-count"><i class="fa fa-list"></i> <?=$total_records?> <?=$xml->records ?? 'records'?></span>
</div>

<div style="background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden;">
    <div style="background:linear-gradient(135deg,#f8fafc,#f1f5f9); padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600;"><i class="fa fa-filter" style="color:#059669;"></i> <?=$xml->search ?? 'Search'?></div>
    <div style="padding:20px;">
        <form method="get"><input type="hidden" name="page" value="compl_list2">
            <div class="date-presets" style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;"><?= render_date_presets($date_preset, 'compl_list2') ?></div>
            <div class="row">
                <div class="col-sm-3" style="margin-bottom:12px;"><input type="text" class="form-control" name="search" placeholder="TAX#, Name..." value="<?=htmlspecialchars($search)?>" style="border-radius:10px;"></div>
                <div class="col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($date_from)?>" style="border-radius:10px;"></div>
                <div class="col-sm-2" style="margin-bottom:12px;"><input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($date_to)?>" style="border-radius:10px;"></div>
                <div class="col-sm-2" style="margin-bottom:12px;">
                    <select name="per_page" class="form-control" onchange="this.form.submit()" style="border-radius:10px;">
                        <?php foreach([10,20,50,100] as $pp): ?><option value="<?=$pp?>" <?=$per_page==$pp?'selected':''?>><?=$pp?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3" style="margin-bottom:12px;">
                    <button type="submit" class="btn btn-success" style="border-radius:10px;"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
                    <a href="?page=compl_list2" class="btn btn-default" style="border-radius:10px;"><i class="fa fa-refresh"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px;">
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:1px solid #e5e7eb; display:flex; align-items:center; gap:16px;">
        <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#16a34a;font-size:20px;"><i class="fa fa-arrow-up"></i></div>
        <div><h3 style="margin:0;font-size:28px;font-weight:700;"><?=$total_out?></h3><p style="margin:0;font-size:13px;color:#6b7280;"><?=$xml->taxinvoice?> <?=$xml->out ?? 'Out'?></p></div>
    </div>
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:1px solid #e5e7eb; display:flex; align-items:center; gap:16px;">
        <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#2563eb;font-size:20px;"><i class="fa fa-arrow-down"></i></div>
        <div><h3 style="margin:0;font-size:28px;font-weight:700;"><?=$total_in?></h3><p style="margin:0;font-size:13px;color:#6b7280;"><?=$xml->taxinvoice?> <?=$xml->in ?? 'In'?></p></div>
    </div>
</div>

<?php foreach(['out' => ['items' => $items_out, 'total' => $total_out, 'label' => $xml->customer, 'join' => 'cus_id'], 'in' => ['items' => $items_in, 'total' => $total_in, 'label' => ($xml->vender ?? 'Vendor'), 'join' => 'ven_id']] as $dir => $cfg): ?>
<div style="background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:600; background:<?=$dir=='out'?'linear-gradient(135deg,#f0fdf4,#dcfce7)':'linear-gradient(135deg,#eff6ff,#dbeafe)'?>; color:<?=$dir=='out'?'#166534':'#1e40af'?>;">
        <i class="fa fa-arrow-<?=$dir=='out'?'up':'down'?>"></i> <?=$xml->taxinvoice?> - <?=$dir=='out'?($xml->out ?? 'Out'):($xml->in ?? 'In')?> <span style="background:rgba(0,0,0,0.1);padding:4px 12px;border-radius:20px;font-size:13px;"><?=$cfg['total']?></span>
    </div>
    <div class="table-responsive"><table class="table" style="margin:0;"><thead><tr>
        <th width="120"><?=$xml->taxno?></th><th width="230"><?=$cfg['label']?></th><th class="hidden-xs" width="230"><?=$xml->description ?? 'Description'?></th>
        <th width="100"><?=$xml->createdate?></th><th class="hidden-xs" width="90"><?=$xml->status?></th><th width="120"><?=$xml->action ?? 'Actions'?></th>
    </tr></thead><tbody>
    <?php if(empty($cfg['items'])): ?>
    <tr><td colspan="6" style="text-align:center;padding:40px;color:#6b7280;"><i class="fa fa-inbox" style="font-size:32px;"></i><br><?=$xml->nodata ?? 'No data found'?></td></tr>
    <?php else: foreach($cfg['items'] as $row): $var=decodenum($row['status']); ?>
    <tr>
        <td style="font-weight:600;color:#059669;">TAX-<?=str_pad($row['texiv_rw'], 8, "0", STR_PAD_LEFT)?></td>
        <td style="font-weight:500;"><?=e($row['name_en'])?></td>
        <td class="hidden-xs"><?=e($row['name'])?></td>
        <td><?=e($row['texiv_create'])?></td>
        <td class="hidden-xs"><span style="background:#dcfce7;color:#166534;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;"><?=$xml->$var?></span></td>
        <td>
            <a href="taxiv.php?id=<?=e($row['id'])?>" target="_blank" style="display:inline-flex;align-items:center;padding:0 12px;height:32px;border-radius:8px;background:#dcfce7;color:#16a34a;font-weight:600;text-decoration:none;font-size:12px;">TAX</a>
            <?php if($dir=='out'): ?><a data-toggle="modal" href="model_mail.php?page=tax&id=<?=e($row['id'])?>" data-target=".bs-example-modal-lg" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#fef3c7;color:#d97706;text-decoration:none;"><i class="fa fa-envelope"></i></a><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
</div>
<?php endforeach; ?>

<?= render_pagination($pagination, '?page=compl_list2', $query_params) ?>
</div>
