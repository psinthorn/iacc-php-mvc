<?php
/**
 * AI System Documentation
 * 
 * Overview of AI integration, tools, and data flow
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-05
 */

// Page context (included from index.php)
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
    echo '<div class="alert alert-danger">Access denied. Super Admin required.</div>';
    return;
}

require_once __DIR__ . '/../../../ai/agent-tools.php';

// Get tool counts
$allTools = getAllTools();
$agentTools = getAgentTools();
$schemaTools = getSchemaTools();
?>

<div class="ai-documentation-page">
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">
            <i class="fa fa-book"></i> AI System Documentation
            <small>Architecture & Data Flow</small>
        </h3>
        <?php $currentPage = 'ai_documentation'; include __DIR__ . '/_nav.php'; ?>
    </div>
</div>

<!-- Hero Header with Stats -->
<div class="doc-hero">
    <div class="hero-content">
        <div class="hero-icon"><i class="fa fa-book"></i></div>
        <div class="hero-text">
            <h2>AI System Documentation</h2>
            <p>Architecture overview, data flow, tool registry, and provider configuration</p>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><span class="hero-stat-value"><?=count($allTools)?></span><span class="hero-stat-label">Total Tools</span></div>
        <div class="hero-stat"><span class="hero-stat-value"><?=count($agentTools)?></span><span class="hero-stat-label">Business</span></div>
        <div class="hero-stat"><span class="hero-stat-value"><?=count($schemaTools)?></span><span class="hero-stat-label">Schema</span></div>
        <div class="hero-stat"><span class="hero-stat-value">2</span><span class="hero-stat-label">Providers</span></div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="tab-container">
    <button class="tab-btn active" data-tab="architecture"><i class="fa fa-sitemap"></i> Architecture</button>
    <button class="tab-btn" data-tab="tools"><i class="fa fa-wrench"></i> Tools (<?=count($allTools)?>)</button>
    <button class="tab-btn" data-tab="files"><i class="fa fa-folder-open"></i> Files & Config</button>
    <button class="tab-btn" data-tab="menu"><i class="fa fa-th-list"></i> Quick Links</button>
</div>

<!-- Tab 1: Architecture -->
<div class="tab-content active" id="tab-architecture">
    <div class="ai-card">
        <div class="ai-card-header"><i class="fa fa-sitemap"></i> AI Data Flow Architecture</div>
        <div class="ai-card-body">
            <div class="flow-diagram">
                <div class="flow-row">
                    <div class="flow-box user-box">
                        <i class="fa fa-user fa-2x"></i>
                        <div>User Message</div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box handler-box">
                        <i class="fa fa-cogs fa-2x"></i>
                        <div>Chat Handler</div>
                        <small>ai/chat-handler.php</small>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box provider-box">
                        <i class="fa fa-cloud fa-2x"></i>
                        <div>AI Provider</div>
                        <small>ai/ai-provider.php</small>
                    </div>
                </div>
                
                <div class="flow-row flow-row-spacer">
                    <div class="flow-box flow-placeholder"></div>
                    <div class="flow-arrow flow-placeholder"></div>
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-arrow flow-placeholder"></div>
                    <div class="flow-arrow-down">↓</div>
                </div>
                
                <div class="flow-row">
                    <div class="flow-box tools-box">
                        <i class="fa fa-wrench fa-2x"></i>
                        <div>getAllTools()</div>
                        <small><?=count($allTools)?> tools</small>
                    </div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-box executor-box">
                        <i class="fa fa-play fa-2x"></i>
                        <div>Agent Executor</div>
                        <small>ai/agent-executor.php</small>
                    </div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-box ai-box">
                        <i class="fa fa-microchip fa-2x"></i>
                        <div>OpenAI / Ollama</div>
                        <small>Tool Calls</small>
                    </div>
                </div>
                
                <div class="flow-row flow-row-spacer">
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-arrow flow-placeholder"></div>
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-arrow flow-placeholder"></div>
                    <div class="flow-box flow-placeholder"></div>
                </div>
                
                <div class="flow-row">
                    <div class="flow-box db-box">
                        <i class="fa fa-database fa-2x"></i>
                        <div>Database</div>
                        <small>MySQL/PDO</small>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box result-box">
                        <i class="fa fa-check fa-2x"></i>
                        <div>Results</div>
                        <small>JSON Response</small>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box response-box">
                        <i class="fa fa-comments fa-2x"></i>
                        <div>AI Response</div>
                        <small>To User</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Tables -->
    <div class="ai-card">
        <div class="ai-card-header"><i class="fa fa-key"></i> Key Database Tables</div>
        <div class="ai-card-body">
            <div class="row">
                <div class="col-md-4"><div class="db-table-card"><code>iv</code><span>Invoices (tex→po.id)</span></div></div>
                <div class="col-md-4"><div class="db-table-card"><code>po</code><span>Purchase Orders (ref→pr.id)</span></div></div>
                <div class="col-md-4"><div class="db-table-card"><code>pr</code><span>Quotations (cus_id, ven_id→company)</span></div></div>
                <div class="col-md-4"><div class="db-table-card"><code>product</code><span>Line items (po_id, price, quantity)</span></div></div>
                <div class="col-md-4"><div class="db-table-card"><code>pay</code><span>Payments (po_id, volumn=amount)</span></div></div>
                <div class="col-md-4"><div class="db-table-card"><code>company</code><span>Companies (customers & vendors)</span></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Tab 2: Tools -->
<div class="tab-content" id="tab-tools">
    <div class="row">
        <div class="col-md-6">
            <div class="ai-card">
                <div class="ai-card-header"><i class="fa fa-briefcase"></i> Business Tools (<?=count($agentTools)?>)</div>
                <div class="ai-card-body">
                    <div class="table-responsive">
                    <table class="table table-condensed table-striped tool-table">
                        <thead><tr><th>Tool</th><th>Type</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php foreach ($agentTools as $tool): ?>
                            <tr>
                                <td><code><?=htmlspecialchars($tool['name'], ENT_QUOTES, 'UTF-8')?></code></td>
                                <td>
                                    <?php if ($tool['operation'] === 'read'): ?>
                                    <span class="op-badge op-read">READ</span>
                                    <?php elseif ($tool['operation'] === 'write'): ?>
                                    <span class="op-badge op-write">WRITE</span>
                                    <?php else: ?>
                                    <span class="op-badge op-util">UTIL</span>
                                    <?php endif; ?>
                                </td>
                                <td><?=htmlspecialchars(substr($tool['description'], 0, 60), ENT_QUOTES, 'UTF-8')?>...</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ai-card">
                <div class="ai-card-header"><i class="fa fa-database"></i> Schema Discovery Tools (<?=count($schemaTools)?>)</div>
                <div class="ai-card-body">
                    <div class="table-responsive">
                    <table class="table table-condensed table-striped tool-table">
                        <thead><tr><th>Tool</th><th>Type</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php foreach ($schemaTools as $tool): ?>
                            <tr>
                                <td><code><?=htmlspecialchars($tool['name'], ENT_QUOTES, 'UTF-8')?></code></td>
                                <td><span class="op-badge op-schema">SCHEMA</span></td>
                                <td><?=htmlspecialchars(substr($tool['description'], 0, 60), ENT_QUOTES, 'UTF-8')?>...</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab 3: Files & Config -->
<div class="tab-content" id="tab-files">
    <div class="row">
        <div class="col-md-6">
            <div class="ai-card">
                <div class="ai-card-header"><i class="fa fa-folder-open"></i> AI File Structure</div>
                <div class="ai-card-body">
                    <pre class="code-block file-tree">ai/
├── <a href="index.php?page=ai_settings">ai-provider.php</a>      # Provider abstraction (OpenAI/Ollama)
├── <a href="#">openai-client.php</a>    # OpenAI API client
├── <a href="#">ollama-client.php</a>    # Ollama API client
├── <a href="#">chat-handler.php</a>     # Main chat API endpoint
├── <a href="#">agent-tools.php</a>      # Tool definitions (23 tools)
├── <a href="#">agent-executor.php</a>   # Tool execution engine
├── <a href="index.php?page=ai_schema_browser">schema-discovery.php</a> # DB schema discovery
├── config.php            # AI configuration
└── prompts/              # System prompts

cache/
├── db-schema.json        # Full schema cache
├── db-schema.md          # Markdown documentation
├── db-schema-compact.txt # Compact for AI context
└── db-schema-hash.txt    # Change detection hash</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ai-card">
                <div class="ai-card-header"><i class="fa fa-cloud"></i> OpenAI Configuration</div>
                <div class="ai-card-body">
                    <div class="config-list">
                        <div class="config-row"><span class="config-key">Model</span><span class="config-val">gpt-4o-mini (default)</span></div>
                        <div class="config-row"><span class="config-key">Endpoint</span><span class="config-val">api.openai.com/v1/chat/completions</span></div>
                        <div class="config-row"><span class="config-key">Features</span><span class="config-val">Function calling, streaming, tool use</span></div>
                        <div class="config-row"><span class="config-key">Timeout</span><span class="config-val">60 seconds</span></div>
                    </div>
                </div>
            </div>
            <div class="ai-card">
                <div class="ai-card-header"><i class="fa fa-server"></i> Ollama Configuration</div>
                <div class="ai-card-body">
                    <div class="config-list">
                        <div class="config-row"><span class="config-key">Model</span><span class="config-val">llama3.2:3b (default)</span></div>
                        <div class="config-row"><span class="config-key">Endpoint</span><span class="config-val">ollama:11434/api/chat</span></div>
                        <div class="config-row"><span class="config-key">Features</span><span class="config-val">Local inference, tool use</span></div>
                        <div class="config-row"><span class="config-key">Timeout</span><span class="config-val">120 seconds</span></div>
                        <div class="config-row"><span class="config-key">Status</span><span class="config-val"><span class="op-badge op-util">OFF by default</span></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab 4: Quick Links -->
<div class="tab-content" id="tab-menu">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="ai-card">
                <div class="ai-card-header"><i class="fa fa-th-list"></i> AI Admin Pages</div>
                <div class="ai-card-body">
                    <div class="menu-list">
                        <a href="index.php?page=ai_settings" class="menu-item">
                            <span class="menu-icon"><i class="fa fa-cogs"></i></span>
                            <span class="menu-text"><strong>AI Settings</strong><small>Configure provider, API keys, models</small></span>
                        </a>
                        <a href="index.php?page=test_crud_ai" class="menu-item">
                            <span class="menu-icon" style="background:#fff3e0;color:#f39c12;"><i class="fa fa-flask"></i></span>
                            <span class="menu-text"><strong>AI CRUD Test</strong><small>Interactive AI chat interface</small></span>
                        </a>
                        <a href="index.php?page=ai_chat_history" class="menu-item">
                            <span class="menu-icon" style="background:#e8f5e9;color:#27ae60;"><i class="fa fa-comments"></i></span>
                            <span class="menu-text"><strong>Chat History</strong><small>View past conversations</small></span>
                        </a>
                        <a href="index.php?page=ai_schema_browser" class="menu-item">
                            <span class="menu-icon" style="background:#e3f2fd;color:#2196F3;"><i class="fa fa-database"></i></span>
                            <span class="menu-text"><strong>Schema Browser</strong><small>Explore database structure</small></span>
                        </a>
                        <a href="index.php?page=ai_action_log" class="menu-item">
                            <span class="menu-icon" style="background:#fce4ec;color:#e91e63;"><i class="fa fa-list-alt"></i></span>
                            <span class="menu-text"><strong>Action Log</strong><small>Audit tool executions</small></span>
                        </a>
                        <a href="index.php?page=ai_schema_refresh" class="menu-item">
                            <span class="menu-icon" style="background:#e0f7fa;color:#00bcd4;"><i class="fa fa-refresh"></i></span>
                            <span class="menu-text"><strong>Refresh Schema</strong><small>Update schema cache</small></span>
                        </a>
                        <a href="index.php?page=ai_documentation" class="menu-item active">
                            <span class="menu-icon"><i class="fa fa-book"></i></span>
                            <span class="menu-text"><strong>Documentation</strong><small>This page — architecture overview</small></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- /.ai-documentation-page -->

<style>
/* Page spacing */
.ai-documentation-page { padding: 0 15px; }

/* Hero Header */
.ai-documentation-page .doc-hero {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}
.ai-documentation-page .hero-content {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}
.ai-documentation-page .hero-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}
.ai-documentation-page .hero-text h2 { margin: 0; font-size: 24px; font-weight: 700; }
.ai-documentation-page .hero-text p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
.ai-documentation-page .hero-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.ai-documentation-page .hero-stat {
    background: rgba(255,255,255,0.15);
    padding: 10px 20px;
    border-radius: 10px;
    text-align: center;
    min-width: 90px;
    border: 1px solid rgba(255,255,255,0.2);
}
.ai-documentation-page .hero-stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
}
.ai-documentation-page .hero-stat-label {
    display: block;
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Tabs */
.ai-documentation-page .tab-container {
    display: flex;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 25px;
    gap: 0;
    flex-wrap: wrap;
}
.ai-documentation-page .tab-btn {
    padding: 12px 24px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.ai-documentation-page .tab-btn:hover { color: #667eea; }
.ai-documentation-page .tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
    font-weight: 600;
}
.ai-documentation-page .tab-content { display: none; animation: docFadeIn 0.3s ease; }
.ai-documentation-page .tab-content.active { display: block; }

/* Cards */
.ai-documentation-page .ai-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.ai-documentation-page .ai-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.ai-documentation-page .ai-card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 15px;
}
.ai-documentation-page .ai-card-header i { color: #667eea; margin-right: 8px; }
.ai-documentation-page .ai-card-body { padding: 20px; }

/* Flow diagram */
.ai-documentation-page .flow-diagram { text-align: center; padding: 25px 10px; }
.ai-documentation-page .flow-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.ai-documentation-page .flow-row-spacer { margin-top: 15px; }
.ai-documentation-page .flow-placeholder { visibility: hidden; }
.ai-documentation-page .flow-box {
    padding: 15px 20px;
    border-radius: 12px;
    min-width: 140px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ai-documentation-page .flow-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.ai-documentation-page .flow-arrow { font-size: 24px; color: #aaa; padding: 0 10px; }
.ai-documentation-page .flow-arrow-down { font-size: 24px; color: #aaa; }
.ai-documentation-page .user-box { background: #e3f2fd; border: 2px solid #2196F3; }
.ai-documentation-page .handler-box { background: #fff3e0; border: 2px solid #ff9800; }
.ai-documentation-page .provider-box { background: #f3e5f5; border: 2px solid #9c27b0; }
.ai-documentation-page .tools-box { background: #e8f5e9; border: 2px solid #4CAF50; }
.ai-documentation-page .executor-box { background: #fce4ec; border: 2px solid #e91e63; }
.ai-documentation-page .ai-box { background: #e0f7fa; border: 2px solid #00bcd4; }
.ai-documentation-page .db-box { background: #fff8e1; border: 2px solid #ffc107; }
.ai-documentation-page .result-box { background: #f1f8e9; border: 2px solid #8bc34a; }
.ai-documentation-page .response-box { background: #e8eaf6; border: 2px solid #3f51b5; }

/* DB table cards */
.ai-documentation-page .db-table-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: transform 0.2s, background 0.2s;
}
.ai-documentation-page .db-table-card:hover {
    transform: translateX(4px);
    background: #f0f4ff;
}
.ai-documentation-page .db-table-card code {
    background: #667eea;
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    min-width: 65px;
    text-align: center;
}
.ai-documentation-page .db-table-card span { font-size: 13px; color: #555; }

/* Code / file tree */
.ai-documentation-page .code-block {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 18px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.8;
    overflow-x: auto;
    margin: 0;
}
.ai-documentation-page .code-block a,
.ai-documentation-page .file-tree a { color: #66d9ef; }
.ai-documentation-page .code-block a:hover,
.ai-documentation-page .file-tree a:hover { color: #a6e22e; text-decoration: none; }

/* Menu list */
.ai-documentation-page .menu-list { display: flex; flex-direction: column; gap: 4px; }
.ai-documentation-page .menu-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 10px;
    color: #333;
    text-decoration: none;
    transition: all 0.2s;
}
.ai-documentation-page .menu-item:hover {
    background: #f0f4ff;
    color: #667eea;
    text-decoration: none;
    transform: translateX(4px);
}
.ai-documentation-page .menu-item.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}
.ai-documentation-page .menu-item.active small { color: rgba(255,255,255,0.7); }
.ai-documentation-page .menu-icon {
    width: 40px;
    height: 40px;
    background: #f0f4ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #667eea;
    flex-shrink: 0;
}
.ai-documentation-page .menu-item.active .menu-icon { background: rgba(255,255,255,0.2); color: #fff; }
.ai-documentation-page .menu-text { display: flex; flex-direction: column; }
.ai-documentation-page .menu-text strong { font-size: 14px; }
.ai-documentation-page .menu-text small { font-size: 12px; color: #888; margin-top: 2px; }

/* Tool table */
.ai-documentation-page .tool-table { font-size: 12px; }
.ai-documentation-page .tool-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
}
.ai-documentation-page .tool-table code {
    background: #f0f4ff;
    color: #667eea;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
}

/* Operation badges */
.ai-documentation-page .op-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.ai-documentation-page .op-read { background: #d4edda; color: #155724; }
.ai-documentation-page .op-write { background: #fff3cd; color: #856404; }
.ai-documentation-page .op-util { background: #e2e3e5; color: #383d41; }
.ai-documentation-page .op-schema { background: #cce5ff; color: #004085; }

/* Config list */
.ai-documentation-page .config-list { display: flex; flex-direction: column; }
.ai-documentation-page .config-row {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}
.ai-documentation-page .config-row:last-child { border-bottom: none; }
.ai-documentation-page .config-key {
    width: 100px;
    font-weight: 600;
    font-size: 13px;
    color: #555;
    flex-shrink: 0;
}
.ai-documentation-page .config-val { font-size: 13px; color: #333; word-break: break-all; }

/* Animations */
@keyframes docFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .ai-documentation-page .flow-row { flex-direction: column; }
    .ai-documentation-page .flow-arrow { transform: rotate(90deg); }
    .ai-documentation-page .flow-placeholder { display: none; }
    .ai-documentation-page .hero-content { flex-direction: column; text-align: center; }
    .ai-documentation-page .hero-stats { justify-content: center; }
    .ai-documentation-page .tab-btn { padding: 10px 14px; font-size: 13px; }
}
</style>

<script>
(function() {
    var page = document.querySelector('.ai-documentation-page');
    if (!page) return;
    page.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            page.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
            page.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            var tabId = 'tab-' + this.getAttribute('data-tab');
            var tab = document.getElementById(tabId);
            if (tab) tab.classList.add('active');
        });
    });
})();
</script>
