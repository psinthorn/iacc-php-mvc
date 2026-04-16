-- ============================================================
-- Seed: Import Tour Agents for company_id=165 (My Samui Island Tour)
-- Source: รายชื่อเอเย่นต์(Sheet1).csv
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/seeds/seed_agents_company165.sql
--
-- Imports:
--   ~45 agent/hotel companies under company_id=165
--   Matching company_addr records
--   Contract rates (Thai/Foreigner × Adult/Child) per agent
-- ============================================================

SET @NOW = NOW();
SET @OWNER = 165;  -- มายสมุย ไอส์แลนด์ทัวร์

-- ============================================================
-- CLEANUP: Remove previous seed data (makes re-runnable)
-- ============================================================
DELETE cr FROM contract_rate cr
  INNER JOIN company c ON cr.agent_company_id = c.id
  WHERE c.company_id = @OWNER AND c.id != @OWNER;

DELETE ca FROM company_addr ca
  INNER JOIN company c ON ca.com_id = c.id
  WHERE c.company_id = @OWNER AND c.id != @OWNER;

DELETE FROM company WHERE company_id = @OWNER AND id != @OWNER;

-- ============================================================
-- 1. AGENT COMPANIES (customer=1, vender=0, company_type='tour_agent')
-- ============================================================

-- Group A: Registered companies (with Tax ID)
INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, company_type, logo, term, company_id) VALUES
('Sunlight Mountain Co.,Ltd.', 'Sunlight Mountain Co.,Ltd.', 'SUNMT', '', '', '077-485745', '', '0845549002275', 1, 0, 'tour_agent', '', '', @OWNER),
('Tourgoat Samui Co.,Ltd', 'Tourgoat Samui Co.,Ltd', 'TRGOT', '', '', '083-5021979', '', '0845567023611', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Head Office)', 'Asian Trails Ltd. (Head Office)', 'ATRHO', '', '', '02 8202000', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Branch 00003)', 'Asian Trails Ltd. (Branch 00003)', 'ATR03', '', '', '077-300681', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Branch 00030)', 'Asian Trails Ltd. (Branch 00030)', 'ATR30', '', '', '077-300681', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Branch 00035)', 'Asian Trails Ltd. (Branch 00035)', 'ATR35', '', '', '077-300681', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Branch 00036)', 'Asian Trails Ltd. (Branch 00036)', 'ATR36', '', '', '077-300681', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Branch 00045)', 'Asian Trails Ltd. (Branch 00045)', 'ATR45', '', '', '077-300681', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Asian Trails Ltd. (Branch 00046)', 'Asian Trails Ltd. (Branch 00046)', 'ATR46', '', '', '077-300681', '', '0105542030326', 1, 0, 'tour_agent', '', '', @OWNER),
('Der Asia Tours Co.,Ltd. (Branch 0004)', 'Der Asia Tours Co.,Ltd. (Branch 0004)', 'DASIA', '', '', '077 601350-52', '', '0105531039602', 1, 0, 'tour_agent', '', '', @OWNER),
('Basson Management Co.,Ltd. (Head Office)', 'Basson Management Co.,Ltd. (Head Office)', 'BASSN', '', '', '077-419094', '', '0845560004475', 1, 0, 'tour_agent', '', '', @OWNER),
('Central Samui Village Co.,Ltd.', 'Central Samui Village Co.,Ltd.', 'CSVIL', '', '', '077-424020', '', '0105532066786', 1, 0, 'hotel', '', '', @OWNER),
('Samui New Star Resort Ltd.,Part.', 'Samui New Star Resort Ltd.,Part.', 'NSTAR', '', '', '077-414500', '', '0843535000150', 1, 0, 'hotel', '', '', @OWNER),
('Bo Phut Property And Resort Co.,Ltd. (Branch 00001)', 'Bo Phut Property And Resort Co.,Ltd. (Branch 00001)', 'BOPHT', '', '', '077-245777', '', '0105546013779', 1, 0, 'hotel', '', '', @OWNER),
('Blue Straits Co.,Ltd. (Head Office)', 'Blue Straits Co.,Ltd. (Head Office)', 'BLUST', '', '', '077-953035', '', '0105565086085', 1, 0, 'hotel', '', '', @OWNER),
('The Culture Co.Ltd. (Branch 00001)', 'The Culture Co.Ltd. (Branch 00001)', 'CULTR', '', '', '077-238823', '', '0105559006121', 1, 0, 'hotel', '', '', @OWNER),
('Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)', 'Baan Chaweng Beach Resort & Spa Co.,Ltd. (Head Office)', 'BCHAW', '', '', '077-300564', '', '0845546003092', 1, 0, 'hotel', '', '', @OWNER),
('Siam Travel Center Co.,Ltd. (Branch 1)', 'Siam Travel Center Co.,Ltd. (Branch 1)', 'SIAMTC', '', '', '077-245555', '', '0845549007978', 1, 0, 'tour_agent', '', '', @OWNER),
('JTB (Thailand) Ltd. (Branch 00001)', 'JTB (Thailand) Ltd. (Branch 00001)', 'JTB01', '', '', '076-261746', '', '0105533128611', 1, 0, 'tour_agent', '', '', @OWNER),
('Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)', 'Samui Bayview Villa And Resort Co.,Ltd. (Branch 0001)', 'SBVIL', '', '', '081-0901561', '', '0105541072815', 1, 0, 'hotel', '', '', @OWNER),
('บริษัท บ้านเมษปิติ จำกัด', 'บริษัท บ้านเมษปิติ จำกัด', 'MESPT', '', '', '077-310420', '', '0845556002951', 1, 0, 'hotel', '', '', @OWNER),
('Wik Service Co.,Ltd.', 'Wik Service Co.,Ltd.', 'WIKSR', '', '', '091-9361926', '', '0105565066653', 1, 0, 'tour_agent', '', '', @OWNER);

-- Capture IDs for Group A (registered companies)
SET @ID_SUNMT = (SELECT id FROM company WHERE name_sh='SUNMT' AND company_id=@OWNER LIMIT 1);
SET @ID_TRGOT = (SELECT id FROM company WHERE name_sh='TRGOT' AND company_id=@OWNER LIMIT 1);
SET @ID_ATRHO = (SELECT id FROM company WHERE name_sh='ATRHO' AND company_id=@OWNER LIMIT 1);
SET @ID_ATR03 = (SELECT id FROM company WHERE name_sh='ATR03' AND company_id=@OWNER LIMIT 1);
SET @ID_ATR30 = (SELECT id FROM company WHERE name_sh='ATR30' AND company_id=@OWNER LIMIT 1);
SET @ID_ATR35 = (SELECT id FROM company WHERE name_sh='ATR35' AND company_id=@OWNER LIMIT 1);
SET @ID_ATR36 = (SELECT id FROM company WHERE name_sh='ATR36' AND company_id=@OWNER LIMIT 1);
SET @ID_ATR45 = (SELECT id FROM company WHERE name_sh='ATR45' AND company_id=@OWNER LIMIT 1);
SET @ID_ATR46 = (SELECT id FROM company WHERE name_sh='ATR46' AND company_id=@OWNER LIMIT 1);
SET @ID_DASIA = (SELECT id FROM company WHERE name_sh='DASIA' AND company_id=@OWNER LIMIT 1);
SET @ID_BASSN = (SELECT id FROM company WHERE name_sh='BASSN' AND company_id=@OWNER LIMIT 1);
SET @ID_CSVIL = (SELECT id FROM company WHERE name_sh='CSVIL' AND company_id=@OWNER LIMIT 1);
SET @ID_NSTAR = (SELECT id FROM company WHERE name_sh='NSTAR' AND company_id=@OWNER LIMIT 1);
SET @ID_BOPHT = (SELECT id FROM company WHERE name_sh='BOPHT' AND company_id=@OWNER LIMIT 1);
SET @ID_BLUST = (SELECT id FROM company WHERE name_sh='BLUST' AND company_id=@OWNER LIMIT 1);
SET @ID_CULTR = (SELECT id FROM company WHERE name_sh='CULTR' AND company_id=@OWNER LIMIT 1);
SET @ID_BCHAW = (SELECT id FROM company WHERE name_sh='BCHAW' AND company_id=@OWNER LIMIT 1);
SET @ID_SIAMTC = (SELECT id FROM company WHERE name_sh='SIAMTC' AND company_id=@OWNER LIMIT 1);
SET @ID_JTB01 = (SELECT id FROM company WHERE name_sh='JTB01' AND company_id=@OWNER LIMIT 1);
SET @ID_SBVIL = (SELECT id FROM company WHERE name_sh='SBVIL' AND company_id=@OWNER LIMIT 1);
SET @ID_MESPT = (SELECT id FROM company WHERE name_sh='MESPT' AND company_id=@OWNER LIMIT 1);
SET @ID_WIKSR = (SELECT id FROM company WHERE name_sh='WIKSR' AND company_id=@OWNER LIMIT 1);

-- Group B: Unregistered agents (Tax ID = 0000000000000)
INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, company_type, logo, term, company_id) VALUES
('SIAM DMC Co.,Ltd.', 'SIAM DMC Co.,Ltd.', 'SDMC', '', '', '087-5117486', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Pattra Vill Resort', 'Pattra Vill Resort', 'PATVL', '', '', '077-423505', '', '0000000000000', 1, 0, 'hotel', '', '', @OWNER),
('Dow Samui Travel', 'Dow Samui Travel', 'DOWSM', '', '', '093 6096287', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Inter Glove', 'Inter Glove', 'INTGL', '', '', '084-1415326', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Smile Samui Tours', 'Smile Samui Tours', 'SMILE', '', '', '081-6762343', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('MeeBoone Travel & Tour', 'MeeBoone Travel & Tour', 'MEEBO', '', '', '077-961297', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Enjoy 4 Travel', 'Enjoy 4 Travel', 'ENJ4T', '', '', '081-0808185', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Tour Online SYS', 'Tour Online SYS', 'TONLS', '', '', '', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Sita Tour', 'Sita Tour', 'SITAT', '', '', '081-7192733', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Thai Winery House & Tour', 'Thai Winery House & Tour', 'TWINE', '', '', '081-5354873', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Rinny Travel', 'Rinny Travel', 'RINNY', '', '', '087-4940405', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Island Experiences', 'Island Experiences', 'ISLXP', '', '', '091-0383460', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Samui Excellent Travel & Tour', 'Samui Excellent Travel & Tour', 'SMEXC', '', '', '086-3227658', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Backpacker Samui Travel', 'Backpacker Samui Travel', 'BKPKR', '', '', '081-4769630', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('T.J. Air Travel', 'T.J. Air Travel', 'TJAIR', '', '', '089-6520883', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Smart Exchange And Travel', 'Smart Exchange And Travel', 'SMART', '', '', '085-6199172', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Al''s Resort', 'Al''s Resort', 'ALSRS', '', '', '077-300561', '', '0000000000000', 1, 0, 'hotel', '', '', @OWNER),
('Samui Merger Travel', 'Samui Merger Travel', 'SMRGR', '', '', '077-447396', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Great Day Tour & Travel', 'Great Day Tour & Travel', 'GRTDY', '', '', '082-4145229', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Samui Highlight Travel', 'Samui Highlight Travel', 'SMHLT', '', '', '089-4744482', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Koh Samui Advisor Co.,Ltd.', 'Koh Samui Advisor Co.,Ltd.', 'KSADV', '', '', '065-0509371', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('Samui Insight Travel', 'Samui Insight Travel', 'SMINST', '', '', '081-6937217', '', '0000000000000', 1, 0, 'tour_agent', '', '', @OWNER),
('White Sand Samui Resort', 'White Sand Samui Resort', 'WTSND', '', '', '077-938909', '', '0000000000000', 1, 0, 'hotel', '', '', @OWNER);

-- Capture IDs for Group B
SET @ID_SDMC  = (SELECT id FROM company WHERE name_sh='SDMC'  AND company_id=@OWNER LIMIT 1);
SET @ID_PATVL = (SELECT id FROM company WHERE name_sh='PATVL' AND company_id=@OWNER LIMIT 1);
SET @ID_DOWSM = (SELECT id FROM company WHERE name_sh='DOWSM' AND company_id=@OWNER LIMIT 1);
SET @ID_INTGL = (SELECT id FROM company WHERE name_sh='INTGL' AND company_id=@OWNER LIMIT 1);
SET @ID_SMILE = (SELECT id FROM company WHERE name_sh='SMILE' AND company_id=@OWNER LIMIT 1);
SET @ID_MEEBO = (SELECT id FROM company WHERE name_sh='MEEBO' AND company_id=@OWNER LIMIT 1);
SET @ID_ENJ4T = (SELECT id FROM company WHERE name_sh='ENJ4T' AND company_id=@OWNER LIMIT 1);
SET @ID_TONLS = (SELECT id FROM company WHERE name_sh='TONLS' AND company_id=@OWNER LIMIT 1);
SET @ID_SITAT = (SELECT id FROM company WHERE name_sh='SITAT' AND company_id=@OWNER LIMIT 1);
SET @ID_TWINE = (SELECT id FROM company WHERE name_sh='TWINE' AND company_id=@OWNER LIMIT 1);
SET @ID_RINNY = (SELECT id FROM company WHERE name_sh='RINNY' AND company_id=@OWNER LIMIT 1);
SET @ID_ISLXP = (SELECT id FROM company WHERE name_sh='ISLXP' AND company_id=@OWNER LIMIT 1);
SET @ID_SMEXC = (SELECT id FROM company WHERE name_sh='SMEXC' AND company_id=@OWNER LIMIT 1);
SET @ID_BKPKR = (SELECT id FROM company WHERE name_sh='BKPKR' AND company_id=@OWNER LIMIT 1);
SET @ID_TJAIR = (SELECT id FROM company WHERE name_sh='TJAIR' AND company_id=@OWNER LIMIT 1);
SET @ID_SMART = (SELECT id FROM company WHERE name_sh='SMART' AND company_id=@OWNER LIMIT 1);
SET @ID_ALSRS = (SELECT id FROM company WHERE name_sh='ALSRS' AND company_id=@OWNER LIMIT 1);
SET @ID_SMRGR = (SELECT id FROM company WHERE name_sh='SMRGR' AND company_id=@OWNER LIMIT 1);
SET @ID_GRTDY = (SELECT id FROM company WHERE name_sh='GRTDY' AND company_id=@OWNER LIMIT 1);
SET @ID_SMHLT = (SELECT id FROM company WHERE name_sh='SMHLT' AND company_id=@OWNER LIMIT 1);
SET @ID_KSADV = (SELECT id FROM company WHERE name_sh='KSADV' AND company_id=@OWNER LIMIT 1);
SET @ID_SMINST = (SELECT id FROM company WHERE name_sh='SMINST' AND company_id=@OWNER LIMIT 1);
SET @ID_WTSND = (SELECT id FROM company WHERE name_sh='WTSND' AND company_id=@OWNER LIMIT 1);

-- ============================================================
-- 2. COMPANY ADDRESSES
-- ============================================================
INSERT INTO company_addr (com_id, adr_tax, city_tax, district_tax, province_tax, zip_tax, adr_bil, city_bil, district_bil, province_bil, zip_bil, valid_start, valid_end) VALUES
(@ID_SUNMT, '123/3 Moo 2, Lipa Noi', 'Koh Samui', 'Koh Samui', 'Suratthani', '84140', '123/3 Moo 2, Lipa Noi', 'Koh Samui', 'Koh Samui', 'Suratthani', '84140', '2026-01-01', '2027-12-31'),
(@ID_TRGOT, '56/2 Moo 5, Maenam', 'Koh Samui', 'Koh Samui', 'Suratthani', '', '56/2 Moo 5, Maenam', 'Koh Samui', 'Koh Samui', 'Suratthani', '', '2026-01-01', '2027-12-31'),
(@ID_ATRHO, '183 Regent House 12th Floor, Raddamri Road, Lumpini Pathumwan', 'Bangkok', 'Pathumwan', 'Bangkok', '10330', '183 Regent House 12th Floor, Raddamri Road, Lumpini Pathumwan', 'Bangkok', 'Pathumwan', 'Bangkok', '10330', '2026-01-01', '2027-12-31'),
(@ID_ATR03, '4/128 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '4/128 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ATR30, '86 Moo 3, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '86 Moo 3, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ATR35, '14/3 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '14/3 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ATR36, '11/34 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '11/34 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ATR45, '155/4 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '155/4 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ATR46, '9/99 Moo 5, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '9/99 Moo 5, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_DASIA, '14/66-67 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '14/66-67 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_BASSN, '156/3 Moo 4, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84310', '156/3 Moo 4, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84310', '2026-01-01', '2027-12-31'),
(@ID_CSVIL, '111 Moo 2, Maret Natien Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '', '111 Moo 2, Maret Natien Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '', '2026-01-01', '2027-12-31'),
(@ID_NSTAR, '83 Moo 3, Chaweng Noi Beach Road, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '83 Moo 3, Chaweng Noi Beach Road, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_BOPHT, '12/12 Moo 1, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '12/12 Moo 1, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_BLUST, '44/133 Moo 1, Maenam Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84330', '44/133 Moo 1, Maenam Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84330', '2026-01-01', '2027-12-31'),
(@ID_CULTR, '86 Moo 4, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '86 Moo 4, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_BCHAW, '90/1 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '90/1 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SIAMTC, '119/33 Moo 1, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '119/33 Moo 1, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_JTB01, '117/8 Moo 5, Chalermprakiet R.9 Rd. Rassada Sub-District, Muang District', 'Phuket', 'Muang', 'Phuket', '83000', '117/8 Moo 5, Chalermprakiet R.9 Rd. Rassada Sub-District, Muang District', 'Phuket', 'Muang', 'Phuket', '83000', '2026-01-01', '2027-12-31'),
(@ID_SBVIL, '104 Moo 3, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '104 Moo 3, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_MESPT, '171 หมู่ 2 ต.บ่อผุด อ.เกาะสมุย', 'เกาะสมุย', 'เกาะสมุย', 'สุราษฏร์ธานี', '84320', '171 หมู่ 2 ต.บ่อผุด อ.เกาะสมุย', 'เกาะสมุย', 'เกาะสมุย', 'สุราษฏร์ธานี', '84320', '2026-01-01', '2027-12-31'),
(@ID_WIKSR, '731 Asoke Din Daeng', 'Din Daeng', 'Din Daeng', 'Bangkok', '10400', '731 Asoke Din Daeng', 'Din Daeng', 'Din Daeng', 'Bangkok', '10400', '2026-01-01', '2027-12-31'),
(@ID_SDMC,  '10/97 The Trendy Building 6 ft. Soi Sukhumvit 13, Khlong Toei Nuae, Wattana', 'Bangkok', 'Wattana', 'Bangkok', '10110', '10/97 The Trendy Building 6 ft. Soi Sukhumvit 13, Khlong Toei Nuae, Wattana', 'Bangkok', 'Wattana', 'Bangkok', '10110', '2026-01-01', '2027-12-31'),
(@ID_PATVL, '124/329 Moo 3, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84310', '124/329 Moo 3, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84310', '2026-01-01', '2027-12-31'),
(@ID_DOWSM, '17/8 Moo 2, Chaweng Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '17/8 Moo 2, Chaweng Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_INTGL, 'Koh Samui', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', 'Koh Samui', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SMILE, '119/23 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '', '119/23 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '', '2026-01-01', '2027-12-31'),
(@ID_MEEBO, '38/18 Moo 3, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '38/18 Moo 3, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ENJ4T, '101/4 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '101/4 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_TONLS, '27/1 Moo 3, Chaweng Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '27/1 Moo 3, Chaweng Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SITAT, '20/301 Moo 4, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '20/301 Moo 4, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_TWINE, '141/20 Moo 4, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '141/20 Moo 4, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_RINNY, '167/7 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '167/7 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ISLXP, '64 Moo 1 Fisherman Village, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '64 Moo 1 Fisherman Village, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SMEXC, '157/61 Moo 1, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '157/61 Moo 1, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_BKPKR, '12 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '12 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_TJAIR, '141/28 Moo 4, Lamai Beach, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '141/28 Moo 4, Lamai Beach, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SMART, '18 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '18 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_ALSRS, '200 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '200 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SMRGR, '26/63 Moo4, Maenam Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84330', '26/63 Moo4, Maenam Beach', 'Koh Samui', 'Koh Samui', 'Suratthani', '84330', '2026-01-01', '2027-12-31'),
(@ID_GRTDY, '25/1 Moo 1, Fisherman Village, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '25/1 Moo 1, Fisherman Village, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SMHLT, '14/72 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '14/72 Moo 2, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_SMINST, '115/33 Moo 6, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '115/33 Moo 6, Bophut', 'Koh Samui', 'Koh Samui', 'Suratthani', '84320', '2026-01-01', '2027-12-31'),
(@ID_WTSND, '124/5 Moo 3, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84310', '124/5 Moo 3, Maret', 'Koh Samui', 'Koh Samui', 'Suratthani', '84310', '2026-01-01', '2027-12-31');

-- ============================================================
-- 3. CONTRACT RATES (Thai/Foreigner × Adult/Child per agent)
--    New schema: one row per agent, model_id = NULL = default rate
--    Old adult rate → adult_thai = adult_foreigner
--    Old child rate → child_thai = child_foreigner
--    Entrance fees = 0 (to be set per-product via UI)
--    Valid: 2026-01-01 to 2026-12-31
-- ============================================================
INSERT INTO contract_rate (company_id, agent_company_id, model_id, rate_type, adult_default, child_default, adult_thai, adult_foreigner, child_thai, child_foreigner, entrance_adult_default, entrance_child_default, entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner, valid_from, valid_to) VALUES
-- Sunlight Mountain: adult=1800, child=1300
(@OWNER, @ID_SUNMT, NULL, 'net_rate', 1800.00, 1300.00, 1800.00, 1800.00, 1300.00, 1300.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Tourgoat Samui: adult=1500, child=1100
(@OWNER, @ID_TRGOT, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails HO: adult=1500, child=1100
(@OWNER, @ID_ATRHO, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails Br 00003
(@OWNER, @ID_ATR03, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails Br 00030
(@OWNER, @ID_ATR30, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails Br 00035
(@OWNER, @ID_ATR35, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails Br 00036
(@OWNER, @ID_ATR36, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails Br 00045
(@OWNER, @ID_ATR45, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Asian Trails Br 00046
(@OWNER, @ID_ATR46, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Der Asia Tours: adult=1800, child=1300
(@OWNER, @ID_DASIA, NULL, 'net_rate', 1800.00, 1300.00, 1800.00, 1800.00, 1300.00, 1300.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Basson Management: adult=1700, child=1200
(@OWNER, @ID_BASSN, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Central Samui Village: adult=1800, child=1300
(@OWNER, @ID_CSVIL, NULL, 'net_rate', 1800.00, 1300.00, 1800.00, 1800.00, 1300.00, 1300.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Samui New Star Resort: adult=1700, child=1200
(@OWNER, @ID_NSTAR, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Bo Phut Property: adult=1700, child=1200
(@OWNER, @ID_BOPHT, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Blue Straits: adult=1700, child=1200
(@OWNER, @ID_BLUST, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- The Culture: adult=1700, child=1200
(@OWNER, @ID_CULTR, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Baan Chaweng Beach Resort: adult=1700, child=1200
(@OWNER, @ID_BCHAW, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Siam Travel Center: adult=1680, child=1260
(@OWNER, @ID_SIAMTC, NULL, 'net_rate', 1680.00, 1260.00, 1680.00, 1680.00, 1260.00, 1260.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- JTB (Thailand): adult=1700, child=1200
(@OWNER, @ID_JTB01, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Samui Bayview Villa: adult=1700, child=1200
(@OWNER, @ID_SBVIL, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- บ้านเมษปิติ: adult=1700, child=1200
(@OWNER, @ID_MESPT, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Wik Service: adult=1500, child=1100
(@OWNER, @ID_WIKSR, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- SIAM DMC: adult=1400, child=1000
(@OWNER, @ID_SDMC, NULL, 'net_rate', 1400.00, 1000.00, 1400.00, 1400.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Pattra Vill Resort: adult=1700, child=1200
(@OWNER, @ID_PATVL, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Dow Samui Travel: adult=1300, child=1000
(@OWNER, @ID_DOWSM, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Inter Glove: adult=1400, child=1100
(@OWNER, @ID_INTGL, NULL, 'net_rate', 1400.00, 1100.00, 1400.00, 1400.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Smile Samui Tours: adult=1300, child=1000
(@OWNER, @ID_SMILE, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- MeeBoone Travel: adult=1100, child=800
(@OWNER, @ID_MEEBO, NULL, 'net_rate', 1100.00, 800.00, 1100.00, 1100.00, 800.00, 800.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Enjoy 4 Travel: adult=1300, child=1000
(@OWNER, @ID_ENJ4T, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Tour Online SYS: adult=1300, child=1000
(@OWNER, @ID_TONLS, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Sita Tour: adult=1400, child=1000
(@OWNER, @ID_SITAT, NULL, 'net_rate', 1400.00, 1000.00, 1400.00, 1400.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Thai Winery House: adult=1300, child=1000
(@OWNER, @ID_TWINE, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Rinny Travel: adult=1300, child=1000
(@OWNER, @ID_RINNY, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Island Experiences: adult=1200, child=0
(@OWNER, @ID_ISLXP, NULL, 'net_rate', 1200.00, 0.00, 1200.00, 1200.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Samui Excellent Travel: adult=1300, child=1100
(@OWNER, @ID_SMEXC, NULL, 'net_rate', 1300.00, 1100.00, 1300.00, 1300.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Backpacker Samui: adult=1200, child=1000
(@OWNER, @ID_BKPKR, NULL, 'net_rate', 1200.00, 1000.00, 1200.00, 1200.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- T.J. Air Travel: adult=1300, child=1000
(@OWNER, @ID_TJAIR, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Smart Exchange: adult=1500, child=1100
(@OWNER, @ID_SMART, NULL, 'net_rate', 1500.00, 1100.00, 1500.00, 1500.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Al's Resort: adult=1700, child=1200
(@OWNER, @ID_ALSRS, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Samui Merger Travel: adult=1400, child=1100
(@OWNER, @ID_SMRGR, NULL, 'net_rate', 1400.00, 1100.00, 1400.00, 1400.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Great Day Tour: adult=1400, child=1100
(@OWNER, @ID_GRTDY, NULL, 'net_rate', 1400.00, 1100.00, 1400.00, 1400.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Samui Highlight Travel: adult=1300, child=1000
(@OWNER, @ID_SMHLT, NULL, 'net_rate', 1300.00, 1000.00, 1300.00, 1300.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Koh Samui Advisor: adult=1400, child=1100
(@OWNER, @ID_KSADV, NULL, 'net_rate', 1400.00, 1100.00, 1400.00, 1400.00, 1100.00, 1100.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- Samui Insight Travel: adult=1200, child=1000
(@OWNER, @ID_SMINST, NULL, 'net_rate', 1200.00, 1000.00, 1200.00, 1200.00, 1000.00, 1000.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31'),
-- White Sand Samui Resort: adult=1700, child=1200
(@OWNER, @ID_WTSND, NULL, 'net_rate', 1700.00, 1200.00, 1700.00, 1700.00, 1200.00, 1200.00, 0, 0, 0, 0, 0, 0, '2026-01-01', '2026-12-31');

-- ============================================================
-- 4. Update company_id=165 type to 'direct' (tour operator)
-- ============================================================
UPDATE company SET company_type = 'direct' WHERE id = @OWNER;

SELECT '=== IMPORT COMPLETE ===' AS status;
SELECT COUNT(*) AS agents_imported FROM company WHERE company_id = @OWNER AND id != @OWNER AND deleted_at IS NULL;
SELECT COUNT(*) AS addresses_created FROM company_addr ca INNER JOIN company c ON ca.com_id = c.id WHERE c.company_id = @OWNER AND c.id != @OWNER;
SELECT COUNT(*) AS contract_rates_created FROM contract_rate WHERE company_id = @OWNER;
