<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Invoice;

class InvoiceRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Invoice());
    }
    public function getPendingPayments() {
        return $this->where('payment_status', 'pending');
    }
    public function findByNumber($invoiceNumber) {
        return $this->findBy('invoice_number', $invoiceNumber);
    }
    public function getByCustomer($customerId) {
        return $this->where('customer_id', $customerId);
    }
}
