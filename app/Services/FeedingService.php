<?php

namespace App\Services;

use App\Enums\LotStatus;
use App\Events\FeedingRecorded;
use App\Models\Feeding;
use App\Models\FeedType;
use App\Models\PigLot;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedingService
{
    public function __construct(private StockService $stocks) {}

    /**
     * Enregistre une distribution d'aliment à un lot.
     *
     * Risque de concurrence : deux techniciens peuvent tenter en même temps
     * d'utiliser les derniers kilogrammes disponibles d'un aliment. Le code naïf
     * (lire le stock, comparer, décrémenter) laisse les deux opérations valider
     * la même quantité : le stock devient négatif. Ici, la transaction +
     * lockForUpdate + revérification de la quantité relue en base dans la
     * transaction empêchent ce scénario : le deuxième traitement attend le commit
     * du premier (T1 à T5 du TD 23), relit un stock déjà diminué, échoue et la
     * totalité de son traitement est annulée (rollback). Aucune distribution ni
     * aucun mouvement orphelin ne subsiste alors.
     */
    public function distribute(
        PigLot $lot,
        FeedType $feedType,
        float $quantity,
        User $user,
        ?CarbonInterface $fedOn = null,
        ?string $notes = null,
    ): Feeding {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La quantité doit être supérieure à zéro.',
            ]);
        }

        return DB::transaction(function () use ($lot, $feedType, $quantity, $user, $fedOn, $notes) {

            // 1. Verrouiller le lot et verifier qu'il est actif
            $lockedLot = PigLot::query()
                ->lockForUpdate()
                ->findOrFail($lot->id);

            if ($lockedLot->status !== LotStatus::Active) {
                throw ValidationException::withMessages([
                    'pig_lot_id' => 'Ce lot est clôturé : impossible d\'enregistrer une distribution.',
                ]);
            }

            // 2. Verifier que lot et aliment appartiennent a la meme exploitation
            if ($lockedLot->farm_id !== $feedType->farm_id) {
                throw ValidationException::withMessages([
                    'feed_type_id' => 'Cet aliment n\'appartient pas à l\'exploitation du lot.',
                ]);
            }

            // 3. Consommer le stock (verrou et verification de negativite dans StockService)
            $movement = $this->stocks->consume($feedType, $lockedLot, $quantity, $user);

            // 4. Creer la distribution (unit_cost, total_cost et mouvement lie)
            $unitCost = round((float) $feedType->unit_price, 2);

            $feeding = new Feeding;
            $feeding->forceFill([
                'farm_id' => $lockedLot->farm_id,
                'pig_lot_id' => $lockedLot->id,
                'feed_type_id' => $feedType->id,
                'user_id' => $user->id,
                'stock_movement_id' => $movement->id,
                'quantity' => round($quantity, 2),
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'fed_on' => ($fedOn ?? now())->toDateString(),
                'notes' => $notes,
            ])->save();

            // 5. Evenement FeedingRecorded (apres commit)
            event(new FeedingRecorded($feeding));

            return $feeding;
        });
    }
}
