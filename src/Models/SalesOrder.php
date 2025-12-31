<?php namespace App\Models;

class SalesOrder extends Model {
    protected $table = 'sales_order';
    protected $fillable = ['so_number', 'so_date', 'customer_id', 'total_amount', 'status', 'delivery_date', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'customer_id' => 'int', 'total_amount' => 'float'];
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function details() { return $this->hasMany(SalesOrderDetail::class, 'so_id'); }
    public function invoices() { return $this->hasMany(Invoice::class, 'so_id'); }
}
