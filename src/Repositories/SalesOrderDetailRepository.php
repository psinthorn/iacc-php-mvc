<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\SalesOrderDetail;

class SalesOrderDetailRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new SalesOrderDetail());
    }
    public function getByOrder($soId) {
        return $this->where('so_id', $soId);
    }
}
