<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\ExpenseDetail;

class ExpenseDetailRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new ExpenseDetail());
    }
    public function getByExpense($expenseId) {
        return $this->where('expense_id', $expenseId);
    }
}
