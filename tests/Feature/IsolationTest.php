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

it('un manager recoit 404 sur les ressources detaillees d une autre exploitation', function () {
    $farmA = Farm::factory()->create();
    $farmB = Farm::factory()->create();

    $userA = User::factory()->manager()->create(['farm_id' => $farmA->id]);

    $lotB = PigLot::factory()->create(['farm_id' => $farmB->id]);
    $feedB = FeedType::factory()->create(['farm_id' => $farmB->id]);
    $saleB = Sale::factory()->create(['farm_id' => $farmB->id]);

    $this->actingAs($userA, 'sanctum');

    $this->getJson("/api/v1/pig-lots/{$lotB->id}")->assertNotFound();
    $this->getJson("/api/v1/feed-types/{$feedB->id}")->assertNotFound();
    $this->getJson("/api/v1/sales/{$saleB->id}")->assertNotFound();
});

it('post feedings avec un feed type d une autre exploitation retourne 422', function () {
    $farmA = Farm::factory()->create();
    $farmB = Farm::factory()->create();

    $userA = User::factory()->manager()->create(['farm_id' => $farmA->id]);
    $lotA = PigLot::factory()->active()->create(['farm_id' => $farmA->id]);
    $feedB = FeedType::factory()->create(['farm_id' => $farmB->id]);

    $this->actingAs($userA, 'sanctum')
        ->postJson('/api/v1/feedings', [
            'pig_lot_id' => $lotA->id,
            'feed_type_id' => $feedB->id,
            'quantity' => 10,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['feed_type_id']);
});

it('aucune route de liste ne renvoie les donnees d une autre exploitation', function () {
    $farmA = Farm::factory()->create();
    $farmB = Farm::factory()->create();

    $userA = User::factory()->manager()->create(['farm_id' => $farmA->id]);

    // Une donnee distinctive dans la ferme B : sa reference unique ne doit
    // jamais apparaitre dans une reponse destinee a la ferme A.
    $lotB = PigLot::factory()->create([
        'farm_id' => $farmB->id,
        'code' => 'FARM-B-SECRET-LOT',
    ]);
    $feedB = FeedType::factory()->create([
        'farm_id' => $farmB->id,
        'name' => 'FARM-B-SECRET-FEED',
    ]);

    $this->actingAs($userA, 'sanctum');

    $endpoints = [
        '/api/v1/pig-lots',
        '/api/v1/feed-types',
        '/api/v1/pens',
        '/api/v1/sales',
        '/api/v1/expenses',
        '/api/v1/feedings',
        '/api/v1/activities',
        '/api/v1/stock-movements',
        '/api/v1/breeders',
        '/api/v1/matings',
        '/api/v1/farrowings',
        '/api/v1/health-events',
        '/api/v1/vaccine-schedules',
    ];

    foreach ($endpoints as $endpoint) {
        $response = $this->actingAs($userA, 'sanctum')->getJson($endpoint);

        $response->assertOk();

        expect($response->getContent())
            ->not->toContain('FARM-B-SECRET-LOT')
            ->and($response->getContent())
            ->not->toContain('FARM-B-SECRET-FEED')
            ->and($response->json('data'))
            ->toBeArray();
    }

    // Garde-fou : les objets B existent bien en base (le test n'est pas vide).
    expect(PigLot::withoutGlobalScopes()->count())->toBe(1)
        ->and(FeedType::withoutGlobalScopes()->count())->toBe(1)
        ->and($lotB->code)->toBe('FARM-B-SECRET-LOT')
        ->and($feedB->name)->toBe('FARM-B-SECRET-FEED');
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
