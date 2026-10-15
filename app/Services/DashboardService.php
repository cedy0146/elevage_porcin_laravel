<?php

namespace App\Services;

use App\Enums\HealthEventStatus;
use App\Models\Activity;
use App\Models\Farm;
use App\Models\FeedType;
use App\Models\HealthEvent;
use App\Models\Mortality;
use App\Models\Pen;
use App\Models\PigLot;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private FinanceService $finance,
        private LotService $lots,
        private StockAutonomyService $autonomy,
    ) {}

    /**
     * Tableau de bord agrégé d'une exploitation (cache 10 minutes).
     *
     * Les indicateurs sont mis en cache ; les activités récentes sont
     * toujours relues en base pour rester fraîches.
     *
     * @return array<string, mixed>
     */
    public function forFarm(Farm $farm): array
    {
        $dashboard = Cache::remember(
            'farm:'.$farm->id.':dashboard',
            (int) config('farm.dashboard_cache_ttl', 600),
            fn (): array => $this->compute($farm),
        );

        return array_merge($dashboard, [
            'recent_activities' => $this->recentActivities($farm),
        ]);
    }

    /**
     * Partie coûteuse du tableau de bord (sérialisable en cache).
     *
     * @return array<string, mixed>
     */
    private function compute(Farm $farm): array
    {
        $feedTypes = FeedType::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id)
            ->orderBy('name')
            ->get(['id', 'name', 'quantity', 'minimum_threshold']);

        $stocks = $feedTypes
            ->map(fn (FeedType $feedType): array => [
                'id' => $feedType->id,
                'name' => $feedType->name,
                'quantity' => round((float) $feedType->quantity, 2),
                'minimum_threshold' => round((float) $feedType->minimum_threshold, 2),
                'is_critical' => (float) $feedType->quantity <= (float) $feedType->minimum_threshold,
            ])
            ->values()
            ->all();

        $activeLots = PigLot::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id)
            ->active()
            ->with(['mortalities', 'sales', 'weighings', 'feedings', 'expenses'])
            ->get(['id', 'initial_count', 'initial_average_weight', 'arrived_on', 'created_at']);

        $lotIndicators = $activeLots
            ->map(fn (PigLot $lot): array => $this->lots->indicators($lot))
            ->values();

        $conversionRatios = $lotIndicators
            ->pluck('feed_conversion_ratio')
            ->filter()
            ->values();

        $averageConversionRatio = $conversionRatios->isNotEmpty()
            ? round($conversionRatios->avg(), 3)
            : null;

        $initialCountTotal = (int) $activeLots->sum('initial_count');
        $mortalityTotal = (int) Mortality::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id)
            ->whereIn('pig_lot_id', $activeLots->pluck('id'))
            ->selectRaw('COALESCE(SUM(count), 0) as total')
            ->value('total');

        $globalMortalityRate = $initialCountTotal > 0
            ? round($mortalityTotal / $initialCountTotal * 100, 2)
            : null;

        $mortalityAlertRate = (float) config('farm.mortality_alert_rate', 5.0);
        $mortalityAlerts = $activeLots
            ->filter(function (PigLot $lot) use ($mortalityAlertRate): bool {
                $initialCount = (int) $lot->initial_count;
                if ($initialCount === 0) {
                    return false;
                }

                $dead = (int) $lot->mortalities->sum('count');

                return $dead / $initialCount * 100 > $mortalityAlertRate;
            })
            ->pluck('id')
            ->values()
            ->all();

        $autonomy = $this->autonomy->forFarm($farm);

        $monthSummary = $this->finance->farmSummary($farm, now()->startOfMonth(), now()->endOfMonth());
        $totalSummary = $this->finance->farmSummary($farm);

        $alertStocks = collect($stocks)->filter(fn (array $stock): bool => $stock['is_critical'])->pluck('name')->values()->all();
        $alertAutonomy = $autonomy->filter(fn (array $item): bool => $item['status'] === 'critical')->pluck('name')->values()->all();

        $healthOverdueCount = HealthEvent::query()
            ->withoutGlobalScopes()
            ->join('vaccine_schedules', 'vaccine_schedules.id', '=', 'health_events.vaccine_schedule_id')
            ->where('health_events.farm_id', $farm->id)
            ->where('health_events.status', HealthEventStatus::Todo)
            ->whereNotNull('health_events.scheduled_on')
            ->whereRaw("date(health_events.scheduled_on, '+' || vaccine_schedules.tolerance_days || ' days') < date('now')")
            ->count();

        return [
            'active_lots_count' => $activeLots->count(),
            'pens_count' => Pen::query()->withoutGlobalScopes()->where('farm_id', $farm->id)->count(),
            'stocks' => $stocks,
            'expenses' => [
                'month' => $monthSummary['expenses'],
                'total' => $totalSummary['expenses'],
            ],
            'revenues' => [
                'month' => $monthSummary['revenues'],
                'total' => $totalSummary['revenues'],
            ],
            'margin' => [
                'month' => $monthSummary['margin'],
                'total' => $totalSummary['margin'],
            ],
            'average_feed_conversion_ratio' => $averageConversionRatio,
            'global_mortality_rate' => $globalMortalityRate,
            'stock_autonomy' => $autonomy->values()->all(),
            'alerts' => [
                'critical_stocks' => $alertStocks,
                'autonomy' => $alertAutonomy,
                'mortality_lots' => $mortalityAlerts,
                'health_overdue_count' => $healthOverdueCount,
            ],
        ];
    }

    /**
     * Activités récentes hors cache, avec chargement anticipé (N+1).
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentActivities(Farm $farm): array
    {
        return Activity::query()
            ->withoutGlobalScopes()
            ->where('farm_id', $farm->id)
            ->with(['user:id,name', 'pigLot:id,code'])
            ->latest('performed_on')
            ->latest('id')
            ->limit(10)
            ->get([
                'id', 'farm_id', 'pig_lot_id', 'user_id', 'type',
                'performed_on', 'description', 'cost',
            ])
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'type' => $activity->type->value,
                'performed_on' => $activity->performed_on?->toDateString(),
                'description' => $activity->description,
                'cost' => $activity->cost !== null ? round((float) $activity->cost, 2) : null,
                'lot' => $activity->pigLot?->code,
                'user' => $activity->user?->name,
            ])
            ->all();
    }
}
