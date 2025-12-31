<?php namespace App\Models;

class AuditLog extends Model {
    protected $table = 'audit_log';
    protected $fillable = ['table_name', 'record_id', 'action', 'old_values', 'new_values', 'user_id', 'ip_address', 'user_agent'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
}
