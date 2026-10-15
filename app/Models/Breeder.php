<?php

namespace App\Models;

use App\Enums\BreederSex;
use App\Enums\BreederStatus;
use App\Models\Concerns\BelongsToFarm;
use Database\Factories\BreederFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tag', 'sex', 'breed', 'born_on'])]
class Breeder extends Model
{
    /** @use HasFactory<BreederFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sex' => BreederSex::class,
            'status' => BreederStatus::class,
            'born_on' => 'date',
        ];
    }

    /** @return HasMany<Mating, $this> */
    public function sowMatings(): HasMany
    {
        return $this->hasMany(Mating::class, 'sow_id');
    }

    /** @return HasMany<Mating, $this> */
    public function boarMatings(): HasMany
    {
        return $this->hasMany(Mating::class, 'boar_id');
    }

    /** @return HasMany<Farrowing, $this> */
    public function farrowings(): HasMany
    {
        return $this->hasMany(Farrowing::class, 'sow_id');
    }
}
