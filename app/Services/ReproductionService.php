<?php

namespace App\Services;

use App\Enums\BreederSex;
use App\Enums\BreederStatus;
use App\Enums\MatingStatus;
use App\Models\Breeder;
use App\Models\Farrowing;
use App\Models\Mating;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Module reproduction : saillies, mises bas, sevrages.
 * Toutes les écritures passent par des transactions avec verrou pessimiste
 * sur la truie : deux saisies simultanées ne peuvent pas créer deux saillies.
 */
class ReproductionService
{
    public function __construct(private LotService $lots) {}

    /**
     * Enregistre une saillie et met la truie en « pregnant ».
     */
    public function registerMating(
        Breeder $sow,
        ?Breeder $boar,
        CarbonInterface $matedOn,
        User $user,
    ): Mating {
        return DB::transaction(function () use ($sow, $boar, $matedOn, $user) {

            // 1. Charger et verrouiller la truie
            $lockedSow = Breeder::query()
                ->lockForUpdate()
                ->findOrFail($sow->id);

            // 2. Verifier les sexes
            if ($lockedSow->sex !== BreederSex::Sow) {
                throw ValidationException::withMessages([
                    'sow_id' => 'Le sujet de la saillie doit être une truie.',
                ]);
            }

            if ($boar !== null && $boar->sex !== BreederSex::Boar) {
                throw ValidationException::withMessages([
                    'boar_id' => 'Le partenaire de la saillie doit être un verrat.',
                ]);
            }

            if ($boar !== null && $boar->farm_id !== $lockedSow->farm_id) {
                throw ValidationException::withMessages([
                    'boar_id' => 'Le verrat appartient à une autre exploitation.',
                ]);
            }

            // 3. Verifier le statut de la truie
            if ($lockedSow->status !== BreederStatus::Active) {
                throw ValidationException::withMessages([
                    'sow_id' => 'Cette truie n\'est pas disponible (gestante, allaitante ou réformée).',
                ]);
            }

            // 4. Verifier qu'aucune saillie n'est en cours
            $inProgress = Mating::query()
                ->where('sow_id', $lockedSow->id)
                ->whereIn('status', [MatingStatus::Pending, MatingStatus::Confirmed])
                ->exists();

            if ($inProgress) {
                throw ValidationException::withMessages([
                    'sow_id' => 'Cette truie a déjà une saillie en cours.',
                ]);
            }

            // 5. Creer la saillie (gestation = config farm.gestation_days)
            $mating = new Mating;
            $mating->forceFill([
                'farm_id' => $lockedSow->farm_id,
                'sow_id' => $lockedSow->id,
                'boar_id' => $boar?->id,
                'user_id' => $user->id,
                'mated_on' => $matedOn->toDateString(),
                'expected_farrowing_on' => $matedOn->copy()->addDays((int) config('farm.gestation_days', 114))->toDateString(),
                'status' => MatingStatus::Pending,
            ])->save();

            // 6. La truie passe en gestante
            $lockedSow->forceFill(['status' => BreederStatus::Pregnant])->save();

            return $mating;
        });
    }

    /**
     * Déclare une saillie échouée : la truie redevient active.
     */
    public function markFailed(Mating $mating): Mating
    {
        return DB::transaction(function () use ($mating) {

            // 1. Charger et verrouiller la saillie
            $locked = Mating::query()
                ->lockForUpdate()
                ->findOrFail($mating->id);

            // 2. Verifier le statut
            if (! in_array($locked->status, [MatingStatus::Pending, MatingStatus::Confirmed], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Seule une saillie en cours peut être déclarée échouée.',
                ]);
            }

            // 3. Marquer comme echouee et liberer la truie
            $locked->forceFill(['status' => MatingStatus::Failed])->save();

            $sow = Breeder::query()->lockForUpdate()->findOrFail($locked->sow_id);
            $sow->forceFill(['status' => BreederStatus::Active])->save();

            return $locked;
        });
    }

    /**
     * Enregistre une mise bas (contrôles de cohérence de dates).
     */
    public function recordFarrowing(
        Mating $mating,
        CarbonInterface $farrowedOn,
        int $bornAlive,
        int $stillborn,
        User $user,
    ): Farrowing {
        return DB::transaction(function () use ($mating, $farrowedOn, $bornAlive, $stillborn, $user) {

            // 1. Charger et verrouiller la saillie
            $locked = Mating::query()
                ->lockForUpdate()
                ->findOrFail($mating->id);

            // 2. Verifier le statut
            if ($locked->status === MatingStatus::Farrowed) {
                throw ValidationException::withMessages([
                    'status' => 'Une mise bas est déjà enregistrée pour cette saillie.',
                ]);
            }

            if (in_array($locked->status, [MatingStatus::Failed], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Cette saillie a été déclarée échouée.',
                ]);
            }

            // 3. Verifier les dates : apres la saillie et dans la fenetre 105-125 jours
            if ($farrowedOn->lt($locked->mated_on)) {
                throw ValidationException::withMessages([
                    'farrowed_on' => 'La mise bas ne peut pas précéder la saillie.',
                ]);
            }

            $days = (int) $locked->mated_on->copy()->startOfDay()->diffInDays($farrowedOn->copy()->startOfDay());

            if ($days < 105 || $days > 125) {
                throw ValidationException::withMessages([
                    'farrowed_on' => "La mise bas doit se situer entre 105 et 125 jours après la saillie (constaté : {$days} jours).",
                ]);
            }

            if ($bornAlive <= 0) {
                throw ValidationException::withMessages([
                    'born_alive' => 'Le nombre de nés vivants doit être supérieur à zéro.',
                ]);
            }

            // 4. Creer la mise bas
            $farrowing = new Farrowing;
            $farrowing->forceFill([
                'farm_id' => $locked->farm_id,
                'mating_id' => $locked->id,
                'sow_id' => $locked->sow_id,
                'user_id' => $user->id,
                'farrowed_on' => $farrowedOn->toDateString(),
                'born_alive' => $bornAlive,
                'stillborn' => $stillborn,
            ])->save();

            // 5. Statuts : saillie farrowed, truie nursing
            $locked->forceFill(['status' => MatingStatus::Farrowed])->save();

            $sow = Breeder::query()->lockForUpdate()->findOrFail($locked->sow_id);
            $sow->forceFill(['status' => BreederStatus::Nursing])->save();

            return $farrowing;
        });
    }

    /**
     * Enregistre un sevrage ; crée éventuellement le lot de naissance.
     */
    public function recordWeaning(
        Farrowing $farrowing,
        int $weanedCount,
        CarbonInterface $weanedOn,
        ?int $penId,
        User $user,
    ): Farrowing {
        return DB::transaction(function () use ($farrowing, $weanedCount, $weanedOn, $penId, $user) {

            // 1. Charger et verrouiller la mise bas
            $locked = Farrowing::query()
                ->lockForUpdate()
                ->findOrFail($farrowing->id);

            // 2. Verifier qu'elle n'est pas deja sevree
            if ($locked->weaned_count !== null) {
                throw ValidationException::withMessages([
                    'weaned_count' => 'Ce sevrage est déjà enregistré.',
                ]);
            }

            // 3. Verifier les quantites et les dates
            if ($weanedCount <= 0) {
                throw ValidationException::withMessages([
                    'weaned_count' => 'Le nombre de porcelets sevrés doit être supérieur à zéro.',
                ]);
            }

            if ($weanedCount > $locked->born_alive) {
                throw ValidationException::withMessages([
                    'weaned_count' => 'Les porcelets sevrés ne peuvent pas dépasser les nés vivants.',
                ]);
            }

            if ($weanedOn->lte($locked->farrowed_on)) {
                throw ValidationException::withMessages([
                    'weaned_on' => 'Le sevrage doit être réalisé après la mise bas.',
                ]);
            }

            // 4. Mettre a jour la mise bas et rendre la truie disponible
            $locked->forceFill([
                'weaned_count' => $weanedCount,
                'weaned_on' => $weanedOn->toDateString(),
            ])->save();

            $sow = Breeder::query()->lockForUpdate()->findOrFail($locked->sow_id);
            $sow->forceFill(['status' => BreederStatus::Active])->save();

            // 5. Creer le lot de naissance si une loge est fournie
            if ($penId !== null) {
                $lot = $this->lots->open([
                    'pen_id' => $penId,
                    'code' => 'N-'.now()->format('Y').'-'.str_pad((string) $locked->id, 3, '0', STR_PAD_LEFT),
                    'source' => 'birth',
                    'initial_count' => $weanedCount,
                    'initial_average_weight' => 6.5,
                    'arrived_on' => $weanedOn->toDateString(),
                    'expected_end_on' => $weanedOn->copy()->addDays(90)->toDateString(),
                    'notes' => 'Porcelets sevrés de la truie '.$locked->sow?->tag.'.',
                ], $user);

                $locked->forceFill(['pig_lot_id' => $lot->id])->save();
            }

            return $locked->fresh();
        });
    }
}
