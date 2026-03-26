<?php
/**
 * Careers Page
 */
session_start();

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';

function __($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

// Sample job listings
$jobs = [
    [
        'title_en' => 'Senior Full-Stack Developer',
        'title_th' => 'นักพัฒนา Full-Stack อาวุโส',
        'type' => 'Full-time',
        'location_en' => 'Bangkok, Thailand',
        'location_th' => 'กรุงเทพฯ, ประเทศไทย',
        'department_en' => 'Engineering',
        'department_th' => 'วิศวกรรม',
    ],
    [
        'title_en' => 'UI/UX Designer',
        'title_th' => 'นักออกแบบ UI/UX',
        'type' => 'Full-time',
        'location_en' => 'Bangkok, Thailand',
        'location_th' => 'กรุงเทพฯ, ประเทศไทย',
        'department_en' => 'Design',
        'department_th' => 'ออกแบบ',
    ],
    [
        'title_en' => 'Product Manager',
        'title_th' => 'ผู้จัดการผลิตภัณฑ์',
        'type' => 'Full-time',
        'location_en' => 'Bangkok, Thailand',
        'location_th' => 'กรุงเทพฯ, ประเทศไทย',
        'department_en' => 'Product',
        'department_th' => 'ผลิตภัณฑ์',
    ],
    [
        'title_en' => 'Customer Support Specialist',
        'title_th' => 'ผู้เชี่ยวชาญฝ่ายสนับสนุนลูกค้า',
        'type' => 'Full-time',
        'location_en' => 'Remote',
        'location_th' => 'ทำงานระยะไกล',
        'department_en' => 'Support',
        'department_th' => 'สนับสนุน',
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'th' ? 'ร่วมงานกับเรา' : 'Careers' ?> - iACC</title>
    
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
        
        .content {
            max-width: 1000px;
            margin: 60px auto;
            padding: 0 20px;
        }
        
        .benefits-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 60px;
        }
        
        .benefit-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .benefit-icon i {
            color: white;
            font-size: 24px;
        }
        
        .benefit-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .benefit-card p {
            color: #666;
            font-size: 14px;
        }
        
        .section-title {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .job-card {
            background: white;
            border-radius: 16px;
            padding: 25px 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .job-info h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .job-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .job-meta span {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .job-meta i {
            color: var(--primary);
        }
        
        .btn-apply {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(142,68,173,0.3);
        }
        
        .no-jobs {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .no-jobs i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-jobs h3 {
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .no-jobs p {
            color: #666;
        }
        
        .cta-section {
            background: white;
            border-radius: 16px;
            padding: 50px;
            text-align: center;
            margin-top: 60px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .cta-section h2 {
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .cta-section p {
            color: #666;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .footer {
            background: var(--dark);
            color: white;
            padding: 30px 20px;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer a { color: #a569bd; text-decoration: none; }
        
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero h1 { font-size: 28px; }
            .job-card { flex-direction: column; align-items: flex-start; }
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
        <h1><?= $lang === 'th' ? 'ร่วมงานกับเรา' : 'Join Our Team' ?></h1>
        <p><?= $lang === 'th' ? 'สร้างอนาคตของซอฟต์แวร์บัญชีไปกับเรา เรากำลังมองหาคนที่มีความสามารถและหลงใหลในการสร้างผลิตภัณฑ์ที่ยอดเยี่ยม' : 'Build the future of accounting software with us. We\'re looking for talented people who are passionate about building great products.' ?></p>
    </section>
    
    <div class="content">
        <!-- Benefits -->
        <h2 class="section-title"><?= $lang === 'th' ? 'ทำไมต้องทำงานกับ iACC?' : 'Why Work at iACC?' ?></h2>
        <div class="benefits-section">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fa fa-heartbeat"></i>
                </div>
                <h3><?= $lang === 'th' ? 'ประกันสุขภาพ' : 'Health Insurance' ?></h3>
                <p><?= $lang === 'th' ? 'ประกันสุขภาพครอบคลุมสำหรับคุณและครอบครัว' : 'Comprehensive health coverage for you and your family.' ?></p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fa fa-home"></i>
                </div>
                <h3><?= $lang === 'th' ? 'ทำงานยืดหยุ่น' : 'Flexible Work' ?></h3>
                <p><?= $lang === 'th' ? 'ทำงานจากที่ไหนก็ได้ เวลาทำงานยืดหยุ่น' : 'Work from anywhere with flexible hours.' ?></p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fa fa-graduation-cap"></i>
                </div>
                <h3><?= $lang === 'th' ? 'การเรียนรู้' : 'Learning Budget' ?></h3>
                <p><?= $lang === 'th' ? 'งบประมาณสำหรับคอร์สเรียนและการพัฒนาตนเอง' : 'Annual budget for courses and self-improvement.' ?></p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fa fa-plane"></i>
                </div>
                <h3><?= $lang === 'th' ? 'วันหยุดไม่จำกัด' : 'Unlimited PTO' ?></h3>
                <p><?= $lang === 'th' ? 'วันหยุดพักผ่อนไม่จำกัดตามความต้องการ' : 'Take the time off you need to recharge.' ?></p>
            </div>
        </div>
        
        <!-- Job Listings -->
        <h2 class="section-title"><?= $lang === 'th' ? 'ตำแหน่งที่เปิดรับ' : 'Open Positions' ?></h2>
        <div class="jobs-list">
            <?php foreach ($jobs as $job): ?>
            <div class="job-card">
                <div class="job-info">
                    <h3><?= $lang === 'th' ? $job['title_th'] : $job['title_en'] ?></h3>
                    <div class="job-meta">
                        <span><i class="fa fa-building"></i> <?= $lang === 'th' ? $job['department_th'] : $job['department_en'] ?></span>
                        <span><i class="fa fa-map-marker"></i> <?= $lang === 'th' ? $job['location_th'] : $job['location_en'] ?></span>
                        <span><i class="fa fa-clock-o"></i> <?= $job['type'] ?></span>
                    </div>
                </div>
                <a href="contact.php?lang=<?= $lang ?>" class="btn-apply">
                    <?= $lang === 'th' ? 'สมัครงาน' : 'Apply Now' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cta-section">
            <h2><?= $lang === 'th' ? 'ไม่เห็นตำแหน่งที่เหมาะสม?' : 'Don\'t See a Perfect Fit?' ?></h2>
            <p><?= $lang === 'th' ? 'เราเปิดรับผู้มีความสามารถเสมอ ส่งประวัติของคุณมาให้เราพิจารณา' : 'We\'re always looking for talented people. Send us your resume for consideration.' ?></p>
            <a href="contact.php?lang=<?= $lang ?>" class="btn-apply">
                <i class="fa fa-envelope"></i> <?= $lang === 'th' ? 'ส่งประวัติ' : 'Submit Your Resume' ?>
            </a>
        </div>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
