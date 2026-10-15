<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\LotStatus;
use App\Events\ExpenseRecorded;
use App\Events\SaleRecorded;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\PigLot;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    /**
     * Enregistre une dépense simple de l'exploitation (avec lot facultatif).
     *
     * Les achats d'aliment au niveau de l'exploitation ne sont pas liés
     * à un lot : ils ne sont jamais recomptés dans la marge d'un lot
     * (celle-ci utilise uniquement Feeding.total_cost).
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

        event(new ExpenseRecorded($expense));

        return $expense;
    }

    /**
     * Enregistre une vente issue d'un lot (transaction + verrou).
     *
     * Quand la vente vide le lot, celui-ci est clôturé automatiquement.
     */
    public function recordSale(
        PigLot $lot,
        int $headCount,
        float $totalWeight,
        float $pricePerKg,
        string $customerName,
        User $user,
        CarbonInterface $soldOn,
    ): Sale {
        $sale = DB::transaction(function () use ($lot, $headCount, $totalWeight, $pricePerKg, $customerName, $user, $soldOn) {

            // 1. Charger et verrouiller le lot
            $locked = PigLot::query()
                ->lockForUpdate()
                ->findOrFail($lot->id);

            // 2. Verifier que le lot est actif
            if ($locked->status !== LotStatus::Active) {
                throw ValidationException::withMessages([
                    'status' => 'Ce lot est clôturé : impossible d\'enregistrer une vente.',
                ]);
            }

            // 3. Verifier les quantites
            if ($headCount <= 0) {
                throw ValidationException::withMessages([
                    'head_count' => 'Le nombre de têtes vendues doit être supérieur à zéro.',
                ]);
            }

            if ($headCount > $locked->currentCount) {
                throw ValidationException::withMessages([
                    'head_count' => 'Le nombre de têtes vendues dépasse l\'effectif du lot.',
                ]);
            }

            // 4. Creer la vente (forceFill : user_id et farm_id non mass-assignables)
            $sale = new Sale;
            $sale->forceFill([
                'farm_id' => $locked->farm_id,
                'pig_lot_id' => $locked->id,
                'user_id' => $user->id,
                'customer_name' => $customerName,
                'head_count' => $headCount,
                'total_weight' => round($totalWeight, 2),
                'price_per_kg' => round($pricePerKg, 2),
                'amount' => round($totalWeight * $pricePerKg, 2),
                'sold_on' => $soldOn->toDateString(),
            ])->save();

            // 5. Evenement de mise a jour du tableau de bord
            event(new SaleRecorded($sale));

            // 6. Cloturer le lot s'il est vide
            if ($locked->fresh()->currentCount === 0) {
                app(LotService::class)->close($locked, $user);
            }

            return $sale;
        });

        return $sale;
    }

    /**
     * Résumé financier d'une exploitation (période facultative).
     *
     * @return array{revenues: float, expenses: float, margin: float}
     */
    public function farmSummary(Farm $farm, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $sales = Sale::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id);

        $expenses = Expense::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id);

        if ($from !== null) {
            $sales->where('sold_on', '>=', $from->toDateString());
            $expenses->where('spent_on', '>=', $from->toDateString());
        }

        if ($to !== null) {
            $sales->where('sold_on', '<=', $to->toDateString());
            $expenses->where('spent_on', '<=', $to->toDateString());
        }

        $revenues = round((float) $sales->sum('amount'), 2);
        $expensesTotal = round((float) $expenses->sum('amount'), 2);

        return [
            'revenues' => $revenues,
            'expenses' => $expensesTotal,
            'margin' => round($revenues - $expensesTotal, 2),
        ];
    }
}
