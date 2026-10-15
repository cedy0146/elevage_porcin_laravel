<?php

namespace App\Models\Concerns;

use App\Models\Farm;
use App\Scopes\FarmScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolation SaaS : chaque modele métier est rattaché a une exploitation.
 * Le Global Scope filtre les lectures, l'evenement creating remplit farm_id.
 */
trait BelongsToFarm
{
    public static function bootBelongsToFarm(): void
    {
        static::addGlobalScope(new FarmScope);

        static::creating(function (Model $model): void {
            $user = auth()->user();

            if ($model->farm_id === null && $user?->farm_id !== null) {
                $model->farm_id = $user->farm_id;
            }
        });
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
