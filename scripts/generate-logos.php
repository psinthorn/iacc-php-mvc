<?php
/**
 * Generate initial-based logos for all companies and update DB
 * Run: docker exec iacc_php php scripts/generate-logos.php
 */

require_once __DIR__ . '/../inc/sys.configs.php';
require_once __DIR__ . '/../inc/class.dbconn.php';

$db = new DbConn($config);
$conn = $db->conn;

// Color palette for logo backgrounds
$colors = [
    [102, 126, 234], // indigo
    [118, 75, 162],  // purple
    [237, 100, 166], // pink
    [246, 153, 63],  // orange
    [72, 187, 120],  // green
    [56, 178, 172],  // teal
    [66, 153, 225],  // blue
    [229, 62, 62],   // red
    [49, 130, 206],  // dark blue
    [214, 158, 46],  // gold
    [128, 90, 213],  // violet
    [56, 161, 105],  // dark green
    [221, 107, 32],  // dark orange
    [190, 75, 219],  // magenta
    [45, 135, 135],  // dark teal
];

$uploadDir = __DIR__ . '/../upload';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get all active companies
$result = $conn->query("SELECT id, name_sh, name_en, logo FROM company WHERE deleted_at IS NULL ORDER BY id");
if (!$result) {
    die("Query failed: " . $conn->error . "\n");
}

$updated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $companyId = $row['id'];
    $nameShort = trim($row['name_sh'] ?? '');
    $nameEn = trim($row['name_en'] ?? '');
    $existingLogo = trim($row['logo'] ?? '');

    // Skip if logo already exists as a file
    if ($existingLogo && file_exists($uploadDir . '/' . $existingLogo)) {
        $skipped++;
        echo "SKIP #{$companyId} {$nameShort} — logo file exists\n";
        continue;
    }

    // Get initials (max 3 chars) from short name, fallback to english name
    $source = $nameShort ?: $nameEn;
    if (!$source) {
        $skipped++;
        echo "SKIP #{$companyId} — no name\n";
        continue;
    }

    // Extract initials: use name_sh directly if <= 3 chars, otherwise take first letters of words
    $initials = '';
    if (mb_strlen($nameShort) <= 3 && preg_match('/^[A-Za-z0-9]+$/', $nameShort)) {
        $initials = strtoupper($nameShort);
    } else {
        // Take first letter of each word (ASCII only)
        $words = preg_split('/[\s\-\_\.]+/', $source);
        foreach ($words as $word) {
            $word = trim($word);
            if ($word !== '' && preg_match('/^[A-Za-z]/', $word)) {
                $initials .= strtoupper($word[0]);
                if (mb_strlen($initials) >= 3) break;
            }
        }
    }

    if (!$initials) {
        // Fallback: use first 2 chars of short name
        $initials = mb_strtoupper(mb_substr(preg_replace('/[^\w]/u', '', $source), 0, 2));
    }
    if (!$initials) {
        $skipped++;
        echo "SKIP #{$companyId} {$nameShort} — cannot extract initials\n";
        continue;
    }

    // Generate logo image (200x200)
    $size = 200;
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // Pick color based on company ID
    $color = $colors[$companyId % count($colors)];
    $bg = imagecolorallocate($img, $color[0], $color[1], $color[2]);
    $white = imagecolorallocate($img, 255, 255, 255);

    // Fill with rounded-feel solid color
    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

    // Draw initials centered
    $fontSize = strlen($initials) <= 2 ? 60 : 42;

    // Use built-in font (no TTF needed)
    // For better quality, use imagestring with large built-in font
    // But for proper centering, we use imagettftext if a font is available
    $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    if (!file_exists($fontFile)) {
        $fontFile = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
    }

    if (file_exists($fontFile)) {
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $initials);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $x = ($size - $textWidth) / 2 - $bbox[0];
        $y = ($size - $textHeight) / 2 - $bbox[7];
        imagettftext($img, $fontSize, 0, (int)$x, (int)$y, $white, $fontFile, $initials);
    } else {
        // Fallback: use built-in font 5 (largest)
        $fw = imagefontwidth(5) * strlen($initials);
        $fh = imagefontheight(5);
        $x = ($size - $fw) / 2;
        $y = ($size - $fh) / 2;
        imagestring($img, 5, (int)$x, (int)$y, $initials, $white);
    }

    // Save as PNG
    $hash = md5($companyId . $nameShort . time());
    $filename = "logo{$hash}.png";
    $filepath = $uploadDir . '/' . $filename;

    imagepng($img, $filepath, 6);
    imagedestroy($img);

    // Update database
    $stmt = $conn->prepare("UPDATE company SET logo = ? WHERE id = ?");
    $stmt->bind_param("si", $filename, $companyId);
    $stmt->execute();
    $stmt->close();

    $updated++;
    echo "OK #{$companyId} {$nameShort} → {$initials} → {$filename}\n";
}

echo "\nDone: {$updated} logos generated, {$skipped} skipped\n";
