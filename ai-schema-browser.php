<?php
/**
 * AI Schema Browser
 * 
 * Browse and explore database schema discovered by AI
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
    
    require_once __DIR__ . '/ai/schema-discovery.php';
    
    $conn = new PDO(
        'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
        'root', 'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    $action = $_GET['action'] ?? '';
    $discovery = new SchemaDiscovery($conn);
    
    switch ($action) {
        case 'refresh_cache':
            $schema = $discovery->discoverSchema();
            $discovery->saveToCache();
            echo json_encode(['success' => true, 'tables' => count($schema['tables'])]);
            exit;
            
        case 'get_table':
            $tableName = $_GET['table'] ?? '';
            $info = $discovery->getTableInfo($tableName);
            echo json_encode(['success' => true, 'table' => $info]);
            exit;
            
        case 'search':
            $pattern = $_GET['pattern'] ?? '';
            $results = $discovery->searchSchema($pattern);
            echo json_encode(['success' => true, 'results' => $results]);
            exit;
            
        case 'get_summary':
            $compact = SchemaDiscovery::loadCompactSchema();
            $full = SchemaDiscovery::loadFullSchema();
            echo json_encode([
                'success' => true, 
                'compact' => $compact,
                'tables' => $full ? count($full['tables']) : 0,
                'cached_at' => $full['discovered_at'] ?? null
            ]);
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

require_once __DIR__ . '/ai/schema-discovery.php';

// Load cached schema
$cachedSchema = SchemaDiscovery::loadFullSchema();
$tables = $cachedSchema['tables'] ?? [];
$discoveredAt = $cachedSchema['discovered_at'] ?? 'Never';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="fa fa-database"></i> Database Schema Browser
            <small>AI Schema Discovery</small>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-info-circle"></i> Schema Cache Status
                <div class="pull-right">
                    <button class="btn btn-xs btn-success" onclick="refreshSchema()">
                        <i class="fa fa-refresh"></i> Refresh Cache
                    </button>
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3><?=count($tables)?></h3>
                            <p>Tables Discovered</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3 id="total-rows">-</h3>
                            <p>Total Rows</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h3 id="total-columns">-</h3>
                            <p>Total Columns</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h4><?=htmlspecialchars($discoveredAt)?></h4>
                            <p>Last Cached</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Search and Table List -->
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-search"></i> Search Schema
            </div>
            <div class="panel-body">
                <div class="input-group">
                    <input type="text" id="search-input" class="form-control" placeholder="Search tables or columns...">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" onclick="searchSchema()">
                            <i class="fa fa-search"></i>
                        </button>
                    </span>
                </div>
                <div id="search-results" style="margin-top: 10px;"></div>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-list"></i> Tables (<?=count($tables)?>)
            </div>
            <div class="panel-body" style="max-height: 400px; overflow-y: auto;">
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
    
    <!-- Table Details -->
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-info-circle"></i> Table Details
                <span id="selected-table" class="text-primary"></span>
            </div>
            <div class="panel-body" id="table-details">
                <p class="text-muted text-center">Select a table to view details</p>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-code"></i> Compact Schema (for AI)
            </div>
            <div class="panel-body">
                <pre id="compact-schema" style="max-height: 300px; overflow-y: auto; font-size: 11px;"><?=htmlspecialchars(SchemaDiscovery::loadCompactSchema() ?: 'Not cached')?></pre>
            </div>
        </div>
    </div>
</div>

<style>
.stat-box {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}
.stat-box h3 {
    margin: 0;
    color: #2196F3;
    font-size: 28px;
}
.stat-box h4 {
    margin: 0;
    color: #666;
    font-size: 14px;
}
.stat-box p {
    margin: 5px 0 0;
    color: #666;
}
.table-item.active {
    background: #2196F3;
    color: white;
}
.table-item.active .badge {
    background: white;
    color: #2196F3;
}
.column-row {
    padding: 5px 10px;
    border-bottom: 1px solid #eee;
}
.column-row:hover {
    background: #f5f5f5;
}
.key-badge {
    font-size: 10px;
    padding: 2px 5px;
}
.fk-link {
    color: #ff9800;
    font-size: 11px;
}
</style>

<script>
const tables = <?=json_encode(array_keys($tables))?>;
let totalRows = 0;
let totalColumns = 0;

// Calculate totals
<?php foreach ($tables as $info): ?>
totalRows += <?=$info['row_count'] ?? 0?>;
totalColumns += <?=count($info['columns'] ?? [])?>;
<?php endforeach; ?>

document.getElementById('total-rows').textContent = totalRows.toLocaleString();
document.getElementById('total-columns').textContent = totalColumns.toLocaleString();

// Table click handlers
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
    
    fetch(`index.php?page=ai_schema_browser&ajax=1&action=get_table&table=${encodeURIComponent(tableName)}`, {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.table) {
            renderTableDetails(data.table);
        } else {
            document.getElementById('table-details').innerHTML = 
                '<div class="alert alert-warning">Could not load table details</div>';
        }
    });
}

function renderTableDetails(table) {
    const columns = table.columns || [];
    const indexes = table.indexes || [];
    const fks = table.foreign_keys || [];
    const sample = table.sample_data || [];
    
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fa fa-columns"></i> Columns (${columns.length})</h5>
                <div style="max-height: 250px; overflow-y: auto;">
                    ${columns.map(c => `
                        <div class="column-row">
                            <strong>${c.name}</strong>
                            <span class="text-muted">${c.full_type || c.type}</span>
                            ${c.key_type === 'PRI' ? '<span class="label label-primary key-badge">PK</span>' : ''}
                            ${c.key_type === 'MUL' ? '<span class="label label-info key-badge">FK/IDX</span>' : ''}
                            ${c.key_type === 'UNI' ? '<span class="label label-warning key-badge">UNI</span>' : ''}
                            ${c.nullable === 'YES' ? '<span class="text-muted" style="font-size:10px;">NULL</span>' : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="col-md-6">
                <h5><i class="fa fa-link"></i> Foreign Keys (${fks.length})</h5>
                ${fks.length > 0 ? fks.map(fk => `
                    <div class="fk-link">
                        ${fk.column_name} → ${fk.ref_table}.${fk.ref_column}
                    </div>
                `).join('') : '<p class="text-muted">No foreign keys</p>'}
                
                <h5 style="margin-top: 15px;"><i class="fa fa-key"></i> Indexes (${indexes.length})</h5>
                ${indexes.length > 0 ? `<small>${[...new Set(indexes.map(i => i.Key_name))].join(', ')}</small>` : '<p class="text-muted">No indexes</p>'}
            </div>
        </div>
    `;
    
    if (sample.length > 0) {
        const sampleCols = Object.keys(sample[0]).slice(0, 6);
        html += `
            <h5 style="margin-top: 15px;"><i class="fa fa-eye"></i> Sample Data</h5>
            <div style="overflow-x: auto;">
                <table class="table table-condensed table-striped" style="font-size: 11px;">
                    <thead>
                        <tr>${sampleCols.map(c => `<th>${c}</th>`).join('')}</tr>
                    </thead>
                    <tbody>
                        ${sample.map(row => `
                            <tr>${sampleCols.map(c => `<td>${escapeHtml(String(row[c] ?? '').substring(0, 50))}</td>`).join('')}</tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    document.getElementById('table-details').innerHTML = html;
}

function searchSchema() {
    const pattern = document.getElementById('search-input').value;
    if (!pattern) return;
    
    fetch(`index.php?page=ai_schema_browser&ajax=1&action=search&pattern=${encodeURIComponent(pattern)}`, {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderSearchResults(data.results);
        }
    });
}

function renderSearchResults(results) {
    const container = document.getElementById('search-results');
    const tables = results.matching_tables || [];
    const columns = results.matching_columns || [];
    
    let html = '';
    
    if (tables.length > 0) {
        html += `<strong>Tables:</strong><br>`;
        html += tables.map(t => `<a href="#" onclick="loadTableDetails('${t.table_name}'); return false;">${t.table_name}</a>`).join(', ');
        html += '<br><br>';
    }
    
    if (columns.length > 0) {
        html += `<strong>Columns:</strong><br>`;
        html += columns.slice(0, 10).map(c => 
            `<a href="#" onclick="loadTableDetails('${c.table_name}'); return false;">${c.table_name}.${c.column_name}</a>`
        ).join('<br>');
        if (columns.length > 10) {
            html += `<br><small class="text-muted">...and ${columns.length - 10} more</small>`;
        }
    }
    
    if (!html) {
        html = '<p class="text-muted">No results found</p>';
    }
    
    container.innerHTML = html;
}

function refreshSchema() {
    if (!confirm('Refresh schema cache from database?')) return;
    
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refreshing...';
    
    fetch('index.php?page=ai_schema_browser&ajax=1&action=refresh_cache', {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Schema refreshed! Found ' + data.tables + ' tables.');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-refresh"></i> Refresh Cache';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Search on enter
document.getElementById('search-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') searchSchema();
});
</script>
