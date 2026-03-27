<?php
// Error reporting settings
ini_set('display_errors', 1); // Show errors in browser for debug
ini_set('log_errors', 1);     // Enable error logging
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log'); // Log file path
error_reporting(E_ALL);       // Report all errors
/**
 * Contact Page
 */
session_start();

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';

function __($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = $lang === 'th' ? 'กรุณากรอกข้อมูลให้ครบทุกช่อง' : 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang === 'th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email address';
    } else {
        // In production, send email here
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('nav_contact') ?> - iACC</title>
    
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
            --success: #27ae60;
            --danger: #e74c3c;
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
        
        .contact-section {
            max-width: 1200px;
            margin: -40px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .contact-form-card, .contact-info-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .contact-form-card h2, .contact-info-card h2 {
            font-size: 24px;
            margin-bottom: 25px;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(142,68,173,0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(142,68,173,0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .contact-icon i {
            color: white;
            font-size: 20px;
        }
        
        .contact-item h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .contact-item p {
            color: #666;
            line-height: 1.6;
        }
        
        .contact-item a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .social-link {
            width: 45px;
            height: 45px;
            background: #f0f0f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social-link:hover {
            background: var(--primary);
            color: white;
        }
        
        .map-container {
            margin-top: 30px;
            border-radius: 12px;
            overflow: hidden;
            height: 200px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .map-placeholder {
            text-align: center;
            color: #888;
        }
        
        .map-placeholder i {
            font-size: 40px;
            margin-bottom: 10px;
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
            .contact-section { grid-template-columns: 1fr; }
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
                <li><a href="contact.php" style="color: white; font-weight: 600;"><?= __('nav_contact') ?></a></li>
                <li><a href="login.php"><?= __('nav_sign_in') ?></a></li>
            </ul>
        </div>
    </nav>
    
    <section class="hero">
        <h1><?= __('nav_contact') ?></h1>
        <p><?= $lang === 'th' ? 'มีคำถามหรือข้อเสนอแนะ? เรายินดีรับฟัง ติดต่อเราได้เลย!' : 'Have questions or feedback? We\'d love to hear from you. Get in touch!' ?></p>
    </section>
    
    <div class="contact-section">
        <div class="contact-form-card">
            <h2><?= $lang === 'th' ? 'ส่งข้อความถึงเรา' : 'Send us a Message' ?></h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i>
                    <?= $lang === 'th' ? 'ส่งข้อความสำเร็จ! เราจะติดต่อกลับเร็วๆ นี้' : 'Message sent successfully! We\'ll get back to you soon.' ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name"><?= $lang === 'th' ? 'ชื่อของคุณ' : 'Your Name' ?> *</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email"><?= $lang === 'th' ? 'อีเมล' : 'Email Address' ?> *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="subject"><?= $lang === 'th' ? 'หัวข้อ' : 'Subject' ?> *</label>
                    <select id="subject" name="subject" required>
                        <option value=""><?= $lang === 'th' ? '-- เลือกหัวข้อ --' : '-- Select Subject --' ?></option>
                        <option value="sales" <?= ($_POST['subject'] ?? '') === 'sales' ? 'selected' : '' ?>><?= $lang === 'th' ? 'สอบถามการขาย' : 'Sales Inquiry' ?></option>
                        <option value="support" <?= ($_POST['subject'] ?? '') === 'support' ? 'selected' : '' ?>><?= $lang === 'th' ? 'ฝ่ายสนับสนุน' : 'Technical Support' ?></option>
                        <option value="billing" <?= ($_POST['subject'] ?? '') === 'billing' ? 'selected' : '' ?>><?= $lang === 'th' ? 'เรื่องการเรียกเก็บเงิน' : 'Billing Question' ?></option>
                        <option value="partnership" <?= ($_POST['subject'] ?? '') === 'partnership' ? 'selected' : '' ?>><?= $lang === 'th' ? 'โอกาสเป็นพันธมิตร' : 'Partnership Opportunity' ?></option>
                        <option value="feedback" <?= ($_POST['subject'] ?? '') === 'feedback' ? 'selected' : '' ?>><?= $lang === 'th' ? 'ข้อเสนอแนะ' : 'Feedback' ?></option>
                        <option value="other" <?= ($_POST['subject'] ?? '') === 'other' ? 'selected' : '' ?>><?= $lang === 'th' ? 'อื่นๆ' : 'Other' ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message"><?= $lang === 'th' ? 'ข้อความ' : 'Message' ?> *</label>
                    <textarea id="message" name="message" required placeholder="<?= $lang === 'th' ? 'เขียนข้อความของคุณที่นี่...' : 'Write your message here...' ?>"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fa fa-paper-plane"></i> <?= $lang === 'th' ? 'ส่งข้อความ' : 'Send Message' ?>
                </button>
            </form>
        </div>
        
        <div class="contact-info-card">
            <h2><?= $lang === 'th' ? 'ข้อมูลติดต่อ' : 'Contact Information' ?></h2>
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fa fa-envelope"></i>
                </div>
                <div>
                    <h3><?= $lang === 'th' ? 'อีเมล' : 'Email' ?></h3>
                    <p>
                        <a href="mailto:support@iacc.com">support@iacc.com</a><br>
                        <a href="mailto:sales@iacc.com">sales@iacc.com</a>
                    </p>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fa fa-phone"></i>
                </div>
                <div>
                    <h3><?= $lang === 'th' ? 'โทรศัพท์' : 'Phone' ?></h3>
                    <p>
                        +66 2 123 4567<br>
                        <small style="color: #888;"><?= $lang === 'th' ? 'จันทร์-ศุกร์, 9:00-18:00 น.' : 'Mon-Fri, 9:00 AM - 6:00 PM' ?></small>
                    </p>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fa fa-map-marker"></i>
                </div>
                <div>
                    <h3><?= $lang === 'th' ? 'ที่อยู่' : 'Address' ?></h3>
                    <p>
                        <?= $lang === 'th' ? '123 อาคารสำนักงาน ชั้น 15<br>ถนนสีลม แขวงสีลม<br>เขตบางรัก กรุงเทพฯ 10500' : '123 Office Building, Floor 15<br>Silom Road, Silom<br>Bang Rak, Bangkok 10500' ?>
                    </p>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div>
                    <h3><?= $lang === 'th' ? 'เวลาทำการ' : 'Business Hours' ?></h3>
                    <p>
                        <?= $lang === 'th' ? 'จันทร์ - ศุกร์: 9:00 - 18:00<br>เสาร์: 9:00 - 12:00<br>อาทิตย์: ปิดทำการ' : 'Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 9:00 AM - 12:00 PM<br>Sunday: Closed' ?>
                    </p>
                </div>
            </div>
            
            <div class="social-links">
                <a href="#" class="social-link" title="Facebook"><i class="fa fa-facebook"></i></a>
                <a href="#" class="social-link" title="Twitter"><i class="fa fa-twitter"></i></a>
                <a href="#" class="social-link" title="LinkedIn"><i class="fa fa-linkedin"></i></a>
                <a href="#" class="social-link" title="LINE"><i class="fa fa-comment"></i></a>
            </div>
            
            <div class="map-container">
                <div class="map-placeholder">
                    <i class="fa fa-map-marker"></i>
                    <p><?= $lang === 'th' ? 'แผนที่สำนักงาน' : 'Office Location Map' ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/inc/public-footer.php'; ?>
</body>
</html>
