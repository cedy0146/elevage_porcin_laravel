<?php

use App\Enums\ExpenseCategory;
use App\Models\Farm;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\Sale;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\LotService;
use App\Services\MortalityService;
use Illuminate\Validation\ValidationException;

function mortalityLotContext(int $initialCount = 20): array
{
    $user = User::factory()->manager()->create();
    $pen = Pen::factory()->create(['farm_id' => $user->farm_id]);

    $lot = PigLot::factory()->active()->create([
        'farm_id' => $user->farm_id,
        'pen_id' => $pen->id,
        'initial_count' => $initialCount,
    ]);

    return [$user, $lot, $pen];
}

it('enregistre une mortalite valide', function () {
    $service = app(MortalityService::class);
    [$user, $lot] = mortalityLotContext();

    $mortality = $service->record($lot, 2, 'grippe', $user, now());

    expect($mortality->count)->toBe(2)
        ->and($mortality->cause)->toBe('grippe')
        ->and($lot->fresh()->currentCount)->toBe(18);
});

it('refuse une mortalite superieure a l effectif', function () {
    $service = app(MortalityService::class);
    [$user, $lot] = mortalityLotContext(5);

    expect(fn () => $service->record($lot, 8, 'grippe', $user, now()))
        ->toThrow(ValidationException::class);
});

it('refuse une mortalite sur un lot clos', function () {
    $service = app(MortalityService::class);
    [$user, $lot] = mortalityLotContext();

    $lot->forceFill(['status' => 'completed', 'closed_on' => now()->toDateString()])->save();

    expect(fn () => $service->record($lot->fresh(), 1, 'grippe', $user, now()))
        ->toThrow(ValidationException::class);
});

it('une vente qui vide le lot cloture le lot et libere la loge', function () {
    $finance = app(FinanceService::class);
    $lots = app(LotService::class);
    [$user, $lot, $pen] = mortalityLotContext(10);

    $sale = $finance->recordSale(
        lot: $lot,
        headCount: 10,
        totalWeight: 950.0,
        pricePerKg: 9000.0,
        customerName: 'Socopa',
        user: $user,
        soldOn: now(),
    );

    expect((float) $sale->amount)->toBe(8_550_000.0)
        ->and($lot->fresh()->status->value)->toBe('completed')
        ->and($lot->fresh()->currentCount)->toBe(0)
        ->and($pen->fresh()->status->value)->toBe('free')
        ->and(Sale::count())->toBe(1);
});

it('refuse une vente superieure a l effectif', function () {
    $finance = app(FinanceService::class);
    [$user, $lot] = mortalityLotContext(5);

    expect(fn () => $finance->recordSale(
        lot: $lot,
        headCount: 8,
        totalWeight: 700.0,
        pricePerKg: 9000.0,
        customerName: 'Socopa',
        user: $user,
        soldOn: now(),
    ))->toThrow(ValidationException::class);
});

it('le resume financier additionne ventes et depenses par periode', function () {
    $finance = app(FinanceService::class);
    $user = User::factory()->manager()->create();

    $farm = Farm::find($user->farm_id);

    $finance->recordExpense($farm, ExpenseCategory::Veterinary, 50_000, now(), $user);
    $finance->recordExpense($farm, ExpenseCategory::Veterinary, 20_000, now()->subMonths(2), $user);

    $pen = Pen::factory()->create(['farm_id' => $farm->id]);
    $lot = PigLot::factory()->active()->create([
        'farm_id' => $farm->id,
        'pen_id' => $pen->id,
        'initial_count' => 10,
    ]);

    $finance->recordSale($lot, 5, 400.0, 10_000.0, 'Socopa', $user, now());
    $finance->recordSale($lot, 5, 350.0, 10_000.0, 'Socopa', $user, now()->subMonths(2));

    $month = $finance->farmSummary($farm, now()->startOfMonth(), now()->endOfMonth());
    $total = $finance->farmSummary($farm);

    expect($month)->toBe(['revenues' => 4000000.0, 'expenses' => 50000.0, 'margin' => 3950000.0])
        ->and($total['revenues'])->toBe(7500000.0)
        ->and($total['expenses'])->toBe(70000.0)
        ->and($total['margin'])->toBe(7430000.0);
});
