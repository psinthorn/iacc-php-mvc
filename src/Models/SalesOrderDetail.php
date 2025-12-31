<?php namespace App\Models;

class SalesOrderDetail extends Model {
    protected $table = 'sales_order_detail';
    protected $fillable = ['so_id', 'product_id', 'quantity', 'unit_price', 'amount', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'so_id' => 'int', 'product_id' => 'int', 'quantity' => 'float', 'unit_price' => 'float', 'amount' => 'float'];
    public function order() { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
}
