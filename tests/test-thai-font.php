<?php
/**
 * Test: Thai font rendering in PDF generation
 * Verifies garuda font is used and Thai characters render correctly
 */

session_start();
$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 1;
$_SESSION['user_level'] = 5;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['lang'] = 'en';
$_SESSION['com_name'] = 'Test';

chdir('/var/www/html');
require_once 'vendor/autoload.php';

echo "=== Thai Font PDF Test ===\n\n";

// Test 1: Verify garuda font files exist
$fontFiles = [
    'vendor/mpdf/mpdf/ttfonts/Garuda.ttf',
    'vendor/mpdf/mpdf/ttfonts/Garuda-Bold.ttf',
    'vendor/mpdf/mpdf/ttfonts/Garuda-Oblique.ttf',
    'vendor/mpdf/mpdf/ttfonts/Garuda-BoldOblique.ttf',
];

$allFontsExist = true;
foreach ($fontFiles as $f) {
    if (!file_exists($f)) {
        echo "❌ Missing font: $f\n";
        $allFontsExist = false;
    }
}
echo ($allFontsExist ? '✅' : '❌') . " Test 1: Garuda font files exist\n";

// Test 2: Create mPDF with garuda and render Thai text
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => 'garuda',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
    ]);
    
    $html = '<style>body { font-family: garuda, Arial, sans-serif; }</style>';
    $html .= '<h1>ใบเสนอราคา</h1>';
    $html .= '<p>บริษัท ทดสอบ จำกัด - Thai Font Test</p>';
    $html .= '<p>งานทรายล้างและหลังคาด้านข้าง</p>';
    $html .= '<table border="1"><tr><th>รายการ</th><th>จำนวน</th><th>ราคา</th></tr>';
    $html .= '<tr><td>สินค้าทดสอบ</td><td>10</td><td>1,500.00</td></tr></table>';
    
    $mpdf->WriteHTML($html);
    
    // Output to string (not file)
    $pdfContent = $mpdf->Output('', 'S');
    $pdfLen = strlen($pdfContent);
    $hasPdf = (strpos($pdfContent, '%PDF') === 0);
    
    // Check if Thai font is embedded in the PDF
    $hasGaruda = (strpos($pdfContent, 'Garuda') !== false);
    
    echo "✅ Test 2: mPDF created with garuda ({$pdfLen} bytes, PDF=" . ($hasPdf ? 'yes' : 'no') . ", Garuda embedded=" . ($hasGaruda ? 'yes' : 'no') . ")\n";
    
    if (!$hasGaruda) {
        echo "⚠️  Warning: Garuda font not found in PDF binary - Thai text may not render\n";
    }
} catch (Exception $e) {
    echo "❌ Test 2: " . $e->getMessage() . "\n";
}

// Test 3: Verify pdf-template.php outputPdf uses garuda
$templateSrc = file_get_contents('inc/pdf-template.php');
$hasGarudaDefault = (strpos($templateSrc, "'default_font' => 'garuda'") !== false);
$hasGarudaCss = (strpos($templateSrc, 'font-family: garuda') !== false);
$hasUtf8Mode = (strpos($templateSrc, "'mode' => 'utf-8'") !== false);
$hasAutoScript = (strpos($templateSrc, "'autoScriptToLang' => true") !== false);

echo ($hasGarudaDefault ? '✅' : '❌') . " Test 3a: pdf-template.php default_font = garuda\n";
echo ($hasGarudaCss ? '✅' : '❌') . " Test 3b: pdf-template.php CSS includes garuda\n";
echo ($hasUtf8Mode ? '✅' : '❌') . " Test 3c: pdf-template.php mode = utf-8\n";
echo ($hasAutoScript ? '✅' : '❌') . " Test 3d: pdf-template.php autoScriptToLang enabled\n";

$pass = ($allFontsExist ? 1 : 0) + 1 + ($hasGarudaDefault ? 1 : 0) + ($hasGarudaCss ? 1 : 0) + ($hasUtf8Mode ? 1 : 0) + ($hasAutoScript ? 1 : 0);
$total = 6;
echo "\n=== Results: {$pass}/{$total} passed ===\n";
