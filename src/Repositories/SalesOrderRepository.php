<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\SalesOrder;

class SalesOrderRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new SalesOrder());
    }
    public function getPendingOrders() {
        return $this->where('status', 'pending');
    }
    public function findByNumber($soNumber) {
        return $this->findBy('so_number', $soNumber);
    }
    public function getByCustomer($customerId) {
        return $this->where('customer_id', $customerId);
    }
}
