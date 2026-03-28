<?php
/**
 * Product Roadmap Page
 */
session_start();

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';

function __($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'th' ? 'แผนพัฒนาผลิตภัณฑ์' : 'Product Roadmap' ?> - iACC</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($lang === 'th'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --dark: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #3498db;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: <?= $lang === 'th' ? "'Sarabun', " : "" ?>'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.6;
            background: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }
        
        .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
        }
        
        .hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 120px 20px 60px;
            text-align: center;
        }
        
        .hero h1 { font-size: 42px; margin-bottom: 10px; }
        .hero p { opacity: 0.9; max-width: 600px; margin: 0 auto; }
        
        .roadmap-section {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--success), var(--primary), var(--info));
            border-radius: 3px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 35px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .timeline-item.completed::before { background: var(--success); }
        .timeline-item.in-progress::before { background: var(--warning); }
        .timeline-item.planned::before { background: var(--info); }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .timeline-quarter {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            background: #f8f4fb;
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        .status-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        
        .status-badge.completed { background: #d4edda; color: #155724; }
        .status-badge.in-progress { background: #fff3cd; color: #856404; }
        .status-badge.planned { background: #d1ecf1; color: #0c5460; }
        
        .timeline-item h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: var(--dark);
        }
        
        .timeline-item p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .feature-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            font-size: 14px;
        }
        
        .feature-list li i {
            color: var(--primary);
            width: 16px;
        }
        
        .suggest-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 700px;
            margin: 0 auto 60px;
        }
        
        .suggest-section h2 {
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .suggest-section p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .btn-suggest {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-suggest:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(142,68,173,0.3);
        }
        
        .footer {
            background: var(--dark);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .footer a { color: #a569bd; text-decoration: none; }
        
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero h1 { font-size: 28px; }
            .timeline { padding-left: 30px; }
            .timeline-item { padding: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="landing.php" class="nav-brand">iACC</a>
            <ul class="nav-links">
                <li><a href="landing.php"><?= $lang === 'th' ? 'หน้าแรก' : 'Home' ?></a></li>
                <li><a href="about.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'เกี่ยวกับ' : 'About' ?></a></li>
                <li><a href="contact.php?lang=<?= $lang ?>"><?= __('nav_contact') ?></a></li>
                <li><a href="login.php"><?= __('nav_sign_in') ?></a></li>
            </ul>
        </div>
    </nav>
    
    <section class="hero">
        <h1><?= $lang === 'th' ? 'แผนพัฒนาผลิตภัณฑ์' : 'Product Roadmap' ?></h1>
        <p><?= $lang === 'th' ? 'ดูว่าเรากำลังสร้างอะไร และวางแผนอะไรไว้สำหรับอนาคต' : 'See what we\'re building and what we have planned for the future.' ?></p>
    </section>
    
    <div class="roadmap-section">
        <div class="timeline">
            <!-- Completed Q4 2025 -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q4 2025</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'เปิดตัวเวอร์ชัน 2.0' : 'Version 2.0 Launch' ?></h3>
                <p><?= $lang === 'th' ? 'อัพเกรดครั้งใหญ่พร้อม UI/UX ใหม่ทั้งหมดและฟีเจอร์หลักที่ปรับปรุง' : 'Major upgrade with complete UI/UX redesign and improved core features.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'ระบบล็อกอินใหม่' : 'New login system' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'แดชบอร์ดทันสมัย' : 'Modern dashboard' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'รองรับหลายภาษา' : 'Multi-language support' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'การจัดการผู้ใช้' : 'User management' ?></li>
                </ul>
            </div>
            
            <!-- Completed Q1 2026 -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q1 2026</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'เวอร์ชัน 4.5 - พร้อมใช้งาน' : 'Version 4.5 - Production Ready' ?></h3>
                <p><?= $lang === 'th' ? 'ระบบความปลอดภัยสมบูรณ์ AI Chatbot และ Multi-Tenant SaaS' : 'Complete security implementation, AI Chatbot, and Multi-Tenant SaaS architecture.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'รหัสผ่าน Bcrypt' : 'Bcrypt password hashing' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'CSRF Protection' : 'CSRF protection' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Rate Limiting' : 'Rate limiting' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'SQL Injection Prevention' : 'SQL injection prevention' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'AI Chatbot (29 เครื่องมือ)' : 'AI Chatbot (29 tools)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Multi-Tenant SaaS' : 'Multi-Tenant SaaS' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'RBAC System' : 'RBAC system' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'UI ทันสมัย 30+ หน้า' : 'Modern UI (30+ pages)' ?></li>
                </ul>
            </div>
            
            <!-- Completed - Production Deployment -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q1 2026</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'การ Deploy สู่ Production' : 'Production Deployment' ?></h3>
                <p><?= $lang === 'th' ? 'ระบบขึ้น cPanel Production สำเร็จ พร้อม CI/CD Pipeline' : 'Successfully deployed to cPanel production with full CI/CD pipeline.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Deploy สู่ cPanel' : 'cPanel deployment' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'PHP 8.3 บน Staging' : 'PHP 8.3 on staging' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'CI/CD GitHub Actions' : 'CI/CD GitHub Actions' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Health Check อัตโนมัติ' : 'Automated health checks' ?></li>
                </ul>
            </div>
            
            <!-- Completed - v5.0 MVC Architecture -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q1 2026</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'v5.0 - สถาปัตยกรรม MVC' : 'v5.0 - MVC Architecture' ?></h3>
                <p><?= $lang === 'th' ? 'การปรับโครงสร้างทั้งหมดจาก Monolithic PHP เป็น MVC Pattern พร้อม 34 Controllers, 28 Models, 98 Views' : 'Complete migration from monolithic PHP to MVC pattern with 34 Controllers, 28 Models, 98 Views.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '139 MVC Routes' : '139 MVC routes' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'PSR-4 Autoloading' : 'PSR-4 autoloading' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '126 MVC Tests' : '126 MVC tests' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'ลบ Legacy Routes 100%' : 'Zero legacy routes' ?></li>
                </ul>
            </div>
            
            <!-- Completed - v5.1 Sales Channel API -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q1 2026</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'v5.1 - Sales Channel API' : 'v5.1 - Sales Channel API' ?></h3>
                <p><?= $lang === 'th' ? 'REST API สำหรับเชื่อมต่อ OTA, PMS และ Channel Manager พร้อมระบบ Webhook และจัดการคำสั่งซื้อ' : 'REST API for OTA, PMS, and channel manager integrations with webhooks and order management.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'REST API (CRUD)' : 'REST API (CRUD)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'API Key Authentication' : 'API key authentication' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Rate Limiting (60/นาที)' : 'Rate limiting (60/min)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Webhooks (HMAC-SHA256)' : 'Webhooks (HMAC-SHA256)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'จัดการคำสั่งซื้อ' : 'Order management UI' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Export CSV/JSON' : 'Export CSV/JSON' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '20 API Tests' : '20 API tests' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Staging Environment' : 'Staging environment' ?></li>
                </ul>
            </div>
            
            <!-- Completed - v5.2 UI Modernization -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q1 2026</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'v5.2 - ปรับปรุง UX/UI ทุกหน้า MVC' : 'v5.2 - MVC View Modernization' ?></h3>
                <p><?= $lang === 'th' ? 'อัปเกรด 18 หน้า MVC Views ทั้งหมดเป็นดีไซน์ Legacy Modern พร้อม Design System แบบ Color-coded ตามโมดูล' : 'Upgraded all 18 MVC views to legacy modern design with color-coded module design system.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '18 Views อัปเกรด' : '18 views upgraded' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'PO Module (4 หน้า)' : 'PO module (4 views)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Delivery Module (4 หน้า)' : 'Delivery module (4 views)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Receipt Module (3 หน้า)' : 'Receipt module (3 views)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Voucher Module (3 หน้า)' : 'Voucher module (3 views)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'PR, Payment, Billing (4 หน้า)' : 'PR, Payment, Billing (4 views)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Gradient สีตามโมดูล' : 'Color-coded module gradients' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Inter Font + Responsive' : 'Inter font + responsive layout' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '42/42 E2E Tests ผ่าน' : '42/42 E2E tests passing' ?></li>
                </ul>
            </div>
            
            <!-- Completed Q2 - Payment Gateway -->
            <div class="timeline-item completed">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q2 2026</span>
                    <span class="status-badge completed"><?= $lang === 'th' ? 'เสร็จสิ้น' : 'Completed' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'v5.3 - Payment Gateway และ Multi-Currency' : 'v5.3 - Payment Gateway & Multi-Currency' ?></h3>
                <p><?= $lang === 'th' ? 'เชื่อมต่อ PromptPay, รองรับหลายสกุลเงิน, รายงานภาษีไทย และ Admin Slip Review' : 'PromptPay integration, multi-currency support, Thai tax reports, and admin slip review workflow.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'QR PromptPay + อัปโหลดสลิป' : 'QR PromptPay + slip upload' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Admin ตรวจสอบสลิป (อนุมัติ/ปฏิเสธ)' : 'Admin slip review (approve/reject)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'รองรับ 10 สกุลเงิน (BOT API)' : '10 currencies with BOT exchange rates' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'ภ.พ.30 — VAT รายเดือน' : 'PP30 — Monthly VAT Return' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'ภ.ง.ด.3/53 — หัก ณ ที่จ่าย' : 'PND3/53 — Withholding Tax' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'แดชบอร์ดภาษีรายปี' : 'Annual tax dashboard' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Export CSV/JSON' : 'CSV/JSON export' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'UI ทันสมัย (master-data.css)' : 'Modern UI (master-data.css)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '21 Routes ใหม่ (160 ทั้งหมด)' : '21 new routes (160 total)' ?></li>
                </ul>
            </div>

            <!-- In Progress Q3 - Expense Module -->
            <div class="timeline-item in-progress">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q3 2026</span>
                    <span class="status-badge in-progress"><?= $lang === 'th' ? 'กำลังดำเนินการ' : 'In Progress' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'v5.4 - ระบบค่าใช้จ่าย (Expense Module)' : 'v5.4 - Expense Tracking Module' ?></h3>
                <p><?= $lang === 'th' ? 'บันทึกค่าใช้จ่ายรายวัน เชื่อมโยงกับโปรเจกต์/ใบแจ้งหนี้ รายงานต้นทุน และ P&L' : 'Daily expense recording, project/invoice linking, cost reporting, and profit & loss analysis.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'CRUD ค่าใช้จ่าย (สร้าง/แก้ไข/ลบ)' : 'Expense CRUD (create/edit/delete)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'หมวดหมู่ค่าใช้จ่าย (10 หมวด EN/TH)' : 'Expense categories (10 seeded, bilingual)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'เชื่อมโยง PO/PR/Invoice' : 'Link to PO/PR/Invoice' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'Workflow อนุมัติ (draft → approved → paid)' : 'Approval workflow (draft → approved → paid)' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'รองรับ VAT/WHT พร้อมคำนวณอัตโนมัติ' : 'VAT/WHT with live calculator' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'อัปโหลดใบเสร็จ/เอกสาร' : 'Receipt/document upload' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? 'สรุปรายเดือน + กราฟแยกหมวด' : 'Monthly summary + category chart' ?></li>
                    <li><i class="fa fa-check"></i> <?= $lang === 'th' ? '11 Routes, 5 Views, 2 Models' : '11 routes, 5 views, 2 models' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'รายงานต้นทุนตามโปรเจกต์' : 'Project cost report' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'Export CSV/PDF' : 'Export CSV/PDF' ?></li>
                </ul>
            </div>

            <!-- Planned Q3 - Journal Module -->
            <div class="timeline-item planned">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q3 2026</span>
                    <span class="status-badge planned"><?= $lang === 'th' ? 'วางแผน' : 'Planned' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'v5.5 - สมุดรายวันและประเภทใบสำคัญ' : 'v5.5 - Journal Module & Voucher Classification' ?></h3>
                <p><?= $lang === 'th' ? 'เพิ่มโมดูลสมุดรายวัน (Journal Voucher) และระบบจำแนกประเภทใบสำคัญ' : 'Add Journal Voucher module and voucher type classification system.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'โมดูล Journal Voucher (สร้าง/ดู/รายการ)' : 'Journal Voucher module (create/view/list)' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'คอลัมน์ voucher_type (payment/receipt/journal)' : 'voucher_type column (payment/receipt/journal)' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'Debit/Credit บันทึกบัญชีคู่' : 'Debit/credit double-entry recording' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'ผังบัญชี (Chart of Accounts)' : 'Chart of Accounts integration' ?></li>
                </ul>
            </div>
            
            <!-- Planned Q4 - Mobile & Integrations -->
            <div class="timeline-item planned">
                <div class="timeline-header">
                    <span class="timeline-quarter">Q4 2026</span>
                    <span class="status-badge planned"><?= $lang === 'th' ? 'วางแผน' : 'Planned' ?></span>
                </div>
                <h3><?= $lang === 'th' ? 'แอปมือถือและการผสานรวมขั้นสูง' : 'Mobile App & Advanced Integrations' ?></h3>
                <p><?= $lang === 'th' ? 'แอป React Native, เชื่อมต่อธนาคาร, E-commerce และ Zapier' : 'React Native app, bank connections, e-commerce and Zapier integrations.' ?></p>
                <ul class="feature-list">
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'React Native App (iOS/Android)' : 'React Native app (iOS/Android)' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'แจ้งเตือนแบบพุช' : 'Push notifications' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'เชื่อมต่อธนาคาร' : 'Bank connections' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'ผสานรวม E-commerce' : 'E-commerce integrations' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'Public Developer API' : 'Public developer API' ?></li>
                    <li><i class="fa fa-circle-o"></i> <?= $lang === 'th' ? 'สแกนใบเสร็จ (OCR)' : 'Receipt scanning (OCR)' ?></li>
                </ul>
            </div>
        </div>
        
        <div class="suggest-section">
            <h2><?= $lang === 'th' ? 'มีไอเดียฟีเจอร์?' : 'Have a Feature Idea?' ?></h2>
            <p><?= $lang === 'th' ? 'เราชอบฟังความคิดเห็นจากผู้ใช้! แจ้งให้เราทราบว่าคุณอยากเห็นอะไรใน iACC' : 'We love hearing from our users! Let us know what you\'d like to see in iACC.' ?></p>
            <a href="contact.php?lang=<?= $lang ?>" class="btn-suggest">
                <i class="fa fa-lightbulb-o"></i> <?= $lang === 'th' ? 'แนะนำฟีเจอร์' : 'Suggest a Feature' ?>
            </a>
        </div>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
