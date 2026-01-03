<?php
/**
 * Test container list - debugging duplicate issue
 */

$docker_socket = '/var/run/docker.sock';

// Get API version
$output = [];
exec("curl -s --unix-socket $docker_socket http://localhost/version 2>&1", $output, $return_var);
$response = implode('', $output);
$version = json_decode($response, true);
$apiVersion = $version['ApiVersion'] ?? '1.43';

// Get containers
$cmd = "curl -s --unix-socket $docker_socket 'http://localhost/v$apiVersion/containers/json?all=true' 2>&1";
$output = [];
exec($cmd, $output, $return_var);
$response = implode("\n", $output);
$containers = json_decode($response, true);

echo "<h2>Raw Container Data</h2>";
echo "<p>API Version: $apiVersion</p>";
echo "<p>Total containers: " . count($containers) . "</p>";

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>#</th><th>ID</th><th>Name</th><th>Image</th><th>State</th></tr>";

foreach ($containers as $i => $c) {
    $id = substr($c['Id'], 0, 12);
    $name = ltrim($c['Names'][0] ?? '', '/');
    $image = $c['Image'];
    $state = $c['State'];
    echo "<tr>";
    echo "<td>$i</td>";
    echo "<td>$id</td>";
    echo "<td>$name</td>";
    echo "<td>$image</td>";
    echo "<td>$state</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Container IDs (checking for duplicates)</h2>";
$ids = [];
$names = [];
foreach ($containers as $c) {
    $ids[] = $c['Id'];
    $names[] = ltrim($c['Names'][0] ?? '', '/');
}

echo "<p>Unique IDs: " . count(array_unique($ids)) . " / " . count($ids) . "</p>";
echo "<p>Unique Names: " . count(array_unique($names)) . " / " . count($names) . "</p>";

if (count($ids) !== count(array_unique($ids))) {
    echo "<p style='color:red;'>DUPLICATE IDs FOUND!</p>";
}
if (count($names) !== count(array_unique($names))) {
    echo "<p style='color:red;'>DUPLICATE NAMES FOUND!</p>";
}

echo "<h2>All Names</h2><pre>";
print_r($names);
echo "</pre>";
