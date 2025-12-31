<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Stock;

class StockRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Stock());
    }
    public function getByProduct($productId) {
        return $this->where('product_id', $productId);
    }
    public function getByWarehouse($warehouseId) {
        return $this->where('warehouse_id', $warehouseId);
    }
    public function getLowStock($threshold = 10) {
        $all = $this->all();
        return array_filter($all, fn($s) => $s->quantity < $threshold);
    }
}
