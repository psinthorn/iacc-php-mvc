<?php
/** @var array $tables */
/** @var string $discoveredAt */
/** @var array|null $cachedSchema */
?>
<div class="ai-schema-browser-page">
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">
            <i class="fa fa-database"></i> Database Schema Browser
            <small>AI Schema Discovery</small>
        </h3>
        <?php $currentPage = 'ai_schema_browser'; include __DIR__ . '/_nav.php'; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="ai-card">
            <div class="ai-card-header">
                <i class="fa fa-info-circle"></i> Schema Cache Status
                <div class="pull-right">
                    <button class="btn btn-xs btn-success" onclick="refreshSchema()" style="border-radius: 6px;">
                        <i class="fa fa-refresh"></i> Refresh Cache
                    </button>
                </div>
            </div>
            <div class="ai-card-body">
                <div class="row">
                    <div class="col-md-3"><div class="stat-card stat-primary"><span class="stat-value"><?=count($tables)?></span><span class="stat-label">Tables Discovered</span></div></div>
                    <div class="col-md-3"><div class="stat-card stat-success"><span class="stat-value" id="total-rows">-</span><span class="stat-label">Total Rows</span></div></div>
                    <div class="col-md-3"><div class="stat-card stat-info"><span class="stat-value" id="total-columns">-</span><span class="stat-label">Total Columns</span></div></div>
                    <div class="col-md-3"><div class="stat-card stat-default"><span class="stat-value stat-value-sm"><?=htmlspecialchars($discoveredAt)?></span><span class="stat-label">Last Cached</span></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-search"></i> Search Schema</div>
            <div class="ai-card-body">
                <div class="input-group">
                    <input type="text" id="search-input" class="form-control" placeholder="Search tables or columns...">
                    <span class="input-group-btn"><button class="btn btn-primary" onclick="searchSchema()"><i class="fa fa-search"></i></button></span>
                </div>
                <div id="search-results" style="margin-top: 10px;"></div>
            </div>
        </div>
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-list"></i> Tables (<?=count($tables)?>)</div>
            <div class="ai-card-body tables-list-container">
                <div class="list-group" id="tables-list">
                    <?php foreach ($tables as $tableName => $info): ?>
                    <a href="#" class="list-group-item table-item" data-table="<?=htmlspecialchars($tableName)?>">
                        <span class="badge"><?=$info['row_count'] ?? 0?></span>
                        <i class="fa fa-table"></i> <?=htmlspecialchars($tableName)?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-info-circle"></i> Table Details <span id="selected-table" class="text-primary"></span></div>
            <div class="ai-card-body" id="table-details"><p class="text-muted text-center">Select a table to view details</p></div>
        </div>
        <div class="ai-card">
            <div class="ai-card-header"><i class="fa fa-code"></i> Compact Schema (for AI)</div>
            <div class="ai-card-body">
                <pre class="code-block" id="compact-schema"><?=htmlspecialchars(\SchemaDiscovery::loadCompactSchema() ?: 'Not cached')?></pre>
            </div>
        </div>
    </div>
</div>
</div><!-- /.ai-schema-browser-page -->

<style>
/* Cards */
.ai-schema-browser-page .ai-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
}
.ai-schema-browser-page .ai-card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 15px;
}
.ai-schema-browser-page .ai-card-header i { color: #667eea; margin-right: 8px; }
.ai-schema-browser-page .ai-card-body { padding: 20px; }

/* Stat cards */
.ai-schema-browser-page .stat-card {
    background: #fff;
    border-radius: 10px;
    padding: 18px 12px;
    text-align: center;
    border: 1px solid #f0f0f0;
    transition: transform 0.2s;
}
.ai-schema-browser-page .stat-card:hover { transform: translateY(-2px); }
.ai-schema-browser-page .stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
}
.ai-schema-browser-page .stat-value-sm { font-size: 14px; }
.ai-schema-browser-page .stat-label {
    display: block;
    font-size: 11px;
    color: #888;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ai-schema-browser-page .stat-primary .stat-value { color: #667eea; }
.ai-schema-browser-page .stat-success .stat-value { color: #27ae60; }
.ai-schema-browser-page .stat-info .stat-value { color: #3498db; }
.ai-schema-browser-page .stat-default .stat-value { color: #666; }

/* Tables list */
.ai-schema-browser-page .tables-list-container { max-height: 400px; overflow-y: auto; padding: 10px; }
.ai-schema-browser-page .table-item {
    border-radius: 8px;
    margin-bottom: 2px;
    border: none;
    transition: all 0.2s;
}
.ai-schema-browser-page .table-item:hover { background: #f0f4ff; }
.ai-schema-browser-page .table-item.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
}
.ai-schema-browser-page .table-item.active .badge { background: rgba(255,255,255,0.3); color: #fff; }
.ai-schema-browser-page .table-item .badge { background: #e9ecef; color: #666; }

/* Column details */
.ai-schema-browser-page .column-row {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f0;
    border-radius: 6px;
    transition: background 0.2s;
}
.ai-schema-browser-page .column-row:hover { background: #f8f9fa; }
.ai-schema-browser-page .key-badge { font-size: 10px; padding: 2px 6px; border-radius: 10px; }
.ai-schema-browser-page .fk-link {
    color: #f39c12;
    font-size: 12px;
    padding: 4px 0;
}

/* Code block */
.ai-schema-browser-page .code-block {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    font-size: 11px;
    line-height: 1.6;
}

/* Search results */
.ai-schema-browser-page #search-results a {
    color: #667eea;
    transition: color 0.2s;
}
.ai-schema-browser-page #search-results a:hover { color: #764ba2; }
</style>

<script>
const tables = <?=json_encode(array_keys($tables))?>;
let totalRows = 0, totalColumns = 0;
<?php foreach ($tables as $info): ?>
totalRows += <?=$info['row_count'] ?? 0?>;
totalColumns += <?=count($info['columns'] ?? [])?>;
<?php endforeach; ?>
document.getElementById('total-rows').textContent = totalRows.toLocaleString();
document.getElementById('total-columns').textContent = totalColumns.toLocaleString();

document.querySelectorAll('.table-item').forEach(el => {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.table-item').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        loadTableDetails(this.dataset.table);
    });
});

function loadTableDetails(tableName) {
    document.getElementById('selected-table').textContent = ' - ' + tableName;
    document.getElementById('table-details').innerHTML = '<p class="text-muted">Loading...</p>';
    fetch(`index.php?page=ai_schema_browser&ajax=1&action=get_table&table=${encodeURIComponent(tableName)}`, { credentials: 'same-origin' })
    .then(r => r.json()).then(data => {
        if (data.success && data.table) renderTableDetails(data.table);
        else document.getElementById('table-details').innerHTML = '<div class="alert alert-warning">Could not load table details</div>';
    });
}

function renderTableDetails(table) {
    const columns = table.columns || [], indexes = table.indexes || [], fks = table.foreign_keys || [], sample = table.sample_data || [];
    let html = `<div class="row"><div class="col-md-6"><h5><i class="fa fa-columns"></i> Columns (${columns.length})</h5><div style="max-height:250px;overflow-y:auto;">
        ${columns.map(c => `<div class="column-row"><strong>${c.name}</strong> <span class="text-muted">${c.full_type||c.type}</span>
        ${c.key_type==='PRI'?'<span class="label label-primary key-badge">PK</span>':''}${c.key_type==='MUL'?'<span class="label label-info key-badge">FK/IDX</span>':''}${c.key_type==='UNI'?'<span class="label label-warning key-badge">UNI</span>':''}${c.nullable==='YES'?'<span class="text-muted" style="font-size:10px;">NULL</span>':''}</div>`).join('')}
        </div></div><div class="col-md-6"><h5><i class="fa fa-link"></i> Foreign Keys (${fks.length})</h5>
        ${fks.length>0?fks.map(fk=>`<div class="fk-link">${fk.column_name} → ${fk.ref_table}.${fk.ref_column}</div>`).join(''):'<p class="text-muted">No foreign keys</p>'}
        <h5 style="margin-top:15px;"><i class="fa fa-key"></i> Indexes (${indexes.length})</h5>
        ${indexes.length>0?`<small>${[...new Set(indexes.map(i=>i.Key_name))].join(', ')}</small>`:'<p class="text-muted">No indexes</p>'}
        </div></div>`;
    if (sample.length > 0) {
        const sampleCols = Object.keys(sample[0]).slice(0,6);
        html += `<h5 style="margin-top:15px;"><i class="fa fa-eye"></i> Sample Data</h5><div style="overflow-x:auto;"><table class="table table-condensed table-striped" style="font-size:11px;"><thead><tr>${sampleCols.map(c=>`<th>${c}</th>`).join('')}</tr></thead><tbody>${sample.map(row=>`<tr>${sampleCols.map(c=>`<td>${escapeHtml(String(row[c]??'').substring(0,50))}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
    }
    document.getElementById('table-details').innerHTML = html;
}

function searchSchema() {
    const pattern = document.getElementById('search-input').value; if (!pattern) return;
    fetch(`index.php?page=ai_schema_browser&ajax=1&action=search&pattern=${encodeURIComponent(pattern)}`, { credentials: 'same-origin' })
    .then(r => r.json()).then(data => { if (data.success) renderSearchResults(data.results); });
}

function renderSearchResults(results) {
    const container = document.getElementById('search-results');
    const tables = results.matching_tables || [], columns = results.matching_columns || [];
    let html = '';
    if (tables.length > 0) html += `<strong>Tables:</strong><br>${tables.map(t=>`<a href="#" onclick="loadTableDetails('${t.table_name}');return false;">${t.table_name}</a>`).join(', ')}<br><br>`;
    if (columns.length > 0) html += `<strong>Columns:</strong><br>${columns.slice(0,10).map(c=>`<a href="#" onclick="loadTableDetails('${c.table_name}');return false;">${c.table_name}.${c.column_name}</a>`).join('<br>')}${columns.length>10?`<br><small class="text-muted">...and ${columns.length-10} more</small>`:''}`;
    container.innerHTML = html || '<p class="text-muted">No results found</p>';
}

function refreshSchema() {
    if (!confirm('Refresh schema cache from database?')) return;
    const btn = event.target.closest('button'); btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refreshing...';
    fetch('index.php?page=ai_schema_browser&ajax=1&action=refresh_cache', { credentials: 'same-origin' })
    .then(r => r.json()).then(data => {
        if (data.success) { alert('Schema refreshed! Found ' + data.tables + ' tables.'); location.reload(); }
        else alert('Error: ' + (data.error || 'Unknown'));
    }).finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa fa-refresh"></i> Refresh Cache'; });
}

function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
document.getElementById('search-input').addEventListener('keypress', e => { if (e.key === 'Enter') searchSchema(); });
</script>
