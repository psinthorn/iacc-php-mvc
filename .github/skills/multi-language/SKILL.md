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
- Table headers, column names
- Success/error/info messages
- Tooltip text, help text
- Status labels, badges
- Select options (e.g., "-- Select --", "-- No Model --")
- Email subjects and body text

---

## Three Translation Systems

iACC has **three** distinct i18n systems. Choose based on context:

| System | Scope | Files | Session Variable | Access Pattern |
|--------|-------|-------|-----------------|----------------|
| **System 1** | Public/Landing pages | `inc/lang/en.php`, `inc/lang/th.php` | `$_SESSION['landing_lang']` (string: `'en'`/`'th'`) | `__('key_name')` |
| **System 2** | In-app MVC views (local) | Inline `$t` array in view | `$_SESSION['lang']` (integer: `0`/`1`) | `$t['key']` |
| **System 3** | In-app global (XML) | `inc/string-us.xml`, `inc/string-th.xml` | `$_SESSION['lang']` (integer: `0`/`1`) | `$xml->keyname ?? 'Fallback'` |

### Choosing the Right System

```
Is this a PUBLIC page (login.php, landing.php, about.php, etc.)?
  → System 1: __('key') with inc/lang/*.php

Is this an IN-APP MVC view file?
  Does the XML already have a key for this string? (check inc/string-us.xml)
    → System 3: $xml->key ?? 'Fallback'
  Is this a module-specific label not in XML?
    → System 2: $t array at top of view
  Is this a standalone file (e.g., export/report.php) with session_start()?
    → System 2: $isThaiLang inline ternary

Is this a sidebar/navbar/footer (shared layout)?
  → System 3: $xml->key ?? 'Fallback' (add key to XML files)
```

---

### System 1: Language Files (Public/Landing Pages)

**Files:** `inc/lang/en.php`, `inc/lang/th.php` (PHP arrays returning key-value pairs)
**Session:** `$_SESSION['landing_lang']` (string: `'en'` or `'th'`)
**Function:** `__('key_name')`

```php
// inc/lang/en.php — returns associative array
return [
    'login_title'       => 'Login',
    'login_ph_email'    => 'Enter your email',
    'login_ph_password' => 'Enter your password',
    'login_btn_signin'  => 'Sign In',
    'login_error_invalid' => 'Invalid email or password',
];

// inc/lang/th.php — matching keys with Thai values
return [
    'login_title'       => 'เข้าสู่ระบบ',
    'login_ph_email'    => 'กรอกอีเมล',
    'login_ph_password' => 'กรอกรหัสผ่าน',
    'login_btn_signin'  => 'เข้าสู่ระบบ',
    'login_error_invalid' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
];
```

**Page setup pattern (add to top of public page):**
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
<html lang="<?= $lang ?>">
<!-- Usage -->
<h1><?= __('login_title') ?></h1>
<input placeholder="<?= __('login_ph_email') ?>">
```

**Important rules for System 1:**
- Both `en.php` and `th.php` MUST have identical keys (no missing keys)
- Add new keys in the same order to both files
- Group keys by section with comments
- Test with `?lang=en` and `?lang=th`

### System 2: Session-Based Inline (In-App MVC Views)

**Session:** `$_SESSION['lang']` (integer: `0` = English, `1` = Thai)
**Detection:** `$isThaiLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1);`

**Pattern A: $t array (for views with many labels)**
```php
<?php
// At the top of the view file
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title'  => 'Products',
        'btn_add'     => 'Add New',
        'col_name'    => 'Product Name',
        'col_price'   => 'Price',
        'msg_saved'   => 'Saved successfully',
    ],
    'th' => [
        'page_title'  => 'สินค้า',
        'btn_add'     => 'เพิ่มใหม่',
        'col_name'    => 'ชื่อสินค้า',
        'col_price'   => 'ราคา',
        'msg_saved'   => 'บันทึกสำเร็จ',
    ]
];
$t = $labels[$lang];
?>
<h1><?= $t['page_title'] ?></h1>
```

**Pattern B: Inline ternary (for a few strings or standalone files)**
```php
<?php
$isThaiLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1);
$period_label = $isThaiLang ? "วันนี้" : "Today";
?>
```

**Pattern C: Bilingual arrays (for status labels, role names, etc.)**
```php
$statusLabels = $isThaiLang
    ? ['0'=>'รอดำเนินการ','1'=>'ใบเสนอราคา','2'=>'ยืนยันแล้ว','3'=>'จัดส่งแล้ว','4'=>'ออกใบแจ้งหนี้แล้ว','5'=>'เสร็จสิ้น']
    : ['0'=>'Pending','1'=>'Quotation','2'=>'Confirmed','3'=>'Delivered','4'=>'Invoiced','5'=>'Completed'];
```

### System 3: XML Language Files (In-App Global/Shared)

**Files:** `inc/string-us.xml` (English), `inc/string-th.xml` (Thai)
**Loaded by:** `inc/class.dbconn.php` → `$xml = simplexml_load_file("inc/string-".$lg.".xml")`
**Available as:** `$xml` global variable, passed to views via `BaseController::render()`
**Access:** `$xml->keyname` returns the translated string (SimpleXMLElement)

```php
<!-- Usage in views — always provide English fallback with ?? -->
<h1><?=$xml->purchasingorder ?? 'Purchase Order'?></h1>
<label><?=$xml->description ?? 'Description'?></label>
<button><?=$xml->save ?? 'Save'?></button>
```

**Adding new XML keys:**

Add to BOTH XML files at the same location (before `</note>` closing tag):
```xml
<!-- inc/string-us.xml -->
<helpdocs>Help &amp; Docs</helpdocs>
<helpcenter>Help Center</helpcenter>

<!-- inc/string-th.xml -->
<helpdocs>ช่วยเหลือและเอกสาร</helpdocs>
<helpcenter>ศูนย์ช่วยเหลือ</helpcenter>
```

**Important rules for System 3:**
- XML keys are **lowercase, no underscores** (e.g., `purchasingorder`, `helpdocs`, `masterdataguide`)
- Use `&amp;` for `&` in XML values
- Both XML files must have matching keys
- Always provide `?? 'English Fallback'` in views in case key is missing
- After editing XML, validate: `docker exec iacc_php php -r "simplexml_load_file('inc/string-th.xml') ? print('OK') : print('FAIL');"`
- Root element is `<note>...</note>` — ensure single closing `</note>` tag
- The `<note>` element on line 94 of `string-th.xml` is a *data element* (not the root) — don't confuse it

**How $xml flows to MVC views:**
```
inc/class.dbconn.php → loads $xml (global)
  → index.php uses global $xml
  → BaseController::__construct() stores $this->xml
  → BaseController::render() passes $xml to every view via extract($data)
  → Views access as $xml->keyname
```

---

## Key Naming Convention

### System 1 (PHP lang files): `{section}_{entity}_{attribute}`

```
login_title           → login page title
login_ph_email        → login page, placeholder, email
login_error_invalid   → login page, error message, invalid
nav_features          → navigation, features link
pricing_pro_feature_1 → pricing section, pro plan, feature 1
```

### System 3 (XML files): lowercase concatenated

```
purchasingorder    → Purchase Order
helpdocs           → Help & Docs
masterdataguide    → Master Data Guide
labourrate         → Labour Rate
```

**Prefixes by type (System 1 & 2):**
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

---

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

**Step 3:** Use in page with `__()` function.

**Step 4:** Verify both languages work via `?lang=en` and `?lang=th`.

### 2. Add Translations for a New In-App MVC View

**Step 1:** Check `inc/string-us.xml` for existing keys you can reuse.

**Step 2:** If keys exist in XML, use `$xml->key ?? 'Fallback'` pattern.

**Step 3:** For module-specific labels not in XML, define `$t` array at top of view:
```php
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = ['en' => [...], 'th' => [...]];
$t = $labels[$lang];
```

**Step 4:** For shared UI elements (sidebar, navbar, footer), add keys to XML files instead.

### 3. Add Keys to XML Language Files

> ⚠️ **MUST DO when adding a sidebar/navbar menu item, footer link, or any shared layout text.** The `$xml->key ?? 'Fallback'` pattern silently shows the English fallback to Thai users when the key is missing — bug looks like the page works, but Thai users see English bleed-through. ALWAYS add to BOTH `inc/string-us.xml` AND `inc/string-th.xml` in the same edit.

**Step 1:** Add key before `</note>` in `inc/string-us.xml`:
```xml
<newkey>English Text</newkey>
```

**Step 2:** Add matching key in same position in `inc/string-th.xml`:
```xml
<newkey>ข้อความภาษาไทย</newkey>
```

**Step 3:** Validate both files:
```bash
docker exec iacc_php php -r "simplexml_load_file('inc/string-us.xml') ? print('OK') : print('FAIL');"
docker exec iacc_php php -r "simplexml_load_file('inc/string-th.xml') ? print('OK') : print('FAIL');"
```

**Step 4:** Use in view: `<?=$xml->newkey ?? 'English Text'?>`

**Step 5 (sidebar items only — coverage check):** confirm both languages render:
```bash
# Returns "MISSING" if either file lacks the key
for k in newkey1 newkey2; do
  for f in inc/string-us.xml inc/string-th.xml; do
    grep -q "<$k>" "$f" || echo "❌ $k missing from $f"
  done
done
```

### 4. Add Bilingual Database Fields

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

### 5. Language Switcher

**Public pages** — URL parameter:
```html
<a href="?lang=en">English</a> | <a href="?lang=th">ภาษาไทย</a>
```

**In-app** — Top navbar form (posts to MVC route):
```php
<form action="index.php?page=lang_switch" method="post">
    <button name="chlang" value="0">EN</button>
    <button name="chlang" value="1">TH</button>
</form>
```

**IMPORTANT:** The language switcher form action must be `index.php?page=lang_switch`, NOT `lang.php` (which doesn't exist).

---

## Completed i18n Coverage

These files are already fully bilingual (reference for patterns):

| File | System | Pattern Used |
|------|--------|-------------|
| `login.php` | System 1 | `__('key')` with `inc/lang/*.php` |
| `landing.php`, `about.php`, `contact.php`, etc. | System 1 | `__('key')` |
| `inc/top-navbar.php` | System 2 | `$isThaiLang` ternary for role labels |
| `inc/footer.php` | System 2 | `$_SESSION['lang']` inline ternary |
| `app/Views/dashboard/index.php` | System 2 | `$t` array (~60 keys) |
| `app/Views/export/report.php` | System 2 | `$isThaiLang` inline ternary |
| `app/Views/po/view.php` | Systems 2+3 | `$xml->key ?? fallback` + `$isThaiLang` arrays |
| `app/Views/po/make.php` | Systems 2+3 | `$xml->key ?? fallback` + `$isThaiLang` ternary |
| `app/Views/layouts/sidebar.php` | System 3 | `$xml->key ?? 'Fallback'` |
| `app/Views/help/*.php` | System 2 | `$t` array |
| `app/Views/account/settings.php` | System 3 | `$xml->key ?? 'Fallback'` |
| `inc/lang/en.php` | — | ~266 keys (login, landing, features, pricing, etc.) |
| `inc/lang/th.php` | — | ~266 keys (matching en.php) |
| `inc/string-us.xml` | — | ~524 keys (app-wide English) |
| `inc/string-th.xml` | — | ~524 keys (app-wide Thai) |

---

## Common Translation Strings

Reuse these standard keys across modules:

### System 1 (PHP lang files)
```php
// English                              // Thai
'btn_save' => 'Save',                   'btn_save' => 'บันทึก',
'btn_cancel' => 'Cancel',               'btn_cancel' => 'ยกเลิก',
'btn_delete' => 'Delete',               'btn_delete' => 'ลบ',
'btn_edit' => 'Edit',                   'btn_edit' => 'แก้ไข',
'btn_add' => 'Add New',                 'btn_add' => 'เพิ่มใหม่',
'btn_search' => 'Search',               'btn_search' => 'ค้นหา',
'btn_back' => 'Back',                   'btn_back' => 'กลับ',
'btn_export' => 'Export',               'btn_export' => 'ส่งออก',
'msg_saved' => 'Saved successfully',    'msg_saved' => 'บันทึกสำเร็จ',
'msg_deleted' => 'Deleted successfully','msg_deleted' => 'ลบสำเร็จ',
'msg_error' => 'An error occurred',     'msg_error' => 'เกิดข้อผิดพลาด',
```

### System 3 (XML — already exists in files)
```
save / บันทึก, cancel / ยกเลิก, delete / ลบ, edit / แก้ไข,
add / เพิ่ม, back / กลับ, search / ค้นหา, Product / สินค้า,
description / รายละเอียด, Price / ราคา, Total / รวม
```

---

## Checklist for New Modules

Before marking a module as complete, verify:

- [ ] All user-facing strings use translation system (no hardcoded text)
- [ ] Both `en` and `th` translations provided
- [ ] Language file keys follow naming convention
- [ ] Correct system used (System 1 for public, System 2/3 for in-app)
- [ ] Page tested with both language settings
- [ ] Database fields with display text have `_th` variant columns if needed
- [ ] Form validation messages are bilingual
- [ ] Success/error flash messages are bilingual
- [ ] Select dropdown options translated (e.g., "-- Select --" → "-- เลือก --")
- [ ] Status labels/badges translated
- [ ] Email templates (if any) support both languages
- [ ] PDF output (if any) renders Thai fonts correctly (see thai-localization skill)
- [ ] XML files validated with `simplexml_load_file()` after editing
- [ ] PHP files pass `php -l` syntax check after editing

---

## Anti-Patterns

```php
// BAD: Hardcoded strings
<h1>Products</h1>
<button>Save</button>
echo "Record saved successfully";

// BAD: Missing Thai translation (key returns key name as fallback)
'module_title' => 'Products',  // en.php
// th.php missing this key → shows "module_title" raw

// BAD: Wrong session variable for context
$lang = $_SESSION['lang'];           // In-app only (integer 0/1)
$lang = $_SESSION['landing_lang'];   // Public pages only (string en/th)
// Don't mix these up!

// BAD: Not handling empty Thai database field
$name = $row['name_th'];  // Could be NULL or empty
// GOOD:
$name = ($isThaiLang && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];

// BAD: Language switcher form pointing to wrong URL
<form action="lang.php">  // lang.php doesn't exist!
// GOOD:
<form action="index.php?page=lang_switch">

// BAD: Missing ?? fallback in XML access
<?=$xml->mykey?>  // Returns empty SimpleXMLElement object if key missing
// GOOD:
<?=$xml->mykey ?? 'My Key'?>

// BAD: Adding sidebar item with $xml->key but never adding the XML keys
<a href="..."><?=$xml->mynewmenu ?? 'My New Menu'?></a>
// (forgot to add <mynewmenu> to BOTH inc/string-us.xml and inc/string-th.xml)
// → English users see "My New Menu" (fallback works)
// → Thai users ALSO see "My New Menu" (silently broken — no error, just English bleed-through)
// GOOD: edit sidebar.php + string-us.xml + string-th.xml in the SAME commit

// BAD: Using underscores in XML key names (inconsistent with existing pattern)
<my_key>Value</my_key>
// GOOD:
<mykey>Value</mykey>

// BAD: Forgetting &amp; for & in XML
<helpdocs>Help & Docs</helpdocs>  // XML parse error!
// GOOD:
<helpdocs>Help &amp; Docs</helpdocs>

// BAD: Duplicate </note> closing tag in XML
</note>
</note>  // Causes parse error!
```

---

## Related Skills

- **thai-localization** — Thai-specific formatting (dates, currency, Buddhist Era, number-to-Thai-words) — use alongside this skill
- **feature-workflow** — Step-by-step module creation — language support is a step in the checklist
- **web-app-dev** — MVC architecture and view rendering — explains how $xml flows to views
