---
name: pdf-generation
description: 'Generate PDF documents for iACC using mPDF 8.x. USE FOR: creating invoice PDFs, quotation PDFs, receipt PDFs, tax invoice PDFs, delivery note PDFs, split invoice PDFs. Use when: building PDF templates, fixing PDF layout, adding Thai font support, formatting currency in PDFs, generating printable documents, customizing PDF headers/footers.'
argument-hint: 'Describe the PDF document type and what data to include'
---

# PDF Generation — iACC with mPDF

## When to Use

- Creating new PDF document templates
- Fixing existing PDF layouts
- Adding Thai text support to PDFs
- Formatting currency, dates, calculations in documents
- Customizing headers, footers, logos

## Architecture

```
app/Views/pdf/
├── invoice.php            # Invoice PDF
├── invoice-mail.php       # Invoice email attachment
├── quotation.php          # Quotation PDF
├── quotation-mail.php     # Quotation email attachment
├── receipt.php            # Receipt/Delivery note PDF
├── tax-invoice.php        # Tax Invoice PDF
├── tax-invoice-mail.php   # Tax Invoice email attachment
└── split-invoice.php      # Split Invoice PDF

inc/pdf-template.php       # Shared PDF template helper
vendor/mpdf/               # mPDF 8.x library (via Composer)
```

## Procedures

### 1. Create a New PDF Template

```php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/pdf-template.php';

// Query data
$id = intval($_GET['id'] ?? 0);
$sql = "SELECT po.*, pr.ven_id, pr.cus_id, pr.dis, pr.vat, pr.over
        FROM po
        JOIN pr ON po.pr_id = pr.id
        WHERE po.id = {$id}";
$data = mysqli_fetch_assoc(mysqli_query($db->conn, $sql));

// Query products
$sql_products = "SELECT p.*, m.des as model_desc
                 FROM product p
                 LEFT JOIN model m ON p.model = m.id
                 WHERE p.po_id = {$id}";
$products = mysqli_query($db->conn, $sql_products);

// Build HTML
$html = getPdfStyles();
$html .= buildPdfHeader($data, $vendor, $customer);
$html .= buildProductTable($products);
$html .= buildTotalsSection($data, $subtotal);
$html .= buildSignatureSection();

// Generate PDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 10,
    'margin_bottom' => 15,
    'margin_left' => 10,
    'margin_right' => 10,
    'default_font' => 'garuda'  // Thai font
]);

$mpdf->WriteHTML($html);
$mpdf->Output('document.pdf', 'I');  // I=inline, D=download
```

### 2. Thai Font Configuration

```php
// mPDF includes 'garuda' font for Thai — no additional setup needed
$mpdf = new \Mpdf\Mpdf([
    'default_font' => 'garuda',     // Thai-compatible font
    'mode' => 'utf-8',              // Required for Thai characters
]);

// Test Thai rendering: กขคงจ ฿ ภาษีมูลค่าเพิ่ม
```

### 3. Standard PDF Styles

```css
body {
  font-family: garuda, Arial, sans-serif;
  font-size: 11px;
}

.title {
  background: #1a5276;
  color: #fff;
  text-align: center;
  padding: 8px;
  font-size: 16px;
  letter-spacing: 2px;
}

.items {
  width: 100%;
  border-collapse: collapse;
}
.items th {
  background: #1a5276;
  color: #fff;
  padding: 6px 8px;
}
.items tr:nth-child(even) {
  background: #f8f9fa;
}

.totals {
  width: 220px;
  margin-left: auto;
  text-align: right;
}
.totals .grand {
  border-top: 2px solid #1a5276;
  font-weight: bold;
  color: #1a5276;
}
```

### 4. Calculation Pattern

```php
$subtotal = 0;
foreach ($products as $product) {
    $subtotal += $product['price'] * $product['quantity'];
}
$discount    = $subtotal * ($data['dis'] / 100);
$afterDiscount = $subtotal - $discount;
$overhead    = $afterDiscount * ($data['over'] / 100);
$vat         = ($afterDiscount + $overhead) * ($data['vat'] / 100);
$grandTotal  = $afterDiscount + $overhead + $vat;

// Format: number_format($grandTotal, 2) → "12,345.67"
// Thai words: bahtThai($grandTotal) → "หนึ่งหมื่นสองพันสามร้อย..."
```

### 5. Multi-Tenant Security in PDFs

```php
// ALWAYS filter by company_id in PDF queries
$com_id = intval($_SESSION['com_id']);
$sql = "SELECT * FROM po
        JOIN pr ON po.pr_id = pr.id
        WHERE po.id = {$id}
        AND (pr.cus_id = '{$com_id}' OR pr.ven_id = '{$com_id}' OR pr.payby = '{$com_id}')";
```

## Document Types

| Type          | Color Theme     | Status Required    |
| ------------- | --------------- | ------------------ |
| Invoice       | Blue `#1a5276`  | `pr.status >= 2`   |
| Tax Invoice   | Blue `#1a5276`  | `pr.status = 5`    |
| Quotation     | Blue `#1a5276`  | `pr.status >= 0`   |
| Receipt       | Green `#059669` | Delivery confirmed |
| Delivery Note | Green `#059669` | PO approved        |

## Output Modes

```php
$mpdf->Output('file.pdf', 'I');  // Inline display in browser
$mpdf->Output('file.pdf', 'D');  // Force download
$mpdf->Output('file.pdf', 'F');  // Save to server file
$mpdf->Output('file.pdf', 'S');  // Return as string (for email)
```
