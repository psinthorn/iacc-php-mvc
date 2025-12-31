<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Warehouse;

class WarehouseRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Warehouse());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
}
