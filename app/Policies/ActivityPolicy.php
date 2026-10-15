<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
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
    public function view(User $user, Activity $activity): bool
    {
        return $user->farm_id === $activity->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    /**
     * Le technicien enregistre les activités.
     */
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        return $user->farm_id === $activity->farm_id
            && $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $user->farm_id === $activity->farm_id
            && $user->isManager();
    }
}
