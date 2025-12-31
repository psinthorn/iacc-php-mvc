<?php namespace App\Models;

class Brand extends Model {
    protected $table = 'brand';
    protected $fillable = ['name', 'code', 'description'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    public function products() { return $this->hasMany(Product::class, 'brand_id'); }
}
