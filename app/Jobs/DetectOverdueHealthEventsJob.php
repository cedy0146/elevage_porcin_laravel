<?php

namespace App\Jobs;

use App\Events\VaccinationOverdue;
use App\Models\HealthEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Détecte chaque jour les actes sanitaires en retard (sans global scope :
 * aucun utilisateur connecté dans un job) et déclenche l'alerte une seule
 * fois par acte grâce à overdue_notified_at.
 */
class DetectOverdueHealthEventsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function handle(): void
    {
        HealthEvent::query()
            ->withoutGlobalScopes()
            ->overdue()
            ->with(['pigLot:id,code,farm_id', 'vaccineSchedule:id,tolerance_days'])
            ->each(function (HealthEvent $event): void {
                event(new VaccinationOverdue($event));
            });
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('business')->error('Echec de detection des actes sanitaires en retard', [
            'error' => $exception->getMessage(),
        ]);
    }
}
