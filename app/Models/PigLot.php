<?php

namespace App\Models;

use App\Enums\LotStatus;
use App\Models\Concerns\BelongsToFarm;
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
    use BelongsToFarm, HasFactory;

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

    /**
     * Recherche partielle sur le code du lot.
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where('code', 'like', '%'.$term.'%');
    }

    /**
     * Filtre par statut (valeur d'enum déjà validée en amont).
     */
    #[Scope]
    protected function statusIs(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    protected function forPen(Builder $query, int $penId): void
    {
        $query->where('pen_id', $penId);
    }

    #[Scope]
    protected function arrivedFrom(Builder $query, ?string $from): void
    {
        if ($from !== null) {
            $query->where('arrived_on', '>=', $from);
        }
    }

    #[Scope]
    protected function arrivedTo(Builder $query, ?string $to): void
    {
        if ($to !== null) {
            $query->where('arrived_on', '<=', $to);
        }
    }

    /**
     * Tri protégé : la colonne est validée contre une liste blanche.
     *
     * @var array<int, string>
     */
    public const SORTABLE = ['code', 'arrived_on', 'status'];

    #[Scope]
    protected function sortApi(Builder $query, string $sort, string $direction): void
    {
        $column = in_array($sort, self::SORTABLE, true) ? $sort : 'created_at';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}
