<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Expense;

class ExpenseRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Expense());
    }
    public function getPendingExpenses() {
        return $this->where('status', 'pending');
    }
    public function getApprovedExpenses() {
        return $this->where('status', 'approved');
    }
}
