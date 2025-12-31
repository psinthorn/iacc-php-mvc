<?php namespace App\Models;

class PurchaseOrder extends Model {
    protected $table = 'purchase_order';
    protected $fillable = ['po_number', 'po_date', 'company_id', 'supplier_id', 'total_amount', 'status', 'delivery_date', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'company_id' => 'int', 'supplier_id' => 'int', 'total_amount' => 'float'];
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function details() { return $this->hasMany(PurchaseOrderDetail::class, 'po_id'); }
}
