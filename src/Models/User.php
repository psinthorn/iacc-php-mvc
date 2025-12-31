<?php namespace App\Models;

class User extends Model {
    protected $table = 'user';
    protected $fillable = ['username', 'email', 'password', 'full_name', 'role', 'status', 'last_login'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    protected $hidden = ['password'];
}
