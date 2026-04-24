<?php
namespace App\Helpers;

/**
 * FileUpload — Secure file upload validator
 *
 * Validates uploads using server-side finfo MIME detection,
 * extension whitelist, size limit, and cryptographic filenames.
 * Never trust $_FILES['type'] — it is client-controlled.
 */
class FileUpload
{
    // Allowed MIME types and their safe extensions
    private const ALLOWED = [
        'image' => [
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'exts'  => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        ],
        'document' => [
            'mimes' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'exts'  => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        ],
    ];

    /**
     * Validate and move an uploaded file securely.
     *
     * @param array  $file       Entry from $_FILES (e.g. $_FILES['logo'])
     * @param string $destDir    Destination directory (no trailing slash)
     * @param string $prefix     Filename prefix (e.g. 'logo', 'slip', 'exp')
     * @param string $type       'image' or 'document'
     * @param int    $maxBytes   Max file size in bytes (default 5 MB)
     * @return string            Saved filename on success, '' on failure
     */
    public static function save(
        array  $file,
        string $destDir,
        string $prefix = 'file',
        string $type   = 'document',
        int    $maxBytes = 5 * 1024 * 1024
    ): string {
        // 1. Basic upload error check
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        // 2. File size check
        if ($file['size'] > $maxBytes) {
            return '';
        }

        // 3. Server-side MIME detection (not from client)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = self::ALLOWED[$type] ?? self::ALLOWED['document'];
        if (!in_array($mimeType, $allowed['mimes'], true)) {
            return '';
        }

        // 4. For images — verify it is actually an image (header check)
        if (str_starts_with($mimeType, 'image/') && !getimagesize($file['tmp_name'])) {
            return '';
        }

        // 5. Determine safe extension from MIME (ignore client filename)
        $extMap = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'application/pdf' => 'pdf',
        ];
        $ext = $extMap[$mimeType] ?? 'bin';

        // 6. Ensure destination directory exists
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            return '';
        }

        // 7. Cryptographically random filename
        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = rtrim($destDir, '/') . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return '';
        }

        return $filename;
    }
}
