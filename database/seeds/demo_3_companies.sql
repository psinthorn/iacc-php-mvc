-- ============================================================
-- Demo Seed: 3 Interconnected Companies
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/seeds/demo_3_companies.sql
-- 
-- Companies:
--   Alpha Tech Solutions (IT services, B2B) — buys from Beta, sells to Gamma
--   Beta Supply Co. (hardware supplier, B2B) — sells to Alpha and Gamma
--   Gamma Design Studio (creative agency, B2B) — buys from Beta, sells to Alpha
--
-- Each company gets: users, master data (category/type/brand/model),
--   payment methods, expense categories, expenses, PRs, POs, products,
--   invoices, chart of accounts, journal entries
-- ============================================================

SET @NOW = NOW();
SET @TODAY = CURDATE();

-- ============================================================
-- CLEANUP: Remove previous demo data if exists (makes seed re-runnable)
-- ============================================================
-- First collect IDs while companies still exist
SET @OLD_ALPHA = (SELECT id FROM company WHERE name_sh = 'ALPHA' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1);
SET @OLD_BETA  = (SELECT id FROM company WHERE name_sh = 'BETA' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1);
SET @OLD_GAMMA = (SELECT id FROM company WHERE name_sh = 'GAMMA' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1);

-- Delete by known identifiers (doesn't depend on company existing)
DELETE FROM journal_entries WHERE journal_voucher_id IN (
    SELECT id FROM journal_vouchers WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0))
);
DELETE FROM journal_vouchers WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM chart_of_accounts WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM expenses WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM expense_categories WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM receipt WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM iv WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM product WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM po WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM pr WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM payment_method WHERE code IN ('BANK-ALPHA','CC-ALPHA','CASH-ALPHA','BANK-BETA','COD-BETA','CHQ-BETA','BANK-GAMMA','CC-GAMMA');
DELETE FROM payment WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM model WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM brand WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM type WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM category WHERE company_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
DELETE FROM authorize WHERE email IN ('admin@alphatech.co.th','sales@alphatech.co.th','staff@alphatech.co.th','admin@betasupply.co.th','sales@betasupply.co.th','admin@gammadesign.co.th','design@gammadesign.co.th');
DELETE FROM company_addr WHERE com_id IN (COALESCE(@OLD_ALPHA,0), COALESCE(@OLD_BETA,0), COALESCE(@OLD_GAMMA,0));
-- Also delete cross-reference company records
DELETE FROM company WHERE name_sh IN ('BETA-V','GAMMA-C','ALPHA-C','GAMMA-C2','BETA-V2','ALPHA-C2');
DELETE FROM company WHERE name_sh IN ('ALPHA','BETA','GAMMA') AND deleted_at IS NULL;
-- Clean orphaned records by known identifiers
DELETE FROM iv WHERE tex IN (69020001, 69020003, 69030001);
DELETE FROM expenses WHERE expense_number LIKE 'EXP-A-2026%' OR expense_number LIKE 'EXP-B-2026%' OR expense_number LIKE 'EXP-G-2026%';

-- ============================================================
-- 1. COMPANIES (owner = themselves, customer+vendor flags)
-- ============================================================
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, customer_type, logo, term, company_id)
VALUES
('Alpha Tech Solutions Co., Ltd.', 'บริษัท อัลฟ่า เทค โซลูชั่นส์ จำกัด', 'ALPHA', 'Somchai Prasert', 'info@alphatech.co.th', 'THB', '02-111-1111', '02-111-1112', '0105560001111', 1, 0, 'b2b', '', 'Net 30', NULL),
('Beta Supply Co., Ltd.', 'บริษัท เบต้า ซัพพลาย จำกัด', 'BETA', 'Nattaya Srisuk', 'info@betasupply.co.th', 'THB', '02-222-2222', '02-222-2223', '0105560002222', 1, 1, 'b2b', '', 'Net 15', NULL),
('Gamma Design Studio Co., Ltd.', 'บริษัท แกมม่า ดีไซน์ สตูดิโอ จำกัด', 'GAMMA', 'Kittipong Dejsupa', 'hello@gammadesign.co.th', 'THB', '02-333-3333', '02-333-3334', '0105560003333', 1, 0, 'b2b', '', 'Net 30', NULL);

SET @ALPHA_ID = (SELECT id FROM company WHERE name_sh = 'ALPHA' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1);
SET @BETA_ID  = (SELECT id FROM company WHERE name_sh = 'BETA' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1);
SET @GAMMA_ID = (SELECT id FROM company WHERE name_sh = 'GAMMA' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1);

-- Self-reference company_id (owner)
UPDATE company SET company_id = @ALPHA_ID WHERE id = @ALPHA_ID;
UPDATE company SET company_id = @BETA_ID  WHERE id = @BETA_ID;
UPDATE company SET company_id = @GAMMA_ID WHERE id = @GAMMA_ID;

-- Also register cross-company relationships:
-- Beta as vendor record under Alpha
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, customer_type, logo, term, company_id)
VALUES
('Beta Supply Co., Ltd.', 'บริษัท เบต้า ซัพพลาย จำกัด', 'BETA-V', 'Nattaya Srisuk', 'info@betasupply.co.th', 'THB', '02-222-2222', '', '0105560002222', 0, 1, 'b2b', '', 'Net 15', @ALPHA_ID),
-- Gamma as customer record under Alpha
('Gamma Design Studio Co., Ltd.', 'บริษัท แกมม่า ดีไซน์ สตูดิโอ จำกัด', 'GAMMA-C', 'Kittipong Dejsupa', 'hello@gammadesign.co.th', 'THB', '02-333-3333', '', '0105560003333', 1, 0, 'b2b', '', 'Net 30', @ALPHA_ID),
-- Alpha as customer record under Beta
('Alpha Tech Solutions Co., Ltd.', 'บริษัท อัลฟ่า เทค โซลูชั่นส์ จำกัด', 'ALPHA-C', 'Somchai Prasert', 'info@alphatech.co.th', 'THB', '02-111-1111', '', '0105560001111', 1, 0, 'b2b', '', 'Net 30', @BETA_ID),
-- Gamma as customer record under Beta
('Gamma Design Studio Co., Ltd.', 'บริษัท แกมม่า ดีไซน์ สตูดิโอ จำกัด', 'GAMMA-C2', 'Kittipong Dejsupa', 'hello@gammadesign.co.th', 'THB', '02-333-3333', '', '0105560003333', 1, 0, 'b2b', '', 'Net 30', @BETA_ID),
-- Beta as vendor record under Gamma
('Beta Supply Co., Ltd.', 'บริษัท เบต้า ซัพพลาย จำกัด', 'BETA-V2', 'Nattaya Srisuk', 'info@betasupply.co.th', 'THB', '02-222-2222', '', '0105560002222', 0, 1, 'b2b', '', 'Net 15', @GAMMA_ID),
-- Alpha as customer record under Gamma
('Alpha Tech Solutions Co., Ltd.', 'บริษัท อัลฟ่า เทค โซลูชั่นส์ จำกัด', 'ALPHA-C2', 'Somchai Prasert', 'info@alphatech.co.th', 'THB', '02-111-1111', '', '0105560001111', 1, 0, 'b2b', '', 'Net 30', @GAMMA_ID);

SET @BETA_VEN_ALPHA  = (SELECT id FROM company WHERE name_sh = 'BETA-V' AND company_id = @ALPHA_ID LIMIT 1);
SET @GAMMA_CUS_ALPHA = (SELECT id FROM company WHERE name_sh = 'GAMMA-C' AND company_id = @ALPHA_ID LIMIT 1);
SET @ALPHA_CUS_BETA  = (SELECT id FROM company WHERE name_sh = 'ALPHA-C' AND company_id = @BETA_ID LIMIT 1);
SET @GAMMA_CUS_BETA  = (SELECT id FROM company WHERE name_sh = 'GAMMA-C2' AND company_id = @BETA_ID LIMIT 1);
SET @BETA_VEN_GAMMA  = (SELECT id FROM company WHERE name_sh = 'BETA-V2' AND company_id = @GAMMA_ID LIMIT 1);
SET @ALPHA_CUS_GAMMA = (SELECT id FROM company WHERE name_sh = 'ALPHA-C2' AND company_id = @GAMMA_ID LIMIT 1);

-- ============================================================
-- 2. COMPANY ADDRESSES
-- ============================================================
INSERT INTO company_addr (com_id, adr_tax, city_tax, district_tax, province_tax, zip_tax, adr_bil, city_bil, district_bil, province_bil, zip_bil, valid_start, valid_end)
VALUES
(@ALPHA_ID, '99/1 Silom Road', 'Bangrak', 'Silom', 'Bangkok', '10500', '99/1 Silom Road', 'Bangrak', 'Silom', 'Bangkok', '10500', '2026-01-01', '2027-12-31'),
(@BETA_ID,  '55/2 Rama 9 Road', 'Huay Kwang', 'Bangkapi', 'Bangkok', '10310', '55/2 Rama 9 Road', 'Huay Kwang', 'Bangkapi', 'Bangkok', '10310', '2026-01-01', '2027-12-31'),
(@GAMMA_ID, '123 Sathorn Soi 12', 'Sathorn', 'Yannawa', 'Bangkok', '10120', '123 Sathorn Soi 12', 'Sathorn', 'Yannawa', 'Bangkok', '10120', '2026-01-01', '2027-12-31');

-- ============================================================
-- 3. USERS (authorize = login accounts)
-- ============================================================
-- Password: demo1234 (bcrypt cost-12 hash)
SET @DEMO_PASS = '$2y$12$gu3NKcey1bDmysbeq7T.nOPLK.2DmS8KIkp72CmaNZpfq95kso6Sa';

INSERT INTO authorize (email, name, phone, password, level, company_id, lang, password_migrated, registered_via, email_verified_at)
VALUES
('admin@alphatech.co.th',  'Somchai Prasert',   '081-111-0001', @DEMO_PASS, 9, @ALPHA_ID, 1, 1, 'admin', @NOW),
('sales@alphatech.co.th',  'Waraporn Thana',    '081-111-0002', @DEMO_PASS, 5, @ALPHA_ID, 1, 1, 'admin', @NOW),
('staff@alphatech.co.th',  'Anong Sombat',      '081-111-0003', @DEMO_PASS, 1, @ALPHA_ID, 1, 1, 'admin', @NOW),
('admin@betasupply.co.th', 'Nattaya Srisuk',    '081-222-0001', @DEMO_PASS, 9, @BETA_ID,  1, 1, 'admin', @NOW),
('sales@betasupply.co.th', 'Pimchanok Wongkla', '081-222-0002', @DEMO_PASS, 5, @BETA_ID,  1, 1, 'admin', @NOW),
('admin@gammadesign.co.th','Kittipong Dejsupa',  '081-333-0001', @DEMO_PASS, 9, @GAMMA_ID, 1, 1, 'admin', @NOW),
('design@gammadesign.co.th','Supaporn Kaewlee',  '081-333-0002', @DEMO_PASS, 5, @GAMMA_ID, 1, 1, 'admin', @NOW);

-- ============================================================
-- 4. CATEGORIES (product categories per company)
-- ============================================================
INSERT INTO category (company_id, cat_name, des) VALUES
-- Alpha: IT services
(@ALPHA_ID, 'IT Hardware', 'Computers, servers, networking equipment'),
(@ALPHA_ID, 'Software', 'Software licenses and subscriptions'),
(@ALPHA_ID, 'IT Services', 'Consulting, maintenance, support contracts'),
-- Beta: Supplies
(@BETA_ID, 'Computers', 'Desktop and laptop computers'),
(@BETA_ID, 'Networking', 'Routers, switches, cables'),
(@BETA_ID, 'Peripherals', 'Monitors, keyboards, mice, printers'),
(@BETA_ID, 'Storage', 'Hard drives, SSDs, NAS'),
-- Gamma: Design
(@GAMMA_ID, 'Graphic Design', 'Logo, branding, print design'),
(@GAMMA_ID, 'Web Development', 'Website design and development'),
(@GAMMA_ID, 'Video Production', 'Video editing, animation, motion graphics');

SET @CAT_HW     = (SELECT id FROM category WHERE company_id = @ALPHA_ID AND cat_name = 'IT Hardware' LIMIT 1);
SET @CAT_SW     = (SELECT id FROM category WHERE company_id = @ALPHA_ID AND cat_name = 'Software' LIMIT 1);
SET @CAT_SVC    = (SELECT id FROM category WHERE company_id = @ALPHA_ID AND cat_name = 'IT Services' LIMIT 1);
SET @CAT_COMP   = (SELECT id FROM category WHERE company_id = @BETA_ID AND cat_name = 'Computers' LIMIT 1);
SET @CAT_NET    = (SELECT id FROM category WHERE company_id = @BETA_ID AND cat_name = 'Networking' LIMIT 1);
SET @CAT_PERIPH = (SELECT id FROM category WHERE company_id = @BETA_ID AND cat_name = 'Peripherals' LIMIT 1);
SET @CAT_STOR   = (SELECT id FROM category WHERE company_id = @BETA_ID AND cat_name = 'Storage' LIMIT 1);
SET @CAT_GD     = (SELECT id FROM category WHERE company_id = @GAMMA_ID AND cat_name = 'Graphic Design' LIMIT 1);
SET @CAT_WEB    = (SELECT id FROM category WHERE company_id = @GAMMA_ID AND cat_name = 'Web Development' LIMIT 1);
SET @CAT_VID    = (SELECT id FROM category WHERE company_id = @GAMMA_ID AND cat_name = 'Video Production' LIMIT 1);

-- ============================================================
-- 5. TYPES (sub-categories)
-- ============================================================
INSERT INTO type (company_id, name, des, cat_id) VALUES
-- Alpha types
(@ALPHA_ID, 'Laptop', 'Laptop computers', @CAT_HW),
(@ALPHA_ID, 'Server', 'Server hardware', @CAT_HW),
(@ALPHA_ID, 'Cloud License', 'Cloud service subscriptions', @CAT_SW),
(@ALPHA_ID, 'On-Premise License', 'Perpetual licenses', @CAT_SW),
(@ALPHA_ID, 'Maintenance Contract', 'Annual maintenance', @CAT_SVC),
-- Beta types
(@BETA_ID, 'Desktop PC', 'Desktop computers', @CAT_COMP),
(@BETA_ID, 'Laptop', 'Laptop computers', @CAT_COMP),
(@BETA_ID, 'Router', 'Network routers', @CAT_NET),
(@BETA_ID, 'Switch', 'Network switches', @CAT_NET),
(@BETA_ID, 'Monitor', 'Computer monitors', @CAT_PERIPH),
(@BETA_ID, 'Keyboard & Mouse', 'Input devices', @CAT_PERIPH),
(@BETA_ID, 'SSD', 'Solid state drives', @CAT_STOR),
(@BETA_ID, 'HDD', 'Hard disk drives', @CAT_STOR),
-- Gamma types
(@GAMMA_ID, 'Logo Design', 'Logo and branding', @CAT_GD),
(@GAMMA_ID, 'Brochure Design', 'Print brochure design', @CAT_GD),
(@GAMMA_ID, 'WordPress Site', 'WordPress website', @CAT_WEB),
(@GAMMA_ID, 'Custom Web App', 'Custom web application', @CAT_WEB),
(@GAMMA_ID, 'Corporate Video', 'Corporate video production', @CAT_VID);

SET @T_LAPTOP_A   = (SELECT id FROM type WHERE company_id = @ALPHA_ID AND name = 'Laptop' LIMIT 1);
SET @T_SERVER_A   = (SELECT id FROM type WHERE company_id = @ALPHA_ID AND name = 'Server' LIMIT 1);
SET @T_CLOUD_A    = (SELECT id FROM type WHERE company_id = @ALPHA_ID AND name = 'Cloud License' LIMIT 1);
SET @T_ONPREM_A   = (SELECT id FROM type WHERE company_id = @ALPHA_ID AND name = 'On-Premise License' LIMIT 1);
SET @T_MAINT_A    = (SELECT id FROM type WHERE company_id = @ALPHA_ID AND name = 'Maintenance Contract' LIMIT 1);
SET @T_DESKTOP_B  = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'Desktop PC' LIMIT 1);
SET @T_LAPTOP_B   = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'Laptop' LIMIT 1);
SET @T_ROUTER_B   = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'Router' LIMIT 1);
SET @T_SWITCH_B   = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'Switch' LIMIT 1);
SET @T_MONITOR_B  = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'Monitor' LIMIT 1);
SET @T_KBMOUSE_B  = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'Keyboard & Mouse' LIMIT 1);
SET @T_SSD_B      = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'SSD' LIMIT 1);
SET @T_HDD_B      = (SELECT id FROM type WHERE company_id = @BETA_ID AND name = 'HDD' LIMIT 1);
SET @T_LOGO_G     = (SELECT id FROM type WHERE company_id = @GAMMA_ID AND name = 'Logo Design' LIMIT 1);
SET @T_BROCH_G    = (SELECT id FROM type WHERE company_id = @GAMMA_ID AND name = 'Brochure Design' LIMIT 1);
SET @T_WP_G       = (SELECT id FROM type WHERE company_id = @GAMMA_ID AND name = 'WordPress Site' LIMIT 1);
SET @T_WEBAPP_G   = (SELECT id FROM type WHERE company_id = @GAMMA_ID AND name = 'Custom Web App' LIMIT 1);
SET @T_VIDEO_G    = (SELECT id FROM type WHERE company_id = @GAMMA_ID AND name = 'Corporate Video' LIMIT 1);

-- ============================================================
-- 6. BRANDS
-- ============================================================
INSERT INTO brand (company_id, brand_name, des, logo, ven_id) VALUES
-- Alpha brands (vendors they buy from)
(@ALPHA_ID, 'Dell', 'Dell Technologies', '', @BETA_VEN_ALPHA),
(@ALPHA_ID, 'HP', 'Hewlett Packard', '', @BETA_VEN_ALPHA),
(@ALPHA_ID, 'Microsoft', 'Microsoft Corporation', '', 0),
(@ALPHA_ID, 'Gamma Creative', 'Gamma Design Studio', '', 0),
-- Beta brands (they distribute)
(@BETA_ID, 'Dell', 'Dell Technologies', '', 0),
(@BETA_ID, 'HP', 'Hewlett Packard', '', 0),
(@BETA_ID, 'Lenovo', 'Lenovo Group', '', 0),
(@BETA_ID, 'Cisco', 'Cisco Systems', '', 0),
(@BETA_ID, 'Samsung', 'Samsung Electronics', '', 0),
(@BETA_ID, 'Logitech', 'Logitech International', '', 0),
-- Gamma brands (their service packages)
(@GAMMA_ID, 'Gamma Standard', 'Standard package', '', 0),
(@GAMMA_ID, 'Gamma Premium', 'Premium package', '', 0);

SET @BR_DELL_A   = (SELECT id FROM brand WHERE company_id = @ALPHA_ID AND brand_name = 'Dell' LIMIT 1);
SET @BR_HP_A     = (SELECT id FROM brand WHERE company_id = @ALPHA_ID AND brand_name = 'HP' LIMIT 1);
SET @BR_MS_A     = (SELECT id FROM brand WHERE company_id = @ALPHA_ID AND brand_name = 'Microsoft' LIMIT 1);
SET @BR_GCREA_A  = (SELECT id FROM brand WHERE company_id = @ALPHA_ID AND brand_name = 'Gamma Creative' LIMIT 1);
SET @BR_DELL_B   = (SELECT id FROM brand WHERE company_id = @BETA_ID AND brand_name = 'Dell' LIMIT 1);
SET @BR_HP_B     = (SELECT id FROM brand WHERE company_id = @BETA_ID AND brand_name = 'HP' LIMIT 1);
SET @BR_LEN_B    = (SELECT id FROM brand WHERE company_id = @BETA_ID AND brand_name = 'Lenovo' LIMIT 1);
SET @BR_CISCO_B  = (SELECT id FROM brand WHERE company_id = @BETA_ID AND brand_name = 'Cisco' LIMIT 1);
SET @BR_SAM_B    = (SELECT id FROM brand WHERE company_id = @BETA_ID AND brand_name = 'Samsung' LIMIT 1);
SET @BR_LOGI_B   = (SELECT id FROM brand WHERE company_id = @BETA_ID AND brand_name = 'Logitech' LIMIT 1);
SET @BR_GSTD_G   = (SELECT id FROM brand WHERE company_id = @GAMMA_ID AND brand_name = 'Gamma Standard' LIMIT 1);
SET @BR_GPRM_G   = (SELECT id FROM brand WHERE company_id = @GAMMA_ID AND brand_name = 'Gamma Premium' LIMIT 1);

-- ============================================================
-- 7. MODELS (products/services with prices)
-- ============================================================
INSERT INTO model (company_id, type_id, brand_id, model_name, des, price) VALUES
-- Alpha models
(@ALPHA_ID, @T_LAPTOP_A,  @BR_DELL_A,  'Dell Latitude 5540',     '14" i7/16GB/512GB SSD',   35000),
(@ALPHA_ID, @T_LAPTOP_A,  @BR_HP_A,    'HP ProBook 450 G10',     '15.6" i5/16GB/256GB SSD', 28000),
(@ALPHA_ID, @T_SERVER_A,  @BR_DELL_A,  'Dell PowerEdge R750',    'Rack server Xeon/64GB',   185000),
(@ALPHA_ID, @T_CLOUD_A,   @BR_MS_A,    'Microsoft 365 Business', 'Per user/month',          450),
(@ALPHA_ID, @T_MAINT_A,   @BR_DELL_A,  'Dell ProSupport 3Y',     '3-year maintenance',      12000),
-- Beta models (their catalog)
(@BETA_ID, @T_DESKTOP_B, @BR_DELL_B,   'Dell OptiPlex 7010',     'i5/16GB/512SSD',          22500),
(@BETA_ID, @T_LAPTOP_B,  @BR_DELL_B,   'Dell Latitude 5540',     '14" i7/16GB/512SSD',      32000),
(@BETA_ID, @T_LAPTOP_B,  @BR_HP_B,     'HP ProBook 450 G10',     '15.6" i5/16GB/256SSD',    25500),
(@BETA_ID, @T_LAPTOP_B,  @BR_LEN_B,    'Lenovo ThinkPad T14s',   '14" Ryzen7/16GB/512SSD',  34000),
(@BETA_ID, @T_ROUTER_B,  @BR_CISCO_B,  'Cisco ISR 1111',         '4-port GE WAN router',    28000),
(@BETA_ID, @T_SWITCH_B,  @BR_CISCO_B,  'Cisco Catalyst 1000-24', '24-port GE switch',       18500),
(@BETA_ID, @T_MONITOR_B, @BR_SAM_B,    'Samsung S27R650',        '27" FHD IPS monitor',     8500),
(@BETA_ID, @T_MONITOR_B, @BR_DELL_B,   'Dell P2723QE',           '27" 4K USB-C monitor',    14500),
(@BETA_ID, @T_KBMOUSE_B, @BR_LOGI_B,   'Logitech MK540',        'Wireless keyboard+mouse',  1450),
(@BETA_ID, @T_SSD_B,     @BR_SAM_B,    'Samsung 870 EVO 1TB',    '1TB SATA SSD',            2800),
(@BETA_ID, @T_HDD_B,     @BR_SAM_B,    'Seagate Barracuda 2TB',  '2TB 7200rpm HDD',         1950),
-- Gamma models (design services)
(@GAMMA_ID, @T_LOGO_G,   @BR_GSTD_G,  'Logo Package Standard',  'Logo + 3 revisions',       15000),
(@GAMMA_ID, @T_LOGO_G,   @BR_GPRM_G,  'Logo Package Premium',   'Logo + brand guidelines',  35000),
(@GAMMA_ID, @T_BROCH_G,  @BR_GSTD_G,  'Brochure A4 Tri-fold',   'Design + print-ready PDF', 8000),
(@GAMMA_ID, @T_WP_G,     @BR_GSTD_G,  'WordPress Basic',        '5-page WordPress site',    25000),
(@GAMMA_ID, @T_WP_G,     @BR_GPRM_G,  'WordPress E-Commerce',   'WooCommerce online store', 65000),
(@GAMMA_ID, @T_WEBAPP_G, @BR_GPRM_G,  'Custom Dashboard',       'Admin dashboard web app',  120000),
(@GAMMA_ID, @T_VIDEO_G,  @BR_GSTD_G,  'Corporate Video 3min',   '3-minute corporate video', 45000),
(@GAMMA_ID, @T_VIDEO_G,  @BR_GPRM_G,  'Corporate Video 5min',   '5-minute with animation',  85000);

-- Fetch model IDs for products
SET @M_DELL_LAT  = (SELECT id FROM model WHERE company_id = @ALPHA_ID AND model_name = 'Dell Latitude 5540' LIMIT 1);
SET @M_HP_PRO_A  = (SELECT id FROM model WHERE company_id = @ALPHA_ID AND model_name = 'HP ProBook 450 G10' LIMIT 1);
SET @M_DELL_SRV  = (SELECT id FROM model WHERE company_id = @ALPHA_ID AND model_name = 'Dell PowerEdge R750' LIMIT 1);
SET @M_MS365     = (SELECT id FROM model WHERE company_id = @ALPHA_ID AND model_name = 'Microsoft 365 Business' LIMIT 1);

SET @M_DELL_OPT  = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Dell OptiPlex 7010' LIMIT 1);
SET @M_DELL_LB   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Dell Latitude 5540' LIMIT 1);
SET @M_HP_PRO_B  = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'HP ProBook 450 G10' LIMIT 1);
SET @M_LEN_T14   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Lenovo ThinkPad T14s' LIMIT 1);
SET @M_CISCO_R   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Cisco ISR 1111' LIMIT 1);
SET @M_CISCO_S   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Cisco Catalyst 1000-24' LIMIT 1);
SET @M_SAM_MON   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Samsung S27R650' LIMIT 1);
SET @M_SAM_SSD   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Samsung 870 EVO 1TB' LIMIT 1);
SET @M_LOGI_KB   = (SELECT id FROM model WHERE company_id = @BETA_ID AND model_name = 'Logitech MK540' LIMIT 1);

SET @M_LOGO_STD  = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'Logo Package Standard' LIMIT 1);
SET @M_LOGO_PRE  = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'Logo Package Premium' LIMIT 1);
SET @M_BROCH     = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'Brochure A4 Tri-fold' LIMIT 1);
SET @M_WP_BASIC  = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'WordPress Basic' LIMIT 1);
SET @M_WP_ECOM   = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'WordPress E-Commerce' LIMIT 1);
SET @M_DASH      = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'Custom Dashboard' LIMIT 1);
SET @M_VID3      = (SELECT id FROM model WHERE company_id = @GAMMA_ID AND model_name = 'Corporate Video 3min' LIMIT 1);

-- ============================================================
-- 8. PAYMENT METHODS (per company)
-- ============================================================
INSERT INTO payment (payment_name, payment_des, com_id) VALUES
('Bank Transfer', 'Transfer to company bank account', @ALPHA_ID),
('Credit Card', 'Visa/Mastercard', @ALPHA_ID),
('Cash', 'Cash payment', @ALPHA_ID),
('Bank Transfer', 'Transfer to company bank account', @BETA_ID),
('Cash on Delivery', 'COD', @BETA_ID),
('Cheque', 'Company cheque', @BETA_ID),
('Bank Transfer', 'Transfer to company bank account', @GAMMA_ID),
('Credit Card', 'Visa/Mastercard', @GAMMA_ID);

INSERT INTO payment_method (company_id, code, name, name_th, icon, is_gateway, is_active, sort_order) VALUES
(@ALPHA_ID, 'BANK-ALPHA',  'Bank Transfer', 'โอนเงิน',     'fa-university', 0, 1, 1),
(@ALPHA_ID, 'CC-ALPHA',    'Credit Card',   'บัตรเครดิต',   'fa-credit-card', 0, 1, 2),
(@ALPHA_ID, 'CASH-ALPHA',  'Cash',          'เงินสด',       'fa-money', 0, 1, 3),
(@BETA_ID,  'BANK-BETA',   'Bank Transfer', 'โอนเงิน',     'fa-university', 0, 1, 1),
(@BETA_ID,  'COD-BETA',    'Cash on Delivery','เก็บเงินปลายทาง','fa-truck', 0, 1, 2),
(@BETA_ID,  'CHQ-BETA',    'Cheque',        'เช็ค',         'fa-file-text', 0, 1, 3),
(@GAMMA_ID, 'BANK-GAMMA',  'Bank Transfer', 'โอนเงิน',     'fa-university', 0, 1, 1),
(@GAMMA_ID, 'CC-GAMMA',    'Credit Card',   'บัตรเครดิต',   'fa-credit-card', 0, 1, 2);

-- ============================================================
-- 9. PURCHASE REQUESTS (PR)
-- ============================================================
-- Alpha buys laptops from Beta (PR→PO)
INSERT INTO pr (company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby) VALUES
(@ALPHA_ID, 'IT Equipment Q1-2026',     'Laptops and monitors for new hires',    0, @GAMMA_CUS_ALPHA, @BETA_VEN_ALPHA, '2026-01-15', '2', 0, 0, 0),
(@ALPHA_ID, 'Network Upgrade Project',  'Router and switch for office network',  0, @GAMMA_CUS_ALPHA, @BETA_VEN_ALPHA, '2026-02-01', '1', 0, 0, 0),
(@ALPHA_ID, 'Design Services - Rebrand','Logo and brochure redesign',            0, @GAMMA_CUS_ALPHA, 0,                '2026-03-01', '0', 0, 0, 0);

SET @PR_ALPHA_1 = (SELECT id FROM pr WHERE company_id = @ALPHA_ID AND name = 'IT Equipment Q1-2026' LIMIT 1);
SET @PR_ALPHA_2 = (SELECT id FROM pr WHERE company_id = @ALPHA_ID AND name = 'Network Upgrade Project' LIMIT 1);
SET @PR_ALPHA_3 = (SELECT id FROM pr WHERE company_id = @ALPHA_ID AND name = 'Design Services - Rebrand' LIMIT 1);

-- Beta receives orders from Alpha and Gamma
INSERT INTO pr (company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby) VALUES
(@BETA_ID, 'Alpha Tech - Laptop Order',   '5x Dell Latitude for Alpha',      0, @ALPHA_CUS_BETA, 0, '2026-01-20', '2', 0, 0, 0),
(@BETA_ID, 'Gamma Design - Equipment',    'MacBook + monitors for Gamma',     0, @GAMMA_CUS_BETA, 0, '2026-02-10', '1', 0, 0, 0),
(@BETA_ID, 'Alpha Tech - Network Gear',   'Cisco router + switch',            0, @ALPHA_CUS_BETA, 0, '2026-02-05', '2', 0, 0, 0);

SET @PR_BETA_1 = (SELECT id FROM pr WHERE company_id = @BETA_ID AND name = 'Alpha Tech - Laptop Order' LIMIT 1);
SET @PR_BETA_2 = (SELECT id FROM pr WHERE company_id = @BETA_ID AND name = 'Gamma Design - Equipment' LIMIT 1);
SET @PR_BETA_3 = (SELECT id FROM pr WHERE company_id = @BETA_ID AND name = 'Alpha Tech - Network Gear' LIMIT 1);

-- Gamma sells design services to Alpha
INSERT INTO pr (company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby) VALUES
(@GAMMA_ID, 'Alpha Tech Rebranding',     'Complete rebrand package',          0, @ALPHA_CUS_GAMMA, @BETA_VEN_GAMMA, '2026-03-05', '2', 0, 0, 0),
(@GAMMA_ID, 'Alpha Tech Corporate Video','3-min corporate video',             0, @ALPHA_CUS_GAMMA, 0,                '2026-03-15', '0', 0, 0, 0);

SET @PR_GAMMA_1 = (SELECT id FROM pr WHERE company_id = @GAMMA_ID AND name = 'Alpha Tech Rebranding' LIMIT 1);
SET @PR_GAMMA_2 = (SELECT id FROM pr WHERE company_id = @GAMMA_ID AND name = 'Alpha Tech Corporate Video' LIMIT 1);

-- ============================================================
-- 10. PURCHASE ORDERS / QUOTATIONS (PO)
-- ============================================================
INSERT INTO po (company_id, name, ref, tax, date, valid_pay, deliver_date, pic, dis, bandven, vat, `over`) VALUES
-- Alpha POs
(@ALPHA_ID, 'QT-2026-0001', @PR_ALPHA_1, '69010001', '2026-01-16', '2026-02-15', '2026-01-25', '', 0, @BETA_VEN_ALPHA, 7, 0),
(@ALPHA_ID, 'QT-2026-0002', @PR_ALPHA_2, '69010002', '2026-02-02', '2026-03-02', '2026-02-15', '', 5, @BETA_VEN_ALPHA, 7, 0),
-- Beta POs (quotations to customers)
(@BETA_ID, 'QT-2026-0010', @PR_BETA_1, '69020001', '2026-01-21', '2026-02-20', '2026-01-30', '', 3, 0, 7, 0),
(@BETA_ID, 'QT-2026-0011', @PR_BETA_2, '69020002', '2026-02-11', '2026-03-11', '2026-02-25', '', 0, 0, 7, 0),
(@BETA_ID, 'QT-2026-0012', @PR_BETA_3, '69020003', '2026-02-06', '2026-03-06', '2026-02-20', '', 5, 0, 7, 0),
-- Gamma POs
(@GAMMA_ID, 'QT-2026-0020', @PR_GAMMA_1, '69030001', '2026-03-06', '2026-04-05', '2026-04-15', '', 0, 0, 7, 0);

SET @PO_ALPHA_1 = (SELECT id FROM po WHERE company_id = @ALPHA_ID AND name = 'QT-2026-0001' LIMIT 1);
SET @PO_ALPHA_2 = (SELECT id FROM po WHERE company_id = @ALPHA_ID AND name = 'QT-2026-0002' LIMIT 1);
SET @PO_BETA_1  = (SELECT id FROM po WHERE company_id = @BETA_ID AND name = 'QT-2026-0010' LIMIT 1);
SET @PO_BETA_2  = (SELECT id FROM po WHERE company_id = @BETA_ID AND name = 'QT-2026-0011' LIMIT 1);
SET @PO_BETA_3  = (SELECT id FROM po WHERE company_id = @BETA_ID AND name = 'QT-2026-0012' LIMIT 1);
SET @PO_GAMMA_1 = (SELECT id FROM po WHERE company_id = @GAMMA_ID AND name = 'QT-2026-0020' LIMIT 1);

-- ============================================================
-- 11. PRODUCTS (line items on POs)
-- ============================================================
INSERT INTO product (company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id) VALUES
-- Alpha PO1: 5 laptops + 5 monitors
(@ALPHA_ID, @PO_ALPHA_1, 35000,  0, @BR_DELL_A, @M_DELL_LAT,  @T_LAPTOP_A, 5, 0, 0, 'Dell Latitude for new hires',     0, 0, 0, '2029-01-16', 0),
(@ALPHA_ID, @PO_ALPHA_1, 8500,   0, @BR_DELL_A, 0,             @T_LAPTOP_A, 5, 0, 0, 'Samsung 27\" monitors',           0, 0, 0, '2029-01-16', 0),
-- Alpha PO2: network gear
(@ALPHA_ID, @PO_ALPHA_2, 28000,  0, @BR_DELL_A, 0,             @T_LAPTOP_A, 1, 0, 0, 'Cisco ISR 1111 Router',           0, 0, 0, '2029-02-02', 0),
(@ALPHA_ID, @PO_ALPHA_2, 18500,  0, @BR_DELL_A, 0,             @T_LAPTOP_A, 2, 0, 0, 'Cisco Catalyst switch',           0, 0, 0, '2029-02-02', 0),
-- Beta PO1: 5 Dell Latitude to Alpha
(@BETA_ID, @PO_BETA_1, 32000,  0, @BR_DELL_B, @M_DELL_LB,   @T_LAPTOP_B, 5, 0, 0, 'Dell Latitude 5540',              0, 0, 0, '2029-01-21', 0),
(@BETA_ID, @PO_BETA_1, 8500,   0, @BR_SAM_B,  @M_SAM_MON,   @T_MONITOR_B, 5, 0, 0, 'Samsung S27R650 monitor',         0, 0, 0, '2029-01-21', 0),
-- Beta PO2: equipment for Gamma
(@BETA_ID, @PO_BETA_2, 34000,  0, @BR_LEN_B,  @M_LEN_T14,   @T_LAPTOP_B, 2, 0, 0, 'Lenovo ThinkPad T14s',            0, 0, 0, '2029-02-11', 0),
(@BETA_ID, @PO_BETA_2, 14500,  0, @BR_DELL_B, 0,             @T_MONITOR_B, 2, 0, 0, 'Dell P2723QE 4K monitor',         0, 0, 0, '2029-02-11', 0),
-- Beta PO3: network for Alpha
(@BETA_ID, @PO_BETA_3, 28000,  0, @BR_CISCO_B, @M_CISCO_R,  @T_ROUTER_B, 1, 0, 0, 'Cisco ISR 1111',                  0, 0, 0, '2029-02-06', 0),
(@BETA_ID, @PO_BETA_3, 18500,  0, @BR_CISCO_B, @M_CISCO_S,  @T_SWITCH_B, 2, 0, 0, 'Cisco Catalyst 1000-24',          0, 0, 0, '2029-02-06', 0),
(@BETA_ID, @PO_BETA_3, 1450,   0, @BR_LOGI_B,  @M_LOGI_KB,  @T_KBMOUSE_B, 10, 0, 0, 'Logitech MK540 kb+mouse set',   0, 0, 0, '2029-02-06', 0),
-- Gamma PO1: rebranding for Alpha
(@GAMMA_ID, @PO_GAMMA_1, 35000, 0, @BR_GPRM_G, @M_LOGO_PRE, @T_LOGO_G,  1, 0, 0, 'Premium logo + brand guidelines', 0, 0, 0, '2026-04-15', 0),
(@GAMMA_ID, @PO_GAMMA_1, 8000,  0, @BR_GSTD_G, @M_BROCH,    @T_BROCH_G, 2, 0, 0, 'Tri-fold brochure design',        0, 0, 0, '2026-04-15', 0);

-- ============================================================
-- 12. INVOICES (for completed POs)
-- ============================================================
INSERT INTO iv (id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, countmailinv, countmailtax, payment_status)
VALUES
(@PO_BETA_1,  @BETA_ID,  69020001, @ALPHA_CUS_BETA, '2026-02-01', 'IV', 69020001, 0, '2026-02-01', 1, 0, 0, 'paid'),
(@PO_BETA_3,  @BETA_ID,  69020003, @ALPHA_CUS_BETA, '2026-02-20', 'IV', 69020003, 0, '2026-02-20', 1, 0, 0, 'pending'),
(@PO_GAMMA_1, @GAMMA_ID, 69030001, @ALPHA_CUS_GAMMA, '2026-03-20', 'IV', 69030001, 0, '2026-03-20', 0, 0, 0, 'pending');

-- ============================================================
-- 13. RECEIPTS (for paid invoices)
-- ============================================================
INSERT INTO receipt (company_id, name, phone, email, createdate, description, payment_method, payment_date, status, invoice_id, source_type, vender, rep_no, rep_rw, brand, subtotal, after_discount, vat_amount, total_amount, vat, dis, include_vat)
VALUES
(@BETA_ID, 'Alpha Tech Solutions', '02-111-1111', 'info@alphatech.co.th', '2026-02-05',
 'Payment for laptop order QT-2026-0010', 'Bank Transfer', '2026-02-05', 'confirmed',
 @PO_BETA_1, 'invoice', @ALPHA_CUS_BETA, 1, 'RC', 0,
 202500.00, 196425.00, 13749.75, 210174.75, 7, 3, 1);

-- ============================================================
-- 14. EXPENSE CATEGORIES (per company)
-- ============================================================
INSERT INTO expense_categories (com_id, name, name_th, code, icon, color, is_active, sort_order) VALUES
-- Alpha
(@ALPHA_ID, 'Office Rent',           'ค่าเช่าสำนักงาน',         'EXP-RENT',  'fa-building',    '#3b82f6', 1, 1),
(@ALPHA_ID, 'Utilities',             'ค่าสาธารณูปโภค',           'EXP-UTIL',  'fa-bolt',         '#f59e0b', 1, 2),
(@ALPHA_ID, 'Office Supplies',       'วัสดุสำนักงาน',             'EXP-SUP',   'fa-pencil',       '#10b981', 1, 3),
(@ALPHA_ID, 'Travel & Transport',    'ค่าเดินทาง',               'EXP-TRVL',  'fa-car',          '#8b5cf6', 1, 4),
(@ALPHA_ID, 'Salary & Wages',        'เงินเดือนและค่าจ้าง',       'EXP-SAL',   'fa-users',        '#ef4444', 1, 5),
(@ALPHA_ID, 'IT Equipment',          'อุปกรณ์ไอที',              'EXP-IT',    'fa-desktop',      '#06b6d4', 1, 6),
(@ALPHA_ID, 'Marketing',             'การตลาด',                 'EXP-MKT',   'fa-bullhorn',     '#f97316', 1, 7),
(@ALPHA_ID, 'Professional Fees',     'ค่าบริการวิชาชีพ',         'EXP-PROF',  'fa-briefcase',    '#6366f1', 1, 8),
(@ALPHA_ID, 'Miscellaneous',         'อื่นๆ',                    'EXP-MISC',  'fa-folder',       '#64748b', 1, 9),
-- Beta
(@BETA_ID, 'Warehouse Rent',         'ค่าเช่าคลังสินค้า',        'EXP-RENT',  'fa-building',     '#3b82f6', 1, 1),
(@BETA_ID, 'Utilities',              'ค่าสาธารณูปโภค',           'EXP-UTIL',  'fa-bolt',         '#f59e0b', 1, 2),
(@BETA_ID, 'Shipping & Logistics',   'ค่าขนส่ง',                'EXP-SHIP',  'fa-truck',        '#10b981', 1, 3),
(@BETA_ID, 'Salary & Wages',         'เงินเดือนและค่าจ้าง',      'EXP-SAL',   'fa-users',        '#ef4444', 1, 4),
(@BETA_ID, 'Inventory Purchase',     'ซื้อสินค้า',              'EXP-INV',   'fa-cubes',        '#8b5cf6', 1, 5),
(@BETA_ID, 'Marketing',              'การตลาด',                 'EXP-MKT',   'fa-bullhorn',     '#f97316', 1, 6),
(@BETA_ID, 'Miscellaneous',          'อื่นๆ',                    'EXP-MISC',  'fa-folder',       '#64748b', 1, 7),
-- Gamma
(@GAMMA_ID, 'Studio Rent',           'ค่าเช่าสตูดิโอ',          'EXP-RENT',  'fa-building',     '#3b82f6', 1, 1),
(@GAMMA_ID, 'Utilities',             'ค่าสาธารณูปโภค',           'EXP-UTIL',  'fa-bolt',         '#f59e0b', 1, 2),
(@GAMMA_ID, 'Software Subscriptions','ค่าซอฟต์แวร์',            'EXP-SOFT',  'fa-cloud',        '#10b981', 1, 3),
(@GAMMA_ID, 'Freelancer Fees',       'ค่าจ้างฟรีแลนซ์',         'EXP-FREE',  'fa-user',         '#8b5cf6', 1, 4),
(@GAMMA_ID, 'Salary & Wages',        'เงินเดือนและค่าจ้าง',      'EXP-SAL',   'fa-users',        '#ef4444', 1, 5),
(@GAMMA_ID, 'Equipment',             'อุปกรณ์',                 'EXP-EQUIP', 'fa-desktop',      '#06b6d4', 1, 6),
(@GAMMA_ID, 'Miscellaneous',         'อื่นๆ',                    'EXP-MISC',  'fa-folder',       '#64748b', 1, 7);

-- Get category IDs for expenses
SET @EC_RENT_A  = (SELECT id FROM expense_categories WHERE com_id = @ALPHA_ID AND code = 'EXP-RENT' LIMIT 1);
SET @EC_UTIL_A  = (SELECT id FROM expense_categories WHERE com_id = @ALPHA_ID AND code = 'EXP-UTIL' LIMIT 1);
SET @EC_IT_A    = (SELECT id FROM expense_categories WHERE com_id = @ALPHA_ID AND code = 'EXP-IT' LIMIT 1);
SET @EC_SAL_A   = (SELECT id FROM expense_categories WHERE com_id = @ALPHA_ID AND code = 'EXP-SAL' LIMIT 1);
SET @EC_MKT_A   = (SELECT id FROM expense_categories WHERE com_id = @ALPHA_ID AND code = 'EXP-MKT' LIMIT 1);
SET @EC_PROF_A  = (SELECT id FROM expense_categories WHERE com_id = @ALPHA_ID AND code = 'EXP-PROF' LIMIT 1);

SET @EC_RENT_B  = (SELECT id FROM expense_categories WHERE com_id = @BETA_ID AND code = 'EXP-RENT' LIMIT 1);
SET @EC_SHIP_B  = (SELECT id FROM expense_categories WHERE com_id = @BETA_ID AND code = 'EXP-SHIP' LIMIT 1);
SET @EC_SAL_B   = (SELECT id FROM expense_categories WHERE com_id = @BETA_ID AND code = 'EXP-SAL' LIMIT 1);
SET @EC_INV_B   = (SELECT id FROM expense_categories WHERE com_id = @BETA_ID AND code = 'EXP-INV' LIMIT 1);

SET @EC_RENT_G  = (SELECT id FROM expense_categories WHERE com_id = @GAMMA_ID AND code = 'EXP-RENT' LIMIT 1);
SET @EC_SOFT_G  = (SELECT id FROM expense_categories WHERE com_id = @GAMMA_ID AND code = 'EXP-SOFT' LIMIT 1);
SET @EC_FREE_G  = (SELECT id FROM expense_categories WHERE com_id = @GAMMA_ID AND code = 'EXP-FREE' LIMIT 1);
SET @EC_SAL_G   = (SELECT id FROM expense_categories WHERE com_id = @GAMMA_ID AND code = 'EXP-SAL' LIMIT 1);

-- ============================================================
-- 15. EXPENSES
-- ============================================================
INSERT INTO expenses (com_id, expense_number, category_id, title, amount, vat_rate, vat_amount, wht_rate, wht_amount, net_amount, expense_date, due_date, paid_date, payment_method, vendor_name, vendor_tax_id, project_name, status) VALUES
-- Alpha expenses (Jan-Mar 2026)
(@ALPHA_ID, 'EXP-A-202601-0001', @EC_RENT_A,  'Office rent January',          45000,   NULL, 0,    NULL, 0,    45000,    '2026-01-05', '2026-01-31', '2026-01-05', 'Bank Transfer', 'Silom Tower Management',  '0105540009876', NULL,                    'paid'),
(@ALPHA_ID, 'EXP-A-202601-0002', @EC_UTIL_A,  'Electricity January',          8500,    7,    595,  NULL, 0,    9095,     '2026-01-25', '2026-02-10', '2026-02-08', 'Bank Transfer', 'MEA',                     '0994000165780', NULL,                    'paid'),
(@ALPHA_ID, 'EXP-A-202601-0003', @EC_SAL_A,   'Monthly salary January',       450000,  NULL, 0,    NULL, 0,    450000,   '2026-01-31', NULL,         '2026-01-31', 'Bank Transfer', NULL,                      NULL,            NULL,                    'paid'),
(@ALPHA_ID, 'EXP-A-202602-0004', @EC_RENT_A,  'Office rent February',         45000,   NULL, 0,    NULL, 0,    45000,    '2026-02-05', '2026-02-28', '2026-02-05', 'Bank Transfer', 'Silom Tower Management',  '0105540009876', NULL,                    'paid'),
(@ALPHA_ID, 'EXP-A-202602-0005', @EC_IT_A,    'Dell Latitude laptops (5x)',   175000,  7,    12250,NULL, 0,    187250,   '2026-02-01', '2026-02-28', '2026-02-15', 'Bank Transfer', 'Beta Supply Co., Ltd.',   '0105560002222', 'IT Equipment Q1-2026',  'paid'),
(@ALPHA_ID, 'EXP-A-202603-0006', @EC_MKT_A,   'Google Ads March',             25000,   7,    1750, 3,    750,  26000,    '2026-03-01', '2026-03-31', NULL,         'Credit Card',   'Google Thailand',         '0107560000150', NULL,                    'approved'),
(@ALPHA_ID, 'EXP-A-202603-0007', @EC_PROF_A,  'Rebranding design services',   51000,   7,    3570, 3,    1530, 53040,    '2026-03-20', '2026-04-20', NULL,         'Bank Transfer', 'Gamma Design Studio',     '0105560003333', 'Rebrand Project',       'pending'),
-- Beta expenses
(@BETA_ID, 'EXP-B-202601-0001', @EC_RENT_B,   'Warehouse rent January',       65000,   NULL, 0,    NULL, 0,    65000,    '2026-01-05', '2026-01-31', '2026-01-05', 'Bank Transfer', 'Rama 9 Warehouse',        '0105550008765', NULL,                    'paid'),
(@BETA_ID, 'EXP-B-202601-0002', @EC_SAL_B,    'Monthly salary January',       380000,  NULL, 0,    NULL, 0,    380000,   '2026-01-31', NULL,         '2026-01-31', 'Bank Transfer', NULL,                      NULL,            NULL,                    'paid'),
(@BETA_ID, 'EXP-B-202601-0003', @EC_INV_B,    'Dell inventory purchase',      750000,  7,    52500,NULL, 0,    802500,   '2026-01-10', '2026-02-10', '2026-02-05', 'Bank Transfer', 'Dell Thailand',           '0107550000200', NULL,                    'paid'),
(@BETA_ID, 'EXP-B-202602-0004', @EC_SHIP_B,   'Shipping to Alpha (laptops)',  3500,    7,    245,  NULL, 0,    3745,     '2026-01-30', '2026-02-15', '2026-02-10', 'Cash',          'Kerry Express',           '0105550001234', NULL,                    'paid'),
(@BETA_ID, 'EXP-B-202602-0005', @EC_RENT_B,   'Warehouse rent February',      65000,   NULL, 0,    NULL, 0,    65000,    '2026-02-05', '2026-02-28', '2026-02-05', 'Bank Transfer', 'Rama 9 Warehouse',        '0105550008765', NULL,                    'paid'),
-- Gamma expenses
(@GAMMA_ID, 'EXP-G-202601-0001', @EC_RENT_G,  'Studio rent January',          35000,   NULL, 0,    NULL, 0,    35000,    '2026-01-05', '2026-01-31', '2026-01-05', 'Bank Transfer', 'Sathorn Loft Building',   '0105550007654', NULL,                    'paid'),
(@GAMMA_ID, 'EXP-G-202601-0002', @EC_SOFT_G,  'Adobe CC annual license',      18000,   7,    1260, NULL, 0,    19260,    '2026-01-15', '2026-01-31', '2026-01-15', 'Credit Card',   'Adobe Systems',           NULL,            NULL,                    'paid'),
(@GAMMA_ID, 'EXP-G-202601-0003', @EC_SAL_G,   'Monthly salary January',       280000,  NULL, 0,    NULL, 0,    280000,   '2026-01-31', NULL,         '2026-01-31', 'Bank Transfer', NULL,                      NULL,            NULL,                    'paid'),
(@GAMMA_ID, 'EXP-G-202603-0004', @EC_FREE_G,  'Freelance videographer',       25000,   7,    1750, 3,    750,  26000,    '2026-03-10', '2026-03-31', NULL,         'Bank Transfer', 'Pichai Singha',           '1100100123456', 'Alpha Corp Video',      'pending');

-- ============================================================
-- 16. CHART OF ACCOUNTS (per company)
-- ============================================================
INSERT INTO chart_of_accounts (com_id, account_code, account_name, account_name_th, account_type, level, is_active, normal_balance) VALUES
-- Alpha accounts
(@ALPHA_ID, '1000', 'Cash and Bank',          'เงินสดและเงินฝากธนาคาร',   'asset',     1, 1, 'debit'),
(@ALPHA_ID, '1100', 'Accounts Receivable',    'ลูกหนี้การค้า',            'asset',     1, 1, 'debit'),
(@ALPHA_ID, '1200', 'Inventory',              'สินค้าคงเหลือ',            'asset',     1, 1, 'debit'),
(@ALPHA_ID, '2000', 'Accounts Payable',       'เจ้าหนี้การค้า',            'liability', 1, 1, 'credit'),
(@ALPHA_ID, '2100', 'VAT Payable',            'ภาษีมูลค่าเพิ่มค้างจ่าย',   'liability', 1, 1, 'credit'),
(@ALPHA_ID, '3000', 'Owner Equity',           'ทุน',                     'equity',    1, 1, 'credit'),
(@ALPHA_ID, '4000', 'Service Revenue',        'รายได้จากการให้บริการ',     'revenue',   1, 1, 'credit'),
(@ALPHA_ID, '4100', 'Product Sales',          'รายได้จากการขายสินค้า',     'revenue',   1, 1, 'credit'),
(@ALPHA_ID, '5000', 'Cost of Services',       'ต้นทุนการให้บริการ',        'expense',   1, 1, 'debit'),
(@ALPHA_ID, '5100', 'Salary Expense',         'ค่าเงินเดือน',             'expense',   1, 1, 'debit'),
(@ALPHA_ID, '5200', 'Rent Expense',           'ค่าเช่า',                 'expense',   1, 1, 'debit'),
(@ALPHA_ID, '5300', 'Utilities Expense',      'ค่าสาธารณูปโภค',           'expense',   1, 1, 'debit'),
(@ALPHA_ID, '5400', 'Marketing Expense',      'ค่าการตลาด',              'expense',   1, 1, 'debit'),
-- Beta accounts
(@BETA_ID, '1000', 'Cash and Bank',           'เงินสดและเงินฝากธนาคาร',   'asset',     1, 1, 'debit'),
(@BETA_ID, '1100', 'Accounts Receivable',     'ลูกหนี้การค้า',            'asset',     1, 1, 'debit'),
(@BETA_ID, '1200', 'Inventory',               'สินค้าคงเหลือ',            'asset',     1, 1, 'debit'),
(@BETA_ID, '2000', 'Accounts Payable',        'เจ้าหนี้การค้า',            'liability', 1, 1, 'credit'),
(@BETA_ID, '2100', 'VAT Payable',             'ภาษีมูลค่าเพิ่มค้างจ่าย',   'liability', 1, 1, 'credit'),
(@BETA_ID, '3000', 'Owner Equity',            'ทุน',                     'equity',    1, 1, 'credit'),
(@BETA_ID, '4000', 'Product Sales',           'รายได้จากการขายสินค้า',     'revenue',   1, 1, 'credit'),
(@BETA_ID, '5000', 'Cost of Goods Sold',      'ต้นทุนสินค้าขาย',          'expense',   1, 1, 'debit'),
(@BETA_ID, '5100', 'Salary Expense',          'ค่าเงินเดือน',             'expense',   1, 1, 'debit'),
(@BETA_ID, '5200', 'Warehouse Rent',          'ค่าเช่าคลังสินค้า',        'expense',   1, 1, 'debit'),
(@BETA_ID, '5300', 'Shipping Expense',        'ค่าขนส่ง',                'expense',   1, 1, 'debit'),
-- Gamma accounts
(@GAMMA_ID, '1000', 'Cash and Bank',          'เงินสดและเงินฝากธนาคาร',   'asset',     1, 1, 'debit'),
(@GAMMA_ID, '1100', 'Accounts Receivable',    'ลูกหนี้การค้า',            'asset',     1, 1, 'debit'),
(@GAMMA_ID, '2000', 'Accounts Payable',       'เจ้าหนี้การค้า',            'liability', 1, 1, 'credit'),
(@GAMMA_ID, '3000', 'Owner Equity',           'ทุน',                     'equity',    1, 1, 'credit'),
(@GAMMA_ID, '4000', 'Design Revenue',         'รายได้จากงานออกแบบ',       'revenue',   1, 1, 'credit'),
(@GAMMA_ID, '5000', 'Cost of Services',       'ต้นทุนการให้บริการ',        'expense',   1, 1, 'debit'),
(@GAMMA_ID, '5100', 'Salary Expense',         'ค่าเงินเดือน',             'expense',   1, 1, 'debit'),
(@GAMMA_ID, '5200', 'Studio Rent',            'ค่าเช่าสตูดิโอ',          'expense',   1, 1, 'debit'),
(@GAMMA_ID, '5300', 'Software Licenses',      'ค่าซอฟต์แวร์',            'expense',   1, 1, 'debit'),
(@GAMMA_ID, '5400', 'Freelancer Expense',     'ค่าจ้างฟรีแลนซ์',         'expense',   1, 1, 'debit');

-- Get account IDs for journal entries
SET @ACC_CASH_A = (SELECT id FROM chart_of_accounts WHERE com_id = @ALPHA_ID AND account_code = '1000' LIMIT 1);
SET @ACC_AR_A   = (SELECT id FROM chart_of_accounts WHERE com_id = @ALPHA_ID AND account_code = '1100' LIMIT 1);
SET @ACC_AP_A   = (SELECT id FROM chart_of_accounts WHERE com_id = @ALPHA_ID AND account_code = '2000' LIMIT 1);
SET @ACC_REV_A  = (SELECT id FROM chart_of_accounts WHERE com_id = @ALPHA_ID AND account_code = '4000' LIMIT 1);
SET @ACC_SAL_A  = (SELECT id FROM chart_of_accounts WHERE com_id = @ALPHA_ID AND account_code = '5100' LIMIT 1);
SET @ACC_RENT_A = (SELECT id FROM chart_of_accounts WHERE com_id = @ALPHA_ID AND account_code = '5200' LIMIT 1);

SET @ACC_CASH_B = (SELECT id FROM chart_of_accounts WHERE com_id = @BETA_ID AND account_code = '1000' LIMIT 1);
SET @ACC_AR_B   = (SELECT id FROM chart_of_accounts WHERE com_id = @BETA_ID AND account_code = '1100' LIMIT 1);
SET @ACC_AP_B   = (SELECT id FROM chart_of_accounts WHERE com_id = @BETA_ID AND account_code = '2000' LIMIT 1);
SET @ACC_REV_B  = (SELECT id FROM chart_of_accounts WHERE com_id = @BETA_ID AND account_code = '4000' LIMIT 1);

SET @ACC_CASH_G = (SELECT id FROM chart_of_accounts WHERE com_id = @GAMMA_ID AND account_code = '1000' LIMIT 1);
SET @ACC_AR_G   = (SELECT id FROM chart_of_accounts WHERE com_id = @GAMMA_ID AND account_code = '1100' LIMIT 1);
SET @ACC_REV_G  = (SELECT id FROM chart_of_accounts WHERE com_id = @GAMMA_ID AND account_code = '4000' LIMIT 1);

-- ============================================================
-- 17. JOURNAL VOUCHERS + ENTRIES
-- ============================================================
-- Alpha: Record salary payment Jan
INSERT INTO journal_vouchers (com_id, jv_number, voucher_type, transaction_date, description, reference, reference_type, total_debit, total_credit, status, posted_at, created_by)
VALUES (@ALPHA_ID, 'JV-2026-0001', 'payment', '2026-01-31', 'January salary payment', 'EXP-A-202601-0003', 'expense', 450000, 450000, 'posted', '2026-01-31', 0);
SET @JV1 = LAST_INSERT_ID();
INSERT INTO journal_entries (journal_voucher_id, account_id, description, debit, credit, sort_order) VALUES
(@JV1, @ACC_SAL_A,  'Salary expense January',  450000, 0, 1),
(@JV1, @ACC_CASH_A, 'Cash payment',            0, 450000, 2);

-- Alpha: Record rent payment Jan
INSERT INTO journal_vouchers (com_id, jv_number, voucher_type, transaction_date, description, reference, reference_type, total_debit, total_credit, status, posted_at, created_by)
VALUES (@ALPHA_ID, 'JV-2026-0002', 'payment', '2026-01-05', 'January office rent', 'EXP-A-202601-0001', 'expense', 45000, 45000, 'posted', '2026-01-05', 0);
SET @JV2 = LAST_INSERT_ID();
INSERT INTO journal_entries (journal_voucher_id, account_id, description, debit, credit, sort_order) VALUES
(@JV2, @ACC_RENT_A, 'Rent expense January',    45000, 0, 1),
(@JV2, @ACC_CASH_A, 'Cash payment',            0, 45000, 2);

-- Beta: Record sale to Alpha (invoice paid)
INSERT INTO journal_vouchers (com_id, jv_number, voucher_type, transaction_date, description, reference, reference_type, total_debit, total_credit, status, posted_at, created_by)
VALUES (@BETA_ID, 'JV-2026-0001', 'receipt', '2026-02-05', 'Payment received from Alpha Tech for laptops', 'QT-2026-0010', 'invoice', 210174.75, 210174.75, 'posted', '2026-02-05', 0);
SET @JV3 = LAST_INSERT_ID();
INSERT INTO journal_entries (journal_voucher_id, account_id, description, debit, credit, sort_order) VALUES
(@JV3, @ACC_CASH_B, 'Bank deposit from Alpha', 210174.75, 0, 1),
(@JV3, @ACC_REV_B,  'Product sales revenue',   0, 210174.75, 2);

-- Gamma: Record design service invoice to Alpha
INSERT INTO journal_vouchers (com_id, jv_number, voucher_type, transaction_date, description, reference, reference_type, total_debit, total_credit, status, posted_at, created_by)
VALUES (@GAMMA_ID, 'JV-2026-0001', 'receipt', '2026-03-20', 'Invoice to Alpha for rebranding', 'QT-2026-0020', 'invoice', 46010, 46010, 'draft', NULL, 0);
SET @JV4 = LAST_INSERT_ID();
INSERT INTO journal_entries (journal_voucher_id, account_id, description, debit, credit, sort_order) VALUES
(@JV4, @ACC_AR_G,  'Receivable from Alpha Tech', 46010, 0, 1),
(@JV4, @ACC_REV_G, 'Design service revenue',     0, 46010, 2);

-- ============================================================
-- DONE - Summary
-- ============================================================
SELECT 'Demo seed complete!' AS status;
SELECT 'Alpha Tech Solutions' AS company, @ALPHA_ID AS id, 'admin@alphatech.co.th / demo1234' AS login
UNION ALL
SELECT 'Beta Supply Co.', @BETA_ID, 'admin@betasupply.co.th / demo1234'
UNION ALL
SELECT 'Gamma Design Studio', @GAMMA_ID, 'admin@gammadesign.co.th / demo1234';
