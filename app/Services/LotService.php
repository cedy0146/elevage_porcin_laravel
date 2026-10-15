<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\LotStatus;
use App\Enums\PenStatus;
use App\Events\LotCompleted;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LotService
{
    public function __construct(
        private FinanceService $finance,
        private HealthService $health,
    ) {}

    /**
     * Ouvre un nouveau lot dans une loge.
     *
     * @param  array<string, mixed>  $data
     */
    public function open(array $data, User $user, ?float $purchaseCost = null): PigLot
    {
        return DB::transaction(function () use ($data, $user, $purchaseCost) {

            // 1. Charger et verrouiller la loge
            $pen = Pen::query()
                ->lockForUpdate()
                ->findOrFail($data['pen_id']);

            // 2. Verifier que la loge appartient a l'exploitation de l'utilisateur
            if ($user->farm_id !== null && $pen->farm_id !== $user->farm_id) {
                throw ValidationException::withMessages([
                    'pen_id' => 'Cette loge n\'appartient pas à votre exploitation.',
                ]);
            }

            // 3. Verifier que la loge est libre
            if ($pen->status !== PenStatus::Free) {
                throw ValidationException::withMessages([
                    'pen_id' => 'Cette loge est déjà occupée ou en maintenance.',
                ]);
            }

            // 4. Verifier la capacite de la loge
            if ((int) $data['initial_count'] > (int) $pen->capacity) {
                throw ValidationException::withMessages([
                    'initial_count' => "L'effectif dépasse la capacité de la loge ({$pen->capacity} places).",
                ]);
            }

            // 5. Verifier qu'aucun lot actif n'occupe deja la loge
            $occupied = PigLot::query()
                ->where('pen_id', $pen->id)
                ->active()
                ->exists();

            if ($occupied) {
                throw ValidationException::withMessages([
                    'pen_id' => 'Un lot est déjà actif dans cette loge.',
                ]);
            }

            // 6. Creer le lot (farm_id pris sur la loge)
            $lot = new PigLot;
            $lot->forceFill([
                'farm_id' => $pen->farm_id,
                'pen_id' => $pen->id,
                'code' => $data['code'],
                'source' => $data['source'] ?? 'purchase',
                'initial_count' => $data['initial_count'],
                'initial_average_weight' => $data['initial_average_weight'] ?? 0,
                'arrived_on' => $data['arrived_on'],
                'expected_end_on' => $data['expected_end_on'],
                'notes' => $data['notes'] ?? null,
                'status' => LotStatus::Active,
            ])->save();

            // 7. Passer la loge a occupee
            $pen->forceFill(['status' => PenStatus::Occupied])->save();

            // 8. Planifier les actes sanitaires du calendrier (meme transaction)
            $this->health->planForLot($lot);

            // 9. Enregistrer l'achat eventuel du lot
            if ($purchaseCost !== null && $purchaseCost > 0) {
                $this->finance->recordExpense(
                    farm: $pen->farm,
                    category: ExpenseCategory::Purchase,
                    amount: $purchaseCost,
                    spentOn: $lot->arrived_on,
                    user: $user,
                    pigLot: $lot,
                    description: "Achat du lot {$lot->code}",
                );
            }

            return $lot;
        });
    }

    /**
     * Clôture un lot et libère sa loge.
     */
    public function close(PigLot $lot, User $user): PigLot
    {
        return DB::transaction(function () use ($lot, $user) {

            // 1. Charger et verrouiller le lot
            $locked = PigLot::query()
                ->lockForUpdate()
                ->findOrFail($lot->id);

            // 2. Refuser une double clôture
            if ($locked->status !== LotStatus::Active) {
                throw ValidationException::withMessages([
                    'status' => 'Ce lot est déjà clôturé.',
                ]);
            }

            // 3. Marquer le lot comme termine
            $locked->forceFill([
                'status' => LotStatus::Completed,
                'closed_on' => now()->toDateString(),
            ])->save();

            // 4. Liberer la loge
            $pen = Pen::query()->findOrFail($locked->pen_id);
            $pen->forceFill(['status' => PenStatus::Free])->save();

            // 5. Evenement et journalisation
            event(new LotCompleted($locked));

            Log::channel('business')->info('Cloture de lot', [
                'lot_id' => $locked->id,
                'farm_id' => $locked->farm_id,
                'user_id' => $user->id,
            ]);

            return $locked;
        });
    }

    /**
     * Indicateurs de gestion simplifiés d'un lot.
     *
     * Formules (divisions par zéro renvoyant null) :
     * - mortality_rate : morts cumulées ÷ effectif initial × 100
     * - average_daily_gain (GMQ) : (dernier poids moyen − poids initial) ÷ âge en jours
     * - feed_conversion_ratio (IC) : aliment distribué ÷ gain total de poids,
     *   avec gain total = (dernier poids moyen − poids initial) × effectif courant
     * - feed_cost_per_kg_gain : coût de l'aliment distribué ÷ gain total
     * - revenue : somme des ventes du lot
     * - margin : ventes − coût de l'aliment distribué − dépenses directes du lot
     *   (les achats d'aliment au niveau de l'exploitation ne sont pas recomptés ici)
     *
     * @return array<string, float|int|null>
     */
    public function indicators(PigLot $lot): array
    {
        $initialCount = (int) $lot->initial_count;
        $currentCount = $lot->currentCount;
        $ageDays = $lot->ageInDays;

        $mortalityCount = $this->loaded($lot, 'mortalities', fn () => $lot->mortalities()->get())->sum('count');
        $sales = $this->loaded($lot, 'sales', fn () => $lot->sales()->get());

        $weighings = $this->loaded($lot, 'weighings', fn () => $lot->weighings()->get());
        $latestWeighing = $weighings
            ->sortByDesc(fn ($weighing) => [$weighing->weighed_on?->toDateString(), $weighing->id])
            ->first();
        $latestAverageWeight = $latestWeighing !== null ? round((float) $latestWeighing->average_weight, 2) : null;

        $feedings = $this->loaded($lot, 'feedings', fn () => $lot->feedings()->get());
        $totalFeedKg = round((float) $feedings->sum('quantity'), 2);
        $feedCost = round((float) $feedings->sum('total_cost'), 2);

        $expenses = $this->loaded($lot, 'expenses', fn () => $lot->expenses()->get());
        $directExpenses = round((float) $expenses->sum('amount'), 2);

        $revenue = round((float) $sales->sum('amount'), 2);

        $mortalityRate = $initialCount > 0
            ? round($mortalityCount / $initialCount * 100, 2)
            : null;

        $initialWeight = (float) $lot->initial_average_weight;
        $weightGain = $latestAverageWeight !== null ? $latestAverageWeight - $initialWeight : null;

        $averageDailyGain = ($weightGain !== null && $ageDays > 0)
            ? round($weightGain / $ageDays, 3)
            : null;

        $totalGain = ($weightGain !== null && $weightGain > 0)
            ? $weightGain * $currentCount
            : null;

        $feedConversionRatio = ($totalGain !== null && $totalGain > 0)
            ? round($totalFeedKg / $totalGain, 3)
            : null;

        $feedCostPerKgGain = ($totalGain !== null && $totalGain > 0)
            ? round($feedCost / $totalGain, 2)
            : null;

        return [
            'current_count' => $currentCount,
            'age_days' => $ageDays,
            'mortality_rate' => $mortalityRate,
            'latest_average_weight' => $latestAverageWeight,
            'total_feed_kg' => $totalFeedKg,
            'feed_cost' => $feedCost,
            'average_daily_gain' => $averageDailyGain,
            'feed_conversion_ratio' => $feedConversionRatio,
            'feed_cost_per_kg_gain' => $feedCostPerKgGain,
            'revenue' => $revenue,
            'margin' => round($revenue - $feedCost - $directExpenses, 2),
        ];
    }

    /**
     * Retourne la relation chargée en mémoire si elle existe, sinon la requête.
     *
     * @param  callable(): Collection  $fallback
     */
    private function loaded(PigLot $lot, string $relation, callable $fallback): Collection
    {
        return $lot->relationLoaded($relation) ? $lot->{$relation} : $fallback();
    }
}
