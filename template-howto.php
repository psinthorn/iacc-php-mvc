<?php
/**
 * iACC — Template How-To Guide
 * Public-facing how-to page covering installation, requirements,
 * and cPanel deployment instructions.
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
    <title><?= $lang === 'th' ? 'คู่มือการติดตั้ง' : 'Hosting & Setup Guide' ?> — iACC Template</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #1e293b; --gray: #64748b; --bg: #f8fafc; --success: #10b981; --warn: #f59e0b; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--dark); line-height: 1.7; background: var(--bg); }
        .top-bar { background: var(--dark); color: white; padding: 16px 20px; }
        .top-bar .inner { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; }
        .top-bar a:hover { color: white; }
        .top-bar h1 { font-size: 20px; font-weight: 700; }
        .top-bar h1 span { color: var(--primary); }
        .container { max-width: 900px; margin: 0 auto; padding: 48px 20px 80px; }
        h2 { font-size: 28px; font-weight: 700; margin-bottom: 12px; margin-top: 48px; }
        h2:first-of-type { margin-top: 0; }
        h3 { font-size: 20px; font-weight: 600; margin-bottom: 10px; margin-top: 28px; }
        p { color: var(--gray); font-size: 15px; margin-bottom: 14px; }
        .hero { text-align: center; margin-bottom: 48px; }
        .hero h2 { font-size: 36px; margin-top: 0; }
        .hero h2 em { font-style: normal; color: var(--primary); }
        .hero p { max-width: 600px; margin: 0 auto; font-size: 17px; }
        .card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .card h3 { margin-top: 0; }
        code { background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 13px; }
        pre { background: #0f172a; color: #e2e8f0; padding: 20px 24px; border-radius: 12px; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.7; margin-bottom: 16px; }
        pre .dim { color: #64748b; }
        pre .hl { color: #86efac; }
        pre .warn { color: #fbbf24; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { text-align: left; padding: 10px 14px; background: #f1f5f9; font-size: 13px; font-weight: 600; }
        table td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: var(--gray); }
        .note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; font-size: 14px; color: #1e40af; }
        .note i { margin-right: 8px; }
        .warn-box { background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; font-size: 14px; color: #92400e; }
        .warn-box i { margin-right: 8px; }
        .success-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; font-size: 14px; color: #065f46; }
        .success-box i { margin-right: 8px; }
        ul, ol { padding-left: 24px; margin-bottom: 16px; }
        li { padding: 4px 0; font-size: 14px; color: var(--gray); }
        .checklist { list-style: none; padding-left: 0; }
        .checklist li { padding: 8px 0; display: flex; align-items: flex-start; gap: 10px; }
        .checklist li i { color: var(--success); margin-top: 3px; flex-shrink: 0; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; }
        .badge-required { background: #fee2e2; color: #991b1b; }
        .badge-optional { background: #e0f2fe; color: #0369a1; }
        .toc { background: white; border-radius: 16px; padding: 24px 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 32px; border: 1px solid #e2e8f0; }
        .toc h3 { margin-top: 0; font-size: 16px; }
        .toc ol { margin-bottom: 0; }
        .toc a { color: var(--primary); text-decoration: none; font-size: 14px; }
        .toc a:hover { text-decoration: underline; }
        .links { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 40px; }
        .links a { color: var(--primary); font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .links a:hover { text-decoration: underline; }
        @media (max-width: 600px) {
            .hero h2 { font-size: 26px; }
        }
    </style>
</head>
<body>
<div class="top-bar">
    <div class="inner">
        <h1><span>iACC</span> <?= $lang === 'th' ? 'คู่มือการติดตั้ง' : 'Setup Guide' ?></h1>
        <a href="landing.php?lang=<?= $lang ?>"><i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับหน้าหลัก' : 'Back to Home' ?></a>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h2><?= $lang === 'th' ? 'คู่มือ<em>การติดตั้งเทมเพลต</em>' : '<em>Template</em> Setup Guide' ?></h2>
        <p><?= $lang === 'th' ? 'ทุกอย่างที่คุณต้องรู้ในการติดตั้งเทมเพลต iACC บน shared hosting, cPanel หรือ VPS' : 'Everything you need to know about deploying your iACC template on shared hosting, cPanel, or VPS.' ?></p>
    </div>

    <!-- Table of Contents -->
    <div class="toc">
        <h3><i class="fa fa-list"></i> <?= $lang === 'th' ? 'สารบัญ' : 'Table of Contents' ?></h3>
        <ol>
            <li><a href="#requirements"><?= $lang === 'th' ? 'ความต้องการของระบบ' : 'System Requirements' ?></a></li>
            <li><a href="#cpanel"><?= $lang === 'th' ? 'การติดตั้งบน cPanel' : 'cPanel Installation' ?></a></li>
            <li><a href="#permissions"><?= $lang === 'th' ? 'สิทธิ์ไฟล์' : 'File Permissions' ?></a></li>
            <li><a href="#config"><?= $lang === 'th' ? 'การตั้งค่า' : 'Configuration' ?></a></li>
            <li><a href="#ssl"><?= $lang === 'th' ? 'SSL Certificate' : 'SSL Certificate' ?></a></li>
            <li><a href="#troubleshooting"><?= $lang === 'th' ? 'แก้ปัญหา' : 'Troubleshooting' ?></a></li>
            <li><a href="#vps"><?= $lang === 'th' ? 'การติดตั้งบน VPS' : 'VPS / Docker Installation' ?></a></li>
        </ol>
    </div>

    <!-- Requirements -->
    <section id="requirements">
        <h2><i class="fa fa-server" style="color:var(--primary)"></i> <?= $lang === 'th' ? 'ความต้องการของระบบ' : 'System Requirements' ?></h2>

        <div class="card">
            <table>
                <tr>
                    <th><?= $lang === 'th' ? 'รายการ' : 'Requirement' ?></th>
                    <th><?= $lang === 'th' ? 'ขั้นต่ำ' : 'Minimum' ?></th>
                    <th><?= $lang === 'th' ? 'แนะนำ' : 'Recommended' ?></th>
                    <th></th>
                </tr>
                <tr>
                    <td>PHP</td>
                    <td>8.0</td>
                    <td>8.2+</td>
                    <td><span class="badge badge-required">Required</span></td>
                </tr>
                <tr>
                    <td>PHP SQLite Extension</td>
                    <td>pdo_sqlite</td>
                    <td>pdo_sqlite</td>
                    <td><span class="badge badge-required">Required</span></td>
                </tr>
                <tr>
                    <td>PHP cURL Extension</td>
                    <td>curl</td>
                    <td>curl</td>
                    <td><span class="badge badge-required">Required</span></td>
                </tr>
                <tr>
                    <td>PHP JSON Extension</td>
                    <td>json</td>
                    <td>json</td>
                    <td><span class="badge badge-required">Required</span></td>
                </tr>
                <tr>
                    <td>MySQL / MariaDB</td>
                    <td colspan="2"><?= $lang === 'th' ? 'ไม่จำเป็น — เทมเพลตใช้ SQLite' : 'Not needed — template uses SQLite' ?></td>
                    <td><span class="badge badge-optional">Not Needed</span></td>
                </tr>
                <tr>
                    <td><?= $lang === 'th' ? 'พื้นที่ดิสก์' : 'Disk Space' ?></td>
                    <td>10 MB</td>
                    <td>50 MB</td>
                    <td></td>
                </tr>
            </table>

            <div class="note">
                <i class="fa fa-info-circle"></i>
                <strong><?= $lang === 'th' ? 'ไม่ต้องใช้ MySQL:' : 'No MySQL Needed:' ?></strong>
                <?= $lang === 'th' ? 'เทมเพลตใช้ SQLite ในการเก็บข้อมูลสินค้าที่ซิงค์จาก iACC API ทำให้ติดตั้งง่ายบน shared hosting ทั่วไป' : 'The template uses SQLite to cache products synced from iACC API. This means no database setup is required on your hosting.' ?>
            </div>
        </div>
    </section>

    <!-- cPanel Installation -->
    <section id="cpanel">
        <h2><i class="fa fa-dashboard" style="color:var(--primary)"></i> <?= $lang === 'th' ? 'การติดตั้งบน cPanel' : 'cPanel Installation' ?></h2>
        <p><?= $lang === 'th' ? 'ขั้นตอนการติดตั้งเทมเพลตบน cPanel shared hosting' : 'Step-by-step guide to deploy the template on cPanel shared hosting.' ?></p>

        <div class="card">
            <h3><?= $lang === 'th' ? 'ขั้นตอนที่ 1: ตรวจสอบ PHP Version' : 'Step 1: Check PHP Version' ?></h3>
            <p><?= $lang === 'th' ? 'เข้าสู่ cPanel → MultiPHP Manager → ตรวจสอบว่า domain ใช้ PHP 8.0 ขึ้นไป' : 'Login to cPanel → MultiPHP Manager → Ensure your domain uses PHP 8.0 or higher.' ?></p>
<pre><span class="dim"># Check via SSH (if available)</span>
php -v
<span class="hl">PHP 8.2.27 (cli)</span>

<span class="dim"># Check required extensions</span>
php -m | grep -E "sqlite|curl|json"
<span class="hl">curl</span>
<span class="hl">json</span>
<span class="hl">pdo_sqlite</span></pre>

            <h3><?= $lang === 'th' ? 'ขั้นตอนที่ 2: อัพโหลดไฟล์' : 'Step 2: Upload Files' ?></h3>
            <p><?= $lang === 'th' ? 'ใช้ File Manager ใน cPanel หรือ FTP client' : 'Use cPanel File Manager or an FTP client.' ?></p>
            <ul class="checklist">
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เปิด cPanel → File Manager' : 'Open cPanel → File Manager' ?></li>
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ไปที่ <code>public_html</code> (หรือ subdomain/subfolder ที่ต้องการ)' : 'Navigate to <code>public_html</code> (or your subdomain/subfolder)' ?></li>
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'อัพโหลดไฟล์ ZIP แล้วแตกไฟล์' : 'Upload the ZIP file and extract it' ?></li>
            </ul>

<pre><span class="dim"># Option A: Upload to root domain (yourdomain.com)</span>
public_html/
  index.php
  setup.php
  config.php
  ...

<span class="dim"># Option B: Upload to subfolder (yourdomain.com/tours)</span>
public_html/tours/
  index.php
  setup.php
  config.php
  ...</pre>

            <h3><?= $lang === 'th' ? 'ขั้นตอนที่ 3: ตั้งค่า PHP Extensions' : 'Step 3: Enable PHP Extensions' ?></h3>
            <p><?= $lang === 'th' ? 'ถ้า pdo_sqlite ไม่พร้อมใช้งาน ให้เปิดใน cPanel' : 'If pdo_sqlite is not enabled, enable it in cPanel.' ?></p>
            <ul class="checklist">
                <li><i class="fa fa-check-circle"></i> cPanel → Select PHP Version (<?= $lang === 'th' ? 'หรือ' : 'or' ?> MultiPHP INI Editor)</li>
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'เปิดเครื่องหมาย ✓ ที่ <code>pdo_sqlite</code> และ <code>curl</code>' : 'Check the boxes for <code>pdo_sqlite</code> and <code>curl</code>' ?></li>
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'คลิก Save' : 'Click Save' ?></li>
            </ul>

            <h3><?= $lang === 'th' ? 'ขั้นตอนที่ 4: ตรวจสอบ .htaccess' : 'Step 4: Verify .htaccess' ?></h3>
            <p><?= $lang === 'th' ? 'เทมเพลตมาพร้อม .htaccess ที่ปกป้องไฟล์สำคัญ ตรวจสอบว่าทำงานถูกต้อง' : 'The template includes .htaccess security rules. Verify they work correctly.' ?></p>
<pre><span class="dim"># The .htaccess file protects:</span>
config.php     <span class="warn"># API credentials — MUST be protected</span>
data/          <span class="warn"># SQLite database — MUST be protected</span>
includes/      <span class="warn"># PHP class files</span></pre>

            <div class="warn-box">
                <i class="fa fa-exclamation-triangle"></i>
                <strong><?= $lang === 'th' ? 'สำคัญ:' : 'Important:' ?></strong>
                <?= $lang === 'th' ? 'ตรวจสอบว่า <code>config.php</code> และโฟลเดอร์ <code>data/</code> ไม่สามารถเข้าถึงได้จาก browser โดยตรง ลองเปิด <code>yourdomain.com/config.php</code> — ต้องแสดง 403 Forbidden' : 'Verify that <code>config.php</code> and <code>data/</code> folder are NOT directly accessible from browser. Try opening <code>yourdomain.com/config.php</code> — it should show 403 Forbidden.' ?>
            </div>

            <h3><?= $lang === 'th' ? 'ขั้นตอนที่ 5: เปิด Setup Wizard' : 'Step 5: Run Setup Wizard' ?></h3>
            <p><?= $lang === 'th' ? 'เปิด browser ไปที่ URL ของเว็บไซต์' : 'Open your browser and navigate to your website URL.' ?></p>
<pre><span class="hl">https://yourdomain.com/setup.php</span>
<span class="dim"># or if in subfolder:</span>
<span class="hl">https://yourdomain.com/tours/setup.php</span></pre>
        </div>
    </section>

    <!-- File Permissions -->
    <section id="permissions">
        <h2><i class="fa fa-lock" style="color:var(--primary)"></i> <?= $lang === 'th' ? 'สิทธิ์ไฟล์' : 'File Permissions' ?></h2>

        <div class="card">
            <p><?= $lang === 'th' ? 'ตั้งค่าสิทธิ์ไฟล์ผ่าน cPanel File Manager หรือ SSH:' : 'Set file permissions via cPanel File Manager or SSH:' ?></p>
            <table>
                <tr><th><?= $lang === 'th' ? 'ไฟล์/โฟลเดอร์' : 'File/Folder' ?></th><th><?= $lang === 'th' ? 'สิทธิ์' : 'Permission' ?></th><th><?= $lang === 'th' ? 'หมายเหตุ' : 'Notes' ?></th></tr>
                <tr><td><code>config.php</code></td><td><code>644</code></td><td><?= $lang === 'th' ? 'ต้องเขียนได้โดย PHP (setup wizard)' : 'Must be writable by PHP (setup wizard)' ?></td></tr>
                <tr><td><code>data/</code></td><td><code>755</code></td><td><?= $lang === 'th' ? 'โฟลเดอร์ SQLite database' : 'SQLite database folder' ?></td></tr>
                <tr><td><code>data/template.db</code></td><td><code>644</code></td><td><?= $lang === 'th' ? 'สร้างอัตโนมัติตอน sync' : 'Auto-created on first sync' ?></td></tr>
                <tr><td><?= $lang === 'th' ? 'ไฟล์ PHP อื่นๆ' : 'Other PHP files' ?></td><td><code>644</code></td><td><?= $lang === 'th' ? 'อ่านอย่างเดียว' : 'Read-only' ?></td></tr>
                <tr><td><?= $lang === 'th' ? 'โฟลเดอร์อื่นๆ' : 'Other folders' ?></td><td><code>755</code></td><td></td></tr>
            </table>

<pre><span class="dim"># Set permissions via SSH</span>
chmod 644 config.php
chmod 755 data/
chmod -R 644 *.php
chmod 755 includes/ css/</pre>
        </div>
    </section>

    <!-- Configuration -->
    <section id="config">
        <h2><i class="fa fa-cog" style="color:var(--primary)"></i> <?= $lang === 'th' ? 'การตั้งค่า' : 'Configuration' ?></h2>

        <div class="card">
            <h3><?= $lang === 'th' ? 'API URL สำหรับ cPanel Hosting' : 'API URL for cPanel Hosting' ?></h3>
            <p><?= $lang === 'th' ? 'เมื่อเทมเพลตอยู่บน hosting อื่น (ไม่ใช่ Docker) ใช้ URL จริงของ iACC:' : 'When the template is on separate hosting (not Docker), use your actual iACC URL:' ?></p>

<pre><span class="dim"># In Setup Wizard → API URL field:</span>
<span class="hl">https://iacc.f2.co.th</span>     <span class="dim"># Production URL</span>
<span class="hl">https://your-iacc.com</span>      <span class="dim"># Your own iACC domain</span>

<span class="dim"># Do NOT use:</span>
<span class="warn">http://localhost</span>           <span class="dim"># Only works if template is on same server</span>
<span class="warn">http://iacc_nginx</span>          <span class="dim"># Docker internal name — won't work outside Docker</span></pre>

            <div class="note">
                <i class="fa fa-info-circle"></i>
                <?= $lang === 'th' ? '<strong>สำคัญ:</strong> ถ้า iACC ของคุณอยู่บน Docker ที่ localhost และเทมเพลตอยู่บน cPanel hosting อื่น คุณต้องใช้ URL สาธารณะ (public domain) ของ iACC ไม่ใช่ localhost' : '<strong>Important:</strong> If your iACC is running on Docker at localhost but your template is on a different cPanel hosting, you must use the public URL (domain) of your iACC installation, not localhost.' ?>
            </div>
        </div>
    </section>

    <!-- SSL -->
    <section id="ssl">
        <h2><i class="fa fa-shield" style="color:var(--primary)"></i> SSL Certificate</h2>

        <div class="card">
            <p><?= $lang === 'th' ? 'แนะนำให้ใช้ HTTPS สำหรับเว็บไซต์ที่รับข้อมูลจองลูกค้า' : 'SSL is recommended for any website that handles customer booking data.' ?></p>
            <ul class="checklist">
                <li><i class="fa fa-check-circle"></i> cPanel → SSL/TLS → <?= $lang === 'th' ? 'ติดตั้ง Let\'s Encrypt (ฟรี)' : 'Install Let\'s Encrypt (free)' ?></li>
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'หรือใช้ AutoSSL ที่มาพร้อม cPanel' : 'Or use the built-in AutoSSL feature' ?></li>
                <li><i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'ตรวจสอบว่า API calls ใช้ HTTPS (ถ้า iACC ใช้ SSL)' : 'Ensure API calls use HTTPS (if your iACC uses SSL)' ?></li>
            </ul>
        </div>
    </section>

    <!-- Troubleshooting -->
    <section id="troubleshooting">
        <h2><i class="fa fa-wrench" style="color:var(--primary)"></i> <?= $lang === 'th' ? 'แก้ปัญหา' : 'Troubleshooting' ?></h2>

        <div class="card">
            <h3><?= $lang === 'th' ? '"Connection failed" ตอน Test Connection' : '"Connection failed" on Test Connection' ?></h3>
            <ul>
                <li><?= $lang === 'th' ? 'ตรวจสอบ URL ว่าถูกต้อง (ต้องเข้าถึงได้จาก server ที่เทมเพลตอยู่)' : 'Verify the URL is correct and accessible from your template server' ?></li>
                <li><?= $lang === 'th' ? 'ตรวจสอบ API Key และ Secret ว่าถูกต้อง' : 'Check that API Key and Secret are correct' ?></li>
                <li><?= $lang === 'th' ? 'ตรวจสอบว่า cURL extension เปิดใช้งานแล้ว' : 'Verify cURL PHP extension is enabled' ?></li>
                <li><?= $lang === 'th' ? 'ถ้า iACC ใช้ SSL ให้ตรวจสอบว่า server รองรับ SSL verification' : 'If iACC uses SSL, ensure your server supports SSL certificate verification' ?></li>
            </ul>

            <h3><?= $lang === 'th' ? '"Failed to write config.php"' : '"Failed to write config.php"' ?></h3>
            <ul>
                <li><?= $lang === 'th' ? 'ตรวจสอบสิทธิ์ไฟล์ config.php (ต้อง 644 หรือ 666)' : 'Check config.php file permissions (must be 644 or 666)' ?></li>
                <li><?= $lang === 'th' ? 'ตรวจสอบว่า PHP มีสิทธิ์เขียนไฟล์ในโฟลเดอร์นั้น' : 'Ensure PHP has write permission to the folder' ?></li>
            </ul>

            <h3><?= $lang === 'th' ? '"No products" หลัง sync' : '"No products" after sync' ?></h3>
            <ul>
                <li><?= $lang === 'th' ? 'ตรวจสอบว่ามีสินค้า (models) ใน iACC account ของคุณ' : 'Verify you have products (models) in your iACC account' ?></li>
                <li><?= $lang === 'th' ? 'ตรวจสอบว่าโฟลเดอร์ <code>data/</code> มีสิทธิ์เขียน (755)' : 'Ensure the <code>data/</code> folder is writable (755)' ?></li>
                <li><?= $lang === 'th' ? 'ลองลบ <code>data/template.db</code> แล้ว sync ใหม่' : 'Try deleting <code>data/template.db</code> and sync again' ?></li>
            </ul>

            <h3><?= $lang === 'th' ? 'หน้าเว็บขาว (blank page)' : 'Blank page / White screen' ?></h3>
            <ul>
                <li><?= $lang === 'th' ? 'ตรวจสอบ PHP version (ต้อง 8.0+)' : 'Check PHP version (must be 8.0+)' ?></li>
                <li><?= $lang === 'th' ? 'เปิด error display ชั่วคราว: เพิ่ม <code>ini_set("display_errors", 1)</code> ที่บรรทัดแรกของ index.php' : 'Temporarily enable error display: add <code>ini_set("display_errors", 1)</code> at the top of index.php' ?></li>
                <li><?= $lang === 'th' ? 'ตรวจสอบ error log ใน cPanel → Errors' : 'Check error log in cPanel → Errors' ?></li>
            </ul>
        </div>
    </section>

    <!-- VPS / Docker -->
    <section id="vps">
        <h2><i class="fa fa-cloud" style="color:var(--primary)"></i> <?= $lang === 'th' ? 'การติดตั้งบน VPS / Docker' : 'VPS / Docker Installation' ?></h2>

        <div class="card">
            <p><?= $lang === 'th' ? 'ถ้าคุณใช้ VPS หรือ Docker สามารถติดตั้งเทมเพลตแบบ standalone ได้:' : 'If you use a VPS or Docker, you can deploy the template as a standalone service:' ?></p>

<pre><span class="dim"># Quick Docker setup</span>
docker run -d --name my-tour-site \
  -p 8080:80 \
  -v ./tour-company-demo:/var/www/html \
  php:8.2-apache

<span class="dim"># Enable required extensions</span>
docker exec my-tour-site docker-php-ext-install pdo_sqlite
docker exec my-tour-site apache2ctl restart</pre>

<pre><span class="dim"># Or use Nginx + PHP-FPM</span>
server {
    listen 80;
    server_name tours.yourdomain.com;
    root /var/www/tour-company-demo;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass php:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    <span class="dim"># Block access to sensitive files</span>
    location ~ (config\.php|data/|includes/) {
        deny all;
    }
}</pre>
        </div>
    </section>

    <!-- Links -->
    <div class="links">
        <a href="api-docs.php?lang=<?= $lang ?>"><i class="fa fa-book"></i> API Documentation</a>
        <a href="template-demo.php?lang=<?= $lang ?>"><i class="fa fa-play-circle"></i> <?= $lang === 'th' ? 'สาธิตการตั้งค่า' : 'Setup Demo' ?></a>
        <a href="landing.php?lang=<?= $lang ?>#templates"><i class="fa fa-download"></i> <?= $lang === 'th' ? 'ดาวน์โหลดเทมเพลต' : 'Download Template' ?></a>
    </div>
</div>

</body>
</html>
