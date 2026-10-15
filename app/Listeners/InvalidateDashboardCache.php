<?php

namespace App\Listeners;

use App\Events\ExpenseRecorded;
use App\Events\FeedingRecorded;
use App\Events\LotCompleted;
use App\Events\MortalityRecorded;
use App\Events\SaleRecorded;
use App\Events\StockCritical;
use Illuminate\Support\Facades\Cache;

/**
 * Invalide le cache du tableau de bord dès qu'une donnée du dashboard change.
 * La clé correspond exactement à celle utilisée par DashboardService.
 */
class InvalidateDashboardCache
{
    /**
     * Handle the event.
     */
    public function handle(FeedingRecorded|LotCompleted|MortalityRecorded|SaleRecorded|ExpenseRecorded|StockCritical $event): void
    {
        $farmId = match (true) {
            $event instanceof StockCritical => $event->feedType->farm_id,
            $event instanceof FeedingRecorded => $event->feeding->farm_id,
            $event instanceof LotCompleted => $event->lot->farm_id,
            $event instanceof MortalityRecorded => $event->mortality->farm_id,
            $event instanceof SaleRecorded => $event->sale->farm_id,
            $event instanceof ExpenseRecorded => $event->expense->farm_id,
        };

        Cache::forget('farm:'.$farmId.':dashboard');
    }
}
