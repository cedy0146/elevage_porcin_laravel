<?php

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Farm;
use App\Models\FeedType;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\FeedingService;
use App\Services\LotService;
use Illuminate\Support\Facades\Cache;

it('construit le tableau de bord avec les agregats de l exploitation', function () {
    $user = User::factory()->manager()->create();
    $farm = Farm::find($user->farm_id);

    $pen = Pen::factory()->create(['farm_id' => $farm->id, 'capacity' => 50]);

    app(LotService::class)->open([
        'pen_id' => $pen->id,
        'code' => 'L-DASH-1',
        'source' => 'purchase',
        'initial_count' => 30,
        'initial_average_weight' => 20,
        'arrived_on' => now()->subDays(20)->toDateString(),
        'expected_end_on' => now()->addDays(40)->toDateString(),
    ], $user);

    $dashboard = app(DashboardService::class)->forFarm($farm);

    expect($dashboard['active_lots_count'])->toBe(1)
        ->and($dashboard['pens_count'])->toBe(1)
        ->and($dashboard['stocks'])->toBeArray()
        ->and($dashboard['expenses'])->toHaveKeys(['month', 'total'])
        ->and($dashboard['revenues'])->toHaveKeys(['month', 'total'])
        ->and($dashboard['margin'])->toHaveKeys(['month', 'total'])
        ->and($dashboard['alerts'])->toHaveKeys(['critical_stocks', 'autonomy', 'mortality_lots'])
        ->and($dashboard['stock_autonomy'])->toBeArray()
        ->and($dashboard)->toHaveKeys(['average_feed_conversion_ratio', 'global_mortality_rate', 'recent_activities']);
});

it('met le tableau de bord en cache et le recharge apres invalidation', function () {
    $farm = Farm::factory()->create();

    $dashboard = app(DashboardService::class)->forFarm($farm);

    expect(Cache::has('farm:'.$farm->id.':dashboard'))->toBeTrue();

    // Les indicateurs sont figes tant que le cache n'est pas invalide.
    $user = User::factory()->technician()->create(['farm_id' => $farm->id]);

    Activity::forceCreate([
        'farm_id' => $farm->id,
        'pig_lot_id' => null,
        'user_id' => $user->id,
        'type' => ActivityType::Other,
        'performed_on' => now()->toDateString(),
        'description' => 'Hors cache',
    ]);

    $again = app(DashboardService::class)->forFarm($farm);
    expect($again['active_lots_count'])->toBe($dashboard['active_lots_count'])
        ->and($again['recent_activities'])->toHaveCount(1)
        ->and($again['recent_activities'][0]['description'])->toBe('Hors cache');

    Cache::forget('farm:'.$farm->id.':dashboard');
    expect(Cache::has('farm:'.$farm->id.':dashboard'))->toBeFalse();
});

it('invalide le cache du tableau de bord apres une distribution', function () {
    $user = User::factory()->technician()->create();
    $farm = Farm::find($user->farm_id);

    $pen = Pen::factory()->create(['farm_id' => $farm->id, 'capacity' => 80]);
    $lot = PigLot::factory()->active()->create([
        'farm_id' => $farm->id,
        'pen_id' => $pen->id,
        'initial_count' => 30,
    ]);
    $feedType = FeedType::factory()->create([
        'farm_id' => $farm->id,
        'quantity' => 100,
        'unit_price' => 3000,
    ]);

    // Le dashboard est mis en cache par la premiere lecture.
    app(DashboardService::class)->forFarm($farm);
    expect(Cache::has('farm:'.$farm->id.':dashboard'))->toBeTrue();

    // L'evenement FeedingRecorded (apres commit) invalide la cle via le listener.
    app(FeedingService::class)->distribute($lot, $feedType, 25, $user);

    expect(Cache::has('farm:'.$farm->id.':dashboard'))->toBeFalse();

    // La lecture suivante reconstruit et remet en cache les indicateurs.
    app(DashboardService::class)->forFarm($farm);
    expect(Cache::has('farm:'.$farm->id.':dashboard'))->toBeTrue();
});
