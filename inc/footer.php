<?php
/**
 * Footer Component
 * Shows copyright, developer info, and auto-versioning from README.md
 */

// Get version from README.md
function get_app_version() {
    $readme_path = __DIR__ . '/../README.md';
    $version = '1.0';
    $last_updated = date('Y');
    
    if (file_exists($readme_path)) {
        $content = file_get_contents($readme_path);
        
        // Extract version: **Version**: X.X
        if (preg_match('/\*\*Version\*\*:\s*([0-9.]+)/i', $content, $matches)) {
            $version = $matches[1];
        }
        
        // Extract last updated: **Last Updated**: Month Day, Year
        if (preg_match('/\*\*Last Updated\*\*:\s*([A-Za-z]+ \d+, \d{4})/i', $content, $matches)) {
            $last_updated = $matches[1];
        }
    }
    
    return [
        'version' => $version,
        'last_updated' => $last_updated
    ];
}

$app_info = get_app_version();
$current_year = date('Y');
?>

<style>
.app-footer {
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
    margin-top: 40px;
    font-size: 13px;
    color: #6c757d;
}

.footer-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.footer-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.footer-logo {
    padding: 4px 8px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.5px;
}

.footer-copyright {
    color: #495057;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.footer-version {
    background: #e9ecef;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: #495057;
}

.footer-developer {
    color: #6c757d;
}

.footer-developer a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.footer-developer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-right {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<footer class="app-footer">
    <div class="footer-content">
        <div class="footer-left">
            <div class="footer-logo">iACC</div>
            <span class="footer-copyright">
                © <?= $current_year ?> iACC. All rights reserved.
            </span>
        </div>
        <div class="footer-right">
            <span class="footer-version">
                v<?= htmlspecialchars($app_info['version']) ?> • <?= htmlspecialchars($app_info['last_updated']) ?>
            </span>
            <span class="footer-developer">
                Developed by <a href="https://www.f2.co.th" target="_blank">F2 Co.,Ltd.</a>
            </span>
        </div>
    </div>
</footer>

<?php
// AI Chat Widget - Only show for logged-in users
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])):
?>
<!-- AI Chat Widget -->
<link rel="stylesheet" href="css/ai-chat.css?v=<?php echo time(); ?>">
<script src="js/ai-chat-widget.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
