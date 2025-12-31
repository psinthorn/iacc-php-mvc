<?php namespace App\Models;

class InvoiceDetail extends Model {
    protected $table = 'invoice_detail';
    protected $fillable = ['invoice_id', 'product_id', 'quantity', 'unit_price', 'amount', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'invoice_id' => 'int', 'product_id' => 'int', 'quantity' => 'float', 'unit_price' => 'float', 'amount' => 'float'];
    public function invoice() { return $this->belongsTo(Invoice::class, 'invoice_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
