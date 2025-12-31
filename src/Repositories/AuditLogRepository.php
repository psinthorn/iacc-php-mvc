<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\AuditLog;

class AuditLogRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new AuditLog());
    }
    public function getByTable($tableName) {
        return $this->where('table_name', $tableName);
    }
    public function getByRecord($tableName, $recordId) {
        $all = $this->getByTable($tableName);
        return array_filter($all, fn($a) => $a->record_id == $recordId);
    }
}
