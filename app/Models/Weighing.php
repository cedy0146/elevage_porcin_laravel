<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFarm;
use Database\Factories\WeighingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pig_lot_id', 'weighed_on', 'average_weight', 'sample_size'])]
class Weighing extends Model
{
    /** @use HasFactory<WeighingFactory> */
    use BelongsToFarm, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weighed_on' => 'date',
            'average_weight' => 'decimal:2',
            'sample_size' => 'integer',
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
