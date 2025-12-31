<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\PurchaseRequest;

class PurchaseRequestRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new PurchaseRequest());
    }
    public function getPendingRequests() {
        return $this->where('status', 'pending');
    }
}
