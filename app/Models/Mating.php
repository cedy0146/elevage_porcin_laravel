<?php

namespace App\Models;

use App\Enums\MatingStatus;
use App\Models\Concerns\BelongsToFarm;
use Database\Factories\MatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['sow_id', 'boar_id', 'mated_on', 'expected_farrowing_on'])]
class Mating extends Model
{
    /** @use HasFactory<MatingFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MatingStatus::class,
            'mated_on' => 'date',
            'expected_farrowing_on' => 'date',
        ];
    }

    /** @return BelongsTo<Breeder, $this> */
    public function sow(): BelongsTo
    {
        return $this->belongsTo(Breeder::class, 'sow_id');
    }

    /** @return BelongsTo<Breeder, $this> */
    public function boar(): BelongsTo
    {
        return $this->belongsTo(Breeder::class, 'boar_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<Farrowing, $this> */
    public function farrowing(): HasOne
    {
        return $this->hasOne(Farrowing::class);
    }
}
