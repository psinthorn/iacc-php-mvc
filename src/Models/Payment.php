<?php namespace App\Models;

class Payment extends Model {
    protected $table = 'payment';
    protected $fillable = ['invoice_id', 'amount', 'payment_date', 'payment_method', 'reference_number', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'invoice_id' => 'int', 'amount' => 'float'];
    public function invoice() { return $this->belongsTo(Invoice::class, 'invoice_id'); }
}
