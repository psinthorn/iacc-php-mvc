<?php namespace App\Models;

class ExpenseDetail extends Model {
    protected $table = 'expense_detail';
    protected $fillable = ['expense_id', 'description', 'amount', 'notes'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'expense_id' => 'int', 'amount' => 'float'];
    public function expense() { return $this->belongsTo(Expense::class, 'expense_id'); }
}
