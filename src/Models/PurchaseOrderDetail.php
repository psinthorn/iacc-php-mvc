<?php namespace App\Models;

class PurchaseOrderDetail extends Model {
    protected $table = 'purchase_order_detail';
    protected $fillable = ['po_id', 'product_id', 'quantity', 'unit_price', 'amount', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'po_id' => 'int', 'product_id' => 'int', 'quantity' => 'float', 'unit_price' => 'float', 'amount' => 'float'];
    public function order() { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
