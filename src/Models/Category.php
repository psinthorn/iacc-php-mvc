<?php namespace App\Models;

class Category extends Model {
    protected $table = 'category';
    protected $fillable = ['name', 'code', 'description'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    public function products() { return $this->hasMany(Product::class, 'category_id'); }
}
