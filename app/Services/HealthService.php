<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\HealthEventStatus;
use App\Enums\HealthEventType;
use App\Enums\LotStatus;
use App\Models\HealthEvent;
use App\Models\PigLot;
use App\Models\User;
use App\Models\VaccineSchedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HealthService
{
    public function __construct(private FinanceService $finance) {}

    /**
     * Planifie un acte « todo » par vaccin actif du calendrier de la farm.
     * Appelé par LotService::open() dans la même transaction.
     */
    public function planForLot(PigLot $lot): void
    {
        $schedules = VaccineSchedule::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $lot->farm_id)
            ->where('is_active', true)
            ->get();

        $arrivedOn = $lot->arrived_on->copy();

        foreach ($schedules as $schedule) {
            $event = new HealthEvent;
            $event->forceFill([
                'farm_id' => $lot->farm_id,
                'pig_lot_id' => $lot->id,
                'vaccine_schedule_id' => $schedule->id,
                'user_id' => null,
                'type' => HealthEventType::Vaccination,
                'name' => $schedule->name,
                'scheduled_on' => $arrivedOn->copy()->addDays($schedule->target_age_days)->toDateString(),
                'status' => HealthEventStatus::Todo,
            ])->save();
        }
    }

    /**
     * Marque un acte sanitaire comme effectué (transaction + verrou).
     *
     * @return array{event: HealthEvent, lateness_days: int|null, within_tolerance: bool|null}
     */
    public function markDone(
        HealthEvent $event,
        User $user,
        CarbonInterface $doneOn,
        float $cost = 0,
    ): array {
        return DB::transaction(function () use ($event, $user, $doneOn, $cost) {

            // 1. Charger et verrouiller l'evenement
            $locked = HealthEvent::query()
                ->lockForUpdate()
                ->findOrFail($event->id);

            // 2. Verifier qu'il n'est pas deja fait
            if ($locked->status === HealthEventStatus::Done) {
                throw ValidationException::withMessages([
                    'status' => 'Cet acte sanitaire est déjà enregistré.',
                ]);
            }

            $lot = $locked->pigLot()->lockForUpdate()->firstOrFail();

            // 3. Verifier que le lot est actif
            if ($lot->status !== LotStatus::Active) {
                throw ValidationException::withMessages([
                    'pig_lot_id' => 'Ce lot est clôturé : impossible d\'enregistrer un acte sanitaire.',
                ]);
            }

            // 4. Verifier la coherence des dates
            if ($doneOn->lt($lot->arrived_on)) {
                throw ValidationException::withMessages([
                    'done_on' => 'La date de l\'acte ne peut pas précéder l\'arrivée du lot.',
                ]);
            }

            // 5. Marquer comme fait
            $locked->forceFill([
                'user_id' => $user->id,
                'done_on' => $doneOn->toDateString(),
                'status' => HealthEventStatus::Done,
                'cost' => round($cost, 2),
            ])->save();

            // 6. Depense associee (categorie Veterinary, liee au lot)
            if ($cost > 0) {
                $this->finance->recordExpense(
                    farm: $lot->farm,
                    category: ExpenseCategory::Veterinary,
                    amount: $cost,
                    spentOn: $doneOn,
                    user: $user,
                    pigLot: $lot,
                    description: $locked->name,
                );
            }

            // 7. Calcul du retard et de la tolerance
            $latenessDays = null;
            $withinTolerance = null;

            if ($locked->scheduled_on !== null) {
                $latenessDays = (int) $locked->scheduled_on->copy()->startOfDay()
                    ->diffInDays($doneOn->copy()->startOfDay());

                $tolerance = $locked->vaccineSchedule?->tolerance_days ?? 0;
                $withinTolerance = $latenessDays <= $tolerance;
            }

            return [
                'event' => $locked->fresh(),
                'lateness_days' => $latenessDays,
                'within_tolerance' => $withinTolerance,
            ];
        });
    }

    /**
     * Enregistre un soin libre (traitement, observation) sur un lot actif.
     */
    public function recordTreatment(
        PigLot $lot,
        string $name,
        CarbonInterface $doneOn,
        User $user,
        float $cost = 0,
        ?string $notes = null,
    ): HealthEvent {
        return DB::transaction(function () use ($lot, $name, $doneOn, $user, $cost, $notes) {

            // 1. Verifier et verrouiller le lot
            $lockedLot = PigLot::query()
                ->lockForUpdate()
                ->findOrFail($lot->id);

            if ($lockedLot->status !== LotStatus::Active) {
                throw ValidationException::withMessages([
                    'pig_lot_id' => 'Ce lot est clôturé : impossible d\'enregistrer un soin.',
                ]);
            }

            if ($doneOn->lt($lockedLot->arrived_on)) {
                throw ValidationException::withMessages([
                    'done_on' => 'La date du soin ne peut pas précéder l\'arrivée du lot.',
                ]);
            }

            // 2. Creer l'acte fait
            $event = new HealthEvent;
            $event->forceFill([
                'farm_id' => $lockedLot->farm_id,
                'pig_lot_id' => $lockedLot->id,
                'vaccine_schedule_id' => null,
                'user_id' => $user->id,
                'type' => HealthEventType::Treatment,
                'name' => $name,
                'scheduled_on' => $doneOn->toDateString(),
                'done_on' => $doneOn->toDateString(),
                'status' => HealthEventStatus::Done,
                'cost' => round($cost, 2),
                'notes' => $notes,
            ])->save();

            // 3. Depense associee
            if ($cost > 0) {
                $this->finance->recordExpense(
                    farm: $lockedLot->farm,
                    category: ExpenseCategory::Veterinary,
                    amount: $cost,
                    spentOn: $doneOn,
                    user: $user,
                    pigLot: $lockedLot,
                    description: $name,
                );
            }

            return $event;
        });
    }
}
