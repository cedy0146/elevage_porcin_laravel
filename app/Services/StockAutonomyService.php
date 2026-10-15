<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Farm;
use App\Models\FeedType;
use App\Models\StockMovement;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StockAutonomyService
{
    /**
     * Estime l'autonomie de chaque aliment de l'exploitation (module personnel).
     *
     * - average_daily_consumption : consommation moyenne par jour sur 14 jours
     * - days_remaining : quantité ÷ consommation moyenne (null sans consommation)
     * - runs_out_on : date d'épuisement estimée
     * - reorder_by : date limite de réapprovisionnement (épuisement − délai de réappro.)
     * - status : critical (épuisement avant le délai), warning (× 2 le délai), ok
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forFarm(Farm $farm, ?CarbonInterface $today = null): Collection
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $leadDays = (int) config('farm.restock_lead_days', 7);

        return FeedType::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id)
            ->orderBy('name')
            ->get(['id', 'name', 'quantity', 'minimum_threshold'])
            ->map(function (FeedType $feedType) use ($farm, $today, $leadDays) {
                $consumption = (float) StockMovement::query()
                    ->withoutGlobalScopes()
                    ->where('farm_id', $farm->id)
                    ->where('feed_type_id', $feedType->id)
                    ->where('type', StockMovementType::Consumption)
                    ->where('moved_at', '>=', $today->copy()->subDays(14))
                    ->sum('quantity');

                $averageDailyConsumption = round($consumption / 14, 2);
                $quantity = (float) $feedType->quantity;

                $daysRemaining = $averageDailyConsumption > 0
                    ? round($quantity / $averageDailyConsumption, 1)
                    : null;

                $runsOutOn = $daysRemaining !== null
                    ? $today->copy()->addDays((int) round($daysRemaining))
                    : null;

                $reorderBy = $runsOutOn?->copy()->subDays($leadDays);

                $status = match (true) {
                    $daysRemaining === null => 'ok',
                    $daysRemaining <= $leadDays => 'critical',
                    $daysRemaining <= $leadDays * 2 => 'warning',
                    default => 'ok',
                };

                return [
                    'id' => $feedType->id,
                    'name' => $feedType->name,
                    'quantity' => $quantity,
                    'average_daily_consumption' => $averageDailyConsumption,
                    'days_remaining' => $daysRemaining,
                    'runs_out_on' => $runsOutOn?->toDateString(),
                    'reorder_by' => $reorderBy?->toDateString(),
                    'status' => $status,
                ];
            })
            ->values();
    }
}
