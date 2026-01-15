<?php
/**
 * CRUD Operations Test Script
 * Tests Create, Read, Update, Delete for all main tables
 * Redesigned with modern UI
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/dev-tools-style.php");

// Check access
check_dev_tools_access();

$db = new DbConn($config);
$results = [];
$test_date = date('Y-m-d H:i:s');

// Helper function to run a test
function runTest($name, $callback) {
    global $results;
    try {
        $result = $callback();
        $results[$name] = $result;
        return $result;
    } catch (Exception $e) {
        $results[$name] = ['status' => 'FAIL', 'error' => $e->getMessage()];
        return $results[$name];
    }
}

// Run all tests
// 1. Database Connection
$connTest = runTest('db_connection', function() use ($db) {
    if ($db->conn && !mysqli_connect_error()) {
        return ['status' => 'PASS', 'message' => 'Connected to MySQL successfully'];
    }
    return ['status' => 'FAIL', 'error' => mysqli_connect_error()];
});

// 2. Table checks
$tables = ['brand', 'category', 'type', 'company', 'company_addr', 'payment', 'pr', 'po', 'authorize'];
$tableResults = [];
foreach ($tables as $table) {
    $result = mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM `$table`");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $tableResults[$table] = ['exists' => true, 'count' => $row['cnt']];
    } else {
        $tableResults[$table] = ['exists' => false, 'count' => 0];
    }
}

// 3. Brand CRUD Test
$brandTest = runTest('brand_create', function() use ($db) {
    $testName = 'Test Brand ' . time();
    $sql = "INSERT INTO brand (brand_name, des, logo, ven_id) VALUES (?, ?, '', 0)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $testName, $testName);
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created ID: $newId", 'id' => $newId, 'name' => $testName];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});

if (isset($brandTest['id'])) {
    $brandRead = runTest('brand_read', function() use ($db, $brandTest) {
        $sql = "SELECT * FROM brand WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $brandTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read: {$row['brand_name']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    
    $brandUpdate = runTest('brand_update', function() use ($db, $brandTest) {
        $newName = 'Updated Brand ' . time();
        $sql = "UPDATE brand SET brand_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $brandTest['id']);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    
    $brandDelete = runTest('brand_delete', function() use ($db, $brandTest) {
        $sql = "DELETE FROM brand WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $brandTest['id']);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted ID: {$brandTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
}

// 4. Category CRUD Test
$catTest = runTest('category_create', function() use ($db) {
    $testName = 'Test Category ' . time();
    $sql = "INSERT INTO category (cat_name, des) VALUES (?, ?)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $testName, $testName);
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created ID: $newId", 'id' => $newId];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});

if (isset($catTest['id'])) {
    runTest('category_read', function() use ($db, $catTest) {
        $sql = "SELECT * FROM category WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $catTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read: {$row['cat_name']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    
    runTest('category_update', function() use ($db, $catTest) {
        $newName = 'Updated Category ' . time();
        $sql = "UPDATE category SET cat_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $catTest['id']);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    
    runTest('category_delete', function() use ($db, $catTest) {
        $sql = "DELETE FROM category WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $catTest['id']);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted ID: {$catTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
}

// 5. Company CRUD Test
$compTest = runTest('company_create', function() use ($db) {
    $testName = 'Test Company ' . time();
    $sql = "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term) 
            VALUES (?, ?, ?, '', '', '', '', '', '1', '0', '', 30)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $testName, $testName, $testName);
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created ID: $newId", 'id' => $newId];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});

if (isset($compTest['id'])) {
    runTest('company_read', function() use ($db, $compTest) {
        $sql = "SELECT * FROM company WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $compTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read: {$row['name_en']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    
    runTest('company_update', function() use ($db, $compTest) {
        $newName = 'Updated Company ' . time();
        $sql = "UPDATE company SET name_en = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $compTest['id']);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    
    runTest('company_delete', function() use ($db, $compTest) {
        $sql = "DELETE FROM company WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $compTest['id']);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted ID: {$compTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
}

// 6. Authorize table check
$authTest = runTest('authorize_read', function() use ($db) {
    $sql = "SELECT id, email, level FROM authorize LIMIT 5";
    $result = mysqli_query($db->conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $count = mysqli_num_rows($result);
        return ['status' => 'PASS', 'message' => "Found $count users"];
    }
    return ['status' => 'WARN', 'message' => 'No users found'];
});

// Password migration check
$pwTest = runTest('password_check', function() use ($db) {
    $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN password_migrated = 1 THEN 1 ELSE 0 END) as migrated FROM authorize";
    $result = mysqli_query($db->conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total = $row['total'];
        $migrated = $row['migrated'] ?? 0;
        if ($migrated == $total && $total > 0) {
            return ['status' => 'PASS', 'message' => "All $total users on bcrypt"];
        } elseif ($migrated > 0) {
            return ['status' => 'WARN', 'message' => "$migrated of $total migrated"];
        }
        return ['status' => 'WARN', 'message' => "Will migrate on login"];
    }
    return ['status' => 'FAIL', 'error' => 'Could not check'];
});

// Calculate summary
$passed = $failed = $warnings = 0;
foreach ($results as $result) {
    if ($result['status'] === 'PASS') $passed++;
    elseif ($result['status'] === 'WARN') $warnings++;
    else $failed++;
}
$total = count($results);

// Helper function to display result badge
function getStatusBadge($result) {
    $status = $result['status'] ?? 'SKIP';
    $message = $result['message'] ?? '';
    $error = $result['error'] ?? '';
    
    if ($status === 'PASS') {
        return "<span class='status-badge status-pass'><i class='fa fa-check'></i> PASS</span> <span style='color:#666;'>$message</span>";
    } elseif ($status === 'WARN') {
        return "<span class='status-badge status-warn'><i class='fa fa-exclamation-triangle'></i> WARNING</span> <span style='color:#666;'>$message</span>";
    } elseif ($status === 'SKIP') {
        return "<span class='status-badge status-info'><i class='fa fa-forward'></i> SKIPPED</span>";
    } else {
        return "<span class='status-badge status-fail'><i class='fa fa-times'></i> FAIL</span> <span style='color:#c0392b;'>$error</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Test - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <?php include_once __DIR__ . '/inc/skeleton-loader.php'; ?>
    <style><?php echo get_skeleton_styles(); ?></style>
</head>
<body>
    <div class="dev-tools-container skeleton-loading" id="pageContainer">
        <!-- Skeleton Loading State -->
        <div class="skeleton-container">
            <?php echo skeleton_page_header(); ?>
            <?php echo skeleton_stat_cards(4); ?>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(); ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_table(9, 3); ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(true); ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(true); ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(true); ?>
            </div>
        </div>
        
        <!-- Actual Content -->
        <div class="content-container">
        <?php echo get_dev_tools_header('CRUD Operations Test', 'Testing Create, Read, Update, Delete operations on all main database tables', 'fa-database', '#3498db'); ?>
        
        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $passed; ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo $warnings; ?></div>
                <div class="stat-label">Warnings</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fa fa-times-circle"></i></div>
                <div class="stat-value"><?php echo $failed; ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-list"></i></div>
                <div class="stat-value"><?php echo $total; ?></div>
                <div class="stat-label">Total Tests</div>
            </div>
        </div>
        
        <!-- Test Date -->
        <div class="info-box info">
            <i class="fa fa-clock-o"></i>
            <div><strong>Test executed:</strong> <?php echo $test_date; ?></div>
        </div>
        
        <!-- Database Connection -->
        <div class="test-section">
            <h2><i class="fa fa-plug"></i> Database Connection</h2>
            <?php echo getStatusBadge($results['db_connection']); ?>
        </div>
        
        <!-- Table Existence -->
        <div class="test-section">
            <h2><i class="fa fa-table"></i> Table Existence Check</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Status</th>
                        <th>Row Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableResults as $table => $info): ?>
                    <tr>
                        <td><code><?php echo $table; ?></code></td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <span class="status-badge status-pass"><i class="fa fa-check"></i> EXISTS</span>
                            <?php else: ?>
                                <span class="status-badge status-fail"><i class="fa fa-times"></i> MISSING</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($info['count']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Brand CRUD -->
        <div class="test-section">
            <h2><i class="fa fa-bookmark"></i> Brand Table CRUD</h2>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <h3>Create</h3>
                    <?php echo getStatusBadge($results['brand_create'] ?? null); ?>
                </div>
                <div>
                    <h3>Read</h3>
                    <?php echo getStatusBadge($results['brand_read'] ?? null); ?>
                </div>
                <div>
                    <h3>Update</h3>
                    <?php echo getStatusBadge($results['brand_update'] ?? null); ?>
                </div>
                <div>
                    <h3>Delete</h3>
                    <?php echo getStatusBadge($results['brand_delete'] ?? null); ?>
                </div>
            </div>
        </div>
        
        <!-- Category CRUD -->
        <div class="test-section">
            <h2><i class="fa fa-folder"></i> Category Table CRUD</h2>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <h3>Create</h3>
                    <?php echo getStatusBadge($results['category_create'] ?? null); ?>
                </div>
                <div>
                    <h3>Read</h3>
                    <?php echo getStatusBadge($results['category_read'] ?? null); ?>
                </div>
                <div>
                    <h3>Update</h3>
                    <?php echo getStatusBadge($results['category_update'] ?? null); ?>
                </div>
                <div>
                    <h3>Delete</h3>
                    <?php echo getStatusBadge($results['category_delete'] ?? null); ?>
                </div>
            </div>
        </div>
        
        <!-- Company CRUD -->
        <div class="test-section">
            <h2><i class="fa fa-building"></i> Company Table CRUD</h2>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <h3>Create</h3>
                    <?php echo getStatusBadge($results['company_create'] ?? null); ?>
                </div>
                <div>
                    <h3>Read</h3>
                    <?php echo getStatusBadge($results['company_read'] ?? null); ?>
                </div>
                <div>
                    <h3>Update</h3>
                    <?php echo getStatusBadge($results['company_update'] ?? null); ?>
                </div>
                <div>
                    <h3>Delete</h3>
                    <?php echo getStatusBadge($results['company_delete'] ?? null); ?>
                </div>
            </div>
        </div>
        
        <!-- User / Auth Tests -->
        <div class="test-section">
            <h2><i class="fa fa-users"></i> Authorization Table</h2>
            <div style="margin-bottom: 15px;">
                <h3>User Records</h3>
                <?php echo getStatusBadge($results['authorize_read']); ?>
            </div>
            <div>
                <h3>Password Migration</h3>
                <?php echo getStatusBadge($results['password_check']); ?>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="summary-box">
            <h3><i class="fa fa-bar-chart"></i> Test Summary</h3>
            <div class="summary-stats">
                <div class="summary-stat pass">
                    <div class="summary-stat-value"><?php echo $passed; ?></div>
                    <div class="summary-stat-label">Passed</div>
                </div>
                <div class="summary-stat warn">
                    <div class="summary-stat-value"><?php echo $warnings; ?></div>
                    <div class="summary-stat-label">Warnings</div>
                </div>
                <div class="summary-stat fail">
                    <div class="summary-stat-value"><?php echo $failed; ?></div>
                    <div class="summary-stat-label">Failed</div>
                </div>
            </div>
            
            <?php if ($failed == 0): ?>
            <div class="info-box success" style="margin-top: 20px; margin-bottom: 0;">
                <i class="fa fa-check-circle"></i>
                <div><strong>All critical CRUD operations are working correctly!</strong></div>
            </div>
            <?php else: ?>
            <div class="info-box danger" style="margin-top: 20px; margin-bottom: 0;">
                <i class="fa fa-exclamation-circle"></i>
                <div><strong>Some tests failed.</strong> Please check the details above.</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="?" class="btn-dev btn-primary"><i class="fa fa-refresh"></i> Run Tests Again</a>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        </div><!-- End content-container -->
    </div>
    <script><?php echo get_skeleton_js('pageContainer', 300); ?></script>
</body>
</html>
