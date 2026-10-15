<?php

namespace App\Policies;

use App\Models\Feeding;
use App\Models\User;

class FeedingPolicy
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
    public function view(User $user, Feeding $feeding): bool
    {
        return $user->farm_id === $feeding->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    /**
     * Le technicien enregistre les distributions.
     */
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Feeding $feeding): bool
    {
        return $user->farm_id === $feeding->farm_id
            && $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Feeding $feeding): bool
    {
        return $user->farm_id === $feeding->farm_id
            && $user->isManager();
    }
}
