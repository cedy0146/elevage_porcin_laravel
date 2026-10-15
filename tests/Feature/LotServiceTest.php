<?php

use App\Enums\PenStatus;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\User;
use App\Services\LotService;
use Illuminate\Validation\ValidationException;

function lotPenContext(array $penAttributes = []): array
{
    $user = User::factory()->manager()->create();
    $pen = Pen::factory()->create(array_merge(['farm_id' => $user->farm_id, 'capacity' => 50], $penAttributes));

    return [$user, $pen];
}

function lotOpenData(Pen $pen, array $overrides = []): array
{
    return array_merge([
        'pen_id' => $pen->id,
        'code' => 'L-TEST-1',
        'source' => 'purchase',
        'initial_count' => 30,
        'initial_average_weight' => 20,
        'arrived_on' => now()->subDays(30)->toDateString(),
        'expected_end_on' => now()->addDays(60)->toDateString(),
    ], $overrides);
}

it('ouvre un lot et occupe la loge', function () {
    $service = app(LotService::class);
    [$user, $pen] = lotPenContext();

    $lot = $service->open(lotOpenData($pen), $user, 1_500_000.0);

    expect($lot->status->value)->toBe('active')
        ->and($lot->farm_id)->toBe($pen->farm_id)
        ->and($lot->pen_id)->toBe($pen->id)
        ->and($pen->fresh()->status)->toBe(PenStatus::Occupied);

    expect(Expense::where('pig_lot_id', $lot->id)->count())->toBe(1)
        ->and((float) Expense::where('pig_lot_id', $lot->id)->value('amount'))->toBe(1500000.0);
});

it('refuse une loge deja occupee', function () {
    $service = app(LotService::class);
    [$user, $pen] = lotPenContext(['status' => 'occupied']);

    expect(fn () => $service->open(lotOpenData($pen), $user))
        ->toThrow(ValidationException::class);

    expect(PigLot::count())->toBe(0);
});

it('refuse un effectif superieur a la capacite de la loge', function () {
    $service = app(LotService::class);
    [$user, $pen] = lotPenContext(['capacity' => 20]);

    expect(fn () => $service->open(lotOpenData($pen, ['initial_count' => 25]), $user))
        ->toThrow(ValidationException::class);

    expect(PigLot::count())->toBe(0)
        ->and($pen->fresh()->status)->toBe(PenStatus::Free);
});

it('refuse une loge d une autre exploitation', function () {
    $service = app(LotService::class);
    $manager = User::factory()->manager()->create();
    $foreignPen = Pen::factory()->create();

    expect($foreignPen->farm_id)->not->toBe($manager->farm_id)
        ->and(fn () => $service->open(lotOpenData($foreignPen), $manager))
        ->toThrow(ValidationException::class);

    expect(PigLot::count())->toBe(0);
});

it('clot un lot et libere la loge', function () {
    $service = app(LotService::class);
    [$user, $pen] = lotPenContext();

    $lot = $service->open(lotOpenData($pen), $user);
    $closed = $service->close($lot, $user);

    expect($closed->status->value)->toBe('completed')
        ->and($closed->closed_on)->not->toBeNull()
        ->and($pen->fresh()->status)->toBe(PenStatus::Free);
});

it('refuse une double cloture', function () {
    $service = app(LotService::class);
    [$user, $pen] = lotPenContext();

    $lot = $service->open(lotOpenData($pen), $user);
    $service->close($lot, $user);

    expect(fn () => $service->close($lot->fresh(), $user))
        ->toThrow(ValidationException::class);
});

it('reutilise une loge liberee par une cloture', function () {
    $service = app(LotService::class);
    [$user, $pen] = lotPenContext();

    $lot = $service->open(lotOpenData($pen), $user);
    $service->close($lot, $user);

    $second = $service->open(lotOpenData($pen, ['code' => 'L-TEST-2']), $user);

    expect($second->id)->not->toBe($lot->id)
        ->and($pen->fresh()->status)->toBe(PenStatus::Occupied);
});

it('les indicateurs sans pesee ni alimentation renvoient null', function () {
    $service = app(LotService::class);
    $user = User::factory()->manager()->create();

    $farm = Farm::factory()->create();
    $pen = Pen::factory()->create(['farm_id' => $farm->id]);

    $lot = PigLot::factory()->active()->create([
        'farm_id' => $farm->id,
        'pen_id' => $pen->id,
        'initial_count' => 20,
        'initial_average_weight' => 25,
        'arrived_on' => now()->subDays(40)->toDateString(),
    ]);

    $indicators = $service->indicators($lot);

    expect($indicators['current_count'])->toBe(20)
        ->and($indicators['age_days'])->toBe(40)
        ->and($indicators['mortality_rate'])->toBe(0.0)
        ->and($indicators['latest_average_weight'])->toBeNull()
        ->and($indicators['average_daily_gain'])->toBeNull()
        ->and($indicators['feed_conversion_ratio'])->toBeNull()
        ->and($indicators['feed_cost_per_kg_gain'])->toBeNull()
        ->and($indicators['total_feed_kg'])->toBe(0.0)
        ->and($indicators['feed_cost'])->toBe(0.0)
        ->and($indicators['revenue'])->toBe(0.0)
        ->and($indicators['margin'])->toBe(0.0);
});
