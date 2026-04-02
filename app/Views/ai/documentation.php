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

<!-- Overview Stats -->
<div class="row">
    <div class="col-md-3">
        <div class="panel panel-primary">
            <div class="panel-heading text-center">Total Tools</div>
            <div class="panel-body text-center">
                <span class="stat-value"><?=count($allTools)?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-success">
            <div class="panel-heading text-center">Business Tools</div>
            <div class="panel-body text-center">
                <span class="stat-value"><?=count($agentTools)?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-info">
            <div class="panel-heading text-center">Schema Tools</div>
            <div class="panel-body text-center">
                <span class="stat-value"><?=count($schemaTools)?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-heading text-center">Providers</div>
            <div class="panel-body text-center">
                <span class="stat-value">2</span>
                <small>OpenAI / Ollama</small>
            </div>
        </div>
    </div>
</div>

<!-- Data Flow Diagram -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-sitemap"></i> AI Data Flow Architecture
            </div>
            <div class="panel-body">
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
    </div>
</div>

<!-- File Structure -->
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-folder-open"></i> AI File Structure
            </div>
            <div class="panel-body">
                <pre class="file-tree">
ai/
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
└── db-schema-hash.txt    # Change detection hash
                </pre>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-link"></i> AI Tools Menu
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Menu Item</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="index.php?page=ai_settings"><i class="fa fa-cogs"></i> AI Settings</a></td>
                            <td>Configure provider, API keys, models</td>
                        </tr>
                        <tr>
                            <td><a href="index.php?page=test_crud_ai"><i class="fa fa-flask"></i> AI CRUD Test</a></td>
                            <td>Interactive AI chat interface</td>
                        </tr>
                        <tr>
                            <td><a href="index.php?page=ai_chat_history"><i class="fa fa-comments"></i> Chat History</a></td>
                            <td>View past conversations</td>
                        </tr>
                        <tr>
                            <td><a href="index.php?page=ai_schema_browser"><i class="fa fa-database"></i> Schema Browser</a></td>
                            <td>Explore database structure</td>
                        </tr>
                        <tr>
                            <td><a href="index.php?page=ai_action_log"><i class="fa fa-list-alt"></i> Action Log</a></td>
                            <td>Audit tool executions</td>
                        </tr>
                        <tr>
                            <td><a href="index.php?page=ai_schema_refresh"><i class="fa fa-refresh"></i> Refresh Schema</a></td>
                            <td>Update schema cache</td>
                        </tr>
                        <tr class="info">
                            <td><a href="index.php?page=ai_documentation"><i class="fa fa-book"></i> Documentation</a></td>
                            <td>This page - architecture overview</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tool Categories -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-wrench"></i> Available Tools (<?=count($allTools)?>)
            </div>
            <div class="panel-body">
                <div class="row">
                    <!-- Business Tools -->
                    <div class="col-md-6">
                        <h4><i class="fa fa-briefcase"></i> Business Tools (<?=count($agentTools)?>)</h4>
                        <div class="table-responsive">
                        <table class="table table-condensed table-striped tool-table">
                            <thead>
                                <tr>
                                    <th>Tool</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agentTools as $tool): ?>
                                <tr>
                                    <td><code><?=htmlspecialchars($tool['name'], ENT_QUOTES, 'UTF-8')?></code></td>
                                    <td>
                                        <?php if ($tool['operation'] === 'read'): ?>
                                        <span class="label label-success">READ</span>
                                        <?php elseif ($tool['operation'] === 'write'): ?>
                                        <span class="label label-warning">WRITE</span>
                                        <?php else: ?>
                                        <span class="label label-default">UTIL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?=htmlspecialchars(substr($tool['description'], 0, 60), ENT_QUOTES, 'UTF-8')?>...</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <!-- Schema Tools -->
                    <div class="col-md-6">
                        <h4><i class="fa fa-database"></i> Schema Discovery Tools (<?=count($schemaTools)?>)</h4>
                        <div class="table-responsive">
                        <table class="table table-condensed table-striped tool-table">
                            <thead>
                                <tr>
                                    <th>Tool</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schemaTools as $tool): ?>
                                <tr>
                                    <td><code><?=htmlspecialchars($tool['name'], ENT_QUOTES, 'UTF-8')?></code></td>
                                    <td><span class="label label-info">SCHEMA</span></td>
                                    <td><?=htmlspecialchars(substr($tool['description'], 0, 60), ENT_QUOTES, 'UTF-8')?>...</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        
                        <h4 class="section-heading"><i class="fa fa-key"></i> Key Database Tables</h4>
                        <div class="table-responsive">
                        <table class="table table-condensed tool-table">
                            <tr><td><code>iv</code></td><td>Invoices (tex→po.id)</td></tr>
                            <tr><td><code>po</code></td><td>Purchase Orders (ref→pr.id)</td></tr>
                            <tr><td><code>pr</code></td><td>Quotations (cus_id, ven_id→company)</td></tr>
                            <tr><td><code>product</code></td><td>Line items (po_id, price, quantity)</td></tr>
                            <tr><td><code>pay</code></td><td>Payments (po_id, volumn=amount)</td></tr>
                            <tr><td><code>company</code></td><td>Companies (customers & vendors)</td></tr>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Provider Configuration -->
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-cloud"></i> OpenAI Configuration
            </div>
            <div class="panel-body">
                <table class="table">
                    <tr><th>Model</th><td>gpt-4o-mini (default)</td></tr>
                    <tr><th>Endpoint</th><td>https://api.openai.com/v1/chat/completions</td></tr>
                    <tr><th>Features</th><td>Function calling, streaming, tool use</td></tr>
                    <tr><th>Timeout</th><td>60 seconds</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-server"></i> Ollama Configuration
            </div>
            <div class="panel-body">
                <table class="table">
                    <tr><th>Model</th><td>llama3.2:3b (default)</td></tr>
                    <tr><th>Endpoint</th><td>http://ollama:11434/api/chat</td></tr>
                    <tr><th>Features</th><td>Local inference, tool use</td></tr>
                    <tr><th>Timeout</th><td>120 seconds</td></tr>
                    <tr><th>Status</th><td>OFF by default (CPU intensive)</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

</div><!-- /.ai-documentation-page -->

<style>
.ai-documentation-page .stat-value {
    font-size: 32px;
    font-weight: 700;
    display: block;
    margin: 0;
}
.ai-documentation-page .flow-diagram {
    text-align: center;
    padding: 20px;
}
.ai-documentation-page .flow-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.ai-documentation-page .flow-row-spacer {
    margin-top: 20px;
}
.ai-documentation-page .flow-placeholder {
    visibility: hidden;
}
.ai-documentation-page .flow-box {
    padding: 15px 20px;
    border-radius: 8px;
    min-width: 140px;
    text-align: center;
}
.ai-documentation-page .flow-arrow {
    font-size: 24px;
    color: #666;
    padding: 0 10px;
}
.ai-documentation-page .flow-arrow-down {
    font-size: 24px;
    color: #666;
}
.ai-documentation-page .user-box { background: #e3f2fd; border: 2px solid #2196F3; }
.ai-documentation-page .handler-box { background: #fff3e0; border: 2px solid #ff9800; }
.ai-documentation-page .provider-box { background: #f3e5f5; border: 2px solid #9c27b0; }
.ai-documentation-page .tools-box { background: #e8f5e9; border: 2px solid #4CAF50; }
.ai-documentation-page .executor-box { background: #fce4ec; border: 2px solid #e91e63; }
.ai-documentation-page .ai-box { background: #e0f7fa; border: 2px solid #00bcd4; }
.ai-documentation-page .db-box { background: #fff8e1; border: 2px solid #ffc107; }
.ai-documentation-page .result-box { background: #f1f8e9; border: 2px solid #8bc34a; }
.ai-documentation-page .response-box { background: #e8eaf6; border: 2px solid #3f51b5; }
.ai-documentation-page .file-tree {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    font-size: 12px;
    line-height: 1.8;
}
.ai-documentation-page .file-tree a {
    color: #2196F3;
}
.ai-documentation-page .tool-table {
    font-size: 12px;
}
.ai-documentation-page .section-heading {
    margin-top: 30px;
}
@media (max-width: 768px) {
    .ai-documentation-page .flow-row {
        flex-direction: column;
    }
    .ai-documentation-page .flow-arrow {
        transform: rotate(90deg);
    }
    .ai-documentation-page .flow-placeholder {
        display: none;
    }
}
</style>
