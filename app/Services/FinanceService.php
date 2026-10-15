<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\PigLot;
use App\Models\User;
use Carbon\CarbonInterface;

class FinanceService
{
    /**
     * Enregistre une dépense simple de l'exploitation (avec lot facultatif).
     */
    public function recordExpense(
        Farm $farm,
        ExpenseCategory $category,
        float $amount,
        CarbonInterface $spentOn,
        User $user,
        ?PigLot $pigLot = null,
        ?string $description = null,
    ): Expense {
        $expense = new Expense;

        $expense->forceFill([
            'farm_id' => $farm->id,
            'pig_lot_id' => $pigLot?->id,
            'user_id' => $user->id,
            'category' => $category,
            'amount' => round($amount, 2),
            'spent_on' => $spentOn->toDateString(),
            'description' => $description,
        ])->save();

        return $expense;
    }
}
