<?php namespace App\Models;

class Supplier extends Model {
    protected $table = 'supplier';
    protected $fillable = ['company_id', 'name', 'code', 'contact_person', 'email', 'phone', 'address'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'company_id' => 'int'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
}
