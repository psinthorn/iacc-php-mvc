<?php
$pageTitle = 'AI Assistant — Settings';

/**
 * AI Settings View (rendered inside admin layout)
 * Variables: $settings, $message, $messageType
 */
?>
<div class="ai-settings-page">
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">
            <i class="fa fa-cogs"></i> AI Settings
            <small>Configure AI provider and model settings</small>
        </h3>
        <?php $currentPage = 'ai_settings'; include __DIR__ . '/_nav.php'; ?>
    </div>
</div>

<!-- Hero Header -->
<div class="settings-hero" id="currentStatusBar">
    <div class="hero-content">
        <div class="hero-icon" id="currentProviderIcon">
            <?php if ($settings['provider'] === 'openai'): ?><i class="fa fa-bolt"></i><?php else: ?><i class="fa fa-server"></i><?php endif; ?>
        </div>
        <div class="hero-text">
            <h2 id="currentProviderName">
                <?= $settings['provider'] === 'openai' ? 'OpenAI' : 'Ollama' ?>
                <span style="font-weight:400;font-size:14px;opacity:0.9;">• <?= $settings['provider'] === 'openai' ? $settings['openai_model'] : $settings['ollama_model'] ?></span>
            </h2>
            <p id="currentProviderDesc">
                <?= $settings['provider'] === 'openai' ? 'Cloud-based AI • Fast response • API key required' : 'Local AI • Privacy-focused • GPU recommended' ?>
            </p>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <span class="hero-stat-value" id="currentStatusBadge"><span class="status-dot"></span><span id="currentStatusText">Checking...</span></span>
            <span class="hero-stat-label">Status</span>
        </div>
        <div class="hero-stat hero-action" onclick="checkCurrentStatus()">
            <span class="hero-stat-value"><i class="fa fa-refresh"></i></span>
            <span class="hero-stat-label">Refresh</span>
        </div>
        <div class="hero-stat hero-action" onclick="quickTestAI()">
            <span class="hero-stat-value"><i class="fa fa-comment"></i></span>
            <span class="hero-stat-label">Quick Test</span>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="ai-alert ai-alert-<?= $messageType === 'error' ? 'error' : $messageType ?>">
    <i class="fa fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="POST" id="settingsForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">

    <!-- OpenAI Provider -->
    <div class="ai-card provider-card <?= $settings['provider'] === 'openai' ? 'selected' : '' ?>" id="openaiCard">
        <div class="ai-card-header provider-header-bar">
            <div class="provider-icon openai"><i class="fa fa-bolt"></i></div>
            <div class="provider-title"><strong>OpenAI</strong><small>Fast, reliable cloud-based AI. Requires API key.</small></div>
            <div class="provider-select">
                <input type="radio" name="provider" value="openai" id="providerOpenAI" <?= $settings['provider'] === 'openai' ? 'checked' : '' ?> onchange="updateProviderUI()">
                <label for="providerOpenAI">Use OpenAI</label>
            </div>
        </div>
        <div class="ai-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>API Key</label>
                    <input type="password" name="openai_api_key" id="openaiApiKey" value="<?= htmlspecialchars($settings['openai_api_key']) ?>" placeholder="sk-...">
                    <small>Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></small>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <select name="openai_model" id="openaiModel">
                        <?php foreach (['gpt-4o-mini' => 'GPT-4o Mini (Fast, Cheap)', 'gpt-4o' => 'GPT-4o (Most Capable)', 'gpt-4-turbo' => 'GPT-4 Turbo', 'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Legacy)'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $settings['openai_model'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Timeout (seconds)</label><input type="number" name="openai_timeout" value="<?= $settings['openai_timeout'] ?>" min="10" max="300"></div>
            <button type="button" class="action-btn small" onclick="testProvider('openai')"><i class="fa fa-plug"></i> Test Connection</button>
            <div class="test-result" id="openaiTestResult"></div>
        </div>
    </div>

    <!-- Ollama Provider -->
    <div class="ai-card provider-card <?= $settings['provider'] === 'ollama' ? 'selected' : 'disabled' ?>" id="ollamaCard">
        <div class="ai-card-header provider-header-bar">
            <div class="provider-icon ollama"><i class="fa fa-server"></i></div>
            <div class="provider-title"><strong>Ollama (Local)</strong><small>Run AI locally. Free but requires powerful hardware (GPU recommended).</small></div>
            <div class="provider-select">
                <label class="toggle-switch"><input type="checkbox" name="ollama_enabled" id="ollamaEnabled" aria-label="Enable Ollama" <?= $settings['ollama_enabled'] ? 'checked' : '' ?> onchange="toggleOllama()"><span class="toggle-slider"></span></label>
                <span style="margin-left:10px;color:#666;">Enable</span>
            </div>
        </div>
        <div class="ai-card-body">
            <div id="ollamaSettings" style="<?= $settings['ollama_enabled'] ? '' : 'display:none;' ?>">
                <div style="margin-bottom:15px;">
                    <input type="radio" name="provider" value="ollama" id="providerOllama" <?= $settings['provider'] === 'ollama' ? 'checked' : '' ?> onchange="updateProviderUI()">
                    <label for="providerOllama" style="font-weight:normal;">Use Ollama as primary provider</label>
                </div>
                <div class="form-group">
                    <label>Ollama URL</label>
                    <input type="text" name="ollama_url" id="ollamaUrl" value="<?= htmlspecialchars($settings['ollama_url']) ?>" placeholder="http://ollama:11434">
                    <small>Use "http://ollama:11434" for Docker, "http://localhost:11434" for local</small>
                </div>
                <div class="form-group">
                    <label>Model Selection</label>
                    <select name="ollama_model" id="ollamaModel" onchange="updateModelInfo()">
                        <optgroup label="🚀 Lightweight (< 2GB RAM) - Fast">
                            <option value="qwen2:0.5b" <?= $settings['ollama_model'] === 'qwen2:0.5b' ? 'selected' : '' ?>>Qwen2 0.5B - Fastest (395MB)</option>
                            <option value="tinyllama:1.1b" <?= $settings['ollama_model'] === 'tinyllama:1.1b' ? 'selected' : '' ?>>TinyLlama 1.1B - Good balance (637MB)</option>
                        </optgroup>
                        <optgroup label="⚡ Small (2-4GB RAM) - Balanced">
                            <option value="gemma:2b" <?= $settings['ollama_model'] === 'gemma:2b' ? 'selected' : '' ?>>Gemma 2B - Google (1.7GB)</option>
                            <option value="phi3:mini" <?= $settings['ollama_model'] === 'phi3:mini' ? 'selected' : '' ?>>Phi-3 Mini - Microsoft, Smart (2.3GB)</option>
                            <option value="llama3.2:1b" <?= $settings['ollama_model'] === 'llama3.2:1b' ? 'selected' : '' ?>>Llama 3.2 1B - Meta (1.3GB)</option>
                        </optgroup>
                        <optgroup label="🔥 Medium (4-8GB RAM) - Better Quality">
                            <option value="llama3.2:3b" <?= $settings['ollama_model'] === 'llama3.2:3b' ? 'selected' : '' ?>>Llama 3.2 3B - Meta (2GB)</option>
                            <option value="mistral:7b" <?= $settings['ollama_model'] === 'mistral:7b' ? 'selected' : '' ?>>Mistral 7B - High quality (4.1GB)</option>
                            <option value="codellama:7b" <?= $settings['ollama_model'] === 'codellama:7b' ? 'selected' : '' ?>>CodeLlama 7B - Code focused (3.8GB)</option>
                        </optgroup>
                        <optgroup label="⚠️ Large (8GB+ RAM) - GPU Recommended">
                            <option value="llama3.1:8b" <?= $settings['ollama_model'] === 'llama3.1:8b' ? 'selected' : '' ?>>Llama 3.1 8B - Very capable (4.7GB)</option>
                            <option value="gemma2:9b" <?= $settings['ollama_model'] === 'gemma2:9b' ? 'selected' : '' ?>>Gemma2 9B - Google latest (5.4GB)</option>
                        </optgroup>
                        <optgroup label="Custom"><option value="custom">Custom model name...</option></optgroup>
                    </select>
                    <input type="text" id="ollamaModelCustom" placeholder="Enter custom model name" style="display:none;margin-top:10px;">
                </div>
                <div id="modelInfoPanel" class="model-info-panel"><div id="modelInfo"></div></div>
                <div id="resourceWarning" class="resource-warning" style="display:none;">
                    <strong><i class="fa fa-exclamation-triangle"></i> High Resource Usage Warning</strong>
                    <p id="warningText" style="margin:10px 0 0 0;"></p>
                </div>
                <div class="form-group"><label>Timeout (seconds)</label><input type="number" name="ollama_timeout" value="<?= $settings['ollama_timeout'] ?>" min="30" max="600"><small>Ollama on CPU can be slow. Increase if you get timeouts.</small></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" class="action-btn small" onclick="testProvider('ollama')"><i class="fa fa-plug"></i> Test Connection</button>
                    <button type="button" class="action-btn small" onclick="pullModel()" id="pullModelBtn"><i class="fa fa-download"></i> Pull Model</button>
                    <button type="button" class="action-btn small" onclick="listInstalledModels()"><i class="fa fa-list"></i> Show Installed</button>
                </div>
                <div class="test-result" id="ollamaTestResult"></div>
            </div>
            <div id="ollamaDisabledMsg" style="<?= $settings['ollama_enabled'] ? 'display:none;' : '' ?>color:#888;font-style:italic;padding:10px 0;"><i class="fa fa-info-circle"></i> Ollama is disabled. Enable to configure local AI.</div>
        </div>
    </div>

    <!-- Common Settings -->
    <div class="ai-card">
        <div class="ai-card-header"><i class="fa fa-sliders"></i> Common Settings</div>
        <div class="ai-card-body">
            <div class="form-row">
                <div class="form-group"><label>Temperature</label><input type="number" name="temperature" value="<?= $settings['temperature'] ?>" min="0" max="2" step="0.1"><small>0 = deterministic, 1 = creative, 2 = very random</small></div>
                <div class="form-group"><label>Max Tokens</label><input type="number" name="max_tokens" value="<?= $settings['max_tokens'] ?>" min="100" max="8000"><small>Maximum response length</small></div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="action-btn primary"><i class="fa fa-save"></i> Save Settings</button>
        <button type="button" class="action-btn secondary" onclick="quickTest()"><i class="fa fa-play"></i> Quick Test</button>
    </div>
    <div class="test-result" id="quickTestResult" style="margin-top:20px;"></div>
</form>

</div><!-- /.ai-settings-page -->

<style>
/* Page container */
.ai-settings-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Header */
.ai-settings-page .settings-hero {
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
.ai-settings-page .hero-content {
    display: flex;
    align-items: center;
    gap: 20px;
}
.ai-settings-page .hero-icon {
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
.ai-settings-page .hero-text h2 { margin: 0; font-size: 22px; font-weight: 700; }
.ai-settings-page .hero-text p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
.ai-settings-page .hero-stats { display: flex; gap: 10px; flex-wrap: wrap; }
.ai-settings-page .hero-stat {
    background: rgba(255,255,255,0.15);
    padding: 10px 18px;
    border-radius: 10px;
    text-align: center;
    min-width: 80px;
    border: 1px solid rgba(255,255,255,0.2);
}
.ai-settings-page .hero-stat-value { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 16px; font-weight: 600; }
.ai-settings-page .hero-stat-label { display: block; font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.ai-settings-page .hero-action { cursor: pointer; transition: background 0.2s; }
.ai-settings-page .hero-action:hover { background: rgba(255,255,255,0.25); }
.ai-settings-page .status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ffc107; display: inline-block;
    animation: settingsPulse 2s infinite;
}
.ai-settings-page .hero-stat .status-dot.online { background: #28a745; }
.ai-settings-page .hero-stat .status-dot.offline { background: #dc3545; }
@keyframes settingsPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

/* Cards */
.ai-settings-page .ai-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.ai-settings-page .ai-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.ai-settings-page .ai-card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 15px;
}
.ai-settings-page .ai-card-header i { color: #667eea; margin-right: 8px; }
.ai-settings-page .ai-card-body { padding: 20px; }

/* Provider cards */
.ai-settings-page .provider-card { border: 2px solid transparent; transition: all 0.3s ease; }
.ai-settings-page .provider-card.selected { border-color: #667eea; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15); }
.ai-settings-page .provider-card.disabled { opacity: 0.6; }
.ai-settings-page .provider-header-bar {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
}
.ai-settings-page .provider-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.ai-settings-page .provider-icon.openai { background: linear-gradient(135deg, #10a37f, #0d8a6a); color: white; }
.ai-settings-page .provider-icon.ollama { background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; }
.ai-settings-page .provider-title { flex: 1; }
.ai-settings-page .provider-title strong { display: block; font-size: 16px; color: #333; }
.ai-settings-page .provider-title small { color: #666; font-size: 13px; }
.ai-settings-page .provider-select {
    display: flex; align-items: center; gap: 8px;
}
.ai-settings-page .provider-select input[type="radio"] { width: 18px; height: 18px; cursor: pointer; }
.ai-settings-page .provider-select label { margin: 0; font-weight: normal; font-size: 13px; color: #555; }

/* Forms */
.ai-settings-page .form-group { margin-bottom: 16px; }
.ai-settings-page .form-group label { display: block; font-weight: 600; font-size: 13px; color: #555; margin-bottom: 6px; }
.ai-settings-page .form-group input,
.ai-settings-page .form-group select {
    width: 100%; padding: 10px 14px;
    border: 1px solid #ddd; border-radius: 8px;
    font-size: 14px; height: 44px;
    transition: border-color 0.2s;
}
.ai-settings-page .form-group input:focus,
.ai-settings-page .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.ai-settings-page .form-group small { display: block; color: #888; font-size: 12px; margin-top: 4px; }
.ai-settings-page .form-row { display: flex; gap: 20px; }
.ai-settings-page .form-row .form-group { flex: 1; }

/* Buttons */
.ai-settings-page .action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 22px; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; text-decoration: none;
}
.ai-settings-page .action-btn.primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff; font-size: 14px; padding: 12px 28px;
}
.ai-settings-page .action-btn.primary:hover { box-shadow: 0 4px 15px rgba(102,126,234,0.4); transform: translateY(-1px); }
.ai-settings-page .action-btn.secondary {
    background: #f0f0f0; color: #555;
    font-size: 14px; padding: 12px 28px;
}
.ai-settings-page .action-btn.secondary:hover { background: #e0e0e0; }
.ai-settings-page .action-btn.small { padding: 8px 16px; font-size: 12px; background: #f0f4ff; color: #667eea; }
.ai-settings-page .action-btn.small:hover { background: #e0e8ff; }
.ai-settings-page .form-actions { display: flex; gap: 15px; margin-top: 20px; }

/* Toggle switch */
.ai-settings-page .toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.ai-settings-page .toggle-switch input { opacity: 0; width: 0; height: 0; }
.ai-settings-page .toggle-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc; transition: .3s; border-radius: 26px;
}
.ai-settings-page .toggle-slider:before {
    position: absolute; content: ""; height: 20px; width: 20px;
    left: 3px; bottom: 3px; background-color: white;
    transition: .3s; border-radius: 50%;
}
.ai-settings-page input:checked + .toggle-slider { background-color: #667eea; }
.ai-settings-page input:checked + .toggle-slider:before { transform: translateX(24px); }

/* Test result */
.ai-settings-page .test-result { margin-top: 15px; padding: 15px; border-radius: 8px; display: none; font-size: 13px; }
.ai-settings-page .test-result.success { background: #d4edda; border: 1px solid #c3e6cb; }
.ai-settings-page .test-result.error { background: #f8d7da; border: 1px solid #f5c6cb; }

/* Model info & warnings */
.ai-settings-page .model-info-panel { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
.ai-settings-page .resource-warning { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 15px; color: #856404; }

/* Alert */
.ai-settings-page .ai-alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
.ai-settings-page .ai-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.ai-settings-page .ai-alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* Spinner */
.ai-settings-page .loading-spinner {
    display: inline-block; width: 16px; height: 16px;
    border: 2px solid #f3f3f3; border-top: 2px solid #667eea;
    border-radius: 50%; animation: settingsSpin 1s linear infinite;
}
@keyframes settingsSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 768px) {
    .ai-settings-page .settings-hero { flex-direction: column; text-align: center; }
    .ai-settings-page .hero-content { flex-direction: column; }
    .ai-settings-page .hero-stats { justify-content: center; }
    .ai-settings-page .form-row { flex-direction: column; gap: 0; }
    .ai-settings-page .provider-header-bar { flex-direction: column; text-align: center; }
    .ai-settings-page .form-actions { flex-direction: column; }
}
</style>

<script>
var ajaxUrl = 'index.php?page=ai_settings_api';
var modelInfo = {
    'qwen2:0.5b': { size:'395MB',ram:'1GB',cpu:15,speed:'Very Fast',quality:'⭐⭐',desc:'Smallest and fastest.' },
    'tinyllama:1.1b': { size:'637MB',ram:'1.5GB',cpu:20,speed:'Fast',quality:'⭐⭐⭐',desc:'Great balance of speed and quality.' },
    'gemma:2b': { size:'1.7GB',ram:'3GB',cpu:30,speed:'Fast',quality:'⭐⭐⭐',desc:'Google model. Good reasoning.' },
    'phi3:mini': { size:'2.3GB',ram:'4GB',cpu:35,speed:'Medium',quality:'⭐⭐⭐⭐',desc:'Microsoft model. Recommended!' },
    'llama3.2:1b': { size:'1.3GB',ram:'2GB',cpu:25,speed:'Fast',quality:'⭐⭐⭐',desc:'Meta latest small model.' },
    'llama3.2:3b': { size:'2GB',ram:'4GB',cpu:40,speed:'Medium',quality:'⭐⭐⭐⭐',desc:'Good quality with reasonable speed.' },
    'mistral:7b': { size:'4.1GB',ram:'8GB',cpu:70,speed:'Slow',quality:'⭐⭐⭐⭐⭐',desc:'High quality. Needs good hardware.' },
    'codellama:7b': { size:'3.8GB',ram:'8GB',cpu:70,speed:'Slow',quality:'⭐⭐⭐⭐⭐',desc:'Specialized for code.' },
    'llama3.1:8b': { size:'4.7GB',ram:'10GB',cpu:80,speed:'Very Slow',quality:'⭐⭐⭐⭐⭐',desc:'Very capable. GPU recommended.' },
    'gemma2:9b': { size:'5.4GB',ram:'12GB',cpu:85,speed:'Very Slow',quality:'⭐⭐⭐⭐⭐',desc:'Google latest. GPU needed.' },
};

function updateModelInfo() {
    var select = document.getElementById('ollamaModel'), model = select.value;
    var infoPanel = document.getElementById('modelInfoPanel'), customInput = document.getElementById('ollamaModelCustom'), warning = document.getElementById('resourceWarning');
    if (model === 'custom') { customInput.style.display='block'; customInput.name='ollama_model'; select.name=''; infoPanel.innerHTML='<i class="fa fa-info-circle"></i> Enter a custom model name.'; warning.style.display='none'; return; }
    customInput.style.display='none'; customInput.name=''; select.name='ollama_model';
    var info = modelInfo[model];
    if (info) {
        infoPanel.innerHTML='<div style="display:flex;gap:20px;flex-wrap:wrap;"><div><strong>Size:</strong> '+info.size+'</div><div><strong>RAM:</strong> '+info.ram+'</div><div><strong>Speed:</strong> '+info.speed+'</div><div><strong>Quality:</strong> '+info.quality+'</div></div><p style="margin:10px 0 0 0;color:#666;">'+info.desc+'</p>';
        if (info.cpu > 60) { warning.style.display='block'; document.getElementById('warningText').innerHTML='This model may use <strong>'+info.cpu+'%+ CPU</strong> and <strong>'+info.ram+'+ RAM</strong>.'; } else { warning.style.display='none'; }
    } else { infoPanel.innerHTML='<i class="fa fa-info-circle"></i> Select a model to see info.'; warning.style.display='none'; }
}

function updateProviderUI() {
    var provider = document.querySelector('input[name="provider"]:checked').value;
    document.getElementById('openaiCard').classList.toggle('selected', provider==='openai');
    document.getElementById('ollamaCard').classList.toggle('selected', provider==='ollama');
}

function toggleOllama() {
    var enabled = document.getElementById('ollamaEnabled').checked;
    document.getElementById('ollamaSettings').style.display = enabled ? '' : 'none';
    document.getElementById('ollamaDisabledMsg').style.display = enabled ? 'none' : '';
    document.getElementById('ollamaCard').classList.toggle('disabled', !enabled);
    if (!enabled && document.getElementById('providerOllama').checked) { document.getElementById('providerOpenAI').checked=true; updateProviderUI(); }
}

function testProvider(provider) {
    var resultDiv = document.getElementById(provider+'TestResult');
    resultDiv.style.display='block'; resultDiv.className='test-result'; resultDiv.innerHTML='<div class="loading-spinner"></div> Testing connection...';
    var formData = new FormData(); formData.append('action','test_provider'); formData.append('provider',provider);
    if (provider==='openai') { formData.append('openai_api_key',document.getElementById('openaiApiKey').value); formData.append('openai_model',document.getElementById('openaiModel').value); }
    else { formData.append('ollama_url',document.getElementById('ollamaUrl').value); var m=document.getElementById('ollamaModel').value; formData.append('ollama_model',m==='custom'?document.getElementById('ollamaModelCustom').value:m); }
    fetch(ajaxUrl,{method:'POST',body:formData,credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(data){
        if (data.available) { resultDiv.className='test-result success'; resultDiv.innerHTML='<i class="fa fa-check-circle"></i> <strong>Connected!</strong>'; }
        else { resultDiv.className='test-result error'; resultDiv.innerHTML='<i class="fa fa-times-circle"></i> <strong>Failed</strong><br>'+(data.error||'Unknown'); }
    }).catch(function(e) { resultDiv.className='test-result error'; resultDiv.innerHTML='<i class="fa fa-times-circle"></i> '+e.message; });
}

function quickTest() {
    var resultDiv=document.getElementById('quickTestResult'); resultDiv.style.display='block'; resultDiv.className='test-result'; resultDiv.innerHTML='<div class="loading-spinner"></div> Running quick test...';
    var fd=new FormData(); fd.append('action','quick_test');
    fetch(ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(data){
        if (data.success) { resultDiv.className='test-result success'; resultDiv.innerHTML='<i class="fa fa-check-circle"></i> <strong>Test Passed!</strong><br>Provider: '+data.provider+'<br>Response: '+(data.response||'N/A')+'<br>Time: '+data.elapsed_time+'s'; }
        else { resultDiv.className='test-result error'; resultDiv.innerHTML='<i class="fa fa-times-circle"></i> <strong>Failed</strong><br>'+(data.error||'Unknown'); }
    }).catch(function(e) { resultDiv.className='test-result error'; resultDiv.innerHTML='<i class="fa fa-times-circle"></i> '+e.message; });
}

function pullModel() {
    var model=document.getElementById('ollamaModel').value==='custom'?document.getElementById('ollamaModelCustom').value:document.getElementById('ollamaModel').value;
    if(!model){alert('Select a model first');return;}
    var resultDiv=document.getElementById('ollamaTestResult'); resultDiv.style.display='block'; resultDiv.className='test-result'; resultDiv.innerHTML='<div class="loading-spinner"></div> Pulling "'+model+'"... This may take several minutes.';
    var fd=new FormData(); fd.append('action','pull_model'); fd.append('model',model); fd.append('ollama_url',document.getElementById('ollamaUrl').value);
    fetch(ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(data){
        resultDiv.className=data.success?'test-result success':'test-result error'; resultDiv.innerHTML=data.success?'<i class="fa fa-check-circle"></i> Model "'+model+'" pulled!':'<i class="fa fa-times-circle"></i> '+(data.error||'Failed');
    }).catch(function(e) { resultDiv.className='test-result error'; resultDiv.innerHTML='<i class="fa fa-times-circle"></i> '+e.message; });
}

function listInstalledModels() {
    var resultDiv=document.getElementById('ollamaTestResult'); resultDiv.style.display='block'; resultDiv.className='test-result'; resultDiv.innerHTML='<div class="loading-spinner"></div> Fetching...';
    var fd=new FormData(); fd.append('action','list_models'); fd.append('ollama_url',document.getElementById('ollamaUrl').value);
    fetch(ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(data){
        if(data.success&&data.models){var h='<i class="fa fa-check-circle"></i> <strong>Installed:</strong><ul style="margin:10px 0;padding-left:20px;">';data.models.forEach(function(m){var s=m.size?(m.size/1024/1024/1024).toFixed(1)+' GB':'?';h+='<li><strong>'+m.name+'</strong> ('+s+')</li>';});h+='</ul>';resultDiv.className='test-result success';resultDiv.innerHTML=h;}
        else{resultDiv.className='test-result error';resultDiv.innerHTML='<i class="fa fa-times-circle"></i> '+(data.error||'Could not fetch');}
    }).catch(function(e) { resultDiv.className='test-result error'; resultDiv.innerHTML='<i class="fa fa-times-circle"></i> '+e.message; });
}

function checkCurrentStatus() {
    var badge=document.getElementById('currentStatusBadge'),statusText=document.getElementById('currentStatusText'),dot=badge.querySelector('.status-dot');
    statusText.textContent='Checking...'; dot.className='status-dot';
    fetch(ajaxUrl+'&action=status',{credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(data){
        if(data.success&&data.provider){var p=data.provider;
            dot.className='status-dot '+(p.available?'online':'offline');
            statusText.textContent=p.available?'Online':'Offline';
            document.getElementById('currentProviderName').innerHTML=p.display_name+' <span style="font-weight:400;font-size:14px;opacity:0.9;">• '+p.model+'</span>';
        }
    }).catch(function(e) { dot.className='status-dot offline'; statusText.textContent='Error'; });
}

function quickTestAI() {
    var statusText=document.getElementById('currentStatusText'); statusText.textContent='Testing...';
    var fd=new FormData(); fd.append('action','quick_test');
    fetch(ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(data){
        var dot=document.querySelector('.ai-settings-page .status-dot');
        if(data.success){statusText.textContent='Working!';dot.className='status-dot online';alert('AI Test Passed!\nProvider: '+data.provider+'\nTime: '+data.elapsed_time+'s');}
        else{statusText.textContent='Failed';dot.className='status-dot offline';alert('Failed: '+(data.error||'Unknown'));}
    }).catch(function(e){document.getElementById('currentStatusText').textContent='Error';alert(e.message);});
    setTimeout(checkCurrentStatus,2000);
}

document.addEventListener('DOMContentLoaded',function(){updateModelInfo();checkCurrentStatus();});
</script>
