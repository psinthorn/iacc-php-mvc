<?php
/**
 * Press / Media Page
 */
session_start();

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';

function __($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

// Sample press releases
$releases = [
    [
        'title_en' => 'iACC Launches Version 2.0 with Complete Redesign',
        'title_th' => 'iACC เปิดตัวเวอร์ชัน 2.0 พร้อมการออกแบบใหม่ทั้งหมด',
        'summary_en' => 'The latest version features a modern UI, multi-language support, and enhanced security features.',
        'summary_th' => 'เวอร์ชันล่าสุดมาพร้อมกับ UI ที่ทันสมัย รองรับหลายภาษา และฟีเจอร์ความปลอดภัยที่เพิ่มขึ้น',
        'date' => '2026-01-02',
    ],
    [
        'title_en' => 'iACC Reaches 500+ Business Users Milestone',
        'title_th' => 'iACC มีผู้ใช้งานธุรกิจครบ 500+ ราย',
        'summary_en' => 'Growing adoption among Thai SMEs demonstrates the value of simplified accounting solutions.',
        'summary_th' => 'การเติบโตในกลุ่ม SME ไทยแสดงให้เห็นถึงคุณค่าของโซลูชันบัญชีที่ใช้งานง่าย',
        'date' => '2025-11-15',
    ],
    [
        'title_en' => 'iACC Announces Partnership with Leading Thai Banks',
        'title_th' => 'iACC ประกาศความร่วมมือกับธนาคารชั้นนำของไทย',
        'summary_en' => 'New integrations will enable direct bank connections and automated reconciliation.',
        'summary_th' => 'การผสานรวมใหม่จะช่วยให้เชื่อมต่อธนาคารโดยตรงและกระทบยอดอัตโนมัติ',
        'date' => '2025-10-01',
    ],
];

function formatDate($date, $lang) {
    $timestamp = strtotime($date);
    if ($lang === 'th') {
        $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        return date('j', $timestamp) . ' ' . $months[(int)date('n', $timestamp)] . ' ' . (date('Y', $timestamp) + 543);
    }
    return date('F j, Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'th' ? 'ข่าวประชาสัมพันธ์' : 'Press' ?> - iACC</title>
    
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
        
        .media-contact {
            background: white;
            border-radius: 16px;
            padding: 30px 40px;
            margin-bottom: 50px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .media-contact-info h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .media-contact-info p {
            color: #666;
        }
        
        .media-contact-info a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .btn-kit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 25px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-kit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(142,68,173,0.3);
        }
        
        .section-title {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 30px;
        }
        
        .releases-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-bottom: 60px;
        }
        
        .release-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .release-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .release-date {
            color: var(--primary);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .release-card h3 {
            font-size: 22px;
            margin-bottom: 12px;
            color: var(--dark);
        }
        
        .release-card p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .read-more {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .read-more:hover {
            text-decoration: underline;
        }
        
        .brand-assets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .asset-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .asset-preview {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .asset-preview.logo-purple {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }
        
        .asset-preview.logo-purple span {
            color: white;
            font-size: 32px;
            font-weight: 700;
        }
        
        .asset-preview.logo-dark span {
            color: var(--dark);
            font-size: 32px;
            font-weight: 700;
        }
        
        .asset-preview.colors {
            display: flex;
            gap: 10px;
        }
        
        .color-swatch {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }
        
        .asset-card h4 {
            font-size: 16px;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .asset-card p {
            color: #888;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .btn-download {
            display: inline-block;
            padding: 10px 20px;
            background: #f0f0f0;
            color: var(--dark);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .btn-download:hover {
            background: #e0e0e0;
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
            .media-contact { flex-direction: column; text-align: center; }
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
        <h1><?= $lang === 'th' ? 'ข่าวประชาสัมพันธ์' : 'Press & Media' ?></h1>
        <p><?= $lang === 'th' ? 'ข่าวสารล่าสุด ข่าวประชาสัมพันธ์ และสื่อต่างๆ เกี่ยวกับ iACC' : 'Latest news, press releases, and media resources about iACC.' ?></p>
    </section>
    
    <div class="content">
        <!-- Media Contact -->
        <div class="media-contact">
            <div class="media-contact-info">
                <h3><?= $lang === 'th' ? 'สอบถามข้อมูลสื่อ' : 'Media Inquiries' ?></h3>
                <p><?= $lang === 'th' ? 'สำหรับการสัมภาษณ์และข่าวประชาสัมพันธ์ กรุณาติดต่อ' : 'For interviews and press inquiries, please contact' ?> <a href="mailto:press@iacc.com">press@iacc.com</a></p>
            </div>
            <a href="#brand-assets" class="btn-kit">
                <i class="fa fa-download"></i>
                <?= $lang === 'th' ? 'ดาวน์โหลด Press Kit' : 'Download Press Kit' ?>
            </a>
        </div>
        
        <!-- Press Releases -->
        <h2 class="section-title"><?= $lang === 'th' ? 'ข่าวประชาสัมพันธ์ล่าสุด' : 'Latest Press Releases' ?></h2>
        <div class="releases-list">
            <?php foreach ($releases as $release): ?>
            <article class="release-card">
                <div class="release-date"><?= formatDate($release['date'], $lang) ?></div>
                <h3><?= $lang === 'th' ? $release['title_th'] : $release['title_en'] ?></h3>
                <p><?= $lang === 'th' ? $release['summary_th'] : $release['summary_en'] ?></p>
                <a href="#" class="read-more"><?= $lang === 'th' ? 'อ่านเพิ่มเติม' : 'Read Full Release' ?> <i class="fa fa-arrow-right"></i></a>
            </article>
            <?php endforeach; ?>
        </div>
        
        <!-- Brand Assets -->
        <h2 class="section-title" id="brand-assets"><?= $lang === 'th' ? 'สื่อและโลโก้' : 'Brand Assets' ?></h2>
        <div class="brand-assets">
            <div class="asset-card">
                <div class="asset-preview logo-purple">
                    <span>iACC</span>
                </div>
                <h4><?= $lang === 'th' ? 'โลโก้ (พื้นสี)' : 'Logo (Colored Background)' ?></h4>
                <p><?= $lang === 'th' ? 'สำหรับใช้บนพื้นหลังสี' : 'For use on colored backgrounds' ?></p>
                <a href="#" class="btn-download"><i class="fa fa-download"></i> PNG / SVG</a>
            </div>
            
            <div class="asset-card">
                <div class="asset-preview logo-dark">
                    <span>iACC</span>
                </div>
                <h4><?= $lang === 'th' ? 'โลโก้ (พื้นขาว)' : 'Logo (Light Background)' ?></h4>
                <p><?= $lang === 'th' ? 'สำหรับใช้บนพื้นหลังสว่าง' : 'For use on light backgrounds' ?></p>
                <a href="#" class="btn-download"><i class="fa fa-download"></i> PNG / SVG</a>
            </div>
            
            <div class="asset-card">
                <div class="asset-preview colors">
                    <div class="color-swatch" style="background: #8e44ad;"></div>
                    <div class="color-swatch" style="background: #6c3483;"></div>
                    <div class="color-swatch" style="background: #2c3e50;"></div>
                    <div class="color-swatch" style="background: #27ae60;"></div>
                </div>
                <h4><?= $lang === 'th' ? 'สีของแบรนด์' : 'Brand Colors' ?></h4>
                <p><?= $lang === 'th' ? 'จานสีอย่างเป็นทางการของ iACC' : 'Official iACC color palette' ?></p>
                <a href="#" class="btn-download"><i class="fa fa-download"></i> <?= $lang === 'th' ? 'คู่มือสี' : 'Color Guide' ?></a>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
