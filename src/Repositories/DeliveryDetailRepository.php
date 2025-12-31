<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\DeliveryDetail;

class DeliveryDetailRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new DeliveryDetail());
    }
    public function getByDelivery($deliveryId) {
        return $this->where('delivery_id', $deliveryId);
    }
}
