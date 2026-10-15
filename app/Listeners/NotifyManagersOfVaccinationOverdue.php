<?php

namespace App\Listeners;

use App\Events\VaccinationOverdue;
use App\Models\User;
use App\Notifications\VaccinationOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyManagersOfVaccinationOverdue implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * Notifie les managers ACTIFS de la même farm que l'événement sanitaire,
     * et personne d'autre (isolation SaaS). Marque overdue_notified_at pour
     * ne notifier qu'une seule fois par acte.
     */
    public function handle(VaccinationOverdue $event): void
    {
        $managers = User::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $event->event->farm_id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->get();

        foreach ($managers as $manager) {
            $manager->notify(new VaccinationOverdueNotification($event->event));
        }

        $event->event->forceFill(['overdue_notified_at' => now()])->save();
    }
}
