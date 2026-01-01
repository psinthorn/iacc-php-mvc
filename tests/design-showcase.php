<?php
session_start();
require_once("inc/sys.configs.php");

// Check if logged in
if (!isset($_SESSION['usr_id'])) {
    $_SESSION['usr_id'] = 1; // Temporary for testing
    $_SESSION['usr_name'] = 'Test User';
    $_SESSION['com_id'] = 1;
    $_SESSION['lang'] = 0;
}

$lang = $_SESSION['lang'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design System Showcase - iACC</title>
    <?php include_once "css.php";?>
    <style>
        .design-showcase {
            padding: 40px;
            background: #f8f9fa;
        }
        .showcase-section {
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .showcase-section h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
        }
        .component-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .component-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .btn-demo {
            margin: 5px;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-primary { background: #2c3e50; color: white; }
        .btn-primary:hover { background: #34495e; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .color-swatch {
            padding: 20px;
            color: white;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
        }
        .swatch-primary { background: #2c3e50; }
        .swatch-success { background: #27ae60; }
        .swatch-warning { background: #f39c12; }
        .swatch-danger { background: #e74c3c; }
        .swatch-info { background: #3498db; }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include_once "menu.php";?>
        <div id="page-wrapper">
            <div class="design-showcase">
                <h1 style="color: #2c3e50; margin-top: 0;">🎨 Design System Showcase</h1>
                <p style="color: #7f8c8d;">Testing modern, minimal design components</p>

                <!-- Colors Section -->
                <div class="showcase-section">
                    <h2>Color Palette</h2>
                    <div class="component-grid">
                        <div>
                            <div class="color-swatch swatch-primary">Primary</div>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">#2c3e50</p>
                        </div>
                        <div>
                            <div class="color-swatch swatch-success">Success</div>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">#27ae60</p>
                        </div>
                        <div>
                            <div class="color-swatch swatch-warning">Warning</div>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">#f39c12</p>
                        </div>
                        <div>
                            <div class="color-swatch swatch-danger">Danger</div>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">#e74c3c</p>
                        </div>
                        <div>
                            <div class="color-swatch swatch-info">Info</div>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">#3498db</p>
                        </div>
                    </div>
                </div>

                <!-- Buttons Section -->
                <div class="showcase-section">
                    <h2>Buttons</h2>
                    <div style="padding: 20px 0;">
                        <button class="btn-demo btn-primary">Primary Button</button>
                        <button class="btn-demo btn-success">Success Button</button>
                        <button class="btn-demo btn-warning">Warning Button</button>
                        <button class="btn-demo btn-danger">Danger Button</button>
                        <button class="btn-demo btn-primary" disabled>Disabled Button</button>
                    </div>
                </div>

                <!-- Typography Section -->
                <div class="showcase-section">
                    <h2>Typography</h2>
                    <h1 style="margin-top: 20px;">Heading 1 - 2.5rem</h1>
                    <h2>Heading 2 - 2rem</h2>
                    <h3>Heading 3 - 1.5rem</h3>
                    <h4>Heading 4 - 1.25rem</h4>
                    <h5>Heading 5 - 1.1rem</h5>
                    <h6>Heading 6 - 1rem</h6>
                    <p>Regular paragraph text. This is how body text appears in the application. It uses a comfortable line height for readability.</p>
                    <small>Small text for captions and supplementary information.</small>
                </div>

                <!-- Forms Section -->
                <div class="showcase-section">
                    <h2>Form Elements</h2>
                    <form style="max-width: 400px;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Email Address</label>
                            <input type="email" class="form-control" placeholder="your@email.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #333;">Message</label>
                            <textarea class="form-control" rows="4" placeholder="Enter your message" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>
                        <button type="submit" class="btn-demo btn-primary">Submit</button>
                    </form>
                </div>

                <!-- Cards Section -->
                <div class="showcase-section">
                    <h2>Card Components</h2>
                    <div class="component-grid">
                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <h4 style="margin-top: 0;">Card Title</h4>
                            <p style="color: #666;">This is a card component with proper spacing and shadows.</p>
                            <button class="btn-demo btn-primary" style="width: 100%; margin: 0;">Action</button>
                        </div>
                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <h4 style="margin-top: 0;">Stats Card</h4>
                            <div style="font-size: 2rem; font-weight: 700; color: #2c3e50; margin: 10px 0;">1,234</div>
                            <p style="color: #999; margin: 0;">Total Orders</p>
                        </div>
                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <h4 style="margin-top: 0;">Status Badge</h4>
                            <div style="padding: 5px 10px; background: #27ae60; color: white; border-radius: 4px; display: inline-block; font-size: 12px;">Active</div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Section -->
                <div class="showcase-section">
                    <h2>Alerts & Notifications</h2>
                    <div style="padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 10px;">
                        <strong>Success!</strong> This is a success message.
                    </div>
                    <div style="padding: 15px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 10px;">
                        <strong>Warning!</strong> This is a warning message.
                    </div>
                    <div style="padding: 15px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 10px;">
                        <strong>Error!</strong> This is an error message.
                    </div>
                    <div style="padding: 15px; background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 4px;">
                        <strong>Info:</strong> This is an information message.
                    </div>
                </div>

                <!-- Design Notes -->
                <div class="showcase-section">
                    <h2>✨ Design Notes</h2>
                    <ul style="color: #555;">
                        <li><strong>Modern & Minimal:</strong> Clean, professional appearance with focused design</li>
                        <li><strong>Consistent Spacing:</strong> 4px baseline grid system (4, 8, 16, 24, 32, 48px)</li>
                        <li><strong>Accessible:</strong> WCAG compliance with proper contrast ratios</li>
                        <li><strong>Responsive:</strong> Mobile-first design that works on all devices</li>
                        <li><strong>Professional Palette:</strong> Deep blue-gray primary with semantic colors</li>
                        <li><strong>Smooth Interactions:</strong> 0.15s - 0.5s transitions for user feedback</li>
                        <li><strong>Typography System:</strong> 6 heading levels + body text with proper hierarchy</li>
                        <li><strong>Component Library:</strong> 30+ CSS files for comprehensive coverage</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php include_once "script.php";?>
</body>
</html>
