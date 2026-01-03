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

// Filter by user email if specified (can't easily do in SQL with partial match)
if (!empty($filter_user)) {
    $logs = array_filter($logs, function($log) use ($filter_user) {
        return stripos($log['user_email'], $filter_user) !== false;
    });
}

// Get unique values for filters
$actions = ['login', 'logout', 'login_failed', 'create', 'update', 'delete', 'view', 'export'];
$entities = ['session', 'company', 'user', 'po', 'pr', 'invoice', 'payment', 'product', 'report'];
?>

<h2><i class="fa fa-history"></i> Audit Log</h2>

<!-- Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> Filters
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="audit_log">
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">User:</label>
                <input type="text" class="form-control" name="user" 
                       placeholder="Email..." value="<?=htmlspecialchars($filter_user)?>" style="width: 150px;">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">Action:</label>
                <select class="form-control" name="action">
                    <option value="">All</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?=$a?>" <?=$filter_action == $a ? 'selected' : ''?>><?=ucfirst(str_replace('_', ' ', $a))?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">Entity:</label>
                <select class="form-control" name="entity">
                    <option value="">All</option>
                    <?php foreach ($entities as $e): ?>
                    <option value="<?=$e?>" <?=$filter_entity == $e ? 'selected' : ''?>><?=ucfirst($e)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">From:</label>
                <input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($filter_date_from)?>">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">To:</label>
                <input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($filter_date_to)?>">
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
            <a href="?page=audit_log" class="btn btn-default"><i class="fa fa-refresh"></i> Clear</a>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-12">
        <span class="label label-info" style="font-size: 14px; padding: 8px 12px;">
            <i class="fa fa-list"></i> Showing <?=count($logs)?> records
        </span>
    </div>
</div>

<!-- Audit Log Table -->
<table class="table table-bordered table-hover table-striped">
    <thead style="background: #333; color: #fff;">
        <tr>
            <th width="140">Date/Time</th>
            <th>User</th>
            <th width="100">Action</th>
            <th>Entity</th>
            <th>Details</th>
            <th width="120">IP Address</th>
        </tr>
    </thead>
    <tbody>
<?php if (empty($logs)): ?>
        <tr>
            <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                <i class="fa fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                <p style="margin-top: 15px;">No audit log entries found.</p>
            </td>
        </tr>
<?php else: ?>
    <?php foreach ($logs as $log): ?>
        <?php
        // Determine action color
        $action_class = 'default';
        switch ($log['action']) {
            case 'login': $action_class = 'success'; break;
            case 'logout': $action_class = 'info'; break;
            case 'login_failed': $action_class = 'danger'; break;
            case 'create': $action_class = 'primary'; break;
            case 'update': $action_class = 'warning'; break;
            case 'delete': $action_class = 'danger'; break;
            case 'export': $action_class = 'info'; break;
        }
        
        // Build details
        $details = '';
        if ($log['entity_name']) {
            $details = htmlspecialchars($log['entity_name']);
        }
        if ($log['entity_id']) {
            $details .= ' (ID: ' . $log['entity_id'] . ')';
        }
        ?>
        <tr>
            <td style="font-size: 12px;">
                <?=date('d/m/Y H:i:s', strtotime($log['created_at']))?>
            </td>
            <td>
                <i class="fa fa-user"></i> <?=htmlspecialchars($log['user_email'])?>
            </td>
            <td>
                <span class="label label-<?=$action_class?>">
                    <?=ucfirst(str_replace('_', ' ', $log['action']))?>
                </span>
            </td>
            <td>
                <code><?=htmlspecialchars($log['entity_type'])?></code>
            </td>
            <td>
                <?=$details?>
                <?php if ($log['old_values'] || $log['new_values']): ?>
                <button class="btn btn-xs btn-default" onclick="toggleDetails(<?=$log['id']?>)">
                    <i class="fa fa-eye"></i> Details
                </button>
                <div id="details-<?=$log['id']?>" style="display: none; margin-top: 10px;">
                    <?php if ($log['old_values']): ?>
                    <div class="well well-sm" style="margin-bottom: 5px; background: #ffe6e6;">
                        <strong>Old:</strong> <pre style="margin: 5px 0; white-space: pre-wrap;"><?=htmlspecialchars($log['old_values'])?></pre>
                    </div>
                    <?php endif; ?>
                    <?php if ($log['new_values']): ?>
                    <div class="well well-sm" style="margin-bottom: 0; background: #e6ffe6;">
                        <strong>New:</strong> <pre style="margin: 5px 0; white-space: pre-wrap;"><?=htmlspecialchars($log['new_values'])?></pre>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </td>
            <td style="font-size: 11px; font-family: monospace;">
                <?=htmlspecialchars($log['ip_address'])?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
    </tbody>
</table>

<script>
function toggleDetails(id) {
    var el = document.getElementById('details-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
