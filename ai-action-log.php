<?php
/**
 * AI Action Log Viewer
 * 
 * View and audit AI tool executions
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-05
 */

// Handle AJAX requests (called directly)
if (isset($_GET['ajax'])) {
    session_start();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    $conn = new PDO(
        'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
        'root', 'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    $companyId = $_SESSION['com_id'] ?? 0;
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_logs':
            $page = max(1, intval($_GET['p'] ?? 1));
            $limit = 50;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? '';
            $toolFilter = $_GET['tool'] ?? '';
            
            $where = "WHERE company_id = ?";
            $bindings = [$companyId];
            
            if ($status) {
                $where .= " AND status = ?";
                $bindings[] = $status;
            }
            if ($toolFilter) {
                $where .= " AND action_type LIKE ?";
                $bindings[] = "%$toolFilter%";
            }
            
            $countSql = "SELECT COUNT(*) as cnt FROM ai_action_log $where";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($bindings);
            $total = $countStmt->fetch()['cnt'];
            
            $sql = "SELECT * FROM ai_action_log $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $conn->prepare($sql);
            $stmt->execute($bindings);
            
            echo json_encode([
                'success' => true, 
                'logs' => $stmt->fetchAll(),
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            exit;
            
        case 'get_stats':
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(status = 'executed') as executed,
                        SUM(status = 'failed') as failed,
                        SUM(status = 'pending') as pending,
                        COUNT(DISTINCT action_type) as unique_tools,
                        COUNT(DISTINCT session_id) as sessions
                    FROM ai_action_log WHERE company_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$companyId]);
            $stats = $stmt->fetch();
            
            $topSql = "SELECT action_type, COUNT(*) as cnt 
                       FROM ai_action_log WHERE company_id = ?
                       GROUP BY action_type ORDER BY cnt DESC LIMIT 5";
            $topStmt = $conn->prepare($topSql);
            $topStmt->execute([$companyId]);
            
            echo json_encode(['success' => true, 'stats' => $stats, 'top_tools' => $topStmt->fetchAll()]);
            exit;
            
        case 'get_tools':
            $sql = "SELECT DISTINCT action_type FROM ai_action_log WHERE company_id = ? ORDER BY action_type";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$companyId]);
            echo json_encode(['success' => true, 'tools' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Page context (included from index.php)
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] < 2) {
    echo '<div class="alert alert-danger">Access denied. Super Admin required.</div>';
    return;
}
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="fa fa-list-alt"></i> AI Action Log
            <small>Tool Execution Audit</small>
        </h1>
    </div>
</div>

<!-- Stats Row -->
<div class="row" id="stats-row">
    <div class="col-md-2">
        <div class="panel panel-primary">
            <div class="panel-heading text-center">Total Actions</div>
            <div class="panel-body text-center">
                <h2 id="stat-total">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-success">
            <div class="panel-heading text-center">Executed</div>
            <div class="panel-body text-center">
                <h2 id="stat-executed">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-danger">
            <div class="panel-heading text-center">Failed</div>
            <div class="panel-body text-center">
                <h2 id="stat-failed">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-warning">
            <div class="panel-heading text-center">Pending</div>
            <div class="panel-body text-center">
                <h2 id="stat-pending">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-info">
            <div class="panel-heading text-center">Unique Tools</div>
            <div class="panel-body text-center">
                <h2 id="stat-tools">-</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-default">
            <div class="panel-heading text-center">Sessions</div>
            <div class="panel-body text-center">
                <h2 id="stat-sessions">-</h2>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-filter"></i> Filters
            </div>
            <div class="panel-body">
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
                        <button class="btn btn-primary btn-block" onclick="loadLogs()">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-list"></i> Action Log
                <span id="log-info" class="text-muted"></span>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="logs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Time</th>
                                <th>Tool</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Session</th>
                                <th>Parameters</th>
                                <th>Result/Error</th>
                            </tr>
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

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Action Details</h4>
            </div>
            <div class="modal-body" id="modal-content">
            </div>
        </div>
    </div>
</div>

<style>
.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}
.status-executed { background: #4CAF50; color: white; }
.status-failed { background: #f44336; color: white; }
.status-pending { background: #ff9800; color: white; }
.status-confirmed { background: #2196F3; color: white; }
.status-cancelled { background: #9e9e9e; color: white; }
.json-preview {
    max-width: 200px;
    max-height: 50px;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 11px;
    color: #666;
    cursor: pointer;
}
.json-preview:hover {
    background: #f5f5f5;
}
#stats-row h2 {
    margin: 0;
}
</style>

<script>
let currentPage = 1;

function loadStats() {
    fetch('index.php?page=ai_action_log&ajax=1&action=get_stats', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const s = data.stats;
            document.getElementById('stat-total').textContent = (s.total || 0).toLocaleString();
            document.getElementById('stat-executed').textContent = (s.executed || 0).toLocaleString();
            document.getElementById('stat-failed').textContent = (s.failed || 0).toLocaleString();
            document.getElementById('stat-pending').textContent = (s.pending || 0).toLocaleString();
            document.getElementById('stat-tools').textContent = (s.unique_tools || 0).toLocaleString();
            document.getElementById('stat-sessions').textContent = (s.sessions || 0).toLocaleString();
        }
    });
}

function loadTools() {
    fetch('index.php?page=ai_action_log&ajax=1&action=get_tools', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('filter-tool');
            data.tools.forEach(tool => {
                const opt = document.createElement('option');
                opt.value = tool;
                opt.textContent = tool;
                select.appendChild(opt);
            });
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
    
    fetch(url, { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderLogs(data.logs);
            renderPagination(data.page, data.pages, data.total);
        }
    });
}

function renderLogs(logs) {
    const tbody = document.getElementById('logs-body');
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No logs found</td></tr>';
        return;
    }
    
    tbody.innerHTML = logs.map(log => `
        <tr>
            <td>${log.id}</td>
            <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
            <td><code>${log.action_type}</code></td>
            <td><span class="status-badge status-${log.status}">${log.status}</span></td>
            <td>${log.user_id || '-'}</td>
            <td><small>${(log.session_id || '').substring(0, 8)}...</small></td>
            <td>
                <div class="json-preview" onclick="showDetail(${log.id}, 'params', ${escapeJson(log.action_params)})">
                    ${truncate(log.action_params || '{}', 50)}
                </div>
            </td>
            <td>
                ${log.error ? `<span class="text-danger" title="${escapeHtml(log.error)}">Error</span>` : 
                  log.result ? `<div class="json-preview" onclick="showDetail(${log.id}, 'result', ${escapeJson(log.result)})">${truncate(log.result, 50)}</div>` : '-'}
            </td>
        </tr>
    `).join('');
}

function renderPagination(current, total, count) {
    document.getElementById('log-info').textContent = ` (${count} total, page ${current} of ${total})`;
    
    if (total <= 1) {
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    let html = '<ul class="pagination">';
    
    if (current > 1) {
        html += `<li><a href="#" onclick="loadLogs(${current-1}); return false;">&laquo;</a></li>`;
    }
    
    for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
        html += `<li class="${i === current ? 'active' : ''}">
            <a href="#" onclick="loadLogs(${i}); return false;">${i}</a>
        </li>`;
    }
    
    if (current < total) {
        html += `<li><a href="#" onclick="loadLogs(${current+1}); return false;">&raquo;</a></li>`;
    }
    
    html += '</ul>';
    document.getElementById('pagination').innerHTML = html;
}

function showDetail(id, type, data) {
    let content;
    try {
        const parsed = typeof data === 'string' ? JSON.parse(data) : data;
        content = `<pre style="max-height: 400px; overflow: auto;">${JSON.stringify(parsed, null, 2)}</pre>`;
    } catch (e) {
        content = `<pre>${escapeHtml(String(data))}</pre>`;
    }
    
    document.getElementById('modal-content').innerHTML = `
        <h5>${type === 'params' ? 'Parameters' : 'Result'} for Action #${id}</h5>
        ${content}
    `;
    
    $('#detailModal').modal('show');
}

function truncate(str, len) {
    if (!str) return '';
    str = String(str);
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJson(str) {
    if (!str) return '"{}"';
    return JSON.stringify(str);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadTools();
    loadLogs();
});
</script>
