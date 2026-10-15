<?php

namespace App\Models;

use App\Enums\FeedStage;
use App\Models\Concerns\BelongsToFarm;
use Database\Factories\FeedTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'stage', 'unit', 'unit_price', 'minimum_threshold'])]
class FeedType extends Model
{
    /** @use HasFactory<FeedTypeFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => FeedStage::class,
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'minimum_threshold' => 'decimal:2',
        ];
    }

    /** @return HasMany<StockMovement> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasMany<Feeding> */
    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class);
    }

    /**
     * Aliments dont le stock est tombe sous le seuil minimum.
     */
    #[Scope]
    protected function belowThreshold(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'minimum_threshold');
    }
}
