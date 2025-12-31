<?php namespace App\Models;

class Warehouse extends Model {
    protected $table = 'warehouse';
    protected $fillable = ['name', 'code', 'location', 'description'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    public function locations() { return $this->hasMany(Location::class, 'warehouse_id'); }
}
