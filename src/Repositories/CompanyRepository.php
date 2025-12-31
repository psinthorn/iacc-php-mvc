<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Company;

class CompanyRepository extends Repository {
    protected $model;
    public function __construct(Database $database) {
        parent::__construct($database, new Company());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
    public function getActiveCompanies() {
        return array_filter($this->all(), fn($c) => $c->status == 1);
    }
}
