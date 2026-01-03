<?php
/**
 * Help & Support Page
 * Provides FAQs, documentation, and contact information
 */
require_once("inc/security.php");
?>

<style>
.help-container {
    max-width: 1000px;
    margin: 0 auto;
}

.help-header {
    text-align: center;
    margin-bottom: 40px;
}

.help-header h1 {
    font-size: 32px;
    margin: 0 0 12px 0;
    color: #333;
}

.help-header p {
    color: #6c757d;
    font-size: 16px;
    margin: 0;
}

.help-search {
    max-width: 500px;
    margin: 25px auto 0;
    position: relative;
}

.help-search input {
    width: 100%;
    padding: 14px 20px 14px 50px;
    border: 2px solid #e9ecef;
    border-radius: 50px;
    font-size: 15px;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.help-search input:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 4px rgba(142, 68, 173, 0.1);
}

.help-search i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #8e44ad;
    font-size: 18px;
}

.help-quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

@media (max-width: 992px) {
    .help-quick-links {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .help-quick-links {
        grid-template-columns: 1fr;
    }
}

.quick-link-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.quick-link-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.quick-link-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}

.quick-link-icon i {
    color: white;
    font-size: 24px;
}

.quick-link-card h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
}

.quick-link-card p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

.help-section {
    margin-bottom: 40px;
}

.help-section-title {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.help-section-title i {
    color: #8e44ad;
}

/* FAQ Accordion */
.faq-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.faq-item {
    border-bottom: 1px solid #f0f0f0;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-question {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s;
}

.faq-question:hover {
    background: #f8f9fa;
}

.faq-question h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 500;
    color: #333;
    flex: 1;
    padding-right: 20px;
}

.faq-question i {
    color: #8e44ad;
    transition: transform 0.3s;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    display: none;
    padding: 0 24px 20px;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.7;
}

.faq-item.active .faq-answer {
    display: block;
}

/* Contact Cards */
.contact-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .contact-cards {
        grid-template-columns: 1fr;
    }
}

.contact-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.contact-card i {
    font-size: 36px;
    color: #8e44ad;
    margin-bottom: 15px;
}

.contact-card h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
}

.contact-card p {
    margin: 0 0 15px 0;
    color: #6c757d;
    font-size: 14px;
}

.contact-card a {
    color: #8e44ad;
    text-decoration: none;
    font-weight: 500;
}

.contact-card a:hover {
    text-decoration: underline;
}

/* Documentation */
.doc-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .doc-grid {
        grid-template-columns: 1fr;
    }
}

.doc-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    gap: 16px;
    align-items: flex-start;
    transition: transform 0.3s, box-shadow 0.3s;
}

.doc-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.doc-icon {
    width: 50px;
    height: 50px;
    background: #f3e8ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.doc-icon i {
    color: #8e44ad;
    font-size: 22px;
}

.doc-content h4 {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 600;
}

.doc-content p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

/* Version Info */
.version-info {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: white;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    margin-top: 40px;
}

.version-info h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
}

.version-info p {
    margin: 0;
    opacity: 0.9;
}

.version-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    margin-top: 10px;
}
</style>

<div class="help-container">
    <div class="help-header">
        <h1><i class="fa fa-life-ring"></i> <?= isset($xml->help_center) ? $xml->help_center : 'Help Center' ?></h1>
        <p><?= isset($xml->help_desc) ? $xml->help_desc : 'Find answers to common questions and get support' ?></p>
        
        <div class="help-search">
            <i class="fa fa-search"></i>
            <input type="text" placeholder="<?= isset($xml->search_help) ? $xml->search_help : 'Search for help...' ?>" id="helpSearch">
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="help-quick-links">
        <a href="#faq" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-question"></i>
            </div>
            <h3><?= isset($xml->faq) ? $xml->faq : 'FAQs' ?></h3>
            <p><?= isset($xml->faq_desc) ? $xml->faq_desc : 'Frequently asked questions' ?></p>
        </a>
        
        <a href="#docs" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-book"></i>
            </div>
            <h3><?= isset($xml->documentation) ? $xml->documentation : 'Documentation' ?></h3>
            <p><?= isset($xml->documentation_desc) ? $xml->documentation_desc : 'User guides and manuals' ?></p>
        </a>
        
        <a href="#contact" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-envelope"></i>
            </div>
            <h3><?= isset($xml->contact_support) ? $xml->contact_support : 'Contact Support' ?></h3>
            <p><?= isset($xml->contact_support_desc) ? $xml->contact_support_desc : 'Get help from our team' ?></p>
        </a>
        
        <a href="index.php?page=dashboard" class="quick-link-card">
            <div class="quick-link-icon">
                <i class="fa fa-play-circle"></i>
            </div>
            <h3><?= isset($xml->getting_started) ? $xml->getting_started : 'Getting Started' ?></h3>
            <p><?= isset($xml->getting_started_desc) ? $xml->getting_started_desc : 'Quick start guide' ?></p>
        </a>
    </div>
    
    <!-- FAQ Section -->
    <div class="help-section" id="faq">
        <h2 class="help-section-title">
            <i class="fa fa-question-circle"></i>
            <?= isset($xml->frequently_asked) ? $xml->frequently_asked : 'Frequently Asked Questions' ?>
        </h2>
        
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4>How do I create a new invoice?</h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    To create a new invoice, navigate to the Invoice section from the sidebar menu. Click on "Create Invoice" or the "+ New" button. Fill in the customer details, add line items, set the due date, and click "Save". You can then print or email the invoice directly to your customer.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4>How do I set up payment gateway?</h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Payment gateways can be configured by administrators. Go to Admin Tools > Payment Gateway Config. Enter your PayPal or Stripe API credentials. Use Test Mode to verify the integration before going live. Once configured, customers can pay invoices online.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4>How do I change my password?</h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Click on your profile picture in the top right corner and select "My Profile". In the profile page, find the "Change Password" section. Enter your current password, then enter and confirm your new password. Click "Update Password" to save the changes.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4>How do I switch between companies?</h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Administrators and Super Admins can switch between companies from the dashboard. Click on the company name in the top navbar or go to Dashboard > Select a company from the list. Regular users are assigned to a specific company and cannot switch.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4>How do I change the language?</h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Go to Settings from your profile dropdown menu. In the Language & Region section, select your preferred language (English or Thai). The page will reload with your selected language. You can also use the language switcher in the top navbar.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h4>How do I generate reports?</h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Navigate to Reports from the sidebar menu. Select the type of report you want to generate (Sales, Payments, etc.). Set your date range and filters, then click "Generate Report". Reports can be exported to PDF or Excel for further analysis.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documentation Section -->
    <div class="help-section" id="docs">
        <h2 class="help-section-title">
            <i class="fa fa-book"></i>
            <?= isset($xml->documentation) ? $xml->documentation : 'Documentation' ?>
        </h2>
        
        <div class="doc-grid">
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="doc-content">
                    <h4>Invoice Management</h4>
                    <p>Learn how to create, edit, and manage invoices, quotations, and receipts.</p>
                </div>
            </div>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-credit-card"></i>
                </div>
                <div class="doc-content">
                    <h4>Payment Processing</h4>
                    <p>Guide to accepting payments via PayPal, Stripe, and bank transfers.</p>
                </div>
            </div>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="doc-content">
                    <h4>User Management</h4>
                    <p>Manage users, roles, and permissions for your organization.</p>
                </div>
            </div>
            
            <div class="doc-card">
                <div class="doc-icon">
                    <i class="fa fa-bar-chart"></i>
                </div>
                <div class="doc-content">
                    <h4>Reports & Analytics</h4>
                    <p>Generate and export financial reports for your business.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Section -->
    <div class="help-section" id="contact">
        <h2 class="help-section-title">
            <i class="fa fa-headphones"></i>
            <?= isset($xml->contact_us) ? $xml->contact_us : 'Contact Us' ?>
        </h2>
        
        <div class="contact-cards">
            <div class="contact-card">
                <i class="fa fa-envelope"></i>
                <h3>Email Support</h3>
                <p>Get help via email within 24 hours</p>
                <a href="mailto:support@iacc.com">support@iacc.com</a>
            </div>
            
            <div class="contact-card">
                <i class="fa fa-phone"></i>
                <h3>Phone Support</h3>
                <p>Mon-Fri, 9:00 AM - 6:00 PM</p>
                <a href="tel:+6621234567">+66 2 123 4567</a>
            </div>
            
            <div class="contact-card">
                <i class="fa fa-comments"></i>
                <h3>Live Chat</h3>
                <p>Chat with our support team</p>
                <a href="#" onclick="alert('Live chat coming soon!')">Start Chat</a>
            </div>
        </div>
    </div>
    
    <!-- Version Info -->
    <div class="version-info">
        <h3>iACC Accounting System</h3>
        <p>Professional accounting management for modern businesses</p>
        <span class="version-badge">Version 2.8</span>
    </div>
</div>

<script>
function toggleFaq(element) {
    const faqItem = element.parentElement;
    const wasActive = faqItem.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't already open
    if (!wasActive) {
        faqItem.classList.add('active');
    }
}

// Search functionality
document.getElementById('helpSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question h4').textContent.toLowerCase();
        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
        
        if (question.includes(query) || answer.includes(query)) {
            item.style.display = 'block';
            if (query.length > 2) {
                item.classList.add('active');
            }
        } else {
            item.style.display = query.length > 0 ? 'none' : 'block';
        }
    });
});
</script>
