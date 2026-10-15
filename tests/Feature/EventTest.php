<?php

use App\Events\StockCritical;
use App\Jobs\DetectCriticalStocksJob;
use App\Models\Farm;
use App\Models\FeedType;
use App\Models\User;
use App\Notifications\StockCriticalNotification;
use App\Services\StockService;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('notifie les managers de la meme exploitation seulement', function () {
    Notification::fake();

    $farmA = Farm::factory()->create();
    $farmB = Farm::factory()->create();

    $managerA = User::factory()->manager()->create(['farm_id' => $farmA->id]);
    $technicianA = User::factory()->technician()->create(['farm_id' => $farmA->id]);
    $managerB = User::factory()->manager()->create(['farm_id' => $farmB->id]);

    $feedType = FeedType::factory()->create([
        'farm_id' => $farmA->id,
        'quantity' => 60,
        'minimum_threshold' => 50,
    ]);

    // 60 -> 40 : le seuil de 50 est franchi vers le bas, une seule notification.
    app(StockService::class)->issue($feedType, 20, $technicianA, 'Perte constatee');

    // 40 -> 30 : deja sous le seuil, pas de nouveau franchissement.
    app(StockService::class)->issue($feedType, 10, $technicianA, 'Perte constatee');

    Notification::assertSentTo($managerA, StockCriticalNotification::class);
    Notification::assertSentToTimes($managerA, StockCriticalNotification::class, 1);
    Notification::assertNotSentTo($managerB, StockCriticalNotification::class);
    Notification::assertNotSentTo($technicianA, StockCriticalNotification::class);
});

it('ne notifie pas quand le stock reste au dessus du seuil', function () {
    Notification::fake();

    $farm = Farm::factory()->create();
    $manager = User::factory()->manager()->create(['farm_id' => $farm->id]);
    $user = User::factory()->technician()->create(['farm_id' => $farm->id]);

    $feedType = FeedType::factory()->create([
        'farm_id' => $farm->id,
        'quantity' => 100,
        'minimum_threshold' => 50,
    ]);

    app(StockService::class)->issue($feedType, 20, $user, 'Perte constatee');

    Notification::assertNotSentTo($manager, StockCriticalNotification::class);
});

it('le job de detection declenche l evenement pour les stocks sous le seuil', function () {
    Event::fake([StockCritical::class]);

    $farm = Farm::factory()->create();
    User::factory()->manager()->create(['farm_id' => $farm->id]);

    FeedType::factory()->create([
        'farm_id' => $farm->id,
        'quantity' => 10,
        'minimum_threshold' => 50,
    ]);

    FeedType::factory()->create([
        'farm_id' => $farm->id,
        'quantity' => 500,
        'minimum_threshold' => 50,
    ]);

    (new DetectCriticalStocksJob)->handle();

    Event::assertDispatched(StockCritical::class, 1);
});

it('le job de detection est planifie quotidiennement', function () {
    Artisan::call('schedule:list');

    $output = Artisan::output();

    expect($output)->toContain('DetectCriticalStocksJob')
        ->and($output)->toContain('0 6 * * *');
});

it('les evenements sont dispatches apres le commit', function () {
    expect(is_subclass_of(
        StockCritical::class,
        ShouldDispatchAfterCommit::class
    ))->toBeTrue();
});
