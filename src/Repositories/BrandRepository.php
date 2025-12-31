<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Brand;

class BrandRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Brand());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
}
