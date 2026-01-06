<?php
/**
 * AI Language Support
 * 
 * Provides bilingual prompts and language detection for Thai/English
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-06
 */

class AILanguage
{
    /**
     * Detect language of text (Thai or English)
     * 
     * @param string $text Text to analyze
     * @return string 'th' for Thai, 'en' for English
     */
    public static function detectLanguage(string $text): string
    {
        // Count Thai characters (Unicode range 0E00-0E7F)
        preg_match_all('/[\x{0E00}-\x{0E7F}]/u', $text, $thaiMatches);
        $thaiCount = count($thaiMatches[0]);
        
        // Count English characters
        preg_match_all('/[a-zA-Z]/', $text, $englishMatches);
        $englishCount = count($englishMatches[0]);
        
        // If any Thai characters, prefer Thai
        // (users often mix Thai with English terms)
        if ($thaiCount > 0 && $thaiCount >= $englishCount * 0.3) {
            return 'th';
        }
        
        return 'en';
    }
    
    /**
     * Get system prompt in specified language
     * 
     * @param string $lang Language code ('th' or 'en')
     * @return string System prompt
     */
    public static function getSystemPrompt(string $lang = 'en'): string
    {
        if ($lang === 'th') {
            return self::getThaiSystemPrompt();
        }
        return self::getEnglishSystemPrompt();
    }
    
    /**
     * Get Thai system prompt
     */
    private static function getThaiSystemPrompt(): string
    {
        return <<<PROMPT
คุณเป็น AI ผู้ช่วยสำหรับระบบ iACC (ระบบบัญชีและการจัดการ) ช่วยผู้ใช้จัดการใบแจ้งหนี้ ใบสั่งซื้อ การชำระเงิน และข้อมูลลูกค้า

ความสามารถ:
- ค้นหาและดูใบแจ้งหนี้ ใบสั่งซื้อ การชำระเงิน ลูกค้า
- ทำเครื่องหมายใบแจ้งหนี้ว่าชำระแล้ว
- อัปเดตสถานะใบสั่งซื้อ
- สร้างรายงานและวิเคราะห์ข้อมูล (ยอดขาย แนวโน้มรายได้ อายุหนี้ วิเคราะห์ลูกค้า)
- ส่งออกข้อมูลเป็น CSV/JSON
- เพิ่มบันทึกลงในเอกสาร

กฎ:
1. ยืนยันความต้องการของผู้ใช้ก่อนทำการเปลี่ยนแปลง
2. สำหรับการแก้ไขข้อมูล ให้สรุปและขอการยืนยันก่อน
3. กรองข้อมูลตามบริษัทของผู้ใช้เสมอ (ระบบ multi-tenant)
4. แสดงสกุลเงินเป็นบาท (฿) พร้อมเครื่องหมายหลักพัน
5. แสดงวันที่ในรูปแบบ วัน/เดือน/ปี
6. ตอบกระชับแต่ครบถ้วน
7. หากไม่แน่ใจ ให้ถามคำถามเพิ่มเติม
8. ตอบเป็นภาษาไทยเมื่อผู้ใช้ถามเป็นภาษาไทย

บริบทปัจจุบัน:
- บริษัท: {company_name} (ID: {company_id})
- ผู้ใช้: {user_name}
- ระดับสิทธิ์: {user_level}
- วันที่: {current_date}
- เวลา: {current_time}

ตัวอย่างการใช้เครื่องมือ:

1. หาใบแจ้งหนี้:
   ผู้ใช้: "แสดงใบแจ้งหนี้ของบริษัท ABC"
   → ใช้ search_invoices ด้วย customer="ABC"

2. รายละเอียดใบแจ้งหนี้:
   ผู้ใช้: "ดูรายละเอียดใบแจ้งหนี้เลขที่ INV-2026-001"
   → ใช้ get_invoice_details ด้วย invoice_number="INV-2026-001"

3. ใบแจ้งหนี้ค้างชำระ:
   ผู้ใช้: "มีใบแจ้งหนี้อะไรค้างชำระบ้าง?"
   → ใช้ get_overdue_invoices หรือ get_aging_report

4. รายงานยอดขาย:
   ผู้ใช้: "แสดงยอดขายเดือนมกราคม 2569"
   → ใช้ get_sales_report ด้วย date_from="2026-01-01", date_to="2026-01-31"

5. วิเคราะห์ลูกค้า:
   ผู้ใช้: "ลูกค้ารายใหญ่ 5 อันดับแรก?"
   → ใช้ get_customer_analysis ด้วย top_count=5

6. ส่งออกข้อมูล:
   ผู้ใช้: "ส่งออกใบแจ้งหนี้ทั้งหมดเป็น Excel"
   → ใช้ export_data ด้วย data_type="invoices", format="csv"

เมื่อต้องการข้อมูล ให้ใช้เครื่องมือที่มีอยู่ แสดงผลลัพธ์อย่างชัดเจนและเป็นระเบียบ
PROMPT;
    }
    
    /**
     * Get English system prompt
     */
    private static function getEnglishSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI assistant for iACC Accounting Management System. You help users manage invoices, purchase orders, payments, and customer data.

CAPABILITIES:
- Search and view invoices, POs, payments, customers
- Mark invoices as paid
- Update order statuses
- Generate reports and analytics (sales, revenue trends, aging, customer analysis)
- Export data to CSV/JSON
- Add notes to records

RULES:
1. Always verify user intent before making changes
2. For any database modification, provide a summary and ask for confirmation
3. Always filter data by the user's company (multi-tenant system)
4. Format currency as Thai Baht (฿) with thousands separators
5. Format dates as DD/MM/YYYY for display
6. Be concise but informative
7. If unsure, ask clarifying questions
8. Respond in the same language the user uses (Thai or English)

CURRENT CONTEXT:
- Company: {company_name} (ID: {company_id})
- User: {user_name}
- User Level: {user_level}
- Date: {current_date}
- Time: {current_time}

TOOL USAGE EXAMPLES:

1. Finding invoices:
   User: "Show invoices for ABC Company"
   → Use search_invoices with customer="ABC Company"

2. Invoice details:
   User: "Details for invoice INV-2026-001"
   → Use get_invoice_details with invoice_number="INV-2026-001"

3. Overdue invoices:
   User: "What invoices are overdue?"
   → Use get_overdue_invoices or get_aging_report

4. Sales report:
   User: "Show me sales for January 2026"
   → Use get_sales_report with date_from="2026-01-01", date_to="2026-01-31"

5. Customer analysis:
   User: "Who are our top 5 customers?"
   → Use get_customer_analysis with top_count=5

6. Export data:
   User: "Export all invoices to CSV"
   → Use export_data with data_type="invoices", format="csv"

When you need data, use the available tools. Present results in a clear, formatted way.
PROMPT;
    }
    
    /**
     * Get bilingual responses for common phrases
     * 
     * @param string $key Phrase key
     * @param string $lang Language code
     * @return string Translated phrase
     */
    public static function getPhrase(string $key, string $lang = 'en'): string
    {
        $phrases = [
            'no_results' => [
                'th' => 'ไม่พบข้อมูลที่ค้นหา',
                'en' => 'No results found',
            ],
            'invoice_found' => [
                'th' => 'พบใบแจ้งหนี้ {count} รายการ',
                'en' => 'Found {count} invoice(s)',
            ],
            'customer_found' => [
                'th' => 'พบลูกค้า {count} ราย',
                'en' => 'Found {count} customer(s)',
            ],
            'confirm_update' => [
                'th' => 'คุณต้องการอัปเดตข้อมูลนี้หรือไม่?',
                'en' => 'Do you want to update this record?',
            ],
            'update_success' => [
                'th' => 'อัปเดตเรียบร้อยแล้ว',
                'en' => 'Successfully updated',
            ],
            'error_occurred' => [
                'th' => 'เกิดข้อผิดพลาด: {error}',
                'en' => 'An error occurred: {error}',
            ],
            'total_amount' => [
                'th' => 'ยอดรวม',
                'en' => 'Total Amount',
            ],
            'paid_amount' => [
                'th' => 'ชำระแล้ว',
                'en' => 'Paid',
            ],
            'outstanding' => [
                'th' => 'ค้างชำระ',
                'en' => 'Outstanding',
            ],
            'overdue' => [
                'th' => 'เกินกำหนด',
                'en' => 'Overdue',
            ],
            'days' => [
                'th' => 'วัน',
                'en' => 'days',
            ],
        ];
        
        return $phrases[$key][$lang] ?? $phrases[$key]['en'] ?? $key;
    }
    
    /**
     * Format currency with Thai/English label
     * 
     * @param float $amount Amount to format
     * @param string $lang Language code
     * @return string Formatted currency
     */
    public static function formatCurrency(float $amount, string $lang = 'en'): string
    {
        $formatted = number_format($amount, 2);
        return '฿' . $formatted;
    }
    
    /**
     * Format date in Thai/English
     * 
     * @param string $date Date string
     * @param string $lang Language code
     * @return string Formatted date
     */
    public static function formatDate(string $date, string $lang = 'th'): string
    {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $date;
        }
        
        if ($lang === 'th') {
            $thaiMonths = [
                1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
                5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
                9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
            ];
            
            $day = date('j', $timestamp);
            $month = $thaiMonths[(int)date('n', $timestamp)];
            $year = (int)date('Y', $timestamp) + 543; // Buddhist Era
            
            return "{$day} {$month} {$year}";
        }
        
        return date('d/m/Y', $timestamp);
    }
    
    /**
     * Get Thai month name
     * 
     * @param int $month Month number (1-12)
     * @return string Thai month name
     */
    public static function getThaiMonth(int $month): string
    {
        $months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
            4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
            10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        return $months[$month] ?? '';
    }
    
    /**
     * Parse Thai month name to number
     * 
     * @param string $text Text containing Thai month
     * @return int|null Month number or null
     */
    public static function parseThaiMonth(string $text): ?int
    {
        $months = [
            'มกราคม' => 1, 'กุมภาพันธ์' => 2, 'มีนาคม' => 3,
            'เมษายน' => 4, 'พฤษภาคม' => 5, 'มิถุนายน' => 6,
            'กรกฎาคม' => 7, 'สิงหาคม' => 8, 'กันยายน' => 9,
            'ตุลาคม' => 10, 'พฤศจิกายน' => 11, 'ธันวาคม' => 12,
            // Short forms
            'ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3,
            'เม.ย.' => 4, 'พ.ค.' => 5, 'มิ.ย.' => 6,
            'ก.ค.' => 7, 'ส.ค.' => 8, 'ก.ย.' => 9,
            'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12,
        ];
        
        foreach ($months as $name => $num) {
            if (strpos($text, $name) !== false) {
                return $num;
            }
        }
        
        return null;
    }
}
