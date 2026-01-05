<?php
/**
 * Database Schema Discovery & Caching
 * 
 * Reads database structure and caches it for AI reference.
 * Run this when schema changes to update the AI's knowledge.
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-05
 */

class SchemaDiscovery
{
    private PDO $db;
    private string $cacheDir;
    private string $database;
    
    public function __construct(PDO $db, string $database = 'iacc')
    {
        $this->db = $db;
        $this->database = $database;
        $this->cacheDir = __DIR__ . '/../cache';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Discover all tables and their structures
     */
    public function discoverSchema(): array
    {
        $schema = [
            'database' => $this->database,
            'discovered_at' => date('Y-m-d H:i:s'),
            'tables' => [],
        ];
        
        // Get all tables
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            $schema['tables'][$table] = [
                'columns' => $this->getColumns($table),
                'indexes' => $this->getIndexes($table),
                'foreign_keys' => $this->getForeignKeys($table),
                'row_count' => $this->getRowCount($table),
                'sample_data' => $this->getSampleData($table, 3),
            ];
        }
        
        return $schema;
    }
    
    /**
     * Get all table names
     */
    private function getTables(): array
    {
        $sql = "SHOW TABLES";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get column information for a table
     */
    private function getColumns(string $table): array
    {
        $sql = "DESCRIBE `$table`";
        $stmt = $this->db->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($col) {
            return [
                'name' => $col['Field'],
                'type' => $col['Type'],
                'nullable' => $col['Null'] === 'YES',
                'key' => $col['Key'],
                'default' => $col['Default'],
                'extra' => $col['Extra'],
            ];
        }, $columns);
    }
    
    /**
     * Get indexes for a table
     */
    private function getIndexes(string $table): array
    {
        $sql = "SHOW INDEX FROM `$table`";
        $stmt = $this->db->query($sql);
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($indexes as $idx) {
            $name = $idx['Key_name'];
            if (!isset($result[$name])) {
                $result[$name] = [
                    'unique' => $idx['Non_unique'] == 0,
                    'columns' => [],
                ];
            }
            $result[$name]['columns'][] = $idx['Column_name'];
        }
        
        return $result;
    }
    
    /**
     * Get foreign key relationships
     */
    private function getForeignKeys(string $table): array
    {
        $sql = "SELECT 
                    COLUMN_NAME as column_name,
                    REFERENCED_TABLE_NAME as ref_table,
                    REFERENCED_COLUMN_NAME as ref_column
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->database, $table]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get approximate row count
     */
    private function getRowCount(string $table): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM `$table`";
            $stmt = $this->db->query($sql);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get sample data (first N rows)
     */
    private function getSampleData(string $table, int $limit = 3): array
    {
        try {
            $sql = "SELECT * FROM `$table` LIMIT $limit";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Generate a human-readable schema summary
     */
    public function generateSchemaSummary(): string
    {
        $schema = $this->discoverSchema();
        $output = "# iACC Database Schema\n";
        $output .= "Database: {$schema['database']}\n";
        $output .= "Discovered: {$schema['discovered_at']}\n\n";
        
        // Group tables by category
        $categories = [
            'Core Business' => ['iv', 'po', 'pr', 'product', 'pay', 'deliver', 'receive'],
            'Companies & Contacts' => ['company', 'company_contacts', 'company_addresses'],
            'Master Data' => ['category', 'brand', 'type', 'payment_method'],
            'Users & Auth' => ['users', 'user_levels', 'sessions'],
            'AI System' => ['ai_sessions', 'ai_conversations', 'ai_action_log'],
            'Other' => [],
        ];
        
        $categorized = [];
        foreach ($schema['tables'] as $table => $info) {
            $found = false;
            foreach ($categories as $cat => $tables) {
                if (in_array($table, $tables)) {
                    $categorized[$cat][$table] = $info;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $categorized['Other'][$table] = $info;
            }
        }
        
        foreach ($categorized as $category => $tables) {
            if (empty($tables)) continue;
            
            $output .= "## $category\n\n";
            
            foreach ($tables as $table => $info) {
                $output .= "### `$table` ({$info['row_count']} rows)\n";
                $output .= "| Column | Type | Key | Nullable |\n";
                $output .= "|--------|------|-----|----------|\n";
                
                foreach ($info['columns'] as $col) {
                    $key = $col['key'] ?: '-';
                    $nullable = $col['nullable'] ? 'YES' : 'NO';
                    $output .= "| {$col['name']} | {$col['type']} | $key | $nullable |\n";
                }
                
                // Show relationships
                if (!empty($info['foreign_keys'])) {
                    $output .= "\n**Relationships:**\n";
                    foreach ($info['foreign_keys'] as $fk) {
                        $output .= "- `{$fk['column_name']}` → `{$fk['ref_table']}.{$fk['ref_column']}`\n";
                    }
                }
                
                $output .= "\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Generate compact schema for system prompt (token-efficient)
     */
    public function generateCompactSchema(): string
    {
        $schema = $this->discoverSchema();
        $output = "DATABASE SCHEMA (key tables):\n\n";
        
        // Focus on most important tables for the AI
        $importantTables = [
            'iv' => 'Invoices - tex(PK, links to po.id), createdate, taxrw(invoice#), status_iv, payment_status, cus_id→company',
            'po' => 'Purchase Orders - id(PK), ref→pr.id, name, date, status, vat, dis(discount)',
            'pr' => 'Purchase Requests/Quotations - id(PK), name, date, cus_id→company(customer), ven_id→company(vendor)',
            'product' => 'Line items - po_id→po.id, price, quantity (total = SUM(price*quantity))',
            'pay' => 'Payments - po_id→po.id, volumn(amount paid)',
            'deliver' => 'Deliveries - po_id→po.id, deliver_date',
            'company' => 'Companies - id(PK), name_en, name_sh, email, phone, address',
        ];
        
        foreach ($importantTables as $table => $desc) {
            if (isset($schema['tables'][$table])) {
                $cols = array_column($schema['tables'][$table]['columns'], 'name');
                $output .= "• $table: $desc\n";
                $output .= "  Columns: " . implode(', ', $cols) . "\n\n";
            }
        }
        
        $output .= "KEY RELATIONSHIPS:\n";
        $output .= "• Invoice flow: pr(quote) → po(order) → iv(invoice) → pay(payment)\n";
        $output .= "• Company links: pr.ven_id = YOUR company, pr.cus_id = customer\n";
        $output .= "• Totals: JOIN product ON po.id=product.po_id, SUM(price*quantity)\n";
        $output .= "• Payments: JOIN pay ON po.id=pay.po_id, SUM(volumn)\n";
        
        return $output;
    }
    
    /**
     * Save schema to cache files
     */
    public function saveToCache(): array
    {
        $schema = $this->discoverSchema();
        $summary = $this->generateSchemaSummary();
        $compact = $this->generateCompactSchema();
        
        // Save full schema as JSON
        $jsonPath = $this->cacheDir . '/db-schema.json';
        file_put_contents($jsonPath, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Save human-readable summary
        $mdPath = $this->cacheDir . '/db-schema.md';
        file_put_contents($mdPath, $summary);
        
        // Save compact version for system prompt
        $compactPath = $this->cacheDir . '/db-schema-compact.txt';
        file_put_contents($compactPath, $compact);
        
        return [
            'json' => $jsonPath,
            'markdown' => $mdPath,
            'compact' => $compactPath,
            'tables_count' => count($schema['tables']),
        ];
    }
    
    /**
     * Load cached compact schema
     */
    public static function loadCompactSchema(): ?string
    {
        $path = __DIR__ . '/../cache/db-schema-compact.txt';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }
    
    /**
     * Load full cached schema
     */
    public static function loadFullSchema(): ?array
    {
        $path = __DIR__ . '/../cache/db-schema.json';
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return null;
    }
    
    /**
     * Get table info for a specific table
     */
    public function getTableInfo(string $tableName): ?array
    {
        $tables = $this->getTables();
        if (!in_array($tableName, $tables)) {
            return null;
        }
        
        return [
            'name' => $tableName,
            'columns' => $this->getColumns($tableName),
            'indexes' => $this->getIndexes($tableName),
            'foreign_keys' => $this->getForeignKeys($tableName),
            'row_count' => $this->getRowCount($tableName),
            'sample_data' => $this->getSampleData($tableName, 5),
        ];
    }
    
    /**
     * Search for tables/columns matching a pattern
     */
    public function searchSchema(string $query): array
    {
        $schema = $this->discoverSchema();
        $results = [
            'tables' => [],
            'columns' => [],
        ];
        
        $query = strtolower($query);
        
        foreach ($schema['tables'] as $table => $info) {
            // Match table names
            if (strpos(strtolower($table), $query) !== false) {
                $results['tables'][] = $table;
            }
            
            // Match column names
            foreach ($info['columns'] as $col) {
                if (strpos(strtolower($col['name']), $query) !== false) {
                    $results['columns'][] = [
                        'table' => $table,
                        'column' => $col['name'],
                        'type' => $col['type'],
                    ];
                }
            }
        }
        
        return $results;
    }
}

// CLI usage
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'schema-discovery.php') {
    require_once __DIR__ . '/../inc/class.dbconn.php';
    
    $pdo = new PDO('mysql:host=mysql;dbname=iacc;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $discovery = new SchemaDiscovery($pdo);
    
    $action = $argv[1] ?? 'cache';
    
    switch ($action) {
        case 'cache':
            echo "Discovering schema and caching...\n";
            $result = $discovery->saveToCache();
            echo "Done! Cached to:\n";
            echo "- JSON: {$result['json']}\n";
            echo "- Markdown: {$result['markdown']}\n";
            echo "- Compact: {$result['compact']}\n";
            echo "Tables found: {$result['tables_count']}\n";
            break;
            
        case 'table':
            $table = $argv[2] ?? null;
            if (!$table) {
                echo "Usage: php schema-discovery.php table <table_name>\n";
                exit(1);
            }
            $info = $discovery->getTableInfo($table);
            print_r($info);
            break;
            
        case 'search':
            $query = $argv[2] ?? null;
            if (!$query) {
                echo "Usage: php schema-discovery.php search <query>\n";
                exit(1);
            }
            $results = $discovery->searchSchema($query);
            print_r($results);
            break;
            
        case 'summary':
            echo $discovery->generateSchemaSummary();
            break;
            
        case 'compact':
            echo $discovery->generateCompactSchema();
            break;
            
        default:
            echo "Usage: php schema-discovery.php [cache|table|search|summary|compact]\n";
    }
}
