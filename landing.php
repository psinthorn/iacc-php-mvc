<?php
/**
 * iACC Landing Page
 * Modern public-facing landing page with multi-language support
 * Accessed via / (included from index.php) or directly via /landing.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If accessed directly (/landing.php) and already logged in, go to dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && !isset($isAuthenticated)) {
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
        
        /* ============ INTEGRATION SECTION ============ */
        .integrations {
            padding: 100px 20px;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 50%, #f3e8ff 100%);
        }
        
        .integration-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 60px;
        }
        
        .integration-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .integration-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .integration-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            margin-bottom: 15px;
        }
        
        .integration-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .integration-card p {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .integration-logos {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .logo-tag {
            display: inline-block;
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* How It Works */
        .how-it-works {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
        }
        
        .how-it-works h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--dark);
        }
        
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .step {
            flex: 1;
            min-width: 180px;
            max-width: 220px;
        }
        
        .step-num {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 auto 12px;
        }
        
        .step p {
            font-size: 0.9rem;
            color: var(--gray-600);
            line-height: 1.4;
        }
        
        .step-arrow {
            color: var(--primary-light);
            font-size: 1.2rem;
        }
        
        /* Featured Card & Badge */
        .featured-card {
            background: linear-gradient(135deg, #fff5f5, #fff0f0) !important;
            border: 2px solid rgba(231, 76, 60, 0.2);
        }
        
        .badge-new {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 700;
            vertical-align: middle;
            margin-left: 6px;
        }
        
        .feature-link {
            display: inline-block;
            margin-top: 10px;
            color: #e74c3c;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .feature-link:hover {
            text-decoration: underline;
        }
        
        /* ============ PRICING SECTION (continued) ============ */
        .pricing {
            padding: 100px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecef 100%);
        }
        
        /* Pricing Tabs */
        .pricing-tabs {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        
        .pricing-tab {
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .pricing-tab:first-child {
            border-radius: 12px 0 0 12px;
        }
        
        .pricing-tab:last-child {
            border-radius: 0 12px 12px 0;
        }
        
        .pricing-tab.active {
            background: var(--primary);
            color: white;
        }
        
        .pricing-tab:hover:not(.active) {
            background: rgba(142, 68, 173, 0.08);
        }
        
        .pricing-tab i {
            font-size: 1.1rem;
        }
        
        .pricing-panel {
            display: none;
        }
        
        .pricing-panel.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .pricing-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .pricing-card.featured {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            transform: scale(1.05);
            box-shadow: 0 30px 60px rgba(142, 68, 173, 0.3);
        }
        
        .pricing-card.featured:hover {
            transform: scale(1.05) translateY(-5px);
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
        
        .pricing-card .plan-duration {
            display: inline-block;
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        
        .pricing-card.featured .plan-duration {
            background: rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
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
        
        /* Addon Note */
        .pricing-addon-note {
            text-align: center;
            margin-top: 30px;
            padding: 16px 24px;
            background: rgba(142, 68, 173, 0.06);
            border-radius: 12px;
            color: var(--gray-600);
            font-size: 0.95rem;
            border-left: 4px solid var(--primary);
        }
        
        .pricing-addon-note i {
            color: var(--primary);
            margin-right: 8px;
        }
        
        /* Comparison Table */
        .pricing-compare {
            margin-top: 60px;
        }
        
        .pricing-compare h3 {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--dark);
        }
        
        .compare-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        
        .compare-table thead th {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 16px 12px;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
        }
        
        .compare-table thead th:first-child {
            text-align: left;
            padding-left: 24px;
        }
        
        .compare-table tbody td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .compare-table tbody td:first-child {
            text-align: left;
            padding-left: 24px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .compare-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .compare-table tbody tr:hover {
            background: rgba(142, 68, 173, 0.03);
        }
        
        .compare-table .plan-highlight {
            background: rgba(142, 68, 173, 0.06);
            font-weight: 600;
            color: var(--primary);
        }
        
        .compare-table thead th.plan-highlight {
            background: var(--warning);
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
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
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
            
            .pricing-grid-4 {
                grid-template-columns: repeat(2, 1fr);
                max-width: 600px;
                margin: 0 auto;
            }
            
            .pricing-card.featured {
                transform: none;
            }
            
            .compare-table {
                font-size: 0.85rem;
            }
            
            .compare-table thead th,
            .compare-table tbody td {
                padding: 10px 8px;
            }
            
            .footer-container {
                grid-template-columns: 1fr 1fr 1fr;
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
            
            .integration-grid {
                grid-template-columns: 1fr;
            }
            
            .pricing-grid-4 {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .pricing-tabs {
                flex-direction: column;
                max-width: 300px;
                margin: 30px auto 30px;
            }
            
            .pricing-tab:first-child {
                border-radius: 12px 12px 0 0;
            }
            
            .pricing-tab:last-child {
                border-radius: 0 0 12px 12px;
            }
            
            .compare-table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .compare-table {
                min-width: 600px;
            }
            
            .steps {
                flex-direction: column;
            }
            
            .step-arrow {
                transform: rotate(90deg);
            }
        }

        /* Templates Section */
        .templates-section {
            padding: 100px 20px;
            background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
        }

        .template-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            max-width: 1000px;
            margin: 0 auto 50px;
        }

        .template-preview {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        .template-preview:hover {
            transform: translateY(-6px);
        }

        .template-preview img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            display: block;
        }

        .template-preview-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 6px 14px;
            background: rgba(142, 68, 173, 0.9);
            color: white;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .template-info h3 {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 12px;
        }

        .template-info p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .template-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .template-tag {
            padding: 4px 12px;
            background: rgba(142, 68, 173, 0.08);
            color: #8e44ad;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }

        .template-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 28px;
        }

        .template-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
        }

        .template-feature i {
            color: #10b981;
            font-size: 14px;
        }

        .template-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .template-buttons .btn {
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .template-buttons .btn-primary {
            background: var(--primary);
            color: white;
        }
        .template-buttons .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .template-buttons .btn-outline {
            border: 2px solid #e2e8f0;
            color: #475569;
            background: white;
        }
        .template-buttons .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .coming-soon-card {
            text-align: center;
            padding: 50px 30px;
            background: white;
            border: 2px dashed #e2e8f0;
            border-radius: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .coming-soon-card i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .coming-soon-card h4 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .coming-soon-card p {
            font-size: 14px;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .template-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .template-preview img {
                height: 220px;
            }
            .template-features {
                grid-template-columns: 1fr;
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
                <li><a href="#integrations"><?= __('nav_integrations') ?></a></li>
                <li><a href="#templates"><?= __('nav_templates') ?></a></li>
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
                <!-- Row 1: Core Accounting -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <h3><?= __('feature_1_title') ?></h3>
                    <p><?= __('feature_1_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <h3><?= __('feature_2_title') ?></h3>
                    <p><?= __('feature_2_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                        <i class="fa fa-bar-chart"></i>
                    </div>
                    <h3><?= __('feature_3_title') ?></h3>
                    <p><?= __('feature_3_desc') ?></p>
                </div>
                
                <!-- Row 2: Financial & Operations -->
                <div class="feature-card featured-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #e67e22, #f39c12);">
                        <i class="fa fa-book"></i>
                    </div>
                    <h3><?= __('feature_4_title') ?> <span class="badge-new">NEW</span></h3>
                    <p><?= __('feature_4_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-credit-card"></i>
                    </div>
                    <h3><?= __('feature_5_title') ?></h3>
                    <p><?= __('feature_5_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                        <i class="fa fa-truck"></i>
                    </div>
                    <h3><?= __('feature_6_title') ?></h3>
                    <p><?= __('feature_6_desc') ?></p>
                </div>
                
                <!-- Row 3: Expense & Tax -->
                <div class="feature-card featured-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <i class="fa fa-calculator"></i>
                    </div>
                    <h3><?= __('feature_7_title') ?> <span class="badge-new">NEW</span></h3>
                    <p><?= __('feature_7_desc') ?></p>
                </div>
                
                <div class="feature-card featured-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #c0392b, #e74c3c);">
                        <i class="fa fa-percent"></i>
                    </div>
                    <h3><?= __('feature_8_title') ?> <span class="badge-new">NEW</span></h3>
                    <p><?= __('feature_8_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <h3><?= __('feature_9_title') ?></h3>
                    <p><?= __('feature_9_desc') ?></p>
                </div>
                
                <!-- Row 4: Platform & Integration -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <h3><?= __('feature_10_title') ?></h3>
                    <p><?= __('feature_10_desc') ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa fa-globe"></i>
                    </div>
                    <h3><?= __('feature_11_title') ?></h3>
                    <p><?= __('feature_11_desc') ?></p>
                </div>
                
                <div class="feature-card featured-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #e74c3c, #f39c12);">
                        <i class="fa fa-plug"></i>
                    </div>
                    <h3><?= __('feature_12_title') ?> <span class="badge-new">NEW</span></h3>
                    <p><?= __('feature_12_desc') ?></p>
                    <a href="#integrations" class="feature-link">Learn more →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sales Channel API Integration Section -->
    <section class="integrations" id="integrations">
        <div class="section-container">
            <div class="section-header">
                <span class="section-label" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;"><?= __('nav_integrations') ?></span>
                <h2 class="section-title"><?= __('integration_title') ?></h2>
                <p class="section-subtitle"><?= __('integration_subtitle') ?></p>
            </div>
            
            <!-- Channel Grid -->
            <div class="integration-grid">
                <div class="integration-card">
                    <div class="integration-icon"><i class="fa fa-code"></i></div>
                    <h3><?= __('integration_channel_1_title') ?></h3>
                    <p><?= __('integration_channel_1_desc') ?></p>
                    <div class="integration-logos">
                        <span class="logo-tag">WordPress</span>
                        <span class="logo-tag">Shopify</span>
                        <span class="logo-tag">WooCommerce</span>
                        <span class="logo-tag">Custom</span>
                    </div>
                </div>
                
                <div class="integration-card">
                    <div class="integration-icon" style="background: linear-gradient(135deg, #06C755, #04a948);"><i class="fa fa-commenting"></i></div>
                    <h3><?= __('integration_channel_2_title') ?></h3>
                    <p><?= __('integration_channel_2_desc') ?></p>
                    <div class="integration-logos">
                        <span class="logo-tag">LINE Messaging API</span>
                        <span class="logo-tag">LINE OA</span>
                    </div>
                </div>
                
                <div class="integration-card">
                    <div class="integration-icon" style="background: linear-gradient(135deg, #1877F2, #0d65d9);"><i class="fa fa-facebook"></i></div>
                    <h3><?= __('integration_channel_3_title') ?></h3>
                    <p><?= __('integration_channel_3_desc') ?></p>
                    <div class="integration-logos">
                        <span class="logo-tag">Messenger</span>
                        <span class="logo-tag">Instagram</span>
                        <span class="logo-tag">FB Shop</span>
                    </div>
                </div>
                
                <div class="integration-card">
                    <div class="integration-icon" style="background: linear-gradient(135deg, #EA4335, #c5221f);"><i class="fa fa-envelope"></i></div>
                    <h3><?= __('integration_channel_4_title') ?></h3>
                    <p><?= __('integration_channel_4_desc') ?></p>
                    <div class="integration-logos">
                        <span class="logo-tag">Gmail</span>
                        <span class="logo-tag">IMAP</span>
                        <span class="logo-tag">SMTP</span>
                    </div>
                </div>
                
                <div class="integration-card">
                    <div class="integration-icon" style="background: linear-gradient(135deg, #8e44ad, #6c3483);"><i class="fa fa-magic"></i></div>
                    <h3><?= __('integration_channel_5_title') ?></h3>
                    <p><?= __('integration_channel_5_desc') ?></p>
                    <div class="integration-logos">
                        <span class="logo-tag">Ollama</span>
                        <span class="logo-tag">OpenAI</span>
                        <span class="logo-tag">Claude</span>
                    </div>
                </div>
                
                <div class="integration-card">
                    <div class="integration-icon" style="background: linear-gradient(135deg, #27ae60, #1e8449);"><i class="fa fa-keyboard-o"></i></div>
                    <h3><?= __('integration_channel_6_title') ?></h3>
                    <p><?= __('integration_channel_6_desc') ?></p>
                    <div class="integration-logos">
                        <span class="logo-tag">Walk-in</span>
                        <span class="logo-tag">Phone</span>
                        <span class="logo-tag">POS</span>
                    </div>
                </div>
            </div>
            
            <!-- How It Works -->
            <div class="how-it-works">
                <h3><?= __('integration_how_title') ?></h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <p><?= __('integration_step_1') ?></p>
                    </div>
                    <div class="step-arrow"><i class="fa fa-arrow-right"></i></div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <p><?= __('integration_step_2') ?></p>
                    </div>
                    <div class="step-arrow"><i class="fa fa-arrow-right"></i></div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <p><?= __('integration_step_3') ?></p>
                    </div>
                    <div class="step-arrow"><i class="fa fa-arrow-right"></i></div>
                    <div class="step">
                        <div class="step-num">4</div>
                        <p><?= __('integration_step_4') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Templates Section -->
    <section class="templates-section" id="templates">
        <div class="section-container">
            <div class="section-header">
                <span class="section-label" style="background: rgba(6, 182, 212, 0.1); color: #0891b2;"><?= __('template_section_label') ?></span>
                <h2 class="section-title"><?= __('template_title') ?></h2>
                <p class="section-subtitle"><?= __('template_subtitle') ?></p>
            </div>

            <!-- Tour Company Template -->
            <div class="template-grid">
                <div class="template-preview">
                    <img src="https://www.mysamuiisland.com/wp-content/uploads/2024/03/angthong-nation-marine-park.jpg" alt="Tour Company Demo Template">
                    <span class="template-preview-badge"><i class="fa fa-star"></i> Free Template</span>
                </div>
                <div class="template-info">
                    <h3><?= __('template_tour_title') ?></h3>
                    <p><?= __('template_tour_desc') ?></p>
                    <div class="template-tags">
                        <?php foreach (explode(',', __('template_tour_tags')) as $tag): ?>
                            <span class="template-tag"><?= trim($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="template-features">
                        <div class="template-feature"><i class="fa fa-check-circle"></i> <?= __('template_feature_responsive') ?></div>
                        <div class="template-feature"><i class="fa fa-check-circle"></i> <?= __('template_feature_api') ?></div>
                        <div class="template-feature"><i class="fa fa-check-circle"></i> <?= __('template_feature_free') ?></div>
                        <div class="template-feature"><i class="fa fa-check-circle"></i> <?= __('template_feature_nocode') ?></div>
                    </div>
                    <div class="template-buttons">
                        <a href="templates/tour-company-demo/index.html" target="_blank" class="btn btn-primary"><i class="fa fa-eye"></i> <?= __('template_btn_preview') ?></a>
                        <a href="template-download.php?template=tour-company-demo" class="btn btn-outline"><i class="fa fa-download"></i> <?= __('template_btn_download') ?></a>
                    </div>
                </div>
            </div>

            <!-- Coming Soon -->
            <div class="coming-soon-card">
                <i class="fa fa-th-large"></i>
                <h4><?= __('template_coming_soon') ?></h4>
                <p><?= __('template_coming_soon_desc') ?></p>
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
            
            <!-- Pricing Tabs -->
            <div class="pricing-tabs">
                <button class="pricing-tab active" onclick="switchPricingTab('accounting')" id="tab-accounting">
                    <i class="fa fa-briefcase"></i> <?= __('pricing_tab_accounting') ?>
                </button>
                <button class="pricing-tab" onclick="switchPricingTab('api')" id="tab-api">
                    <i class="fa fa-plug"></i> <?= __('pricing_tab_api') ?>
                    <span class="badge-new">NEW</span>
                </button>
            </div>
            
            <!-- Panel 1: Accounting Plans -->
            <div class="pricing-panel active" id="panel-accounting">
                <div class="pricing-grid">
                    <div class="pricing-card">
                        <h3><?= __('pricing_free') ?></h3>
                        <div class="pricing-price">฿0<span><?= __('pricing_month') ?></span></div>
                        <ul class="pricing-features">
                            <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_1') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_4') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_2') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_free_feature_3') ?></li>
                            <li style="opacity:0.5;"><i class="fa fa-times" style="color:var(--danger);"></i> <?= __('pricing_free_feature_5') ?></li>
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
                            <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_6') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_pro_feature_7') ?></li>
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
                            <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_6') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_7') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_8') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_enterprise_feature_5') ?></li>
                        </ul>
                        <a href="login.php" class="btn btn-outline" style="width: 100%;"><?= __('pricing_contact') ?></a>
                    </div>
                </div>
            </div>
            
            <!-- Panel 2: Booking API Plans -->
            <div class="pricing-panel" id="panel-api">
                <div class="pricing-grid-4">
                    <!-- Trial -->
                    <div class="pricing-card">
                        <span class="plan-duration"><?= __('pricing_api_trial_duration') ?></span>
                        <h3><?= __('pricing_api_trial') ?></h3>
                        <div class="pricing-price"><?= __('pricing_api_trial_price') ?></div>
                        <ul class="pricing-features">
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_trial_feature_1') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_trial_feature_2') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_trial_feature_3') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_trial_feature_4') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_trial_feature_5') ?></li>
                        </ul>
                        <a href="login.php" class="btn btn-outline" style="width: 100%;"><?= __('nav_get_started') ?></a>
                    </div>
                    
                    <!-- Starter -->
                    <div class="pricing-card">
                        <span class="plan-duration"><?= __('pricing_monthly_yearly') ?></span>
                        <h3><?= __('pricing_api_starter') ?></h3>
                        <div class="pricing-price">฿990<span><?= __('pricing_month') ?></span></div>
                        <ul class="pricing-features">
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_starter_feature_1') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_starter_feature_2') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_starter_feature_3') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_starter_feature_4') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_starter_feature_5') ?></li>
                        </ul>
                        <a href="login.php" class="btn btn-outline" style="width: 100%;"><?= __('hero_cta_start') ?></a>
                    </div>
                    
                    <!-- Professional (Featured) -->
                    <div class="pricing-card featured">
                        <span class="pricing-badge"><?= __('pricing_popular') ?></span>
                        <span class="plan-duration"><?= __('pricing_monthly_yearly') ?></span>
                        <h3><?= __('pricing_api_professional') ?></h3>
                        <div class="pricing-price">฿2,990<span><?= __('pricing_month') ?></span></div>
                        <ul class="pricing-features">
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_pro_feature_1') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_pro_feature_2') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_pro_feature_3') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_pro_feature_4') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_pro_feature_5') ?></li>
                        </ul>
                        <a href="login.php" class="btn btn-white" style="width: 100%;"><?= __('hero_cta_start') ?></a>
                    </div>
                    
                    <!-- Enterprise -->
                    <div class="pricing-card">
                        <span class="plan-duration"><?= __('pricing_custom') ?></span>
                        <h3><?= __('pricing_api_enterprise') ?></h3>
                        <div class="pricing-price"><?= __('pricing_contact') ?></div>
                        <ul class="pricing-features">
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_ent_feature_1') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_ent_feature_2') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_ent_feature_3') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_ent_feature_4') ?></li>
                            <li><i class="fa fa-check"></i> <?= __('pricing_api_ent_feature_5') ?></li>
                        </ul>
                        <a href="login.php" class="btn btn-outline" style="width: 100%;"><?= __('pricing_contact') ?></a>
                    </div>
                </div>
                
                <!-- Comparison Table -->
                <div class="pricing-compare">
                    <h3><?= __('pricing_compare_title') ?></h3>
                    <div class="compare-table-wrapper">
                        <table class="compare-table">
                            <thead>
                                <tr>
                                    <th><?= __('pricing_compare_feature') ?></th>
                                    <th><?= __('pricing_api_trial') ?></th>
                                    <th><?= __('pricing_api_starter') ?></th>
                                    <th class="plan-highlight"><?= __('pricing_api_professional') ?></th>
                                    <th><?= __('pricing_api_enterprise') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= __('pricing_compare_price') ?></td>
                                    <td><?= __('pricing_api_trial_price') ?></td>
                                    <td>฿990<?= __('pricing_month') ?></td>
                                    <td class="plan-highlight">฿2,990<?= __('pricing_month') ?></td>
                                    <td><?= __('pricing_custom') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('pricing_compare_duration') ?></td>
                                    <td><?= __('pricing_api_trial_duration') ?></td>
                                    <td><?= __('pricing_monthly_yearly') ?></td>
                                    <td class="plan-highlight"><?= __('pricing_monthly_yearly') ?></td>
                                    <td><?= __('pricing_custom') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('pricing_compare_bookings') ?></td>
                                    <td>50</td>
                                    <td>500</td>
                                    <td class="plan-highlight">5,000</td>
                                    <td><?= __('pricing_unlimited') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('pricing_compare_api_keys') ?></td>
                                    <td>1</td>
                                    <td>3</td>
                                    <td class="plan-highlight">10</td>
                                    <td><?= __('pricing_unlimited') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('pricing_compare_channels') ?></td>
                                    <td><?= __('pricing_website_only') ?></td>
                                    <td><?= __('pricing_web_email') ?></td>
                                    <td class="plan-highlight"><?= __('pricing_all_channels') ?></td>
                                    <td><?= __('pricing_all_priority') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('pricing_compare_ai') ?></td>
                                    <td><?= __('pricing_ollama_only') ?></td>
                                    <td><?= __('pricing_ollama_1cloud') ?></td>
                                    <td class="plan-highlight"><?= __('pricing_all_ai') ?></td>
                                    <td><?= __('pricing_all_ai_custom') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('pricing_compare_support') ?></td>
                                    <td><?= __('pricing_community') ?></td>
                                    <td><?= __('pricing_email_support') ?></td>
                                    <td class="plan-highlight"><?= __('pricing_priority_support') ?></td>
                                    <td><?= __('pricing_dedicated_support') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Addon Note -->
            <div class="pricing-addon-note">
                <i class="fa fa-info-circle"></i>
                <?= __('pricing_addon_note') ?>
            </div>
        </div>
    </section>

    <!-- ── Sponsors, Adopters & Testimonials ───────────────────── -->
    <?php
    // Load sponsors/adopters with testimonials from DB (only if DB is available)
    $testimonials = [];
    if (isset($db) && $db->conn) {
        // Guard: columns may not exist yet if migration hasn't been run on this environment
        $colCheck = mysqli_query($db->conn,
            "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'api_subscriptions'
               AND COLUMN_NAME = 'sponsor_type'"
        );
        $colExists = $colCheck && (int)(mysqli_fetch_assoc($colCheck)['cnt'] ?? 0) > 0;

        if ($colExists) {
            $res = mysqli_query($db->conn,
                "SELECT s.sponsor_type, s.testimonial, s.testimonial_contact,
                        COALESCE(c.name_en, c.name_th, 'Company') AS company_name,
                        c.logo
                 FROM api_subscriptions s
                 JOIN company c ON c.id = s.company_id
                 WHERE s.show_on_landing = 1 AND s.sponsor_type IS NOT NULL
                 ORDER BY FIELD(s.sponsor_type,'sponsor','adopter'), c.name_en ASC"
            );
            while ($res && $row = mysqli_fetch_assoc($res)) {
                $testimonials[] = $row;
            }
        }
    }
    if (!empty($testimonials)):
    ?>
    <section style="padding:70px 0;background:#faf5ff;">
        <div class="section-container">
            <div style="text-align:center;margin-bottom:48px;">
                <span style="background:rgba(142,68,173,.1);color:#8e44ad;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;letter-spacing:.04em;">OUR COMMUNITY</span>
                <h2 style="margin:14px 0 8px;font-size:2rem;color:#1e1b4b;">Trusted by Tour Operators &amp; Sponsors</h2>
                <p style="color:#64748b;font-size:1rem;">Companies that adopted iACC and helped shape the platform.</p>
            </div>

            <!-- Sponsor / Adopter badges -->
            <?php
            $sponsors = array_filter($testimonials, fn($t) => $t['sponsor_type'] === 'sponsor');
            $adopters = array_filter($testimonials, fn($t) => $t['sponsor_type'] === 'adopter');
            ?>
            <?php if ($sponsors): ?>
            <div style="text-align:center;margin-bottom:32px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#8e44ad;margin-bottom:14px;">
                    <i class="fa fa-star"></i> Financial Sponsors
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:16px;justify-content:center;">
                <?php foreach ($sponsors as $s):
                    $name = htmlspecialchars($s['company_name'], ENT_QUOTES, 'UTF-8'); ?>
                    <div style="background:white;border-radius:12px;padding:14px 24px;box-shadow:0 2px 12px rgba(142,68,173,.1);border:1px solid #e9d5ff;display:flex;align-items:center;gap:10px;">
                        <?php if ($s['logo']): ?>
                        <img src="<?= htmlspecialchars($s['logo'], ENT_QUOTES) ?>" alt="<?= $name ?>" style="height:32px;object-fit:contain;">
                        <?php else: ?>
                        <span style="font-size:20px;color:#8e44ad;">&#x2B50;</span>
                        <?php endif; ?>
                        <span style="font-weight:700;color:#1e1b4b;font-size:15px;"><?= $name ?></span>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Testimonial cards -->
            <?php if (array_filter($testimonials, fn($t) => $t['testimonial'])): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;margin-bottom:40px;">
            <?php foreach ($testimonials as $t):
                if (empty($t['testimonial'])) continue;
                $isSponsor = $t['sponsor_type'] === 'sponsor';
                $badgeStyle = $isSponsor
                    ? 'background:#fef9c3;color:#854d0e;'
                    : 'background:#ede9fe;color:#5b21b6;';
                $icon = $isSponsor ? '&#x2B50;' : '&#x1F49C;';
                $label = $isSponsor ? 'Sponsor' : 'Adopter';
                $name  = htmlspecialchars($t['company_name'], ENT_QUOTES, 'UTF-8');
                $quote = htmlspecialchars($t['testimonial'], ENT_QUOTES, 'UTF-8');
                $who   = htmlspecialchars($t['testimonial_contact'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <div style="background:white;border-radius:16px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #f3e8ff;display:flex;flex-direction:column;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <span style="font-weight:700;font-size:15px;color:#1e1b4b;"><?= $name ?></span>
                    <span style="<?= $badgeStyle ?>padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;"><?= $icon ?> <?= $label ?></span>
                </div>
                <p style="color:#475569;font-size:14px;line-height:1.7;flex:1;font-style:italic;">
                    &ldquo;<?= $quote ?>&rdquo;
                </p>
                <?php if ($who): ?>
                <p style="margin:16px 0 0;font-size:13px;font-weight:600;color:#8e44ad;">— <?= $who ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($adopters): ?>
            <div style="text-align:center;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:12px;">
                    <i class="fa fa-heart"></i> Project Adopters
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">
                <?php foreach ($adopters as $a):
                    $name = htmlspecialchars($a['company_name'], ENT_QUOTES, 'UTF-8'); ?>
                    <span style="background:white;border:1px solid #ddd6fe;color:#5b21b6;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;">
                        &#x1F49C; <?= $name ?>
                    </span>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

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
                    <li><a href="privacy.php?lang=<?= $lang ?>"><?= __('footer_privacy') ?></a></li>
                    <li><a href="terms.php?lang=<?= $lang ?>"><?= __('footer_terms') ?></a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4><?= __('footer_developers') ?></h4>
                <ul>
                    <li><a href="api-docs.php?lang=<?= $lang ?>"><?= __('footer_api_docs') ?></a></li>
                    <li><a href="template-demo.php?lang=<?= $lang ?>"><?= __('footer_template_demo') ?></a></li>
                    <li><a href="template-howto.php?lang=<?= $lang ?>"><?= __('footer_howto') ?></a></li>
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
        
        // Pricing tab switcher
        function switchPricingTab(tab) {
            // Toggle tab active state
            document.getElementById('tab-accounting').classList.toggle('active', tab === 'accounting');
            document.getElementById('tab-api').classList.toggle('active', tab === 'api');
            
            // Toggle panel visibility
            document.getElementById('panel-accounting').classList.toggle('active', tab === 'accounting');
            document.getElementById('panel-api').classList.toggle('active', tab === 'api');
        }
    </script>
</body>
</html>
