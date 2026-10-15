<?php

namespace App\Jobs;

use App\Events\StockCritical;
use App\Models\FeedType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Détecte quotidiennement les aliments sous le seuil minimum et déclenche
 * l'événement StockCritical pour chacun. Un job tourne sans utilisateur
 * connecté : les scopes globaux sont donc contournés.
 */
class DetectCriticalStocksJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array|int $backoff = [10, 30, 60];

    public function handle(): void
    {
        FeedType::query()
            ->withoutGlobalScopes()
            ->belowThreshold()
            ->whereHas('farm', function ($query): void {
                $query->where('status', 'active');
            })
            ->get()
            ->each(function (FeedType $feedType): void {
                event(new StockCritical($feedType));
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('business')->error('Echec du job de detection de stock critique', [
            'message' => $exception->getMessage(),
        ]);
    }
}
