<?php
/**
 * Shared Developer Tools Styles & Functions
 * Common styling for all developer tools pages
 */

// Check Developer role access
function check_dev_tools_access() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Check for Developer role (preferred) or fall back to user_level for backward compatibility
    $has_developer_role = function_exists('has_role') ? has_role('Developer') : false;
    $user_level = $_SESSION['user_level'] ?? 0;
    
    if (!$has_developer_role && $user_level < 2) {
        echo "<script>alert('Access Denied. Developer role required.');window.location='index.php';</script>";
        exit;
    }
    
    // If user has level >= 2 but no Developer role, still allow (backward compatibility)
    // But log a warning that they should have Developer role assigned
    if ($user_level >= 2 && !$has_developer_role) {
        error_log("Warning: User ID " . ($_SESSION['user_id'] ?? 'unknown') . " accessed dev tools without Developer role (using user_level fallback)");
    }
}

// Check Docker tools access (for Docker-specific pages)
function check_docker_tools_access() {
    // First check dev tools access
    check_dev_tools_access();
    
    // Then check if Docker tools are enabled
    $docker_enabled = function_exists('is_docker_tools_enabled') ? is_docker_tools_enabled() : false;
    
    if (!$docker_enabled) {
        $docker_status = function_exists('get_docker_tools_status') ? get_docker_tools_status() : ['mode_text' => 'Unknown'];
        $user_level = $_SESSION['user_level'] ?? 0;
        
        echo get_dev_tools_css();
        echo <<<HTML
        <div style="min-height: 100vh; background: #ffffff; padding: 40px;">
            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 12px; padding: 50px; max-width: 600px; margin: 40px auto; text-align: center;">
                <i class="fa fa-server" style="font-size: 64px; color: #6c757d; margin-bottom: 25px;"></i>
                <h2 style="color: #333; margin-bottom: 15px;">Docker Tools Disabled</h2>
                <p style="color: #6c757d; margin-bottom: 20px;">
                    This feature is currently disabled.<br>
                    <strong style="color: #333;">Current mode:</strong> {$docker_status['mode_text']}
                </p>
                <p style="color: #6c757d; margin-bottom: 30px;">
                    Docker tools are designed for Docker environments only.<br>
                    If running on cPanel or non-Docker server, this feature is not available.
                </p>
HTML;
        
        if ($user_level >= 2) {
            echo <<<HTML
                <a href="index.php?page=dashboard" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">
                    <i class="fa fa-cog"></i> Go to Dashboard to Enable
                </a>
                <p style="color: #6c757d; font-size: 12px; margin-top: 20px;">
                    Change Docker Tools mode in Dashboard → Developer Tools panel
                </p>
HTML;
        } else {
            echo <<<HTML
                <p style="color: #6c757d; font-size: 12px;">
                    Contact Super Admin to enable Docker tools.
                </p>
HTML;
        }
        
        echo <<<HTML
            </div>
        </div>
HTML;
        exit;
    }
}

// Get developer tools header HTML
function get_dev_tools_header($title, $subtitle, $icon = 'fa-wrench', $color = '#e74c3c') {
    return <<<HTML
    <div class="dev-tools-header" style="background: linear-gradient(135deg, {$color}, #c0392b);">
        <div class="header-content">
            <div class="header-icon">
                <i class="fa {$icon}"></i>
            </div>
            <div class="header-text">
                <h1>{$title}</h1>
                <p class="subtitle">{$subtitle}</p>
            </div>
        </div>
        <div class="header-nav">
            <a href="index.php?page=dashboard" class="nav-btn"><i class="fa fa-arrow-left"></i> Dashboard</a>
            <a href="index.php?page=test_crud" class="nav-btn"><i class="fa fa-database"></i> CRUD</a>
            <a href="index.php?page=debug_session" class="nav-btn"><i class="fa fa-key"></i> Session</a>
            <a href="index.php?page=debug_invoice" class="nav-btn"><i class="fa fa-file-text-o"></i> Invoice</a>
            <a href="index.php?page=test_rbac" class="nav-btn"><i class="fa fa-shield"></i> RBAC</a>
            <a href="index.php?page=dev_roadmap" class="nav-btn"><i class="fa fa-road"></i> Roadmap</a>
            <a href="index.php?page=docker_test" class="nav-btn"><i class="fa fa-cloud"></i> Docker</a>
            <a href="index.php?page=test_containers" class="nav-btn"><i class="fa fa-cube"></i> Containers</a>
            <a href="index.php?page=api_lang_debug" class="nav-btn"><i class="fa fa-language"></i> Lang</a>
        </div>
    </div>
HTML;
}

// Get developer tools CSS
function get_dev_tools_css() {
    return <<<CSS
    <style>
        :root {
            --primary: #e74c3c;
            --primary-dark: #c0392b;
            --success: #27ae60;
            --success-light: #d4edda;
            --warning: #f39c12;
            --warning-light: #fff3cd;
            --danger: #e74c3c;
            --danger-light: #f8d7da;
            --info: #3498db;
            --info-light: #d1ecf1;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #dee2e6;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .dev-tools-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .dev-tools-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(231, 76, 60, 0.3);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .header-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .header-text h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .header-text .subtitle {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .header-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .nav-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .nav-btn i {
            margin-right: 5px;
        }
        
        /* Cards */
        .card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border-bottom: 1px solid var(--border);
            padding: 18px 22px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header i {
            color: var(--primary);
            font-size: 18px;
        }
        
        .card-header .badge {
            margin-left: auto;
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .card-body {
            padding: 22px;
        }
        
        /* Test Results */
        .test-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .test-section h2 {
            color: var(--dark);
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-section h2 i {
            color: var(--primary);
        }
        
        .test-section h3 {
            color: var(--gray);
            font-size: 14px;
            font-weight: 600;
            margin: 15px 0 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pass, .status-success {
            background: var(--success-light);
            color: var(--success);
        }
        
        .status-fail, .status-error {
            background: var(--danger-light);
            color: var(--danger);
        }
        
        .status-warn, .status-warning {
            background: var(--warning-light);
            color: #856404;
        }
        
        .status-info {
            background: var(--info-light);
            color: var(--info);
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--dark), #34495e);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table th:first-child {
            border-radius: 8px 0 0 0;
        }
        
        .data-table th:last-child {
            border-radius: 0 8px 0 0;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:nth-child(even) {
            background: #fafbfc;
        }
        
        .data-table tbody tr:nth-child(even):hover {
            background: #f0f1f2;
        }
        
        /* Code blocks */
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .code-block .key {
            color: #9cdcfe;
        }
        
        .code-block .value {
            color: #ce9178;
        }
        
        .code-block .string {
            color: #ce9178;
        }
        
        .code-block .number {
            color: #b5cea8;
        }
        
        .code-block .boolean {
            color: #569cd6;
        }
        
        /* Info boxes */
        .info-box {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .info-box i {
            font-size: 18px;
            margin-top: 2px;
        }
        
        .info-box.success {
            background: var(--success-light);
            border-left: 4px solid var(--success);
        }
        
        .info-box.success i { color: var(--success); }
        
        .info-box.warning {
            background: var(--warning-light);
            border-left: 4px solid var(--warning);
        }
        
        .info-box.warning i { color: var(--warning); }
        
        .info-box.danger {
            background: var(--danger-light);
            border-left: 4px solid var(--danger);
        }
        
        .info-box.danger i { color: var(--danger); }
        
        .info-box.info {
            background: var(--info-light);
            border-left: 4px solid var(--info);
        }
        
        .info-box.info i { color: var(--info); }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }
        
        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--gray);
        }
        
        .stat-card.success .stat-icon { color: var(--success); }
        .stat-card.warning .stat-icon { color: var(--warning); }
        .stat-card.danger .stat-icon { color: var(--danger); }
        .stat-card.info .stat-icon { color: var(--info); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Buttons */
        .btn-dev {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--dark);
        }
        
        .btn-outline:hover {
            background: var(--light);
            text-decoration: none;
        }
        
        /* Key-value display */
        .kv-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .kv-item {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        
        .kv-item:last-child {
            border-bottom: none;
        }
        
        .kv-key {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px;
        }
        
        .kv-value {
            color: var(--gray);
            word-break: break-all;
        }
        
        .kv-value.mono {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
        }
        
        /* Container state colors */
        .state-running { color: var(--success); }
        .state-exited { color: var(--danger); }
        .state-paused { color: var(--warning); }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .test-section, .card, .stat-card {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Summary box */
        .summary-box {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .summary-box h3 {
            margin: 0 0 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .summary-stat {
            text-align: center;
        }
        
        .summary-stat-value {
            font-size: 36px;
            font-weight: 700;
        }
        
        .summary-stat-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
        }
        
        .summary-stat.pass .summary-stat-value { color: #2ecc71; }
        .summary-stat.warn .summary-stat-value { color: #f1c40f; }
        .summary-stat.fail .summary-stat-value { color: #e74c3c; }
        .summary-stat.info .summary-stat-value { color: #3498db; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .header-nav {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .kv-item {
                flex-direction: column;
                gap: 5px;
            }
            
            .kv-key {
                min-width: auto;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
CSS;
}

// Helper function to format JSON for display
function format_json_html($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $json = htmlspecialchars($json);
    // Color code JSON
    $json = preg_replace('/"([^"]+)":/','<span class="key">"$1"</span>:', $json);
    $json = preg_replace('/: "([^"]*)"/', ': <span class="string">"$1"</span>', $json);
    $json = preg_replace('/: (\d+)/', ': <span class="number">$1</span>', $json);
    $json = preg_replace('/: (true|false)/', ': <span class="boolean">$1</span>', $json);
    return $json;
}

// Get app version from README.md
function get_dev_tools_version() {
    $readme_path = __DIR__ . '/../README.md';
    $version = '1.0';
    $last_updated = date('Y');
    
    if (file_exists($readme_path)) {
        $content = file_get_contents($readme_path);
        
        if (preg_match('/\*\*Version\*\*:\s*([0-9.]+)/i', $content, $matches)) {
            $version = $matches[1];
        }
        
        if (preg_match('/\*\*Last Updated\*\*:\s*([A-Za-z]+ \d+, \d{4})/i', $content, $matches)) {
            $last_updated = $matches[1];
        }
    }
    
    return ['version' => $version, 'last_updated' => $last_updated];
}

// Get footer HTML for dev tools pages
function get_dev_tools_footer() {
    $app_info = get_dev_tools_version();
    $current_year = date('Y');
    
    return <<<HTML
    <style>
    .dev-footer {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 16px 24px;
        margin-top: 40px;
        font-size: 13px;
        color: #6c757d;
    }
    .dev-footer-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dev-footer-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dev-footer-logo {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
    }
    .dev-footer-version {
        background: #e9ecef;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        color: #495057;
    }
    </style>
    <footer class="dev-footer">
        <div class="dev-footer-content">
            <div class="dev-footer-left">
                <div class="dev-footer-logo">iA</div>
                <span>© {$current_year} iACC. All rights reserved.</span>
            </div>
            <div>
                <span class="dev-footer-version">v{$app_info['version']} • {$app_info['last_updated']}</span>
                <span style="margin-left: 16px;">Developed by <a href="https://www.f2.co.th" target="_blank" style="color: #e74c3c; text-decoration: none; font-weight: 500;">F2 Co.,Ltd.</a></span>
            </div>
        </div>
    </footer>
HTML;
}
?>
