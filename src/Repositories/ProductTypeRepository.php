<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\ProductType;

class ProductTypeRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new ProductType());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
}
