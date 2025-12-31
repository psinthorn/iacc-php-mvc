<?php namespace App\Models;

class Delivery extends Model {
    protected $table = 'delivery';
    protected $fillable = ['delivery_number', 'delivery_date', 'so_id', 'customer_id', 'destination', 'status', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'so_id' => 'int', 'customer_id' => 'int'];
    public function salesOrder() { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function details() { return $this->hasMany(DeliveryDetail::class, 'delivery_id'); }
}
