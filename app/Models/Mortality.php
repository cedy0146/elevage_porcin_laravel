<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Database\Factories\MortalityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pig_lot_id', 'died_on', 'count', 'cause'])]
class Mortality extends Model
{
    /** @use HasFactory<MortalityFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'died_on' => 'date',
            'count' => 'integer',
        ];
    }

    public function pigLot(): BelongsTo
    {
        return $this->belongsTo(PigLot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
