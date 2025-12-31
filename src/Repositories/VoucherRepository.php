<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Voucher;

class VoucherRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Voucher());
    }
    public function getPendingVouchers() {
        return $this->where('status', 'pending');
    }
    public function findByNumber($voucherNumber) {
        return $this->findBy('voucher_number', $voucherNumber);
    }
}
