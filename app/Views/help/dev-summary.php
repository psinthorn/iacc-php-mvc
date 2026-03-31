<?php
/**
 * Developer System Summary
 * Technical reference for developers — architecture, database, API, deployment
 * Bilingual: English / Thai
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$t = [
    'en' => [
        'page_title'    => 'System Summary — Developer Reference',
        'page_subtitle' => 'Technical architecture, database schema, API endpoints, and deployment guide',
        'back_help'     => 'Back to Help Center',
        'updated'       => 'Last Updated',
        'toc'           => 'Table of Contents',
        'print'         => 'Print',
        'sec_arch'      => 'Architecture Overview',
        'sec_stack'     => 'Technology Stack',
        'sec_db'        => 'Database Schema',
        'sec_mvc'       => 'MVC Structure',
        'sec_routes'    => 'Routing System',
        'sec_api'       => 'Sales Channel API',
        'sec_auth'      => 'Authentication & Roles',
        'sec_ai'        => 'AI Integration',
        'sec_deploy'    => 'Deployment',
        'sec_docker'    => 'Docker Environment',
        'sec_testing'   => 'Testing',
        'sec_conventions' => 'Coding Conventions',
        // Card titles
        'request_flow'  => 'Request Flow',
        'dir_structure' => 'Directory Structure',
        'backend'       => 'Backend',
        'frontend'      => 'Frontend',
        'core_tables'   => 'Core Tables',
        'multi_tenant'  => 'Multi-Tenant Isolation',
        'multi_tenant_desc' => 'Every data query is filtered by <code>com_id</code> (company ID) from <code>$_SESSION[\'com_id\']</code>. The <code>CompanyFilter</code> singleton handles this:',
        'migrations'    => 'Migrations',
        'migrations_desc' => 'Two mirror locations for MySQL 5.7 compatibility:',
        'migrations_current' => '<strong>Current:</strong> 001–021. Run via:',
        'prepared_stmt' => '<strong>Always use prepared statements.</strong> Legacy HardClass methods (insertDbMax, etc.) exist but use isolated <code>$args</code> arrays per operation to avoid state leakage.',
        'route_types'   => 'Route Types',
        'routes_count'  => '<strong>175+ routes</strong> currently defined. URL pattern:',
        'api_endpoints' => 'API Endpoints',
        'authentication'=> 'Authentication',
        'perm_levels'   => 'Permission Levels',
        'session_vars'  => 'Session Variables',
        'self_reg_flow' => 'Self-Registration Flow (v6.0)',
        'components'    => 'Components',
        'containers'    => 'Containers',
        'key_commands'  => 'Key Commands',
        'test_suites'   => 'Test Suites',
        'deploy_options'=> 'Deployment Options',
        'naming'        => 'Naming',
        'security_rules'=> 'Security Rules',
        'multi_lang'    => 'Multi-Language (Mandatory)',
        'multi_lang_desc'=> 'Every new module <strong>must</strong> support bilingual (EN/TH) from day one. Two systems:',
        // Table headers
        'th_table'      => 'Table',
        'th_purpose'    => 'Purpose',
        'th_key_cols'   => 'Key Columns',
        'th_path'       => 'Path',
        'th_style'      => 'Style',
        'th_use'        => 'Use',
        'th_type'       => 'Type',
        'th_syntax'     => 'Syntax',
        'th_auth'       => 'Auth',
        'th_layout'     => 'Layout',
        'th_method'     => 'Method',
        'th_endpoint'   => 'Endpoint',
        'th_description'=> 'Description',
        'th_level'      => 'Level',
        'th_role'       => 'Role',
        'th_access'     => 'Access',
        'th_key'        => 'Key',
        'th_file'       => 'File',
        'th_tests'      => 'Tests',
        'th_covers'     => 'Covers',
        'th_container'  => 'Container',
        'th_service'    => 'Service',
        'th_port'       => 'Port',
        'th_target'     => 'Target',
        'th_script'     => 'Script',
        'th_context'    => 'Context',
        'th_system'     => 'System',
        'th_session'    => 'Session',
        // Table values
        'users_auth'    => 'Users & authentication',
        'companies_mt'  => 'Companies (multi-tenant)',
        'prod_categories'=> 'Product categories',
        'brands_suppliers'=> 'Brands/suppliers',
        'prod_types'    => 'Product types',
        'prod_models'   => 'Product models (SKUs)',
        'po_quotations' => 'Purchase Orders & Quotations',
        'po_line_items' => 'PO line items',
        'purchase_req'  => 'Purchase Requisitions',
        'invoices'      => 'Invoices',
        'delivery_track'=> 'Delivery tracking',
        'pay_vouchers'  => 'Payment vouchers (money out)',
        'receipts_in'   => 'Receipts (money in)',
        'expense_records'=> 'Expense records',
        'double_entry'  => 'Double-entry journal',
        'reg_tokens'    => 'Registration tokens',
        'user_tracking' => 'User action tracking',
        'normal'        => 'Normal',
        'required'      => 'Required',
        'none'          => 'None',
        'no_sidebar'    => 'No sidebar',
        'sidebar_header'=> 'Sidebar + header',
        'create_order'  => 'Create new order',
        'list_orders'   => 'List orders',
        'get_order'     => 'Get order details',
        'update_order'  => 'Update order',
        'create_customer'=> 'Create customer',
        'list_products' => 'List products',
        'manage_webhooks'=> 'Manage webhooks',
        'user_role'     => 'User',
        'admin_role'    => 'Admin',
        'super_admin'   => 'Super Admin',
        'access_user'   => 'Assigned company only, create/view documents',
        'access_admin'  => 'All companies, master data, user management',
        'access_super'  => 'Full access: gateway config, tax, audit, dev tools',
        'current_user_id'=> 'Current user ID',
        'current_com_id'=> 'Current company ID',
        'perm_level'    => 'Permission level (0/1/2)',
        'lang_session'  => 'Language (0=EN, 1=TH)',
        'csrf_token'    => 'CSRF protection token',
        'current_com_name'=> 'Current company name',
        'ai_provider_desc'=> 'AIProvider wrapper — routes to Ollama or OpenAI',
        'ollama_desc'   => 'Local Ollama API client (llama3.2, mistral)',
        'openai_desc'   => 'OpenAI API client (GPT-4o turbo)',
        'agent_desc'    => 'Agent tool execution with permission checks',
        'chat_handler'  => 'Chat message processing',
        'chat_stream'   => 'SSE streaming endpoint',
        'schema_disc'   => 'Auto-discover DB schema for AI context',
        'crud_tests'    => 'Core CRUD operations, HardClass methods',
        'reg_tests'     => 'Self-registration, email verification, onboarding',
        'api_tests'     => 'Sales Channel API endpoints',
        'mvc_tests'     => 'MVC controllers, models, routes',
        'security_1'    => 'Prepared statements for all queries',
        'security_2'    => 'CSRF token on all POST forms',
        'security_3'    => '<code>htmlspecialchars()</code> on all output',
        'security_4'    => 'Company filter on all data queries',
        'security_5'    => 'Rate limiting on auth endpoints',
        'security_6'    => 'Isolated <code>$args</code> arrays per DB operation',
        'security_7'    => 'Input validation at system boundaries',
        'public_landing'=> 'Public/Landing pages',
        'inapp_mvc'     => 'In-app MVC views',
        'dev_ref_title' => 'iACC Developer Reference',
        'dev_ref_stats' => '40+ Controllers • 30+ Models • 117+ Views • 175+ Routes • 192+ Tests',
    ],
    'th' => [
        'page_title'    => 'สรุประบบ — เอกสารสำหรับนักพัฒนา',
        'page_subtitle' => 'สถาปัตยกรรม ฐานข้อมูล API และคู่มือการปรับใช้',
        'back_help'     => 'กลับไปศูนย์ช่วยเหลือ',
        'updated'       => 'อัปเดตล่าสุด',
        'toc'           => 'สารบัญ',
        'print'         => 'พิมพ์',
        'sec_arch'      => 'ภาพรวมสถาปัตยกรรม',
        'sec_stack'     => 'เทคโนโลยี',
        'sec_db'        => 'โครงสร้างฐานข้อมูล',
        'sec_mvc'       => 'โครงสร้าง MVC',
        'sec_routes'    => 'ระบบเส้นทาง',
        'sec_api'       => 'Sales Channel API',
        'sec_auth'      => 'การยืนยันตัวตนและบทบาท',
        'sec_ai'        => 'การรวม AI',
        'sec_deploy'    => 'การปรับใช้',
        'sec_docker'    => 'สภาพแวดล้อม Docker',
        'sec_testing'   => 'การทดสอบ',
        'sec_conventions' => 'แบบแผนการเขียนโค้ด',
        // Card titles
        'request_flow'  => 'รูปแบบคำขอ',
        'dir_structure' => 'โครงสร้างไดเรกทอรี',
        'backend'       => 'แบ็กเอนด์',
        'frontend'      => 'ฟรอนต์เอนด์',
        'core_tables'   => 'ตารางหลัก',
        'multi_tenant'  => 'การแยกข้อมูลหลายบริษัท',
        'multi_tenant_desc' => 'ทุกคำสั่งค้นหาจะถูกกรองด้วย <code>com_id</code> (รหัสบริษัท) จาก <code>$_SESSION[\'com_id\']</code> โดย <code>CompanyFilter</code> singleton จัดการดังนี้:',
        'migrations'    => 'ไมเกรชัน',
        'migrations_desc' => 'สองตำแหน่งสำหรับความเข้ากันได้กับ MySQL 5.7:',
        'migrations_current' => '<strong>ปัจจุบัน:</strong> 001–021 รันผ่าน:',
        'prepared_stmt' => '<strong>ใช้ prepared statements เสมอ</strong> เมธอดดั้งเดิม HardClass (insertDbMax ฯลฯ) ใช้งานได้ แต่ต้องใช้อาร์เรย์ <code>$args</code> แยกต่างหากสำหรับแต่ละการดำเนินการ',
        'route_types'   => 'ประเภทเส้นทาง',
        'routes_count'  => '<strong>175+ เส้นทาง</strong>ที่กำหนดแล้ว รูปแบบ URL:',
        'api_endpoints' => 'จุดปลาย API',
        'authentication'=> 'การยืนยันตัวตน',
        'perm_levels'   => 'ระดับสิทธิ์',
        'session_vars'  => 'ตัวแปร Session',
        'self_reg_flow' => 'ขั้นตอนการลงทะเบียนด้วยตนเอง (v6.0)',
        'components'    => 'ส่วนประกอบ',
        'containers'    => 'คอนเทนเนอร์',
        'key_commands'  => 'คำสั่งที่สำคัญ',
        'test_suites'   => 'ชุดทดสอบ',
        'deploy_options'=> 'ตัวเลือกการปรับใช้',
        'naming'        => 'การตั้งชื่อ',
        'security_rules'=> 'กฎความปลอดภัย',
        'multi_lang'    => 'หลายภาษา (บังคับ)',
        'multi_lang_desc'=> 'ทุกโมดูลใหม่ <strong>ต้อง</strong> รองรับสองภาษา (EN/TH) ตั้งแต่วันแรก สองระบบ:',
        // Table headers
        'th_table'      => 'ตาราง',
        'th_purpose'    => 'วัตถุประสงค์',
        'th_key_cols'   => 'คอลัมน์สำคัญ',
        'th_path'       => 'ที่อยู่',
        'th_style'      => 'รูปแบบ',
        'th_use'        => 'การใช้งาน',
        'th_type'       => 'ประเภท',
        'th_syntax'     => 'ไวยากรณ์',
        'th_auth'       => 'การยืนยัน',
        'th_layout'     => 'เลย์เอาต์',
        'th_method'     => 'เมธอด',
        'th_endpoint'   => 'จุดปลาย',
        'th_description'=> 'คำอธิบาย',
        'th_level'      => 'ระดับ',
        'th_role'       => 'บทบาท',
        'th_access'     => 'สิทธิ์เข้าถึง',
        'th_key'        => 'คีย์',
        'th_file'       => 'ไฟล์',
        'th_tests'      => 'ทดสอบ',
        'th_covers'     => 'ครอบคลุม',
        'th_container'  => 'คอนเทนเนอร์',
        'th_service'    => 'บริการ',
        'th_port'       => 'พอร์ต',
        'th_target'     => 'เป้าหมาย',
        'th_script'     => 'สคริปต์',
        'th_context'    => 'บริบท',
        'th_system'     => 'ระบบ',
        'th_session'    => 'เซสชัน',
        // Table values
        'users_auth'    => 'ผู้ใช้และการยืนยันตัวตน',
        'companies_mt'  => 'บริษัท (หลายบริษัท)',
        'prod_categories'=> 'หมวดหมู่สินค้า',
        'brands_suppliers'=> 'แบรนด์/ซัพพลายเออร์',
        'prod_types'    => 'ประเภทสินค้า',
        'prod_models'   => 'รุ่นสินค้า (SKUs)',
        'po_quotations' => 'ใบสั่งซื้อและใบเสนอราคา',
        'po_line_items' => 'รายการสินค้าในใบสั่งซื้อ',
        'purchase_req'  => 'ใบขอซื้อ',
        'invoices'      => 'ใบแจ้งหนี้',
        'delivery_track'=> 'ติดตามการจัดส่ง',
        'pay_vouchers'  => 'ใบสำคัญจ่าย (เงินออก)',
        'receipts_in'   => 'ใบเสร็จ (เงินเข้า)',
        'expense_records'=> 'รายการค่าใช้จ่าย',
        'double_entry'  => 'สมุดรายวันบัญชีคู่',
        'reg_tokens'    => 'โทเค็นการลงทะเบียน',
        'user_tracking' => 'การติดตามการกระทำของผู้ใช้',
        'normal'        => 'ปกติ',
        'required'      => 'จำเป็น',
        'none'          => 'ไม่มี',
        'no_sidebar'    => 'ไม่มีแถบด้านข้าง',
        'sidebar_header'=> 'แถบด้านข้าง + ส่วนหัว',
        'create_order'  => 'สร้างคำสั่งซื้อใหม่',
        'list_orders'   => 'แสดงรายการคำสั่งซื้อ',
        'get_order'     => 'รายละเอียดคำสั่งซื้อ',
        'update_order'  => 'อัปเดตคำสั่งซื้อ',
        'create_customer'=> 'สร้างลูกค้า',
        'list_products' => 'แสดงรายการสินค้า',
        'manage_webhooks'=> 'จัดการ Webhooks',
        'user_role'     => 'ผู้ใช้',
        'admin_role'    => 'ผู้ดูแลระบบ',
        'super_admin'   => 'ผู้ดูแลระบบสูงสุด',
        'access_user'   => 'เฉพาะบริษัทที่กำหนด สร้าง/ดูเอกสาร',
        'access_admin'  => 'ทุกบริษัท ข้อมูลหลัก จัดการผู้ใช้',
        'access_super'  => 'สิทธิ์เต็ม: ตั้งค่าชำระเงิน ภาษี ตรวจสอบ เครื่องมือนักพัฒนา',
        'current_user_id'=> 'รหัสผู้ใช้ปัจจุบัน',
        'current_com_id'=> 'รหัสบริษัทปัจจุบัน',
        'perm_level'    => 'ระดับสิทธิ์ (0/1/2)',
        'lang_session'  => 'ภาษา (0=EN, 1=TH)',
        'csrf_token'    => 'โทเค็นป้องกัน CSRF',
        'current_com_name'=> 'ชื่อบริษัทปัจจุบัน',
        'ai_provider_desc'=> 'AIProvider wrapper — เส้นทางไป Ollama หรือ OpenAI',
        'ollama_desc'   => 'ไคลเอนต์ Ollama API ในเครื่อง (llama3.2, mistral)',
        'openai_desc'   => 'ไคลเอนต์ OpenAI API (GPT-4o turbo)',
        'agent_desc'    => 'การทำงาน Agent tool พร้อมการตรวจสอบสิทธิ์',
        'chat_handler'  => 'ประมวลผลข้อความแชท',
        'chat_stream'   => 'จุดปลาย SSE streaming',
        'schema_disc'   => 'ค้นพบ DB schema อัตโนมัติสำหรับ AI context',
        'crud_tests'    => 'CRUD operations หลัก, เมธอด HardClass',
        'reg_tests'     => 'การลงทะเบียน การยืนยันอีเมล การ onboarding',
        'api_tests'     => 'จุดปลาย Sales Channel API',
        'mvc_tests'     => 'MVC controllers, models, routes',
        'security_1'    => 'ใช้ Prepared statements ทุกคำสั่ง',
        'security_2'    => 'CSRF token ในทุกฟอร์ม POST',
        'security_3'    => '<code>htmlspecialchars()</code> ในทุกเอาต์พุต',
        'security_4'    => 'ตัวกรองบริษัทในทุกคำสั่งค้นหา',
        'security_5'    => 'จำกัดอัตราการเรียกใช้จุดปลายยืนยันตัวตน',
        'security_6'    => 'อาร์เรย์ <code>$args</code> แยกต่างหากต่อการดำเนินการ',
        'security_7'    => 'ตรวจสอบข้อมูลที่ขอบเขตระบบ',
        'public_landing'=> 'หน้าสาธารณะ/แลนดิ้ง',
        'inapp_mvc'     => 'วิว MVC ในแอป',
        'dev_ref_title' => 'เอกสารอ้างอิงนักพัฒนา iACC',
        'dev_ref_stats' => '40+ Controllers • 30+ Models • 117+ Views • 175+ Routes • 192+ Tests',
    ]
][$lang];
?>

<style>
.dev-container { max-width: 1000px; margin: 0 auto; }
.dev-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
.dev-header h1 { font-size: 28px; color: #333; margin: 0 0 8px 0; }
.dev-header p { color: #6c757d; font-size: 15px; margin: 0; }
.dev-meta { display: flex; justify-content: center; gap: 20px; margin-top: 12px; }
.dev-meta span { font-size: 13px; color: #999; }
.dev-actions { display: flex; justify-content: center; gap: 12px; margin-top: 12px; }
.dev-actions a, .dev-actions button { font-size: 13px; padding: 6px 16px; border-radius: 6px; text-decoration: none; border: 1px solid #ddd; background: white; color: #555; cursor: pointer; }
.dev-actions a:hover, .dev-actions button:hover { background: #2c3e50; color: white; border-color: #2c3e50; }

.dev-toc { background: #f1f3f5; border-radius: 10px; padding: 20px 28px; margin-bottom: 30px; columns: 2; column-gap: 30px; }
.dev-toc h3 { margin: 0 0 12px 0; font-size: 16px; color: #333; column-span: all; }
.dev-toc a { display: block; color: #2c3e50; text-decoration: none; font-size: 14px; padding: 3px 0; break-inside: avoid; }
.dev-toc a:hover { color: #8e44ad; }

.dev-section { margin-bottom: 36px; }
.dev-section h2 { font-size: 22px; font-weight: 600; color: #2c3e50; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #2c3e50; }
.dev-section h2 i { margin-right: 8px; }

.dev-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.dev-card h3 { margin: 0 0 10px 0; font-size: 16px; font-weight: 600; color: #333; }
.dev-card p { margin: 0 0 8px 0; font-size: 14px; color: #555; line-height: 1.6; }

pre.dev-code { background: #1e1e2e; color: #cdd6f4; border-radius: 8px; padding: 16px; font-size: 13px; line-height: 1.6; overflow-x: auto; margin: 10px 0; }
pre.dev-code .comment { color: #6c7086; }
pre.dev-code .keyword { color: #cba6f7; }
pre.dev-code .string { color: #a6e3a1; }
pre.dev-code .variable { color: #89b4fa; }

.dev-table { width: 100%; border-collapse: collapse; font-size: 13px; margin: 10px 0; }
.dev-table th { background: #2c3e50; color: white; padding: 10px 12px; text-align: left; font-weight: 500; }
.dev-table td { padding: 8px 12px; border-bottom: 1px solid #eee; vertical-align: top; }
.dev-table tr:hover td { background: #f8f9fa; }
.dev-table code { background: #f1f3f5; padding: 2px 6px; border-radius: 3px; font-size: 12px; }

.dev-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
@media (max-width: 768px) { .dev-grid { grid-template-columns: 1fr; } .dev-toc { columns: 1; } }

.dev-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; }
.dev-badge.php { background: #7b7fb5; color: white; }
.dev-badge.mysql { background: #4479a1; color: white; }
.dev-badge.docker { background: #2496ed; color: white; }
.dev-badge.nginx { background: #009639; color: white; }
.dev-badge.js { background: #f7df1e; color: #333; }

@media print { .dev-actions { display: none; } .dev-card { break-inside: avoid; } }
</style>

<div class="dev-container">
    <div class="dev-header">
        <h1><i class="fa fa-cogs"></i> <?= $t['page_title'] ?></h1>
        <p><?= $t['page_subtitle'] ?></p>
        <div class="dev-meta">
            <span><i class="fa fa-calendar"></i> <?= $t['updated'] ?>: 2026-03-31</span>
            <span><i class="fa fa-tag"></i> v6.0</span>
            <span><span class="dev-badge php">PHP 8.2</span> <span class="dev-badge mysql">MySQL 5.7</span> <span class="dev-badge docker">Docker</span> <span class="dev-badge nginx">Nginx</span></span>
        </div>
        <div class="dev-actions">
            <a href="index.php?page=help"><i class="fa fa-arrow-left"></i> <?= $t['back_help'] ?></a>
            <button onclick="window.print()"><i class="fa fa-print"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <!-- TOC -->
    <div class="dev-toc">
        <h3><i class="fa fa-list"></i> <?= $t['toc'] ?></h3>
        <a href="#arch">1. <?= $t['sec_arch'] ?></a>
        <a href="#stack">2. <?= $t['sec_stack'] ?></a>
        <a href="#db">3. <?= $t['sec_db'] ?></a>
        <a href="#mvc">4. <?= $t['sec_mvc'] ?></a>
        <a href="#routes">5. <?= $t['sec_routes'] ?></a>
        <a href="#api">6. <?= $t['sec_api'] ?></a>
        <a href="#auth">7. <?= $t['sec_auth'] ?></a>
        <a href="#ai">8. <?= $t['sec_ai'] ?></a>
        <a href="#docker">9. <?= $t['sec_docker'] ?></a>
        <a href="#testing">10. <?= $t['sec_testing'] ?></a>
        <a href="#deploy">11. <?= $t['sec_deploy'] ?></a>
        <a href="#conventions">12. <?= $t['sec_conventions'] ?></a>
    </div>

    <!-- 1. Architecture -->
    <div class="dev-section" id="arch">
        <h2><i class="fa fa-sitemap"></i> 1. <?= $t['sec_arch'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['request_flow'] ?></h3>
<pre class="dev-code"><span class="comment">// Web Application</span>
Browser → index.php → routes.php → Controller → Model → View

<span class="comment">// REST API</span>
Client → api.php → Controller → Service → JSON Response

<span class="comment">// AI Chat</span>
Browser → ai/chat-stream.php → AIProvider → Ollama/OpenAI → SSE Response</pre>
        </div>
        <div class="dev-card">
            <h3><?= $t['dir_structure'] ?></h3>
<pre class="dev-code">app/
├── Config/routes.php         <span class="comment"># All route definitions</span>
├── Controllers/              <span class="comment"># 40+ controllers (extend BaseController)</span>
├── Models/                   <span class="comment"># 30+ models (extend BaseModel)</span>
├── Services/                 <span class="comment"># Business services (ChannelService, etc.)</span>
├── Views/                    <span class="comment"># 117+ views organized by module</span>
├── Helpers/                  <span class="comment"># CompanyFilter, utility functions</span>
└── Middleware/               <span class="comment"># Auth, rate limiting</span>
ai/                           <span class="comment"># AI integration (Ollama, OpenAI)</span>
inc/                          <span class="comment"># Legacy includes (HardClass, lang files)</span>
database/migrations/          <span class="comment"># Numbered SQL migrations</span>
tests/                        <span class="comment"># E2E test suites</span></pre>
        </div>
    </div>

    <!-- 2. Technology Stack -->
    <div class="dev-section" id="stack">
        <h2><i class="fa fa-layer-group"></i> 2. <?= $t['sec_stack'] ?></h2>
        <div class="dev-grid">
            <div class="dev-card">
                <h3><?= $t['backend'] ?></h3>
                <table class="dev-table">
                    <tr><td><strong>PHP</strong></td><td>8.2 (PHP-FPM)</td></tr>
                    <tr><td><strong>MySQL</strong></td><td>5.7 (InnoDB, utf8mb4)</td></tr>
                    <tr><td><strong>Nginx</strong></td><td>Alpine (reverse proxy)</td></tr>
                    <tr><td><strong>Framework</strong></td><td>Custom MVC (no framework)</td></tr>
                    <tr><td><strong>PDF</strong></td><td>mPDF 8.x</td></tr>
                    <tr><td><strong>Email</strong></td><td>PHPMailer + MailHog (dev)</td></tr>
                </table>
            </div>
            <div class="dev-card">
                <h3><?= $t['frontend'] ?></h3>
                <table class="dev-table">
                    <tr><td><strong>CSS</strong></td><td>Bootstrap 5.3.3</td></tr>
                    <tr><td><strong>Icons</strong></td><td>Font Awesome 4.7</td></tr>
                    <tr><td><strong>Fonts</strong></td><td>Inter (Latin), system Thai</td></tr>
                    <tr><td><strong>JS</strong></td><td>Vanilla JS, jQuery (legacy)</td></tr>
                    <tr><td><strong>Charts</strong></td><td>Chart.js</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Database Schema -->
    <div class="dev-section" id="db">
        <h2><i class="fa fa-database"></i> 3. <?= $t['sec_db'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['core_tables'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_table'] ?></th><th><?= $t['th_purpose'] ?></th><th><?= $t['th_key_cols'] ?></th></tr>
                <tr><td><code>authorize</code></td><td><?= $t['users_auth'] ?></td><td>id, email, name, password, com_id, lang, user_level, registered_via</td></tr>
                <tr><td><code>company</code></td><td><?= $t['companies_mt'] ?></td><td>id, com_name, address, tax_id, registered_via, onboarding_completed</td></tr>
                <tr><td><code>category</code></td><td><?= $t['prod_categories'] ?></td><td>id, name, com_id</td></tr>
                <tr><td><code>brand</code></td><td><?= $t['brands_suppliers'] ?></td><td>id, name, category_id, com_id</td></tr>
                <tr><td><code>type</code></td><td><?= $t['prod_types'] ?></td><td>id, name, brand_id, com_id</td></tr>
                <tr><td><code>model</code></td><td><?= $t['prod_models'] ?></td><td>id, name, price, type_id, com_id</td></tr>
                <tr><td><code>po</code></td><td><?= $t['po_quotations'] ?></td><td>id, po_no, com_id, total, status, po_id_new (versioning)</td></tr>
                <tr><td><code>product</code></td><td><?= $t['po_line_items'] ?></td><td>id, po_id, type, model, price, quantity</td></tr>
                <tr><td><code>pr</code></td><td><?= $t['purchase_req'] ?></td><td>id, pr_no, com_id, status</td></tr>
                <tr><td><code>compl</code></td><td><?= $t['invoices'] ?></td><td>id, inv_no, po_id, total, status</td></tr>
                <tr><td><code>delivery_notes</code></td><td><?= $t['delivery_track'] ?></td><td>id, dn_no, po_id, status</td></tr>
                <tr><td><code>voucher</code></td><td><?= $t['pay_vouchers'] ?></td><td>id, voucher_no, amount, payment_method</td></tr>
                <tr><td><code>receipt</code></td><td><?= $t['receipts_in'] ?></td><td>id, receipt_no, amount, payment_method</td></tr>
                <tr><td><code>expenses</code></td><td><?= $t['expense_records'] ?></td><td>id, amount, category_id, project_id</td></tr>
                <tr><td><code>journal_entries</code></td><td><?= $t['double_entry'] ?></td><td>id, entry_no, account_id, debit, credit</td></tr>
                <tr><td><code>email_verifications</code></td><td><?= $t['reg_tokens'] ?></td><td>id, email, token, payload, expires_at</td></tr>
                <tr><td><code>audit_logs</code></td><td><?= $t['user_tracking'] ?></td><td>id, user_id, action, table_name, record_id</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3><?= $t['multi_tenant'] ?></h3>
            <p><?= $t['multi_tenant_desc'] ?></p>
<pre class="dev-code"><span class="comment">// 19 filtered tables (require company_id)</span>
<span class="variable">$filter</span> = CompanyFilter::getInstance(<span class="variable">$com_id</span>);
<span class="variable">$where</span>  = <span class="variable">$filter</span>->whereCompanyFilter(<span class="string">'po'</span>);
<span class="comment">// Returns: "WHERE po.com_id = '5'"</span>

<span class="comment">// 6 global tables (no filtering): authorize, company, api_keys, ...</span></pre>
        </div>
        <div class="dev-card">
            <h3><?= $t['migrations'] ?></h3>
            <p><?= $t['migrations_desc'] ?></p>
            <table class="dev-table">
                <tr><th><?= $t['th_path'] ?></th><th><?= $t['th_style'] ?></th><th><?= $t['th_use'] ?></th></tr>
                <tr><td><code>database/migrations/NNN_name.sql</code></td><td>Simple (IF NOT EXISTS)</td><td>MySQL 8+ or phpMyAdmin</td></tr>
                <tr><td><code>migrations/NNN_name.sql</code></td><td>Stored procedure</td><td>MySQL 5.7 CLI (idempotent)</td></tr>
            </table>
            <p><?= $t['migrations_current'] ?> <code>mysql -u root -p iacc &lt; migrations/NNN.sql</code></p>
        </div>
    </div>

    <!-- 4. MVC Structure -->
    <div class="dev-section" id="mvc">
        <h2><i class="fa fa-cubes"></i> 4. <?= $t['sec_mvc'] ?></h2>
        <div class="dev-card">
            <h3>BaseController</h3>
<pre class="dev-code"><span class="keyword">class</span> MyController <span class="keyword">extends</span> BaseController {
    <span class="keyword">public function</span> index() {
        <span class="variable">$this</span>->requireAuth();          <span class="comment">// Enforce login</span>
        <span class="variable">$this</span>->verifyCsrf();            <span class="comment">// POST routes</span>
        <span class="variable">$data</span> = <span class="variable">$model</span>->getAll();
        <span class="variable">$this</span>->render(<span class="string">'module/list'</span>, [<span class="string">'items'</span> => <span class="variable">$data</span>]);
    }
}</pre>
        </div>
        <div class="dev-card">
            <h3>BaseModel</h3>
<pre class="dev-code"><span class="keyword">class</span> MyModel <span class="keyword">extends</span> BaseModel {
    <span class="keyword">public function</span> create(<span class="variable">$data</span>): int {
        <span class="variable">$stmt</span> = <span class="variable">$this</span>->conn->prepare(<span class="string">"INSERT INTO table (col) VALUES (?)"</span>);
        <span class="variable">$stmt</span>->bind_param(<span class="string">'s'</span>, <span class="variable">$data</span>[<span class="string">'col'</span>]);
        <span class="variable">$stmt</span>->execute();
        <span class="keyword">return</span> <span class="variable">$stmt</span>->insert_id;
    }
}</pre>
            <p><?= $t['prepared_stmt'] ?></p>
        </div>
    </div>

    <!-- 5. Routing -->
    <div class="dev-section" id="routes">
        <h2><i class="fa fa-road"></i> 5. <?= $t['sec_routes'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['route_types'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_type'] ?></th><th><?= $t['th_syntax'] ?></th><th><?= $t['th_auth'] ?></th><th><?= $t['th_layout'] ?></th></tr>
                <tr><td><?= $t['normal'] ?></td><td><code>['Controller', 'method']</code></td><td><?= $t['required'] ?></td><td><?= $t['sidebar_header'] ?></td></tr>
                <tr><td>Public</td><td><code>['Controller', 'method', 'public']</code></td><td><?= $t['none'] ?></td><td>Standalone</td></tr>
                <tr><td>Standalone</td><td><code>['Controller', 'method', 'standalone']</code></td><td><?= $t['required'] ?></td><td><?= $t['no_sidebar'] ?></td></tr>
            </table>
<pre class="dev-code"><span class="comment">// app/Config/routes.php</span>
<span class="keyword">return</span> [
    <span class="string">'dashboard'</span>    => [<span class="string">'DashboardController'</span>, <span class="string">'index'</span>],
    <span class="string">'pdf_invoice'</span>  => [<span class="string">'PdfController'</span>, <span class="string">'invoice'</span>, <span class="string">'standalone'</span>],
    <span class="string">'register'</span>     => [<span class="string">'RegistrationController'</span>, <span class="string">'showForm'</span>, <span class="string">'public'</span>],
];</pre>
            <p><?= $t['routes_count'] ?> <code>index.php?page=route_name</code></p>
        </div>
    </div>

    <!-- 6. Sales Channel API -->
    <div class="dev-section" id="api">
        <h2><i class="fa fa-plug"></i> 6. <?= $t['sec_api'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['api_endpoints'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_method'] ?></th><th><?= $t['th_endpoint'] ?></th><th><?= $t['th_description'] ?></th></tr>
                <tr><td>POST</td><td><code>/api.php?action=orders</code></td><td><?= $t['create_order'] ?></td></tr>
                <tr><td>GET</td><td><code>/api.php?action=orders</code></td><td><?= $t['list_orders'] ?></td></tr>
                <tr><td>GET</td><td><code>/api.php?action=orders&id=N</code></td><td><?= $t['get_order'] ?></td></tr>
                <tr><td>PUT</td><td><code>/api.php?action=orders&id=N</code></td><td><?= $t['update_order'] ?></td></tr>
                <tr><td>POST</td><td><code>/api.php?action=customers</code></td><td><?= $t['create_customer'] ?></td></tr>
                <tr><td>GET</td><td><code>/api.php?action=products</code></td><td><?= $t['list_products'] ?></td></tr>
                <tr><td>POST</td><td><code>/api.php?action=webhooks</code></td><td><?= $t['manage_webhooks'] ?></td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3><?= $t['authentication'] ?></h3>
<pre class="dev-code"><span class="comment">// API Key in header</span>
X-API-Key: your-api-key-here

<span class="comment">// Idempotency key (optional, prevents duplicates)</span>
X-Idempotency-Key: unique-request-id

<span class="comment">// Rate limiting: per-key, configurable per plan</span></pre>
        </div>
    </div>

    <!-- 7. Auth & Roles -->
    <div class="dev-section" id="auth">
        <h2><i class="fa fa-shield"></i> 7. <?= $t['sec_auth'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['perm_levels'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_level'] ?></th><th><?= $t['th_role'] ?></th><th><?= $t['th_access'] ?></th></tr>
                <tr><td>0</td><td><?= $t['user_role'] ?></td><td><?= $t['access_user'] ?></td></tr>
                <tr><td>1</td><td><?= $t['admin_role'] ?></td><td><?= $t['access_admin'] ?></td></tr>
                <tr><td>2</td><td><?= $t['super_admin'] ?></td><td><?= $t['access_super'] ?></td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3><?= $t['session_vars'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_key'] ?></th><th><?= $t['th_type'] ?></th><th><?= $t['th_description'] ?></th></tr>
                <tr><td><code>$_SESSION['user_id']</code></td><td>int</td><td><?= $t['current_user_id'] ?></td></tr>
                <tr><td><code>$_SESSION['com_id']</code></td><td>int</td><td><?= $t['current_com_id'] ?></td></tr>
                <tr><td><code>$_SESSION['user_level']</code></td><td>int</td><td><?= $t['perm_level'] ?></td></tr>
                <tr><td><code>$_SESSION['lang']</code></td><td>int</td><td><?= $t['lang_session'] ?></td></tr>
                <tr><td><code>$_SESSION['csrf_token']</code></td><td>string</td><td><?= $t['csrf_token'] ?></td></tr>
                <tr><td><code>$_SESSION['com_name']</code></td><td>string</td><td><?= $t['current_com_name'] ?></td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3><?= $t['self_reg_flow'] ?></h3>
<pre class="dev-code">1. User fills registration form → POST /register
2. System creates email_verifications record with token
3. Email sent with verification link
4. User clicks link → GET /register/verify?token=xxx
5. System creates: company + authorize + trial subscription
6. Auto-login → redirect to onboarding wizard
7. User completes company setup → dashboard</pre>
        </div>
    </div>

    <!-- 8. AI Integration -->
    <div class="dev-section" id="ai">
        <h2><i class="fa fa-robot"></i> 8. <?= $t['sec_ai'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['components'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_file'] ?></th><th><?= $t['th_purpose'] ?></th></tr>
                <tr><td><code>ai/ai-provider.php</code></td><td><?= $t['ai_provider_desc'] ?></td></tr>
                <tr><td><code>ai/ollama-client.php</code></td><td><?= $t['ollama_desc'] ?></td></tr>
                <tr><td><code>ai/openai-client.php</code></td><td><?= $t['openai_desc'] ?></td></tr>
                <tr><td><code>ai/agent-executor.php</code></td><td><?= $t['agent_desc'] ?></td></tr>
                <tr><td><code>ai/chat-handler.php</code></td><td><?= $t['chat_handler'] ?></td></tr>
                <tr><td><code>ai/chat-stream.php</code></td><td><?= $t['chat_stream'] ?></td></tr>
                <tr><td><code>ai/schema-discovery.php</code></td><td><?= $t['schema_disc'] ?></td></tr>
            </table>
        </div>
    </div>

    <!-- 9. Docker -->
    <div class="dev-section" id="docker">
        <h2><i class="fa fa-server"></i> 9. <?= $t['sec_docker'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['containers'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_container'] ?></th><th><?= $t['th_service'] ?></th><th><?= $t['th_port'] ?></th></tr>
                <tr><td><code>iacc_nginx</code></td><td>Nginx reverse proxy</td><td>80, 443</td></tr>
                <tr><td><code>iacc_php</code></td><td>PHP 8.2 FPM</td><td>9000 (internal)</td></tr>
                <tr><td><code>iacc_mysql</code></td><td>MySQL 5.7</td><td>3306</td></tr>
                <tr><td><code>iacc_phpmyadmin</code></td><td>phpMyAdmin</td><td>8083</td></tr>
                <tr><td><code>iacc_mailhog_server</code></td><td>MailHog SMTP + Web</td><td>1025, 8025</td></tr>
                <tr><td><code>iacc_ollama</code></td><td>Ollama AI</td><td>11434</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3><?= $t['key_commands'] ?></h3>
<pre class="dev-code"><span class="comment"># Start / stop</span>
docker compose up -d
docker compose down

<span class="comment"># Run migration</span>
docker exec iacc_mysql mysql -uroot -proot iacc < migrations/NNN.sql

<span class="comment"># PHP syntax check</span>
docker exec iacc_php php -l /var/www/html/app/Controllers/MyController.php

<span class="comment"># Run E2E tests</span>
curl -s "http://localhost/tests/test-e2e-crud.php"

<span class="comment"># View logs</span>
docker logs iacc_php --tail 50
docker logs iacc_nginx --tail 50</pre>
        </div>
    </div>

    <!-- 10. Testing -->
    <div class="dev-section" id="testing">
        <h2><i class="fa fa-flask"></i> 10. <?= $t['sec_testing'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['test_suites'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_file'] ?></th><th><?= $t['th_tests'] ?></th><th><?= $t['th_covers'] ?></th></tr>
                <tr><td><code>tests/test-e2e-crud.php</code></td><td>42</td><td><?= $t['crud_tests'] ?></td></tr>
                <tr><td><code>tests/test-registration.php</code></td><td>32</td><td><?= $t['reg_tests'] ?></td></tr>
                <tr><td><code>tests/test-api-*.php</code></td><td>~50+</td><td><?= $t['api_tests'] ?></td></tr>
                <tr><td><code>tests/test-mvc-*.php</code></td><td>~50+</td><td><?= $t['mvc_tests'] ?></td></tr>
            </table>
<pre class="dev-code"><span class="comment"># Run all tests</span>
curl -s "http://localhost/tests/test-e2e-crud.php"
docker exec iacc_php php /var/www/html/tests/test-registration.php</pre>
        </div>
    </div>

    <!-- 11. Deployment -->
    <div class="dev-section" id="deploy">
        <h2><i class="fa fa-cloud-upload"></i> 11. <?= $t['sec_deploy'] ?></h2>
        <div class="dev-card">
            <h3><?= $t['deploy_options'] ?></h3>
            <table class="dev-table">
                <tr><th><?= $t['th_target'] ?></th><th><?= $t['th_script'] ?></th><th><?= $t['th_method'] ?></th></tr>
                <tr><td>cPanel</td><td><code>deploy-cpanel.sh</code></td><td>FTP upload</td></tr>
                <tr><td>DigitalOcean</td><td><code>deploy-digitalocean.sh</code></td><td>SSH + Docker</td></tr>
                <tr><td>Docker Prod</td><td><code>docker-compose.prod.yml</code></td><td>Docker Compose</td></tr>
            </table>
<pre class="dev-code"><span class="comment"># Production Docker</span>
docker compose -f docker-compose.prod.yml up -d

<span class="comment"># Production differences:</span>
<span class="comment"># - Docker socket proxy (read-only, secure)</span>
<span class="comment"># - No direct Docker socket mounting</span>
<span class="comment"># - Container management disabled</span></pre>
        </div>
    </div>

    <!-- 12. Conventions -->
    <div class="dev-section" id="conventions">
        <h2><i class="fa fa-code"></i> 12. <?= $t['sec_conventions'] ?></h2>
        <div class="dev-grid">
            <div class="dev-card">
                <h3><?= $t['naming'] ?></h3>
                <table class="dev-table">
                    <tr><td>Controllers</td><td><code>PascalCaseController.php</code></td></tr>
                    <tr><td>Models</td><td><code>PascalCase.php</code></td></tr>
                    <tr><td>Views</td><td><code>kebab-case.php</code></td></tr>
                    <tr><td>Routes</td><td><code>snake_case</code></td></tr>
                    <tr><td>Migrations</td><td><code>NNN_snake_case.sql</code></td></tr>
                    <tr><td>DB Tables</td><td><code>snake_case</code></td></tr>
                </table>
            </div>
            <div class="dev-card">
                <h3><?= $t['security_rules'] ?></h3>
                <ul style="font-size:13px; line-height:1.8; padding-left:18px; margin:0;">
                    <li><?= $t['security_1'] ?></li>
                    <li><?= $t['security_2'] ?></li>
                    <li><?= $t['security_3'] ?></li>
                    <li><?= $t['security_4'] ?></li>
                    <li><?= $t['security_5'] ?></li>
                    <li><?= $t['security_6'] ?></li>
                    <li><?= $t['security_7'] ?></li>
                </ul>
            </div>
        </div>
        <div class="dev-card">
            <h3><?= $t['multi_lang'] ?></h3>
            <p><?= $t['multi_lang_desc'] ?></p>
            <table class="dev-table">
                <tr><th><?= $t['th_context'] ?></th><th><?= $t['th_system'] ?></th><th><?= $t['th_session'] ?></th></tr>
                <tr><td><?= $t['public_landing'] ?></td><td><code>inc/lang/en.php</code> + <code>th.php</code> → <code>__('key')</code></td><td><code>$_SESSION['landing_lang']</code> (string)</td></tr>
                <tr><td><?= $t['inapp_mvc'] ?></td><td><code>$labels[$lang]</code> array at top of view</td><td><code>$_SESSION['lang']</code> (int 0/1)</td></tr>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: #2c3e50; color: white; border-radius: 12px; padding: 24px; text-align: center; margin-top: 20px;">
        <h3 style="margin: 0 0 6px 0; font-size: 18px;"><?= $t['dev_ref_title'] ?></h3>
        <p style="margin: 0; opacity: 0.8; font-size: 13px;"><?= $t['dev_ref_stats'] ?></p>
        <span style="display: inline-block; background: rgba(255,255,255,0.15); padding: 4px 14px; border-radius: 20px; font-size: 12px; margin-top: 10px;">v6.0 • PHP 8.2 • MySQL 5.7 • Docker</span>
    </div>
</div>
