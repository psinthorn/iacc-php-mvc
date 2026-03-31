---
name: multi-language
description: 'Multi-language (i18n) support for iACC. USE FOR: adding translation keys, creating bilingual UI, language file management, translation function usage, language switching, new module localization. Use when: building any new module or page, adding UI text, creating forms with labels, building views with user-facing strings. EVERY new module MUST support multi-language from the beginning.'
argument-hint: 'Describe the module or feature needing multi-language support'
---

# Multi-Language (i18n) — iACC

## When to Use

- **EVERY** new module, page, view, or feature — i18n is mandatory from day one
- Adding any user-facing text (labels, buttons, headings, messages, placeholders)
- Creating public landing pages or in-app views
- Building forms, tables, error messages, or notifications

## CRITICAL RULE

**Never hardcode user-facing strings.** Every string shown to a user MUST go through the translation system. This applies to:
- Page titles, headings, section labels
- Form labels, placeholders, validation messages
- Button text, link text
- Table headers
- Success/error/info messages
- Tooltip text, help text
- Email subjects and body text

## Two Translation Systems

iACC has two distinct i18n systems depending on context:

### System 1: Language Files (Public/Landing Pages)

**Files:** `inc/lang/en.php`, `inc/lang/th.php`
**Session:** `$_SESSION['landing_lang']` (string: `'en'` or `'th'`)
**Function:** `__('key_name')`

```php
// inc/lang/en.php
return [
    'module_page_title' => 'Page Title',
    'module_form_label' => 'Label Text',
    'module_btn_submit' => 'Submit',
];

// inc/lang/th.php
return [
    'module_page_title' => 'ชื่อหน้า',
    'module_form_label' => 'ข้อความป้ายกำกับ',
    'module_btn_submit' => 'ส่ง',
];
```

**Page setup pattern:**
```php
<?php
// Language handling
$lang = $_GET['lang'] ?? $_SESSION['landing_lang'] ?? 'en';
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

// Load language file
$t = require __DIR__ . '/inc/lang/' . $lang . '.php';

// Translation helper
function __($key) {
    global $t;
    return isset($t[$key]) ? $t[$key] : $key;
}
?>

<!-- Usage in HTML -->
<h1><?= __('module_page_title') ?></h1>
<label><?= __('module_form_label') ?></label>
<button><?= __('module_btn_submit') ?></button>
```

### System 2: Session-Based (In-App MVC Views)

**Session:** `$_SESSION['lang']` (integer: `0` = English, `1` = Thai)
**Database:** `authorize.lang` column (TINYINT)
**Switcher:** Sidebar buttons → `index.php?page=lang_switch`

```php
// In-app views use session-based language detection
$isThaiLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1);

// Pattern for bilingual database fields
$displayName = ($isThaiLang && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];

// Pattern for inline translations
$pageTitle = $isThaiLang ? 'รายการสินค้า' : 'Product List';
$btnSave = $isThaiLang ? 'บันทึก' : 'Save';
```

**For new MVC modules — use the helper approach:**
```php
// At the top of the view file
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'Products',
        'btn_add'    => 'Add New',
        'btn_save'   => 'Save',
        'btn_cancel' => 'Cancel',
        'col_name'   => 'Product Name',
        'col_price'  => 'Price',
        'msg_saved'  => 'Saved successfully',
        'msg_deleted'=> 'Deleted successfully',
    ],
    'th' => [
        'page_title' => 'สินค้า',
        'btn_add'    => 'เพิ่มใหม่',
        'btn_save'   => 'บันทึก',
        'btn_cancel' => 'ยกเลิก',
        'col_name'   => 'ชื่อสินค้า',
        'col_price'  => 'ราคา',
        'msg_saved'  => 'บันทึกสำเร็จ',
        'msg_deleted'=> 'ลบสำเร็จ',
    ]
];
$t = $labels[$lang];

// Usage
<h1><?= $t['page_title'] ?></h1>
<button class="btn btn-primary"><?= $t['btn_add'] ?></button>
```

## Key Naming Convention

**Pattern:** `{section}_{entity}_{attribute}`

```
nav_features          → navigation, features link
feature_1_title       → features section, item 1, title
pricing_pro_feature_1 → pricing section, pro plan, feature 1
login_email           → login page, email field
module_btn_submit     → module section, submit button
module_col_name       → module section, name column header
module_msg_saved      → module section, saved message
```

**Prefixes by type:**
| Prefix | Usage | Example |
|--------|-------|---------|
| `nav_` | Navigation links | `nav_products` |
| `btn_` | Buttons | `btn_save`, `btn_cancel` |
| `col_` | Table column headers | `col_name`, `col_price` |
| `msg_` | Messages (success/error) | `msg_saved`, `msg_error` |
| `lbl_` | Form labels | `lbl_email`, `lbl_phone` |
| `ph_`  | Placeholders | `ph_search`, `ph_enter_name` |
| `ttl_` | Page/section titles | `ttl_products`, `ttl_settings` |
| `err_` | Validation errors | `err_required`, `err_invalid` |
| `confirm_` | Confirmation dialogs | `confirm_delete` |

## Procedures

### 1. Add Translations for a New Public Page

**Step 1:** Add keys to `inc/lang/en.php`:
```php
// My Module Section
'mymodule_title' => 'My Module',
'mymodule_subtitle' => 'Description of module',
'mymodule_btn_action' => 'Take Action',
```

**Step 2:** Add matching keys to `inc/lang/th.php`:
```php
// My Module Section
'mymodule_title' => 'โมดูลของฉัน',
'mymodule_subtitle' => 'คำอธิบายโมดูล',
'mymodule_btn_action' => 'ดำเนินการ',
```

**Step 3:** Use in page:
```php
<h1><?= __('mymodule_title') ?></h1>
```

**Step 4:** Verify both languages work via `?lang=en` and `?lang=th`.

### 2. Add Translations for a New In-App MVC View

**Step 1:** Define `$labels` array at the top of the view with `'en'` and `'th'` keys.

**Step 2:** Detect language:
```php
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$t = $labels[$lang];
```

**Step 3:** Use `$t['key']` everywhere in the view. Never use raw strings.

**Step 4:** For database fields with dual columns (e.g., `name` + `name_th`):
```php
$displayName = ($lang === 'th' && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];
```

### 3. Add Bilingual Database Fields

When a database table has user-visible text that differs by language:

```sql
ALTER TABLE products 
    ADD COLUMN name_th VARCHAR(255) DEFAULT NULL AFTER name,
    ADD COLUMN description_th TEXT DEFAULT NULL AFTER description;
```

Display pattern:
```php
$name = ($isThaiLang && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];
```

### 4. Language Switcher

**Public pages** — URL parameter:
```html
<a href="?lang=en">English</a> | <a href="?lang=th">ภาษาไทย</a>
```

**In-app** — Sidebar form (already built in `app/Views/layouts/sidebar.php`):
```php
<form action="index.php?page=lang_switch" method="post">
    <button name="chlang" value="0">English</button>
    <button name="chlang" value="1">ภาษาไทย</button>
</form>
```

## Common Translation Strings

Reuse these standard keys across modules:

```php
// English
'btn_save' => 'Save',
'btn_cancel' => 'Cancel',
'btn_delete' => 'Delete',
'btn_edit' => 'Edit',
'btn_add' => 'Add New',
'btn_search' => 'Search',
'btn_back' => 'Back',
'btn_export' => 'Export',
'btn_print' => 'Print',
'msg_saved' => 'Saved successfully',
'msg_deleted' => 'Deleted successfully',
'msg_error' => 'An error occurred',
'msg_required' => 'This field is required',
'msg_confirm_delete' => 'Are you sure you want to delete?',
'col_actions' => 'Actions',
'col_status' => 'Status',
'col_date' => 'Date',
'col_created' => 'Created',

// Thai
'btn_save' => 'บันทึก',
'btn_cancel' => 'ยกเลิก',
'btn_delete' => 'ลบ',
'btn_edit' => 'แก้ไข',
'btn_add' => 'เพิ่มใหม่',
'btn_search' => 'ค้นหา',
'btn_back' => 'กลับ',
'btn_export' => 'ส่งออก',
'btn_print' => 'พิมพ์',
'msg_saved' => 'บันทึกสำเร็จ',
'msg_deleted' => 'ลบสำเร็จ',
'msg_error' => 'เกิดข้อผิดพลาด',
'msg_required' => 'กรุณากรอกข้อมูลนี้',
'msg_confirm_delete' => 'คุณแน่ใจหรือไม่ว่าต้องการลบ?',
'col_actions' => 'จัดการ',
'col_status' => 'สถานะ',
'col_date' => 'วันที่',
'col_created' => 'สร้างเมื่อ',
```

## Checklist for New Modules

Before marking a module as complete, verify:

- [ ] All user-facing strings use translation system (no hardcoded text)
- [ ] Both `en` and `th` translations provided
- [ ] Language file keys follow naming convention (`{section}_{entity}_{attribute}`)
- [ ] Page tested with `?lang=en` and `?lang=th` (public) or language switcher (in-app)
- [ ] Database fields with display text have `_th` variant columns if needed
- [ ] Form validation messages are bilingual
- [ ] Success/error flash messages are bilingual
- [ ] Email templates (if any) support both languages
- [ ] PDF output (if any) renders Thai fonts correctly (see thai-localization skill)

## Anti-Patterns

```php
// BAD: Hardcoded strings
<h1>Products</h1>
<button>Save</button>
echo "Record saved successfully";

// BAD: Missing Thai translation (key returns as-is)
'module_title' => 'Products',  // en.php
// th.php missing this key → shows "module_title" raw

// BAD: Wrong session variable for context
$lang = $_SESSION['lang'];           // In-app only (integer 0/1)
$lang = $_SESSION['landing_lang'];   // Public pages only (string en/th)

// BAD: Not handling empty Thai field
$name = $row['name_th'];  // Could be NULL or empty
// GOOD:
$name = ($isThaiLang && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];
```

## Related Skills

- **thai-localization** — Thai-specific formatting (dates, currency, tax) — use alongside this skill
- **feature-workflow** — Step-by-step module creation — language support is step in the checklist
