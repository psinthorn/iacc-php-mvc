<?php
/**
 * Bulk-generate initial-based logos for all companies missing one
 * Run: docker exec iacc_php php scripts/generate-logos.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/sys.configs.php';
require_once __DIR__ . '/../inc/class.dbconn.php';

use App\Services\LogoGenerator;

$db = new DbConn($config);
$conn = $db->conn;
$generator = new LogoGenerator();
$uploadDir = __DIR__ . '/../upload';

// Get all active companies
$result = $conn->query("SELECT id, name_sh, name_en, logo FROM company WHERE deleted_at IS NULL ORDER BY id");
if (!$result) {
    die("Query failed: " . $conn->error . "\n");
}

$updated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $companyId = (int)$row['id'];
    $nameShort = trim($row['name_sh'] ?? '');
    $nameEn = trim($row['name_en'] ?? '');
    $existingLogo = trim($row['logo'] ?? '');

    // Skip if logo already exists as a file
    if ($existingLogo && file_exists($uploadDir . '/' . $existingLogo)) {
        $skipped++;
        echo "SKIP #{$companyId} {$nameShort} — logo file exists\n";
        continue;
    }

    $source = $nameShort ?: $nameEn;
    if (!$source) {
        $skipped++;
        echo "SKIP #{$companyId} — no name\n";
        continue;
    }

    $initials = $generator->extractInitials($nameShort, $nameEn);
    $filename = $generator->generate($initials, $companyId);

    // Update database
    $stmt = $conn->prepare("UPDATE company SET logo = ? WHERE id = ?");
    $stmt->bind_param("si", $filename, $companyId);
    $stmt->execute();
    $stmt->close();

    $updated++;
    echo "OK #{$companyId} {$nameShort} → {$initials} → {$filename}\n";
}

echo "\nDone: {$updated} logos generated, {$skipped} skipped\n";
