<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\PurchaseOrderDetail;

class PurchaseOrderDetailRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new PurchaseOrderDetail());
    }
    public function getByOrder($poId) {
        return $this->where('po_id', $poId);
    }
}
