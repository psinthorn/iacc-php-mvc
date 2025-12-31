<?php namespace App\Models;

class StockMovement extends Model {
    protected $table = 'stock_movement';
    protected $fillable = ['product_id', 'warehouse_id', 'location_id', 'movement_type', 'quantity', 'reference_id', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'product_id' => 'int', 'warehouse_id' => 'int', 'location_id' => 'int', 'quantity' => 'float'];
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
