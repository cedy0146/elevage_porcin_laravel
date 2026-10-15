<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class ExpenseRecorded implements ShouldDispatchAfterCommit
{
    public function __construct(public Expense $expense) {}
}
