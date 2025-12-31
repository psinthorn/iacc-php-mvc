<?php

namespace App\Models;

/**
 * Product Model
 * 
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $category_id
 * @property int $type_id
 * @property int $brand_id
 * @property float $unit_price
 * @property float $cost_price
 * @property string $unit
 * @property string $description
 * @property int $status
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Product extends Model
{
    protected $table = 'product';
    protected $fillable = ['name', 'code', 'category_id', 'type_id', 'brand_id', 'unit_price', 'cost_price', 'unit', 'description', 'status'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'category_id' => 'int', 'type_id' => 'int', 'brand_id' => 'int', 'unit_price' => 'float', 'cost_price' => 'float', 'status' => 'int'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_id');
    }
}
