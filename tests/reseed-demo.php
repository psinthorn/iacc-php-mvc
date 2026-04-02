<?php
/**
 * Re-seed demo companies with full CompanySeeder data
 * Usage: docker exec iacc_php php /var/www/html/tests/reseed-demo.php
 */
require_once __DIR__ . '/../app/Services/CompanySeeder.php';

$conn = new mysqli('iacc_mysql', 'root', 'root', 'iacc');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

// Find the 3 demo companies
$result = $conn->query("SELECT id, name_en FROM company WHERE name_en LIKE '%Alpha Tech%' OR name_en LIKE '%Beta Supply%' OR name_en LIKE '%Gamma Design%' ORDER BY id ASC");

$companies = [];
$seen = [];
while ($row = $result->fetch_assoc()) {
    // Take only the first occurrence of each company name (skip duplicates)
    $key = $row['name_en'];
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $companies[] = $row;
    }
}

if (empty($companies)) {
    die("No demo companies found.\n");
}

echo "Found " . count($companies) . " demo companies:\n";
foreach ($companies as $c) {
    echo "  - ID {$c['id']}: {$c['name_en']}\n";
}
echo "\n";

$seeder = new \App\Services\CompanySeeder($conn);

// Disable FK checks for clean delete
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

foreach ($companies as $c) {
    $id = (int) $c['id'];
    echo "=== {$c['name_en']} (ID: {$id}) ===\n";

    // Clean existing seeder data
    $conn->query("DELETE FROM expense_categories WHERE com_id = {$id}");
    echo "  Cleaned expense_categories: {$conn->affected_rows} rows removed\n";

    $conn->query("DELETE FROM payment_method WHERE company_id = {$id}");
    echo "  Cleaned payment_method: {$conn->affected_rows} rows removed\n";

    $conn->query("DELETE FROM chart_of_accounts WHERE com_id = {$id}");
    echo "  Cleaned chart_of_accounts: {$conn->affected_rows} rows removed\n";

    // Re-seed
    $summary = $seeder->seedAll($id);
    echo "  Seeded expense_categories: {$summary['expense_categories']}\n";
    echo "  Seeded payment_methods: {$summary['payment_methods']}\n";
    echo "  Seeded chart_of_accounts: {$summary['chart_of_accounts']}\n";
    echo "\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Done! All 3 demo companies re-seeded with full CompanySeeder data.\n";
