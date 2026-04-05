<?php
namespace App\Services;

class LogoGenerator
{
    private const COLORS = [
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

    private const SIZE = 200;

    private string $uploadDir;

    public function __construct(?string $uploadDir = null)
    {
        $this->uploadDir = $uploadDir ?? dirname(__DIR__, 2) . '/upload';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Extract initials from company names (max 3 chars)
     */
    public function extractInitials(string $nameShort, string $nameEn = ''): string
    {
        $source = $nameShort ?: $nameEn;
        if ($source === '') {
            return '?';
        }

        // Use name_sh directly if <= 3 ASCII chars
        if (mb_strlen($nameShort) <= 3 && preg_match('/^[A-Za-z0-9]+$/', $nameShort)) {
            return strtoupper($nameShort);
        }

        // Take first letter of each word (ASCII only)
        $initials = '';
        $words = preg_split('/[\s\-\_\.]+/', $source);
        foreach ($words as $word) {
            $word = trim($word);
            if ($word !== '' && preg_match('/^[A-Za-z]/', $word)) {
                $initials .= strtoupper($word[0]);
                if (mb_strlen($initials) >= 3) break;
            }
        }

        if ($initials === '') {
            $initials = mb_strtoupper(mb_substr(preg_replace('/[^\w]/u', '', $source), 0, 2));
        }

        return $initials ?: '?';
    }

    /**
     * Generate a logo PNG and return the filename
     */
    public function generate(string $initials, int $companyId): string
    {
        $size = self::SIZE;
        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);

        $color = self::COLORS[$companyId % count(self::COLORS)];
        $bg = imagecolorallocate($img, $color[0], $color[1], $color[2]);
        $white = imagecolorallocate($img, 255, 255, 255);

        imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

        $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        if (!file_exists($fontFile)) {
            $fontFile = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
        }

        if (file_exists($fontFile)) {
            // Dynamic sizing: scale text to fill ~75% of canvas
            $targetWidth = $size * 0.75;
            $targetHeight = $size * 0.55;
            $fontSize = 120; // start large

            // Shrink until text fits within target area
            do {
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $initials);
                $textWidth = $bbox[2] - $bbox[0];
                $textHeight = $bbox[1] - $bbox[7];
                if ($textWidth <= $targetWidth && $textHeight <= $targetHeight) break;
                $fontSize -= 2;
            } while ($fontSize > 10);

            $x = ($size - $textWidth) / 2 - $bbox[0];
            $y = ($size - $textHeight) / 2 - $bbox[7];
            imagettftext($img, $fontSize, 0, (int)$x, (int)$y, $white, $fontFile, $initials);
        } else {
            $fw = imagefontwidth(5) * strlen($initials);
            $fh = imagefontheight(5);
            $x = ($size - $fw) / 2;
            $y = ($size - $fh) / 2;
            imagestring($img, 5, (int)$x, (int)$y, $initials, $white);
        }

        $hash = md5($companyId . $initials . time() . mt_rand());
        $filename = "logo{$hash}.png";
        $filepath = $this->uploadDir . '/' . $filename;

        imagepng($img, $filepath, 6);
        imagedestroy($img);

        return $filename;
    }

    /**
     * Generate a logo for a company and return the filename
     * Convenience method combining extractInitials + generate
     */
    public function generateForCompany(int $companyId, string $nameShort, string $nameEn = ''): string
    {
        $initials = $this->extractInitials($nameShort, $nameEn);
        return $this->generate($initials, $companyId);
    }
}
