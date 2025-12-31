<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\StockMovement;

class StockMovementRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new StockMovement());
    }
    public function getByProduct($productId) {
        return $this->where('product_id', $productId);
    }
}
