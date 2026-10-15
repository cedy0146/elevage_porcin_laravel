<?php

namespace App\Models;

use App\Enums\LotStatus;
use Database\Factories\PigLotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pen_id',
    'code',
    'source',
    'initial_count',
    'initial_average_weight',
    'arrived_on',
    'expected_end_on',
    'notes',
])]
class PigLot extends Model
{
    /** @use HasFactory<PigLotFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_count' => 'integer',
            'initial_average_weight' => 'decimal:2',
            'arrived_on' => 'date',
            'expected_end_on' => 'date',
            'closed_on' => 'date',
            'status' => LotStatus::class,
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    /** @return HasMany<Feeding> */
    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class);
    }

    /** @return HasMany<Weighing> */
    public function weighings(): HasMany
    {
        return $this->hasMany(Weighing::class);
    }

    /** @return HasMany<Mortality> */
    public function mortalities(): HasMany
    {
        return $this->hasMany(Mortality::class);
    }

    /** @return HasMany<Activity> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return HasMany<Expense> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return HasMany<Sale> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** @return HasMany<StockMovement> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Effectif courant : initial moins les mortalites moins les tetes vendues.
     */
    protected function currentCount(): Attribute
    {
        return Attribute::get(function (): int {
            $mortality = $this->relationLoaded('mortalities')
                ? $this->mortalities->sum('count')
                : $this->mortalities()->sum('count');

            $sold = $this->relationLoaded('sales')
                ? $this->sales->sum('head_count')
                : $this->sales()->sum('head_count');

            return max(0, (int) $this->initial_count - (int) $mortality - (int) $sold);
        });
    }

    /**
     * Age du lot en jours depuis l'arrivee.
     */
    protected function ageInDays(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->arrived_on === null) {
                return 0;
            }

            return (int) $this->arrived_on->startOfDay()->diffInDays(now()->startOfDay());
        });
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', LotStatus::Active);
    }
}
