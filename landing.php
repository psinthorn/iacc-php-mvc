<?php
/**
 * iACC Landing Page
 * Modern public-facing landing page with multi-language support
 */
session_start();

// Check if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

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

// Helper function
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
    <meta name="description" content="iACC - Professional Accounting Management System for modern businesses">
    <title>iACC - Accounting Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if ($lang === 'th'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --primary-light: #a569bd;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
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
            overflow-x: hidden;
        }
        
        /* ============ TOP NAVBAR ============ */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .top-navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .nav-brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
        }
        
        .nav-brand-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .nav-brand-text span {
            color: var(--primary);
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 30px;
            list-style: none;
        }
        
        .nav-menu a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }
        
        .nav-menu a:hover {
            color: var(--primary);
        }
        
        .lang-switcher {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
            padding-left: 20px;
            border-left: 1px solid var(--gray-200);
        }
        
        .lang-switcher a {
            font-size: 13px;
            color: var(--gray-600);
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .lang-switcher a:hover,
        .lang-switcher a.active {
            color: var(--primary);
            background: rgba(142, 68, 173, 0.1);
        }
        
        .lang-switcher span {
            color: var(--gray-200);
            font-size: 12px;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(142, 68, 173, 0.4);
        }
        
        .btn-lg {
            padding: 16px 32px;
            font-size: 16px;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--dark);
            cursor: pointer;
        }
        
        /* ============ HERO SECTION ============ */
        .hero {
            min-height: 100vh;
            padding: 120px 20px 80px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 150%;
            background: linear-gradient(135deg, rgba(142, 68, 173, 0.1), rgba(52, 152, 219, 0.1));
            border-radius: 50%;
            z-index: 0;
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .hero-content h1 span {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            color: var(--gray-600);
            margin-bottom: 30px;
            max-width: 500px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .hero-stats {
            display: flex;
            gap: 40px;
            margin-top: 50px;
        }
        
        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-item p {
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .hero-image {
            position: relative;
        }
        
        .hero-image-main {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            background: white;
            padding: 20px;
        }
        
        .dashboard-preview {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 15px;
            padding: 30px;
            color: white;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .dashboard-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .dash-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 15px;
        }
        
        .dash-card-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .dash-card-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        /* ============ FEATURES SECTION ============ */
        .features {
            padding: 100px 20px;
            background: white;
        }
        
        .section-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-label {
            display: inline-block;
            background: rgba(142, 68, 173, 0.1);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .feature-card {
            padding: 35px;
            background: var(--gray-100);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: white;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .feature-card p {
            color: var(--gray-600);
            font-size: 0.95rem;
        }
        
        /* ============ PRICING SECTION ============ */
        .pricing {
            padding: 100px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecef 100%);
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .pricing-card.featured {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            transform: scale(1.05);
            box-shadow: 0 30px 60px rgba(142, 68, 173, 0.3);
        }
        
        .pricing-card.featured .pricing-price,
        .pricing-card.featured h3 {
            color: white;
        }
        
        .pricing-card.featured .pricing-features li {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--warning);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .pricing-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0;
        }
        
        .pricing-price span {
            font-size: 1rem;
            font-weight: 400;
        }
        
        .pricing-features {
            list-style: none;
            margin: 30px 0;
            text-align: left;
        }
        
        .pricing-features li {
            padding: 10px 0;
            color: var(--gray-600);
        }
        
        .pricing-features li i {
            margin-right: 10px;
            color: var(--success);
        }
        
        .pricing-card.featured .pricing-features li i {
            color: rgba(255, 255, 255, 0.8);
        }
        
        /* ============ CTA SECTION ============ */
        .cta {
            padding: 100px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            text-align: center;
            color: white;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .btn-white {
            background: white;
            color: var(--primary);
        }
        
        .btn-white:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
        }
        
        /* ============ FOOTER ============ */
        .footer {
            padding: 60px 20px 30px;
            background: var(--dark);
            color: white;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
        }
        
        .footer-brand {
            margin-bottom: 20px;
        }
        
        .footer-brand h3 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            margin-top: 15px;
        }
        
        .footer-column h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 12px;
        }
        
        .footer-column ul a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column ul a:hover {
            color: white;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 50px auto 0;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--primary);
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 992px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                margin: 0 auto 30px;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-image {
                order: -1;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .features-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .pricing-card.featured {
                transform: none;
            }
            
            .footer-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-actions .btn-outline {
                display: none;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 20px;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-navbar" id="navbar">
        <div class="nav-container">
            <a href="landing.php" class="nav-brand">
                <div class="nav-brand-text"><span style="color: var(--primary);">iACC</span></div>
            </a>
            
            <ul class="nav-menu">
                <li><a href="#features"><?= __('nav_features') ?></a></li>
                <li><a href="#pricing"><?= __('nav_pricing') ?></a></li>
                <li><a href="#about"><?= __('nav_about') ?></a></li>
                <li><a href="#contact"><?= __('nav_contact') ?></a></li>
                <li class="lang-switcher">
                    <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
                    <span>|</span>
                    <a href="?lang=th" class="<?= $lang === 'th' ? 'active' : '' ?>">TH</a>
                </li>
            </ul>
            
            <div class="nav-actions">
                <a href="login.php" class="btn btn-outline"><?= __('nav_sign_in') ?></a>
                <a href="login.php" class="btn btn-primary"><?= __('nav_get_started') ?></a>
            </div>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fa fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1><?= __('hero_title') ?> <span><?= __('hero_title_highlight') ?></span></h1>
                <p><?= __('hero_subtitle') ?></p>
                
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="fa fa-rocket"></i> <?= __('hero_cta_start') ?>
                    </a>
                    <a href="#features" class="btn btn-outline btn-lg">
                        <i class="fa fa-play-circle"></i> <?= __('hero_cta_demo') ?>
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3>500+</h3>
                        <p>Active Users</p>
                    </div>
                    <div class="stat-item">
                        <h3>50K+</h3>
                        <p>Invoices Processed</p>
                    </div>
                    <div class="stat-item">
                        <h3>99.9%</h3>
                        <p>Uptime</p>
                    </div>
                </div>
            </div>
            
            <div class="hero-image">
                <div class="hero-image-main">
                    <div class="dashboard-preview">
                        <div class="dashboard-header">
                            <span class="dashboard-title"><i class="fa fa-tachometer"></i> Dashboard</span>
                            <span style="opacity: 0.8; font-size: 0.85rem;">Jan 2026</span>
                        </div>
                        <div class="dashboard-cards">
                            <div class="dash-card">
                                <div class="dash-card-value">฿1.2M</div>
                                <div class="dash-card-label">Revenue</div>
                            </div>
                            <div class="dash-card">
                                <div class="dash-card-value">284</div>
                                <div class="dash-card-label">Invoices</div>
                            </div>
                            <div class="dash-card">
                                <div class="dash-card-value">45</div>
                                <div class="dash-card-label">Pending</div>
                            </div>
                            <div class="dash-card">
                                <div class="dash-card-value">98%</div>
                                <div class="dash-card-label">Paid</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-container">
            <div class="section-header">
                <span class="section-label"><?= __('nav_features') ?></span>
                <h2 class="section-title"><?= __('features_title') ?></h2>
                <p class="section-subtitle"><?= __('features_subtitle') ?></p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <h3><?= __('feature_1_title') ?></h3>
                    <p><?= __('feature_1_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-credit-card"></i>
                    </div>
                    <h3><?= __('feature_2_title') ?></h3>
                    <p><?= __('feature_2_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-bar-chart"></i>
                    </div>
                    <h3><?= __('feature_3_title') ?></h3>
                    <p><?= __('feature_3_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <h3><?= __('feature_4_title') ?></h3>
                    <p><?= __('feature_4_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <h3><?= __('feature_5_title') ?></h3>
                    <p><?= __('feature_5_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-globe"></i>
                    </div>
                    <h3><?= __('feature_6_title') ?></h3>
                    <p><?= __('feature_6_desc') ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="section-container">
            <div class="section-header">
                <span class="section-label"><?= __('nav_pricing') ?></span>
                <h2 class="section-title"><?= __('pricing_title') ?></h2>
                <p class="section-subtitle"><?= __('pricing_subtitle') ?></p>
            </div>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <h3><?= __('pricing_free') ?></h3>
                    <div class="pricing-price">฿0<span><?= __('pricing_month') ?></span></div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_1') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_4') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_2') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_3') ?></li>
                    </ul>
                    <a href="login.php" class="btn btn-outline" style="width: 100%;"><?= __('nav_get_started') ?></a>
                </div>
                
                <div class="pricing-card featured">
                    <span class="pricing-badge"><?= __('pricing_popular') ?></span>
                    <h3><?= __('pricing_pro') ?></h3>
                    <div class="pricing-price">฿990<span><?= __('pricing_month') ?></span></div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_1') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_4') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_2') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_5') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_3') ?></li>
                    </ul>
                    <a href="login.php" class="btn btn-white" style="width: 100%;"><?= __('hero_cta_start') ?></a>
                </div>
                
                <div class="pricing-card">
                    <h3><?= __('pricing_enterprise') ?></h3>
                    <div class="pricing-price"><?= __('pricing_contact') ?></div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_1') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_2') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_3') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_4') ?></li>
                        <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_5') ?></li>
                    </ul>
                    <a href="login.php" class="btn btn-outline" style="width: 100%;"><?= __('pricing_contact') ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="section-container">
            <h2><?= __('cta_title') ?></h2>
            <p><?= __('cta_subtitle') ?></p>
            <a href="login.php" class="btn btn-white btn-lg">
                <i class="fa fa-rocket"></i> <?= __('cta_button') ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-brand">
                <h3><span style="color: var(--primary);">iACC</span></h3>
                <p><?= __('footer_tagline') ?></p>
            </div>
            
            <div class="footer-column">
                <h4><?= __('footer_product') ?></h4>
                <ul>
                    <li><a href="#features"><?= __('nav_features') ?></a></li>
                    <li><a href="#pricing"><?= __('nav_pricing') ?></a></li>
                    <li><a href="blog.php?lang=<?= $lang ?>"><?= __('footer_updates') ?></a></li>
                    <li><a href="roadmap.php?lang=<?= $lang ?>"><?= __('footer_roadmap') ?></a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4><?= __('footer_company') ?></h4>
                <ul>
                    <li><a href="about.php?lang=<?= $lang ?>"><?= __('footer_about') ?></a></li>
                    <li><a href="careers.php?lang=<?= $lang ?>"><?= __('footer_careers') ?></a></li>
                    <li><a href="blog.php?lang=<?= $lang ?>"><?= __('footer_blog') ?></a></li>
                    <li><a href="press.php?lang=<?= $lang ?>"><?= __('footer_press') ?></a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4><?= __('footer_support') ?></h4>
                <ul>
                    <li><a href="login.php"><?= __('footer_help') ?></a></li>
                    <li><a href="contact.php?lang=<?= $lang ?>"><?= __('footer_contact') ?></a></li>
                    <li><a href="privacy.php?lang=<?= $lang ?>"><?= __('footer_status') ?></a></li>
                    <li><a href="terms.php?lang=<?= $lang ?>"><?= __('footer_terms') ?></a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p><?= __('footer_copyright') ?> | <?= $lang === 'th' ? 'พัฒนาโดย' : 'Developed by' ?> <a href="#" style="color: var(--primary);">F2 Co.,Ltd.</a></p>
            <div class="social-links">
                <a href="#"><i class="fa fa-facebook"></i></a>
                <a href="#"><i class="fa fa-twitter"></i></a>
                <a href="#"><i class="fa fa-linkedin"></i></a>
                <a href="#"><i class="fa fa-github"></i></a>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
