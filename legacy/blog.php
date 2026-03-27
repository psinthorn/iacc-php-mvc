<?php
/**
 * Blog Page
 */
session_start();

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';

function __($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

// Sample blog posts
$posts = [
    [
        'title_en' => 'Introducing iACC 2.0 - A Complete Redesign',
        'title_th' => 'เปิดตัว iACC 2.0 - ออกแบบใหม่ทั้งหมด',
        'excerpt_en' => 'We\'re excited to announce the launch of iACC 2.0, featuring a completely redesigned interface, improved performance, and powerful new features.',
        'excerpt_th' => 'เราตื่นเต้นที่จะประกาศเปิดตัว iACC 2.0 พร้อมอินเทอร์เฟซที่ออกแบบใหม่ทั้งหมด ประสิทธิภาพที่ดีขึ้น และฟีเจอร์ใหม่ที่ทรงพลัง',
        'category_en' => 'Product Updates',
        'category_th' => 'อัปเดตผลิตภัณฑ์',
        'date' => '2026-01-02',
        'image' => 'fa-rocket',
    ],
    [
        'title_en' => '5 Tips for Better Invoice Management',
        'title_th' => '5 เคล็ดลับการจัดการใบแจ้งหนี้ให้ดีขึ้น',
        'excerpt_en' => 'Learn how to streamline your invoicing process, reduce late payments, and keep your cash flow healthy with these practical tips.',
        'excerpt_th' => 'เรียนรู้วิธีปรับปรุงกระบวนการออกใบแจ้งหนี้ ลดการชำระเงินล่าช้า และรักษากระแสเงินสดให้ดีด้วยเคล็ดลับเหล่านี้',
        'category_en' => 'Tips & Tutorials',
        'category_th' => 'เคล็ดลับ',
        'date' => '2025-12-28',
        'image' => 'fa-lightbulb-o',
    ],
    [
        'title_en' => 'Understanding Thai Tax Regulations for SMEs',
        'title_th' => 'ทำความเข้าใจกฎระเบียบภาษีไทยสำหรับ SME',
        'excerpt_en' => 'A comprehensive guide to Thai tax regulations for small and medium enterprises, including VAT, withholding tax, and annual filings.',
        'excerpt_th' => 'คู่มือฉบับสมบูรณ์เกี่ยวกับกฎระเบียบภาษีไทยสำหรับวิสาหกิจขนาดกลางและขนาดย่อม รวมถึง VAT ภาษีหัก ณ ที่จ่าย และการยื่นแบบประจำปี',
        'category_en' => 'Guides',
        'category_th' => 'คู่มือ',
        'date' => '2025-12-20',
        'image' => 'fa-book',
    ],
    [
        'title_en' => 'How to Choose the Right Accounting Software',
        'title_th' => 'วิธีเลือกซอฟต์แวร์บัญชีที่เหมาะสม',
        'excerpt_en' => 'With so many options available, choosing the right accounting software can be overwhelming. Here\'s what to look for.',
        'excerpt_th' => 'ด้วยตัวเลือกมากมาย การเลือกซอฟต์แวร์บัญชีที่เหมาะสมอาจทำให้สับสน นี่คือสิ่งที่ควรพิจารณา',
        'category_en' => 'Guides',
        'category_th' => 'คู่มือ',
        'date' => '2025-12-15',
        'image' => 'fa-check-circle',
    ],
    [
        'title_en' => 'Year-End Financial Checklist for Businesses',
        'title_th' => 'รายการตรวจสอบทางการเงินสิ้นปีสำหรับธุรกิจ',
        'excerpt_en' => 'Make sure your business is ready for the new year with this comprehensive year-end financial checklist.',
        'excerpt_th' => 'ตรวจสอบให้แน่ใจว่าธุรกิจของคุณพร้อมสำหรับปีใหม่ด้วยรายการตรวจสอบทางการเงินสิ้นปีฉบับสมบูรณ์นี้',
        'category_en' => 'Tips & Tutorials',
        'category_th' => 'เคล็ดลับ',
        'date' => '2025-12-10',
        'image' => 'fa-calendar-check-o',
    ],
    [
        'title_en' => 'Automating Your Accounting Workflow',
        'title_th' => 'ทำให้เวิร์กโฟลว์การบัญชีเป็นอัตโนมัติ',
        'excerpt_en' => 'Save time and reduce errors by automating repetitive accounting tasks. Learn how to set up automated workflows in iACC.',
        'excerpt_th' => 'ประหยัดเวลาและลดข้อผิดพลาดด้วยการทำงานบัญชีซ้ำๆ เป็นอัตโนมัติ เรียนรู้วิธีตั้งค่าเวิร์กโฟลว์อัตโนมัติใน iACC',
        'category_en' => 'Tips & Tutorials',
        'category_th' => 'เคล็ดลับ',
        'date' => '2025-12-05',
        'image' => 'fa-cogs',
    ],
];

function formatDate($date, $lang) {
    $timestamp = strtotime($date);
    if ($lang === 'th') {
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return date('j', $timestamp) . ' ' . $months[(int)date('n', $timestamp)] . ' ' . (date('Y', $timestamp) + 543);
    }
    return date('M j, Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'th' ? 'บทความ' : 'Blog' ?> - iACC</title>
    
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
        
        .featured-post {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        
        .featured-image {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }
        
        .featured-image i {
            font-size: 80px;
            color: rgba(255,255,255,0.3);
        }
        
        .featured-content {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .post-category {
            display: inline-block;
            background: #f8f4fb;
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .featured-content h2 {
            font-size: 26px;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .featured-content p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .post-meta {
            color: #888;
            font-size: 14px;
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .post-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .post-image {
            height: 150px;
            background: linear-gradient(135deg, #a569bd, #8e44ad);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .post-image i {
            font-size: 40px;
            color: rgba(255,255,255,0.4);
        }
        
        .post-content {
            padding: 25px;
        }
        
        .post-content h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .post-content p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .read-more {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .read-more:hover {
            text-decoration: underline;
        }
        
        .newsletter-section {
            background: white;
            border-radius: 16px;
            padding: 50px;
            text-align: center;
            margin-top: 60px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .newsletter-section h2 {
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .newsletter-section p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .newsletter-form {
            display: flex;
            gap: 10px;
            max-width: 450px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
        }
        
        .newsletter-form input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .newsletter-form button {
            padding: 14px 25px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .newsletter-form button:hover {
            transform: translateY(-2px);
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
            .featured-post { grid-template-columns: 1fr; }
            .featured-image { min-height: 200px; }
            .newsletter-form { flex-direction: column; }
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
        <h1><?= $lang === 'th' ? 'บทความและข่าวสาร' : 'Blog & Updates' ?></h1>
        <p><?= $lang === 'th' ? 'เคล็ดลับ คู่มือ และข่าวสารล่าสุดเกี่ยวกับการบัญชีและ iACC' : 'Tips, guides, and the latest news about accounting and iACC.' ?></p>
    </section>
    
    <div class="content">
        <!-- Featured Post -->
        <?php $featured = $posts[0]; ?>
        <div class="featured-post">
            <div class="featured-image">
                <i class="fa <?= $featured['image'] ?>"></i>
            </div>
            <div class="featured-content">
                <span class="post-category"><?= $lang === 'th' ? $featured['category_th'] : $featured['category_en'] ?></span>
                <h2><?= $lang === 'th' ? $featured['title_th'] : $featured['title_en'] ?></h2>
                <p><?= $lang === 'th' ? $featured['excerpt_th'] : $featured['excerpt_en'] ?></p>
                <span class="post-meta"><?= formatDate($featured['date'], $lang) ?></span>
            </div>
        </div>
        
        <!-- Posts Grid -->
        <div class="posts-grid">
            <?php for ($i = 1; $i < count($posts); $i++): $post = $posts[$i]; ?>
            <article class="post-card">
                <div class="post-image">
                    <i class="fa <?= $post['image'] ?>"></i>
                </div>
                <div class="post-content">
                    <span class="post-category"><?= $lang === 'th' ? $post['category_th'] : $post['category_en'] ?></span>
                    <h3><?= $lang === 'th' ? $post['title_th'] : $post['title_en'] ?></h3>
                    <p><?= $lang === 'th' ? $post['excerpt_th'] : $post['excerpt_en'] ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="post-meta"><?= formatDate($post['date'], $lang) ?></span>
                        <a href="#" class="read-more"><?= $lang === 'th' ? 'อ่านเพิ่มเติม' : 'Read More' ?> <i class="fa fa-arrow-right"></i></a>
                    </div>
                </div>
            </article>
            <?php endfor; ?>
        </div>
        
        <!-- Newsletter -->
        <div class="newsletter-section">
            <h2><?= $lang === 'th' ? 'สมัครรับข่าวสาร' : 'Subscribe to Our Newsletter' ?></h2>
            <p><?= $lang === 'th' ? 'รับบทความและอัปเดตล่าสุดส่งตรงถึงอีเมลของคุณ' : 'Get the latest articles and updates delivered to your inbox.' ?></p>
            <form class="newsletter-form" onsubmit="event.preventDefault(); alert('<?= $lang === 'th' ? 'ขอบคุณที่สมัครรับข่าวสาร!' : 'Thanks for subscribing!' ?>');">
                <input type="email" placeholder="<?= $lang === 'th' ? 'อีเมลของคุณ' : 'Your email address' ?>" required>
                <button type="submit"><?= $lang === 'th' ? 'สมัคร' : 'Subscribe' ?></button>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
