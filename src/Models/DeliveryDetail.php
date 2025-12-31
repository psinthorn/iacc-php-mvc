<?php namespace App\Models;

class DeliveryDetail extends Model {
    protected $table = 'delivery_detail';
    protected $fillable = ['delivery_id', 'product_id', 'quantity', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'delivery_id' => 'int', 'product_id' => 'int', 'quantity' => 'float'];
    public function delivery() { return $this->belongsTo(Delivery::class, 'delivery_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
