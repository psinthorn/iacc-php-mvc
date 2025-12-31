<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Product;

class ProductRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Product());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
    public function getByCategory($categoryId) {
        return $this->where('category_id', $categoryId);
    }
    public function getByType($typeId) {
        return $this->where('type_id', $typeId);
    }
}
