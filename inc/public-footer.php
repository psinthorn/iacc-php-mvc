<?php
/**
 * Shared Public Footer Component
 * Include this in all public-facing pages (landing, about, terms, privacy, contact, roadmap, careers, blog, press)
 */
?>
<style>
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
        transition: background 0.3s;
    }
    
    .social-links a:hover {
        background: var(--primary);
    }
    
    @media (max-width: 992px) {
        .footer-container {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 576px) {
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

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <h3><span style="color: var(--primary);">iACC</span></h3>
            <p><?= $lang === 'th' ? 'ซอฟต์แวร์บัญชีที่ดีที่สุดสำหรับธุรกิจของคุณ' : 'The best accounting software for your business' ?></p>
        </div>
        
        <div class="footer-column">
            <h4><?= $lang === 'th' ? 'ผลิตภัณฑ์' : 'Product' ?></h4>
            <ul>
                <li><a href="landing.php?lang=<?= $lang ?>#features"><?= $lang === 'th' ? 'ฟีเจอร์' : 'Features' ?></a></li>
                <li><a href="landing.php?lang=<?= $lang ?>#pricing"><?= $lang === 'th' ? 'ราคา' : 'Pricing' ?></a></li>
                <li><a href="blog.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'อัปเดต' : 'Updates' ?></a></li>
                <li><a href="roadmap.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'แผนพัฒนา' : 'Roadmap' ?></a></li>
            </ul>
        </div>
        
        <div class="footer-column">
            <h4><?= $lang === 'th' ? 'บริษัท' : 'Company' ?></h4>
            <ul>
                <li><a href="about.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'เกี่ยวกับเรา' : 'About Us' ?></a></li>
                <li><a href="careers.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'ร่วมงาน' : 'Careers' ?></a></li>
                <li><a href="blog.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'บล็อก' : 'Blog' ?></a></li>
                <li><a href="press.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'ข่าว' : 'Press' ?></a></li>
            </ul>
        </div>
        
        <div class="footer-column">
            <h4><?= $lang === 'th' ? 'สนับสนุน' : 'Support' ?></h4>
            <ul>
                <li><a href="login.php"><?= $lang === 'th' ? 'ศูนย์ช่วยเหลือ' : 'Help Center' ?></a></li>
                <li><a href="contact.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'ติดต่อเรา' : 'Contact' ?></a></li>
                <li><a href="privacy.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'ความเป็นส่วนตัว' : 'Privacy' ?></a></li>
                <li><a href="terms.php?lang=<?= $lang ?>"><?= $lang === 'th' ? 'ข้อกำหนด' : 'Terms' ?></a></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> iACC. <?= $lang === 'th' ? 'สงวนลิขสิทธิ์' : 'All rights reserved' ?>. | <?= $lang === 'th' ? 'พัฒนาโดย' : 'Developed by' ?> <a href="#" style="color: var(--primary);">F2 Co.,Ltd.</a></p>
        <div class="social-links">
            <a href="#"><i class="fa fa-facebook"></i></a>
            <a href="#"><i class="fa fa-twitter"></i></a>
            <a href="#"><i class="fa fa-linkedin"></i></a>
            <a href="#"><i class="fa fa-github"></i></a>
        </div>
    </div>
</footer>
