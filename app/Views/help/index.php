<?php
$pageTitle = 'Help';

/**
 * Help & Support Page
 * Provides FAQs, documentation, and contact information
 * Bilingual: English / Thai
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$t = [
    'en' => [
        'help_center'    => 'Help Center',
        'help_desc'      => 'Find answers to common questions and get support',
        'search_help'    => 'Search for help...',
        'faq'            => 'FAQs',
        'faq_desc'       => 'Frequently asked questions',
        'documentation'  => 'Documentation',
        'doc_desc'       => 'User guides and manuals',
        'contact_support'=> 'Contact Support',
        'contact_desc'   => 'Get help from our team',
        'getting_started'=> 'Getting Started',
        'getting_desc'   => 'Quick start guide',
        'faq_title'      => 'Frequently Asked Questions',
        'faq1_q'         => 'How do I create a new invoice?',
        'faq1_a'         => 'Navigate to the Invoice section from the sidebar menu. Click on "Create Invoice" or the "+ New" button. Fill in the customer details, add line items, set the due date, and click "Save". You can then print or email the invoice directly to your customer.',
        'faq2_q'         => 'How do I set up payment gateway?',
        'faq2_a'         => 'Payment gateways can be configured by administrators. Go to Admin Tools > Payment Gateway Config. Enter your PayPal or Stripe API credentials. Use Test Mode to verify the integration before going live. Once configured, customers can pay invoices online.',
        'faq3_q'         => 'How do I change my password?',
        'faq3_a'         => 'Click on your profile picture in the top right corner and select "My Profile". In the profile page, find the "Change Password" section. Enter your current password, then enter and confirm your new password. Click "Update Password" to save the changes.',
        'faq4_q'         => 'How do I switch between companies?',
        'faq4_a'         => 'Administrators and Super Admins can switch between companies from the dashboard. Click on the company name in the top navbar or go to Dashboard > Select a company from the list. Regular users are assigned to a specific company and cannot switch.',
        'faq5_q'         => 'How do I change the language?',
        'faq5_a'         => 'Go to Settings from your profile dropdown menu. In the Language & Region section, select your preferred language (English or Thai). The page will reload with your selected language. You can also use the language switcher in the top navbar.',
        'faq6_q'         => 'How do I generate reports?',
        'faq6_a'         => 'Navigate to Reports from the sidebar menu. Select the type of report you want to generate (Sales, Payments, etc.). Set your date range and filters, then click "Generate Report". Reports can be exported to PDF or Excel for further analysis.',
        'user_manual'    => 'User Manual',
        'user_manual_desc'=> 'Step-by-step guide from setup to reports',
        'doc_invoice'    => 'Invoice Management',
        'doc_invoice_desc'=> 'Learn how to create, edit, and manage invoices, quotations, and receipts.',
        'doc_payment'    => 'Payment Processing',
        'doc_payment_desc'=> 'Guide to accepting payments via PayPal, Stripe, and bank transfers.',
        'doc_users'      => 'User Management',
        'doc_users_desc' => 'Manage users, roles, and permissions for your organization.',
        'doc_reports'    => 'Reports & Analytics',
        'doc_reports_desc'=> 'Generate and export financial reports for your business.',
        'contact_us'     => 'Contact Us',
        'email_support'  => 'Email Support',
        'email_desc'     => 'Get help via email within 24 hours',
        'phone_support'  => 'Phone Support',
        'phone_hours'    => 'Mon-Fri, 9:00 AM - 6:00 PM',
        'live_chat'      => 'Live Chat',
        'live_chat_desc' => 'Chat with our support team',
        'start_chat'     => 'Start Chat',
        'chat_soon'      => 'Live chat coming soon!',
        'system_name'    => 'iACC Accounting System',
        'system_desc'    => 'Professional accounting management for modern businesses',
    ],
    'th' => [
        'help_center'    => 'ศูนย์ช่วยเหลือ',
        'help_desc'      => 'ค้นหาคำตอบสำหรับคำถามทั่วไปและรับการสนับสนุน',
        'search_help'    => 'ค้นหาความช่วยเหลือ...',
        'faq'            => 'คำถามที่พบบ่อย',
        'faq_desc'       => 'คำถามที่ถูกถามบ่อย',
        'documentation'  => 'เอกสาร',
        'doc_desc'       => 'คู่มือผู้ใช้และคำแนะนำ',
        'contact_support'=> 'ติดต่อฝ่ายสนับสนุน',
        'contact_desc'   => 'รับความช่วยเหลือจากทีมงาน',
        'getting_started'=> 'เริ่มต้นใช้งาน',
        'getting_desc'   => 'คู่มือเริ่มต้นอย่างรวดเร็ว',
        'faq_title'      => 'คำถามที่พบบ่อย',
        'faq1_q'         => 'ฉันจะสร้างใบแจ้งหนี้ใหม่ได้อย่างไร?',
        'faq1_a'         => 'ไปที่หน้าใบแจ้งหนี้จากเมนูด้านข้าง คลิกที่ "สร้างใบแจ้งหนี้" หรือปุ่ม "+ ใหม่" กรอกรายละเอียดลูกค้า เพิ่มรายการสินค้า ตั้งวันครบกำหนด แล้วคลิก "บันทึก" จากนั้นคุณสามารถพิมพ์หรือส่งใบแจ้งหนี้ทางอีเมลให้ลูกค้าได้โดยตรง',
        'faq2_q'         => 'ฉันจะตั้งค่าช่องทางชำระเงินได้อย่างไร?',
        'faq2_a'         => 'ช่องทางชำระเงินสามารถตั้งค่าได้โดยผู้ดูแลระบบ ไปที่ Admin Tools > Payment Gateway Config กรอกข้อมูล API ของ PayPal หรือ Stripe ใช้โหมดทดสอบเพื่อตรวจสอบก่อนเปิดใช้งานจริง เมื่อตั้งค่าเรียบร้อย ลูกค้าสามารถชำระเงินออนไลน์ได้',
        'faq3_q'         => 'ฉันจะเปลี่ยนรหัสผ่านได้อย่างไร?',
        'faq3_a'         => 'คลิกที่รูปโปรไฟล์มุมขวาบนแล้วเลือก "โปรไฟล์ของฉัน" ในหน้าโปรไฟล์ ให้หาส่วน "เปลี่ยนรหัสผ่าน" กรอกรหัสผ่านปัจจุบัน แล้วกรอกและยืนยันรหัสผ่านใหม่ คลิก "อัปเดตรหัสผ่าน" เพื่อบันทึก',
        'faq4_q'         => 'ฉันจะสลับบริษัทได้อย่างไร?',
        'faq4_a'         => 'ผู้ดูแลระบบสามารถสลับบริษัทได้จากแดชบอร์ด คลิกที่ชื่อบริษัทในแถบด้านบนหรือไปที่แดชบอร์ด > เลือกบริษัทจากรายการ ผู้ใช้ทั่วไปจะถูกกำหนดให้อยู่ในบริษัทเดียวและไม่สามารถสลับได้',
        'faq5_q'         => 'ฉันจะเปลี่ยนภาษาได้อย่างไร?',
        'faq5_a'         => 'ไปที่การตั้งค่าจากเมนูโปรไฟล์ ในส่วนภาษาและภูมิภาค เลือกภาษาที่ต้องการ (อังกฤษ หรือ ไทย) หน้าจะโหลดใหม่ด้วยภาษาที่เลือก คุณยังสามารถใช้ตัวสลับภาษาในแถบด้านบนได้',
        'faq6_q'         => 'ฉันจะสร้างรายงานได้อย่างไร?',
        'faq6_a'         => 'ไปที่รายงานจากเมนูด้านข้าง เลือกประเภทรายงานที่ต้องการ (ยอดขาย, การชำระเงิน ฯลฯ) ตั้งช่วงวันที่และตัวกรอง แล้วคลิก "สร้างรายงาน" รายงานสามารถส่งออกเป็น PDF หรือ Excel ได้',
        'user_manual'    => 'คู่มือผู้ใช้งาน',
        'user_manual_desc'=> 'คู่มือทีละขั้นตอน ตั้งแต่การตั้งค่าไปจนถึงรายงาน',
        'doc_invoice'    => 'จัดการใบแจ้งหนี้',
        'doc_invoice_desc'=> 'เรียนรู้วิธีสร้าง แก้ไข และจัดการใบแจ้งหนี้ ใบเสนอราคา และใบเสร็จ',
        'doc_payment'    => 'การรับชำระเงิน',
        'doc_payment_desc'=> 'คู่มือการรับชำระเงินผ่าน PayPal, Stripe และโอนเงินผ่านธนาคาร',
        'doc_users'      => 'จัดการผู้ใช้',
        'doc_users_desc' => 'จัดการผู้ใช้ บทบาท และสิทธิ์สำหรับองค์กรของคุณ',
        'doc_reports'    => 'รายงานและการวิเคราะห์',
        'doc_reports_desc'=> 'สร้างและส่งออกรายงานทางการเงินสำหรับธุรกิจของคุณ',
        'contact_us'     => 'ติดต่อเรา',
        'email_support'  => 'สนับสนุนทางอีเมล',
        'email_desc'     => 'รับความช่วยเหลือทางอีเมลภายใน 24 ชั่วโมง',
        'phone_support'  => 'สนับสนุนทางโทรศัพท์',
        'phone_hours'    => 'จันทร์-ศุกร์ 9:00 - 18:00 น.',
        'live_chat'      => 'แชทสด',
        'live_chat_desc' => 'แชทกับทีมสนับสนุนของเรา',
        'start_chat'     => 'เริ่มแชท',
        'chat_soon'      => 'แชทสดเร็วๆ นี้!',
        'system_name'    => 'ระบบบัญชี iACC',
        'system_desc'    => 'ระบบจัดการบัญชีอย่างมืออาชีพสำหรับธุรกิจยุคใหม่',
    ]
][$lang];
?>

<style>
.help-container {
    max-width: 1000px;
    margin: 0 auto;
}

.help-header {
    text-align: center;
    margin-bottom: 40px;
}

.help-header h1 {
    font-size: 32px;
    margin: 0 0 12px 0;
    color: #333;
}

.help-header p {
    color: #6c757d;
    font-size: 16px;
    margin: 0;
}

.help-search {
    max-width: 500px;
    margin: 25px auto 0;
    position: relative;
}

.help-search input {
    width: 100%;
    padding: 14px 20px 14px 50px;
    border: 2px solid #e9ecef;
    border-radius: 50px;
    font-size: 15px;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.help-search input:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 4px rgba(142, 68, 173, 0.1);
}

.help-search i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #8e44ad;
    font-size: 18px;
}

.help-quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

@media (max-width: 992px) {
    .help-quick-links {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .help-quick-links {
        grid-template-columns: 1fr;
    }
}

.quick-link-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.quick-link-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.quick-link-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}

.quick-link-icon i {
    color: white;
    font-size: 24px;
}

.quick-link-card h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
}

.quick-link-card p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

.help-section {
    margin-bottom: 40px;
}

.help-section-title {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.help-section-title i {
    color: #8e44ad;
}

/* FAQ Accordion */
.faq-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.faq-item {
    border-bottom: 1px solid #f0f0f0;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-question {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s;
}

.faq-question:hover {
    background: #f8f9fa;
}

.faq-question h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 500;
    color: #333;
    flex: 1;
    padding-right: 20px;
}

.faq-question i {
    color: #8e44ad;
    transition: transform 0.3s;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    display: none;
    padding: 0 24px 20px;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.7;
}

.faq-item.active .faq-answer {
    display: block;
}

/* Contact Cards */
.contact-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .contact-cards {
        grid-template-columns: 1fr;
    }
}

.contact-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.contact-card i {
    font-size: 36px;
    color: #8e44ad;
    margin-bottom: 15px;
}

.contact-card h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
}

.contact-card p {
    margin: 0 0 15px 0;
    color: #6c757d;
    font-size: 14px;
}

.contact-card a {
    color: #8e44ad;
    text-decoration: none;
    font-weight: 500;
}

.contact-card a:hover {
    text-decoration: underline;
}

/* Documentation */
.doc-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .doc-grid {
        grid-template-columns: 1fr;
    }
}

.doc-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    gap: 16px;
    align-items: flex-start;
    transition: transform 0.3s, box-shadow 0.3s;
}

.doc-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.doc-icon {
    width: 50px;
    height: 50px;
    background: #f3e8ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.doc-icon i {
    color: #8e44ad;
    font-size: 22px;
}

.doc-content h4 {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 600;
}

.doc-content p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

/* Version Info */
.version-info {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: white;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    margin-top: 40px;
}

.version-info h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
}

.version-info p {
    margin: 0;
    opacity: 0.9;
}

.version-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    margin-top: 10px;
}
</style>

<div class="help-container">
    <div class="help-header">
        <h1><i class="fa fa-life-ring"></i> <?= $t['help_center'] ?></h1>
        <p><?= $t['help_desc'] ?></p>
        
        <div class="help-search">
            <i class="fa fa-search"></i>
            <input type="text" placeholder="<?= $t['search_help'] ?>" id="helpSearch">
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="help-quick-links">
        <a href="#faq" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-question"></i>
            </div>
            <h3><?= $t['faq'] ?></h3>
            <p><?= $t['faq_desc'] ?></p>
        </a>
        
        <a href="#docs" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-book"></i>
            </div>
            <h3><?= $t['documentation'] ?></h3>
            <p><?= $t['doc_desc'] ?></p>
        </a>
        
        <a href="#contact" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-envelope"></i>
            </div>
            <h3><?= $t['contact_support'] ?></h3>
            <p><?= $t['contact_desc'] ?></p>
        </a>
        
        <a href="index.php?page=dashboard" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-play-circle"></i>
            </div>
            <h3><?= $t['getting_started'] ?></h3>
            <p><?= $t['getting_desc'] ?></p>
        </a>
    </div>
    
    <!-- FAQ Section -->
    <div class="help-section" id="faq">
        <h2 class="help-section-title">
            <i class="fa fa-question-circle"></i>
            <?= $t['faq_title'] ?>
        </h2>
        
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4><?= $t['faq1_q'] ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <?= $t['faq1_a'] ?>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4><?= $t['faq2_q'] ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <?= $t['faq2_a'] ?>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4><?= $t['faq3_q'] ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <?= $t['faq3_a'] ?>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4><?= $t['faq4_q'] ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <?= $t['faq4_a'] ?>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4><?= $t['faq5_q'] ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <?= $t['faq5_a'] ?>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4><?= $t['faq6_q'] ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <?= $t['faq6_a'] ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documentation Section -->
    <div class="help-section" id="docs">
        <h2 class="help-section-title">
            <i class="fa fa-book"></i>
            <?= $t['documentation'] ?>
        </h2>
        
        <div class="doc-grid">
            <a href="index.php?page=user_manual" class="doc-card" style="text-decoration:none; color:inherit;">
                <div class="doc-icon" style="background: #8e44ad;">
                    <i class="fa fa-book" style="color: white;"></i>
                </div>
                <div class="doc-content">
                    <h4><?= $t['user_manual'] ?></h4>
                    <p><?= $t['user_manual_desc'] ?></p>
                </div>
            </a>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="doc-content">
                    <h4><?= $t['doc_invoice'] ?></h4>
                    <p><?= $t['doc_invoice_desc'] ?></p>
                </div>
            </div>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-credit-card"></i>
                </div>
                <div class="doc-content">
                    <h4><?= $t['doc_payment'] ?></h4>
                    <p><?= $t['doc_payment_desc'] ?></p>
                </div>
            </div>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="doc-content">
                    <h4><?= $t['doc_users'] ?></h4>
                    <p><?= $t['doc_users_desc'] ?></p>
                </div>
            </div>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-bar-chart"></i>
                </div>
                <div class="doc-content">
                    <h4><?= $t['doc_reports'] ?></h4>
                    <p><?= $t['doc_reports_desc'] ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Section -->
    <div class="help-section" id="contact">
        <h2 class="help-section-title">
            <i class="fa fa-headphones"></i>
            <?= $t['contact_us'] ?>
        </h2>
        
        <div class="contact-cards">
            <div class="contact-card">
                <i class="fa fa-envelope"></i>
                <h3><?= $t['email_support'] ?></h3>
                <p><?= $t['email_desc'] ?></p>
                <a href="mailto:support@iacc.com">support@iacc.com</a>
            </div>
            
            <div class="contact-card">
                <i class="fa fa-phone"></i>
                <h3><?= $t['phone_support'] ?></h3>
                <p><?= $t['phone_hours'] ?></p>
                <a href="tel:+6621234567">+66 2 123 4567</a>
            </div>
            
            <div class="contact-card">
                <i class="fa fa-comments"></i>
                <h3><?= $t['live_chat'] ?></h3>
                <p><?= $t['live_chat_desc'] ?></p>
                <a href="#" onclick="alert('<?= $t['chat_soon'] ?>')"><?= $t['start_chat'] ?></a>
            </div>
        </div>
    </div>
    
    <!-- Version Info -->
    <div class="version-info">
        <h3><?= $t['system_name'] ?></h3>
        <p><?= $t['system_desc'] ?></p>
        <span class="version-badge">Version 2.8</span>
    </div>
</div>

<script>
function toggleFaq(element) {
    const faqItem = element.parentElement;
    const wasActive = faqItem.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't already open
    if (!wasActive) {
        faqItem.classList.add('active');
    }
}

// Search functionality
document.getElementById('helpSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question h4').textContent.toLowerCase();
        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
        
        if (question.includes(query) || answer.includes(query)) {
            item.style.display = 'block';
            if (query.length > 2) {
                item.classList.add('active');
            }
        } else {
            item.style.display = query.length > 0 ? 'none' : 'block';
        }
    });
});
</script>
