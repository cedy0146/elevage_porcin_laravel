<?php

namespace App\Policies;

use App\Models\Pen;
use App\Models\User;

class PenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pen $pen): bool
    {
        return $user->farm_id === $pen->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pen $pen): bool
    {
        return $user->farm_id === $pen->farm_id
            && $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pen $pen): bool
    {
        return $user->farm_id === $pen->farm_id
            && $user->isManager();
    }
}
