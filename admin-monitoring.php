<?php
/**
 * Admin Monitoring Dashboard
 * System health, performance metrics, and security monitoring
 * Access: Admin (level 1) and Super Admin (level 2) only
 */
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/monitoring.php");
require_once("inc/performance.php");

$db = new DbConn($config);

// Check authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check admin level (1 = Admin, 2 = Super Admin)
$user_level = $_SESSION['user_level'] ?? 0;
if ($user_level < 1) {
    echo "<script>alert('Access Denied. Admin privileges required.');window.location='index.php';</script>";
    exit;
}

$is_super_admin = ($user_level >= 2);

// Handle actions
if (isset($_GET['action']) && $is_super_admin) {
    switch ($_GET['action']) {
        case 'clear_cache':
            if (csrf_verify($_GET['token'] ?? '')) {
                QueryCache::clear();
                $action_message = ['type' => 'success', 'text' => 'Query cache cleared successfully'];
            }
            break;
        case 'rotate_logs':
            if (csrf_verify($_GET['token'] ?? '')) {
                rotate_logs();
                $action_message = ['type' => 'success', 'text' => 'Log files rotated successfully'];
            }
            break;
    }
}

// Gather monitoring data
$health = get_system_health($db->conn);
$db_stats = get_database_stats($db->conn);
$slow_queries = get_slow_queries(10);
$recent_errors = get_recent_errors(20);
$login_activity = get_login_activity($db->conn, 7);
$app_metrics = get_app_metrics($db->conn);
$log_stats = get_log_stats();

// Load language
$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring - iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8e44ad;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --dark: #2c3e50;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }
        
        .monitoring-header {
            background: linear-gradient(135deg, var(--primary), #6c3483);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .monitoring-header h1 {
            margin: 0;
            font-weight: 600;
        }
        
        .monitoring-header .subtitle {
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Health Status */
        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .health-item {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .health-item.healthy { border-left: 4px solid var(--success); }
        .health-item.warning { border-left: 4px solid var(--warning); }
        .health-item.unhealthy { border-left: 4px solid var(--danger); }
        
        .health-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .health-item.healthy .health-icon { color: var(--success); }
        .health-item.warning .health-icon { color: var(--warning); }
        .health-item.unhealthy .health-icon { color: var(--danger); }
        
        .health-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .health-status {
            font-weight: 600;
            margin-top: 5px;
        }
        
        /* Metrics */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid #eee;
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .metric-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Tables */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background: var(--light);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* Query display */
        .query-text {
            font-family: monospace;
            font-size: 11px;
            max-width: 500px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .error-text {
            font-family: monospace;
            font-size: 11px;
            color: var(--danger);
        }
        
        /* Badges */
        .badge-healthy { background: var(--success); }
        .badge-warning { background: var(--warning); }
        .badge-unhealthy { background: var(--danger); }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
        }
        
        /* Login chart */
        .login-bar {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .login-date {
            width: 100px;
            font-size: 12px;
            color: #666;
        }
        
        .login-bars {
            flex: 1;
            display: flex;
            gap: 2px;
        }
        
        .bar-success {
            background: var(--success);
            height: 20px;
            border-radius: 3px;
        }
        
        .bar-failed {
            background: var(--danger);
            height: 20px;
            border-radius: 3px;
        }
        
        .login-count {
            width: 80px;
            text-align: right;
            font-size: 12px;
        }
        
        /* Suspicious IP */
        .suspicious-ip {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #fff5f5;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid var(--danger);
        }
        
        /* Refresh button */
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(142, 68, 173, 0.4);
            cursor: pointer;
            font-size: 20px;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .monitoring-header {
                padding: 20px;
            }
            .health-grid {
                grid-template-columns: 1fr 1fr;
            }
            .metric-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        <?php include_once __DIR__ . '/inc/skeleton-loader.php'; echo get_skeleton_styles(); ?>
    </style>
</head>
<body>

<div class="skeleton-loading" id="pageContainer">
<!-- Skeleton Loading State -->
<div class="skeleton-container" style="padding: 20px;">
    <!-- Header skeleton -->
    <div style="background: linear-gradient(135deg, #8e44ad, #6c3483); padding: 30px; border-radius: 15px; margin-bottom: 30px;">
        <div class="skeleton skeleton-dark" style="width: 300px; height: 32px; margin-bottom: 10px;"></div>
        <div class="skeleton skeleton-dark" style="width: 250px; height: 16px;"></div>
    </div>
    
    <!-- Stats skeleton -->
    <div class="health-grid" style="margin-bottom: 20px;">
        <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="health-item">
            <div class="skeleton skeleton-icon" style="margin: 0 auto 10px;"></div>
            <div class="skeleton" style="width: 80px; height: 14px; margin: 0 auto 8px;"></div>
            <div class="skeleton" style="width: 60px; height: 20px; margin: 0 auto;"></div>
        </div>
        <?php endfor; ?>
    </div>
    
    <!-- Cards skeleton -->
    <div class="row">
        <div class="col-md-6">
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header"><div class="skeleton" style="width: 150px; height: 16px;"></div></div>
                <div class="card-body">
                    <div class="metric-grid">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="metric-card">
                            <div class="skeleton" style="width: 60px; height: 28px; margin: 0 auto 8px;"></div>
                            <div class="skeleton" style="width: 80px; height: 12px; margin: 0 auto;"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header"><div class="skeleton" style="width: 120px; height: 16px;"></div></div>
                <div class="card-body">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="login-bar">
                        <div class="skeleton" style="width: 100px; height: 14px;"></div>
                        <div class="skeleton" style="flex: 1; height: 20px; margin: 0 10px;"></div>
                        <div class="skeleton" style="width: 60px; height: 14px;"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Table skeleton -->
    <div class="card">
        <div class="card-header"><div class="skeleton" style="width: 150px; height: 16px;"></div></div>
        <div class="card-body">
            <?php for ($i = 0; $i < 6; $i++): ?>
            <div style="display: flex; gap: 20px; padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                <div class="skeleton" style="width: 60%; height: 14px;"></div>
                <div class="skeleton" style="width: 15%; height: 14px;"></div>
                <div class="skeleton" style="width: 15%; height: 14px;"></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Actual Content -->
<div class="content-container">
<!-- Header -->
<div class="monitoring-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fa fa-dashboard"></i> System Monitoring</h1>
            <div class="subtitle">Real-time system health and performance metrics</div>
        </div>
        <div class="action-buttons mt-2">
            <a href="index.php" class="btn btn-light btn-action">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
            <?php if ($is_super_admin): ?>
            <a href="?action=clear_cache&token=<?= csrf_token() ?>" class="btn btn-warning btn-action" onclick="return confirm('Clear all query cache?')">
                <i class="fa fa-trash"></i> Clear Cache
            </a>
            <a href="?action=rotate_logs&token=<?= csrf_token() ?>" class="btn btn-info btn-action" onclick="return confirm('Rotate log files?')">
                <i class="fa fa-refresh"></i> Rotate Logs
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($action_message)): ?>
<div class="alert alert-<?= $action_message['type'] ?> alert-dismissible fade in">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <?= e($action_message['text']) ?>
</div>
<?php endif; ?>

<!-- System Health -->
<div class="card">
    <div class="card-header">
        <i class="fa fa-heartbeat"></i> System Health
        <span class="badge badge-<?= $health['overall'] ?> float-right" style="text-transform: uppercase;">
            <?= $health['overall'] ?>
        </span>
    </div>
    <div class="card-body">
        <div class="health-grid">
            <?php foreach ($health['components'] as $name => $component): ?>
            <div class="health-item <?= $component['status'] ?>">
                <div class="health-icon">
                    <?php
                    $icons = [
                        'database' => 'fa-database',
                        'disk' => 'fa-hdd-o',
                        'logs' => 'fa-file-text',
                        'uploads' => 'fa-upload',
                        'cache' => 'fa-bolt'
                    ];
                    ?>
                    <i class="fa <?= $icons[$name] ?? 'fa-check-circle' ?>"></i>
                </div>
                <div class="health-label"><?= ucfirst($name) ?></div>
                <div class="health-status">
                    <?php if ($name === 'disk'): ?>
                        <?= $component['used_percent'] ?>% used
                    <?php else: ?>
                        <?= ucfirst($component['status']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- App Metrics -->
<div class="card">
    <div class="card-header">
        <i class="fa fa-bar-chart"></i> Application Metrics
    </div>
    <div class="card-body">
        <div class="metric-grid">
            <?php 
            $metric_labels = [
                'company' => 'Companies',
                'iv' => 'Invoices',
                'receipt' => 'Receipts',
                'voucher' => 'Vouchers',
                'product' => 'Products',
                'pr' => 'Purchase Requests',
                'authorize' => 'Users'
            ];
            foreach ($app_metrics['counts'] ?? [] as $table => $count): 
            ?>
            <div class="metric-card">
                <div class="metric-value"><?= number_format($count) ?></div>
                <div class="metric-label"><?= $metric_labels[$table] ?? ucfirst($table) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (isset($app_metrics['revenue_mtd'])): ?>
            <div class="metric-card">
                <div class="metric-value">฿<?= number_format($app_metrics['revenue_mtd'], 0) ?></div>
                <div class="metric-label">Revenue MTD</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Login Activity -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-sign-in"></i> Login Activity (7 Days)
                <span class="badge badge-info float-right"><?= $login_activity['unique_today'] ?? 0 ?> today</span>
            </div>
            <div class="card-body">
                <?php 
                $max_logins = 1;
                foreach ($login_activity['daily'] ?? [] as $day) {
                    $max_logins = max($max_logins, $day['successful'] + $day['failed']);
                }
                foreach ($login_activity['daily'] ?? [] as $day): 
                    $success_width = ($day['successful'] / $max_logins) * 100;
                    $failed_width = ($day['failed'] / $max_logins) * 100;
                ?>
                <div class="login-bar">
                    <div class="login-date"><?= date('M d', strtotime($day['date'])) ?></div>
                    <div class="login-bars">
                        <div class="bar-success" style="width: <?= $success_width ?>%" title="<?= $day['successful'] ?> successful"></div>
                        <?php if ($day['failed'] > 0): ?>
                        <div class="bar-failed" style="width: <?= $failed_width ?>%" title="<?= $day['failed'] ?> failed"></div>
                        <?php endif; ?>
                    </div>
                    <div class="login-count">
                        <span style="color: var(--success)"><?= $day['successful'] ?></span>
                        <?php if ($day['failed'] > 0): ?>
                        / <span style="color: var(--danger)"><?= $day['failed'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($login_activity['daily'])): ?>
                <p class="text-muted text-center">No login activity recorded</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Suspicious IPs -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-warning"></i> Suspicious Activity (24h)
            </div>
            <div class="card-body">
                <?php if (!empty($login_activity['suspicious_ips'])): ?>
                    <?php foreach ($login_activity['suspicious_ips'] as $ip): ?>
                    <div class="suspicious-ip">
                        <span><i class="fa fa-exclamation-triangle"></i> <?= e($ip['ip_address']) ?></span>
                        <span class="badge badge-danger"><?= $ip['attempts'] ?> failed attempts</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-shield fa-3x" style="color: var(--success);"></i>
                        <p class="mt-2">No suspicious activity detected</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Slow Queries -->
<div class="card">
    <div class="card-header">
        <i class="fa fa-clock-o"></i> Slow Queries (>1 second)
    </div>
    <div class="card-body">
        <?php if (!empty($slow_queries)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Duration</th>
                        <th>Query</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slow_queries as $query): ?>
                    <tr>
                        <td><small><?= e($query['timestamp']) ?></small></td>
                        <td><span class="badge badge-warning"><?= $query['duration'] ?>s</span></td>
                        <td class="query-text" title="<?= e($query['query']) ?>"><?= e($query['query']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-4">
            <i class="fa fa-rocket fa-3x" style="color: var(--success);"></i>
            <p class="mt-2">No slow queries detected. Great performance!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Database Stats -->
<div class="card">
    <div class="card-header">
        <i class="fa fa-database"></i> Database Statistics
        <span class="float-right text-muted">
            Total: <?= $db_stats['totals']['total_data_mb'] ?? 0 ?> MB data, 
            <?= $db_stats['totals']['total_index_mb'] ?? 0 ?> MB indexes
        </span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th class="text-right">Rows</th>
                        <th class="text-right">Data (MB)</th>
                        <th class="text-right">Index (MB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($db_stats['tables'] ?? [], 0, 15) as $table): ?>
                    <tr>
                        <td><code><?= e($table['table_name']) ?></code></td>
                        <td class="text-right"><?= number_format($table['table_rows']) ?></td>
                        <td class="text-right"><?= $table['data_mb'] ?></td>
                        <td class="text-right"><?= $table['index_mb'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Errors -->
<?php if ($is_super_admin): ?>
<div class="card">
    <div class="card-header">
        <i class="fa fa-bug"></i> Recent Errors
        <span class="badge badge-danger float-right"><?= count($recent_errors) ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($recent_errors)): ?>
        <div style="max-height: 300px; overflow-y: auto; background: #1a1a2e; border-radius: 8px; padding: 15px;">
            <?php foreach ($recent_errors as $error): ?>
            <div class="error-text" style="color: #ff6b6b; margin-bottom: 8px; border-bottom: 1px solid #333; padding-bottom: 8px;">
                <?= e($error) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-4">
            <i class="fa fa-check-circle fa-3x" style="color: var(--success);"></i>
            <p class="mt-2">No recent errors</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Log Files -->
<div class="card">
    <div class="card-header">
        <i class="fa fa-file-text-o"></i> Log Files
    </div>
    <div class="card-body">
        <?php if (!empty($log_stats)): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>File</th>
                        <th class="text-right">Size</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($log_stats as $log): ?>
                    <tr>
                        <td><code><?= e($log['name']) ?></code></td>
                        <td class="text-right">
                            <span class="<?= $log['size'] > 5242880 ? 'text-warning' : '' ?>">
                                <?= $log['size_formatted'] ?>
                            </span>
                        </td>
                        <td><small><?= $log['modified'] ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-center">No log files found</p>
        <?php endif; ?>
    </div>
</div>

<!-- Refresh Button -->
<button class="refresh-btn" onclick="location.reload()" title="Refresh">
    <i class="fa fa-refresh"></i>
</button>

<!-- Footer -->
<div class="text-center text-muted py-4">
    <small>Last updated: <?= date('Y-m-d H:i:s') ?> | Auto-refresh: <span id="countdown">60</span>s</small>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script>
// Remove skeleton loading
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var container = document.getElementById('pageContainer');
        if (container) {
            container.classList.remove('skeleton-loading');
        }
    }, 300);
});

// Auto-refresh every 60 seconds
let countdown = 60;
setInterval(function() {
    countdown--;
    document.getElementById('countdown').textContent = countdown;
    if (countdown <= 0) {
        location.reload();
    }
}, 1000);
</script>
</div><!-- End content-container -->
</div><!-- End pageContainer -->
</body>
</html>
