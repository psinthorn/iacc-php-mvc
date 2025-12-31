<?php namespace App\Models;

class ReceiveItem extends Model {
    protected $table = 'receive_item';
    protected $fillable = ['po_id', 'product_id', 'quantity', 'received_date', 'remarks'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'po_id' => 'int', 'product_id' => 'int', 'quantity' => 'float'];
    public function order() { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
