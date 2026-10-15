<?php

use App\Models\Farm;
use App\Models\FeedType;
use App\Models\PigLot;
use App\Models\Sale;
use App\Models\User;

it('ne expose jamais les donnees d une autre exploitation', function () {
    $farmA = Farm::factory()->create();
    $farmB = Farm::factory()->create();

    $userA = User::factory()->manager()->create(['farm_id' => $farmA->id]);

    $lotB = PigLot::factory()->create(['farm_id' => $farmB->id]);
    $feedB = FeedType::factory()->create(['farm_id' => $farmB->id]);
    $saleB = Sale::factory()->create(['farm_id' => $farmB->id]);

    $this->actingAs($userA, 'sanctum');

    // Les listes ne contiennent aucune donnee de la ferme B.
    expect(PigLot::count())->toBe(0)
        ->and(FeedType::count())->toBe(0)
        ->and(Sale::count())->toBe(0);

    // Le route model binding (find) retourne null : la route respondrait 404.
    expect(PigLot::find($lotB->id))->toBeNull()
        ->and(FeedType::find($feedB->id))->toBeNull()
        ->and(Sale::find($saleB->id))->toBeNull();
});

it('remplit automatiquement farm_id depuis l utilisateur connecte', function () {
    $farm = Farm::factory()->create();
    $user = User::factory()->technician()->create(['farm_id' => $farm->id]);

    $this->actingAs($user, 'sanctum');

    $feedType = FeedType::factory()->create(['farm_id' => null]);

    expect($feedType->farm_id)->toBe($farm->id);
});

it('l admin voit toutes les donnees sans filtre de ferme', function () {
    $farmA = Farm::factory()->create();
    $farmB = Farm::factory()->create();

    PigLot::factory()->create(['farm_id' => $farmA->id]);
    PigLot::factory()->create(['farm_id' => $farmB->id]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'sanctum');

    expect(PigLot::count())->toBe(2);
});
