<?php

use App\Models\Feeding;
use App\Models\FeedType;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FeedingService;
use Illuminate\Validation\ValidationException;

function feedingContext(array $feedAttributes = []): array
{
    $user = User::factory()->technician()->create();
    $pen = Pen::factory()->create(['farm_id' => $user->farm_id]);

    $lot = PigLot::factory()->active()->create([
        'farm_id' => $user->farm_id,
        'pen_id' => $pen->id,
    ]);

    $feedType = FeedType::factory()->create(array_merge([
        'farm_id' => $user->farm_id,
        'quantity' => 100,
        'unit_price' => 3000,
    ], $feedAttributes));

    return [$user, $lot, $feedType];
}

it('distribue de l aliment et decremente le stock', function () {
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext();

    $feeding = $service->distribute($lot, $feedType, 25, $user);

    expect((float) $feedType->fresh()->quantity)->toBe(75.0)
        ->and(StockMovement::count())->toBe(1)
        ->and($feeding->stockMovement)->not->toBeNull()
        ->and($feeding->pigLot->id)->toBe($lot->id)
        ->and($feeding->feedType->id)->toBe($feedType->id)
        ->and((float) $feeding->unit_cost)->toBe(3000.0)
        ->and((float) $feeding->total_cost)->toBe(75000.0)
        ->and($feeding->stockMovement->type->value)->toBe('consumption');
});

it('refuse une distribution si le stock est insuffisant sans rien enregistrer', function () {
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext();

    expect(fn () => $service->distribute($lot, $feedType, 500, $user))
        ->toThrow(ValidationException::class);

    expect((float) $feedType->fresh()->quantity)->toBe(100.0)
        ->and(StockMovement::count())->toBe(0)
        ->and(Feeding::count())->toBe(0);
});

it('accepte une distribution qui epuise exactement le stock', function () {
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext(['quantity' => 30]);

    $feeding = $service->distribute($lot, $feedType, 30, $user);

    expect((float) $feedType->fresh()->quantity)->toBe(0.0)
        ->and(Feeding::count())->toBe(1)
        ->and($feeding->id)->not->toBeNull();

    expect(fn () => $service->distribute($lot, $feedType, 1, $user))
        ->toThrow(ValidationException::class);

    expect(Feeding::count())->toBe(1);
});

it('refuse une distribution sur un lot clos', function () {
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext();

    $lot->forceFill(['status' => 'completed', 'closed_on' => now()->toDateString()])->save();

    expect(fn () => $service->distribute($lot->fresh(), $feedType, 5, $user))
        ->toThrow(ValidationException::class);

    expect(Feeding::count())->toBe(0)
        ->and((float) $feedType->fresh()->quantity)->toBe(100.0);
});

it('refuse une distribution d une quantite nulle ou negative', function () {
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext();

    expect(fn () => $service->distribute($lot, $feedType, 0, $user))
        ->toThrow(ValidationException::class);

    expect(Feeding::count())->toBe(0);
});
