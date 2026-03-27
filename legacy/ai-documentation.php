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

require_once __DIR__ . '/ai/agent-tools.php';

// Get tool counts
$allTools = getAllTools();
$agentTools = getAgentTools();
$schemaTools = getSchemaTools();
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="fa fa-book"></i> AI System Documentation
            <small>Architecture & Data Flow</small>
        </h1>
    </div>
</div>

<!-- Overview Stats -->
<div class="row">
    <div class="col-md-3">
        <div class="panel panel-primary">
            <div class="panel-heading text-center">Total Tools</div>
            <div class="panel-body text-center">
                <h2><?=count($allTools)?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-success">
            <div class="panel-heading text-center">Business Tools</div>
            <div class="panel-body text-center">
                <h2><?=count($agentTools)?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-info">
            <div class="panel-heading text-center">Schema Tools</div>
            <div class="panel-body text-center">
                <h2><?=count($schemaTools)?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-heading text-center">Providers</div>
            <div class="panel-body text-center">
                <h2>2</h2>
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
                    
                    <div class="flow-row" style="margin-top: 20px;">
                        <div class="flow-box" style="visibility: hidden;"></div>
                        <div class="flow-arrow" style="visibility: hidden;"></div>
                        <div class="flow-arrow-down">↓</div>
                        <div class="flow-arrow" style="visibility: hidden;"></div>
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
                            <i class="fa fa-robot fa-2x"></i>
                            <div>OpenAI / Ollama</div>
                            <small>Tool Calls</small>
                        </div>
                    </div>
                    
                    <div class="flow-row" style="margin-top: 20px;">
                        <div class="flow-arrow-down">↓</div>
                        <div class="flow-arrow" style="visibility: hidden;"></div>
                        <div class="flow-arrow-down">↓</div>
                        <div class="flow-arrow" style="visibility: hidden;"></div>
                        <div class="flow-box" style="visibility: hidden;"></div>
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
                        <table class="table table-condensed table-striped" style="font-size: 12px;">
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
                                    <td><code><?=$tool['name']?></code></td>
                                    <td>
                                        <?php if ($tool['operation'] === 'read'): ?>
                                        <span class="label label-success">READ</span>
                                        <?php elseif ($tool['operation'] === 'write'): ?>
                                        <span class="label label-warning">WRITE</span>
                                        <?php else: ?>
                                        <span class="label label-default">UTIL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?=substr($tool['description'], 0, 60)?>...</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Schema Tools -->
                    <div class="col-md-6">
                        <h4><i class="fa fa-database"></i> Schema Discovery Tools (<?=count($schemaTools)?>)</h4>
                        <table class="table table-condensed table-striped" style="font-size: 12px;">
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
                                    <td><code><?=$tool['name']?></code></td>
                                    <td><span class="label label-info">SCHEMA</span></td>
                                    <td><?=substr($tool['description'], 0, 60)?>...</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <h4 style="margin-top: 30px;"><i class="fa fa-key"></i> Key Database Tables</h4>
                        <table class="table table-condensed" style="font-size: 12px;">
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

<style>
.flow-diagram {
    text-align: center;
    padding: 20px;
}
.flow-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}
.flow-box {
    padding: 15px 20px;
    border-radius: 8px;
    min-width: 140px;
    text-align: center;
}
.flow-arrow {
    font-size: 24px;
    color: #666;
    padding: 0 10px;
}
.flow-arrow-down {
    font-size: 24px;
    color: #666;
}
.user-box { background: #e3f2fd; border: 2px solid #2196F3; }
.handler-box { background: #fff3e0; border: 2px solid #ff9800; }
.provider-box { background: #f3e5f5; border: 2px solid #9c27b0; }
.tools-box { background: #e8f5e9; border: 2px solid #4CAF50; }
.executor-box { background: #fce4ec; border: 2px solid #e91e63; }
.ai-box { background: #e0f7fa; border: 2px solid #00bcd4; }
.db-box { background: #fff8e1; border: 2px solid #ffc107; }
.result-box { background: #f1f8e9; border: 2px solid #8bc34a; }
.response-box { background: #e8eaf6; border: 2px solid #3f51b5; }

.file-tree {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    font-size: 12px;
    line-height: 1.8;
}
.file-tree a {
    color: #2196F3;
}

.panel-body h2 {
    margin: 0;
}
</style>
