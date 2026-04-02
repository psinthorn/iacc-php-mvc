<?php
/** @var array|null $cached */
/** @var bool $autoRefresh */
?>
<div class="ai-schema-refresh-page">
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">
            <i class="fa fa-refresh"></i> Schema Refresh
            <small>Keep AI in sync with database changes</small>
        </h3>
        <?php $currentPage = 'ai_schema_refresh'; include __DIR__ . '/_nav.php'; ?>
    </div>
</div>

<!-- Hero Header -->
<div class="refresh-hero">
    <div class="hero-content">
        <div class="hero-icon"><i class="fa fa-refresh"></i></div>
        <div class="hero-text">
            <h2>Schema Refresh</h2>
            <p>Keep the AI schema cache in sync with your database changes</p>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <span class="hero-stat-value" id="cache-status-icon"><?=$cached ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>'?></span>
            <span class="hero-stat-label"><?=$cached ? 'Cached' : 'Not Cached'?></span>
        </div>
        <div class="hero-stat">
            <span class="hero-stat-value" id="table-count"><?=$cached ? count($cached['tables']) : 0?></span>
            <span class="hero-stat-label">Tables</span>
        </div>
        <div class="hero-stat">
            <span class="hero-stat-value" id="auto-status-icon"><?=$autoRefresh ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>'?></span>
            <span class="hero-stat-label">Auto-Refresh</span>
        </div>
    </div>
</div>

<!-- Controls -->
<div class="ai-card">
    <div class="ai-card-header"><i class="fa fa-cogs"></i> Schema Refresh Controls</div>
    <div class="ai-card-body">
        <div class="controls-grid">
            <div class="control-block">
                <div class="control-icon" style="background:#e3f2fd;color:#2196F3;"><i class="fa fa-refresh"></i></div>
                <h5>Manual Refresh</h5>
                <p>Refresh schema cache immediately</p>
                <button class="action-btn primary" onclick="refreshSchema()"><i class="fa fa-refresh"></i> Refresh Now</button>
            </div>
            <div class="control-block">
                <div class="control-icon" style="background:#e8f5e9;color:#4CAF50;"><i class="fa fa-magic"></i></div>
                <h5>Auto-Refresh</h5>
                <p>Automatically refresh when changes detected</p>
                <div class="toggle-group">
                    <button class="toggle-btn <?=$autoRefresh ? 'active' : ''?>" onclick="setAutoRefresh(true)"><i class="fa fa-check"></i> Enable</button>
                    <button class="toggle-btn <?=!$autoRefresh ? 'active off' : ''?>" onclick="setAutoRefresh(false)"><i class="fa fa-times"></i> Disable</button>
                </div>
            </div>
            <div class="control-block">
                <div class="control-icon" style="background:#fff3e0;color:#ff9800;"><i class="fa fa-search"></i></div>
                <h5>Check for Changes</h5>
                <p>Compare current schema with cached version</p>
                <button class="action-btn info" onclick="checkChanges()"><i class="fa fa-search"></i> Check Now</button>
                <span id="change-status" style="display:inline-block;margin-top:8px;"></span>
            </div>
        </div>
        <div class="last-refresh-bar">
            <i class="fa fa-clock-o"></i> Last Refresh: <strong id="last-refresh"><?=htmlspecialchars($cached['discovered_at'] ?? 'Never')?></strong>
        </div>
    </div>
</div>

<!-- Schema Hash & Migrations -->
<div class="row">
    <div class="col-md-6">
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-key"></i> Schema Hash</div>
            <div class="ai-card-body">
                <div class="hash-row"><span class="hash-label">Current Hash</span><code class="hash-value" id="current-hash">Loading...</code></div>
                <div class="hash-row"><span class="hash-label">Cached Hash</span><code class="hash-value" id="cached-hash">Loading...</code></div>
                <div class="hash-row"><span class="hash-label">Status</span><span id="hash-status">Loading...</span></div>
                <button class="action-btn small" onclick="saveCurrentHash()" style="margin-top:12px;"><i class="fa fa-save"></i> Save Current Hash</button>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-database"></i> Recent Migrations</div>
            <div class="ai-card-body migrations-scroll">
                <div id="migrations-list">Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="ai-card">
    <div class="ai-card-header"><i class="fa fa-info-circle"></i> How Auto-Refresh Works</div>
    <div class="ai-card-body">
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <h5>Schema Hashing</h5>
                <p>A hash is calculated from all table/column definitions. When this hash changes, we know the schema has been modified.</p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <h5>Change Detection</h5>
                <p>The system compares the current hash with the last saved hash. Changes are detected after migrations or manual ALTER statements.</p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <h5>Auto Cache Update</h5>
                <p>When auto-refresh is enabled, schema changes automatically trigger a cache refresh so the AI always has current information.</p>
            </div>
        </div>
        <div class="integration-tip">
            <strong><i class="fa fa-terminal"></i> Integration:</strong> Add this to your migration scripts or cron job:
            <pre class="code-block">curl -s "<?=rtrim($_SERVER['HTTP_HOST'] ?? 'localhost', '/')?>/index.php?page=ai_schema_refresh&ajax=1&action=check_changes"</pre>
        </div>
    </div>
</div>

</div><!-- /.ai-schema-refresh-page -->

<style>
/* Page container */
.ai-schema-refresh-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Header */
.ai-schema-refresh-page .refresh-hero {
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 10px 40px rgba(0, 188, 212, 0.3);
}
.ai-schema-refresh-page .hero-content {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}
.ai-schema-refresh-page .hero-icon {
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
.ai-schema-refresh-page .hero-text h2 { margin: 0; font-size: 24px; font-weight: 700; }
.ai-schema-refresh-page .hero-text p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
.ai-schema-refresh-page .hero-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.ai-schema-refresh-page .hero-stat {
    background: rgba(255,255,255,0.15);
    padding: 10px 20px;
    border-radius: 10px;
    text-align: center;
    min-width: 90px;
    border: 1px solid rgba(255,255,255,0.2);
}
.ai-schema-refresh-page .hero-stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
}
.ai-schema-refresh-page .hero-stat-label {
    display: block;
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Cards */
.ai-schema-refresh-page .ai-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.ai-schema-refresh-page .ai-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.ai-schema-refresh-page .ai-card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 15px;
}
.ai-schema-refresh-page .ai-card-header i { color: #00bcd4; margin-right: 8px; }
.ai-schema-refresh-page .ai-card-body { padding: 20px; }

/* Controls grid */
.ai-schema-refresh-page .controls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin-bottom: 20px;
}
.ai-schema-refresh-page .control-block {
    text-align: center;
    padding: 20px 15px;
    border-radius: 10px;
    background: #fafbfc;
    border: 1px solid #f0f0f0;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ai-schema-refresh-page .control-block:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.ai-schema-refresh-page .control-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
}
.ai-schema-refresh-page .control-block h5 {
    margin: 0 0 6px;
    font-weight: 600;
    font-size: 14px;
}
.ai-schema-refresh-page .control-block p {
    color: #888;
    font-size: 12px;
    margin: 0 0 14px;
}

/* Buttons */
.ai-schema-refresh-page .action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 22px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.ai-schema-refresh-page .action-btn.primary {
    background: linear-gradient(135deg, #2196F3, #1976D2);
    color: #fff;
}
.ai-schema-refresh-page .action-btn.primary:hover { box-shadow: 0 4px 12px rgba(33,150,243,0.4); }
.ai-schema-refresh-page .action-btn.info {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: #fff;
}
.ai-schema-refresh-page .action-btn.info:hover { box-shadow: 0 4px 12px rgba(255,152,0,0.4); }
.ai-schema-refresh-page .action-btn.small {
    padding: 6px 14px;
    font-size: 12px;
    background: #f0f0f0;
    color: #555;
}
.ai-schema-refresh-page .action-btn.small:hover { background: #e0e0e0; }

/* Toggle group */
.ai-schema-refresh-page .toggle-group {
    display: inline-flex;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e0e0e0;
}
.ai-schema-refresh-page .toggle-btn {
    padding: 8px 18px;
    border: none;
    background: #f5f5f5;
    color: #666;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.ai-schema-refresh-page .toggle-btn.active {
    background: linear-gradient(135deg, #4CAF50, #388E3C);
    color: #fff;
}
.ai-schema-refresh-page .toggle-btn.active.off {
    background: linear-gradient(135deg, #9e9e9e, #757575);
}
.ai-schema-refresh-page .toggle-btn:hover { opacity: 0.85; }

/* Last refresh bar */
.ai-schema-refresh-page .last-refresh-bar {
    background: #f8f9fa;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    color: #666;
    border-top: 1px solid #f0f0f0;
}

/* Hash rows */
.ai-schema-refresh-page .hash-row {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 12px;
}
.ai-schema-refresh-page .hash-row:last-of-type { border-bottom: none; }
.ai-schema-refresh-page .hash-label {
    font-weight: 600;
    font-size: 13px;
    color: #555;
    min-width: 100px;
    flex-shrink: 0;
}
.ai-schema-refresh-page .hash-value {
    background: #f0f4ff;
    color: #667eea;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    word-break: break-all;
}

/* Migrations */
.ai-schema-refresh-page .migrations-scroll { max-height: 220px; overflow-y: auto; }

/* Steps */
.ai-schema-refresh-page .steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}
.ai-schema-refresh-page .step-card {
    text-align: center;
    padding: 20px 15px;
}
.ai-schema-refresh-page .step-num {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
}
.ai-schema-refresh-page .step-card h5 { font-weight: 600; font-size: 14px; margin: 0 0 8px; }
.ai-schema-refresh-page .step-card p { font-size: 13px; color: #666; margin: 0; line-height: 1.5; }

/* Integration tip */
.ai-schema-refresh-page .integration-tip {
    background: #f8f9fa;
    padding: 15px 18px;
    border-radius: 8px;
    font-size: 13px;
    border-left: 4px solid #00bcd4;
}
.ai-schema-refresh-page .code-block {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 12px 15px;
    border-radius: 6px;
    font-size: 12px;
    margin: 10px 0 0;
    overflow-x: auto;
}

/* Status labels */
.ai-schema-refresh-page .status-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.ai-schema-refresh-page .status-pill.success { background: #d4edda; color: #155724; }
.ai-schema-refresh-page .status-pill.warning { background: #fff3cd; color: #856404; }
.ai-schema-refresh-page .status-pill.default { background: #e2e3e5; color: #383d41; }

/* Responsive */
@media (max-width: 768px) {
    .ai-schema-refresh-page .hero-content { flex-direction: column; text-align: center; }
    .ai-schema-refresh-page .hero-stats { justify-content: center; }
    .ai-schema-refresh-page .controls-grid { grid-template-columns: 1fr; }
}
</style>

<script>
function refreshSchema() {
    var btn = event.target.closest('button'); btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refreshing...';
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=refresh&trigger=manual', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            document.getElementById('table-count').textContent = data.tables;
            document.getElementById('last-refresh').textContent = data.cached_at;
            document.getElementById('cache-status-icon').innerHTML = '<i class="fa fa-check"></i>';
            alert('Schema refreshed! Found ' + data.tables + ' tables.');
            loadStatus();
        } else { alert('Error: ' + (data.error || 'Unknown')); }
    }).finally(function() { btn.disabled = false; btn.innerHTML = '<i class="fa fa-refresh"></i> Refresh Now'; });
}

function setAutoRefresh(enabled) {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=set_auto_refresh', {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'enabled=' + (enabled ? '1' : '0') + '&csrf_token=' + encodeURIComponent('<?= csrf_token() ?>')
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            document.querySelectorAll('.toggle-btn').forEach(function(b) { b.classList.remove('active', 'off'); });
            event.target.classList.add('active');
            if (!enabled) event.target.classList.add('off');
            document.getElementById('auto-status-icon').innerHTML = enabled ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
        }
    });
}

function checkChanges() {
    var btn = event.target.closest('button'); btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=check_changes', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); }).then(function(data) {
        var status = '';
        if (data.changed) {
            status = data.refreshed ? '<span class="status-pill success">Changed & Refreshed</span>' : '<span class="status-pill warning">Changes Detected</span>';
        } else {
            status = '<span class="status-pill default">No Changes</span>';
        }
        document.getElementById('change-status').innerHTML = status;
        loadStatus();
    }).finally(function() { btn.disabled = false; btn.innerHTML = '<i class="fa fa-search"></i> Check Now'; });
}

function loadStatus() {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=status', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            document.getElementById('current-hash').textContent = data.current_hash || 'N/A';
            document.getElementById('cached-hash').textContent = data.last_hash || 'Not saved';
            if (data.schema_changed) document.getElementById('hash-status').innerHTML = '<span class="status-pill warning">Schema Changed</span>';
            else if (data.last_hash) document.getElementById('hash-status').innerHTML = '<span class="status-pill success">In Sync</span>';
            else document.getElementById('hash-status').innerHTML = '<span class="status-pill default">No baseline</span>';
        }
    });
}

function saveCurrentHash() {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=save_hash', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            document.getElementById('cached-hash').textContent = data.hash;
            document.getElementById('hash-status').innerHTML = '<span class="status-pill success">In Sync</span>';
        }
    });
}

function loadMigrations() {
    fetch('index.php?page=ai_schema_refresh&ajax=1&action=get_migrations', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); }).then(function(data) {
        var container = document.getElementById('migrations-list');
        if (data.migrations && data.migrations.length > 0) {
            container.innerHTML = '<table class="table table-condensed table-striped" style="font-size:11px;"><thead><tr><th>Migration</th><th>Status</th><th>Date</th></tr></thead><tbody>' +
                data.migrations.map(function(m) { return '<tr><td>' + m.migration_name + '</td><td><span class="status-pill ' + (m.status==='success'?'success':'warning') + '">' + m.status + '</span></td><td>' + m.executed_at + '</td></tr>'; }).join('') + '</tbody></table>';
        } else { container.innerHTML = '<p style="color:#999;text-align:center;padding:20px;">No migrations found</p>'; }
    });
}

document.addEventListener('DOMContentLoaded', function() { loadStatus(); loadMigrations(); });
</script>
