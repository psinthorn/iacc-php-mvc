<?php
/**
 * iACC — Template Setup Demo
 * Public-facing page showing how the template setup wizard works,
 * with screenshots/walkthrough of the process.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';
function __($key) { global $t; return $t[$key] ?? $key; }
$htmlLang = $lang === 'th' ? 'th' : 'en';
?>
<!DOCTYPE html>
<html lang="<?= $htmlLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'th' ? 'สาธิตการตั้งค่าเทมเพลต' : 'Template Setup Demo' ?> — iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #1e293b; --gray: #64748b; --bg: #f8fafc; --ocean: #0369a1; --success: #10b981; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--dark); line-height: 1.6; background: var(--bg); }
        .top-bar { background: var(--dark); color: white; padding: 16px 20px; }
        .top-bar .inner { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; }
        .top-bar a:hover { color: white; }
        .top-bar h1 { font-size: 20px; font-weight: 700; }
        .top-bar h1 span { color: var(--primary); }
        .container { max-width: 900px; margin: 0 auto; padding: 48px 20px 80px; }
        .hero-section { text-align: center; margin-bottom: 56px; }
        .hero-section h2 { font-size: 36px; font-weight: 800; margin-bottom: 12px; }
        .hero-section h2 em { font-style: normal; color: var(--primary); }
        .hero-section p { font-size: 17px; color: var(--gray); max-width: 600px; margin: 0 auto; }
        /* Timeline */
        .timeline { position: relative; padding-left: 40px; }
        .timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 3px; background: linear-gradient(to bottom, var(--primary), var(--ocean), var(--success)); border-radius: 2px; }
        .step { position: relative; margin-bottom: 48px; }
        .step:last-child { margin-bottom: 0; }
        .step-dot { position: absolute; left: -40px; top: 4px; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: white; z-index: 1; }
        .step:nth-child(1) .step-dot { background: var(--primary); }
        .step:nth-child(2) .step-dot { background: #3b82f6; }
        .step:nth-child(3) .step-dot { background: var(--ocean); }
        .step:nth-child(4) .step-dot { background: #8b5cf6; }
        .step:nth-child(5) .step-dot { background: var(--success); }
        .step:nth-child(6) .step-dot { background: #e74c3c; }
        .step h3 { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .admin-features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
        .admin-feat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; align-items: flex-start; gap: 10px; }
        .admin-feat i { color: var(--primary); font-size: 16px; margin-top: 2px; flex-shrink: 0; }
        .admin-feat h4 { font-size: 13px; font-weight: 600; margin-bottom: 2px; color: var(--dark); }
        .admin-feat p { font-size: 12px; color: var(--gray); line-height: 1.4; margin: 0; }
        .step p { color: var(--gray); font-size: 15px; margin-bottom: 16px; }
        .step-card { background: white; border-radius: 16px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
        .step-card ul { list-style: none; margin-top: 12px; }
        .step-card ul li { padding: 8px 0; font-size: 14px; color: var(--dark); display: flex; align-items: flex-start; gap: 10px; }
        .step-card ul li i { color: var(--success); margin-top: 3px; flex-shrink: 0; }
        .step-card code { background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 13px; font-family: monospace; }
        .step-image { margin-top: 16px; background: #f8fafc; border-radius: 12px; padding: 20px; border: 2px dashed #e2e8f0; text-align: center; }
        .step-image i { font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 8px; }
        .step-image span { color: var(--gray); font-size: 13px; }
        .code-block { background: #0f172a; color: #e2e8f0; padding: 16px 20px; border-radius: 10px; font-family: monospace; font-size: 13px; line-height: 1.7; margin-top: 12px; overflow-x: auto; }
        .code-block .dim { color: #64748b; }
        .code-block .hl { color: #86efac; }
        /* CTA */
        .cta-section { text-align: center; margin-top: 64px; padding: 48px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 20px; color: white; }
        .cta-section h3 { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
        .cta-section p { opacity: 0.9; margin-bottom: 24px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 50px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.2s; }
        .btn-white { background: white; color: var(--primary); }
        .btn-white:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        .btn-outline { border: 2px solid rgba(255,255,255,0.4); color: white; margin-left: 12px; }
        .btn-outline:hover { border-color: white; background: rgba(255,255,255,0.1); }
        .links { display: flex; gap: 16px; justify-content: center; margin-top: 32px; }
        .links a { color: var(--primary); font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .links a:hover { text-decoration: underline; }
        @media (max-width: 600px) {
            .hero-section h2 { font-size: 26px; }
            .cta-section { padding: 32px 20px; }
        }
    </style>
</head>
<body>
<div class="top-bar">
    <div class="inner">
        <h1><span>iACC</span> <?= $lang === 'th' ? 'สาธิตเทมเพลต' : 'Template Demo' ?></h1>
        <a href="landing.php?lang=<?= $lang ?>"><i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับหน้าหลัก' : 'Back to Home' ?></a>
    </div>
</div>

<div class="container">
    <div class="hero-section">
        <h2><?= $lang === 'th' ? 'ตั้งค่าเว็บไซต์ <em>ใน 5 นาที</em>' : 'Set Up Your Website <em>in 5 Minutes</em>' ?></h2>
        <p><?= $lang === 'th' ? 'ดูขั้นตอนการตั้งค่าเทมเพลต Tour Company Demo ตั้งแต่ดาวน์โหลดจนถึงรับออร์เดอร์จริง' : 'Follow the complete setup walkthrough — from download to receiving real bookings via iACC API.' ?></p>
    </div>

    <div class="timeline">
        <!-- Step 1 -->
        <div class="step">
            <div class="step-dot">1</div>
            <h3><?= $lang === 'th' ? 'ดาวน์โหลดเทมเพลต' : 'Download the Template' ?></h3>
            <p><?= $lang === 'th' ? 'ดาวน์โหลดไฟล์ ZIP จากหน้า Templates หรือ GitHub' : 'Download the ZIP file from the Templates page or GitHub.' ?></p>
            <div class="step-card">
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ไปที่หน้า Templates บนเว็บ iACC' : 'Go to Templates section on iACC website' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'คลิก "ดาวน์โหลดฟรี" ที่เทมเพลต Tour Company Demo' : 'Click "Download Free" on Tour Company Demo template' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'แตกไฟล์ ZIP ไปยังโฟลเดอร์เว็บของคุณ' : 'Extract ZIP to your web hosting folder' ?></li>
                </ul>
                <div class="code-block">
<span class="dim"># File structure after extraction</span>
tour-company-demo/
  index.php          <span class="dim"># Main website</span>
  setup.php          <span class="dim"># Setup wizard</span>
  sync.php           <span class="dim"># Product sync</span>
  admin.php          <span class="dim"># Admin panel</span>
  book.php           <span class="dim"># Booking handler</span>
  config.php         <span class="dim"># Configuration</span>
  css/style.css      <span class="dim"># Stylesheet</span>
  includes/          <span class="dim"># PHP classes</span>
  data/              <span class="dim"># SQLite database</span>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="step">
            <div class="step-dot">2</div>
            <h3><?= $lang === 'th' ? 'สร้าง API Key' : 'Generate API Key' ?></h3>
            <p><?= $lang === 'th' ? 'สร้าง API Key และ Secret จากระบบ iACC ของคุณ' : 'Generate your API Key and Secret from your iACC account.' ?></p>
            <div class="step-card">
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เข้าสู่ระบบ iACC → ตั้งค่า → Sales Channel API' : 'Login to iACC → Settings → Sales Channel API' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เปิดใช้งานแผน Free Trial (50 bookings ฟรี)' : 'Activate Free Trial plan (50 bookings free)' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'คลิก "Generate New API Key"' : 'Click "Generate New API Key"' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'คัดลอก API Key (เริ่มด้วย iACC_) และ Secret' : 'Copy the API Key (starts with iACC_) and Secret' ?></li>
                </ul>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="step">
            <div class="step-dot">3</div>
            <h3><?= $lang === 'th' ? 'เปิด Setup Wizard' : 'Run the Setup Wizard' ?></h3>
            <p><?= $lang === 'th' ? 'เปิดเว็บไซต์ครั้งแรก ระบบจะพาไปหน้า setup โดยอัตโนมัติ' : 'Open your website for the first time — it auto-redirects to the setup wizard.' ?></p>
            <div class="step-card">
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เปิด https://your-site.com/setup.php' : 'Open https://your-site.com/setup.php' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'กรอก URL ของ iACC, API Key และ Secret' : 'Enter your iACC URL, API Key, and Secret' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'กด "Test Connection" — ต้องแสดง Connected + แผนของคุณ' : 'Click "Test Connection" — should show Connected + your plan' ?></li>
                </ul>
                <div class="step-image">
                    <i class="fa fa-plug"></i>
                    <span><?= $lang === 'th' ? 'Step 1: ทดสอบการเชื่อมต่อ API' : 'Step 1: Test API Connection' ?></span>
                </div>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="step">
            <div class="step-dot">4</div>
            <h3><?= $lang === 'th' ? 'ซิงค์สินค้า' : 'Sync Products' ?></h3>
            <p><?= $lang === 'th' ? 'ดึงข้อมูลสินค้าและหมวดหมู่จาก iACC มาเก็บในเว็บไซต์' : 'Pull your products and categories from iACC into your local website.' ?></p>
            <div class="step-card">
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'กด "Sync Products Now" ในหน้า Setup Wizard' : 'Click "Sync Products Now" in the Setup Wizard' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ระบบดึงหมวดหมู่และสินค้าทั้งหมดจาก iACC API' : 'System fetches all categories and products from iACC API' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ข้อมูลถูกเก็บใน SQLite บนเว็บของคุณ (โหลดเร็ว ไม่ต้องเรียก API ทุกครั้ง)' : 'Data stored in local SQLite database (fast loading, no API call per page view)' ?></li>
                </ul>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="step">
            <div class="step-dot">5</div>
            <h3><?= $lang === 'th' ? 'เปิดใช้งาน!' : 'Go Live!' ?></h3>
            <p><?= $lang === 'th' ? 'ตั้งชื่อเว็บไซต์ เลือกสี บันทึก แล้วเปิดใช้งานได้เลย' : 'Set your site name, choose a theme color, save, and your website is live!' ?></p>
            <div class="step-card">
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'กรอกชื่อเว็บไซต์และเลือกสีธีม' : 'Enter your site title and pick a theme color' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'กด "Save & Launch" — ระบบบันทึก config.php' : 'Click "Save & Launch" — config.php is written' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เว็บไซต์ของคุณพร้อมรับจองแล้ว! 🎉' : 'Your website is ready to receive bookings! 🎉' ?></li>
                </ul>
            </div>
        </div>

        <!-- Step 6: Admin Panel -->
        <div class="step">
            <div class="step-dot">6</div>
            <h3><?= $lang === 'th' ? 'จัดการผ่าน Admin Panel' : 'Manage with Admin Panel' ?></h3>
            <p><?= $lang === 'th' ? 'หลังจากเว็บไซต์ออนไลน์แล้ว ใช้ Admin Panel เพื่อจัดการทุกอย่าง — API, สินค้า, และข้อมูลการจอง' : 'Once your site is live, use the built-in Admin Panel to manage everything — API, products, and bookings.' ?></p>

            <!-- 6a: Login -->
            <div class="step-card" style="margin-bottom:20px;">
                <h3 style="font-size:16px;"><i class="fa fa-lock" style="color:var(--primary);"></i> <?= $lang === 'th' ? 'เข้าสู่ระบบ Admin' : 'Admin Login' ?></h3>
                <p style="font-size:14px; color:var(--gray); margin-bottom:12px;"><?= $lang === 'th' ? 'Admin Panel มีระบบ login ป้องกัน — คุณต้องเข้าสู่ระบบก่อนเข้าถึงหน้าจัดการ' : 'The Admin Panel is password-protected — you must sign in before accessing management features.' ?></p>
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เปิด <code>admin.php</code> — ระบบจะ redirect ไปหน้า login อัตโนมัติ' : 'Open <code>admin.php</code> — you will be redirected to the login page automatically' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ใส่ Username และ Password (ค่าเริ่มต้น: <code>admin</code> / <code>admin123</code>)' : 'Enter Username and Password (default: <code>admin</code> / <code>admin123</code>)' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'กด "Sign In" — เข้าสู่หน้าจัดการทันที' : 'Click "Sign In" — you are taken to the admin dashboard' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เปลี่ยนรหัสผ่านผ่าน <code>setup.php</code> → Step 3 (Admin Login)' : 'Change password via <code>setup.php</code> → Step 3 (Admin Login section)' ?></li>
                </ul>
                <div class="code-block" style="margin-top:12px;">
<span class="dim"># Default Login Credentials</span>
<?= $lang === 'th' ? 'ชื่อผู้ใช้' : 'Username' ?>: <span class="hl">admin</span>
<?= $lang === 'th' ? 'รหัสผ่าน' : 'Password' ?>: <span class="hl">admin123</span>

<span class="dim"># <?= $lang === 'th' ? '⚠️ เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!' : '⚠️ Change the default password after your first login!' ?></span>
<span class="dim"># <?= $lang === 'th' ? 'ไปที่ Setup Wizard → ตั้งรหัสผ่านใหม่ → กด Save & Launch' : 'Go to Setup Wizard → set a new password → click Save & Launch' ?></span>
                </div>
            </div>

            <!-- 6b: Admin Bar -->
            <div class="step-card" style="margin-bottom:20px;">
                <h3 style="font-size:16px;"><i class="fa fa-bars" style="color:var(--primary);"></i> <?= $lang === 'th' ? 'แถบ Admin Bar' : 'Admin Bar' ?></h3>
                <p style="font-size:14px; color:var(--gray); margin-bottom:12px;"><?= $lang === 'th' ? 'แถบด้านบนของเว็บไซต์แสดงลิงก์ด่วนสำหรับเจ้าของเว็บ' : 'The dark bar at the top of your website provides quick links for site owners.' ?></p>
                <ul>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? '<strong>Admin Panel</strong> — เปิดหน้า admin.php เพื่อจัดการสินค้า, API, Bookings' : '<strong>Admin Panel</strong> — opens admin.php for product, API, and booking management' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? '<strong>Sync Products</strong> — ซิงค์สินค้าจาก iACC แบบด่วน' : '<strong>Sync Products</strong> — quick sync products from iACC' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? '<strong>Settings</strong> — เปิด Setup Wizard เพื่อเปลี่ยนการตั้งค่า' : '<strong>Settings</strong> — open Setup Wizard to reconfigure' ?></li>
                    <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? '<strong>Login / Logout</strong> — แสดงปุ่ม Login (สีเขียว) หรือ Logout (สีแดง) ตามสถานะ' : '<strong>Login / Logout</strong> — shows Login (green) or Logout (red) based on session state' ?></li>
                </ul>
            </div>

            <!-- 6c: 4 Tabs -->
            <div class="step-card">
                <h3 style="font-size:16px;"><i class="fa fa-th-large" style="color:var(--primary);"></i> <?= $lang === 'th' ? 'แท็บจัดการ 4 แท็บ' : '4 Management Tabs' ?></h3>
                <p style="font-size:14px; color:var(--gray); margin-bottom:16px;"><?= $lang === 'th' ? 'หลังเข้าสู่ระบบ หน้า Admin แสดง Dashboard พร้อม 4 แท็บ:' : 'After login, the Admin dashboard shows 4 tabs:' ?></p>

                <div class="admin-features">
                    <div class="admin-feat">
                        <i class="fa fa-cube"></i>
                        <div>
                            <h4><?= $lang === 'th' ? '① Products' : '① Products' ?></h4>
                            <p><?= $lang === 'th' ? 'ดูสินค้าทั้งหมด กรองตามหมวด/สถานะ เปิด-ปิดสินค้าด้วย toggle switch — สินค้าที่ปิดจะซ่อนจากเว็บไซต์ทันที' : 'View all products, filter by category/status, toggle products on/off with switches — disabled products are hidden from visitors instantly' ?></p>
                        </div>
                    </div>
                    <div class="admin-feat">
                        <i class="fa fa-key"></i>
                        <div>
                            <h4><?= $lang === 'th' ? '② API Settings' : '② API Settings' ?></h4>
                            <p><?= $lang === 'th' ? 'อัพเดท API URL, Key, Secret — ทดสอบการเชื่อมต่อก่อนบันทึก — ดูแผนปัจจุบัน (Free/Professional/Enterprise) และ config ต่างๆ' : 'Update API URL, Key, Secret — test connection before saving — view current plan (Free/Professional/Enterprise) and site config' ?></p>
                        </div>
                    </div>
                    <div class="admin-feat">
                        <i class="fa fa-refresh"></i>
                        <div>
                            <h4><?= $lang === 'th' ? '③ Sync' : '③ Sync' ?></h4>
                            <p><?= $lang === 'th' ? 'ดึงข้อมูลล่าสุดจาก iACC API — จำนวนหมวดหมู่/สินค้า/วันที่ sync ล่าสุด — สถานะเปิด/ปิดของสินค้าจะถูกเก็บไว้ตลอดการ sync' : 'Pull latest data from iACC API — shows category/product counts and last sync date — active/inactive states are preserved during sync' ?></p>
                        </div>
                    </div>
                    <div class="admin-feat">
                        <i class="fa fa-calendar-check-o"></i>
                        <div>
                            <h4><?= $lang === 'th' ? '④ Bookings' : '④ Bookings' ?></h4>
                            <p><?= $lang === 'th' ? 'ดูรายการจองล่าสุด 10 รายการ — ชื่อลูกค้า, อีเมล, สินค้า, จำนวน, ยอดรวม, สถานะ — Booking ถูกสร้างอัตโนมัติเมื่อลูกค้าจองผ่านเว็บ' : 'View 10 most recent bookings — guest name, email, product, quantity, total, status — bookings are created automatically when customers book via your website' ?></p>
                        </div>
                    </div>
                </div>

                <div class="code-block" style="margin-top:16px;">
<span class="dim"># Admin Panel URL</span>
https://your-site.com/<span class="hl">admin.php</span>

<span class="dim"># Admin Panel Flow</span>
<span class="hl">admin.php</span> → <?= $lang === 'th' ? 'ยังไม่ login' : 'not logged in' ?>? → <span class="hl">admin-login.php</span> → <?= $lang === 'th' ? 'ใส่ username/password' : 'enter credentials' ?> → <span class="hl">admin.php</span>

<span class="dim"># <?= $lang === 'th' ? 'ไฟล์ที่เกี่ยวข้อง' : 'Related Files' ?></span>
admin-login.php    <span class="dim"># <?= $lang === 'th' ? 'หน้า login (bcrypt auth)' : 'Login page (bcrypt auth)' ?></span>
admin.php          <span class="dim"># <?= $lang === 'th' ? 'หน้า admin (4 tabs)' : 'Admin panel (4 tabs)' ?></span>
setup.php          <span class="dim"># <?= $lang === 'th' ? 'ตั้งค่า username/password ใน Step 3' : 'Set username/password in Step 3' ?></span>
config.php         <span class="dim"># <?= $lang === 'th' ? 'เก็บ hash ของรหัสผ่าน (bcrypt)' : 'Stores password hash (bcrypt)' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Links -->
    <div class="links">
        <a href="api-docs.php?lang=<?= $lang ?>"><i class="fa fa-book"></i> <?= $lang === 'th' ? 'API Documentation' : 'API Documentation' ?></a>
        <a href="template-howto.php?lang=<?= $lang ?>"><i class="fa fa-question-circle"></i> <?= $lang === 'th' ? 'คู่มือการติดตั้ง' : 'Hosting Guide' ?></a>
        <a href="templates/tour-company-demo/index.html" target="_blank"><i class="fa fa-eye"></i> <?= $lang === 'th' ? 'ดูตัวอย่าง' : 'Live Preview' ?></a>
    </div>

    <!-- CTA -->
    <div class="cta-section">
        <h3><?= $lang === 'th' ? 'พร้อมเริ่มต้นแล้วหรือยัง?' : 'Ready to Get Started?' ?></h3>
        <p><?= $lang === 'th' ? 'ดาวน์โหลดเทมเพลตฟรี แล้วเปิดเว็บไซต์ธุรกิจของคุณได้ทันที' : 'Download the free template and launch your business website today.' ?></p>
        <a href="template-download.php?template=tour-company-demo" class="btn btn-white">
            <i class="fa fa-download"></i> <?= $lang === 'th' ? 'ดาวน์โหลดเทมเพลต' : 'Download Template' ?>
        </a>
        <a href="login.php" class="btn btn-outline">
            <i class="fa fa-sign-in"></i> <?= $lang === 'th' ? 'เข้าสู่ระบบ iACC' : 'Sign In to iACC' ?>
        </a>
    </div>
</div>

</body>
</html>
