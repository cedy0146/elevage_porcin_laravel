<?php

use App\Enums\StockMovementType;
use App\Models\Farm;
use App\Models\FeedType;
use App\Models\StockMovement;
use App\Services\StockAutonomyService;

function autonomyFeedType(float $quantity = 100.0): FeedType
{
    $farm = Farm::factory()->create();

    return FeedType::factory()->create([
        'farm_id' => $farm->id,
        'name' => 'Aliment finition',
        'quantity' => $quantity,
        'minimum_threshold' => 20,
    ]);
}

it('une autonomie sans consommation est nulle', function () {
    $feedType = autonomyFeedType();

    $result = app(StockAutonomyService::class)
        ->forFarm($feedType->farm)
        ->firstWhere('id', $feedType->id);

    expect($result['average_daily_consumption'])->toBe(0.0)
        ->and($result['days_remaining'])->toBeNull()
        ->and($result['runs_out_on'])->toBeNull()
        ->and($result['reorder_by'])->toBeNull()
        ->and($result['status'])->toBe('ok');
});

it('l autonomie est calculee a partir de la consommation des quatorze derniers jours', function () {
    $feedType = autonomyFeedType(100.0);

    StockMovement::factory()->create([
        'farm_id' => $feedType->farm_id,
        'feed_type_id' => $feedType->id,
        'type' => StockMovementType::Consumption,
        'quantity' => 70,
        'moved_at' => now()->subDays(7),
    ]);

    $result = app(StockAutonomyService::class)
        ->forFarm($feedType->farm)
        ->firstWhere('id', $feedType->id);

    // 70 kg consommés sur 14 jours -> 5 kg/jour -> 100 kg -> 20 jours
    expect($result['average_daily_consumption'])->toBe(5.0)
        ->and($result['days_remaining'])->toBe(20.0)
        ->and($result['runs_out_on'])->toBe(now()->addDays(20)->toDateString())
        ->and($result['reorder_by'])->toBe(now()->addDays(13)->toDateString())
        ->and($result['status'])->toBe('ok');
});

it('un stock epuise avant le delai de reapprovisionnement est critique', function () {
    $feedType = autonomyFeedType(10.0);

    StockMovement::factory()->create([
        'farm_id' => $feedType->farm_id,
        'feed_type_id' => $feedType->id,
        'type' => StockMovementType::Consumption,
        'quantity' => 56,
        'moved_at' => now()->subDays(3),
    ]);

    $result = app(StockAutonomyService::class)
        ->forFarm($feedType->farm)
        ->firstWhere('id', $feedType->id);

    // 56 kg / 14 j = 4 kg/j -> 10 kg -> 2.5 j -> critical (<= 7)
    expect($result['days_remaining'])->toBe(2.5)
        ->and($result['status'])->toBe('critical')
        ->and($result['reorder_by'])->not->toBeNull();
});

it('un stock epuise dans le double du delai est en alerte', function () {
    $feedType = autonomyFeedType(25.0);

    StockMovement::factory()->create([
        'farm_id' => $feedType->farm_id,
        'feed_type_id' => $feedType->id,
        'type' => StockMovementType::Consumption,
        'quantity' => 28,
        'moved_at' => now()->subDays(2),
    ]);

    $result = app(StockAutonomyService::class)
        ->forFarm($feedType->farm)
        ->firstWhere('id', $feedType->id);

    // 28 kg / 14 j = 2 kg/j -> 25 kg -> 12.5 j (warn : 7 < 12.5 <= 14)
    expect($result['days_remaining'])->toBe(12.5)
        ->and($result['status'])->toBe('warning');
});
