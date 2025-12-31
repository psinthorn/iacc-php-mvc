<?php namespace App\Models;

class Stock extends Model {
    protected $table = 'stock';
    protected $fillable = ['product_id', 'warehouse_id', 'location_id', 'quantity', 'last_updated'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'product_id' => 'int', 'warehouse_id' => 'int', 'location_id' => 'int', 'quantity' => 'float'];
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function location() { return $this->belongsTo(Location::class, 'location_id'); }
}
