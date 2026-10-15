<?php

namespace App\Models;

use App\Enums\PenStatus;
use App\Enums\PenType;
use App\Models\Concerns\BelongsToFarm;
use Database\Factories\PenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'capacity', 'type'])]
class Pen extends Model
{
    /** @use HasFactory<PenFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'type' => PenType::class,
            'status' => PenStatus::class,
        ];
    }

    /** @return HasMany<PigLot> */
    public function pigLots(): HasMany
    {
        return $this->hasMany(PigLot::class);
    }
}
