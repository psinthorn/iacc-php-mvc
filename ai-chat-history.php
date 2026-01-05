<?php
/**
 * AI Chat History Viewer
 * 
 * View and manage AI chat conversation history
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
    
    // Get PDO connection
    $conn = new PDO(
        'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
        'root', 'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    $companyId = $_SESSION['com_id'] ?? 0;
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_sessions':
            $sql = "SELECT 
                        session_id,
                        MIN(created_at) as started_at,
                        MAX(created_at) as last_message,
                        COUNT(*) as message_count,
                        user_id
                    FROM ai_chat_history 
                    WHERE company_id = ?
                    GROUP BY session_id, user_id
                    ORDER BY MAX(created_at) DESC
                    LIMIT 50";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$companyId]);
            echo json_encode(['success' => true, 'sessions' => $stmt->fetchAll()]);
            exit;
            
        case 'get_messages':
            $sessionId = $_GET['session_id'] ?? '';
            $sql = "SELECT * FROM ai_chat_history WHERE session_id = ? AND company_id = ? ORDER BY created_at ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$sessionId, $companyId]);
            echo json_encode(['success' => true, 'messages' => $stmt->fetchAll()]);
            exit;
            
        case 'delete_session':
            $sessionId = $_POST['session_id'] ?? '';
            $sql = "DELETE FROM ai_chat_history WHERE session_id = ? AND company_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$sessionId, $companyId]);
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
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
            <i class="fa fa-comments"></i> AI Chat History
        </h1>
    </div>
</div>

<div class="row">
    <!-- Sessions List -->
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-list"></i> Chat Sessions
                <button class="btn btn-xs btn-primary pull-right" onclick="loadSessions()">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
            <div class="panel-body" style="max-height: 600px; overflow-y: auto;">
                <div id="sessions-list">
                    <p class="text-muted">Loading sessions...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Messages View -->
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-comments-o"></i> Conversation
                <span id="session-info" class="text-muted"></span>
            </div>
            <div class="panel-body" id="messages-container" style="max-height: 600px; overflow-y: auto;">
                <p class="text-muted text-center">Select a session to view messages</p>
            </div>
        </div>
    </div>
</div>

<style>
.session-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
}
.session-item:hover {
    background: #f5f5f5;
}
.session-item.active {
    background: #e3f2fd;
    border-left: 3px solid #2196F3;
}
.message-bubble {
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    max-width: 80%;
}
.message-user {
    background: #e3f2fd;
    margin-left: auto;
    text-align: right;
}
.message-assistant {
    background: #f5f5f5;
}
.message-meta {
    font-size: 11px;
    color: #999;
    margin-top: 5px;
}
.tool-calls {
    background: #fff3e0;
    border-left: 3px solid #ff9800;
    padding: 5px 10px;
    margin-top: 5px;
    font-size: 12px;
}
</style>

<script>
let currentSessionId = null;

function loadSessions() {
    fetch('index.php?page=ai_chat_history&ajax=1&action=get_sessions', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderSessions(data.sessions);
        }
    });
}

function renderSessions(sessions) {
    const container = document.getElementById('sessions-list');
    
    if (sessions.length === 0) {
        container.innerHTML = '<p class="text-muted">No chat sessions found</p>';
        return;
    }
    
    container.innerHTML = sessions.map(s => `
        <div class="session-item ${s.session_id === currentSessionId ? 'active' : ''}" 
             onclick="loadMessages('${s.session_id}')">
            <div><strong>${s.session_id.substring(0, 8)}...</strong></div>
            <div class="text-muted" style="font-size: 12px;">
                ${s.message_count} messages • ${new Date(s.last_message).toLocaleString()}
            </div>
            <button class="btn btn-xs btn-danger" onclick="event.stopPropagation(); deleteSession('${s.session_id}')">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    `).join('');
}

function loadMessages(sessionId) {
    currentSessionId = sessionId;
    document.getElementById('session-info').textContent = ' - Session: ' + sessionId.substring(0, 8) + '...';
    
    // Update active state
    document.querySelectorAll('.session-item').forEach(el => el.classList.remove('active'));
    event.target.closest('.session-item')?.classList.add('active');
    
    fetch(`index.php?page=ai_chat_history&ajax=1&action=get_messages&session_id=${sessionId}`, {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderMessages(data.messages);
        }
    });
}

function renderMessages(messages) {
    const container = document.getElementById('messages-container');
    
    container.innerHTML = messages.map(m => {
        const toolCalls = m.tool_calls ? JSON.parse(m.tool_calls) : null;
        const toolHtml = toolCalls ? `
            <div class="tool-calls">
                <strong>Tools:</strong> ${toolCalls.map(t => t.name || t.function?.name).join(', ')}
            </div>
        ` : '';
        
        return `
            <div class="message-bubble message-${m.role}">
                <div>${escapeHtml(m.content || '(No content)')}</div>
                ${toolHtml}
                <div class="message-meta">
                    ${m.role} • ${new Date(m.created_at).toLocaleString()}
                </div>
            </div>
        `;
    }).join('');
    
    container.scrollTop = container.scrollHeight;
}

function deleteSession(sessionId) {
    if (!confirm('Delete this chat session?')) return;
    
    fetch('index.php?page=ai_chat_history&ajax=1&action=delete_session', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'session_id=' + encodeURIComponent(sessionId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadSessions();
            if (sessionId === currentSessionId) {
                document.getElementById('messages-container').innerHTML = 
                    '<p class="text-muted text-center">Session deleted</p>';
            }
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load on page ready
document.addEventListener('DOMContentLoaded', loadSessions);
</script>
