<?php

namespace App\Jobs;

use App\Enums\MatingStatus;
use App\Models\Mating;
use App\Models\User;
use App\Notifications\FarrowingDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Chaque jour, notifie les managers des exploitations dont une truie est
 * attendue en mise bas dans moins de 7 jours. WithoutGlobalScopes : aucun
 * utilisateur connecté dans un job.
 */
class DetectUpcomingFarrowingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function handle(): void
    {
        $matings = Mating::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [MatingStatus::Pending, MatingStatus::Confirmed])
            ->whereBetween('expected_farrowing_on', [
                now()->toDateString(),
                now()->addDays(7)->toDateString(),
            ])
            ->with(['sow:id,tag,farm_id'])
            ->get();

        foreach ($matings as $mating) {
            $managers = User::query()
                ->withoutGlobalScopes()
                ->where('farm_id', $mating->farm_id)
                ->where('role', 'manager')
                ->where('is_active', true)
                ->get();

            foreach ($managers as $manager) {
                $manager->notify(new FarrowingDueNotification($mating));
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('business')->error('Echec de detection des mises bas prochaines', [
            'error' => $exception->getMessage(),
        ]);
    }
}
