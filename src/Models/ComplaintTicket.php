<?php namespace App\Models;

class ComplaintTicket extends Model {
    protected $table = 'complaint_ticket';
    protected $fillable = ['ticket_number', 'customer_id', 'so_id', 'description', 'status', 'priority', 'resolution', 'resolved_date'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'customer_id' => 'int', 'so_id' => 'int'];
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function salesOrder() { return $this->belongsTo(SalesOrder::class, 'so_id'); }
}
