<?php namespace App\Models;

class Report extends Model {
    protected $table = 'report';
    protected $fillable = ['name', 'code', 'query', 'description', 'parameters', 'status'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
}
