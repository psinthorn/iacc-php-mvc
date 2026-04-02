<div class="ai-action-log-page">
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">
            <i class="fa fa-list-alt"></i> AI Action Log
            <small>Tool Execution Audit</small>
        </h3>
        <?php $currentPage = 'ai_action_log'; include __DIR__ . '/_nav.php'; ?>
    </div>
</div>

<!-- Hero Header -->
<div class="action-log-hero">
    <div class="hero-content">
        <div class="hero-icon"><i class="fa fa-list-alt"></i></div>
        <div class="hero-text">
            <h2>AI Action Log</h2>
            <p>Monitor tool executions, track status, and audit AI activity</p>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><span class="hero-stat-value" id="hero-total">-</span><span class="hero-stat-label">Total</span></div>
        <div class="hero-stat"><span class="hero-stat-value" id="hero-executed">-</span><span class="hero-stat-label">Executed</span></div>
        <div class="hero-stat"><span class="hero-stat-value" id="hero-failed">-</span><span class="hero-stat-label">Failed</span></div>
    </div>
</div>

<!-- Stats Row -->
<div class="stat-cards" id="stats-row">
    <div class="stat-card stat-primary"><span class="stat-value" id="stat-total">-</span><span class="stat-label">Total Actions</span></div>
    <div class="stat-card stat-success"><span class="stat-value" id="stat-executed">-</span><span class="stat-label">Executed</span></div>
    <div class="stat-card stat-danger"><span class="stat-value" id="stat-failed">-</span><span class="stat-label">Failed</span></div>
    <div class="stat-card stat-warning"><span class="stat-value" id="stat-pending">-</span><span class="stat-label">Pending</span></div>
    <div class="stat-card stat-info"><span class="stat-value" id="stat-tools">-</span><span class="stat-label">Unique Tools</span></div>
    <div class="stat-card stat-default"><span class="stat-value" id="stat-sessions">-</span><span class="stat-label">Sessions</span></div>
</div>

<!-- Filters -->
<div class="row">
    <div class="col-lg-12">
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-filter"></i> Filters</div>
            <div class="ai-card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select id="filter-status" class="form-control" onchange="loadLogs()">
                            <option value="">All Statuses</option>
                            <option value="executed">Executed</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tool</label>
                        <select id="filter-tool" class="form-control" onchange="loadLogs()">
                            <option value="">All Tools</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button class="action-btn primary" style="width:100%;" onclick="loadLogs()"><i class="fa fa-refresh"></i> Refresh</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="row">
    <div class="col-lg-12">
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-list"></i> Action Log <span id="log-info" class="text-muted"></span></div>
            <div class="ai-card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="logs-table">
                        <thead>
                            <tr><th>ID</th><th>Time</th><th>Tool</th><th>Status</th><th>User</th><th>Session</th><th>Parameters</th><th>Result/Error</th></tr>
                        </thead>
                        <tbody id="logs-body">
                            <tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <nav id="pagination" class="text-center"></nav>
            </div>
        </div>
    </div>
</div>
</div><!-- /.ai-action-log-page -->

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Action Details</h4></div>
            <div class="modal-body" id="modal-content"></div>
        </div>
    </div>
</div>

<style>
/* Page container */
.ai-action-log-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Header */
.ai-action-log-page .action-log-hero {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}
.ai-action-log-page .hero-content { display: flex; align-items: center; gap: 20px; }
.ai-action-log-page .hero-icon {
    width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;
}
.ai-action-log-page .hero-text h2 { margin: 0; font-size: 22px; font-weight: 700; }
.ai-action-log-page .hero-text p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
.ai-action-log-page .hero-stats { display: flex; gap: 10px; flex-wrap: wrap; }
.ai-action-log-page .hero-stat {
    background: rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 10px;
    text-align: center; min-width: 80px; border: 1px solid rgba(255,255,255,0.2);
}
.ai-action-log-page .hero-stat-value { display: block; font-size: 20px; font-weight: 700; }
.ai-action-log-page .hero-stat-label { display: block; font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

/* Stat cards (CSS Grid) */
.ai-action-log-page .stat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

/* Stat cards */
.ai-action-log-page .stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 15px;
    text-align: center;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    border-left: 4px solid #ccc;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ai-action-log-page .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.ai-action-log-page .stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
}
.ai-action-log-page .stat-label {
    display: block;
    font-size: 12px;
    color: #888;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ai-action-log-page .stat-primary { border-left-color: #667eea; }
.ai-action-log-page .stat-primary .stat-value { color: #667eea; }
.ai-action-log-page .stat-success { border-left-color: #27ae60; }
.ai-action-log-page .stat-success .stat-value { color: #27ae60; }
.ai-action-log-page .stat-danger { border-left-color: #e74c3c; }
.ai-action-log-page .stat-danger .stat-value { color: #e74c3c; }
.ai-action-log-page .stat-warning { border-left-color: #f39c12; }
.ai-action-log-page .stat-warning .stat-value { color: #f39c12; }
.ai-action-log-page .stat-info { border-left-color: #3498db; }
.ai-action-log-page .stat-info .stat-value { color: #3498db; }
.ai-action-log-page .stat-default { border-left-color: #95a5a6; }
.ai-action-log-page .stat-default .stat-value { color: #95a5a6; }

/* Cards */
.ai-action-log-page .ai-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.ai-action-log-page .ai-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.ai-action-log-page .ai-card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 15px;
}
.ai-action-log-page .ai-card-header i { color: #667eea; margin-right: 8px; }
.ai-action-log-page .ai-card-body { padding: 20px; }

/* Status badges */
.ai-action-log-page .status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.ai-action-log-page .status-executed { background: #d4edda; color: #155724; }
.ai-action-log-page .status-failed { background: #f8d7da; color: #721c24; }
.ai-action-log-page .status-pending { background: #fff3cd; color: #856404; }
.ai-action-log-page .status-confirmed { background: #cce5ff; color: #004085; }
.ai-action-log-page .status-cancelled { background: #e2e3e5; color: #383d41; }

/* JSON preview */
.ai-action-log-page .json-preview {
    max-width: 200px;
    max-height: 50px;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 11px;
    color: #666;
    cursor: pointer;
    padding: 4px 8px;
    background: #f8f9fa;
    border-radius: 4px;
    transition: background 0.2s;
}
.ai-action-log-page .json-preview:hover { background: #e9ecef; }

/* Table */
.ai-action-log-page .table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
}
.ai-action-log-page .table tbody tr {
    animation: alFadeIn 0.3s ease;
}

/* Modal */
#detailModal .modal-content { border-radius: 12px; overflow: hidden; }
#detailModal .modal-header { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; }
#detailModal .modal-header .close { color: #fff; opacity: 0.8; }
#detailModal .modal-body pre {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 8px;
    font-size: 13px;
}

/* Pagination */
.ai-action-log-page .pagination > li > a { border-radius: 8px; margin: 0 2px; }
.ai-action-log-page .pagination > .active > a { background: #667eea; border-color: #667eea; }

@keyframes alFadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Buttons */
.ai-action-log-page .action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 22px; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; text-decoration: none;
}
.ai-action-log-page .action-btn.primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}
.ai-action-log-page .action-btn.primary:hover { box-shadow: 0 4px 15px rgba(102,126,234,0.4); transform: translateY(-1px); }

/* Responsive */
@media (max-width: 768px) {
    .ai-action-log-page .action-log-hero { flex-direction: column; text-align: center; }
    .ai-action-log-page .hero-content { flex-direction: column; }
    .ai-action-log-page .hero-stats { justify-content: center; }
    .ai-action-log-page .stat-cards { grid-template-columns: repeat(2, 1fr); }
}
</style>

<script>
let currentPage = 1;

function loadStats() {
    fetch('index.php?page=ai_action_log&ajax=1&action=get_stats', { credentials: 'same-origin' })
    .then(r => r.json()).then(data => {
        if (data.success) {
            const s = data.stats;
            document.getElementById('stat-total').textContent = (s.total||0).toLocaleString();
            document.getElementById('stat-executed').textContent = (s.executed||0).toLocaleString();
            document.getElementById('stat-failed').textContent = (s.failed||0).toLocaleString();
            document.getElementById('hero-total').textContent = (s.total||0).toLocaleString();
            document.getElementById('hero-executed').textContent = (s.executed||0).toLocaleString();
            document.getElementById('hero-failed').textContent = (s.failed||0).toLocaleString();
            document.getElementById('stat-pending').textContent = (s.pending||0).toLocaleString();
            document.getElementById('stat-tools').textContent = (s.unique_tools||0).toLocaleString();
            document.getElementById('stat-sessions').textContent = (s.sessions||0).toLocaleString();
        }
    });
}

function loadTools() {
    fetch('index.php?page=ai_action_log&ajax=1&action=get_tools', { credentials: 'same-origin' })
    .then(r => r.json()).then(data => {
        if (data.success) {
            const select = document.getElementById('filter-tool');
            data.tools.forEach(tool => { const opt = document.createElement('option'); opt.value = tool; opt.textContent = tool; select.appendChild(opt); });
        }
    });
}

function loadLogs(page = 1) {
    currentPage = page;
    const status = document.getElementById('filter-status').value;
    const tool = document.getElementById('filter-tool').value;
    let url = `index.php?page=ai_action_log&ajax=1&action=get_logs&p=${page}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;
    if (tool) url += `&tool=${encodeURIComponent(tool)}`;
    fetch(url, { credentials: 'same-origin' }).then(r => r.json()).then(data => {
        if (data.success) { renderLogs(data.logs); renderPagination(data.page, data.pages, data.total); }
    });
}

function renderLogs(logs) {
    const tbody = document.getElementById('logs-body');
    if (logs.length === 0) { tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No logs found</td></tr>'; return; }
    tbody.innerHTML = logs.map(log => `<tr>
        <td>${log.id}</td>
        <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
        <td><code>${log.action_type}</code></td>
        <td><span class="status-badge status-${log.status}">${log.status}</span></td>
        <td>${log.user_id||'-'}</td>
        <td><small>${(log.session_id||'').substring(0,8)}...</small></td>
        <td><div class="json-preview" onclick="showDetail(${log.id},'params',${escapeJson(log.action_params)})">${truncate(log.action_params||'{}',50)}</div></td>
        <td>${log.error?`<span class="text-danger" title="${escapeHtml(log.error)}">Error</span>`:log.result?`<div class="json-preview" onclick="showDetail(${log.id},'result',${escapeJson(log.result)})">${truncate(log.result,50)}</div>`:'-'}</td>
    </tr>`).join('');
}

function renderPagination(current, total, count) {
    document.getElementById('log-info').textContent = ` (${count} total, page ${current} of ${total})`;
    if (total <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let html = '<ul class="pagination">';
    if (current > 1) html += `<li><a href="#" onclick="loadLogs(${current-1});return false;">&laquo;</a></li>`;
    for (let i = Math.max(1,current-2); i <= Math.min(total,current+2); i++) html += `<li class="${i===current?'active':''}"><a href="#" onclick="loadLogs(${i});return false;">${i}</a></li>`;
    if (current < total) html += `<li><a href="#" onclick="loadLogs(${current+1});return false;">&raquo;</a></li>`;
    html += '</ul>';
    document.getElementById('pagination').innerHTML = html;
}

function showDetail(id, type, data) {
    let content;
    try { const parsed = typeof data === 'string' ? JSON.parse(data) : data; content = `<pre style="max-height:400px;overflow:auto;">${JSON.stringify(parsed,null,2)}</pre>`; }
    catch(e) { content = `<pre>${escapeHtml(String(data))}</pre>`; }
    document.getElementById('modal-content').innerHTML = `<h5>${type==='params'?'Parameters':'Result'} for Action #${id}</h5>${content}`;
    $('#detailModal').modal('show');
}

function truncate(str, len) { if (!str) return ''; str = String(str); return str.length > len ? str.substring(0, len) + '...' : str; }
function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
function escapeJson(str) { if (!str) return '"{}"'; return JSON.stringify(str); }

document.addEventListener('DOMContentLoaded', function() { loadStats(); loadTools(); loadLogs(); });
</script>
