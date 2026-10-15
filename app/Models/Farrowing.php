<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Database\Factories\FarrowingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mating_id', 'sow_id', 'farrowed_on', 'born_alive', 'stillborn', 'weaned_count', 'weaned_on', 'pig_lot_id'])]
class Farrowing extends Model
{
    /** @use HasFactory<FarrowingFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'farrowed_on' => 'date',
            'weaned_on' => 'date',
            'born_alive' => 'integer',
            'stillborn' => 'integer',
            'weaned_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Mating, $this> */
    public function mating(): BelongsTo
    {
        return $this->belongsTo(Mating::class);
    }

    /** @return BelongsTo<Breeder, $this> */
    public function sow(): BelongsTo
    {
        return $this->belongsTo(Breeder::class, 'sow_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PigLot, $this> */
    public function pigLot(): BelongsTo
    {
        return $this->belongsTo(PigLot::class);
    }
}
