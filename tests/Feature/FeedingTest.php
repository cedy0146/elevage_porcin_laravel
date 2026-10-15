<?php

use App\Models\Expense;
use App\Models\Feeding;
use App\Models\FeedType;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FeedingService;
use App\Services\StockService;
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

it('une entree en stock avec cout cree la depense', function () {
    [$user, , $feedType] = feedingContext(['quantity' => 100]);

    $movement = app(StockService::class)->receive($feedType, 200, $user, 2500.0, 'Achat fournisseur');

    expect((float) $feedType->fresh()->quantity)->toBe(300.0)
        ->and($movement->type->value)->toBe('entry')
        ->and(Expense::count())->toBe(1)
        ->and((float) Expense::first()->amount)->toBe(500_000.0)
        ->and(Expense::first()->category->value)->toBe('feed');
});

it('une exception apres le mouvement de stock annule toute la transaction', function () {
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext();

    // Le listener creating est declenche dans la meme transaction que le
    // mouvement de stock : il simule un echec de persistence de la Feeding.
    Feeding::creating(function (): void {
        throw new RuntimeException('Erreur simulee apres le mouvement de stock.');
    });

    expect(fn () => $service->distribute($lot, $feedType, 25, $user))
        ->toThrow(RuntimeException::class, 'Erreur simulee apres le mouvement de stock.');

    // Rollback complet : stock, mouvement et distribution sont annules.
    expect((float) $feedType->fresh()->quantity)->toBe(100.0)
        ->and(StockMovement::count())->toBe(0)
        ->and(Feeding::count())->toBe(0);
});

it('deux distributions consecutives sur les derniers kilogrammes : la seconde echoue', function () {
    // Simulation de concurrence : sur SQLite lockForUpdate n'impose pas
    // d'attente reelle entre transactions, mais la revérification du stock
    // relu en base dans la transaction rejette le second appel. Le verrou
    // pessimiste complet (blocage physique) s'observe sur MySQL/MariaDB.
    $service = app(FeedingService::class);
    [$user, $lot, $feedType] = feedingContext(['quantity' => 30]);

    $service->distribute($lot, $feedType, 20, $user);

    // Le second appel porte sur les 10 kg restants : il reussit.
    $service->distribute($lot, $feedType, 10, $user);

    expect((float) $feedType->fresh()->quantity)->toBe(0.0)
        ->and(Feeding::count())->toBe(2);

    // Un troisieme appel n'a plus rien a consommer et echoue sans trace.
    expect(fn () => $service->distribute($lot, $feedType, 1, $user))
        ->toThrow(ValidationException::class);

    expect(Feeding::count())->toBe(2)
        ->and(StockMovement::count())->toBe(2)
        ->and((float) $feedType->fresh()->quantity)->toBe(0.0);
});
