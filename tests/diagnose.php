<?php
chdir(__DIR__ . "/.."); // Set working directory to project root
/**
 * iACC Diagnostic Tool for cPanel
 * Upload this to your hosting root and access it via browser
 * DELETE THIS FILE after debugging!
 * 
 * Usage: https://iacc.f2.co.th/diagnose.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<html><head><title>iACC Diagnostics</title>";
echo "<style>body{font-family:monospace;max-width:900px;margin:20px auto;background:#1a1a2e;color:#e0e0e0;padding:20px}";
echo "h1{color:#667eea}h2{color:#f0a500;border-bottom:1px solid #333;padding-bottom:5px}";
echo ".ok{color:#4caf50}.err{color:#f44336}.warn{color:#ff9800}";
echo "pre{background:#0d0d1a;padding:10px;border-radius:5px;overflow-x:auto;border:1px solid #333}";
echo ".box{background:#0d0d1a;padding:15px;border-radius:8px;margin:10px 0;border:1px solid #333}</style></head><body>";

echo "<h1>🔧 iACC Diagnostic Tool</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// ======= 1. PHP Version =======
echo "<h2>1. PHP Version</h2>";
$phpVer = phpversion();
echo "<div class='box'>PHP Version: <b>$phpVer</b> ";
if (version_compare($phpVer, '8.0', '>=')) {
    echo "<span class='warn'>⚠️ PHP 8.x - each() function removed, needs fixes</span>";
} elseif (version_compare($phpVer, '7.4', '>=')) {
    echo "<span class='ok'>✅ OK (7.4+)</span>";
} else {
    echo "<span class='err'>❌ Too old (needs 7.4+)</span>";
}
echo "</div>";

// ======= 2. Required Extensions =======
echo "<h2>2. PHP Extensions</h2>";
$required = ['mysqli', 'mbstring', 'session', 'json', 'simplexml', 'libxml'];
echo "<div class='box'>";
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? "<span class='ok'>✅" : "<span class='err'>❌") . " $ext</span><br>";
}
echo "</div>";

// ======= 3. Config File =======
echo "<h2>3. Configuration</h2>";
echo "<div class='box'>";
$configFile = __DIR__ . '/../inc/sys.configs.php';
if (file_exists($configFile)) {
    echo "<span class='ok'>✅ sys.configs.php exists</span><br>";
    
    // Check if it still has placeholder values
    $configContent = file_get_contents($configFile);
    if (strpos($configContent, 'YOUR_CPANEL_USERNAME') !== false) {
        echo "<span class='err'>❌ Config still has placeholder values (YOUR_CPANEL_USERNAME)! Update database credentials.</span><br>";
    } else {
        echo "<span class='ok'>✅ Config appears customized</span><br>";
    }
    
    if (strpos($configContent, '"mysql"') !== false || strpos($configContent, "'mysql'") !== false) {
        echo "<span class='err'>❌ Config uses Docker hostname 'mysql' - should be 'localhost' for cPanel!</span><br>";
    }
} else {
    echo "<span class='err'>❌ sys.configs.php NOT FOUND - rename sys.configs.cpanel.php to sys.configs.php</span><br>";
}

// Check cpanel config availability
if (file_exists(__DIR__ . '/../inc/sys.configs.cpanel.php')) {
    echo "<span class='ok'>✅ sys.configs.cpanel.php available as template</span><br>";
}
echo "</div>";

// ======= 4. Database Connection =======
echo "<h2>4. Database Connection</h2>";
echo "<div class='box'>";
try {
    // Load config
    $config = [];
    // Manually parse config to avoid side effects
    $configLines = file_get_contents($configFile);
    preg_match_all('/\$config\["(\w+)"\]\s*=\s*"([^"]*)"/', $configLines, $matches);
    for ($i = 0; $i < count($matches[1]); $i++) {
        $config[$matches[1][$i]] = $matches[2][$i];
    }
    
    echo "Host: <b>" . ($config['hostname'] ?? 'NOT SET') . "</b><br>";
    echo "User: <b>" . ($config['username'] ?? 'NOT SET') . "</b><br>";
    echo "DB: <b>" . ($config['dbname'] ?? 'NOT SET') . "</b><br>";
    
    if (!empty($config['hostname']) && !empty($config['username'])) {
        $conn = @mysqli_connect($config['hostname'], $config['username'], $config['password'] ?? '', $config['dbname'] ?? '');
        if ($conn) {
            echo "<span class='ok'>✅ Database connection successful!</span><br>";
            
            // Check required tables
            $tables_needed = ['company', 'company_addr', 'keep_log', 'users'];
            $result = mysqli_query($conn, "SHOW TABLES");
            $existing = [];
            while ($row = mysqli_fetch_row($result)) {
                $existing[] = $row[0];
            }
            echo "Tables found: <b>" . count($existing) . "</b><br>";
            foreach ($tables_needed as $t) {
                if (in_array($t, $existing)) {
                    echo "<span class='ok'>  ✅ $t</span><br>";
                } else {
                    echo "<span class='err'>  ❌ $t MISSING - need to import SQL</span><br>";
                }
            }
            
            // Check company table structure
            if (in_array('company', $existing)) {
                $cols = mysqli_query($conn, "SHOW COLUMNS FROM company");
                $colNames = [];
                while ($col = mysqli_fetch_assoc($cols)) {
                    $colNames[] = $col['Field'];
                }
                echo "<br>Company columns: " . implode(', ', $colNames) . "<br>";
                
                // Check for company_id column (multi-tenant)
                if (!in_array('company_id', $colNames)) {
                    echo "<span class='warn'>⚠️ 'company_id' column missing in company table</span><br>";
                }
                if (!in_array('deleted_at', $colNames)) {
                    echo "<span class='warn'>⚠️ 'deleted_at' column missing in company table</span><br>";
                }
            }
            
            mysqli_close($conn);
        } else {
            echo "<span class='err'>❌ Connection FAILED: " . mysqli_connect_error() . "</span><br>";
        }
    } else {
        echo "<span class='err'>❌ Config values missing or not parseable</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='err'>❌ Error: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// ======= 5. Directory Permissions =======
echo "<h2>5. Directory Permissions</h2>";
echo "<div class='box'>";
$dirs = [
    'logs' => __DIR__ . '/../logs',
    'upload' => __DIR__ . '/../upload',
    'file' => __DIR__ . '/../file',
    'cache' => __DIR__ . '/../cache',
    'inc' => __DIR__ . '/../inc',
];
foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        $writable = is_writable($path);
        echo ($writable ? "<span class='ok'>✅" : "<span class='err'>❌") . " /$name/ - " . ($writable ? "writable" : "NOT WRITABLE") . "</span><br>";
    } else {
        echo "<span class='warn'>⚠️ /$name/ does not exist</span>";
        // Try to create it
        if (@mkdir($path, 0755, true)) {
            echo " → <span class='ok'>Created!</span>";
        } else {
            echo " → <span class='err'>Could not create</span>";
        }
        echo "<br>";
    }
}
echo "</div>";

// ======= 6. Error Log Contents =======
echo "<h2>6. Error Logs</h2>";
$logFiles = [
    'logs/php_errors.log',
    'logs/app.log',
    'logs/error.log',
    'error.log',
    'php-error.log',
];
echo "<div class='box'>";
foreach ($logFiles as $lf) {
    $fullPath = __DIR__ . '/../' . $lf;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "<b>📄 $lf</b> (" . number_format($size) . " bytes)<br>";
        // Show last 20 lines
        $content = file_get_contents($fullPath);
        $lines = explode("\n", trim($content));
        $last = array_slice($lines, -20);
        echo "<pre>" . htmlspecialchars(implode("\n", $last)) . "</pre>";
    } else {
        echo "<span class='warn'>⚠️ $lf not found</span><br>";
    }
}
echo "</div>";

// ======= 7. each() Function Check =======
echo "<h2>7. Deprecated Function Check</h2>";
echo "<div class='box'>";
if (function_exists('each')) {
    echo "<span class='ok'>✅ each() function available (PHP < 8.0)</span><br>";
} else {
    echo "<span class='warn'>⚠️ each() NOT available (PHP 8.0+) - files need update</span><br>";
    
    // Scan for remaining each() usage
    $filesToCheck = glob(__DIR__ . '/../*.php');
    $filesToCheck = array_merge($filesToCheck, glob(__DIR__ . '/../inc/*.php'));
    $found = [];
    foreach ($filesToCheck as $f) {
        $content = file_get_contents($f);
        if (preg_match('/each\s*\(/', $content)) {
            $lines = explode("\n", $content);
            foreach ($lines as $num => $line) {
                if (preg_match('/\beach\s*\(/', $line) && strpos($line, '//') === false) {
                    $found[] = basename($f) . ":" . ($num+1) . " → " . trim($line);
                }
            }
        }
    }
    if ($found) {
        echo "<span class='err'>❌ Files still using each():</span><pre>" . htmlspecialchars(implode("\n", $found)) . "</pre>";
    } else {
        echo "<span class='ok'>✅ No remaining each() calls found</span><br>";
    }
}
echo "</div>";

// ======= 8. Session Test =======
echo "<h2>8. Session & Security</h2>";
echo "<div class='box'>";
session_start();
echo "Session save path: <b>" . (session_save_path() ?: 'default') . "</b><br>";
echo "Session ID: <b>" . session_id() . "</b><br>";
echo "Session status: <b>" . (session_status() === PHP_SESSION_ACTIVE ? 'Active ✅' : 'Inactive ❌') . "</b><br>";

if (isset($_SESSION['user_id'])) {
    echo "Logged in as: user_id=<b>" . htmlspecialchars($_SESSION['user_id']) . "</b>, com_id=<b>" . htmlspecialchars($_SESSION['com_id'] ?? 'not set') . "</b><br>";
} else {
    echo "<span class='warn'>⚠️ Not logged in - login first to test company creation</span><br>";
}

// Check CSRF
if (isset($_SESSION['csrf_token'])) {
    echo "CSRF token: <span class='ok'>✅ Set</span><br>";
} else {
    echo "CSRF token: <span class='warn'>⚠️ Not set (will be created on first page load)</span><br>";
}
echo "</div>";

// ======= 9. File Existence Check =======
echo "<h2>9. Critical Files</h2>";
echo "<div class='box'>";
$criticalFiles = [
    'index.php',
    'inc/sys.configs.php',
    'inc/class.dbconn.php',
    'inc/class.hard.php',
    'inc/security.php',
    'inc/class.company_filter.php',
    'inc/error-handler.php',
    'inc/string-us.xml',
    'inc/string-th.xml',
];
foreach ($criticalFiles as $f) {
    $exists = file_exists(__DIR__ . '/../' . $f);
    echo ($exists ? "<span class='ok'>✅" : "<span class='err'>❌") . " $f</span><br>";
}
echo "</div>";

// ======= 10. Quick Simulation =======
echo "<h2>10. Quick MVC Simulation</h2>";
echo "<div class='box'>";
echo "Simulating require chain for MVC controllers...<br>";
try {
    // Test loading config
    $testConfig = [];
    @include(__DIR__ . '/../inc/sys.configs.php');
    echo "<span class='ok'>✅ sys.configs.php loaded</span><br>";
    
    // Test DB connection class
    if (file_exists(__DIR__ . '/../inc/class.dbconn.php')) {
        // Check if simplexml can load language file
        $langFile = __DIR__ . '/../inc/string-us.xml';
        if (file_exists($langFile)) {
            $xml = @simplexml_load_file($langFile, "SimpleXMLElement", LIBXML_NOCDATA);
            if ($xml) {
                echo "<span class='ok'>✅ Language XML loaded</span><br>";
            } else {
                echo "<span class='err'>❌ Failed to parse string-us.xml</span><br>";
            }
        } else {
            echo "<span class='err'>❌ string-us.xml not found</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='err'>❌ Error: " . $e->getMessage() . "</span><br>";
} catch (Error $e) {
    echo "<span class='err'>❌ Fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "</span><br>";
}
echo "</div>";

echo "<br><p style='color:#f44336'><b>⚠️ DELETE this file (diagnose.php) after debugging!</b></p>";
echo "</body></html>";
?>
