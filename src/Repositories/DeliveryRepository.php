<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Delivery;

class DeliveryRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Delivery());
    }
    public function getPendingDeliveries() {
        return $this->where('status', 'pending');
    }
    public function getByCustomer($customerId) {
        return $this->where('customer_id', $customerId);
    }
}
