<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Report;

class ReportRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Report());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
    public function getActiveReports() {
        return array_filter($this->all(), fn($r) => $r->status == 1);
    }
}
