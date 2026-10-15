<?php

namespace App\Services;

use App\Enums\LotStatus;
use App\Events\MortalityRecorded;
use App\Models\Mortality;
use App\Models\PigLot;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MortalityService
{
    /**
     * Enregistre une mortalité dans un lot (transaction + verrou).
     */
    public function record(
        PigLot $lot,
        int $count,
        ?string $cause,
        User $user,
        CarbonInterface $diedOn,
    ): Mortality {
        return DB::transaction(function () use ($lot, $count, $cause, $user, $diedOn) {

            // 1. Charger et verrouiller le lot
            $locked = PigLot::query()
                ->lockForUpdate()
                ->findOrFail($lot->id);

            // 2. Verifier que le lot est actif
            if ($locked->status !== LotStatus::Active) {
                throw ValidationException::withMessages([
                    'status' => 'Ce lot est clôturé : impossible d\'enregistrer une mortalité.',
                ]);
            }

            // 3. Verifier le nombre de morts
            if ($count <= 0) {
                throw ValidationException::withMessages([
                    'count' => 'Le nombre de morts doit être supérieur à zéro.',
                ]);
            }

            if ($count > $locked->currentCount) {
                throw ValidationException::withMessages([
                    'count' => 'Le nombre de morts dépasse l\'effectif restant du lot.',
                ]);
            }

            // 4. Creer la mortalite (forceFill : user_id et farm_id non mass-assignables)
            $mortality = new Mortality;
            $mortality->forceFill([
                'farm_id' => $locked->farm_id,
                'pig_lot_id' => $locked->id,
                'user_id' => $user->id,
                'died_on' => $diedOn->toDateString(),
                'count' => $count,
                'cause' => $cause,
            ])->save();

            // 5. Evenement de mise a jour du tableau de bord
            event(new MortalityRecorded($mortality));

            // 6. Alerte si le taux de mortalite depasse le seuil
            $mortalityRate = $locked->mortalities()
                ->selectRaw('COALESCE(SUM(count), 0) as total')
                ->value('total');

            $mortalityRate = $locked->initial_count > 0
                ? $mortalityRate / $locked->initial_count * 100
                : 0;

            if ($mortalityRate > (float) config('farm.mortality_alert_rate')) {
                Log::channel('business')->warning('Taux de mortalite eleve', [
                    'lot_id' => $locked->id,
                    'farm_id' => $locked->farm_id,
                    'mortality_rate' => round($mortalityRate, 2),
                    'user_id' => $user->id,
                ]);
            }

            return $mortality;
        });
    }
}
