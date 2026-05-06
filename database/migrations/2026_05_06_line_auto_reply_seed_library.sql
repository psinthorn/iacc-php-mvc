-- Migration: 2026_05_06_line_auto_reply_seed_library.sql
-- v6.2-x — LINE OA Auto-Reply Seed Library
-- Inserts 14 bilingual starter rules (7 intents × TH + EN) per company.
-- Idempotent: each rule checks NOT EXISTS by (company_id, trigger_keyword)
-- so re-running this migration is safe and won't duplicate or overwrite
-- operator-customized rules.
--
-- Active defaults:
--   - Greeting / price / booking / payment / cancel rules → is_active=1
--   - Location / contact rules → is_active=0 (contain {placeholder} text
--     that the operator must edit before going live)
--
-- Compatible: MySQL 5.7 / MariaDB / cPanel phpMyAdmin (no CLI required)

-- 1. Greeting (TH)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'สวัสดี', 'contains', 'text',
       'สวัสดีค่ะ ยินดีต้อนรับสู่บริษัททัวร์ของเรา หากต้องการสอบถามข้อมูลทัวร์ ราคา หรือการจอง สามารถพิมพ์ข้อความได้เลยค่ะ', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'สวัสดี' AND r.deleted_at IS NULL);

-- 2. Greeting (EN)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'hello', 'contains', 'text',
       'Hi! Thanks for reaching out. How can we help you with your tour today?', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'hello' AND r.deleted_at IS NULL);

-- 3. Price inquiry (TH)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'ราคา', 'contains', 'text',
       'ขอบคุณที่สนใจค่ะ กรุณาแจ้ง:\n1) ทัวร์ที่สนใจ\n2) วันเดินทาง\n3) จำนวนผู้เดินทาง (ผู้ใหญ่/เด็ก)\n\nเพื่อให้เราเสนอราคาที่ดีที่สุดให้คุณค่ะ', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'ราคา' AND r.deleted_at IS NULL);

-- 4. Price inquiry (EN)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'price', 'contains', 'text',
       'Thanks for your interest! Please let us know:\n1) Which tour\n2) Travel date\n3) Number of travelers (adults/children)\n\nWe will send you the best quote.', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'price' AND r.deleted_at IS NULL);

-- 5. Booking (TH)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'จอง', 'contains', 'text',
       'ยินดีต้อนรับค่ะ ในการจองทัวร์ กรุณาแจ้ง:\n1) ชื่อทัวร์\n2) วันที่เดินทาง\n3) จำนวนผู้เดินทาง\n4) ชื่อ-นามสกุล ผู้จอง\n5) เบอร์ติดต่อ\n\nเจ้าหน้าที่จะตอบกลับโดยเร็วค่ะ', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'จอง' AND r.deleted_at IS NULL);

-- 6. Booking (EN)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'book', 'contains', 'text',
       'Great! To book a tour, please provide:\n1) Tour name\n2) Travel date\n3) Number of travelers\n4) Your full name\n5) Contact number\n\nOur team will get back to you shortly.', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'book' AND r.deleted_at IS NULL);

-- 7. Payment (TH)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'ชำระ', 'contains', 'text',
       'ช่องทางการชำระเงิน:\n- โอนผ่านธนาคาร\n- พร้อมเพย์ (PromptPay)\n\nหลังโอนเรียบร้อยแล้ว กรุณาส่งสลิปการโอนมาทาง LINE นี้ พร้อมระบุชื่อผู้จองและเลขการจองค่ะ', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'ชำระ' AND r.deleted_at IS NULL);

-- 8. Payment (EN)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'payment', 'contains', 'text',
       'Payment options:\n- Bank transfer\n- PromptPay\n\nAfter payment, please send the slip via LINE with your name and booking reference.', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'payment' AND r.deleted_at IS NULL);

-- 9. Location (TH) — DISABLED by default; contains placeholder
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'ที่อยู่', 'contains', 'text',
       'ที่อยู่ของเรา: [กรุณาแก้ไขที่อยู่ของคุณที่นี่]\nแผนที่: [กรุณาเพิ่มลิงก์ Google Maps ของคุณ]\nเวลาทำการ: [กรุณาเพิ่มเวลาทำการ]', 0, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'ที่อยู่' AND r.deleted_at IS NULL);

-- 10. Location (EN) — DISABLED by default; contains placeholder
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'location', 'contains', 'text',
       'Our address: [please edit with your address]\nMap: [please add your Google Maps link]\nHours: [please add your business hours]', 0, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'location' AND r.deleted_at IS NULL);

-- 11. Contact (TH) — DISABLED by default; contains placeholder
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'ติดต่อ', 'contains', 'text',
       'ช่องทางติดต่อเรา:\nโทร: [กรุณาแก้ไขเบอร์โทรของคุณที่นี่]\nอีเมล: [กรุณาเพิ่มอีเมล]\nเวลาทำการ: [เวลาทำการ]', 0, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'ติดต่อ' AND r.deleted_at IS NULL);

-- 12. Contact (EN) — DISABLED by default; contains placeholder
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'contact', 'contains', 'text',
       'Contact us:\nPhone: [please edit with your phone number]\nEmail: [please add your email]\nHours: [please add your business hours]', 0, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'contact' AND r.deleted_at IS NULL);

-- 13. Cancellation (TH)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'ยกเลิก', 'contains', 'text',
       'หากต้องการยกเลิกการจอง กรุณาแจ้ง:\n1) เลขที่การจอง\n2) เหตุผลในการยกเลิก\n\nเจ้าหน้าที่จะติดต่อกลับเรื่องเงื่อนไขการยกเลิกและการคืนเงินโดยเร็วค่ะ', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'ยกเลิก' AND r.deleted_at IS NULL);

-- 14. Cancellation (EN)
INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
SELECT c.id, 'cancel', 'contains', 'text',
       'To cancel a booking, please provide:\n1) Booking reference\n2) Reason for cancellation\n\nOur team will respond shortly with cancellation and refund terms.', 1, 0
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (SELECT 1 FROM line_auto_replies r WHERE r.company_id = c.id AND r.trigger_keyword = 'cancel' AND r.deleted_at IS NULL);
