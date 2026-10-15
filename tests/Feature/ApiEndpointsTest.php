<?php

use App\Jobs\GenerateLotReportJob;
use App\Models\Feeding;
use App\Models\FeedType;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

function apiLotContext(): array
{
    $manager = User::factory()->manager()->create();
    $pen = Pen::factory()->create(['farm_id' => $manager->farm_id, 'capacity' => 80]);

    $lot = PigLot::factory()->active()->create([
        'farm_id' => $manager->farm_id,
        'pen_id' => $pen->id,
        'code' => 'L-API-1',
        'initial_count' => 40,
    ]);

    $feedType = FeedType::factory()->create([
        'farm_id' => $manager->farm_id,
        'quantity' => 100,
        'unit_price' => 3000,
    ]);

    return [$manager, $lot, $feedType, $pen];
}

it('retourne une liste de lots paginee avec data links et meta', function () {
    [$manager, $lot] = apiLotContext();

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/v1/pig-lots')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'code', 'status', 'current_count', 'pen']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.0.code', 'L-API-1');
});

it('post feedings cree une distribution puis refuse un stock insuffisant', function () {
    [$manager, $lot, $feedType] = apiLotContext();

    $this->actingAs($manager, 'sanctum')
        ->postJson('/api/v1/feedings', [
            'pig_lot_id' => $lot->id,
            'feed_type_id' => $feedType->id,
            'quantity' => 30,
        ])
        ->assertCreated()
        ->assertJsonPath('data.quantity', '30.00');

    expect((float) $feedType->fresh()->quantity)->toBe(70.0);

    $this->actingAs($manager, 'sanctum')
        ->postJson('/api/v1/feedings', [
            'pig_lot_id' => $lot->id,
            'feed_type_id' => $feedType->id,
            'quantity' => 500,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);

    expect((float) $feedType->fresh()->quantity)->toBe(70.0)
        ->and(Feeding::count())->toBe(1)
        ->and(StockMovement::count())->toBe(1);
});

it('un technicien recoit 403 sur les finances', function () {
    $technician = User::factory()->technician()->create();

    $this->actingAs($technician, 'sanctum')->getJson('/api/v1/expenses')->assertForbidden();
    $this->actingAs($technician, 'sanctum')->getJson('/api/v1/sales')->assertForbidden();
    $this->actingAs($technician, 'sanctum')->postJson('/api/v1/expenses', [
        'category' => 'feed', 'amount' => 1000, 'spent_on' => now()->toDateString(),
    ])->assertForbidden();
});

it('un technicien recoit 403 sur les ecritures managers et peut distribuer', function () {
    [$manager, $lot, $feedType] = apiLotContext();
    $technician = User::factory()->technician()->create(['farm_id' => $manager->farm_id]);

    $this->actingAs($technician, 'sanctum')
        ->postJson('/api/v1/pens', ['code' => 'P-NEW', 'name' => 'Loge', 'capacity' => 30, 'type' => 'fattening'])
        ->assertForbidden();

    $this->actingAs($technician, 'sanctum')
        ->postJson('/api/v1/sales', [
            'pig_lot_id' => $lot->id,
            'head_count' => 1,
            'total_weight' => 100,
            'price_per_kg' => 9000,
            'customer_name' => 'Client',
            'sold_on' => now()->toDateString(),
        ])
        ->assertForbidden();

    // Le technicien enregistre les distributions au quotidien.
    $this->actingAs($technician, 'sanctum')
        ->postJson('/api/v1/feedings', [
            'pig_lot_id' => $lot->id,
            'feed_type_id' => $feedType->id,
            'quantity' => 5,
        ])
        ->assertCreated();
});

it('l admin recoit 403 sur les donnees metier', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/pig-lots')->assertForbidden();
    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/feed-types')->assertForbidden();
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/pens', [
        'code' => 'P-ADM', 'name' => 'Loge', 'capacity' => 30, 'type' => 'fattening',
    ])->assertForbidden();
});

it('un sort hors liste blanche n injecte jamais de sql', function () {
    [$manager, $lot] = apiLotContext();

    $response = $this->actingAs($manager, 'sanctum')
        ->getJson('/api/v1/pig-lots?sort=id;DROP TABLE pig_lots--&direction=asc')
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($lot->id);

    // La table existe toujours et l'ordre par défaut s'applique.
    expect(PigLot::count())->toBe(1);
});

it('les filtres et la pagination limitent la reponse', function () {
    [$manager, $lot, $feedType] = apiLotContext();

    $this->actingAs($manager, 'sanctum')
        ->postJson('/api/v1/feedings', [
            'pig_lot_id' => $lot->id,
            'feed_type_id' => $feedType->id,
            'quantity' => 10,
        ])->assertCreated();

    $this->actingAs($manager, 'sanctum')
        ->getJson("/api/v1/feedings?pig_lot_id={$lot->id}&per_page=1")
        ->assertOk()
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1);
});

it('le rapport de lot est genere asynchronement avec une reponse 202', function () {
    Bus::fake([GenerateLotReportJob::class]);

    [$manager, $lot] = apiLotContext();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/pig-lots/{$lot->id}/report")
        ->assertStatus(202);

    Bus::assertDispatched(GenerateLotReportJob::class);
});

it('un lot d une autre exploitation est introuvable', function () {
    [, $lot] = apiLotContext();
    $otherManager = User::factory()->manager()->create();

    $this->actingAs($otherManager, 'sanctum')
        ->getJson("/api/v1/pig-lots/{$lot->id}")
        ->assertNotFound();
});

it('l entree en stock est reservee au manager', function () {
    [$manager, , $feedType] = apiLotContext();
    $technician = User::factory()->technician()->create(['farm_id' => $manager->farm_id]);

    $this->actingAs($technician, 'sanctum')
        ->postJson("/api/v1/feed-types/{$feedType->id}/entries", ['quantity' => 50])
        ->assertForbidden();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/feed-types/{$feedType->id}/entries", ['quantity' => 50])
        ->assertCreated();

    expect((float) $feedType->fresh()->quantity)->toBe(150.0);
});
