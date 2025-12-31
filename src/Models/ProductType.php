<?php

namespace App\Models;

/**
 * ProductType Model
 */
class ProductType extends Model
{
    protected $table = 'product_type';
    protected $fillable = ['name', 'code', 'description'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function products()
    {
        return $this->hasMany(Product::class, 'type_id');
    }
}
