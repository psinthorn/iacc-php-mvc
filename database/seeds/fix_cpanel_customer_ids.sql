-- ============================================================
-- Fix: Create missing customer companies on cPanel
-- and remap tour_bookings.customer_id to new auto-increment IDs
-- ============================================================
-- Problem: The seed_bookings_cpanel.sql only exported agent companies
-- (IDs 239-243) but missed the 19 customer companies (IDs 220-238).
-- On cPanel, IDs 220-238 belong to different, pre-existing companies.
-- ============================================================
-- Run this AFTER importing seed_bookings_cpanel.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Customer: Sarah Johnson (Docker ID: 220)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Sarah Johnson','ซาราห์ จอห์นสัน','','Sarah','sarah.j@email.com','THB','082-345-6702','','',1,0,'','',165,'admin');
SET @new_220 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_220 WHERE customer_id = 220 AND company_id = 165;

-- Customer: Michael Brown (Docker ID: 221)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Michael Brown','ไมเคิล บราวน์','','Michael','michael.b@email.com','THB','083-456-7803','','',1,0,'','',165,'admin');
SET @new_221 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_221 WHERE customer_id = 221 AND company_id = 165;

-- Customer: Emily Davis (Docker ID: 222)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Emily Davis','เอมิลี่ เดวิส','','Emily','emily.d@email.com','THB','084-567-8904','','',1,0,'','',165,'admin');
SET @new_222 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_222 WHERE customer_id = 222 AND company_id = 165;

-- Customer: David Martinez (Docker ID: 223)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('David Martinez','เดวิด มาร์ติเนซ','','David','david.m@email.com','THB','085-678-9005','','',1,0,'','',165,'admin');
SET @new_223 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_223 WHERE customer_id = 223 AND company_id = 165;

-- Customer: Lisa Anderson (Docker ID: 224)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Lisa Anderson','ลิซ่า แอนเดอร์สัน','','Lisa','lisa.a@email.com','THB','086-789-0106','','',1,0,'','',165,'admin');
SET @new_224 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_224 WHERE customer_id = 224 AND company_id = 165;

-- Customer: Robert Taylor (Docker ID: 225)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Robert Taylor','โรเบิร์ต เทย์เลอร์','','Robert','robert.t@email.com','THB','087-890-1207','','',1,0,'','',165,'admin');
SET @new_225 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_225 WHERE customer_id = 225 AND company_id = 165;

-- Customer: Jennifer Thomas (Docker ID: 226)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Jennifer Thomas','เจนนิเฟอร์ โทมัส','','Jennifer','jennifer.t@email.com','THB','088-901-2308','','',1,0,'','',165,'admin');
SET @new_226 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_226 WHERE customer_id = 226 AND company_id = 165;

-- Customer: William Garcia (Docker ID: 227)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('William Garcia','วิลเลียม การ์เซีย','','William','william.g@email.com','THB','089-012-3409','','',1,0,'','',165,'admin');
SET @new_227 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_227 WHERE customer_id = 227 AND company_id = 165;

-- Customer: Amanda Lee (Docker ID: 228)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Amanda Lee','อแมนด้า ลี','','Amanda','amanda.l@email.com','THB','090-123-4510','','',1,0,'','',165,'admin');
SET @new_228 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_228 WHERE customer_id = 228 AND company_id = 165;

-- Customer: สมชาย ใจดี (Docker ID: 229)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('สมชาย ใจดี','สมชาย ใจดี','','สมชาย','somchai@email.com','THB','091-234-5611','','',1,0,'','',165,'admin');
SET @new_229 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_229 WHERE customer_id = 229 AND company_id = 165;

-- Customer: สมหญิง รักสวย (Docker ID: 230)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('สมหญิง รักสวย','สมหญิง รักสวย','','สมหญิง','somying@email.com','THB','092-345-6712','','',1,0,'','',165,'admin');
SET @new_230 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_230 WHERE customer_id = 230 AND company_id = 165;

-- Customer: วิชัย สุขสันต์ (Docker ID: 231)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('วิชัย สุขสันต์','วิชัย สุขสันต์','','วิชัย','wichai@email.com','THB','093-456-7813','','',1,0,'','',165,'admin');
SET @new_231 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_231 WHERE customer_id = 231 AND company_id = 165;

-- Customer: อรุณ แสงทอง (Docker ID: 232)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('อรุณ แสงทอง','อรุณ แสงทอง','','อรุณ','arun@email.com','THB','094-567-8914','','',1,0,'','',165,'admin');
SET @new_232 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_232 WHERE customer_id = 232 AND company_id = 165;

-- Customer: Marie Dubois (Docker ID: 233)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Marie Dubois','มารี ดูบัวส์','','Marie','marie.d@email.com','THB','095-678-9015','','',1,0,'','',165,'admin');
SET @new_233 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_233 WHERE customer_id = 233 AND company_id = 165;

-- Customer: Hans Mueller (Docker ID: 234)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Hans Mueller','ฮันส์ มุลเลอร์','','Hans','hans.m@email.com','THB','096-789-0116','','',1,0,'','',165,'admin');
SET @new_234 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_234 WHERE customer_id = 234 AND company_id = 165;

-- Customer: Yuki Tanaka (Docker ID: 235)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Yuki Tanaka','ยูกิ ทานากะ','','Yuki','yuki.t@email.com','THB','097-890-1217','','',1,0,'','',165,'admin');
SET @new_235 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_235 WHERE customer_id = 235 AND company_id = 165;

-- Customer: Chen Wei (Docker ID: 236)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Chen Wei','เฉิน เว่ย','','Chen','chen.w@email.com','THB','098-901-2318','','',1,0,'','',165,'admin');
SET @new_236 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_236 WHERE customer_id = 236 AND company_id = 165;

-- Customer: Kim Soo-jin (Docker ID: 237)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Kim Soo-jin','คิม ซูจิน','','Kim','kim.sj@email.com','THB','099-012-3419','','',1,0,'','',165,'admin');
SET @new_237 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_237 WHERE customer_id = 237 AND company_id = 165;

-- Customer: Priya Sharma (Docker ID: 238)
INSERT INTO company (name_en, name_th, name_sh, contact, email, default_currency, phone, fax, tax, customer, vender, logo, term, company_id, registered_via) VALUES ('Priya Sharma','ปรียา ชาร์มา','','Priya','priya.s@email.com','THB','080-123-4520','','',1,0,'','',165,'admin');
SET @new_238 = LAST_INSERT_ID();
UPDATE tour_bookings SET customer_id = @new_238 WHERE customer_id = 238 AND company_id = 165;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE. 19 customer companies created and bookings remapped.
-- ============================================================
