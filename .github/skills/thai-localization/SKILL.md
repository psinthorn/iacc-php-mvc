---
name: thai-localization
description: 'Thai language and localization for iACC. USE FOR: Thai date formatting, Buddhist Era conversion, Thai currency (Baht) formatting, number-to-Thai-words conversion, Thai month names, bilingual UI strings, Thai tax rules (VAT PP30, WHT), Thai language detection, Thai font support. Use when: formatting dates for Thai users, converting amounts to Thai words, adding Thai translations, implementing Thai tax calculations, handling Thai text encoding.'
argument-hint: 'Describe the Thai localization feature needed'
---

# Thai Localization — iACC

## When to Use

- Formatting dates for Thai display (Buddhist Era)
- Converting amounts to Thai words (บาทถ้วน)
- Adding Thai UI translations
- Implementing Thai tax calculations (PP30, WHT)
- Detecting Thai language input
- Handling Thai text in PDFs

## Key Concepts

### Buddhist Era (พุทธศักราช)

```php
// Gregorian → Buddhist Era: add 543
$thai_year = date('Y') + 543;  // 2026 → 2569

// Display: 29 มีนาคม 2569
```

### Thai Months

```php
// Full month names
$thaiMonths = [
    1 => 'มกราคม',    2 => 'กุมภาพันธ์',   3 => 'มีนาคม',
    4 => 'เมษายน',    5 => 'พฤษภาคม',     6 => 'มิถุนายน',
    7 => 'กรกฎาคม',   8 => 'สิงหาคม',     9 => 'กันยายน',
    10 => 'ตุลาคม',   11 => 'พฤศจิกายน',  12 => 'ธันวาคม'
];

// Abbreviated month names
$thaiMonthsShort = [
    1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
    5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
    9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
];
```

## Procedures

### 1. Format Thai Date

```php
function formatThaiDate(string $date): string {
    $timestamp = strtotime($date);
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];

    $day = date('j', $timestamp);
    $month = $thaiMonths[(int)date('n', $timestamp)];
    $year = (int)date('Y', $timestamp) + 543;

    return "{$day} {$month} {$year}";
    // Output: "29 มีนาคม 2569"
}
```

### 2. Convert Amount to Thai Words

```php
// Location: inc/class.current.php → bahtThai()

// 12345.67 → "หนึ่งหมื่นสองพันสามร้อยสี่สิบห้าบาทหกสิบเจ็ดสตางค์"
// 1000.00  → "หนึ่งพันบาทถ้วน"

// Special Thai counting rules:
// - 1 at tens position = สิบ (not หนึ่งสิบ)
// - 1 at ones position (with digits before) = เอ็ด (not หนึ่ง)
// - 2 at tens position = ยี่สิบ (not สองสิบ)
// - 0 satang = "ถ้วน" (exact)

$thaiNum = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
$unitBaht = ['บาท', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
```

### 3. Thai Currency Formatting

```php
// Format with Baht symbol and thousands separator
function formatBaht(float $amount): string {
    return '฿' . number_format($amount, 2);
    // Output: "฿12,345.67"
}

// For PDFs/documents
function formatBahtText(float $amount): string {
    return number_format($amount, 2) . ' บาท';
    // Output: "12,345.67 บาท"
}
```

### 4. Language Detection

```php
// Location: ai/ai-language.php

function detectLanguage(string $text): string {
    // Count Thai characters (Unicode range: \x{0E00}-\x{0E7F})
    preg_match_all('/[\x{0E00}-\x{0E7F}]/u', $text, $thaiMatches);
    preg_match_all('/[a-zA-Z]/', $text, $engMatches);

    $thaiCount = count($thaiMatches[0]);
    $engCount = count($engMatches[0]);

    return ($thaiCount > $engCount) ? 'th' : 'en';
}
```

### 5. Bilingual UI Strings

```php
// Language files location:
// inc/lang/th.php — Thai strings
// inc/lang/en.php — English strings
// inc/string-th.xml — Thai XML strings (legacy)
// inc/string-us.xml — English XML strings (legacy)

// Usage in views:
$lang = include("inc/lang/{$_SESSION['language']}.php");
echo $lang['nav_features'];  // "คุณสมบัติ" or "Features"

// Adding new strings:
// inc/lang/th.php
return [
    'my_new_label' => 'ป้ายกำกับใหม่',
    // ...
];

// inc/lang/en.php
return [
    'my_new_label' => 'New Label',
    // ...
];
```

### 6. Thai Tax Calculations

```php
// VAT (ภาษีมูลค่าเพิ่ม) — Standard rate: 7%
$vat = $amount * 0.07;

// Withholding Tax (หัก ณ ที่จ่าย)
// Common rates:
// 1% — Advertising
// 2% — Transportation
// 3% — Services, Fees
// 5% — Rent

// PP30 (ภ.พ.30) — Monthly VAT Return
// Output VAT - Input VAT = Tax payable/refundable

// WHT Certificate (ภ.ง.ด.3 / ภ.ง.ด.53)
// Report withholding tax deducted at source
```

### 7. Thai PO/Invoice Number Format

```php
// Format: YY + padded sequential number
// YY = Thai short year (date("y") + 43 = Buddhist Era short year)
$thai_yy = date("y") + 43;  // 26 + 43 = 69 (= พ.ศ. 2569)
$po_number = $thai_yy . str_pad($id, 6, '0', STR_PAD_LEFT);
// Result: "69000042" (Year 2569, PO #42)
```

## File Locations

```
inc/lang/th.php          # Thai UI strings
inc/lang/en.php          # English UI strings
inc/string-th.xml        # Legacy Thai XML strings
inc/string-us.xml        # Legacy English XML strings
inc/class.current.php    # bahtThai() — number to Thai words
ai/ai-language.php       # Language detection, Thai date formatting
app/Models/TaxReport.php # Thai tax report generation (PP30, WHT)
app/Views/tax/           # Tax report views (dashboard, PP30, WHT)
```

## Important Notes

- Always use UTF-8 encoding for Thai text
- PDF font: `garuda` (included with mPDF)
- Database charset: `utf8mb4` for full Thai support
- Column sizes: Use VARCHAR(255)+ for Thai text (Thai chars use more bytes)
