<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\VoucherDetail;

class VoucherDetailRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new VoucherDetail());
    }
    public function getByVoucher($voucherId) {
        return $this->where('voucher_id', $voucherId);
    }
}
