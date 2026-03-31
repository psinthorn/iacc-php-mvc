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
            <h3>Request Flow</h3>
<pre class="dev-code"><span class="comment">// Web Application</span>
Browser → index.php → routes.php → Controller → Model → View

<span class="comment">// REST API</span>
Client → api.php → Controller → Service → JSON Response

<span class="comment">// AI Chat</span>
Browser → ai/chat-stream.php → AIProvider → Ollama/OpenAI → SSE Response</pre>
        </div>
        <div class="dev-card">
            <h3>Directory Structure</h3>
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
                <h3>Backend</h3>
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
                <h3>Frontend</h3>
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
            <h3>Core Tables</h3>
            <table class="dev-table">
                <tr><th>Table</th><th>Purpose</th><th>Key Columns</th></tr>
                <tr><td><code>authorize</code></td><td>Users & authentication</td><td>id, email, name, password, com_id, lang, user_level, registered_via</td></tr>
                <tr><td><code>company</code></td><td>Companies (multi-tenant)</td><td>id, com_name, address, tax_id, registered_via, onboarding_completed</td></tr>
                <tr><td><code>category</code></td><td>Product categories</td><td>id, name, com_id</td></tr>
                <tr><td><code>brand</code></td><td>Brands/suppliers</td><td>id, name, category_id, com_id</td></tr>
                <tr><td><code>type</code></td><td>Product types</td><td>id, name, brand_id, com_id</td></tr>
                <tr><td><code>model</code></td><td>Product models (SKUs)</td><td>id, name, price, type_id, com_id</td></tr>
                <tr><td><code>po</code></td><td>Purchase Orders & Quotations</td><td>id, po_no, com_id, total, status, po_id_new (versioning)</td></tr>
                <tr><td><code>product</code></td><td>PO line items</td><td>id, po_id, type, model, price, quantity</td></tr>
                <tr><td><code>pr</code></td><td>Purchase Requisitions</td><td>id, pr_no, com_id, status</td></tr>
                <tr><td><code>compl</code></td><td>Invoices</td><td>id, inv_no, po_id, total, status</td></tr>
                <tr><td><code>delivery_notes</code></td><td>Delivery tracking</td><td>id, dn_no, po_id, status</td></tr>
                <tr><td><code>voucher</code></td><td>Payment vouchers (money out)</td><td>id, voucher_no, amount, payment_method</td></tr>
                <tr><td><code>receipt</code></td><td>Receipts (money in)</td><td>id, receipt_no, amount, payment_method</td></tr>
                <tr><td><code>expenses</code></td><td>Expense records</td><td>id, amount, category_id, project_id</td></tr>
                <tr><td><code>journal_entries</code></td><td>Double-entry journal</td><td>id, entry_no, account_id, debit, credit</td></tr>
                <tr><td><code>email_verifications</code></td><td>Registration tokens</td><td>id, email, token, payload, expires_at</td></tr>
                <tr><td><code>audit_logs</code></td><td>User action tracking</td><td>id, user_id, action, table_name, record_id</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3>Multi-Tenant Isolation</h3>
            <p>Every data query is filtered by <code>com_id</code> (company ID) from <code>$_SESSION['com_id']</code>. The <code>CompanyFilter</code> singleton handles this:</p>
<pre class="dev-code"><span class="comment">// 19 filtered tables (require company_id)</span>
<span class="variable">$filter</span> = CompanyFilter::getInstance(<span class="variable">$com_id</span>);
<span class="variable">$where</span>  = <span class="variable">$filter</span>->whereCompanyFilter(<span class="string">'po'</span>);
<span class="comment">// Returns: "WHERE po.com_id = '5'"</span>

<span class="comment">// 6 global tables (no filtering): authorize, company, api_keys, ...</span></pre>
        </div>
        <div class="dev-card">
            <h3>Migrations</h3>
            <p>Two mirror locations for MySQL 5.7 compatibility:</p>
            <table class="dev-table">
                <tr><th>Path</th><th>Style</th><th>Use</th></tr>
                <tr><td><code>database/migrations/NNN_name.sql</code></td><td>Simple (IF NOT EXISTS)</td><td>MySQL 8+ or phpMyAdmin</td></tr>
                <tr><td><code>migrations/NNN_name.sql</code></td><td>Stored procedure</td><td>MySQL 5.7 CLI (idempotent)</td></tr>
            </table>
            <p><strong>Current:</strong> 001–021. Run via: <code>mysql -u root -p iacc &lt; migrations/NNN.sql</code></p>
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
            <p><strong>Always use prepared statements.</strong> Legacy HardClass methods (insertDbMax, etc.) exist but use isolated <code>$args</code> arrays per operation to avoid state leakage.</p>
        </div>
    </div>

    <!-- 5. Routing -->
    <div class="dev-section" id="routes">
        <h2><i class="fa fa-road"></i> 5. <?= $t['sec_routes'] ?></h2>
        <div class="dev-card">
            <h3>Route Types</h3>
            <table class="dev-table">
                <tr><th>Type</th><th>Syntax</th><th>Auth</th><th>Layout</th></tr>
                <tr><td>Normal</td><td><code>['Controller', 'method']</code></td><td>Required</td><td>Sidebar + header</td></tr>
                <tr><td>Public</td><td><code>['Controller', 'method', 'public']</code></td><td>None</td><td>Standalone</td></tr>
                <tr><td>Standalone</td><td><code>['Controller', 'method', 'standalone']</code></td><td>Required</td><td>No sidebar</td></tr>
            </table>
<pre class="dev-code"><span class="comment">// app/Config/routes.php</span>
<span class="keyword">return</span> [
    <span class="string">'dashboard'</span>    => [<span class="string">'DashboardController'</span>, <span class="string">'index'</span>],
    <span class="string">'pdf_invoice'</span>  => [<span class="string">'PdfController'</span>, <span class="string">'invoice'</span>, <span class="string">'standalone'</span>],
    <span class="string">'register'</span>     => [<span class="string">'RegistrationController'</span>, <span class="string">'showForm'</span>, <span class="string">'public'</span>],
];</pre>
            <p><strong>175+ routes</strong> currently defined. URL pattern: <code>index.php?page=route_name</code></p>
        </div>
    </div>

    <!-- 6. Sales Channel API -->
    <div class="dev-section" id="api">
        <h2><i class="fa fa-plug"></i> 6. <?= $t['sec_api'] ?></h2>
        <div class="dev-card">
            <h3>API Endpoints</h3>
            <table class="dev-table">
                <tr><th>Method</th><th>Endpoint</th><th>Description</th></tr>
                <tr><td>POST</td><td><code>/api.php?action=orders</code></td><td>Create new order</td></tr>
                <tr><td>GET</td><td><code>/api.php?action=orders</code></td><td>List orders</td></tr>
                <tr><td>GET</td><td><code>/api.php?action=orders&id=N</code></td><td>Get order details</td></tr>
                <tr><td>PUT</td><td><code>/api.php?action=orders&id=N</code></td><td>Update order</td></tr>
                <tr><td>POST</td><td><code>/api.php?action=customers</code></td><td>Create customer</td></tr>
                <tr><td>GET</td><td><code>/api.php?action=products</code></td><td>List products</td></tr>
                <tr><td>POST</td><td><code>/api.php?action=webhooks</code></td><td>Manage webhooks</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3>Authentication</h3>
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
            <h3>Permission Levels</h3>
            <table class="dev-table">
                <tr><th>Level</th><th>Role</th><th>Access</th></tr>
                <tr><td>0</td><td>User</td><td>Assigned company only, create/view documents</td></tr>
                <tr><td>1</td><td>Admin</td><td>All companies, master data, user management</td></tr>
                <tr><td>2</td><td>Super Admin</td><td>Full access: gateway config, tax, audit, dev tools</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3>Session Variables</h3>
            <table class="dev-table">
                <tr><th>Key</th><th>Type</th><th>Description</th></tr>
                <tr><td><code>$_SESSION['user_id']</code></td><td>int</td><td>Current user ID</td></tr>
                <tr><td><code>$_SESSION['com_id']</code></td><td>int</td><td>Current company ID</td></tr>
                <tr><td><code>$_SESSION['user_level']</code></td><td>int</td><td>Permission level (0/1/2)</td></tr>
                <tr><td><code>$_SESSION['lang']</code></td><td>int</td><td>Language (0=EN, 1=TH)</td></tr>
                <tr><td><code>$_SESSION['csrf_token']</code></td><td>string</td><td>CSRF protection token</td></tr>
                <tr><td><code>$_SESSION['com_name']</code></td><td>string</td><td>Current company name</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3>Self-Registration Flow (v6.0)</h3>
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
            <h3>Components</h3>
            <table class="dev-table">
                <tr><th>File</th><th>Purpose</th></tr>
                <tr><td><code>ai/ai-provider.php</code></td><td>AIProvider wrapper — routes to Ollama or OpenAI</td></tr>
                <tr><td><code>ai/ollama-client.php</code></td><td>Local Ollama API client (llama3.2, mistral)</td></tr>
                <tr><td><code>ai/openai-client.php</code></td><td>OpenAI API client (GPT-4o turbo)</td></tr>
                <tr><td><code>ai/agent-executor.php</code></td><td>Agent tool execution with permission checks</td></tr>
                <tr><td><code>ai/chat-handler.php</code></td><td>Chat message processing</td></tr>
                <tr><td><code>ai/chat-stream.php</code></td><td>SSE streaming endpoint</td></tr>
                <tr><td><code>ai/schema-discovery.php</code></td><td>Auto-discover DB schema for AI context</td></tr>
            </table>
        </div>
    </div>

    <!-- 9. Docker -->
    <div class="dev-section" id="docker">
        <h2><i class="fa fa-server"></i> 9. <?= $t['sec_docker'] ?></h2>
        <div class="dev-card">
            <h3>Containers</h3>
            <table class="dev-table">
                <tr><th>Container</th><th>Service</th><th>Port</th></tr>
                <tr><td><code>iacc_nginx</code></td><td>Nginx reverse proxy</td><td>80, 443</td></tr>
                <tr><td><code>iacc_php</code></td><td>PHP 8.2 FPM</td><td>9000 (internal)</td></tr>
                <tr><td><code>iacc_mysql</code></td><td>MySQL 5.7</td><td>3306</td></tr>
                <tr><td><code>iacc_phpmyadmin</code></td><td>phpMyAdmin</td><td>8083</td></tr>
                <tr><td><code>iacc_mailhog_server</code></td><td>MailHog SMTP + Web</td><td>1025, 8025</td></tr>
                <tr><td><code>iacc_ollama</code></td><td>Ollama AI</td><td>11434</td></tr>
            </table>
        </div>
        <div class="dev-card">
            <h3>Key Commands</h3>
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
            <h3>Test Suites</h3>
            <table class="dev-table">
                <tr><th>File</th><th>Tests</th><th>Covers</th></tr>
                <tr><td><code>tests/test-e2e-crud.php</code></td><td>42</td><td>Core CRUD operations, HardClass methods</td></tr>
                <tr><td><code>tests/test-registration.php</code></td><td>32</td><td>Self-registration, email verification, onboarding</td></tr>
                <tr><td><code>tests/test-api-*.php</code></td><td>~50+</td><td>Sales Channel API endpoints</td></tr>
                <tr><td><code>tests/test-mvc-*.php</code></td><td>~50+</td><td>MVC controllers, models, routes</td></tr>
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
            <h3>Deployment Options</h3>
            <table class="dev-table">
                <tr><th>Target</th><th>Script</th><th>Method</th></tr>
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
                <h3>Naming</h3>
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
                <h3>Security Rules</h3>
                <ul style="font-size:13px; line-height:1.8; padding-left:18px; margin:0;">
                    <li>Prepared statements for all queries</li>
                    <li>CSRF token on all POST forms</li>
                    <li><code>htmlspecialchars()</code> on all output</li>
                    <li>Company filter on all data queries</li>
                    <li>Rate limiting on auth endpoints</li>
                    <li>Isolated <code>$args</code> arrays per DB operation</li>
                    <li>Input validation at system boundaries</li>
                </ul>
            </div>
        </div>
        <div class="dev-card">
            <h3>Multi-Language (Mandatory)</h3>
            <p>Every new module <strong>must</strong> support bilingual (EN/TH) from day one. Two systems:</p>
            <table class="dev-table">
                <tr><th>Context</th><th>System</th><th>Session</th></tr>
                <tr><td>Public/Landing pages</td><td><code>inc/lang/en.php</code> + <code>th.php</code> → <code>__('key')</code></td><td><code>$_SESSION['landing_lang']</code> (string)</td></tr>
                <tr><td>In-app MVC views</td><td><code>$labels[$lang]</code> array at top of view</td><td><code>$_SESSION['lang']</code> (int 0/1)</td></tr>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: #2c3e50; color: white; border-radius: 12px; padding: 24px; text-align: center; margin-top: 20px;">
        <h3 style="margin: 0 0 6px 0; font-size: 18px;">iACC Developer Reference</h3>
        <p style="margin: 0; opacity: 0.8; font-size: 13px;">40+ Controllers • 30+ Models • 117+ Views • 175+ Routes • 192+ Tests</p>
        <span style="display: inline-block; background: rgba(255,255,255,0.15); padding: 4px 14px; border-radius: 20px; font-size: 12px; margin-top: 10px;">v6.0 • PHP 8.2 • MySQL 5.7 • Docker</span>
    </div>
</div>
