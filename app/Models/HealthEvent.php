<?php

namespace App\Models;

use App\Enums\HealthEventStatus;
use App\Enums\HealthEventType;
use App\Models\Concerns\BelongsToFarm;
use Database\Factories\HealthEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pig_lot_id',
    'breeder_id',
    'vaccine_schedule_id',
    'type',
    'name',
    'scheduled_on',
    'notes',
])]
class HealthEvent extends Model
{
    /** @use HasFactory<HealthEventFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HealthEventType::class,
            'status' => HealthEventStatus::class,
            'scheduled_on' => 'date',
            'done_on' => 'date',
            'cost' => 'decimal:2',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function pigLot(): BelongsTo
    {
        return $this->belongsTo(PigLot::class);
    }

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }

    public function vaccineSchedule(): BelongsTo
    {
        return $this->belongsTo(VaccineSchedule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Actes à faire dont la date prévue + la tolérance du calendrier
     * est déjà dépassée (la tolérance vient de vaccine_schedules).
     * Requête SQLite : date(scheduled_on, '+N days') < date('now').
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query
            ->join('vaccine_schedules', 'vaccine_schedules.id', '=', 'health_events.vaccine_schedule_id')
            ->where('health_events.status', HealthEventStatus::Todo)
            ->whereNotNull('health_events.scheduled_on')
            ->whereNull('health_events.overdue_notified_at')
            ->whereRaw("date(health_events.scheduled_on, '+' || vaccine_schedules.tolerance_days || ' days') < date('now')")
            ->select('health_events.*');
    }
}
