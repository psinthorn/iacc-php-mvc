<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Supplier;

class SupplierRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Supplier());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
    public function getByCompany($companyId) {
        return $this->where('company_id', $companyId);
    }
}
