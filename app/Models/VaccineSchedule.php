<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Database\Factories\VaccineScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'target_age_days', 'tolerance_days', 'is_active'])]
class VaccineSchedule extends Model
{
    /** @use HasFactory<VaccineScheduleFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_age_days' => 'integer',
            'tolerance_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function healthEvents(): HasMany
    {
        return $this->hasMany(HealthEvent::class);
    }
}
