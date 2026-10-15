<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FarmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user !== null && $user->farm_id !== null) {
            $builder->where($model->getTable().'.farm_id', $user->farm_id);
        }
    }
}
