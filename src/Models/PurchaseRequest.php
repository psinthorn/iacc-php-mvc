<?php namespace App\Models;

class PurchaseRequest extends Model {
    protected $table = 'purchase_request';
    protected $fillable = ['pr_number', 'pr_date', 'company_id', 'supplier_id', 'description', 'status', 'created_by'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'company_id' => 'int', 'supplier_id' => 'int'];
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
}
