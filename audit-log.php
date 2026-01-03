<?php
// Security already checked in index.php
// Only Super Admin can view audit logs
$user_level = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
if ($user_level < 2) {
    echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied. Super Admin privileges required.</div>';
    return;
}

// Include audit functions
require_once("inc/audit.php");

// Get filter parameters
$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_entity = isset($_GET['entity']) ? trim($_GET['entity']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build filters array
$filters = [];
if (!empty($filter_action)) $filters['action'] = $filter_action;
if (!empty($filter_entity)) $filters['entity_type'] = $filter_entity;
if (!empty($filter_date_from)) $filters['date_from'] = $filter_date_from;
if (!empty($filter_date_to)) $filters['date_to'] = $filter_date_to;

// Get audit logs
$logs = get_audit_logs($db->conn, 200, $filters);

// Filter by user email if specified
if (!empty($filter_user)) {
    $logs = array_filter($logs, function($log) use ($filter_user) {
        return stripos($log['user_email'], $filter_user) !== false;
    });
}

// Get unique values for filters
$actions = ['login', 'logout', 'login_failed', 'create', 'update', 'delete', 'view', 'export'];
$entities = ['session', 'company', 'user', 'po', 'pr', 'invoice', 'payment', 'product', 'report'];

// Action icons and colors
$action_styles = [
    'login' => ['icon' => 'sign-in', 'bg' => '#10b981', 'color' => '#fff'],
    'logout' => ['icon' => 'sign-out', 'bg' => '#6b7280', 'color' => '#fff'],
    'login_failed' => ['icon' => 'times-circle', 'bg' => '#ef4444', 'color' => '#fff'],
    'create' => ['icon' => 'plus', 'bg' => '#3b82f6', 'color' => '#fff'],
    'update' => ['icon' => 'pencil', 'bg' => '#f59e0b', 'color' => '#fff'],
    'delete' => ['icon' => 'trash', 'bg' => '#ef4444', 'color' => '#fff'],
    'view' => ['icon' => 'eye', 'bg' => '#8b5cf6', 'color' => '#fff'],
    'export' => ['icon' => 'download', 'bg' => '#06b6d4', 'color' => '#fff'],
];

// Count by action type for stats
$action_counts = array_count_values(array_column($logs, 'action'));
?>

<style>
.audit-container { max-width: 1400px; margin: 0 auto; }
.audit-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.audit-title { font-size: 28px; font-weight: 600; color: #1f2937; margin: 0; display: flex; align-items: center; gap: 12px; }
.audit-title i { color: #6b7280; }
.audit-badge { background: #f3f4f6; color: #374151; padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 500; }

/* Stats Cards */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; padding: 16px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: transform 0.2s, box-shadow 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.stat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-size: 16px; }
.stat-value { font-size: 22px; font-weight: 700; color: #1f2937; }
.stat-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

/* Filter Card */
.filter-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
.filter-grid { display: grid; grid-template-columns: repeat(5, 1fr) auto; gap: 16px; align-items: end; }
.filter-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-group input, .filter-group select { width: 100%; height: 44px; padding: 0 16px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; background: #f9fafb; box-sizing: border-box; }
.filter-group input:focus, .filter-group select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); background: #fff; }
.filter-actions { display: flex; gap: 10px; }
.btn-filter { padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
.btn-filter-primary { background: #3b82f6; color: #fff; }
.btn-filter-primary:hover { background: #2563eb; }
.btn-filter-secondary { background: #f3f4f6; color: #374151; }
.btn-filter-secondary:hover { background: #e5e7eb; }

/* Timeline */
.timeline { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
.timeline-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
.timeline-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: #1f2937; }
.timeline-body { max-height: 600px; overflow-y: auto; }
.timeline-item { display: grid; grid-template-columns: 140px 48px 1fr auto; gap: 0; align-items: start; padding: 16px 24px; border-bottom: 1px solid #f3f4f6; transition: background-color 0.2s; }
.timeline-item:hover { background: #f9fafb; }
.timeline-item:last-child { border-bottom: none; }

.timeline-time { font-size: 12px; color: #6b7280; padding-top: 4px; }
.timeline-time .date { font-weight: 600; color: #374151; }
.timeline-time .time { margin-top: 2px; }

.timeline-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; }

.timeline-content { padding-left: 16px; }
.timeline-action { font-weight: 600; color: #1f2937; font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
.timeline-entity { display: inline-flex; align-items: center; gap: 6px; background: #f3f4f6; padding: 3px 10px; border-radius: 6px; font-size: 12px; color: #4b5563; font-weight: 500; }
.timeline-user { font-size: 13px; color: #6b7280; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
.timeline-user i { color: #9ca3af; }

.timeline-meta { text-align: right; padding-top: 4px; }
.timeline-ip { font-family: 'SF Mono', 'Menlo', monospace; font-size: 11px; color: #9ca3af; background: #f3f4f6; padding: 4px 8px; border-radius: 6px; }

.timeline-details { margin-top: 12px; }
.details-toggle { font-size: 12px; color: #3b82f6; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 4px 0; }
.details-toggle:hover { color: #2563eb; }
.details-content { display: none; margin-top: 10px; }
.details-content.show { display: block; }
.diff-box { border-radius: 8px; padding: 12px; font-family: 'SF Mono', 'Menlo', monospace; font-size: 12px; margin-bottom: 8px; white-space: pre-wrap; word-break: break-all; }
.diff-old { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.diff-new { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.diff-label { font-weight: 600; margin-bottom: 6px; display: block; font-family: -apple-system, sans-serif; }

/* Empty State */
.empty-state { padding: 80px 24px; text-align: center; }
.empty-icon { width: 80px; height: 80px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
.empty-icon i { font-size: 32px; color: #9ca3af; }
.empty-title { font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 8px; }
.empty-text { color: #6b7280; font-size: 14px; }

/* Responsive */
@media (max-width: 1200px) {
    .filter-grid { grid-template-columns: repeat(3, 1fr); }
    .filter-actions { grid-column: span 3; justify-content: flex-start; }
}
@media (max-width: 768px) {
    .timeline-item { grid-template-columns: 1fr; gap: 12px; }
    .timeline-meta { text-align: left; }
    .filter-grid { grid-template-columns: 1fr 1fr; }
    .filter-actions { grid-column: span 2; }
}
</style>

<div class="audit-container">
    <!-- Header -->
    <div class="audit-header">
        <h1 class="audit-title">
            <i class="fa fa-shield"></i> Audit Log
        </h1>
        <span class="audit-badge">
            <i class="fa fa-database"></i> <?=count($logs)?> records
        </span>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <?php 
        $display_actions = ['login', 'logout', 'create', 'update', 'delete', 'login_failed'];
        foreach ($display_actions as $action): 
            $style = $action_styles[$action] ?? ['icon' => 'circle', 'bg' => '#6b7280', 'color' => '#fff'];
            $count = $action_counts[$action] ?? 0;
        ?>
        <div class="stat-card">
            <div class="stat-icon" style="background: <?=$style['bg']?>20; color: <?=$style['bg']?>;">
                <i class="fa fa-<?=$style['icon']?>"></i>
            </div>
            <div class="stat-value"><?=$count?></div>
            <div class="stat-label"><?=ucfirst(str_replace('_', ' ', $action))?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="get" action="">
            <input type="hidden" name="page" value="audit_log">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>User</label>
                    <input type="text" name="user" placeholder="Search email..." value="<?=htmlspecialchars($filter_user)?>">
                </div>
                <div class="filter-group">
                    <label>Action</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?=$a?>" <?=$filter_action == $a ? 'selected' : ''?>><?=ucfirst(str_replace('_', ' ', $a))?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Entity</label>
                    <select name="entity">
                        <option value="">All Entities</option>
                        <?php foreach ($entities as $e): ?>
                        <option value="<?=$e?>" <?=$filter_entity == $e ? 'selected' : ''?>><?=ucfirst($e)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From</label>
                    <input type="date" name="date_from" value="<?=htmlspecialchars($filter_date_from)?>">
                </div>
                <div class="filter-group">
                    <label>To</label>
                    <input type="date" name="date_to" value="<?=htmlspecialchars($filter_date_to)?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter btn-filter-primary">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="?page=audit_log" class="btn-filter btn-filter-secondary">
                        <i class="fa fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Timeline -->
    <div class="timeline">
        <div class="timeline-header">
            <h3><i class="fa fa-clock-o"></i> Activity Timeline</h3>
        </div>
        <div class="timeline-body">
            <?php if (empty($logs)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa fa-inbox"></i>
                </div>
                <div class="empty-title">No Activity Found</div>
                <div class="empty-text">There are no audit log entries matching your filters.</div>
            </div>
            <?php else: ?>
                <?php foreach ($logs as $log): 
                    $style = $action_styles[$log['action']] ?? ['icon' => 'circle', 'bg' => '#6b7280', 'color' => '#fff'];
                    $record_id = !empty($log['entity_id']) ? $log['entity_id'] : (!empty($log['record_id']) ? $log['record_id'] : null);
                ?>
                <div class="timeline-item">
                    <div class="timeline-time">
                        <div class="date"><?=date('M j, Y', strtotime($log['created_at']))?></div>
                        <div class="time"><?=date('H:i:s', strtotime($log['created_at']))?></div>
                    </div>
                    <div class="timeline-icon" style="background: <?=$style['bg']?>; color: <?=$style['color']?>;">
                        <i class="fa fa-<?=$style['icon']?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-action">
                            <?=ucfirst(str_replace('_', ' ', $log['action']))?>
                            <span class="timeline-entity">
                                <i class="fa fa-cube"></i>
                                <?=htmlspecialchars($log['entity_type'])?>
                                <?php if ($record_id): ?>
                                    #<?=intval($record_id)?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="timeline-user">
                            <i class="fa fa-user-circle"></i>
                            <?=htmlspecialchars($log['user_email'])?>
                        </div>
                        <?php if ($log['old_values'] || $log['new_values']): ?>
                        <div class="timeline-details">
                            <span class="details-toggle" onclick="toggleDetails(<?=$log['id']?>)">
                                <i class="fa fa-code"></i> View Changes
                            </span>
                            <div class="details-content" id="details-<?=$log['id']?>">
                                <?php if ($log['old_values']): ?>
                                <div class="diff-box diff-old">
                                    <span class="diff-label">− Previous</span>
                                    <?=htmlspecialchars($log['old_values'])?>
                                </div>
                                <?php endif; ?>
                                <?php if ($log['new_values']): ?>
                                <div class="diff-box diff-new">
                                    <span class="diff-label">+ Updated</span>
                                    <?=htmlspecialchars($log['new_values'])?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-meta">
                        <span class="timeline-ip"><?=htmlspecialchars($log['ip_address'])?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDetails(id) {
    const el = document.getElementById('details-' + id);
    el.classList.toggle('show');
}
</script>
