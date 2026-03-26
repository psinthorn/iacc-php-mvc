<?php
// Error reporting settings
ini_set('display_errors', 1); // Show errors in browser for debug
ini_set('log_errors', 1);     // Enable error logging
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log'); // Log file path
error_reporting(E_ALL);       // Report all errors
/**
 * About Us Page
 * Public-facing page with company information
 */
session_start();

// Language handling
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) {
    $lang = 'en';
}
$_SESSION['landing_lang'] = $lang;

// Load language file
$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
if (file_exists($langFile)) {
    $t = require $langFile;
} else {
    $t = require __DIR__ . '/inc/lang/en.php';
}

function __($key) {
    global $t;
    return isset($t[$key]) ? $t[$key] : $key;
}

$htmlLang = $lang === 'th' ? 'th' : 'en';
?>
<!DOCTYPE html>
<html lang="<?= $htmlLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="About iACC - Professional Accounting Management System">
    <title>About Us - iACC</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if ($lang === 'th'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --primary-light: #a569bd;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?= $lang === 'th' ? "'Sarabun', " : "" ?>'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Navbar */
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
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: white;
        }
        
        /* Hero */
        .hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 150px 20px 80px;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 20px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Content */
        .content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 80px 20px;
        }
        
        .section {
            margin-bottom: 60px;
        }
        
        .section h2 {
            font-size: 32px;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        .section p {
            font-size: 16px;
            color: #555;
            margin-bottom: 15px;
        }
        
        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin: 60px 0;
        }
        
        .stat-card {
            background: var(--gray-100);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 42px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .stat-card p {
            color: var(--gray-600);
            margin: 0;
        }
        
        /* Team */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }
        
        .team-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .team-avatar {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .team-avatar i {
            font-size: 80px;
            color: rgba(255,255,255,0.8);
        }
        
        .team-info {
            padding: 25px;
        }
        
        .team-info h4 {
            margin: 0 0 5px;
            font-size: 18px;
        }
        
        .team-info p {
            color: var(--gray-600);
            margin: 0;
            font-size: 14px;
        }
        
        /* Values */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }
        
        .value-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .value-card i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .value-card h4 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .value-card p {
            color: var(--gray-600);
            font-size: 14px;
            margin: 0;
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .footer a {
            color: var(--primary-light);
            text-decoration: none;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }
            
            .stats, .team-grid, .values-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="landing.php" class="nav-brand">iACC</a>
            <ul class="nav-links">
                <li><a href="landing.php"><?= $lang === 'th' ? 'หน้าแรก' : 'Home' ?></a></li>
                <li><a href="landing.php#features"><?= __('nav_features') ?></a></li>
                <li><a href="landing.php#pricing"><?= __('nav_pricing') ?></a></li>
                <li><a href="contact.php"><?= __('nav_contact') ?></a></li>
                <li><a href="login.php"><?= __('nav_sign_in') ?></a></li>
            </ul>
        </div>
    </nav>
    
    <section class="hero">
        <h1><?= $lang === 'th' ? 'เกี่ยวกับเรา' : 'About Us' ?></h1>
        <p><?= $lang === 'th' ? 'เรียนรู้เพิ่มเติมเกี่ยวกับ iACC และภารกิจของเราในการทำให้การจัดการบัญชีง่ายขึ้น' : 'Learn more about iACC and our mission to simplify accounting management' ?></p>
    </section>
    
    <div class="content">
        <!-- Mission -->
        <div class="section">
            <h2><?= $lang === 'th' ? 'ภารกิจของเรา' : 'Our Mission' ?></h2>
            <p><?= $lang === 'th' ? 'ที่ iACC เราเชื่อว่าทุกธุรกิจสมควรได้รับเครื่องมือจัดการบัญชีที่ทรงพลังแต่ใช้งานง่าย ภารกิจของเราคือการทำให้การจัดการทางการเงินเป็นเรื่องง่ายสำหรับธุรกิจทุกขนาด' : 'At iACC, we believe every business deserves powerful yet simple accounting tools. Our mission is to make financial management accessible to businesses of all sizes.' ?></p>
            <p><?= $lang === 'th' ? 'ก่อตั้งในปี 2020 เราได้ช่วยเหลือธุรกิจหลายร้อยแห่งปรับปรุงกระบวนการทางการเงินของพวกเขา ตั้งแต่การออกใบแจ้งหนี้ไปจนถึงการรายงาน' : 'Founded in 2020, we have helped hundreds of businesses streamline their financial processes, from invoicing to reporting.' ?></p>
        </div>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <h3>500+</h3>
                <p><?= $lang === 'th' ? 'ผู้ใช้งาน' : 'Active Users' ?></p>
            </div>
            <div class="stat-card">
                <h3>50K+</h3>
                <p><?= $lang === 'th' ? 'ใบแจ้งหนี้' : 'Invoices Processed' ?></p>
            </div>
            <div class="stat-card">
                <h3>99.9%</h3>
                <p><?= $lang === 'th' ? 'อัปไทม์' : 'Uptime' ?></p>
            </div>
            <div class="stat-card">
                <h3>24/7</h3>
                <p><?= $lang === 'th' ? 'สนับสนุน' : 'Support' ?></p>
            </div>
        </div>
        
        <!-- Values -->
        <div class="section">
            <h2><?= $lang === 'th' ? 'ค่านิยมของเรา' : 'Our Values' ?></h2>
            <div class="values-grid">
                <div class="value-card">
                    <i class="fa fa-heart"></i>
                    <h4><?= $lang === 'th' ? 'ลูกค้าต้องมาก่อน' : 'Customer First' ?></h4>
                    <p><?= $lang === 'th' ? 'ลูกค้าของเราอยู่ในหัวใจของทุกสิ่งที่เราทำ เราฟังความต้องการของพวกเขาและส่งมอบโซลูชันที่สำคัญจริงๆ' : 'Our customers are at the heart of everything we do. We listen to their needs and deliver solutions that truly matter.' ?></p>
                </div>
                <div class="value-card">
                    <i class="fa fa-shield"></i>
                    <h4><?= $lang === 'th' ? 'ความปลอดภัย' : 'Security' ?></h4>
                    <p><?= $lang === 'th' ? 'เราให้ความสำคัญกับความปลอดภัยของข้อมูลอย่างจริงจัง ข้อมูลทางการเงินของคุณได้รับการปกป้องด้วยการเข้ารหัสระดับอุตสาหกรรม' : 'We take data security seriously. Your financial data is protected with industry-standard encryption.' ?></p>
                </div>
                <div class="value-card">
                    <i class="fa fa-rocket"></i>
                    <h4><?= $lang === 'th' ? 'นวัตกรรม' : 'Innovation' ?></h4>
                    <p><?= $lang === 'th' ? 'เราปรับปรุงแพลตฟอร์มอย่างต่อเนื่องด้วยคุณสมบัติใหม่และการปรับปรุงตามความคิดเห็นของผู้ใช้' : 'We continuously improve our platform with new features and enhancements based on user feedback.' ?></p>
                </div>
            </div>
        </div>
        
        <!-- Team -->
        <div class="section">
            <h2><?= $lang === 'th' ? 'ทีมของเรา' : 'Our Team' ?></h2>
            <p><?= $lang === 'th' ? 'พบกับผู้คนที่อยู่เบื้องหลัง iACC' : 'Meet the people behind iACC' ?></p>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="team-info">
                        <h4>John Smith</h4>
                        <p><?= $lang === 'th' ? 'ผู้ก่อตั้งและ CEO' : 'Founder & CEO' ?></p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="team-info">
                        <h4>Sarah Johnson</h4>
                        <p><?= $lang === 'th' ? 'หัวหน้าฝ่ายเทคโนโลยี' : 'CTO' ?></p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="team-info">
                        <h4>Michael Chen</h4>
                        <p><?= $lang === 'th' ? 'หัวหน้าฝ่ายผลิตภัณฑ์' : 'Head of Product' ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CTA -->
        <div class="section" style="text-align: center; margin-top: 60px;">
            <h2><?= $lang === 'th' ? 'พร้อมที่จะเริ่มต้นหรือยัง?' : 'Ready to Get Started?' ?></h2>
            <p style="margin-bottom: 30px;"><?= $lang === 'th' ? 'เข้าร่วมกับธุรกิจหลายร้อยแห่งที่ใช้ iACC' : 'Join hundreds of businesses using iACC' ?></p>
            <a href="login.php" class="btn btn-primary"><?= __('hero_cta_start') ?></a>
        </div>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
