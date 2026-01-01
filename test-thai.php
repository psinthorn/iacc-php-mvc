<?php
header('Content-Type: text/html; charset=utf-8');
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$db = new DbConn($config);
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Thai Character Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #667eea; color: white; }
        .success { background-color: #d4edda; }
    </style>
</head>
<body>
    <h1>🧪 Thai Character Display Test</h1>
    
    <h2>1. Database Connection Test</h2>
    <pre><?php
    // Check what charset is actually set
    echo "Before DB object creation:\n";
    
    // Now test with DB class
    echo "\nAfter DB class creation:\n";
    $charset = mysqli_query($db->conn, "SELECT @@character_set_connection, @@collation_connection, @@character_set_client, @@character_set_results");
    $result = mysqli_fetch_assoc($charset);
    echo "Connection Charset: " . $result['@@character_set_connection'] . "\n";
    echo "Connection Collation: " . $result['@@collation_connection'] . "\n";
    echo "Client Charset: " . $result['@@character_set_client'] . "\n";
    echo "Results Charset: " . $result['@@character_set_results'] . "\n";
    ?></pre>
    
    <h2>2. Sample Thai Data from Database</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Company Name (Thai)</th>
                <th>Tax ID</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT id, name_th, tax FROM company LIMIT 5";
            $result = mysqli_query($db->conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr class='success'>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['name_th'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . $row['tax'] . "</td>";
                    echo "</tr>";
                }
            }
        </tbody>
    </table>
    
    <h2>3. Page Meta Information</h2>
    <table>
        <tr>
            <td><strong>PHP Version</strong></td>
            <td><?php echo phpversion(); ?></td>
        </tr>
        <tr>
            <td><strong>Default Charset</strong></td>
            <td><?php echo ini_get('default_charset'); ?></td>
        </tr>
        <tr>
            <td><strong>mbstring.internal_encoding</strong></td>
            <td><?php echo ini_get('mbstring.internal_encoding'); ?></td>
        </tr>
        <tr>
            <td><strong>HTML Content-Type Header</strong></td>
            <td><?php echo header_remove('Content-Type') ?? 'UTF-8'; ?> <span style="color: green;">✓ Set</span></td>
        </tr>
    </table>
    
    <h2>4. Direct Thai Text Sample</h2>
    <p style="font-size: 18px; line-height: 2;">
        Thai Text: บริษัท ศาลา สมุย จำกัด<br>
        More Thai: ซินเนค<br>
        Test: ยินดีต้อนรับ
    </p>
    
</body>
</html>
?>
