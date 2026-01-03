<?php
/**
 * Docker Test Script
 * Test if Docker socket is accessible
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Docker Socket Test</h2>";

// Check socket
$socket = '/var/run/docker.sock';
echo "<p><strong>Socket path:</strong> $socket</p>";
echo "<p><strong>Socket exists:</strong> " . (file_exists($socket) ? '✅ YES' : '❌ NO') . "</p>";

if (file_exists($socket)) {
    echo "<p><strong>Socket readable:</strong> " . (is_readable($socket) ? '✅ YES' : '❌ NO') . "</p>";
    
    // Test API call using shell curl (more reliable than PHP curl for Unix sockets)
    echo "<h3>Testing Docker API via shell curl...</h3>";
    
    $output = [];
    exec("curl -s --unix-socket $socket http://localhost/version 2>&1", $output, $return_var);
    $response = implode('', $output);
    
    echo "<p><strong>Exit code:</strong> $return_var</p>";
    
    if ($response) {
        $version = json_decode($response, true);
        if ($version) {
            echo "<p><strong>Docker Version:</strong> " . ($version['Version'] ?? 'Unknown') . "</p>";
            echo "<p><strong>API Version:</strong> " . ($version['ApiVersion'] ?? 'Unknown') . "</p>";
            $apiVersion = $version['ApiVersion'] ?? '1.43';
        } else {
            echo "<p style='color:red;'>Failed to parse version response</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 300)) . "</pre>";
            $apiVersion = '1.43';
        }
    } else {
        echo "<p style='color:red;'>No response from Docker API</p>";
        $apiVersion = '1.43';
    }
    
    // Test containers list
    echo "<h3>Containers:</h3>";
    
    $output = [];
    exec("curl -s --unix-socket $socket 'http://localhost/v$apiVersion/containers/json?all=true' 2>&1", $output, $return_var);
    $response = implode('', $output);
    
    if ($response) {
        $containers = json_decode($response, true);
        if (is_array($containers)) {
            echo "<table border='1' cellpadding='8'>";
            echo "<tr><th>Name</th><th>Image</th><th>State</th><th>Status</th></tr>";
            foreach ($containers as $c) {
                $name = ltrim($c['Names'][0] ?? '', '/');
                $image = $c['Image'] ?? '';
                $state = $c['State'] ?? '';
                $status = $c['Status'] ?? '';
                $color = $state === 'running' ? 'green' : 'red';
                echo "<tr><td>$name</td><td>$image</td><td style='color:$color;'>$state</td><td>$status</td></tr>";
            }
            echo "</table>";
            echo "<p style='color:green; font-weight:bold;'>✅ Docker API is working! You can now use the Container Monitor.</p>";
            echo "<p><a href='index.php?page=containers'>Go to Container Monitor →</a></p>";
        } else {
            echo "<p style='color:red;'>Failed to parse containers</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    } else {
        echo "<p style='color:red;'>No response from containers API</p>";
    }
} else {
    echo "<p style='color:red;'>Docker socket not found. Make sure it's mounted in docker-compose.yml:</p>";
    echo "<pre>volumes:
  - /var/run/docker.sock:/var/run/docker.sock</pre>";
}
