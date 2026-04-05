---
name: form-consistency
description: 'Fix and enforce consistent form input sizing across iACC. USE FOR: input field height mismatches, form-control sizing, fixing inline height overrides, standardizing padding/height on inputs/selects/textareas, removing !important hacks, aligning form elements in the same row. Use when: inputs look different heights on the same page, select and text input heights mismatch, placeholder text is clipped, form elements are visually misaligned, creating new forms.'
---

# Form Input Consistency

Fix and prevent the input field height inconsistency problem across the iACC project.

## Problem Summary

The project has **7 conflicting height values** (28px–54px) for `.form-control` inputs across CSS files and inline styles. Inputs on the same row can appear at different heights, and some are too small to display default text.

## Root Causes

1. **No global form CSS** — `form-improvements.css` is loaded per-view, not globally
2. **`!important` abuse** — `master-data.css` forces 52–54px with `!important` in toolbars/inline forms
3. **Mixed sizing methods** — Some rules use `height`, others `min-height`, some both
4. **Legacy inline styles** — PHP views hardcode `style="height:28px"` etc.
5. **No size tokens** — No CSS variables for standard input sizes

## Standard Input Sizes

All form inputs MUST use one of these three sizes:

| Size | CSS Class | Height | Padding | Use Case |
|------|-----------|--------|---------|----------|
| **Default** | `.form-control` | 44px | 10px 14px | Standard forms, settings, CRUD pages |
| **Small** | `.form-control-sm` | 36px | 6px 10px | Inline table filters, compact toolbars, table-row inputs |
| **Large** | `.form-control-lg` | 52px | 14px 16px | Search bars, hero inputs, landing pages |

**FORBIDDEN values**: 28px, 30px, 32px, 42px, 54px — these are legacy or accidental.

## CSS Variables (defined in `css/form-improvements.css`)

```css
:root {
    --input-height-default: 44px;
    --input-height-sm: 36px;
    --input-height-lg: 52px;
    --input-padding-default: 10px 14px;
    --input-padding-sm: 6px 10px;
    --input-padding-lg: 14px 16px;
    --input-font-size: 14px;
    --input-border-radius: 6px;
    --input-border-color: #d1d5db;
    --input-focus-border: #4f46e5;
    --input-focus-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
```

## Fix Procedure

### Step 1: Add CSS variables to `form-improvements.css`

Add the `:root` variables above at the top of `css/form-improvements.css` (after the comment block). Then update the existing `.form-control` rule to use them:

```css
.form-control {
    height: var(--input-height-default);
    min-height: var(--input-height-default);
    padding: var(--input-padding-default);
    font-size: var(--input-font-size);
    border-radius: var(--input-border-radius);
    border: 1px solid var(--input-border-color);
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: var(--input-focus-border);
    box-shadow: var(--input-focus-shadow);
}

.form-control-sm {
    height: var(--input-height-sm);
    min-height: var(--input-height-sm);
    padding: var(--input-padding-sm);
    font-size: 13px;
}

.form-control-lg {
    height: var(--input-height-lg);
    min-height: var(--input-height-lg);
    padding: var(--input-padding-lg);
    font-size: 16px;
}

/* Select must match text input height */
select.form-control {
    height: var(--input-height-default);
    min-height: var(--input-height-default);
    padding-right: 30px;
}

select.form-control-sm {
    height: var(--input-height-sm);
    min-height: var(--input-height-sm);
}

/* Textarea: auto height, but min visible area */
textarea.form-control {
    height: auto;
    min-height: 100px;
}

/* File input: auto height with consistent padding */
input[type="file"].form-control {
    height: auto;
    padding: 10px 14px;
    line-height: 1.5;
}

/* Color input: match default height */
input[type="color"].form-control {
    height: var(--input-height-default);
    padding: 4px 8px;
}
```

### Step 2: Fix `master-data.css` `!important` overrides

Replace the forced heights with variable references. Remove `!important` where possible:

```css
/* BEFORE (BAD) */
.action-toolbar .search-box input { height: 52px !important; }
.inline-form-container .form-control { height: 54px !important; min-height: 54px !important; }

/* AFTER (GOOD) */
.action-toolbar .search-box input { height: var(--input-height-lg); }
.action-toolbar select.form-control { height: var(--input-height-lg); min-height: var(--input-height-lg); }
.inline-form-container .form-control { height: var(--input-height-default); min-height: var(--input-height-default); }
.inline-form-container select { height: var(--input-height-default); min-height: var(--input-height-default); }
```

### Step 3: Remove inline height styles from PHP views

Search and fix these known files:

| File | Line | Current | Fix |
|------|------|---------|-----|
| `app/Views/company/credits.php` | ~103 | `style="height: 36px"` on select | Remove inline style, add class `form-control-sm` if small size needed |
| `legacy/rep-make.php` | ~332,344,368 | `style="height: 42px"` on select | Remove inline style (default 44px is close enough) |
| `legacy/po-deliv.php` | ~445,449 | `style="height: 32px; padding: 6px 8px"` | Remove inline style, add class `form-control-sm` |
| `legacy/po-view.php` | ~624 | `style="width:150px; height:28px"` | Remove `height:28px`, keep `width:150px`, add `form-control-sm` |
| `app/Views/expense/categories.php` | ~157 | `style="height:42px; padding:4px"` on color | Remove inline style |
| `templates/tour-company-demo/setup.php` | ~294 | `style="height:44px; padding:6px"` on color | Remove inline style (matches default) |

**Pattern to search**: `grep -rn 'style=.*height.*px' app/Views/ legacy/ templates/ --include="*.php"`

### Step 4: Ensure `form-improvements.css` loads globally

In `app/Views/layouts/head.php`, add `form-improvements.css` to the global CSS includes (after Bootstrap, before page-specific CSS):

```html
<link rel="stylesheet" href="css/form-improvements.css">
```

This ensures the base sizing applies everywhere even if individual views forget to import it.

## Rules for New Code

### DO
- Use `.form-control` class on all inputs, selects, textareas
- Use `.form-control-sm` for compact contexts (inline table editing, filter bars)
- Use `.form-control-lg` for prominent search/hero inputs
- Use CSS variables if you need custom sizing in a specific context
- Keep `select` and `input` on the same row using the same size class

### DON'T
- Never use inline `style="height:..."` on form elements
- Never use `!important` on form height/padding rules
- Never invent new height values (only 36px, 44px, 52px)
- Never set `height` without also setting matching `min-height`
- Never use `px` padding without `box-sizing: border-box`
- Never add `text-decoration: underline` on links — global rule in `sb-admin.css` removes all underlines

## Link Styling Convention

All links across the project have **no underline** on any state (normal, hover, focus). This is enforced globally in `css/sb-admin.css`:

```css
a, a:hover, a:focus {
    text-decoration: none;
}
```

- Do NOT add `text-decoration: underline` or `text-decoration: underline on hover` to any link
- Use color change or opacity on hover instead of underline for interactivity cues
- This applies to all views: admin, public, forms, tables, headers

## Same-Row Alignment Checklist

When inputs appear side by side (e.g., in `.form-inline`, `.input-group`, or grid columns):

1. All elements use the same size class (all default, or all `-sm`, or all `-lg`)
2. No element has inline height override
3. Buttons in the same row match: `.btn` = 44px, `.btn-sm` = 36px, `.btn-lg` = 52px
4. `.input-group-btn .btn` height matches the adjacent input

## CSS Scoping for Views in Admin Layout

When converting standalone pages to normal routes (rendered inside admin layout), CSS selectors can leak into the sidebar, navbar, and other layout elements.

**Always scope view-specific CSS under a page wrapper class:**

```css
/* BAD — leaks into admin layout */
.form-group input { height: 44px; }
.card { border: 2px solid #e0e0e0; }

/* GOOD — scoped to page only */
.ai-settings-page .form-group input { height: 44px; }
.ai-settings-page .card { border: 2px solid #e0e0e0; }
```

## Page Header Convention

All `.master-data-header` elements use a **gradient background** with forced white text, defined globally in `master-data.css`:

- **Default**: Purple gradient (`#4f46e5` → `#4338ca`)
- **Module themes**: Use `data-theme` attribute — `teal`, `blue`, `emerald`, `rose`, `amber`
- **Text**: ALL children forced white via `!important` selector list
- **Buttons**: `.btn-header-primary` (translucent white bg) and `.btn-header-outline` (subtle white border)
- **Layout**: `.header-content`, `.header-text`, `.header-actions` are all defined globally

```html
<!-- Default purple header -->
<div class="master-data-header">
  <div class="header-content">
    <div class="header-text">
      <h2><i class="fa fa-icon"></i> Title</h2>
      <p>Subtitle text</p>
    </div>
    <div class="header-actions">
      <a href="#" class="btn-header btn-header-outline"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="#" class="btn-header btn-header-primary"><i class="fa fa-plus"></i> Create</a>
    </div>
  </div>
</div>

<!-- Module-specific teal header (e.g., Journal/Accounting) -->
<div class="master-data-header" data-theme="teal">
  ...same structure...
</div>
```

### Available Themes
| Theme | Gradient | Module |
|-------|----------|--------|
| (default) | Purple `#4f46e5` → `#4338ca` | Expense, generic |
| `teal` | `#0d9488` → `#0891b2` | Journal, Accounting |
| `blue` | `#2563eb` → `#1d4ed8` | Tax, Reports |
| `emerald` | `#059669` → `#047857` | (available) |
| `rose` | `#e11d48` → `#be123c` | (available) |
| `amber` | `#d97706` → `#b45309` | (available) |

### DON'T
- Never inline `background:linear-gradient(...)` or `color:white` on header elements
- Never duplicate `.btn-header` styles in view `<style>` blocks
- Never use `style="..."` on buttons inside the header — use classes only
```

### `overflow: hidden` Clips Dropdowns

`overflow: hidden` on a parent container clips absolutely-positioned children like search result dropdowns, datepickers, and popovers.

```css
/* BAD — clips dropdown results */
.company-selector-card { overflow: hidden; }

/* GOOD — allows dropdown to overflow */
.company-selector-card { overflow: visible; }
```

**Also watch for**: CSS class mismatches between JS and CSS (e.g., JS creates `search-result-item` but CSS styles `md-search-result-item`).

## Verification

After fixing, run this visual check:

```bash
# Find remaining inline height overrides on form elements
grep -rn 'style=.*height.*px' app/Views/ legacy/ templates/ --include="*.php" | grep -i 'input\|select\|form-control'

# Find remaining !important on height in CSS
grep -rn 'height.*!important' css/ --include="*.css" | grep -v 'bootstrap'
```

Both commands should return zero results (excluding Bootstrap source files).
