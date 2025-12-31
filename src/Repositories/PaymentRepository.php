<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Payment;

class PaymentRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Payment());
    }
    public function getByInvoice($invoiceId) {
        return $this->where('invoice_id', $invoiceId);
    }
}
