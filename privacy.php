<?php
/**
 * Privacy Policy Page
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
    <title><?= $lang === 'th' ? 'นโยบายความเป็นส่วนตัว' : 'Privacy Policy' ?> - iACC</title>
    
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
        
        .highlight-box {
            background: #f8f4fb;
            border-left: 4px solid var(--primary);
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
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
        <h1><?= $lang === 'th' ? 'นโยบายความเป็นส่วนตัว' : 'Privacy Policy' ?></h1>
        <p><?= $lang === 'th' ? 'อัปเดตล่าสุด: 3 มกราคม 2026' : 'Last updated: January 3, 2026' ?></p>
    </section>
    
    <div class="content">
        <div class="highlight-box">
            <p style="margin: 0;"><strong><?= $lang === 'th' ? 'สรุป:' : 'Summary:' ?></strong> <?= $lang === 'th' ? 'เราให้ความสำคัญกับความเป็นส่วนตัวของคุณ เราเก็บรวบรวมเฉพาะข้อมูลที่จำเป็นเพื่อให้บริการของเรา และเราไม่ขายข้อมูลของคุณให้บุคคลที่สาม' : 'We value your privacy. We only collect information necessary to provide our services, and we never sell your data to third parties.' ?></p>
        </div>
        
        <h2>1. <?= $lang === 'th' ? 'ข้อมูลที่เราเก็บรวบรวม' : 'Information We Collect' ?></h2>
        <p><?= $lang === 'th' ? 'เราเก็บรวบรวมข้อมูลที่คุณให้โดยตรงเมื่อคุณ:' : 'We collect information you provide directly when you:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'สร้างบัญชี (อีเมล, ชื่อ, รหัสผ่าน)' : 'Create an account (email, name, password)' ?></li>
            <li><?= $lang === 'th' ? 'ตั้งค่าโปรไฟล์บริษัทของคุณ' : 'Set up your company profile' ?></li>
            <li><?= $lang === 'th' ? 'สร้างใบแจ้งหนี้และบันทึกทางการเงิน' : 'Create invoices and financial records' ?></li>
            <li><?= $lang === 'th' ? 'ติดต่อฝ่ายสนับสนุนของเรา' : 'Contact our support team' ?></li>
        </ul>
        
        <h2>2. <?= $lang === 'th' ? 'เราใช้ข้อมูลของคุณอย่างไร' : 'How We Use Your Information' ?></h2>
        <p><?= $lang === 'th' ? 'เราใช้ข้อมูลที่เก็บรวบรวมเพื่อ:' : 'We use collected information to:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'ให้และบำรุงรักษาบริการ' : 'Provide and maintain the Service' ?></li>
            <li><?= $lang === 'th' ? 'ประมวลผลธุรกรรมและส่งการแจ้งเตือนที่เกี่ยวข้อง' : 'Process transactions and send related notifications' ?></li>
            <li><?= $lang === 'th' ? 'ตอบกลับคำถามและให้การสนับสนุน' : 'Respond to inquiries and provide support' ?></li>
            <li><?= $lang === 'th' ? 'ส่งการอัปเดตและการสื่อสารทางการตลาด (ด้วยความยินยอมของคุณ)' : 'Send updates and marketing communications (with your consent)' ?></li>
            <li><?= $lang === 'th' ? 'ปรับปรุงบริการของเรา' : 'Improve our services' ?></li>
        </ul>
        
        <h2>3. <?= $lang === 'th' ? 'ความปลอดภัยของข้อมูล' : 'Data Security' ?></h2>
        <p><?= $lang === 'th' ? 'เราใช้มาตรการรักษาความปลอดภัยมาตรฐานอุตสาหกรรม ได้แก่:' : 'We implement industry-standard security measures including:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'การเข้ารหัส SSL สำหรับข้อมูลที่ส่ง' : 'SSL encryption for data in transit' ?></li>
            <li><?= $lang === 'th' ? 'การแฮชรหัสผ่าน (bcrypt)' : 'Password hashing (bcrypt)' ?></li>
            <li><?= $lang === 'th' ? 'การตรวจสอบความปลอดภัยเป็นประจำ' : 'Regular security audits' ?></li>
            <li><?= $lang === 'th' ? 'การควบคุมการเข้าถึงและการตรวจสอบสิทธิ์' : 'Access controls and authentication' ?></li>
            <li><?= $lang === 'th' ? 'การสำรองข้อมูลอัตโนมัติ' : 'Automated data backups' ?></li>
        </ul>
        
        <h2>4. <?= $lang === 'th' ? 'การแบ่งปันข้อมูล' : 'Data Sharing' ?></h2>
        <p><?= $lang === 'th' ? 'เราไม่ขาย ให้เช่า หรือแบ่งปันข้อมูลส่วนบุคคลของคุณกับบุคคลที่สาม ยกเว้น:' : 'We do not sell, rent, or share your personal information with third parties except:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'เมื่อได้รับความยินยอมจากคุณ' : 'With your consent' ?></li>
            <li><?= $lang === 'th' ? 'กับผู้ให้บริการที่ช่วยเราดำเนินงาน' : 'With service providers who help us operate' ?></li>
            <li><?= $lang === 'th' ? 'เมื่อกฎหมายบังคับ' : 'When required by law' ?></li>
            <li><?= $lang === 'th' ? 'เพื่อปกป้องสิทธิ์และความปลอดภัยของเรา' : 'To protect our rights and safety' ?></li>
        </ul>
        
        <h2>5. <?= $lang === 'th' ? 'การเก็บรักษาข้อมูล' : 'Data Retention' ?></h2>
        <p><?= $lang === 'th' ? 'เราเก็บรักษาข้อมูลของคุณตราบเท่าที่บัญชีของคุณยังใช้งานอยู่หรือตามที่จำเป็นเพื่อให้บริการ คุณสามารถขอให้ลบข้อมูลของคุณได้ตลอดเวลา' : 'We retain your data as long as your account is active or as needed to provide services. You can request deletion of your data at any time.' ?></p>
        
        <h2>6. <?= $lang === 'th' ? 'สิทธิ์ของคุณ' : 'Your Rights' ?></h2>
        <p><?= $lang === 'th' ? 'คุณมีสิทธิ์ที่จะ:' : 'You have the right to:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'เข้าถึงข้อมูลส่วนบุคคลของคุณ' : 'Access your personal data' ?></li>
            <li><?= $lang === 'th' ? 'แก้ไขข้อมูลที่ไม่ถูกต้อง' : 'Correct inaccurate data' ?></li>
            <li><?= $lang === 'th' ? 'ขอให้ลบข้อมูลของคุณ' : 'Request deletion of your data' ?></li>
            <li><?= $lang === 'th' ? 'ส่งออกข้อมูลของคุณ' : 'Export your data' ?></li>
            <li><?= $lang === 'th' ? 'ยกเลิกการรับการสื่อสารทางการตลาด' : 'Opt-out of marketing communications' ?></li>
        </ul>
        
        <h2>7. <?= $lang === 'th' ? 'คุกกี้และการติดตาม' : 'Cookies and Tracking' ?></h2>
        <p><?= $lang === 'th' ? 'เราใช้คุกกี้เพื่อ:' : 'We use cookies to:' ?></p>
        <ul>
            <li><?= $lang === 'th' ? 'รักษาสถานะการเข้าสู่ระบบของคุณ' : 'Maintain your login session' ?></li>
            <li><?= $lang === 'th' ? 'จดจำการตั้งค่าของคุณ' : 'Remember your preferences' ?></li>
            <li><?= $lang === 'th' ? 'วิเคราะห์รูปแบบการใช้งาน' : 'Analyze usage patterns' ?></li>
        </ul>
        <p><?= $lang === 'th' ? 'คุณสามารถควบคุมคุกกี้ผ่านการตั้งค่าเบราว์เซอร์ของคุณ' : 'You can control cookies through your browser settings.' ?></p>
        
        <h2>8. <?= $lang === 'th' ? 'ความเป็นส่วนตัวของเด็ก' : 'Children\'s Privacy' ?></h2>
        <p><?= $lang === 'th' ? 'บริการของเราไม่ได้มีไว้สำหรับเด็กอายุต่ำกว่า 16 ปี เราไม่ได้เก็บรวบรวมข้อมูลจากเด็กโดยเจตนา' : 'Our Service is not intended for children under 16. We do not knowingly collect information from children.' ?></p>
        
        <h2>9. <?= $lang === 'th' ? 'การถ่ายโอนระหว่างประเทศ' : 'International Transfers' ?></h2>
        <p><?= $lang === 'th' ? 'ข้อมูลของคุณอาจถูกถ่ายโอนและประมวลผลในประเทศอื่นที่กฎหมายคุ้มครองข้อมูลอาจแตกต่างออกไป เรารับประกันการป้องกันที่เหมาะสมสำหรับการถ่ายโอนดังกล่าว' : 'Your data may be transferred to and processed in countries with different data protection laws. We ensure appropriate safeguards for such transfers.' ?></p>
        
        <h2>10. <?= $lang === 'th' ? 'การเปลี่ยนแปลงนโยบายนี้' : 'Changes to This Policy' ?></h2>
        <p><?= $lang === 'th' ? 'เราอาจอัปเดตนโยบายความเป็นส่วนตัวนี้เป็นครั้งคราว เราจะแจ้งให้คุณทราบถึงการเปลี่ยนแปลงที่สำคัญผ่านทางอีเมลหรือการแจ้งเตือนบนบริการ' : 'We may update this Privacy Policy from time to time. We will notify you of significant changes via email or a notice on the Service.' ?></p>
        
        <h2>11. <?= $lang === 'th' ? 'ติดต่อเรา' : 'Contact Us' ?></h2>
        <p><?= $lang === 'th' ? 'หากคุณมีคำถามเกี่ยวกับนโยบายความเป็นส่วนตัวนี้ กรุณาติดต่อ:' : 'If you have questions about this Privacy Policy, please contact:' ?></p>
        <p><strong>Email:</strong> privacy@iacc.com</p>
        <p><strong><?= $lang === 'th' ? 'โทรศัพท์' : 'Phone' ?>:</strong> +66 2 123 4567</p>
        <p><strong><?= $lang === 'th' ? 'ที่อยู่' : 'Address' ?>:</strong> <?= $lang === 'th' ? 'กรุงเทพมหานคร, ประเทศไทย' : 'Bangkok, Thailand' ?></p>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
