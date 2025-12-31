<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\ReceiveItem;

class ReceiveItemRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new ReceiveItem());
    }
    public function getByOrder($poId) {
        return $this->where('po_id', $poId);
    }
}
