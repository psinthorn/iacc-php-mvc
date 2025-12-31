<?php namespace App\Models;

class VoucherDetail extends Model {
    protected $table = 'voucher_detail';
    protected $fillable = ['voucher_id', 'account_code', 'description', 'debit', 'credit', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'voucher_id' => 'int', 'debit' => 'float', 'credit' => 'float'];
    public function voucher() { return $this->belongsTo(Voucher::class, 'voucher_id'); }
}
