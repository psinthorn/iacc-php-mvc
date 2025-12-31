<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\PurchaseOrder;

class PurchaseOrderRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new PurchaseOrder());
    }
    public function getPendingOrders() {
        return $this->where('status', 'pending');
    }
    public function findByNumber($poNumber) {
        return $this->findBy('po_number', $poNumber);
    }
}
