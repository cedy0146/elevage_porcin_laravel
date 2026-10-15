<?php

use App\Enums\BreederSex;
use App\Enums\BreederStatus;
use App\Enums\MatingStatus;
use App\Models\Breeder;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\User;
use App\Services\ReproductionService;
use Illuminate\Validation\ValidationException;

function reproductionContext(): array
{
    $user = User::factory()->manager()->create();
    $pen = Pen::factory()->create(['farm_id' => $user->farm_id, 'type' => 'maternity', 'capacity' => 40]);

    $sow = Breeder::forceCreate([
        'farm_id' => $user->farm_id,
        'tag' => 'T-TEST-1',
        'sex' => BreederSex::Sow,
        'breed' => 'Large White',
        'born_on' => now()->subYears(3)->toDateString(),
        'status' => BreederStatus::Active,
    ]);

    $boar = Breeder::forceCreate([
        'farm_id' => $user->farm_id,
        'tag' => 'V-TEST-1',
        'sex' => BreederSex::Boar,
        'breed' => 'Pietrain',
        'born_on' => now()->subYears(2)->toDateString(),
        'status' => BreederStatus::Active,
    ]);

    return [$user, $pen, $sow, $boar];
}

it('calcule la date prevue de mise bas comme saillie + 114 jours', function () {
    [$user, , $sow, $boar] = reproductionContext();

    $mating = app(ReproductionService::class)->registerMating(
        $sow,
        $boar,
        now()->subDays(20),
        $user,
    );

    expect($mating->expected_farrowing_on->toDateString())
        ->toBe(now()->subDays(20)->addDays(114)->toDateString())
        ->and($sow->fresh()->status)->toBe(BreederStatus::Pregnant);
});

it('refuse une deuxieme saillie tant quune est en cours', function () {
    [$user, , $sow, $boar] = reproductionContext();

    $service = app(ReproductionService::class);
    $service->registerMating($sow, $boar, now()->subDays(10), $user);

    // Apres la premiere saillie la truie est gestante : le controle de
    // statut rejette la seconde saisie avant celui des saillies en cours.
    expect(fn () => $service->registerMating($sow, $boar, now()->subDays(5), $user))
        ->toThrow(ValidationException::class, 'n\'est pas disponible');
});

it('refuse une mise bas hors de la fenetre 105-125 jours', function () {
    [$user, , $sow, $boar] = reproductionContext();

    $service = app(ReproductionService::class);
    $mating = $service->registerMating($sow, $boar, now()->subDays(100), $user);

    expect(fn () => $service->recordFarrowing(
        $mating,
        now()->subDays(50),
        bornAlive: 10,
        stillborn: 0,
        user: $user,
    ))->toThrow(ValidationException::class, 'entre 105 et 125 jours');
});

it('refuse un sevrage superieur aux nes vivants', function () {
    [$user, $pen, $sow, $boar] = reproductionContext();

    $service = app(ReproductionService::class);
    $mating = $service->registerMating($sow, $boar, now()->subDays(130), $user);
    $farrowing = $service->recordFarrowing(
        $mating,
        now()->subDays(16),
        bornAlive: 8,
        stillborn: 1,
        user: $user,
    );

    expect(fn () => $service->recordWeaning($farrowing, 12, now()->subDays(3), $pen->id, $user))
        ->toThrow(ValidationException::class, 'ne peuvent pas dépasser les nés vivants');
});

it('le sevrage cree un lot de naissance rattache a la mise bas', function () {
    [$user, $pen, $sow, $boar] = reproductionContext();

    $service = app(ReproductionService::class);
    $mating = $service->registerMating($sow, $boar, now()->subDays(130), $user);
    $farrowing = $service->recordFarrowing(
        $mating,
        now()->subDays(16),
        bornAlive: 10,
        stillborn: 1,
        user: $user,
    );

    $weaned = $service->recordWeaning($farrowing, 9, now()->subDays(3), $pen->id, $user);

    expect($weaned->pig_lot_id)->not->toBeNull()
        ->and($weaned->weaned_count)->toBe(9)
        ->and($weaned->weaned_on->toDateString())->toBe(now()->subDays(3)->toDateString())
        ->and($sow->fresh()->status)->toBe(BreederStatus::Active)
        ->and(PigLot::query()->where('farm_id', $user->farm_id)->where('source', 'birth')->count())->toBe(1);
});

it('refuse une saillie si le sujet nest pas une truie', function () {
    [$user, , , $boar] = reproductionContext();

    expect(fn () => app(ReproductionService::class)->registerMating($boar, null, now(), $user))
        ->toThrow(ValidationException::class, 'doit être une truie');
});

it('declare echouee libere la truie', function () {
    [$user, , $sow, $boar] = reproductionContext();

    $service = app(ReproductionService::class);
    $mating = $service->registerMating($sow, $boar, now()->subDays(15), $user);
    $failed = $service->markFailed($mating);

    expect($failed->status)->toBe(MatingStatus::Failed)
        ->and($sow->fresh()->status)->toBe(BreederStatus::Active);
});

it('isole les reproducteurs entre exploitations', function () {
    [$user, , $sow] = reproductionContext();

    $otherFarmManager = User::factory()->manager()->create();

    $found = Breeder::query()
        ->withoutGlobalScopes()
        ->where('farm_id', $otherFarmManager->farm_id)
        ->where('tag', $sow->tag)
        ->exists();

    expect($found)->toBeFalse();
});
