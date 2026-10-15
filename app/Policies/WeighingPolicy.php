<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Weighing;

class WeighingPolicy
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
    public function view(User $user, Weighing $weighing): bool
    {
        return $user->farm_id === $weighing->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    /**
     * Le technicien enregistre les pesées.
     */
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Weighing $weighing): bool
    {
        return $user->farm_id === $weighing->farm_id
            && $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Weighing $weighing): bool
    {
        return $user->farm_id === $weighing->farm_id
            && $user->isManager();
    }
}
