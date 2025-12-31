<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Category;

class CategoryRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Category());
    }
    public function findByCode($code) {
        return $this->findBy('code', $code);
    }
}
