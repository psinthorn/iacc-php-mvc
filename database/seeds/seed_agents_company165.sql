-- ============================================================
-- Seed: Agent List — Company 165 (My Samui Island Tour)
-- Source: agent_list.xlsx  |  Generated: 2026-04-23
-- Total agents: 45
--
-- Strategy: one company row can be BOTH vendor and customer.
--   If company already exists (by name_en, company_id=165):
--     → UPDATE to set vender='1' (no duplicate created)
--   If company does not exist:
--     → INSERT new row with vender='1'
-- Idempotent: safe to run multiple times.
-- ============================================================

START TRANSACTION;

-- [01] Sunlight Mountain Co.,Ltd.
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-485745', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0845549002275',   tax)
WHERE company_id = 165 AND name_en = 'Sunlight Mountain Co.,Ltd.';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Sunlight Mountain Co.,Ltd.', 'Sunlight Mountain Co.,Ltd.', '', '', '', '0845549002275', '', '', '1', '0', '', '077-485745'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Sunlight Mountain Co.,Ltd.');
SET @aid_1 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Sunlight Mountain Co.,Ltd.' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_1, 165, 'net_rate', 1800.00, 1300.00, '123/3  Moo 2, Lipa Noi, Koh Samui, Suratthani   84140 | Full Moon rate: 1500'
WHERE @aid_1 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_1 AND company_id = 165);

-- [02] Tourgoat Samui Co.,Ltd
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '083-5021979', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0845567023611',   tax)
WHERE company_id = 165 AND name_en = 'Tourgoat Samui Co.,Ltd';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Tourgoat Samui Co.,Ltd', 'Tourgoat Samui Co.,Ltd', '', '', '', '0845567023611', '', '', '1', '0', '', '083-5021979'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Tourgoat Samui Co.,Ltd');
SET @aid_2 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Tourgoat Samui Co.,Ltd' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_2, 165, 'net_rate', 1500.00, 1100.00, '56/2  Moo 5, Maenam, | Full Moon rate: 1000'
WHERE @aid_2 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_2 AND company_id = 165);

-- [03] Asian Trails Ltd. ( Head Office )
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '02 8202000', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. ( Head Office )';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. ( Head Office )', 'Asian Trails Ltd. ( Head Office )', '', '', '', '0105542030326', '', '', '1', '0', '', '02 8202000'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. ( Head Office )');
SET @aid_3 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. ( Head Office )' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_3, 165, 'net_rate', 1500.00, 1100.00, '183 Regent House 12th Floor, Raddamri Road, Lumpini Pathumwan, Bangkok  10330 | Full Moon rate: 980'
WHERE @aid_3 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_3 AND company_id = 165);

-- [04] Asian Trails Ltd. (Branch 00003)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300681', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00003)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. (Branch 00003)', 'Asian Trails Ltd. (Branch 00003)', '', '', '', '0105542030326', '', '', '1', '0', '', '077-300681'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00003)');
SET @aid_4 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00003)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_4, 165, 'net_rate', 1500.00, 1100.00, '4/128 Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_4 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_4 AND company_id = 165);

-- [05] Asian Trails Ltd. (Branch 00030)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300681', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00030)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. (Branch 00030)', 'Asian Trails Ltd. (Branch 00030)', '', '', '', '0105542030326', '', '', '1', '0', '', '077-300681'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00030)');
SET @aid_5 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00030)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_5, 165, 'net_rate', 1500.00, 1100.00, '86  Moo 3, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_5 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_5 AND company_id = 165);

-- [06] Asian Trails Ltd. (Branch 00035)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300681', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00035)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. (Branch 00035)', 'Asian Trails Ltd. (Branch 00035)', '', '', '', '0105542030326', '', '', '1', '0', '', '077-300681'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00035)');
SET @aid_6 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00035)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_6, 165, 'net_rate', 1500.00, 1100.00, '14/3  Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_6 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_6 AND company_id = 165);

-- [07] Asian Trails Ltd. (Branch 00036)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300681', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00036)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. (Branch 00036)', 'Asian Trails Ltd. (Branch 00036)', '', '', '', '0105542030326', '', '', '1', '0', '', '077-300681'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00036)');
SET @aid_7 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00036)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_7, 165, 'net_rate', 1500.00, 1100.00, '11/34  Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_7 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_7 AND company_id = 165);

-- [08] Asian Trails Ltd. (Branch 00045)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300681', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00045)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. (Branch 00045)', 'Asian Trails Ltd. (Branch 00045)', '', '', '', '0105542030326', '', '', '1', '0', '', '077-300681'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00045)');
SET @aid_8 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00045)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_8, 165, 'net_rate', 1500.00, 1100.00, '155/4  Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_8 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_8 AND company_id = 165);

-- [09] Asian Trails Ltd. (Branch 00046)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300681', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105542030326',   tax)
WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00046)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Asian Trails Ltd. (Branch 00046)', 'Asian Trails Ltd. (Branch 00046)', '', '', '', '0105542030326', '', '', '1', '0', '', '077-300681'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00046)');
SET @aid_9 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Asian Trails Ltd. (Branch 00046)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_9, 165, 'net_rate', 1500.00, 1100.00, '9/99  Moo 5, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_9 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_9 AND company_id = 165);

-- [10] Der Asia Tours Co.,Ltd. (Branch 0004)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077 601350-52', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105531039602',   tax)
WHERE company_id = 165 AND name_en = 'Der Asia Tours Co.,Ltd. (Branch 0004)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Der Asia Tours Co.,Ltd. (Branch 0004)', 'Der Asia Tours Co.,Ltd. (Branch 0004)', '', '', '', '0105531039602', '', '', '1', '0', '', '077 601350-52'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Der Asia Tours Co.,Ltd. (Branch 0004)');
SET @aid_10 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Der Asia Tours Co.,Ltd. (Branch 0004)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_10, 165, 'net_rate', 1800.00, 1300.00, '14/66-67  Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_10 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_10 AND company_id = 165);

-- [11] Basson Management Co.,Ltd. ( Head Office )
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-419094', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0845560004475',   tax)
WHERE company_id = 165 AND name_en = 'Basson Management Co.,Ltd. ( Head Office )';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Basson Management Co.,Ltd. ( Head Office )', 'Basson Management Co.,Ltd. ( Head Office )', '', '', '', '0845560004475', '', '', '1', '0', '', '077-419094'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Basson Management Co.,Ltd. ( Head Office )');
SET @aid_11 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Basson Management Co.,Ltd. ( Head Office )' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_11, 165, 'net_rate', 1700.00, 1200.00, '156/3 Moo 4,  Maret, Koh Samui, Suratthani 84310 | Full Moon rate: 1000'
WHERE @aid_11 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_11 AND company_id = 165);

-- [12] Central Samui Village Co.,Ltd.
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-424020', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105532066786',   tax)
WHERE company_id = 165 AND name_en = 'Central Samui Village Co.,Ltd.';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Central Samui Village Co.,Ltd.', 'Central Samui Village Co.,Ltd.', '', '', '', '0105532066786', '', '', '1', '0', '', '077-424020'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Central Samui Village Co.,Ltd.');
SET @aid_12 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Central Samui Village Co.,Ltd.' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_12, 165, 'net_rate', 1800.00, 1300.00, '111 Moo 2,  Maret Natien Beach, | Full Moon rate: 1500'
WHERE @aid_12 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_12 AND company_id = 165);

-- [13] Samui New Star Resort Ltd.,Part.
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-414500', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0843535000150',   tax)
WHERE company_id = 165 AND name_en = 'Samui New Star Resort Ltd.,Part.';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Samui New Star Resort Ltd.,Part.', 'Samui New Star Resort Ltd.,Part.', '', '', '', '0843535000150', '', '', '1', '0', '', '077-414500'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Samui New Star Resort Ltd.,Part.');
SET @aid_13 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Samui New Star Resort Ltd.,Part.' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_13, 165, 'net_rate', 1700.00, 1200.00, '83 Moo 3, Chaweng Noi Beach Road, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_13 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_13 AND company_id = 165);

-- [14] Bo Phut Property And Resort Co.,Ltd. (Branch 00001)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-245777', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105546013779',   tax)
WHERE company_id = 165 AND name_en = 'Bo Phut Property And Resort Co.,Ltd. (Branch 00001)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Bo Phut Property And Resort Co.,Ltd. (Branch 00001)', 'Bo Phut Property And Resort Co.,Ltd. (Branch 00001)', '', '', '', '0105546013779', '', '', '1', '0', '', '077-245777'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Bo Phut Property And Resort Co.,Ltd. (Branch 00001)');
SET @aid_14 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Bo Phut Property And Resort Co.,Ltd. (Branch 00001)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_14, 165, 'net_rate', 1700.00, 1200.00, '12/12 Moo 1,  Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_14 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_14 AND company_id = 165);

-- [15] Blue Straits Co.,Ltd. (Head Office)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-953035', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105565086085',   tax)
WHERE company_id = 165 AND name_en = 'Blue Straits Co.,Ltd. (Head Office)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Blue Straits Co.,Ltd. (Head Office)', 'Blue Straits Co.,Ltd. (Head Office)', '', '', '', '0105565086085', '', '', '1', '0', '', '077-953035'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Blue Straits Co.,Ltd. (Head Office)');
SET @aid_15 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Blue Straits Co.,Ltd. (Head Office)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_15, 165, 'net_rate', 1700.00, 1200.00, '44/133  Moo 1,  Maenam Beach, Koh Samui, Suratthani  84330 | Full Moon rate: 1000'
WHERE @aid_15 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_15 AND company_id = 165);

-- [16] The Culture Co.Ltd. (Branch 00001)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-238823', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105559006121',   tax)
WHERE company_id = 165 AND name_en = 'The Culture Co.Ltd. (Branch 00001)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'The Culture Co.Ltd. (Branch 00001)', 'The Culture Co.Ltd. (Branch 00001)', '', '', '', '0105559006121', '', '', '1', '0', '', '077-238823'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'The Culture Co.Ltd. (Branch 00001)');
SET @aid_16 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'The Culture Co.Ltd. (Branch 00001)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_16, 165, 'net_rate', 1700.00, 1200.00, '86 Moo 4, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_16 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_16 AND company_id = 165);

-- [17] Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300564', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0845546003092',   tax)
WHERE company_id = 165 AND name_en = 'Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)', 'Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)', '', '', '', '0845546003092', '', '', '1', '0', '', '077-300564'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)');
SET @aid_17 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_17, 165, 'net_rate', 1700.00, 1200.00, '90/1  Moo 2,  Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_17 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_17 AND company_id = 165);

-- [18] Siam Travel Center Co.,Ltd. (Branch 1)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-245555', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0845549007978',   tax)
WHERE company_id = 165 AND name_en = 'Siam Travel Center Co.,Ltd. (Branch 1)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Siam Travel Center Co.,Ltd. (Branch 1)', 'Siam Travel Center Co.,Ltd. (Branch 1)', '', '', '', '0845549007978', '', '', '1', '0', '', '077-245555'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Siam Travel Center Co.,Ltd. (Branch 1)');
SET @aid_18 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Siam Travel Center Co.,Ltd. (Branch 1)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_18, 165, 'net_rate', 1680.00, 1260.00, '119/33 Moo 1, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 980'
WHERE @aid_18 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_18 AND company_id = 165);

-- [19] JTB (Thailand) Ltd. (Branch 00001)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '076-261746', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105533128611',   tax)
WHERE company_id = 165 AND name_en = 'JTB (Thailand) Ltd. (Branch 00001)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'JTB (Thailand) Ltd. (Branch 00001)', 'JTB (Thailand) Ltd. (Branch 00001)', '', '', '', '0105533128611', '', '', '1', '0', '', '076-261746'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'JTB (Thailand) Ltd. (Branch 00001)');
SET @aid_19 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'JTB (Thailand) Ltd. (Branch 00001)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_19, 165, 'net_rate', 1700.00, 1200.00, '117/8 Moo 5, Chalermprakiet R.9 Rd. Rassada Sub-District, Muang District, Phuket 83000 | Full Moon rate: 1000'
WHERE @aid_19 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_19 AND company_id = 165);

-- [20] Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-0901561', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105541072815',   tax)
WHERE company_id = 165 AND name_en = 'Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)', 'Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)', '', '', '', '0105541072815', '', '', '1', '0', '', '081-0901561'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)');
SET @aid_20 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_20, 165, 'net_rate', 1700.00, 1200.00, '104 Moo 3, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_20 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_20 AND company_id = 165);

-- [21] บริษัท บ้านเมษปิติ จำกัด
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-310420', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0845556002951',   tax)
WHERE company_id = 165 AND name_en = 'บริษัท บ้านเมษปิติ จำกัด';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'บริษัท บ้านเมษปิติ จำกัด', 'บริษัท บ้านเมษปิติ จำกัด', '', '', '', '0845556002951', '', '', '1', '0', '', '077-310420'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'บริษัท บ้านเมษปิติ จำกัด');
SET @aid_21 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'บริษัท บ้านเมษปิติ จำกัด' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_21, 165, 'net_rate', 1700.00, 1200.00, '171 หมู่ 2 ต.บ่อผุด อ.เกาะสมุย จ.สุราษฏร์ธานี  84320 | Full Moon rate: 1000'
WHERE @aid_21 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_21 AND company_id = 165);

-- [22] Wik Service Co.,Ltd.
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '091-9361926', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '0105565066653',   tax)
WHERE company_id = 165 AND name_en = 'Wik Service Co.,Ltd.';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Wik Service Co.,Ltd.', 'Wik Service Co.,Ltd.', '', '', '', '0105565066653', '', '', '1', '0', '', '091-9361926'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Wik Service Co.,Ltd.');
SET @aid_22 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Wik Service Co.,Ltd.' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_22, 165, 'net_rate', 1500.00, 1100.00, '731  Asoke Din Daeng, Din Daeng, Bangkok   10400 | Full Moon rate: 1000'
WHERE @aid_22 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_22 AND company_id = 165);

-- [23] SIAM DMC Co.,Ltd.
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '087-5117486', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'SIAM DMC Co.,Ltd.';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'SIAM DMC Co.,Ltd.', 'SIAM DMC Co.,Ltd.', '', '', '', '', '', '', '1', '0', '', '087-5117486'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'SIAM DMC Co.,Ltd.');
SET @aid_23 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'SIAM DMC Co.,Ltd.' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_23, 165, 'net_rate', 1400.00, 1000.00, '10/97  The Trendy Building 6 ft. Soi Sukhumvit 13 (Saengchan), Khlong Toei Nuae Wattana, Bangkok.  10110 | Full Moon rate: 900'
WHERE @aid_23 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_23 AND company_id = 165);

-- [24] Pattra Vill Resort
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-423505', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Pattra Vill Resort';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Pattra Vill Resort', 'Pattra Vill Resort', '', '', '', '', '', '', '1', '0', '', '077-423505'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Pattra Vill Resort');
SET @aid_24 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Pattra Vill Resort' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_24, 165, 'net_rate', 1700.00, 1200.00, '124/329  Moo 3, Maret, Koh Samui, Suratthani 84310 | Full Moon rate: 1000'
WHERE @aid_24 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_24 AND company_id = 165);

-- [25] Dow Samui Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '093 6096287', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Dow Samui Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Dow Samui Travel', 'Dow Samui Travel', '', '', '', '', '', '', '1', '0', '', '093 6096287'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Dow Samui Travel');
SET @aid_25 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Dow Samui Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_25, 165, 'net_rate', 1300.00, 1000.00, '17/8 Moo 2, Chaweng Beach, Koh Samui, Suratthani   84320 | Full Moon rate: 900'
WHERE @aid_25 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_25 AND company_id = 165);

-- [26] Inter Glove
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '084-1415326', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Inter Glove';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Inter Glove', 'Inter Glove', '', '', '', '', '', '', '1', '0', '', '084-1415326'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Inter Glove');
SET @aid_26 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Inter Glove' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_26, 165, 'net_rate', 1400.00, 1100.00, 'Koh Samui, Suratthani   84320 | Full Moon rate: 900'
WHERE @aid_26 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_26 AND company_id = 165);

-- [27] Smile Samui Tours
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-6762343', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Smile Samui Tours';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Smile Samui Tours', 'Smile Samui Tours', '', '', '', '', '', '', '1', '0', '', '081-6762343'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Smile Samui Tours');
SET @aid_27 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Smile Samui Tours' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_27, 165, 'net_rate', 1300.00, 1000.00, '119/23 Moo 2, Bophut, Koh Samui, Suratthani | Full Moon rate: 650'
WHERE @aid_27 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_27 AND company_id = 165);

-- [28] MeeBoone Travel & Tour
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-961297', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'MeeBoone Travel & Tour';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'MeeBoone Travel & Tour', 'MeeBoone Travel & Tour', '', '', '', '', '', '', '1', '0', '', '077-961297'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'MeeBoone Travel & Tour');
SET @aid_28 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'MeeBoone Travel & Tour' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_28, 165, 'net_rate', 1100.00, 800.00, '38/18 Moo 3, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 700'
WHERE @aid_28 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_28 AND company_id = 165);

-- [29] Enjoy 4 Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-0808185', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Enjoy 4 Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Enjoy 4 Travel', 'Enjoy 4 Travel', '', '', '', '', '', '', '1', '0', '', '081-0808185'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Enjoy 4 Travel');
SET @aid_29 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Enjoy 4 Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_29, 165, 'net_rate', 1300.00, 1000.00, '101/4  Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 900'
WHERE @aid_29 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_29 AND company_id = 165);

-- [30] Tour Online SYS
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Tour Online SYS';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Tour Online SYS', 'Tour Online SYS', '', '', '', '', '', '', '1', '0', '', ''
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Tour Online SYS');
SET @aid_30 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Tour Online SYS' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_30, 165, 'net_rate', 1300.00, 1000.00, '27/1 Moo 3, Chaweng Beach, Koh Samui, Suratthani   84320 | Full Moon rate: 800'
WHERE @aid_30 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_30 AND company_id = 165);

-- [31] Sita Tour
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-7192733', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Sita Tour';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Sita Tour', 'Sita Tour', '', '', '', '', '', '', '1', '0', '', '081-7192733'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Sita Tour');
SET @aid_31 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Sita Tour' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_31, 165, 'net_rate', 1400.00, 1000.00, '20/301  Moo 4  Bophut, Koh Samui, Suratthani  84320 | Full Moon rate: 800'
WHERE @aid_31 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_31 AND company_id = 165);

-- [32] Thai Winery House & Tour
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-5354873', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Thai Winery House & Tour';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Thai Winery House & Tour', 'Thai Winery House & Tour', '', '', '', '', '', '', '1', '0', '', '081-5354873'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Thai Winery House & Tour');
SET @aid_32 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Thai Winery House & Tour' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_32, 165, 'net_rate', 1300.00, 1000.00, '141/20 Moo 4, Maret, Koh Samui, Suratthani 84320 | Full Moon rate: 900'
WHERE @aid_32 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_32 AND company_id = 165);

-- [33] Rinny Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '087-4940405', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Rinny Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Rinny Travel', 'Rinny Travel', '', '', '', '', '', '', '1', '0', '', '087-4940405'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Rinny Travel');
SET @aid_33 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Rinny Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_33, 165, 'net_rate', 1300.00, 1000.00, '167/7 Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 900'
WHERE @aid_33 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_33 AND company_id = 165);

-- [34] Island Experiences
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '091-0383460', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Island Experiences';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Island Experiences', 'Island Experiences', '', '', '', '', '', '', '1', '0', '', '091-0383460'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Island Experiences');
SET @aid_34 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Island Experiences' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_34, 165, 'net_rate', 1200.00, 0.00, '64 Moo 1 Fisherman Village, Bophut, Koh Samui, Suratthani   84320'
WHERE @aid_34 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_34 AND company_id = 165);

-- [35] Samui Excellent Travel & Tour
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '086-3227658', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Samui Excellent Travel & Tour';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Samui Excellent Travel & Tour', 'Samui Excellent Travel & Tour', '', '', '', '', '', '', '1', '0', '', '086-3227658'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Samui Excellent Travel & Tour');
SET @aid_35 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Samui Excellent Travel & Tour' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_35, 165, 'net_rate', 1300.00, 1100.00, '157/61 Moo 1, Bophut, Koh Samui, Suratthani, 84320 | Full Moon rate: 800'
WHERE @aid_35 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_35 AND company_id = 165);

-- [36] Backpacker Samui Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-4769630', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Backpacker Samui Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Backpacker Samui Travel', 'Backpacker Samui Travel', '', '', '', '', '', '', '1', '0', '', '081-4769630'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Backpacker Samui Travel');
SET @aid_36 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Backpacker Samui Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_36, 165, 'net_rate', 1200.00, 1000.00, '12 Moo 2, Bophut, Koh Samui, Suratthani   84320'
WHERE @aid_36 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_36 AND company_id = 165);

-- [37] T.J. Air Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '089-6520883', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'T.J. Air Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'T.J. Air Travel', 'T.J. Air Travel', '', '', '', '', '', '', '1', '0', '', '089-6520883'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'T.J. Air Travel');
SET @aid_37 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'T.J. Air Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_37, 165, 'net_rate', 1300.00, 1000.00, '141/28 Moo 4, Lamai Beach, Maret, Koh Samui, Suratthani   84320'
WHERE @aid_37 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_37 AND company_id = 165);

-- [38] Smart Exchange And Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '085-6199172', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Smart Exchange And Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Smart Exchange And Travel', 'Smart Exchange And Travel', '', '', '', '', '', '', '1', '0', '', '085-6199172'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Smart Exchange And Travel');
SET @aid_38 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Smart Exchange And Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_38, 165, 'net_rate', 1500.00, 1100.00, '18 Moo 2,  Bophut, Koh Samui, Suratthani 84320'
WHERE @aid_38 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_38 AND company_id = 165);

-- [39] Al's Resort
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-300561', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Al\'s Resort';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Al\'s Resort', 'Al\'s Resort', '', '', '', '', '', '', '1', '0', '', '077-300561'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Al\'s Resort');
SET @aid_39 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Al\'s Resort' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_39, 165, 'net_rate', 1700.00, 1200.00, '200  Moo 2, Bophut, Koh Samui, Suratthani 84320 | Full Moon rate: 1000'
WHERE @aid_39 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_39 AND company_id = 165);

-- [40] Samui Merger Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-447396', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Samui Merger Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Samui Merger Travel', 'Samui Merger Travel', '', '', '', '', '', '', '1', '0', '', '077-447396'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Samui Merger Travel');
SET @aid_40 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Samui Merger Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_40, 165, 'net_rate', 1400.00, 1100.00, '26/63 Moo4, Maenam Beach, Koh Samui, Suratthani   84330'
WHERE @aid_40 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_40 AND company_id = 165);

-- [41] Great Day Tour & Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '082-4145229', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Great Day Tour & Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Great Day Tour & Travel', 'Great Day Tour & Travel', '', '', '', '', '', '', '1', '0', '', '082-4145229'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Great Day Tour & Travel');
SET @aid_41 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Great Day Tour & Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_41, 165, 'net_rate', 1400.00, 1100.00, '25/1 Moo 1, Fisherman Village, Bophut Koh Samui, Suratthani 84320'
WHERE @aid_41 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_41 AND company_id = 165);

-- [42] Samui Highlight Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '089-4744482', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Samui Highlight Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Samui Highlight Travel', 'Samui Highlight Travel', '', '', '', '', '', '', '1', '0', '', '089-4744482'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Samui Highlight Travel');
SET @aid_42 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Samui Highlight Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_42, 165, 'net_rate', 1300.00, 1000.00, '14/72 Moo 2,  Bophut, Koh Samui  Surat Thani  84320'
WHERE @aid_42 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_42 AND company_id = 165);

-- [43] Koh Samui Advisor Co.,Ltd.
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '065-0509371', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Koh Samui Advisor Co.,Ltd.';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Koh Samui Advisor Co.,Ltd.', 'Koh Samui Advisor Co.,Ltd.', '', '', '', '', '', '', '1', '0', '', '065-0509371'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Koh Samui Advisor Co.,Ltd.');
SET @aid_43 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Koh Samui Advisor Co.,Ltd.' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_43, 165, 'net_rate', 1400.00, 1100.00, 'Full Moon rate: 900'
WHERE @aid_43 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_43 AND company_id = 165);

-- [44] Samui Insight Travel
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '081-6937217', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'Samui Insight Travel';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'Samui Insight Travel', 'Samui Insight Travel', '', '', '', '', '', '', '1', '0', '', '081-6937217'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'Samui Insight Travel');
SET @aid_44 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'Samui Insight Travel' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_44, 165, 'net_rate', 1200.00, 1000.00, '115/33 Moo 6, Bophut, Koh Samui, Suratthani   84320'
WHERE @aid_44 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_44 AND company_id = 165);

-- [45] White Sand Samui Resort
UPDATE `company`
SET vender = '1',
    phone  = IF(phone = '' OR phone IS NULL, '077-938909', phone),
    tax    = IF(tax   = '' OR tax   IS NULL, '',   tax)
WHERE company_id = 165 AND name_en = 'White Sand Samui Resort';
INSERT INTO `company` (company_id, name_en, name_th, name_sh, contact, fax, tax, logo, term, vender, customer, email, phone)
SELECT 165, 'White Sand Samui Resort', 'White Sand Samui Resort', '', '', '', '', '', '', '1', '0', '', '077-938909'
WHERE NOT EXISTS (SELECT 1 FROM `company` WHERE company_id = 165 AND name_en = 'White Sand Samui Resort');
SET @aid_45 = (SELECT id FROM `company` WHERE company_id = 165 AND name_en = 'White Sand Samui Resort' ORDER BY id ASC LIMIT 1);
INSERT INTO `tour_agent_profiles` (company_ref_id, company_id, commission_type, commission_adult, commission_child, notes)
SELECT @aid_45, 165, 'net_rate', 1700.00, 1200.00, '124/5 Moo 3,  Maret, Koh Samui, Suratthani  84310 | Full Moon rate: 1000'
WHERE @aid_45 IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tour_agent_profiles` WHERE company_ref_id = @aid_45 AND company_id = 165);

COMMIT;

-- ============================================================
-- Optional: find and merge any existing duplicates
-- (same name_en, same company_id, one vendor + one customer)
-- ============================================================
-- SELECT name_en, COUNT(*) cnt FROM company WHERE company_id = 165
-- GROUP BY name_en HAVING cnt > 1 ORDER BY name_en;
