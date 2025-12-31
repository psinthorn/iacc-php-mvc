<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Location;

class LocationRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Location());
    }
    public function getByWarehouse($warehouseId) {
        return $this->where('warehouse_id', $warehouseId);
    }
}
