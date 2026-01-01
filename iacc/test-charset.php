<?php
// Test mysqli_set_charset directly
$conn = mysqli_connect('mysql', 'root', 'root', 'iacc');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Initial charset:\n";
$result = mysqli_query($conn, "SELECT @@character_set_connection, @@character_set_client, @@character_set_results");
$row = mysqli_fetch_assoc($result);
var_dump($row);

echo "\n\nSetting charset to utf8mb4...\n";
$set_result = mysqli_set_charset($conn, "utf8mb4");
echo "mysqli_set_charset result: " . ($set_result ? "TRUE" : "FALSE") . "\n";

echo "\nAfter setting charset:\n";
$result = mysqli_query($conn, "SELECT @@character_set_connection, @@character_set_client, @@character_set_results");
$row = mysqli_fetch_assoc($result);
var_dump($row);

// Now test querying Thai data
echo "\n\nThai Data Test:\n";
$query = "SELECT id, name_th FROM company LIMIT 3";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name_th'] . "\n";
    }
}

mysqli_close($conn);
