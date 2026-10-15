<?php

namespace App\Policies;

use App\Models\Mortality;
use App\Models\User;

class MortalityPolicy
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
    public function view(User $user, Mortality $mortality): bool
    {
        return $user->farm_id === $mortality->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    /**
     * Le technicien enregistre les mortalités.
     */
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Mortality $mortality): bool
    {
        return $user->farm_id === $mortality->farm_id
            && $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Mortality $mortality): bool
    {
        return $user->farm_id === $mortality->farm_id
            && $user->isManager();
    }
}
