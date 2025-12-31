<?php
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");
require_once("core-function.php");

$users = new DbConn($config);
DbConn::setGlobalConnection($users->conn);
$users->checkSecurity();
$har = new HardClass($users->conn);

// Set audit context
set_audit_context();

$page_title = "Audit Log Viewer";
$filter_table = isset($_GET['table']) ? $_GET['table'] : '';
$filter_operation = isset($_GET['operation']) ? $_GET['operation'] : '';
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';
$hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .container { margin-top: 20px; }
        .filter-box { background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .audit-table { background: white; border-radius: 4px; }
        .label-INSERT { background-color: #5cb85c; }
        .label-UPDATE { background-color: #f0ad4e; }
        .label-DELETE { background-color: #d9534f; }
        .timestamp { font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1><?php echo $page_title; ?></h1>
    
    <!-- Filter Box -->
    <div class="filter-box">
        <form class="form-inline" method="get">
            <label>Table:</label>
            <input type="text" name="table" placeholder="e.g., company" value="<?php echo htmlspecialchars($filter_table); ?>" class="input-small">
            
            <label>Operation:</label>
            <select name="operation" class="input-small">
                <option value="">All</option>
                <option value="INSERT" <?php echo $filter_operation == 'INSERT' ? 'selected' : ''; ?>>INSERT</option>
                <option value="UPDATE" <?php echo $filter_operation == 'UPDATE' ? 'selected' : ''; ?>>UPDATE</option>
                <option value="DELETE" <?php echo $filter_operation == 'DELETE' ? 'selected' : ''; ?>>DELETE</option>
            </select>
            
            <label>Hours:</label>
            <select name="hours" class="input-small">
                <option value="1" <?php echo $hours == 1 ? 'selected' : ''; ?>>Last Hour</option>
                <option value="24" <?php echo $hours == 24 ? 'selected' : ''; ?>>Last 24 Hours</option>
                <option value="168" <?php echo $hours == 168 ? 'selected' : ''; ?>>Last Week</option>
                <option value="720" <?php echo $hours == 720 ? 'selected' : ''; ?>>Last Month</option>
                <option value="0" <?php echo $hours == 0 ? 'selected' : ''; ?>>All</option>
            </select>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="audit-log.php" class="btn">Clear</a>
        </form>
    </div>
    
    <!-- Audit Log Table -->
    <div class="audit-table">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Table</th>
                    <th>Operation</th>
                    <th>Record ID</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build query
                $conn = DbConn::getGlobalConnection();
                $sql = "SELECT * FROM audit_log WHERE 1=1 ";
                
                if ($filter_table) {
                    $filter_table = mysqli_real_escape_string($conn, $filter_table);
                    $sql .= " AND table_name LIKE '%$filter_table%'";
                }
                
                if ($filter_operation) {
                    $filter_operation = mysqli_real_escape_string($conn, $filter_operation);
                    $sql .= " AND operation = '$filter_operation'";
                }
                
                if ($hours > 0) {
                    $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)";
                }
                
                $sql .= " ORDER BY created_at DESC";
                
                // Get total count
                $count_result = mysqli_query($conn, $sql);
                $total = mysqli_num_rows($count_result);
                $total_pages = ceil($total / $limit);
                
                // Get paginated results
                $sql .= " LIMIT $offset, $limit";
                $result = mysqli_query($conn, $sql);
                
                $row_count = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                    $row_count++;
                    $timestamp = date('Y-m-d H:i:s', strtotime($row['created_at']));
                    $table = htmlspecialchars($row['table_name']);
                    $operation = htmlspecialchars($row['operation']);
                    $description = htmlspecialchars($row['description']);
                    $user = $row['user_id'] ? "ID: {$row['user_id']}" : 'System';
                    $ip = htmlspecialchars($row['ip_address']);
                    $label_class = "label-{$operation}";
                    
                    echo "
                        <tr>
                            <td><span class='timestamp'>$timestamp</span></td>
                            <td><code>$table</code></td>
                            <td><span class='label $label_class'>$operation</span></td>
                            <td>#{$row['record_id']}</td>
                            <td>$description</td>
                            <td>$user</td>
                            <td><small>$ip</small></td>
                        </tr>
                    ";
                }
                
                if ($row_count == 0) {
                    echo "<tr><td colspan='7' class='text-center text-muted'>No audit log entries found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination" style="text-align: center; margin-top: 20px;">
        <ul>
            <?php if ($page > 1): ?>
            <li><a href="?table=<?php echo urlencode($filter_table); ?>&operation=<?php echo urlencode($filter_operation); ?>&hours=<?php echo $hours; ?>&p=1">« First</a></li>
            <li><a href="?table=<?php echo urlencode($filter_table); ?>&operation=<?php echo urlencode($filter_operation); ?>&hours=<?php echo $hours; ?>&p=<?php echo $page-1; ?>">← Previous</a></li>
            <?php endif; ?>
            
            <li><a href="#">Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total; ?> total)</a></li>
            
            <?php if ($page < $total_pages): ?>
            <li><a href="?table=<?php echo urlencode($filter_table); ?>&operation=<?php echo urlencode($filter_operation); ?>&hours=<?php echo $hours; ?>&p=<?php echo $page+1; ?>">Next →</a></li>
            <li><a href="?table=<?php echo urlencode($filter_table); ?>&operation=<?php echo urlencode($filter_operation); ?>&hours=<?php echo $hours; ?>&p=<?php echo $total_pages; ?>">Last »</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div style="margin-top: 30px;">
        <h3>Statistics</h3>
        <?php
        $stats = get_audit_statistics();
        ?>
        <p><strong>Total Audit Entries:</strong> <?php echo $stats['total_entries']; ?></p>
        <p><strong>Last 24 Hours:</strong> <?php echo $stats['last_24_hours']; ?> entries</p>
        
        <h4>By Operation:</h4>
        <ul>
            <?php foreach ($stats['by_operation'] as $op => $count): ?>
            <li><?php echo htmlspecialchars($op); ?>: <?php echo $count; ?></li>
            <?php endforeach; ?>
        </ul>
        
        <h4>Top Tables (by audit entries):</h4>
        <ol>
            <?php foreach ($stats['by_table'] as $tbl => $count): ?>
            <li><a href="?table=<?php echo urlencode($tbl); ?>"><?php echo htmlspecialchars($tbl); ?></a>: <?php echo $count; ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
