/**
 * AI Chat Widget
 * 
 * Frontend chat interface for iACC AI Assistant
 * 
 * @version 1.0
 * @date 2026-01-04
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        apiEndpoint: '/ai/chat-handler.php',
        streamEndpoint: '/ai/chat-stream.php',
        useStreaming: true,  // Enable SSE streaming for real-time responses
        position: 'bottom-right',
        theme: 'light',
        quickActions: [
            { label: '📊 สรุปวันนี้', prompt: 'สรุปข้อมูลวันนี้' },
            { label: '💰 ใบแจ้งหนี้ค้างชำระ', prompt: 'แสดงใบแจ้งหนี้ที่ยังไม่ได้ชำระ' },
            { label: '⏰ เกินกำหนด', prompt: 'แสดงใบแจ้งหนี้ที่เกินกำหนดชำระ' },
            { label: '📈 รายงานยอดขาย', prompt: 'แสดงรายงานยอดขายเดือนนี้' },
            { label: '📉 แนวโน้มรายได้', prompt: 'แสดงแนวโน้มรายได้ 6 เดือนย้อนหลัง' },
            { label: '👥 วิเคราะห์ลูกค้า', prompt: 'วิเคราะห์ลูกค้า Top 10' },
            { label: '📋 รายงาน Aging', prompt: 'แสดง Aging Report ลูกหนี้' },
        ]
    };

    // State
    let state = {
        isOpen: false,
        isLoading: false,
        sessionId: null,
        messages: [],
        pendingConfirmation: null,
        // Streaming state
        currentEventSource: null,
        streamingMessageEl: null,
        streamingContent: '',
    };

    // DOM Elements
    let elements = {};

    /**
     * Initialize the chat widget
     */
    function init() {
        createWidget();
        bindEvents();
        checkHealth();
    }

    /**
     * Create the widget DOM structure
     */
    function createWidget() {
        // Create container
        const container = document.createElement('div');
        container.id = 'ai-chat-widget';
        container.className = 'ai-chat-widget';
        container.innerHTML = `
            <!-- Toggle Button -->
            <button class="ai-chat-toggle" id="ai-chat-toggle" title="AI Assistant">
                <svg class="ai-chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
                <span class="ai-chat-badge" id="ai-chat-badge" style="display:none">!</span>
            </button>

            <!-- Chat Window -->
            <div class="ai-chat-window" id="ai-chat-window">
                <!-- Header -->
                <div class="ai-chat-header">
                    <div class="ai-chat-header-info">
                        <div class="ai-chat-avatar">🤖</div>
                        <div class="ai-chat-header-text">
                            <div class="ai-chat-title">iACC Assistant</div>
                            <div class="ai-chat-status" id="ai-chat-status">
                                <span class="status-dot"></span>
                                <span class="status-text">กำลังเชื่อมต่อ...</span>
                            </div>
                        </div>
                    </div>
                    <div class="ai-chat-header-actions">
                        <a href="index.php?page=ai_settings" class="ai-chat-btn-icon" title="AI Settings" target="_blank">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                        </a>
                        <button class="ai-chat-btn-icon" id="ai-chat-clear" title="เริ่มใหม่">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                        <button class="ai-chat-btn-icon" id="ai-chat-close" title="ปิด">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="ai-chat-messages" id="ai-chat-messages">
                    <!-- Welcome message -->
                    <div class="ai-chat-message assistant">
                        <div class="ai-chat-message-avatar">🤖</div>
                        <div class="ai-chat-message-content">
                            <div class="ai-chat-message-text">
                                สวัสดีครับ! ผมเป็นผู้ช่วย AI ของระบบ iACC 
                                <br><br>
                                คุณสามารถถามเกี่ยวกับใบแจ้งหนี้ ใบสั่งซื้อ การชำระเงิน หรือข้อมูลลูกค้าได้เลยครับ
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="ai-chat-quick-actions" id="ai-chat-quick-actions">
                        ${CONFIG.quickActions.map(action => `
                            <button class="ai-quick-action-btn" data-prompt="${action.prompt}">
                                ${action.label}
                            </button>
                        `).join('')}
                    </div>
                </div>

                <!-- Input -->
                <div class="ai-chat-input-container">
                    <div class="ai-chat-input-wrapper">
                        <textarea 
                            class="ai-chat-input" 
                            id="ai-chat-input" 
                            placeholder="พิมพ์ข้อความ..."
                            rows="1"
                        ></textarea>
                        <button class="ai-chat-send" id="ai-chat-send" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>
                    <div class="ai-chat-powered" id="ai-chat-powered">
                        Powered by AI ✨
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(container);

        // Store element references
        elements = {
            container: container,
            toggle: document.getElementById('ai-chat-toggle'),
            window: document.getElementById('ai-chat-window'),
            messages: document.getElementById('ai-chat-messages'),
            input: document.getElementById('ai-chat-input'),
            send: document.getElementById('ai-chat-send'),
            close: document.getElementById('ai-chat-close'),
            clear: document.getElementById('ai-chat-clear'),
            status: document.getElementById('ai-chat-status'),
            badge: document.getElementById('ai-chat-badge'),
            quickActions: document.getElementById('ai-chat-quick-actions'),
            powered: document.getElementById('ai-chat-powered'),
        };
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Toggle chat
        elements.toggle.addEventListener('click', toggleChat);
        elements.close.addEventListener('click', closeChat);

        // Send message
        elements.send.addEventListener('click', sendMessage);
        elements.input.addEventListener('keydown', handleInputKeydown);
        elements.input.addEventListener('input', handleInputChange);

        // Clear chat
        elements.clear.addEventListener('click', clearChat);

        // Quick actions
        document.querySelectorAll('.ai-quick-action-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const prompt = btn.dataset.prompt;
                elements.input.value = prompt;
                sendMessage();
            });
        });

        // Auto-resize textarea
        elements.input.addEventListener('input', autoResize);
    }

    /**
     * Toggle chat window
     */
    function toggleChat() {
        state.isOpen = !state.isOpen;
        elements.window.classList.toggle('open', state.isOpen);
        elements.toggle.classList.toggle('active', state.isOpen);
        
        if (state.isOpen) {
            elements.input.focus();
            elements.badge.style.display = 'none';
        }
    }

    /**
     * Close chat window
     */
    function closeChat() {
        state.isOpen = false;
        elements.window.classList.remove('open');
        elements.toggle.classList.remove('active');
    }

    /**
     * Clear chat history
     */
    function clearChat() {
        state.messages = [];
        state.sessionId = null;
        state.pendingConfirmation = null;
        
        // Keep only welcome message
        elements.messages.innerHTML = `
            <div class="ai-chat-message assistant">
                <div class="ai-chat-message-avatar">🤖</div>
                <div class="ai-chat-message-content">
                    <div class="ai-chat-message-text">
                        เริ่มการสนทนาใหม่แล้วครับ มีอะไรให้ช่วยไหมครับ?
                    </div>
                </div>
            </div>
            <div class="ai-chat-quick-actions">
                ${CONFIG.quickActions.map(action => `
                    <button class="ai-quick-action-btn" data-prompt="${action.prompt}">
                        ${action.label}
                    </button>
                `).join('')}
            </div>
        `;

        // Re-bind quick actions
        document.querySelectorAll('.ai-quick-action-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const prompt = btn.dataset.prompt;
                elements.input.value = prompt;
                sendMessage();
            });
        });
    }

    /**
     * Handle input keydown
     */
    function handleInputKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    /**
     * Handle input change
     */
    function handleInputChange() {
        elements.send.disabled = elements.input.value.trim() === '' || state.isLoading;
    }

    /**
     * Auto-resize textarea
     */
    function autoResize() {
        elements.input.style.height = 'auto';
        elements.input.style.height = Math.min(elements.input.scrollHeight, 120) + 'px';
    }

    /**
     * Send message to API
     */
    async function sendMessage() {
        const message = elements.input.value.trim();
        if (!message || state.isLoading) return;

        // Add user message to UI
        addMessage('user', message);
        elements.input.value = '';
        elements.input.style.height = 'auto';
        elements.send.disabled = true;

        // Hide quick actions after first message
        const quickActions = elements.messages.querySelector('.ai-chat-quick-actions');
        if (quickActions) {
            quickActions.style.display = 'none';
        }

        // Show loading
        setLoading(true);

        // Use streaming or regular mode
        if (CONFIG.useStreaming) {
            sendMessageStreaming(message);
        } else {
            sendMessageRegular(message);
        }
    }

    /**
     * Send message using regular POST request
     */
    async function sendMessageRegular(message) {
        try {
            const response = await fetch(CONFIG.apiEndpoint + '?action=chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',  // Include session cookies for cross-origin
                body: JSON.stringify({
                    message: message,
                    session_id: state.sessionId,
                }),
            });

            if (!response.ok) {
                console.error('HTTP Error:', response.status, response.statusText);
            }

            const data = await response.json();
            console.log('Chat response:', data);  // Debug log

            if (data.success) {
                state.sessionId = data.data.session_id;
                
                // Handle message - use fallback if empty
                const message = data.data.message || (data.data.tool_results?.length > 0 
                    ? 'กำลังประมวลผล...' 
                    : 'ไม่สามารถตอบกลับได้');
                addMessage('assistant', message);

                // Handle confirmation
                if (data.data.requires_confirmation) {
                    state.pendingConfirmation = data.data.confirmation_id;
                    addConfirmationButtons(data.data.confirmation_id);
                }
            } else {
                addMessage('assistant', '❌ เกิดข้อผิดพลาด: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Chat error:', error);
            addMessage('assistant', '❌ ไม่สามารถเชื่อมต่อได้ กรุณาลองใหม่');
        } finally {
            setLoading(false);
        }
    }

    /**
     * Send message using SSE streaming
     */
    function sendMessageStreaming(message) {
        // Close any existing EventSource
        if (state.currentEventSource) {
            state.currentEventSource.close();
        }

        // Reset streaming state
        state.streamingContent = '';
        state.streamingMessageEl = null;

        // Build SSE URL with query parameters
        const params = new URLSearchParams({
            message: message,
            session_id: state.sessionId || '',
        });
        const url = CONFIG.streamEndpoint + '?' + params.toString();

        try {
            const eventSource = new EventSource(url, { withCredentials: true });
            state.currentEventSource = eventSource;

            // Handle different event types
            eventSource.addEventListener('thinking', (e) => {
                console.log('🤔 Thinking:', e.data);
                updateStreamingStatus('กำลังคิด...');
            });

            eventSource.addEventListener('language', (e) => {
                console.log('🌐 Language:', e.data);
            });

            eventSource.addEventListener('status', (e) => {
                console.log('📊 Status:', e.data);
                updateStreamingStatus(e.data);
            });

            eventSource.addEventListener('tools', (e) => {
                console.log('🔧 Tools available:', e.data);
            });

            eventSource.addEventListener('tool_call', (e) => {
                try {
                    const data = JSON.parse(e.data);
                    console.log('🔨 Tool call:', data);
                    updateStreamingStatus(`กำลังใช้เครื่องมือ: ${data.tool}`);
                } catch (err) {
                    console.log('🔨 Tool call:', e.data);
                }
            });

            eventSource.addEventListener('tool_result', (e) => {
                console.log('✅ Tool result:', e.data);
            });

            eventSource.addEventListener('chunk', (e) => {
                // Append chunk to streaming content
                state.streamingContent += e.data;
                updateStreamingMessage(state.streamingContent);
            });

            eventSource.addEventListener('done', (e) => {
                try {
                    const data = JSON.parse(e.data);
                    console.log('✅ Done:', data);
                    
                    // Update session ID
                    if (data.session_id) {
                        state.sessionId = data.session_id;
                    }
                    
                    // Finalize the message
                    finalizeStreamingMessage(state.streamingContent);
                    
                    // Handle confirmation if needed
                    if (data.requires_confirmation) {
                        state.pendingConfirmation = data.confirmation_id;
                        addConfirmationButtons(data.confirmation_id);
                    }
                } catch (err) {
                    console.log('Done event:', e.data);
                    finalizeStreamingMessage(state.streamingContent);
                }
                
                eventSource.close();
                state.currentEventSource = null;
                setLoading(false);
            });

            eventSource.addEventListener('error', (e) => {
                if (e.data) {
                    console.error('❌ Stream error:', e.data);
                    addMessage('assistant', '❌ เกิดข้อผิดพลาด: ' + e.data);
                }
                eventSource.close();
                state.currentEventSource = null;
                setLoading(false);
            });

            eventSource.onerror = (e) => {
                console.error('EventSource error:', e);
                if (eventSource.readyState === EventSource.CLOSED) {
                    console.log('EventSource closed');
                } else {
                    // Connection error - fallback to regular mode
                    console.warn('Streaming failed, falling back to regular mode');
                    eventSource.close();
                    state.currentEventSource = null;
                    sendMessageRegular(message);
                }
            };

        } catch (error) {
            console.error('Failed to create EventSource:', error);
            // Fallback to regular mode
            sendMessageRegular(message);
        }
    }

    /**
     * Update streaming status indicator
     */
    function updateStreamingStatus(status) {
        const loadingEl = elements.messages.querySelector('.ai-chat-loading');
        if (loadingEl) {
            const contentEl = loadingEl.querySelector('.ai-chat-message-content');
            if (contentEl) {
                contentEl.innerHTML = `
                    <div class="ai-chat-typing">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="ai-chat-streaming-status">${status}</div>
                `;
            }
        }
    }

    /**
     * Update streaming message in real-time
     */
    function updateStreamingMessage(content) {
        // Remove loading indicator and create/update streaming message
        const loadingEl = elements.messages.querySelector('.ai-chat-loading');
        
        if (!state.streamingMessageEl) {
            // Create streaming message element
            if (loadingEl) {
                loadingEl.remove();
            }
            
            const messageEl = document.createElement('div');
            messageEl.className = 'ai-chat-message assistant ai-chat-streaming';
            messageEl.innerHTML = `
                <div class="ai-chat-message-avatar">🤖</div>
                <div class="ai-chat-message-content">
                    <div class="ai-chat-message-text">${formatMessage(content)}</div>
                    <div class="ai-chat-streaming-cursor"></div>
                </div>
            `;
            elements.messages.appendChild(messageEl);
            state.streamingMessageEl = messageEl;
        } else {
            // Update existing streaming message
            const textEl = state.streamingMessageEl.querySelector('.ai-chat-message-text');
            if (textEl) {
                textEl.innerHTML = formatMessage(content);
            }
        }
        
        scrollToBottom();
    }

    /**
     * Finalize streaming message
     */
    function finalizeStreamingMessage(content) {
        if (state.streamingMessageEl) {
            // Remove streaming class and cursor
            state.streamingMessageEl.classList.remove('ai-chat-streaming');
            const cursor = state.streamingMessageEl.querySelector('.ai-chat-streaming-cursor');
            if (cursor) cursor.remove();
            
            // Add timestamp
            const contentEl = state.streamingMessageEl.querySelector('.ai-chat-message-content');
            if (contentEl) {
                const timeEl = document.createElement('div');
                timeEl.className = 'ai-chat-message-time';
                timeEl.textContent = formatTime(new Date());
                contentEl.appendChild(timeEl);
            }
            
            // Save to state
            state.messages.push({ role: 'assistant', content, time: new Date() });
            
            // Reset streaming state
            state.streamingMessageEl = null;
            state.streamingContent = '';
        } else if (content) {
            // No streaming element, add regular message
            addMessage('assistant', content);
        }
        
        // Remove any remaining loading indicator
        const loadingEl = elements.messages.querySelector('.ai-chat-loading');
        if (loadingEl) {
            loadingEl.remove();
        }
    }

    /**
     * Add message to UI
     */
    function addMessage(role, content) {
        const messageEl = document.createElement('div');
        messageEl.className = `ai-chat-message ${role}`;
        
        const avatar = role === 'user' ? '👤' : '🤖';
        
        messageEl.innerHTML = `
            <div class="ai-chat-message-avatar">${avatar}</div>
            <div class="ai-chat-message-content">
                <div class="ai-chat-message-text">${formatMessage(content)}</div>
                <div class="ai-chat-message-time">${formatTime(new Date())}</div>
            </div>
        `;

        // Remove loading indicator if exists
        const loadingEl = elements.messages.querySelector('.ai-chat-loading');
        if (loadingEl) {
            loadingEl.remove();
        }

        elements.messages.appendChild(messageEl);
        scrollToBottom();

        // Save to state
        state.messages.push({ role, content, time: new Date() });
    }

    /**
     * Add confirmation buttons
     */
    function addConfirmationButtons(confirmationId) {
        const buttonsEl = document.createElement('div');
        buttonsEl.className = 'ai-chat-confirmation';
        buttonsEl.innerHTML = `
            <button class="ai-confirm-btn confirm" data-id="${confirmationId}">
                ✓ ยืนยัน
            </button>
            <button class="ai-confirm-btn cancel" data-id="${confirmationId}">
                ✕ ยกเลิก
            </button>
        `;

        elements.messages.appendChild(buttonsEl);
        scrollToBottom();

        // Bind events
        buttonsEl.querySelector('.confirm').addEventListener('click', () => confirmAction(confirmationId));
        buttonsEl.querySelector('.cancel').addEventListener('click', () => cancelAction(confirmationId));
    }

    /**
     * Confirm action
     */
    async function confirmAction(confirmationId) {
        setLoading(true);
        
        try {
            const response = await fetch(CONFIG.apiEndpoint + '?action=confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    confirmation_id: confirmationId,
                    session_id: state.sessionId,
                }),
            });

            const data = await response.json();

            // Remove confirmation buttons
            const confirmEl = elements.messages.querySelector('.ai-chat-confirmation');
            if (confirmEl) confirmEl.remove();

            if (data.success) {
                addMessage('assistant', data.data.message);
            } else {
                addMessage('assistant', '❌ ' + (data.error || 'Action failed'));
            }
        } catch (error) {
            addMessage('assistant', '❌ เกิดข้อผิดพลาด');
        } finally {
            setLoading(false);
            state.pendingConfirmation = null;
        }
    }

    /**
     * Cancel action
     */
    async function cancelAction(confirmationId) {
        setLoading(true);

        try {
            await fetch(CONFIG.apiEndpoint + '?action=cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    confirmation_id: confirmationId,
                    session_id: state.sessionId,
                }),
            });

            // Remove confirmation buttons
            const confirmEl = elements.messages.querySelector('.ai-chat-confirmation');
            if (confirmEl) confirmEl.remove();

            addMessage('assistant', '❌ ยกเลิกการดำเนินการแล้ว');
        } catch (error) {
            console.error('Cancel error:', error);
        } finally {
            setLoading(false);
            state.pendingConfirmation = null;
        }
    }

    /**
     * Set loading state
     */
    function setLoading(loading) {
        state.isLoading = loading;
        elements.send.disabled = loading || elements.input.value.trim() === '';

        if (loading) {
            // Add loading indicator
            const loadingEl = document.createElement('div');
            loadingEl.className = 'ai-chat-message assistant ai-chat-loading';
            loadingEl.innerHTML = `
                <div class="ai-chat-message-avatar">🤖</div>
                <div class="ai-chat-message-content">
                    <div class="ai-chat-typing">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;
            elements.messages.appendChild(loadingEl);
            scrollToBottom();
        }
    }

    /**
     * Check API health
     */
    async function checkHealth() {
        try {
            // Use 'ping' action which doesn't require authentication
            const response = await fetch(CONFIG.apiEndpoint + '?action=ping', {
                credentials: 'include'
            });
            const data = await response.json();
            console.log('Health check response:', data);  // Debug log

            if (data.success && data.provider?.available) {
                const providerName = data.provider.display_name || data.provider.name;
                const model = data.provider.model || '';
                setStatus('online', `${providerName} • ${model}`);
                // Update powered by text
                updatePoweredBy(data.provider);
            } else if (data.success && data.ollama?.available) {
                // Backward compatibility
                setStatus('online', 'ออนไลน์');
                updatePoweredBy({ name: 'ollama', display_name: 'Ollama' });
            } else {
                const providerName = data.provider?.display_name || 'AI';
                setStatus('offline', `${providerName} ไม่พร้อม`);
                if (data.provider) updatePoweredBy(data.provider);
            }
        } catch (error) {
            setStatus('offline', 'ไม่สามารถเชื่อมต่อได้');
        }
    }

    /**
     * Set status indicator
     */
    function setStatus(status, text) {
        elements.status.className = 'ai-chat-status ' + status;
        elements.status.querySelector('.status-text').textContent = text;
    }

    /**
     * Update "Powered by" text based on active provider
     */
    function updatePoweredBy(provider) {
        if (!elements.powered) return;
        
        const providerIcons = {
            'openai': '⚡',
            'ollama': '🦙',
            'default': '✨'
        };
        
        const name = provider.display_name || provider.name || 'AI';
        const icon = providerIcons[provider.name] || providerIcons.default;
        
        elements.powered.innerHTML = `Powered by ${name} ${icon}`;
    }

    /**
     * Format message content
     */
    function formatMessage(content) {
        if (!content) return '';
        
        // Escape HTML
        let formatted = content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Convert markdown-like formatting
        // Bold
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic
        formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Code
        formatted = formatted.replace(/`(.*?)`/g, '<code>$1</code>');
        // Line breaks
        formatted = formatted.replace(/\n/g, '<br>');

        return formatted;
    }

    /**
     * Format time
     */
    function formatTime(date) {
        return date.toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    /**
     * Scroll to bottom of messages
     */
    function scrollToBottom() {
        elements.messages.scrollTop = elements.messages.scrollHeight;
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
