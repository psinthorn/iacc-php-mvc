<?php
/**
 * Audit Log List View — MVC version
 * Variables: $logs, $actionCounts, $filterUser, $filterAction, $filterEntity, $filterDateFrom, $filterDateTo
 */
$actions  = ['login','logout','login_failed','create','update','delete','view','export'];
$entities = ['session','company','user','po','pr','invoice','payment','product','report'];
$actionStyles = [
    'login'=>['icon'=>'sign-in','bg'=>'#10b981'],'logout'=>['icon'=>'sign-out','bg'=>'#6b7280'],
    'login_failed'=>['icon'=>'times-circle','bg'=>'#ef4444'],'create'=>['icon'=>'plus','bg'=>'#3b82f6'],
    'update'=>['icon'=>'pencil','bg'=>'#f59e0b'],'delete'=>['icon'=>'trash','bg'=>'#ef4444'],
    'view'=>['icon'=>'eye','bg'=>'#8b5cf6'],'export'=>['icon'=>'download','bg'=>'#06b6d4'],
];
$displayActions = ['login','logout','create','update','delete','login_failed'];
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/master-data.css">
<style>
.audit-container { max-width:1400px; margin:0 auto; padding:20px; font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; }
.audit-header { background:linear-gradient(135deg,#4f46e5,#4338ca); color:#fff; padding:28px 32px; border-radius:16px; margin-bottom:24px; box-shadow:0 10px 40px rgba(79,70,229,.3); display:flex; align-items:center; justify-content:space-between; }
.audit-header .header-content { display:flex; align-items:center; gap:16px; }
.audit-header .header-icon { width:56px; height:56px; background:rgba(255,255,255,.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
.audit-title { font-size:26px; font-weight:700; margin:0; }
.audit-subtitle { margin:4px 0 0; opacity:.9; font-size:14px; }
.audit-badge { background:rgba(255,255,255,.2); color:#fff; padding:8px 18px; border-radius:20px; font-size:14px; font-weight:600; border:2px solid rgba(255,255,255,.3); }
.stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card { background:#fff; border-radius:16px; padding:20px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,.08); border:1px solid #e5e7eb; }
.stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; font-size:18px; }
.stat-value { font-size:28px; font-weight:700; color:#1f2937; }
.stat-label { font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; margin-top:4px; font-weight:600; }
.filter-card { background:#fff; border-radius:16px; padding:24px; margin-bottom:24px; box-shadow:0 4px 20px rgba(0,0,0,.08); border:1px solid #e5e7eb; }
.filter-grid { display:grid; grid-template-columns:repeat(5,1fr) auto; gap:16px; align-items:end; }
.filter-group label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
.filter-group input,.filter-group select { width:100%; height:46px; padding:0 16px; border:2px solid #e5e7eb; border-radius:10px; font-size:14px; background:#fff; box-sizing:border-box; }
.filter-group input:focus,.filter-group select:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 4px rgba(79,70,229,.1); }
.filter-actions { display:flex; gap:10px; }
.btn-filter { padding:12px 24px; border-radius:10px; font-size:14px; font-weight:600; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; }
.btn-filter-primary { background:linear-gradient(135deg,#4f46e5,#4338ca); color:#fff; }
.btn-filter-secondary { background:#fff; color:#374151; border:2px solid #e5e7eb; }
.timeline { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); border:1px solid #e5e7eb; overflow:hidden; }
.timeline-header { padding:20px 24px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#f8fafc,#f1f5f9); }
.timeline-header h3 { margin:0; font-size:16px; font-weight:600; color:#1f2937; display:flex; align-items:center; gap:10px; }
.timeline-header h3 i { color:#4f46e5; }
.timeline-body { max-height:600px; overflow-y:auto; }
.timeline-item { display:grid; grid-template-columns:140px 48px 1fr auto; gap:0; align-items:start; padding:18px 24px; border-bottom:1px solid #f3f4f6; transition:background .2s; }
.timeline-item:hover { background:#f9fafb; }
.timeline-time { font-size:12px; color:#6b7280; padding-top:4px; }
.timeline-time .date { font-weight:600; color:#374151; }
.timeline-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; color:#fff; }
.timeline-content { padding-left:16px; }
.timeline-action { font-weight:600; color:#1f2937; font-size:14px; margin-bottom:4px; display:flex; align-items:center; gap:8px; }
.timeline-entity { display:inline-flex; align-items:center; gap:6px; background:#f3f4f6; padding:3px 10px; border-radius:6px; font-size:12px; color:#4b5563; font-weight:500; }
.timeline-user { font-size:13px; color:#6b7280; margin-top:6px; display:flex; align-items:center; gap:6px; }
.timeline-user i { color:#9ca3af; }
.timeline-meta { text-align:right; padding-top:4px; }
.timeline-ip { font-family:'SF Mono','Menlo',monospace; font-size:11px; color:#9ca3af; background:#f3f4f6; padding:4px 8px; border-radius:6px; }
.timeline-details { margin-top:12px; }
.details-toggle { font-size:12px; color:#3b82f6; cursor:pointer; display:inline-flex; align-items:center; gap:4px; }
.details-content { display:none; margin-top:10px; }
.details-content.show { display:block; }
.diff-box { border-radius:8px; padding:12px; font-family:'SF Mono','Menlo',monospace; font-size:12px; margin-bottom:8px; white-space:pre-wrap; word-break:break-all; }
.diff-old { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.diff-new { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.diff-label { font-weight:600; margin-bottom:6px; display:block; font-family:-apple-system,sans-serif; }
.empty-state { padding:80px 24px; text-align:center; }
.empty-icon { width:80px; height:80px; background:#f3f4f6; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
.empty-icon i { font-size:32px; color:#9ca3af; }
.empty-title { font-size:18px; font-weight:600; color:#374151; margin-bottom:8px; }
.empty-text { color:#6b7280; font-size:14px; }
@media(max-width:1200px){ .filter-grid{grid-template-columns:repeat(3,1fr);} .filter-actions{grid-column:span 3;} }
@media(max-width:768px){ .timeline-item{grid-template-columns:1fr;gap:12px;} .timeline-meta{text-align:left;} .filter-grid{grid-template-columns:1fr 1fr;} .filter-actions{grid-column:span 2;} }
</style>

<div class="audit-container">
    <div class="audit-header">
        <div class="header-content">
            <div class="header-icon"><i class="fa fa-shield"></i></div>
            <div>
                <h1 class="audit-title">Audit Log</h1>
                <p class="audit-subtitle">Track all system activities and security events</p>
            </div>
        </div>
        <span class="audit-badge"><i class="fa fa-database"></i> <?=count($logs)?> records</span>
    </div>

    <div class="stats-row">
        <?php foreach ($displayActions as $a):
            $s = $actionStyles[$a] ?? ['icon'=>'circle','bg'=>'#6b7280'];
            $c = $actionCounts[$a] ?? 0;
        ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:<?=$s['bg']?>20;color:<?=$s['bg']?>;"><i class="fa fa-<?=$s['icon']?>"></i></div>
            <div class="stat-value"><?=$c?></div>
            <div class="stat-label"><?=ucfirst(str_replace('_',' ',$a))?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="filter-card">
        <form method="get">
            <input type="hidden" name="page" value="audit_log">
            <div class="filter-grid">
                <div class="filter-group"><label>User</label><input type="text" name="user" placeholder="Search email..." value="<?=htmlspecialchars($filterUser)?>"></div>
                <div class="filter-group"><label>Action</label><select name="action"><option value="">All Actions</option><?php foreach($actions as $a):?><option value="<?=$a?>" <?=$filterAction==$a?'selected':''?>><?=ucfirst(str_replace('_',' ',$a))?></option><?php endforeach;?></select></div>
                <div class="filter-group"><label>Entity</label><select name="entity"><option value="">All Entities</option><?php foreach($entities as $e):?><option value="<?=$e?>" <?=$filterEntity==$e?'selected':''?>><?=ucfirst($e)?></option><?php endforeach;?></select></div>
                <div class="filter-group"><label>From</label><input type="date" name="date_from" value="<?=htmlspecialchars($filterDateFrom)?>"></div>
                <div class="filter-group"><label>To</label><input type="date" name="date_to" value="<?=htmlspecialchars($filterDateTo)?>"></div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter btn-filter-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="?page=audit_log" class="btn-filter btn-filter-secondary"><i class="fa fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
    </div>

    <div class="timeline">
        <div class="timeline-header"><h3><i class="fa fa-clock-o"></i> Activity Timeline</h3></div>
        <div class="timeline-body">
<?php if (empty($logs)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fa fa-inbox"></i></div><div class="empty-title">No Activity Found</div><div class="empty-text">There are no audit log entries matching your filters.</div></div>
<?php else: foreach ($logs as $log):
    $s = $actionStyles[$log['action']] ?? ['icon'=>'circle','bg'=>'#6b7280'];
    $rid = !empty($log['entity_id']) ? $log['entity_id'] : (!empty($log['record_id']) ? $log['record_id'] : null);
?>
            <div class="timeline-item">
                <div class="timeline-time">
                    <div class="date"><?=date('M j, Y', strtotime($log['created_at']))?></div>
                    <div class="time"><?=date('H:i:s', strtotime($log['created_at']))?></div>
                </div>
                <div class="timeline-icon" style="background:<?=$s['bg']?>;"><i class="fa fa-<?=$s['icon']?>"></i></div>
                <div class="timeline-content">
                    <div class="timeline-action">
                        <?=ucfirst(str_replace('_',' ',$log['action']))?>
                        <span class="timeline-entity"><i class="fa fa-cube"></i> <?=htmlspecialchars($log['entity_type'])?><?php if($rid):?> #<?=intval($rid)?><?php endif;?></span>
                    </div>
                    <div class="timeline-user"><i class="fa fa-user-circle"></i> <?=htmlspecialchars($log['user_email'])?></div>
                    <?php if ($log['old_values'] || $log['new_values']): ?>
                    <div class="timeline-details">
                        <span class="details-toggle" onclick="toggleDetails(<?=$log['id']?>)"><i class="fa fa-code"></i> View Changes</span>
                        <div class="details-content" id="details-<?=$log['id']?>">
                            <?php if($log['old_values']):?><div class="diff-box diff-old"><span class="diff-label">− Previous</span><?=htmlspecialchars($log['old_values'])?></div><?php endif;?>
                            <?php if($log['new_values']):?><div class="diff-box diff-new"><span class="diff-label">+ Updated</span><?=htmlspecialchars($log['new_values'])?></div><?php endif;?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="timeline-meta"><span class="timeline-ip"><?=htmlspecialchars($log['ip_address'])?></span></div>
            </div>
<?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>function toggleDetails(id){document.getElementById('details-'+id).classList.toggle('show');}</script>
