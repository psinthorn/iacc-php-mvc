<?php
/**
 * Terms of Service Page
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
    <title><?= $lang === 'th' ? 'ข้อกำหนดการใช้งาน' : 'Terms of Service' ?> - iACC</title>
    
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
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: <?= $lang === 'th' ? "'Sarabun', " : "" ?>'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.8;
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
        .hero p { opacity: 0.9; }
        
        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .content h2 {
            font-size: 24px;
            color: var(--primary);
            margin: 40px 0 15px;
        }
        
        .content h2:first-of-type { margin-top: 0; }
        
        .content p, .content li {
            color: #555;
            margin-bottom: 15px;
        }
        
        .content ul {
            margin-left: 25px;
            margin-bottom: 20px;
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
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="landing.php" class="nav-brand">iACC</a>
            <ul class="nav-links">
                <li><a href="landing.php"><?= $lang === 'th' ? 'หน้าแรก' : 'Home' ?></a></li>
                <li><a href="about.php"><?= $lang === 'th' ? 'เกี่ยวกับ' : 'About' ?></a></li>
                <li><a href="contact.php"><?= __('nav_contact') ?></a></li>
                <li><a href="login.php"><?= __('nav_sign_in') ?></a></li>
            </ul>
        </div>
    </nav>
    
    <section class="hero">
        <h1><?= $lang === 'th' ? 'ข้อกำหนดการใช้งาน' : 'Terms of Service' ?></h1>
        <p><?= $lang === 'th' ? 'อัปเดตล่าสุด: 3 มกราคม 2026' : 'Last updated: January 3, 2026' ?></p>
    </section>
    
    <div class="content">
        <h2>1. <?= $lang === 'th' ? 'การยอมรับข้อกำหนด' : 'Acceptance of Terms' ?></h2>
        <p><?= $lang === 'th' ? 'โดยการเข้าถึงและใช้งาน iACC ("บริการ") คุณยอมรับและตกลงที่จะผูกพันตามข้อกำหนดและเงื่อนไขเหล่านี้ หากคุณไม่เห็นด้วยกับส่วนใดส่วนหนึ่งของข้อกำหนดเหล่านี้ คุณไม่ควรเข้าถึงบริการ' : 'By accessing and using iACC ("Service"), you accept and agree to be bound by these Terms and Conditions. If you disagree with any part of these terms, you should not access the Service.' ?></p>
        
        <h2>2. <?= $lang === 'th' ? 'คำอธิบายบริการ' : 'Description of Service' ?></h2>
        <p><?= $lang === 'th' ? 'iACC เป็นระบบซอฟต์แวร์การจัดการบัญชีที่ให้บริการ:' : 'iACC is an accounting management software system that provides:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'การจัดการใบแจ้งหนี้และใบเสร็จ' : 'Invoice and receipt management' ?></li>
            <li><?= $lang === 'th' ? 'การประมวลผลการชำระเงิน' : 'Payment processing' ?></li>
            <li><?= $lang === 'th' ? 'การรายงานทางการเงิน' : 'Financial reporting' ?></li>
            <li><?= $lang === 'th' ? 'การจัดการผู้ใช้' : 'User management' ?></li>
            <li><?= $lang === 'th' ? 'รองรับหลายบริษัท' : 'Multi-company support' ?></li>
        </ul>
        
        <h2>3. <?= $lang === 'th' ? 'บัญชีผู้ใช้' : 'User Accounts' ?></h2>
        <p><?= $lang === 'th' ? 'เมื่อคุณสร้างบัญชีกับเรา คุณต้อง:' : 'When you create an account with us, you must:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'ให้ข้อมูลที่ถูกต้อง สมบูรณ์ และเป็นปัจจุบัน' : 'Provide accurate, complete, and current information' ?></li>
            <li><?= $lang === 'th' ? 'รักษาความปลอดภัยของรหัสผ่านและบัญชีของคุณ' : 'Maintain the security of your password and account' ?></li>
            <li><?= $lang === 'th' ? 'แจ้งเราทันทีเมื่อมีการเข้าถึงโดยไม่ได้รับอนุญาต' : 'Notify us immediately of any unauthorized access' ?></li>
            <li><?= $lang === 'th' ? 'รับผิดชอบต่อกิจกรรมทั้งหมดภายใต้บัญชีของคุณ' : 'Accept responsibility for all activities under your account' ?></li>
        </ul>
        
        <h2>4. <?= $lang === 'th' ? 'การใช้งานที่ยอมรับได้' : 'Acceptable Use' ?></h2>
        <p><?= $lang === 'th' ? 'คุณตกลงที่จะไม่:' : 'You agree not to:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'ใช้บริการเพื่อวัตถุประสงค์ที่ผิดกฎหมาย' : 'Use the Service for any unlawful purpose' ?></li>
            <li><?= $lang === 'th' ? 'พยายามเข้าถึงบัญชีหรือระบบอื่นโดยไม่ได้รับอนุญาต' : 'Attempt to gain unauthorized access to other accounts or systems' ?></li>
            <li><?= $lang === 'th' ? 'ส่งมัลแวร์หรือโค้ดที่เป็นอันตราย' : 'Transmit malware or harmful code' ?></li>
            <li><?= $lang === 'th' ? 'รบกวนการทำงานที่ถูกต้องของบริการ' : 'Interfere with the proper functioning of the Service' ?></li>
        </ul>
        
        <h2>5. <?= $lang === 'th' ? 'การชำระเงินและการเรียกเก็บเงิน' : 'Payment and Billing' ?></h2>
        <p><?= $lang === 'th' ? 'สำหรับบริการแบบชำระเงิน คุณตกลงที่จะชำระค่าธรรมเนียมทั้งหมดตามราคาที่มีผลบังคับใช้ในขณะนั้น การชำระเงินไม่สามารถขอคืนได้ยกเว้นตามที่ระบุไว้ในนโยบายการคืนเงินของเรา' : 'For paid services, you agree to pay all fees according to the pricing in effect at the time. Payments are non-refundable except as specified in our refund policy.' ?></p>
        
        <h2>6. <?= $lang === 'th' ? 'ทรัพย์สินทางปัญญา' : 'Intellectual Property' ?></h2>
        <p><?= $lang === 'th' ? 'บริการและเนื้อหาดั้งเดิม คุณสมบัติ และฟังก์ชันการทำงานเป็นและจะยังคงเป็นทรัพย์สินพิเศษของ iACC และผู้ให้ใบอนุญาต' : 'The Service and its original content, features, and functionality are and will remain the exclusive property of iACC and its licensors.' ?></p>
        
        <h2>7. <?= $lang === 'th' ? 'การยกเลิก' : 'Termination' ?></h2>
        <p><?= $lang === 'th' ? 'เราอาจยกเลิกหรือระงับบัญชีของคุณได้ทันทีโดยไม่ต้องแจ้งให้ทราบล่วงหน้า สำหรับพฤติกรรมที่เราเชื่อว่าละเมิดข้อกำหนดเหล่านี้หรือเป็นอันตรายต่อผู้ใช้อื่น บริการ หรือบุคคลที่สาม' : 'We may terminate or suspend your account immediately, without prior notice, for conduct that we believe violates these Terms or is harmful to other users, the Service, or third parties.' ?></p>
        
        <h2>8. <?= $lang === 'th' ? 'ข้อจำกัดความรับผิด' : 'Limitation of Liability' ?></h2>
        <p><?= $lang === 'th' ? 'ไม่ว่าในกรณีใด iACC ผู้อำนวยการ พนักงาน หุ้นส่วน ตัวแทน ซัพพลายเออร์ หรือบริษัทในเครือจะไม่รับผิดต่อความเสียหายทางอ้อม โดยบังเอิญ พิเศษ เป็นผลสืบเนื่อง หรือเพื่อการลงโทษ' : 'In no event shall iACC, its directors, employees, partners, agents, suppliers, or affiliates be liable for any indirect, incidental, special, consequential, or punitive damages.' ?></p>
        
        <h2>9. <?= $lang === 'th' ? 'การเปลี่ยนแปลงข้อกำหนด' : 'Changes to Terms' ?></h2>
        <p><?= $lang === 'th' ? 'เราขอสงวนสิทธิ์ในการแก้ไขหรือแทนที่ข้อกำหนดเหล่านี้ได้ตลอดเวลา โดยการโพสต์ข้อกำหนดใหม่บนหน้านี้ การใช้บริการต่อหลังจากมีการเปลี่ยนแปลงถือเป็นการยอมรับข้อกำหนดใหม่' : 'We reserve the right to modify or replace these Terms at any time by posting new terms on this page. Your continued use of the Service after changes constitutes acceptance of the new terms.' ?></p>
        
        <h2>10. <?= $lang === 'th' ? 'ติดต่อเรา' : 'Contact Us' ?></h2>
        <p><?= $lang === 'th' ? 'หากคุณมีคำถามเกี่ยวกับข้อกำหนดเหล่านี้ กรุณาติดต่อเราที่:' : 'If you have any questions about these Terms, please contact us at:' ?></p>
        <p><strong>Email:</strong> legal@iacc.com</p>
        <p><strong><?= $lang === 'th' ? 'โทรศัพท์' : 'Phone' ?>:</strong> +66 2 123 4567</p>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
