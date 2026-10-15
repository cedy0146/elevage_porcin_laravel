<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\StockMovementType;
use App\Events\StockCritical;
use App\Models\FeedType;
use App\Models\PigLot;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée unique de toutes les écritures sur feed_types.quantity.
 * Chaque mouvement est tracé dans stock_movements et la quantité disponible
 * ne peut jamais devenir négative (vérification sous verrou pessimiste).
 */
class StockService
{
    public function __construct(private FinanceService $finance) {}

    /**
     * Stock initial d'un aliment.
     */
    public function recordInitial(FeedType $feedType, float $quantity, User $user): StockMovement
    {
        return $this->applyMovement($feedType, StockMovementType::Initial, $quantity, $user);
    }

    /**
     * Entrée en stock (achat / réapprovisionnement).
     * Si un coût unitaire est fourni, la dépense associée est enregistrée dans la même transaction.
     */
    public function receive(
        FeedType $feedType,
        float $quantity,
        User $user,
        ?float $unitCost = null,
        ?string $reason = null,
    ): StockMovement {
        return DB::transaction(function () use ($feedType, $quantity, $user, $unitCost, $reason) {
            $movement = $this->applyMovement(
                $feedType,
                StockMovementType::Entry,
                $quantity,
                $user,
                unitCost: $unitCost,
                reason: $reason,
            );

            if ($unitCost !== null) {
                $this->finance->recordExpense(
                    farm: $feedType->farm,
                    category: ExpenseCategory::Feed,
                    amount: $quantity * $unitCost,
                    spentOn: now(),
                    user: $user,
                    description: "Achat de {$quantity} {$feedType->unit} de {$feedType->name}",
                );
            }

            return $movement;
        });
    }

    /**
     * Sortie hors lot (perte, don, rebut...).
     */
    public function issue(FeedType $feedType, float $quantity, User $user, string $reason): StockMovement
    {
        return $this->applyMovement($feedType, StockMovementType::Exit, -$quantity, $user, reason: $reason);
    }

    /**
     * Consommation d'aliment par un lot (distribution).
     */
    public function consume(FeedType $feedType, PigLot $lot, float $quantity, User $user): StockMovement
    {
        return $this->applyMovement($feedType, StockMovementType::Consumption, -$quantity, $user, pigLot: $lot);
    }

    /**
     * Ajustement d'inventaire : $countedQuantity est le nouveau comptage réel.
     * L'ancienne quantité est relue sous verrou dans applyMovement().
     */
    public function adjust(FeedType $feedType, float $countedQuantity, User $user, string $reason): StockMovement
    {
        return $this->applyMovement($feedType, StockMovementType::Adjustment, $countedQuantity, $user, reason: $reason);
    }

    /**
     * Point d'entrée privé unique de tous les mouvements de stock.
     *
     * @param  float  $value  quantité du mouvement, ou comptage réel pour un ajustement
     */
    private function applyMovement(
        FeedType $feedType,
        StockMovementType $type,
        float $value,
        User $user,
        ?float $unitCost = null,
        ?string $reason = null,
        ?PigLot $pigLot = null,
    ): StockMovement {
        return DB::transaction(function () use ($feedType, $type, $value, $user, $unitCost, $reason, $pigLot) {

            // 1. Charger et verrouiller le type d'aliment
            $locked = FeedType::query()
                ->lockForUpdate()
                ->findOrFail($feedType->id);

            // 2. Calculer la nouvelle quantite
            $before = round((float) $locked->quantity, 2);

            if ($type === StockMovementType::Adjustment) {
                $delta = round($value - $before, 2);

                if ($delta === 0.0) {
                    throw ValidationException::withMessages([
                        'quantity' => "Aucun écart constaté pour {$locked->name} : le comptage correspond au stock.",
                    ]);
                }
            } else {
                $delta = round($value, 2);

                $isIncrease = $type->increasesStock();

                if ($delta === 0.0 || ($isIncrease && $delta < 0) || (! $isIncrease && $delta > 0)) {
                    throw ValidationException::withMessages([
                        'quantity' => 'La quantité doit être supérieure à zéro.',
                    ]);
                }
            }

            $after = round($before + $delta, 2);

            // 3. Verifier que le stock ne devient jamais negatif
            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuffisant pour {$locked->name} : disponible {$before}, demande ".abs($delta),
                ]);
            }

            // 4. Creer le mouvement (quantity toujours positive, avec l'ancienne et la nouvelle quantite)
            $movement = new StockMovement;
            $movement->forceFill([
                'farm_id' => $locked->farm_id,
                'feed_type_id' => $locked->id,
                'pig_lot_id' => $pigLot?->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => abs($delta),
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $unitCost,
                'reason' => $reason,
                'moved_at' => now(),
            ])->save();

            // 5. Mettre a jour la quantite (seul endroit du projet ou le stock est modifie)
            $locked->forceFill(['quantity' => $after])->save();

            // 6. Evenement si le seuil minimum est franchi vers le bas
            if ($before > (float) $locked->minimum_threshold && $after <= (float) $locked->minimum_threshold) {
                event(new StockCritical($locked));
            }

            return $movement;
        });
    }
}
