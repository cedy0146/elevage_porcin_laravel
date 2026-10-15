<?php

namespace App\Listeners;

use App\Events\StockCritical;
use Illuminate\Support\Facades\Log;

class LogCriticalStock
{
    /**
     * Handle the event.
     */
    public function handle(StockCritical $event): void
    {
        Log::channel('business')->warning('Stock critique detecte', [
            'feed_type_id' => $event->feedType->id,
            'farm_id' => $event->feedType->farm_id,
            'quantity' => $event->feedType->quantity,
        ]);
    }
}
