<?php namespace App\Models;

class Voucher extends Model {
    protected $table = 'voucher';
    protected $fillable = ['voucher_number', 'voucher_date', 'description', 'total_amount', 'status', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'total_amount' => 'float'];
    public function details() { return $this->hasMany(VoucherDetail::class, 'voucher_id'); }
}
