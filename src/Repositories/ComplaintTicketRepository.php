<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\ComplaintTicket;

class ComplaintTicketRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new ComplaintTicket());
    }
    public function getOpenComplaints() {
        return array_filter($this->all(), fn($c) => in_array($c->status, ['open', 'pending']));
    }
    public function getByCustomer($customerId) {
        return $this->where('customer_id', $customerId);
    }
}
