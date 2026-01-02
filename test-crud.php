<?php
/**
 * CRUD Operations Test Script
 * Tests Create, Read, Update, Delete for all main tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");

$db = new DbConn($config);

echo "<html><head><title>CRUD Test Results</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .warn { color: orange; font-weight: bold; }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    h3 { color: #555; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #007bff; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
</style></head><body>";

echo "<h1>🧪 CRUD Operations Test - iAcc System</h1>";
echo "<p>Test Date: " . date('Y-m-d H:i:s') . "</p>";

$results = [];

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

// Helper to display result
function displayResult($result) {
    if ($result['status'] === 'PASS') {
        echo "<span class='pass'>✅ PASS</span>";
    } elseif ($result['status'] === 'WARN') {
        echo "<span class='warn'>⚠️ WARNING</span>";
    } else {
        echo "<span class='fail'>❌ FAIL</span>";
    }
    if (isset($result['message'])) {
        echo " - " . htmlspecialchars($result['message']);
    }
    if (isset($result['error'])) {
        echo "<br><pre>" . htmlspecialchars($result['error']) . "</pre>";
    }
}

// ============================================================
// 1. DATABASE CONNECTION TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>1. Database Connection</h2>";

$connTest = runTest('db_connection', function() use ($db) {
    if ($db->conn && !mysqli_connect_error()) {
        return ['status' => 'PASS', 'message' => 'Connected to MySQL successfully'];
    }
    return ['status' => 'FAIL', 'error' => mysqli_connect_error()];
});
displayResult($connTest);
echo "</div>";

// ============================================================
// 2. TABLE EXISTENCE CHECK
// ============================================================
echo "<div class='test-section'>";
echo "<h2>2. Table Existence Check</h2>";

$tables = ['brand', 'category', 'type', 'company', 'company_addr', 'payment', 'pr', 'po', 'authorize'];

echo "<table><tr><th>Table</th><th>Status</th><th>Row Count</th></tr>";
foreach ($tables as $table) {
    $result = mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM `$table`");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<tr><td>$table</td><td class='pass'>EXISTS</td><td>{$row['cnt']}</td></tr>";
    } else {
        echo "<tr><td>$table</td><td class='fail'>MISSING</td><td>-</td></tr>";
    }
}
echo "</table></div>";

// ============================================================
// 3. BRAND TABLE CRUD TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>3. Brand Table CRUD Test</h2>";

// CREATE
echo "<h3>CREATE</h3>";
$brandTest = runTest('brand_create', function() use ($db) {
    $testName = 'Test Brand ' . time();
    $sql = "INSERT INTO brand (brand_name, des, logo, ven_id) VALUES (?, ?, '', 0)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $testName, $testName);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created brand ID: $newId", 'id' => $newId, 'name' => $testName];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});
displayResult($brandTest);

// READ
echo "<h3>READ</h3>";
if (isset($brandTest['id'])) {
    $readTest = runTest('brand_read', function() use ($db, $brandTest) {
        $sql = "SELECT * FROM brand WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $brandTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read brand: {$row['brand_name']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    displayResult($readTest);
}

// UPDATE
echo "<h3>UPDATE</h3>";
if (isset($brandTest['id'])) {
    $updateTest = runTest('brand_update', function() use ($db, $brandTest) {
        $newName = 'Updated Brand ' . time();
        $sql = "UPDATE brand SET brand_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $brandTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($updateTest);
}

// DELETE
echo "<h3>DELETE</h3>";
if (isset($brandTest['id'])) {
    $deleteTest = runTest('brand_delete', function() use ($db, $brandTest) {
        $sql = "DELETE FROM brand WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $brandTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted brand ID: {$brandTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($deleteTest);
}
echo "</div>";

// ============================================================
// 4. CATEGORY TABLE CRUD TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>4. Category Table CRUD Test</h2>";

// CREATE
echo "<h3>CREATE</h3>";
$catTest = runTest('category_create', function() use ($db) {
    $testName = 'Test Category ' . time();
    $sql = "INSERT INTO category (cat_name, des) VALUES (?, ?)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $testName, $testName);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created category ID: $newId", 'id' => $newId];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});
displayResult($catTest);

// READ
echo "<h3>READ</h3>";
if (isset($catTest['id'])) {
    $readTest = runTest('category_read', function() use ($db, $catTest) {
        $sql = "SELECT * FROM category WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $catTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read category: {$row['cat_name']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    displayResult($readTest);
}

// UPDATE
echo "<h3>UPDATE</h3>";
if (isset($catTest['id'])) {
    $updateTest = runTest('category_update', function() use ($db, $catTest) {
        $newName = 'Updated Category ' . time();
        $sql = "UPDATE category SET cat_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $catTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($updateTest);
}

// DELETE
echo "<h3>DELETE</h3>";
if (isset($catTest['id'])) {
    $deleteTest = runTest('category_delete', function() use ($db, $catTest) {
        $sql = "DELETE FROM category WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $catTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted category ID: {$catTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($deleteTest);
}
echo "</div>";

// ============================================================
// 5. TYPE TABLE CRUD TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>5. Type (Product) Table CRUD Test</h2>";

// First get a category for FK
$catRow = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT id FROM category LIMIT 1"));
$catId = $catRow ? $catRow['id'] : 0;

// CREATE
echo "<h3>CREATE</h3>";
$typeTest = runTest('type_create', function() use ($db, $catId) {
    $testName = 'Test Type ' . time();
    $sql = "INSERT INTO type (name, des, cat_id) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $testName, $testName, $catId);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created type ID: $newId", 'id' => $newId];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});
displayResult($typeTest);

// READ
echo "<h3>READ</h3>";
if (isset($typeTest['id'])) {
    $readTest = runTest('type_read', function() use ($db, $typeTest) {
        $sql = "SELECT * FROM type WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $typeTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read type: {$row['name']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    displayResult($readTest);
}

// UPDATE
echo "<h3>UPDATE</h3>";
if (isset($typeTest['id'])) {
    $updateTest = runTest('type_update', function() use ($db, $typeTest) {
        $newName = 'Updated Type ' . time();
        $sql = "UPDATE type SET name = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $typeTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($updateTest);
}

// DELETE
echo "<h3>DELETE</h3>";
if (isset($typeTest['id'])) {
    $deleteTest = runTest('type_delete', function() use ($db, $typeTest) {
        $sql = "DELETE FROM type WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $typeTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted type ID: {$typeTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($deleteTest);
}
echo "</div>";

// ============================================================
// 6. COMPANY TABLE CRUD TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>6. Company Table CRUD Test</h2>";

// CREATE
echo "<h3>CREATE</h3>";
$compTest = runTest('company_create', function() use ($db) {
    $testName = 'Test Company ' . time();
    $sql = "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term) 
            VALUES (?, ?, ?, '', '', '', '', '', '1', '0', '', 30)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $testName, $testName, $testName);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created company ID: $newId", 'id' => $newId];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});
displayResult($compTest);

// READ
echo "<h3>READ</h3>";
if (isset($compTest['id'])) {
    $readTest = runTest('company_read', function() use ($db, $compTest) {
        $sql = "SELECT * FROM company WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $compTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read company: {$row['name_en']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    displayResult($readTest);
}

// UPDATE
echo "<h3>UPDATE</h3>";
if (isset($compTest['id'])) {
    $updateTest = runTest('company_update', function() use ($db, $compTest) {
        $newName = 'Updated Company ' . time();
        $sql = "UPDATE company SET name_en = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $compTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($updateTest);
}

// DELETE
echo "<h3>DELETE</h3>";
if (isset($compTest['id'])) {
    $deleteTest = runTest('company_delete', function() use ($db, $compTest) {
        $sql = "DELETE FROM company WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $compTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted company ID: {$compTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($deleteTest);
}
echo "</div>";

// ============================================================
// 7. PAYMENT TABLE CRUD TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>7. Payment Table CRUD Test</h2>";

// CREATE
echo "<h3>CREATE</h3>";
$payTest = runTest('payment_create', function() use ($db) {
    $testName = 'Test Payment ' . time();
    $sql = "INSERT INTO payment (payment_name, payment_des, com_id) VALUES (?, ?, 1)";
    $stmt = mysqli_prepare($db->conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $testName, $testName);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db->conn);
        return ['status' => 'PASS', 'message' => "Created payment ID: $newId", 'id' => $newId];
    }
    return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
});
displayResult($payTest);

// READ
echo "<h3>READ</h3>";
if (isset($payTest['id'])) {
    $readTest = runTest('payment_read', function() use ($db, $payTest) {
        $sql = "SELECT * FROM payment WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $payTest['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return ['status' => 'PASS', 'message' => "Read payment: {$row['payment_name']}"];
        }
        return ['status' => 'FAIL', 'error' => 'Record not found'];
    });
    displayResult($readTest);
}

// UPDATE
echo "<h3>UPDATE</h3>";
if (isset($payTest['id'])) {
    $updateTest = runTest('payment_update', function() use ($db, $payTest) {
        $newName = 'Updated Payment ' . time();
        $sql = "UPDATE payment SET payment_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newName, $payTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Updated to: $newName"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($updateTest);
}

// DELETE
echo "<h3>DELETE</h3>";
if (isset($payTest['id'])) {
    $deleteTest = runTest('payment_delete', function() use ($db, $payTest) {
        $sql = "DELETE FROM payment WHERE id = ?";
        $stmt = mysqli_prepare($db->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $payTest['id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            return ['status' => 'PASS', 'message' => "Deleted payment ID: {$payTest['id']}"];
        }
        return ['status' => 'FAIL', 'error' => mysqli_error($db->conn)];
    });
    displayResult($deleteTest);
}
echo "</div>";

// ============================================================
// 8. AUTHORIZE (USER) TABLE TEST
// ============================================================
echo "<div class='test-section'>";
echo "<h2>8. Authorize (User) Table Test</h2>";

// READ existing users
echo "<h3>READ Users</h3>";
$authTest = runTest('authorize_read', function() use ($db) {
    $sql = "SELECT usr_id, usr_name, level FROM authorize LIMIT 5";
    $result = mysqli_query($db->conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row['usr_name'];
        }
        return ['status' => 'PASS', 'message' => "Found " . count($users) . " users: " . implode(', ', $users)];
    }
    return ['status' => 'WARN', 'message' => 'No users found'];
});
displayResult($authTest);

// Check password_migrated column
echo "<h3>Password Migration Status</h3>";
$pwTest = runTest('password_check', function() use ($db) {
    $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN password_migrated = 1 THEN 1 ELSE 0 END) as migrated FROM authorize";
    $result = mysqli_query($db->conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total = $row['total'];
        $migrated = $row['migrated'] ?? 0;
        if ($migrated == $total && $total > 0) {
            return ['status' => 'PASS', 'message' => "All $total users migrated to bcrypt"];
        } elseif ($migrated > 0) {
            return ['status' => 'WARN', 'message' => "$migrated of $total users migrated"];
        } else {
            return ['status' => 'WARN', 'message' => "No users migrated yet (will migrate on next login)"];
        }
    }
    return ['status' => 'FAIL', 'error' => 'Could not check password status'];
});
displayResult($pwTest);
echo "</div>";

// ============================================================
// 9. DEPRECATED mysql_* FUNCTION CHECK
// ============================================================
echo "<div class='test-section'>";
echo "<h2>9. Deprecated Function Check</h2>";

$phpFiles = ['core-function.php', 'brand.php', 'brand-list.php', 'category.php', 'type.php', 'company.php'];
echo "<table><tr><th>File</th><th>mysql_query</th><th>mysqli_query</th><th>Status</th></tr>";

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $oldCount = preg_match_all('/\bmysql_query\b/', $content);
        $newCount = preg_match_all('/\bmysqli_query\b/', $content);
        
        $status = $oldCount > 0 ? "<span class='fail'>NEEDS UPDATE</span>" : "<span class='pass'>OK</span>";
        echo "<tr><td>$file</td><td>$oldCount</td><td>$newCount</td><td>$status</td></tr>";
    }
}
echo "</table></div>";

// ============================================================
// SUMMARY
// ============================================================
echo "<div class='test-section'>";
echo "<h2>📊 Test Summary</h2>";

$passed = 0;
$failed = 0;
$warnings = 0;

foreach ($results as $name => $result) {
    if ($result['status'] === 'PASS') $passed++;
    elseif ($result['status'] === 'WARN') $warnings++;
    else $failed++;
}

echo "<table>";
echo "<tr><th>Result</th><th>Count</th></tr>";
echo "<tr><td class='pass'>PASSED</td><td>$passed</td></tr>";
echo "<tr><td class='warn'>WARNINGS</td><td>$warnings</td></tr>";
echo "<tr><td class='fail'>FAILED</td><td>$failed</td></tr>";
echo "<tr><td><strong>TOTAL</strong></td><td><strong>" . count($results) . "</strong></td></tr>";
echo "</table>";

if ($failed == 0) {
    echo "<p class='pass'>✅ All critical CRUD operations are working!</p>";
} else {
    echo "<p class='fail'>❌ Some tests failed. Please check the details above.</p>";
}
echo "</div>";

echo "</body></html>";
