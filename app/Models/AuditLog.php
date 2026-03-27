<?php
namespace App\Models;

/**
 * AuditLog Model - Reads audit log entries
 * Replaces: audit-log.php (data layer)
 */
class AuditLog extends BaseModel
{
    protected string $table = 'audit_log';
    protected bool $useCompanyFilter = false;

    public function getLogs(int $limit = 200, array $filters = []): array
    {
        require_once __DIR__ . '/../../inc/audit.php';
        return get_audit_logs($this->conn, $limit, $filters);
    }

    public function filterByUser(array $logs, string $email): array
    {
        if (empty($email)) return $logs;
        return array_filter($logs, fn($log) => stripos($log['user_email'], $email) !== false);
    }
}
