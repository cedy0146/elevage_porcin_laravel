<?php

namespace App\Listeners;

use App\Events\StockCritical;
use App\Models\User;
use App\Notifications\StockCriticalNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Envoie l'alerte de stock critique aux managers ACTIFS de la MÊME exploitation
 * que l'aliment concerné, et à personne d'autre.
 */
class NotifyManagersOfCriticalStock implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array|int $backoff = 10;

    public function handle(StockCritical $event): void
    {
        User::query()
            ->where('farm_id', $event->feedType->farm_id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->get()
            ->each(function (User $manager) use ($event): void {
                $manager->notify(new StockCriticalNotification($event->feedType));
            });
    }
}
