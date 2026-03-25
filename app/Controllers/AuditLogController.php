<?php
namespace App\Controllers;

use App\Models\AuditLog;

/**
 * AuditLogController - System audit log viewer (Super Admin only)
 * Replaces: audit-log.php
 */
class AuditLogController extends BaseController
{
    private AuditLog $auditLog;

    public function __construct()
    {
        parent::__construct();
        $this->auditLog = new AuditLog();
    }

    public function index(): void
    {
        // Super Admin check
        $userLevel = intval($_SESSION['user_level'] ?? 0);
        if ($userLevel < 2) {
            $this->render('audit/denied');
            return;
        }

        $filterUser    = trim($this->input('user', ''));
        $filterAction  = trim($this->input('action', ''));
        $filterEntity  = trim($this->input('entity', ''));
        $filterDateFrom = trim($this->input('date_from', ''));
        $filterDateTo   = trim($this->input('date_to', ''));

        $filters = [];
        if (!empty($filterAction))   $filters['action']      = $filterAction;
        if (!empty($filterEntity))   $filters['entity_type']  = $filterEntity;
        if (!empty($filterDateFrom)) $filters['date_from']    = $filterDateFrom;
        if (!empty($filterDateTo))   $filters['date_to']      = $filterDateTo;

        $logs = $this->auditLog->getLogs(200, $filters);
        $logs = $this->auditLog->filterByUser($logs, $filterUser);

        $actionCounts = array_count_values(array_column($logs, 'action'));

        $this->render('audit/list', [
            'logs'           => $logs,
            'actionCounts'   => $actionCounts,
            'filterUser'     => $filterUser,
            'filterAction'   => $filterAction,
            'filterEntity'   => $filterEntity,
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo'   => $filterDateTo,
        ]);
    }
}
