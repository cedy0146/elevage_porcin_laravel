<?php

use App\Enums\HealthEventStatus;
use App\Enums\HealthEventType;
use App\Jobs\DetectOverdueHealthEventsJob;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\HealthEvent;
use App\Models\Pen;
use App\Models\PigLot;
use App\Models\User;
use App\Models\VaccineSchedule;
use App\Notifications\VaccinationOverdueNotification;
use App\Services\HealthService;
use App\Services\LotService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function healthContext(): array
{
    $user = User::factory()->manager()->create();
    $pen = Pen::factory()->create(['farm_id' => $user->farm_id, 'capacity' => 80]);

    VaccineSchedule::forceCreate([
        'farm_id' => $user->farm_id,
        'name' => 'Vaccin peste porcine classique',
        'target_age_days' => 45,
        'tolerance_days' => 2,
        'is_active' => true,
    ]);

    return [$user, $pen];
}

it('planifie les actes du calendrier a louverture dun lot', function () {
    [$user, $pen] = healthContext();

    $lot = app(LotService::class)->open([
        'pen_id' => $pen->id,
        'code' => 'L-HEALTH-1',
        'source' => 'purchase',
        'initial_count' => 30,
        'initial_average_weight' => 25,
        'arrived_on' => now()->subDays(10)->toDateString(),
        'expected_end_on' => now()->addDays(60)->toDateString(),
    ], $user);

    $event = HealthEvent::where('pig_lot_id', $lot->id)->firstOrFail();

    expect($event->status)->toBe(HealthEventStatus::Todo)
        ->and($event->type)->toBe(HealthEventType::Vaccination)
        ->and($event->name)->toBe('Vaccin peste porcine classique')
        ->and($event->scheduled_on->toDateString())->toBe(now()->subDays(10)->addDays(45)->toDateString());
});

it('refuse un doublon dacte sanitaire', function () {
    [$user, $pen] = healthContext();

    $lot = app(LotService::class)->open([
        'pen_id' => $pen->id,
        'code' => 'L-HEALTH-2',
        'source' => 'purchase',
        'initial_count' => 30,
        'initial_average_weight' => 25,
        'arrived_on' => now()->subDays(50)->toDateString(),
        'expected_end_on' => now()->addDays(30)->toDateString(),
    ], $user);

    $event = HealthEvent::where('pig_lot_id', $lot->id)->firstOrFail();

    app(HealthService::class)->markDone($event, $user, now());

    expect(fn () => app(HealthService::class)->markDone($event->fresh(), $user, now()))
        ->toThrow(ValidationException::class);
});

it('refuse un acte realise avant larrivee du lot', function () {
    [$user, $pen] = healthContext();

    $lot = app(LotService::class)->open([
        'pen_id' => $pen->id,
        'code' => 'L-HEALTH-3',
        'source' => 'purchase',
        'initial_count' => 30,
        'initial_average_weight' => 25,
        'arrived_on' => now()->subDays(20)->toDateString(),
        'expected_end_on' => now()->addDays(40)->toDateString(),
    ], $user);

    $event = HealthEvent::where('pig_lot_id', $lot->id)->firstOrFail();

    expect(fn () => app(HealthService::class)->markDone($event, $user, now()->subDays(25)))
        ->toThrow(ValidationException::class);
});

it('marque un acte fait avec calcul du retard et depense veterinaire', function () {
    [$user, $pen] = healthContext();

    $lot = app(LotService::class)->open([
        'pen_id' => $pen->id,
        'code' => 'L-HEALTH-4',
        'source' => 'purchase',
        'initial_count' => 30,
        'initial_average_weight' => 25,
        'arrived_on' => now()->subDays(50)->toDateString(),
        'expected_end_on' => now()->addDays(30)->toDateString(),
    ], $user);

    $event = HealthEvent::where('pig_lot_id', $lot->id)->firstOrFail();

    // Date prevue = arrivee + 45 = il y a 5 jours ; fait il y a 1 jour : retard de 4.
    $result = app(HealthService::class)->markDone($event, $user, now()->subDays(1), 45_000.0);

    expect($result['event']->status)->toBe(HealthEventStatus::Done)
        ->and($result['lateness_days'])->toBe(4)
        ->and($result['within_tolerance'])->toBeFalse()
        ->and(Expense::where('pig_lot_id', $lot->id)->count())->toBe(1);
});

it('detecte les actes en retard et notifie une seule fois les managers de la ferme', function () {
    Notification::fake();

    $farm = Farm::factory()->create();
    $manager = User::factory()->manager()->create(['farm_id' => $farm->id]);
    $technician = User::factory()->technician()->create(['farm_id' => $farm->id]);
    $otherManager = User::factory()->manager()->create();

    $schedule = VaccineSchedule::forceCreate([
        'farm_id' => $farm->id,
        'name' => 'Fer',
        'target_age_days' => 3,
        'tolerance_days' => 2,
        'is_active' => true,
    ]);

    $pen = Pen::factory()->create(['farm_id' => $farm->id]);
    $lot = PigLot::factory()->active()->create([
        'farm_id' => $farm->id,
        'pen_id' => $pen->id,
    ]);

    $overdue = HealthEvent::forceCreate([
        'farm_id' => $farm->id,
        'pig_lot_id' => $lot->id,
        'vaccine_schedule_id' => $schedule->id,
        'user_id' => $technician->id,
        'type' => 'vaccination',
        'name' => 'Fer',
        'scheduled_on' => now()->subDays(10)->toDateString(),
        'status' => 'todo',
    ]);

    (new DetectOverdueHealthEventsJob)->handle();

    Notification::assertSentTo($manager, VaccinationOverdueNotification::class);
    Notification::assertNotSentTo($otherManager, VaccinationOverdueNotification::class);
    Notification::assertNotSentTo($technician, VaccinationOverdueNotification::class);

    expect($overdue->fresh()->overdue_notified_at)->not->toBeNull();

    // Un second passage ne renvoie rien : overdue_notified_at est deja rempli.
    (new DetectOverdueHealthEventsJob)->handle();

    Notification::assertSentToTimes($manager, VaccinationOverdueNotification::class, 1);
});
