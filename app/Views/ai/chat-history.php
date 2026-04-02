<div class="ai-chat-history-page">
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">
            <i class="fa fa-comments"></i> AI Chat History
        </h3>
        <?php $currentPage = 'ai_chat_history'; include __DIR__ . '/_nav.php'; ?>
    </div>
</div>

<!-- Hero Header -->
<div class="chat-history-hero">
    <div class="hero-content">
        <div class="hero-icon"><i class="fa fa-comments"></i></div>
        <div class="hero-text">
            <h2>AI Chat History</h2>
            <p>Browse past conversations and review AI responses</p>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat hero-action" onclick="loadSessions()">
            <span class="hero-stat-value"><i class="fa fa-refresh"></i></span>
            <span class="hero-stat-label">Refresh</span>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sessions List -->
    <div class="col-md-4">
        <div class="ai-card">
            <div class="ai-card-header">
                <i class="fa fa-list"></i> Chat Sessions
            </div>
            <div class="ai-card-body sessions-container">
                <div id="sessions-list">
                    <p class="text-muted">Loading sessions...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Messages View -->
    <div class="col-md-8">
        <div class="ai-card">
            <div class="ai-card-header">
                <i class="fa fa-comments-o"></i> Conversation
                <span id="session-info" class="text-muted"></span>
            </div>
            <div class="ai-card-body messages-container" id="messages-container">
                <p class="text-muted text-center">Select a session to view messages</p>
            </div>
        </div>
    </div>
</div>
</div><!-- /.ai-chat-history-page -->

<style>
/* Page container */
.ai-chat-history-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Header */
.ai-chat-history-page .chat-history-hero {
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
.ai-chat-history-page .hero-content { display: flex; align-items: center; gap: 20px; }
.ai-chat-history-page .hero-icon {
    width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;
}
.ai-chat-history-page .hero-text h2 { margin: 0; font-size: 22px; font-weight: 700; }
.ai-chat-history-page .hero-text p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
.ai-chat-history-page .hero-stats { display: flex; gap: 10px; flex-wrap: wrap; }
.ai-chat-history-page .hero-stat {
    background: rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 10px;
    text-align: center; min-width: 80px; border: 1px solid rgba(255,255,255,0.2);
}
.ai-chat-history-page .hero-stat-value { display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; }
.ai-chat-history-page .hero-stat-label { display: block; font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.ai-chat-history-page .hero-action { cursor: pointer; transition: background 0.2s; }
.ai-chat-history-page .hero-action:hover { background: rgba(255,255,255,0.25); }

/* Cards */
.ai-chat-history-page .ai-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.ai-chat-history-page .ai-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.ai-chat-history-page .ai-card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 15px;
}
.ai-chat-history-page .ai-card-header i { color: #667eea; margin-right: 8px; }
.ai-chat-history-page .ai-card-body { padding: 20px; }

/* Sessions list */
.ai-chat-history-page .sessions-container { max-height: 600px; overflow-y: auto; }
.ai-chat-history-page .session-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    border-radius: 8px;
    margin-bottom: 4px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}
.ai-chat-history-page .session-item:hover { background: #f0f4ff; }
.ai-chat-history-page .session-item.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
}
.ai-chat-history-page .session-item.active .text-muted { color: rgba(255,255,255,0.7) !important; }
.ai-chat-history-page .session-item .session-info { flex: 1; }
.ai-chat-history-page .session-item .btn-danger {
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: 6px;
}
.ai-chat-history-page .session-item:hover .btn-danger { opacity: 1; }
.ai-chat-history-page .session-item.active .btn-danger { opacity: 1; background: rgba(255,255,255,0.2); border-color: transparent; color: #fff; }

/* Messages */
.ai-chat-history-page .messages-container { max-height: 600px; overflow-y: auto; }
.ai-chat-history-page .message-bubble {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 12px;
    max-width: 80%;
    animation: chFadeIn 0.3s ease;
    line-height: 1.5;
}
.ai-chat-history-page .message-user {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    margin-left: auto;
    text-align: right;
    border-bottom-right-radius: 4px;
}
.ai-chat-history-page .message-assistant {
    background: #f0f2f5;
    color: #333;
    border-bottom-left-radius: 4px;
}
.ai-chat-history-page .message-meta {
    font-size: 11px;
    color: #999;
    margin-top: 6px;
}
.ai-chat-history-page .message-user .message-meta { color: rgba(255,255,255,0.7); }
.ai-chat-history-page .tool-calls {
    background: #fff3e0;
    border-left: 3px solid #ff9800;
    padding: 8px 12px;
    margin-top: 8px;
    font-size: 12px;
    border-radius: 0 6px 6px 0;
}
.ai-chat-history-page .message-user .tool-calls { border-left-color: rgba(255,255,255,0.5); background: rgba(255,255,255,0.15); }

@keyframes chFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .ai-chat-history-page .chat-history-hero { flex-direction: column; text-align: center; }
    .ai-chat-history-page .hero-content { flex-direction: column; }
    .ai-chat-history-page .hero-stats { justify-content: center; }
}
</style>

<script>
let currentSessionId = null;

function loadSessions() {
    fetch('index.php?page=ai_chat_history&ajax=1&action=get_sessions', { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => { if (data.success) renderSessions(data.sessions); });
}

function renderSessions(sessions) {
    const container = document.getElementById('sessions-list');
    if (sessions.length === 0) { container.innerHTML = '<p class="text-muted">No chat sessions found</p>'; return; }
    container.innerHTML = sessions.map(s => `
        <div class="session-item ${s.session_id === currentSessionId ? 'active' : ''}" onclick="loadMessages('${s.session_id}')">
            <div class="session-info">
                <div><strong>${s.session_id.substring(0, 8)}...</strong></div>
                <div class="text-muted" style="font-size: 12px;">${s.message_count} messages &bull; ${new Date(s.last_message).toLocaleString()}</div>
            </div>
            <button class="btn btn-xs btn-danger" onclick="event.stopPropagation(); deleteSession('${s.session_id}')"><i class="fa fa-trash"></i></button>
        </div>
    `).join('');
}

function loadMessages(sessionId) {
    currentSessionId = sessionId;
    document.getElementById('session-info').textContent = ' - Session: ' + sessionId.substring(0, 8) + '...';
    document.querySelectorAll('.session-item').forEach(el => el.classList.remove('active'));
    event.target.closest('.session-item')?.classList.add('active');

    fetch(`index.php?page=ai_chat_history&ajax=1&action=get_messages&session_id=${sessionId}`, { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => { if (data.success) renderMessages(data.messages); });
}

function renderMessages(messages) {
    const container = document.getElementById('messages-container');
    container.innerHTML = messages.map(m => {
        const toolCalls = m.tool_calls ? JSON.parse(m.tool_calls) : null;
        const toolHtml = toolCalls ? `<div class="tool-calls"><strong>Tools:</strong> ${toolCalls.map(t => t.name || t.function?.name).join(', ')}</div>` : '';
        return `<div class="message-bubble message-${m.role}"><div>${escapeHtml(m.content || '(No content)')}</div>${toolHtml}<div class="message-meta">${m.role} • ${new Date(m.created_at).toLocaleString()}</div></div>`;
    }).join('');
    container.scrollTop = container.scrollHeight;
}

function deleteSession(sessionId) {
    if (!confirm('Delete this chat session?')) return;
    fetch('index.php?page=ai_chat_history&ajax=1&action=delete_session', {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'session_id=' + encodeURIComponent(sessionId) + '&csrf_token=' + encodeURIComponent('<?= csrf_token() ?>')
    }).then(r => r.json()).then(data => {
        if (data.success) {
            loadSessions();
            if (sessionId === currentSessionId) document.getElementById('messages-container').innerHTML = '<p class="text-muted text-center">Session deleted</p>';
        }
    });
}

function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }

document.addEventListener('DOMContentLoaded', loadSessions);
</script>
