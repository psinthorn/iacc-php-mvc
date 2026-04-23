# Claude Code Handoff — Tour Booking Form Enhancements

**Date:** April 21, 2026  
**Branch:** (current working branch)  
**Feature:** Tour Booking — Customer/Agent/Sales Rep smart search + contact fields

---

## What Was Built

The tour booking form (`app/Views/tour-booking/make.php`) was restructured to:

1. **Agent Info section** — Replaced static `<select>` with smart autocomplete search (searches `tour_agent_profiles JOIN company`)
2. **Customer Info section** — Smart search + editable Name / Gender / Nationality / Email / Mobile / Messengers fields
3. **Sales Rep Info section** — NEW section: smart search for sales rep (same agent pool) + editable Email / Mobile / Messengers fields
4. **Create New modals** — `#qcOverlay` (customer), `#srOverlay` (sales rep)

---

## Files Modified

| File | What Changed |
|------|-------------|
| `app/Views/tour-booking/make.php` | Full form restructure, smart search HTML + JS for all 3 sections |
| `app/Controllers/TourBookingController.php` | Added `agentSearch()`, `salesRepSearch()`, `salesRepCreate()` |
| `app/Models/TourBooking.php` | Added `searchAgents()`, `quickCreateAgent()`; fixed `searchCustomers()` to exclude vendors |
| `app/Config/routes.php` | Added `tour_booking_agent_search`, `tour_booking_sales_rep_search`, `tour_booking_sales_rep_create` |
| `database/migrations/tour_all_migrations.sql` | 3 ALTER TABLE statements (see DB section below) |

---

## Database — Migrations Appended (NOT YET RUN IN PRODUCTION)

These are at the bottom of `database/migrations/tour_all_migrations.sql` and need to be applied:

```sql
-- 1. sales_rep_id on bookings
ALTER TABLE tour_bookings
  ADD COLUMN sales_rep_id INT(11) DEFAULT NULL COMMENT 'FK to tour_agent_profiles.company_ref_id' AFTER agent_id,
  ADD KEY idx_sales_rep (sales_rep_id);

-- 2. contact_messengers on tour_booking_contacts
ALTER TABLE tour_booking_contacts
  ADD COLUMN contact_messengers TEXT DEFAULT NULL AFTER nationality;

-- 3. contact_messengers on tour_agent_profiles (NOT YET RUN - column doesn't exist yet)
ALTER TABLE tour_agent_profiles
  ADD COLUMN contact_telegram VARCHAR(100) DEFAULT NULL AFTER contact_whatsapp,
  ADD COLUMN contact_wechat VARCHAR(100) DEFAULT NULL AFTER contact_telegram,
  ADD COLUMN contact_messengers TEXT DEFAULT NULL AFTER contact_wechat;
```

**IMPORTANT:** `tour_agent_profiles` does NOT have `contact_messengers` yet.  
Current real columns on `tour_agent_profiles`: `contact_line`, `contact_whatsapp`, `contact_person`, `contact_mobile`, `contact_email`  
The code currently uses `CONCAT_WS(', ', contact_line, contact_whatsapp)` as a workaround.

Run migrations:
```bash
docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/tour_all_migrations.sql
```

Or run just the ALTER statements above manually.

---

## ⚠️ REMAINING TASK — `store()` Handler (Most Critical)

**File:** `app/Controllers/TourBookingController.php`  
**Method:** `store()` starting at **line 123**

### What needs to be added:

**1. Parse `sales_rep_id` into the `$data` array** (around line 142, alongside `agent_id`):
```php
'sales_rep_id' => intval($_POST['sales_rep_id'] ?? 0),
```

**2. Parse `contact_messengers` into `$contactData`** (around line 238, alongside other contact fields):
```php
$contactData = [
    'contact_name' => trim($_POST['contact_name'] ?? ''),
    'mobile'       => trim($_POST['contact_mobile'] ?? ''),
    'email'        => trim($_POST['contact_email'] ?? ''),
    'gender'       => trim($_POST['contact_gender'] ?? ''),
    'nationality'  => trim($_POST['contact_nationality'] ?? ''),
    'contact_messengers' => trim($_POST['contact_messengers'] ?? ''),  // ADD THIS
];
```

**3. Parse sales rep contact fields** — The form posts `sales_rep_email`, `sales_rep_mobile`, `sales_rep_messengers`. These need to be saved. Options:
- Store them in a new `tour_booking_sales_reps` table (preferred), OR
- Store sales_rep_id only (contact info lives in `tour_agent_profiles`)

Currently `sales_rep_id` column on `tour_bookings` IS in migration but `updateBooking()` / `createBooking()` in the model also need to handle `sales_rep_id`.

**4. Check `saveBookingContact()` supports `contact_messengers`:**
```bash
grep -n "saveBookingContact\|contact_messengers" app/Models/TourBooking.php
```

---

## Form POST Field Names (from make.php)

| Field | HTML name | Notes |
|-------|-----------|-------|
| Agent ID | `agent_id` | Hidden input, `company.id` |
| Sales Rep ID | `sales_rep_id` | Hidden input, `company.id` (same pool as agent) |
| Customer ID | `customer_id` | Hidden input, `company.id` |
| Customer Name | `contact_name` | Editable |
| Customer Email | `contact_email` | Editable |
| Customer Mobile | `contact_mobile` | Editable |
| Customer Gender | `contact_gender` | Select |
| Customer Nationality | `contact_nationality` | Text |
| Customer Messengers | `contact_messengers` | Text (e.g. "Line: @user") |
| Sales Rep Email | `sales_rep_email` | Auto-filled, editable |
| Sales Rep Mobile | `sales_rep_mobile` | Auto-filled, editable |
| Sales Rep Messengers | `sales_rep_messengers` | Auto-filled, editable |

---

## Smart Search Pattern (for reference)

All 3 AC sections follow the same pattern:

```
ac-wrap div
  └─ ac-selected chip (hidden unless preselected) — shows name + × clear button
  └─ <input type="text" id="xxxSearch"> — shown when no selection
  └─ ac-list div — dropdown results
<input type="hidden" name="xxx_id" id="xxx_id"> — stores selected ID
```

JS: debounce 250ms → fetch `index.php?page=tour_booking_xxx_search&q=` → fill `ac-list`  
Route: `standalone` flag so it returns raw JSON (no HTML shell)

---

## Key Schema Facts

### `company` table
- `customer = 1` → customer
- `vender = 1` → vendor/agent
- `company_id` → tenant isolation
- `id` → what `tour_bookings.agent_id` and `tour_bookings.customer_id` store

### `tour_agent_profiles` table
- `company_ref_id` → FK to `company.id`
- `company_id` → tenant isolation (same as booking company)
- `contact_mobile` (NOT `contact_phone`)
- `contact_email`, `contact_line`, `contact_whatsapp`
- NO `contact_messengers` column yet (migration pending)

### `tour_bookings` table
- `agent_id` → FK to `company.id`  
- `sales_rep_id` → FK to `company.id` (migration pending — may not exist yet)

### `tour_booking_contacts` table
- `contact_messengers` → migration pending — may not exist yet

---

## Test Plan After Completing store()

1. Create new booking → verify `sales_rep_id`, `contact_messengers` saved
2. Edit booking → verify agent/customer/sales rep pre-selected correctly
3. Search agent: type "samui" → should show "Samui Island Tours Co.,Ltd." 
4. Search customer: type agent company name → should NOT appear (vendor excluded)
5. Create new sales rep via modal → verify it creates company + tour_agent_profiles record

---

## Quick Commands

```bash
# PHP syntax check
docker exec iacc_php php -l /var/www/html/app/Controllers/TourBookingController.php
docker exec iacc_php php -l /var/www/html/app/Models/TourBooking.php

# Test agent search endpoint (need valid session cookie)
curl -s "http://localhost/index.php?page=tour_booking_agent_search&q=samui"

# Check PHP errors
docker logs iacc_php --tail 50

# Check tour_booking_contacts columns
docker exec iacc_mysql mysql -uroot -proot iacc -e "DESCRIBE tour_booking_contacts" 2>/dev/null

# Check tour_bookings columns
docker exec iacc_mysql mysql -uroot -proot iacc -e "DESCRIBE tour_bookings" 2>/dev/null
```

---

## Todo List Status

- [x] Add `sales_rep_id` to `tour_bookings` migration  
- [x] Add `contact_messengers` to `tour_booking_contacts` migration  
- [x] Add `contact_messengers` to `tour_agent_profiles` migration (pending run)  
- [x] Restructure Customer section (Name/Gender/Nationality + Email/Mobile/Messengers)  
- [x] Create Sales Rep section  
- [x] Customer autocomplete + create-new modal (`#qcOverlay`)  
- [x] Sales Rep smart search + create-new modal (`#srOverlay`)  
- [x] Agent Info smart search (replaced static `<select>`)  
- [x] Routes for agent/salesRep search + salesRep create  
- [x] Controller: `agentSearch()`, `salesRepSearch()`, `salesRepCreate()`  
- [x] Model: `searchAgents()`, `quickCreateAgent()`  
- [x] Fix: `searchCustomers()` excludes vendors (`vender != 1`)  
- [x] Fix: column name `contact_mobile` (not `contact_phone`)  
- [ ] **`store()` — add `sales_rep_id` to `$data`**  
- [ ] **`store()` — add `contact_messengers` to `$contactData`**  
- [ ] **`saveBookingContact()` — ensure it handles `contact_messengers`**  
- [ ] **`createBooking()` / `updateBooking()` — ensure they handle `sales_rep_id`**  
- [ ] Run pending DB migrations  
- [ ] Add Thai translations for new field labels  
- [ ] End-to-end test  
