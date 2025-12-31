<?php namespace App\Models;

class Customer extends Model {
    protected $table = 'customer';
    protected $fillable = ['company_id', 'name', 'code', 'email', 'phone', 'address', 'credit_limit'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'company_id' => 'int', 'credit_limit' => 'float'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function orders() { return $this->hasMany(SalesOrder::class, 'customer_id'); }
}
