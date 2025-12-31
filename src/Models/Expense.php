<?php namespace App\Models;

class Expense extends Model {
    protected $table = 'expense';
    protected $fillable = ['expense_number', 'expense_date', 'description', 'total_amount', 'status', 'approved_by', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'total_amount' => 'float'];
    public function details() { return $this->hasMany(ExpenseDetail::class, 'expense_id'); }
}
