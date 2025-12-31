<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\InvoiceDetail;

class InvoiceDetailRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new InvoiceDetail());
    }
    public function getByInvoice($invoiceId) {
        return $this->where('invoice_id', $invoiceId);
    }
}
