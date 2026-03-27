<?php
/**
 * Developer Roadmap Page
 * Displays the PROJECT_ROADMAP_2026.md in a styled format
 * 
 * Access: Developer role required
 * Created: January 9, 2026
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/dev-tools-style.php");

$db = new DbConn($config);
$db->checkSecurity();

// Require Developer access
check_dev_tools_access();

// Read the roadmap markdown file
$roadmapFile = __DIR__ . '/docs/PROJECT_ROADMAP_2026.md';
$roadmapContent = file_exists($roadmapFile) ? file_get_contents($roadmapFile) : 'Roadmap file not found.';

// Simple markdown to HTML conversion
function markdown_to_html($text) {
    // Headers
    $text = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // Bold and italic
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    
    // Code blocks
    $text = preg_replace('/```(\w+)?\n(.*?)```/s', '<pre class="code-block"><code>$2</code></pre>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $text);
    
    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $text);
    
    // Horizontal rules
    $text = preg_replace('/^---+$/m', '<hr>', $text);
    
    // Lists
    $text = preg_replace('/^- (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/^(\d+)\. (.+)$/m', '<li>$2</li>', $text);
    
    // Tables - simple conversion
    $lines = explode("\n", $text);
    $inTable = false;
    $result = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^\|(.+)\|$/', $line)) {
            if (!$inTable) {
                $result[] = '<table class="data-table">';
                $inTable = true;
            }
            
            // Skip separator line
            if (preg_match('/^\|[-:\|\s]+\|$/', $line)) {
                continue;
            }
            
            $cells = array_map('trim', explode('|', trim($line, '|')));
            $result[] = '<tr>';
            foreach ($cells as $cell) {
                $tag = (count($result) == 2) ? 'th' : 'td';
                $result[] = "<$tag>" . $cell . "</$tag>";
            }
            $result[] = '</tr>';
        } else {
            if ($inTable) {
                $result[] = '</table>';
                $inTable = false;
            }
            $result[] = $line;
        }
    }
    
    if ($inTable) {
        $result[] = '</table>';
    }
    
    $text = implode("\n", $result);
    
    // Wrap consecutive li elements in ul
    $text = preg_replace('/(<li>.*?<\/li>\n?)+/s', '<ul>$0</ul>', $text);
    
    // Paragraphs - wrap lines that aren't already wrapped
    $text = preg_replace('/^([^<\n].+)$/m', '<p>$1</p>', $text);
    
    // Clean up empty paragraphs
    $text = preg_replace('/<p>\s*<\/p>/', '', $text);
    
    // Emojis - keep them visible
    $text = str_replace(['✅', '❌', '⚠️', '📊', '🎯', '📋', '🔐', '🎨', '🚀', '💡', '📦', '🔧', '👥', '📝'], 
                        ['✅', '❌', '⚠️', '📊', '🎯', '📋', '🔐', '🎨', '🚀', '💡', '📦', '🔧', '👥', '📝'], $text);
    
    return $text;
}

$htmlContent = markdown_to_html($roadmapContent);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Roadmap - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <?php include_once __DIR__ . '/inc/skeleton-loader.php'; ?>
    <style>
        <?php echo get_skeleton_styles(); ?>
        
        .roadmap-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .roadmap-content h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #8b5cf6;
        }
        
        .roadmap-content h2 {
            color: #2c3e50;
            font-size: 22px;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .roadmap-content h3 {
            color: #374151;
            font-size: 18px;
            margin: 25px 0 10px;
        }
        
        .roadmap-content h4 {
            color: #6b7280;
            font-size: 16px;
            margin: 20px 0 10px;
        }
        
        .roadmap-content p {
            color: #4b5563;
            margin: 10px 0;
            line-height: 1.7;
        }
        
        .roadmap-content ul {
            margin: 10px 0 10px 20px;
            padding: 0;
        }
        
        .roadmap-content li {
            color: #4b5563;
            margin: 5px 0;
            line-height: 1.6;
        }
        
        .roadmap-content .data-table {
            margin: 15px 0;
        }
        
        .roadmap-content .inline-code {
            background: #f3f4f6;
            color: #8b5cf6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
        }
        
        .roadmap-content .code-block {
            background: #1a1a2e;
            color: #00ff88;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
        }
        
        .roadmap-content .code-block code {
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .roadmap-content hr {
            border: none;
            border-top: 2px solid #e5e7eb;
            margin: 30px 0;
        }
        
        .roadmap-content a {
            color: #8b5cf6;
            text-decoration: none;
        }
        
        .roadmap-content a:hover {
            text-decoration: underline;
        }
        
        .roadmap-content strong {
            color: #1f2937;
        }
        
        .toc-sidebar {
            position: sticky;
            top: 20px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .toc-sidebar h4 {
            margin: 0 0 15px;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .toc-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .toc-sidebar li {
            margin: 8px 0;
        }
        
        .toc-sidebar a {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            display: block;
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .toc-sidebar a:hover {
            background: #f3f4f6;
            color: #8b5cf6;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 250px;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            .toc-sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dev-tools-container skeleton-loading" id="pageContainer">
        <!-- Skeleton Loading State -->
        <div class="skeleton-container">
            <?php echo skeleton_page_header(); ?>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(true); ?>
            </div>
        </div>
        
        <!-- Actual Content -->
        <div class="content-container">
        <?php echo get_dev_tools_header('Project Roadmap', '2026 Development Roadmap & Technical Documentation', 'fa-road', '#8b5cf6'); ?>
        
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                <div class="stat-value">4.7</div>
                <div class="stat-label">Current Version</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-shield"></i></div>
                <div class="stat-value">100%</div>
                <div class="stat-label">Security Complete</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fa fa-database"></i></div>
                <div class="stat-value">42</div>
                <div class="stat-label">Database Tables</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fa fa-cogs"></i></div>
                <div class="stat-value">29</div>
                <div class="stat-label">AI Tools</div>
            </div>
        </div>
        
        <div class="main-grid">
            <!-- Main Content -->
            <div class="roadmap-content">
                <?php echo $htmlContent; ?>
            </div>
            
            <!-- Table of Contents Sidebar -->
            <div class="toc-sidebar">
                <h4><i class="fa fa-list"></i> Quick Navigation</h4>
                <ul>
                    <li><a href="#executive-summary">Executive Summary</a></li>
                    <li><a href="#phase-1">Phase 1: Tech Stack</a></li>
                    <li><a href="#phase-2">Phase 2: Security</a></li>
                    <li><a href="#phase-3">Phase 3: Performance</a></li>
                    <li><a href="#phase-4">Phase 4: Features</a></li>
                </ul>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">
                
                <h4><i class="fa fa-file-text-o"></i> Documentation</h4>
                <ul>
                    <li><a href="index.php?page=ai_documentation"><i class="fa fa-book"></i> AI Docs</a></li>
                    <li><a href="index.php?page=test_rbac"><i class="fa fa-shield"></i> RBAC Test</a></li>
                    <li><a href="index.php?page=test_crud"><i class="fa fa-database"></i> CRUD Test</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="docs/PROJECT_ROADMAP_2026.md" target="_blank" class="btn-dev btn-primary"><i class="fa fa-external-link"></i> View Raw Markdown</a>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        </div><!-- End content-container -->
    </div>
    <script><?php echo get_skeleton_js('pageContainer', 300); ?></script>
</body>
</html>
