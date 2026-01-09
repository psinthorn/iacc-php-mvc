<?php
/**
 * Admin Monitoring Dashboard
 * System health, performance metrics, and security monitoring
 * Access: Developer role required
 * Updated: January 9, 2026 - v4.7 UX/UI refresh
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/monitoring.php");
require_once("inc/performance.php");
require_once("inc/dev-tools-style.php");

$db = new DbConn($config);
$db->checkSecurity();

// Require Developer access
check_dev_tools_access();

$is_super_admin = ($_SESSION['user_level'] ?? 0) >= 2;

// Handle actions
$action_message = null;
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

// Calculate summary stats
$total_tables = count($db_stats['tables'] ?? []);
$total_rows = array_sum(array_column($db_stats['tables'] ?? [], 'table_rows'));
$total_data_mb = $db_stats['totals']['total_data_mb'] ?? 0;
$health_status = $health['overall'] ?? 'unknown';
$suspicious_count = count($login_activity['suspicious_ips'] ?? []);
$error_count = count($recent_errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <?php include_once __DIR__ . '/inc/skeleton-loader.php'; ?>
    <style>
        <?php echo get_skeleton_styles(); ?>
        
        /* Custom monitoring styles */
        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .health-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: transform 0.2s;
        }
        
        .health-item:hover {
            transform: translateY(-2px);
        }
        
        .health-item.healthy { border-left: 4px solid #27ae60; }
        .health-item.warning { border-left: 4px solid #f39c12; }
        .health-item.unhealthy { border-left: 4px solid #e74c3c; }
        
        .health-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .health-item.healthy .health-icon { color: #27ae60; }
        .health-item.warning .health-icon { color: #f39c12; }
        .health-item.unhealthy .health-icon { color: #e74c3c; }
        
        .health-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .health-status {
            font-weight: 600;
            margin-top: 5px;
            color: #1f2937;
        }
        
        /* Metric cards - similar to stat-cards */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
        }
        
        .metric-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #8b5cf6;
        }
        
        .metric-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        /* Login chart */
        .login-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .login-date {
            width: 80px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .login-bars {
            flex: 1;
            display: flex;
            gap: 2px;
            height: 24px;
        }
        
        .bar-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-radius: 4px;
            min-width: 2px;
        }
        
        .bar-failed {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 4px;
            min-width: 2px;
        }
        
        .login-count {
            width: 70px;
            text-align: right;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Suspicious IP */
        .suspicious-ip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #fff5f5;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #e74c3c;
        }
        
        .suspicious-ip i {
            color: #e74c3c;
            margin-right: 8px;
        }
        
        /* Query text */
        .query-text {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 11px;
            max-width: 500px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #6b7280;
        }
        
        /* Error log terminal */
        .error-terminal {
            background: #1a1a2e;
            border-radius: 10px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .error-line {
            color: #ff6b6b;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 11px;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #2d2d4a;
        }
        
        /* Two column layout */
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .two-col-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Refresh button */
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            border: none;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(139, 92, 246, 0.5);
        }
        
        /* Action buttons in header */
        .header-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        
        .header-actions .btn-action {
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .header-actions .btn-action:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .header-actions .btn-action.warning {
            background: rgba(243, 156, 18, 0.3);
        }
        
        .header-actions .btn-action.info {
            background: rgba(52, 152, 219, 0.3);
        }
    </style>
</head>
<body>
    <div class="dev-tools-container skeleton-loading" id="pageContainer">
        <!-- Skeleton Loading State -->
        <div class="skeleton-container">
            <?php echo skeleton_page_header(); ?>
            <div class="stats-grid" style="margin-top: 20px;">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="stat-card">
                    <div class="skeleton skeleton-icon"></div>
                    <div class="skeleton" style="width: 60px; height: 28px; margin: 10px auto;"></div>
                    <div class="skeleton" style="width: 80px; height: 14px; margin: 0 auto;"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(true); ?>
            </div>
        </div>
        
        <!-- Actual Content -->
        <div class="content-container">
        
        <!-- Custom Header with actions -->
        <div class="dev-tools-header" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fa fa-dashboard"></i>
                </div>
                <div class="header-text">
                    <h1>System Monitoring</h1>
                    <p class="subtitle">Real-time system health, performance metrics, and security monitoring</p>
                </div>
            </div>
            
            <?php if ($is_super_admin): ?>
            <div class="header-actions">
                <a href="?action=clear_cache&token=<?= csrf_token() ?>" class="btn-action warning" onclick="return confirm('Clear all query cache?')">
                    <i class="fa fa-trash"></i> Clear Cache
                </a>
                <a href="?action=rotate_logs&token=<?= csrf_token() ?>" class="btn-action info" onclick="return confirm('Rotate log files?')">
                    <i class="fa fa-refresh"></i> Rotate Logs
                </a>
            </div>
            <?php endif; ?>
            
            <div class="header-nav">
                <a href="index.php?page=dashboard" class="nav-btn"><i class="fa fa-arrow-left"></i> Dashboard</a>
                <a href="index.php?page=test_crud" class="nav-btn"><i class="fa fa-database"></i> CRUD</a>
                <a href="index.php?page=debug_session" class="nav-btn"><i class="fa fa-key"></i> Session</a>
                <a href="index.php?page=test_rbac" class="nav-btn"><i class="fa fa-shield"></i> RBAC</a>
                <a href="index.php?page=dev_roadmap" class="nav-btn"><i class="fa fa-road"></i> Roadmap</a>
                <a href="index.php?page=docker_test" class="nav-btn"><i class="fa fa-cloud"></i> Docker</a>
                <a href="index.php?page=test_containers" class="nav-btn"><i class="fa fa-cube"></i> Containers</a>
            </div>
        </div>
        
        <?php if ($action_message): ?>
        <div class="summary-box" style="background: <?= $action_message['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $action_message['type'] === 'success' ? '#155724' : '#721c24' ?>; border: none; margin-bottom: 20px;">
            <i class="fa fa-<?= $action_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= e($action_message['text']) ?>
        </div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card <?= $health_status === 'healthy' ? 'success' : ($health_status === 'warning' ? 'warning' : 'danger') ?>">
                <div class="stat-icon"><i class="fa fa-heartbeat"></i></div>
                <div class="stat-value"><?= ucfirst($health_status) ?></div>
                <div class="stat-label">System Health</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-database"></i></div>
                <div class="stat-value"><?= $total_tables ?></div>
                <div class="stat-label">Database Tables</div>
            </div>
            <div class="stat-card <?= $suspicious_count > 0 ? 'danger' : 'success' ?>">
                <div class="stat-icon"><i class="fa fa-<?= $suspicious_count > 0 ? 'warning' : 'shield' ?>"></i></div>
                <div class="stat-value"><?= $suspicious_count ?></div>
                <div class="stat-label">Suspicious IPs (24h)</div>
            </div>
            <div class="stat-card <?= $error_count > 0 ? 'warning' : 'success' ?>">
                <div class="stat-icon"><i class="fa fa-bug"></i></div>
                <div class="stat-value"><?= $error_count ?></div>
                <div class="stat-label">Recent Errors</div>
            </div>
        </div>
        
        <!-- System Health Components -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-heartbeat"></i> System Health Components</span>
                <span class="badge badge-<?= $health_status ?>" style="background: <?= $health_status === 'healthy' ? '#27ae60' : ($health_status === 'warning' ? '#f39c12' : '#e74c3c') ?>; padding: 5px 12px; border-radius: 20px; color: white; font-size: 11px; text-transform: uppercase;">
                    <?= $health_status ?>
                </span>
            </div>
            <div class="health-grid">
                <?php 
                $icons = [
                    'database' => 'fa-database',
                    'disk' => 'fa-hdd-o',
                    'logs' => 'fa-file-text',
                    'uploads' => 'fa-upload',
                    'cache' => 'fa-bolt'
                ];
                foreach ($health['components'] as $name => $component): 
                ?>
                <div class="health-item <?= $component['status'] ?>">
                    <div class="health-icon">
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
        
        <!-- Application Metrics -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-bar-chart"></i> Application Metrics</span>
                <span style="color: #6b7280; font-size: 12px;">
                    Total: <?= number_format($total_rows) ?> rows | <?= $total_data_mb ?> MB
                </span>
            </div>
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
                    <div class="metric-value" style="font-size: 18px;">฿<?= number_format($app_metrics['revenue_mtd'], 0) ?></div>
                    <div class="metric-label">Revenue MTD</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Two Column: Login Activity & Suspicious IPs -->
        <div class="two-col-grid">
            <!-- Login Activity -->
            <div class="data-card">
                <div class="card-header">
                    <span><i class="fa fa-sign-in"></i> Login Activity (7 Days)</span>
                    <span style="background: #3498db; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                        <?= $login_activity['unique_today'] ?? 0 ?> today
                    </span>
                </div>
                <?php 
                $max_logins = 1;
                foreach ($login_activity['daily'] ?? [] as $day) {
                    $max_logins = max($max_logins, $day['successful'] + $day['failed']);
                }
                ?>
                <?php if (!empty($login_activity['daily'])): ?>
                    <?php foreach ($login_activity['daily'] ?? [] as $day): 
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
                            <span style="color: #27ae60"><?= $day['successful'] ?></span>
                            <?php if ($day['failed'] > 0): ?>
                            / <span style="color: #e74c3c"><?= $day['failed'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px; color: #6b7280;">
                        <i class="fa fa-info-circle" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <p>No login activity recorded</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Suspicious IPs -->
            <div class="data-card">
                <div class="card-header">
                    <span><i class="fa fa-warning"></i> Suspicious Activity (24h)</span>
                </div>
                <?php if (!empty($login_activity['suspicious_ips'])): ?>
                    <?php foreach ($login_activity['suspicious_ips'] as $ip): ?>
                    <div class="suspicious-ip">
                        <span><i class="fa fa-exclamation-triangle"></i> <?= e($ip['ip_address']) ?></span>
                        <span style="background: #e74c3c; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                            <?= $ip['attempts'] ?> failed
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px;">
                        <i class="fa fa-shield" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                        <p style="color: #6b7280;">No suspicious activity detected</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Slow Queries -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-clock-o"></i> Slow Queries (&gt;1 second)</span>
            </div>
            <?php if (!empty($slow_queries)): ?>
            <div class="table-container">
                <table class="data-table">
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
                            <td>
                                <span style="background: #f39c12; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px;">
                                    <?= $query['duration'] ?>s
                                </span>
                            </td>
                            <td class="query-text" title="<?= e($query['query']) ?>"><?= e($query['query']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 30px;">
                <i class="fa fa-rocket" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                <p style="color: #6b7280;">No slow queries detected. Great performance!</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Database Statistics -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-database"></i> Database Statistics</span>
                <span style="color: #6b7280; font-size: 12px;">
                    <?= $db_stats['totals']['total_data_mb'] ?? 0 ?> MB data | <?= $db_stats['totals']['total_index_mb'] ?? 0 ?> MB indexes
                </span>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th style="text-align: right;">Rows</th>
                            <th style="text-align: right;">Data (MB)</th>
                            <th style="text-align: right;">Index (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($db_stats['tables'] ?? [], 0, 15) as $table): ?>
                        <tr>
                            <td><code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= e($table['table_name']) ?></code></td>
                            <td style="text-align: right;"><?= number_format($table['table_rows']) ?></td>
                            <td style="text-align: right;"><?= $table['data_mb'] ?></td>
                            <td style="text-align: right;"><?= $table['index_mb'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($is_super_admin && !empty($recent_errors)): ?>
        <!-- Recent Errors (Super Admin only) -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-bug"></i> Recent Errors</span>
                <span style="background: #e74c3c; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                    <?= $error_count ?>
                </span>
            </div>
            <div class="error-terminal">
                <?php foreach ($recent_errors as $error): ?>
                <div class="error-line"><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Log Files -->
        <div class="data-card">
            <div class="card-header">
                <span><i class="fa fa-file-text-o"></i> Log Files</span>
            </div>
            <?php if (!empty($log_stats)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th style="text-align: right;">Size</th>
                            <th>Last Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log_stats as $log): ?>
                        <tr>
                            <td><code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= e($log['name']) ?></code></td>
                            <td style="text-align: right; <?= $log['size'] > 5242880 ? 'color: #f39c12; font-weight: 600;' : '' ?>">
                                <?= $log['size_formatted'] ?>
                            </td>
                            <td><small style="color: #6b7280;"><?= $log['modified'] ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #6b7280;">
                <p>No log files found</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; color: #6b7280; padding: 20px; font-size: 12px;">
            Last updated: <?= date('Y-m-d H:i:s') ?> | Auto-refresh: <span id="countdown">60</span>s
        </div>
        
        </div><!-- End content-container -->
    </div>
    
    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="location.reload()" title="Refresh">
        <i class="fa fa-refresh"></i>
    </button>
    
    <script><?php echo get_skeleton_js('pageContainer', 300); ?></script>
    <script>
    // Auto-refresh every 60 seconds
    let countdown = 60;
    setInterval(function() {
        countdown--;
        const el = document.getElementById('countdown');
        if (el) el.textContent = countdown;
        if (countdown <= 0) {
            location.reload();
        }
    }, 1000);
    </script>
</body>
</html>
