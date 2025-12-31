<?php namespace App\Models;

class Location extends Model {
    protected $table = 'location';
    protected $fillable = ['warehouse_id', 'name', 'code', 'capacity', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'warehouse_id' => 'int', 'capacity' => 'float'];
    public function warehouse() { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
}
