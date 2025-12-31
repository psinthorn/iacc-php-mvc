<?php namespace App\Models;

class Invoice extends Model {
    protected $table = 'invoice';
    protected $fillable = ['invoice_number', 'invoice_date', 'so_id', 'customer_id', 'total_amount', 'payment_status', 'due_date', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'so_id' => 'int', 'customer_id' => 'int', 'total_amount' => 'float'];
    public function salesOrder() { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function details() { return $this->hasMany(InvoiceDetail::class, 'invoice_id'); }
}
