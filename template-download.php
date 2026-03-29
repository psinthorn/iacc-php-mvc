<?php
/**
 * Template Download Handler
 * Zips and serves template folders for download
 * Usage: template-download.php?template=tour-company-demo
 */

$templateName = isset($_GET['template']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['template']) : '';

if (empty($templateName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Template name required']);
    exit;
}

$templateDir = __DIR__ . '/templates/' . $templateName;

if (!is_dir($templateDir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Template not found']);
    exit;
}

// Create zip
$zipFile = sys_get_temp_dir() . '/iacc-template-' . $templateName . '-' . time() . '.zip';
$zip = new ZipArchive();

if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot create zip file']);
    exit;
}

// Recursively add files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templateDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    $relativePath = $templateName . '/' . substr($file->getPathname(), strlen($templateDir) + 1);
    
    if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
    } else {
        $zip->addFile($file->getPathname(), $relativePath);
    }
}

$zip->close();

// Serve the file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="iacc-template-' . $templateName . '.zip"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: no-cache, no-store, must-revalidate');

readfile($zipFile);
unlink($zipFile);
exit;
