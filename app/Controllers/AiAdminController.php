<?php
namespace App\Controllers;

/**
 * AiAdminController - AI Admin Panel pages
 * 
 * Handles: chat history, schema browser, action log, schema refresh
 * All pages require Super Admin (user_level >= 2)
 * Each method handles both AJAX (JSON API) and page rendering
 */
class AiAdminController extends BaseController
{
    /** @var \PDO Direct PDO connection for AI tables */
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        
        // Require super admin
        if (($this->user['level'] ?? 0) < 2) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Access denied']);
            }
            echo '<div class="alert alert-danger">Access denied. Super Admin required.</div>';
            return;
        }
        
        // Create PDO connection for AI queries
        $this->pdo = new \PDO(
            'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
            'root', 'root',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
        );
    }

    // ==================== Chat History ====================

    public function chatHistory(): void
    {
        if ($this->isAjax()) {
            $this->handleChatHistoryAjax();
            return;
        }
        $this->render('ai/chat-history');
    }

    private function handleChatHistoryAjax(): void
    {
        $companyId = $this->user['com_id'];
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'get_sessions':
                $sql = "SELECT session_id, MIN(created_at) as started_at, MAX(created_at) as last_message,
                        COUNT(*) as message_count, user_id
                        FROM ai_chat_history WHERE company_id = ?
                        GROUP BY session_id, user_id ORDER BY MAX(created_at) DESC LIMIT 50";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$companyId]);
                $this->json(['success' => true, 'sessions' => $stmt->fetchAll()]);

            case 'get_messages':
                $sessionId = $_GET['session_id'] ?? '';
                $sql = "SELECT * FROM ai_chat_history WHERE session_id = ? AND company_id = ? ORDER BY created_at ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$sessionId, $companyId]);
                $this->json(['success' => true, 'messages' => $stmt->fetchAll()]);

            case 'delete_session':
                $sessionId = $_POST['session_id'] ?? '';
                $sql = "DELETE FROM ai_chat_history WHERE session_id = ? AND company_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$sessionId, $companyId]);
                $this->json(['success' => true, 'deleted' => $stmt->rowCount()]);

            default:
                $this->json(['success' => false, 'error' => 'Unknown action']);
        }
    }

    // ==================== Schema Browser ====================

    public function schemaBrowser(): void
    {
        if ($this->isAjax()) {
            $this->handleSchemaBrowserAjax();
            return;
        }

        require_once __DIR__ . '/../../ai/schema-discovery.php';
        $cachedSchema = \SchemaDiscovery::loadFullSchema();
        $tables = $cachedSchema['tables'] ?? [];
        $discoveredAt = $cachedSchema['discovered_at'] ?? 'Never';

        $this->render('ai/schema-browser', compact('tables', 'discoveredAt', 'cachedSchema'));
    }

    private function handleSchemaBrowserAjax(): void
    {
        require_once __DIR__ . '/../../ai/schema-discovery.php';
        $action = $_GET['action'] ?? '';
        $discovery = new \SchemaDiscovery($this->pdo);

        switch ($action) {
            case 'refresh_cache':
                $schema = $discovery->discoverSchema();
                $discovery->saveToCache();
                $this->json(['success' => true, 'tables' => count($schema['tables'])]);

            case 'get_table':
                $tableName = $_GET['table'] ?? '';
                $info = $discovery->getTableInfo($tableName);
                $this->json(['success' => true, 'table' => $info]);

            case 'search':
                $pattern = $_GET['pattern'] ?? '';
                $results = $discovery->searchSchema($pattern);
                $this->json(['success' => true, 'results' => $results]);

            case 'get_summary':
                $compact = \SchemaDiscovery::loadCompactSchema();
                $full = \SchemaDiscovery::loadFullSchema();
                $this->json([
                    'success' => true,
                    'compact' => $compact,
                    'tables' => $full ? count($full['tables']) : 0,
                    'cached_at' => $full['discovered_at'] ?? null
                ]);

            default:
                $this->json(['success' => false, 'error' => 'Unknown action']);
        }
    }

    // ==================== Action Log ====================

    public function actionLog(): void
    {
        if ($this->isAjax()) {
            $this->handleActionLogAjax();
            return;
        }
        $this->render('ai/action-log');
    }

    private function handleActionLogAjax(): void
    {
        $companyId = $this->user['com_id'];
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

                $countStmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM ai_action_log $where");
                $countStmt->execute($bindings);
                $total = $countStmt->fetch()['cnt'];

                $stmt = $this->pdo->prepare("SELECT * FROM ai_action_log $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
                $stmt->execute($bindings);

                $this->json([
                    'success' => true,
                    'logs' => $stmt->fetchAll(),
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $limit)
                ]);

            case 'get_stats':
                $sql = "SELECT COUNT(*) as total, SUM(status = 'executed') as executed,
                        SUM(status = 'failed') as failed, SUM(status = 'pending') as pending,
                        COUNT(DISTINCT action_type) as unique_tools, COUNT(DISTINCT session_id) as sessions
                        FROM ai_action_log WHERE company_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$companyId]);
                $stats = $stmt->fetch();

                $topStmt = $this->pdo->prepare(
                    "SELECT action_type, COUNT(*) as cnt FROM ai_action_log WHERE company_id = ? GROUP BY action_type ORDER BY cnt DESC LIMIT 5"
                );
                $topStmt->execute([$companyId]);

                $this->json(['success' => true, 'stats' => $stats, 'top_tools' => $topStmt->fetchAll()]);

            case 'get_tools':
                $stmt = $this->pdo->prepare("SELECT DISTINCT action_type FROM ai_action_log WHERE company_id = ? ORDER BY action_type");
                $stmt->execute([$companyId]);
                $this->json(['success' => true, 'tools' => $stmt->fetchAll(\PDO::FETCH_COLUMN)]);

            default:
                $this->json(['success' => false, 'error' => 'Unknown action']);
        }
    }

    // ==================== Schema Refresh ====================

    public function schemaRefresh(): void
    {
        if ($this->isAjax()) {
            $this->handleSchemaRefreshAjax();
            return;
        }

        require_once __DIR__ . '/../../ai/schema-discovery.php';
        $cached = \SchemaDiscovery::loadFullSchema();
        $autoRefresh = $this->getAutoRefreshSetting();

        $this->render('ai/schema-refresh', compact('cached', 'autoRefresh'));
    }

    private function handleSchemaRefreshAjax(): void
    {
        require_once __DIR__ . '/../../ai/schema-discovery.php';
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'refresh':
                try {
                    $discovery = new \SchemaDiscovery($this->pdo);
                    $schema = $discovery->discoverSchema();
                    $discovery->saveToCache();

                    $logSql = "INSERT INTO ai_action_log (company_id, user_id, session_id, action_type, action_params, status, created_at)
                               VALUES (?, ?, 'system', 'schema_refresh', ?, 'executed', NOW())";
                    $this->pdo->prepare($logSql)->execute([
                        $this->user['com_id'], $this->user['id'],
                        json_encode(['tables' => count($schema['tables']), 'trigger' => $_GET['trigger'] ?? 'manual'])
                    ]);

                    $this->json(['success' => true, 'tables' => count($schema['tables']), 'cached_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $this->json(['success' => false, 'error' => $e->getMessage()]);
                }

            case 'status':
                $cached = \SchemaDiscovery::loadFullSchema();
                $autoRefresh = $this->getAutoRefreshSetting();
                $hashFile = __DIR__ . '/../../cache/db-schema-hash.txt';
                $lastHash = file_exists($hashFile) ? file_get_contents($hashFile) : null;
                $currentHash = $this->calculateSchemaHash();
                $this->json([
                    'success' => true,
                    'cached' => $cached ? true : false,
                    'cached_at' => $cached['discovered_at'] ?? null,
                    'tables' => $cached ? count($cached['tables']) : 0,
                    'auto_refresh' => $autoRefresh,
                    'schema_changed' => $lastHash && $currentHash !== $lastHash,
                    'current_hash' => substr($currentHash, 0, 12),
                    'last_hash' => $lastHash ? substr($lastHash, 0, 12) : null
                ]);

            case 'check_changes':
                $hashFile = __DIR__ . '/../../cache/db-schema-hash.txt';
                $lastHash = file_exists($hashFile) ? file_get_contents($hashFile) : null;
                $currentHash = $this->calculateSchemaHash();
                $changed = !$lastHash || $currentHash !== $lastHash;
                $autoRefresh = $this->getAutoRefreshSetting();
                $refreshed = false;

                if ($changed && $autoRefresh) {
                    $discovery = new \SchemaDiscovery($this->pdo);
                    $schema = $discovery->discoverSchema();
                    $discovery->saveToCache();
                    file_put_contents($hashFile, $currentHash);
                    $refreshed = true;
                }

                $this->json(['success' => true, 'changed' => $changed, 'refreshed' => $refreshed, 'auto_refresh' => $autoRefresh]);

            case 'set_auto_refresh':
                $enabled = ($_POST['enabled'] ?? '0') === '1';
                $this->setAutoRefreshSetting($enabled);
                $this->json(['success' => true, 'enabled' => $enabled]);

            case 'save_hash':
                $hashFile = __DIR__ . '/../../cache/db-schema-hash.txt';
                $currentHash = $this->calculateSchemaHash();
                file_put_contents($hashFile, $currentHash);
                $this->json(['success' => true, 'hash' => substr($currentHash, 0, 12)]);

            case 'get_migrations':
                try {
                    $stmt = $this->pdo->query("SELECT * FROM _migration_log ORDER BY executed_at DESC LIMIT 20");
                    $this->json(['success' => true, 'migrations' => $stmt->fetchAll()]);
                } catch (\Exception $e) {
                    $this->json(['success' => true, 'migrations' => [], 'note' => 'No migration table']);
                }

            default:
                $this->json(['success' => false, 'error' => 'Unknown action']);
        }
    }

    // ==================== Helper Methods ====================

    private function isAjax(): bool
    {
        return isset($_GET['ajax']);
    }

    private function calculateSchemaHash(): string
    {
        $sql = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_KEY 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                ORDER BY TABLE_NAME, ORDINAL_POSITION";
        $stmt = $this->pdo->query($sql);
        return md5(json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    private function getAutoRefreshSetting(): bool
    {
        try {
            $stmt = $this->pdo->query("SELECT setting_value FROM ai_settings WHERE setting_key = 'schema_auto_refresh' LIMIT 1");
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result && $result['setting_value'] === '1';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function setAutoRefreshSetting(bool $enabled): void
    {
        $sql = "INSERT INTO ai_settings (setting_key, setting_value, updated_at) 
                VALUES ('schema_auto_refresh', ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
        $value = $enabled ? '1' : '0';
        $this->pdo->prepare($sql)->execute([$value, $value]);
    }
}
